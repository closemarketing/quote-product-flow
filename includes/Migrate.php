<?php
/**
 * Data migration and repair utilities (1.x → 2.0.0+)
 *
 * Functions provided:
 *
 * qpfw_migrate_cpt_slugs()       – Standard migration: renames PBC post types,
 *                                   taxonomy and meta keys to qpfw_* prefixes.
 *                                   Runs once; safe to re-run via the admin button.
 *
 * qpfw_repair_stale_phase_refs() – Repair tool: fixes sites where phases were
 *                                   deleted and recreated after migration (new IDs),
 *                                   leaving variation qpfw_phase values broken.
 *                                   Uses enquiry history to infer the correct mapping,
 *                                   creates missing phases, deduplicates meta entries
 *                                   and removes dependency references to deleted posts.
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2024 Closemarketing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Recursively rename all array keys with a pbc_ prefix to qpfw_.
 *
 * @param mixed $data The value to transform (array or scalar).
 * @return mixed
 */
function qpfw_migrate_rename_pbc_keys( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}
	$result = array();
	foreach ( $data as $key => $value ) {
		$new_key            = is_string( $key ) && 0 === strpos( $key, 'pbc_' )
			? 'qpfw_' . substr( $key, 4 )
			: $key;
		$result[ $new_key ] = qpfw_migrate_rename_pbc_keys( $value );
	}
	return $result;
}

/**
 * Migrate legacy CPT slugs and meta keys to prefixed versions.
 *
 * Checks first whether legacy posts exist before touching the database.
 * Stores a flag in wp_options so it only runs once.
 */
function qpfw_migrate_cpt_slugs() {
	if ( get_option( 'qpfw_cpt_migration_done' ) ) {
		return;
	}

	global $wpdb;

	$migrated = false;

	// 1. Migrate post types.
	$legacy_post_types = array(
		'phases'    => 'qpfw_phases',
		'variation' => 'qpfw_variation',
		'enquiry'   => 'qpfw_enquiry',
	);

	foreach ( $legacy_post_types as $old_slug => $new_slug ) {
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s",
				$old_slug
			)
		);

		if ( $count > 0 ) {
			$wpdb->update(
				$wpdb->posts,
				array( 'post_type' => $new_slug ),
				array( 'post_type' => $old_slug ),
				array( '%s' ),
				array( '%s' )
			);
			$migrated = true;
		}
	}

	// 2. Migrate taxonomy: variation_tag → qpfw_variation_tag.
	$tax_count = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'variation_tag'"
	);

	if ( $tax_count > 0 ) {
		$wpdb->update(
			$wpdb->term_taxonomy,
			array( 'taxonomy' => 'qpfw_variation_tag' ),
			array( 'taxonomy' => 'variation_tag' ),
			array( '%s' ),
			array( '%s' )
		);
		$migrated = true;
	}

	// 3. Migrate meta keys: pbc_* → qpfw_*.
	$pbc_count = (int) $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE 'pbc\_%'"
	);

	if ( $pbc_count > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"UPDATE {$wpdb->postmeta}
			SET meta_key = CONCAT('qpfw_', SUBSTRING(meta_key, 5))
			WHERE meta_key LIKE 'pbc\_%'"
		);
		$migrated = true;
	}

	// 4. Migrate serialized array keys inside meta values: pbc_* → qpfw_*.
	// The UPDATE above renames meta_key names but leaves pbc_* keys inside
	// serialized arrays (e.g. qpfw_depends stored ['pbc_depvar' => ...]).
	$array_meta_keys = array(
		'qpfw_depends',
		'qpfw_pricegroup',
		'qpfw_imgprodgroup',
		'qpfw_question_depends',
	);

	foreach ( $array_meta_keys as $meta_key ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
				$meta_key
			)
		);

		if ( empty( $rows ) ) {
			continue;
		}

		foreach ( $rows as $row ) {
			$value = maybe_unserialize( $row->meta_value );

			if ( ! is_array( $value ) ) {
				continue;
			}

			$new_value = qpfw_migrate_rename_pbc_keys( $value );

			if ( $new_value === $value ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->postmeta,
				array( 'meta_value' => maybe_serialize( $new_value ) ),
				array( 'meta_id' => (int) $row->meta_id ),
				array( '%s' ),
				array( '%d' )
			);
			$migrated = true;
		}
	}

	if ( $migrated ) {
		flush_rewrite_rules();
	}

	update_option( 'qpfw_cpt_migration_done', '2.0.0' );
}

/**
 * Repair stale phase references when phases were deleted and recreated after migration.
 *
 * This handles sites where the standard migration already ran but the old qpfw_phases
 * posts were later deleted and replaced with new ones (new IDs). Variations still
 * reference the deleted phase IDs via qpfw_phase meta.
 *
 * Algorithm:
 *  1. Detect variations whose qpfw_phase points to a non-existent post.
 *  2. Infer the correct phase name for each broken group using enquiry history
 *     (enquiries store "PhaseName: VariationTitle" strings we can match against).
 *  3. Map to the existing qpfw_phases post with that title, or create a new one.
 *  4. Update qpfw_phase for all affected variations.
 *  5. Deduplicate repeated qpfw_* meta rows (left over from repeated imports).
 *  6. Remove qpfw_depends items that reference variation IDs that no longer exist.
 *
 * @return array Stats: fixed_phases, created_phases, deduped_rows, fixed_depends.
 */
function qpfw_repair_stale_phase_refs() {
	global $wpdb;

	// ── 1. Find old phase IDs referenced by variations but not present as posts ──

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	$broken_phase_ids = $wpdb->get_col(
		"SELECT DISTINCT pm.meta_value
		 FROM {$wpdb->postmeta} pm
		 LEFT JOIN {$wpdb->posts} p
		   ON p.ID = pm.meta_value AND p.post_type = 'qpfw_phases'
		 WHERE pm.meta_key = 'qpfw_phase'
		   AND p.ID IS NULL"
	);

	$stats = array(
		'fixed_phases'      => 0,
		'created_phases'    => 0,
		'deduped_rows'      => 0,
		'fixed_depends'     => 0,
		'fixed_pricegroups' => 0,
	);

	if ( ! empty( $broken_phase_ids ) ) {

		// ── 2. Infer phase title for each broken old phase ID ──────────────────
		// Enquiries store meta like qpfw_phase_var_N = "PhaseName: VariationTitle".
		// For each broken phase group we collect which phase names appear most
		// often when matching the variation titles.

		$old_to_title = array();

		foreach ( $broken_phase_ids as $old_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$var_titles = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT p2.post_title
					 FROM {$wpdb->postmeta} pm2
					 JOIN {$wpdb->posts} p2 ON p2.ID = pm2.post_id
					 WHERE pm2.meta_key   = 'qpfw_phase'
					   AND pm2.meta_value = %s
					   AND p2.post_type   = 'qpfw_variation'
					   AND p2.post_status != 'trash'",
					$old_id
				)
			);

			if ( empty( $var_titles ) ) {
				continue;
			}

			$phase_name_votes = array();

			foreach ( $var_titles as $vtitle ) {
				$like = '%: ' . $wpdb->esc_like( $vtitle );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$matched_phase_names = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT SUBSTRING_INDEX(meta_value, ': ', 1)
						 FROM {$wpdb->postmeta}
						 WHERE meta_key LIKE 'qpfw_phase_var_%'
						   AND meta_value LIKE %s",
						$like
					)
				);
				foreach ( $matched_phase_names as $pname ) {
					$pname = trim( $pname );
					if ( $pname ) {
						$phase_name_votes[ $pname ] = ( isset( $phase_name_votes[ $pname ] ) ? $phase_name_votes[ $pname ] : 0 ) + 1;
					}
				}
			}

			if ( empty( $phase_name_votes ) ) {
				continue;
			}

			arsort( $phase_name_votes );
			reset( $phase_name_votes );
			$old_to_title[ $old_id ] = key( $phase_name_votes );
		}

		// ── 3. Build title → current phase ID map ──────────────────────────────
		$current_phases = get_posts(
			array(
				'post_type'      => 'qpfw_phases',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		$title_to_new_id = array();
		foreach ( $current_phases as $cp ) {
			$title_to_new_id[ $cp->post_title ] = $cp->ID;
		}

		// Find the parent phase (root container) for any phases we may create.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$parent_phase_id = (int) $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'qpfw_phases'
			   AND post_parent = 0
			   AND post_status = 'publish'
			 ORDER BY menu_order ASC
			 LIMIT 1"
		);

		// ── 4. Build old_id → new_id map; create missing phases ────────────────
		$old_to_new = array();

		foreach ( $old_to_title as $old_id => $title ) {
			if ( isset( $title_to_new_id[ $title ] ) ) {
				$old_to_new[ $old_id ] = $title_to_new_id[ $title ];
			} else {
				// Phase does not exist → create it.
				$new_id = wp_insert_post(
					array(
						'post_title'   => $title,
						'post_type'    => 'qpfw_phases',
						'post_status'  => 'publish',
						'post_parent'  => $parent_phase_id,
						'menu_order'   => 99,
					)
				);

				if ( ! is_wp_error( $new_id ) && $new_id > 0 ) {
					$old_to_new[ $old_id ]    = $new_id;
					$title_to_new_id[ $title ] = $new_id;
					++$stats['created_phases'];
				}
			}
		}

		// ── 5. Update qpfw_phase for all affected variations ───────────────────
		foreach ( $old_to_new as $old_id => $new_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows_updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->postmeta}
					 SET meta_value = %d
					 WHERE meta_key   = 'qpfw_phase'
					   AND meta_value = %s",
					$new_id,
					$old_id
				)
			);
			$stats['fixed_phases'] += (int) $rows_updated;
		}
	}

	// ── 6. Deduplicate repeated qpfw_* meta rows ──────────────────────────────
	// A repeated import can leave N identical (post_id, meta_key) rows.
	// Keep only the row with the lowest meta_id.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	$deduped = (int) $wpdb->query(
		"DELETE pm_dup
		 FROM {$wpdb->postmeta} pm_dup
		 INNER JOIN {$wpdb->postmeta} pm_keep
		   ON  pm_dup.post_id   = pm_keep.post_id
		   AND pm_dup.meta_key  = pm_keep.meta_key
		   AND pm_dup.meta_id   > pm_keep.meta_id
		 WHERE pm_dup.meta_key LIKE 'qpfw_%'"
	);
	$stats['deduped_rows'] = $deduped;

	// ── 7. Remap qpfw_depends IDs from old production IDs to current local IDs ─
	// When data is imported from a production site and variations receive new
	// auto-increment IDs, the qpfw_depends values still reference the original
	// production IDs. We resolve the mapping via the post guid field, which
	// WordPress preserves from the source site and embeds the original ID as
	// the ?p=N query parameter.
	//
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	$guid_map_rows = $wpdb->get_results(
		"SELECT
		     CAST(SUBSTRING_INDEX(guid, 'p=', -1) AS UNSIGNED) AS old_id,
		     ID AS new_id
		 FROM {$wpdb->posts}
		 WHERE post_type IN ('qpfw_variation', 'variation')
		   AND post_status != 'trash'
		   AND guid LIKE '%p=%'
		   AND CAST(SUBSTRING_INDEX(guid, 'p=', -1) AS UNSIGNED) != ID"
	);

	$id_remap = array();
	foreach ( $guid_map_rows as $gm ) {
		$old = (int) $gm->old_id;
		$new = (int) $gm->new_id;
		if ( $old > 0 && $new > 0 ) {
			$id_remap[ $old ] = $new;
		}
	}

	if ( ! empty( $id_remap ) ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$dep_rows = $wpdb->get_results(
			"SELECT meta_id, meta_value
			 FROM {$wpdb->postmeta}
			 WHERE meta_key   = 'qpfw_depends'
			   AND meta_value NOT IN ('a:0:{}', '')"
		);

		foreach ( $dep_rows as $row ) {
			$depends = maybe_unserialize( $row->meta_value );
			if ( ! is_array( $depends ) || empty( $depends ) ) {
				continue;
			}

			$new_depends = array();
			$changed     = false;

			foreach ( $depends as $dep ) {
				if ( ! is_array( $dep ) || empty( $dep['qpfw_depvar'] ) ) {
					$new_depends[] = $dep;
					continue;
				}
				$parts  = explode( '|', $dep['qpfw_depvar'] );
				$var_id = isset( $parts[1] ) ? (int) $parts[1] : 0;
				if ( $var_id > 0 && isset( $id_remap[ $var_id ] ) ) {
					$dep['qpfw_depvar'] = $parts[0] . '|' . $id_remap[ $var_id ];
					$changed = true;
				}
				$new_depends[] = $dep;
			}

			if ( $changed ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$wpdb->postmeta,
					array( 'meta_value' => maybe_serialize( array_values( $new_depends ) ) ),
					array( 'meta_id' => (int) $row->meta_id ),
					array( '%s' ),
					array( '%d' )
				);
				++$stats['fixed_depends'];
			}
		}
	}

	// ── 8. Clean up corrupted qpfw_pricegroup rows (all-empty duplicates from doubling bug) ──
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
	$pg_rows = $wpdb->get_results(
		"SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'qpfw_pricegroup' AND meta_value NOT IN ('a:0:{}','')"
	);
	foreach ( $pg_rows as $pg_row ) {
		$pg = maybe_unserialize( $pg_row->meta_value );
		if ( ! is_array( $pg ) || empty( $pg ) ) {
			continue;
		}
		$clean   = array();
		$changed = false;
		foreach ( $pg as $entry ) {
			if ( ! is_array( $entry ) ) {
				$clean[] = $entry;
				continue;
			}
			$meaprice = isset( $entry['qpfw_meaprice'] ) ? $entry['qpfw_meaprice'] : null;
			$pricem   = isset( $entry['qpfw_pricem'] ) ? $entry['qpfw_pricem'] : null;
			// Drop rows where both fields exist but are both empty strings (doubling-bug artifact).
			if ( '' === $meaprice && '' === $pricem ) {
				$changed = true;
				continue;
			}
			$clean[] = $entry;
		}
		if ( $changed ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->postmeta,
				array( 'meta_value' => maybe_serialize( array_values( $clean ) ) ),
				array( 'meta_id' => (int) $pg_row->meta_id ),
				array( '%s' ),
				array( '%d' )
			);
			++$stats['fixed_pricegroups'];
		}
	}

	flush_rewrite_rules();

	return $stats;
}
