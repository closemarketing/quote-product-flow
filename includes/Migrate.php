<?php
/**
 * Data migration for CPT slug renaming (1.x → 2.0.0)
 *
 * Renames post types: phases → qpfw_phases, variation → qpfw_variation, enquiry → qpfw_enquiry
 * Renames taxonomy:   variation_tag → qpfw_variation_tag
 * Renames meta keys:  pbc_* → qpfw_*
 *
 * Runs once on plugin activation (or update to 2.0.0) only when legacy data exists.
 *
 * @package    WordPress
 * @author     David Perez <david@close.technology>
 * @copyright  2024 Closemarketing
 */

defined( 'ABSPATH' ) || exit;

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

	if ( $migrated ) {
		flush_rewrite_rules();
	}

	update_option( 'qpfw_cpt_migration_done', '2.0.0' );
}
