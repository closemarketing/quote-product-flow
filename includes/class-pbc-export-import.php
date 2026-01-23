<?php
/**
 * Class Export Import
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class for Export Import
 */
class PBC_Export_Import {
	/**
	 * Construct and intialize
	 */
	public function __construct() {
		// Generate slugs when saving posts.
		add_action( 'save_post_phases', array( $this, 'generate_phase_slug' ), 10, 3 );
		add_action( 'save_post_variation', array( $this, 'generate_variation_slug' ), 10, 3 );

		// AJAX handlers for export/import.
		add_action( 'wp_ajax_pbc_export_data', array( $this, 'export_data_ajax' ) );
		add_action( 'wp_ajax_pbc_import_data', array( $this, 'import_data_ajax' ) );
	}

	/**
	 * Generate unique slug for phase
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an existing post being updated.
	 * @return void
	 */
	public function generate_phase_slug( $post_id, $post, $update ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Avoid autosave and revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check if we already have a slug.
		$existing_slug = get_post_meta( $post_id, 'pbc_phase_slug', true );
		if ( ! empty( $existing_slug ) ) {
			return;
		}

		// Generate slug from title + post_id.
		$slug = 'phase-' . sanitize_title( $post->post_title ) . '-' . $post_id;
		update_post_meta( $post_id, 'pbc_phase_slug', $slug );
	}

	/**
	 * Generate unique slug for variation
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool    $update Whether this is an existing post being updated.
	 * @return void
	 */
	public function generate_variation_slug( $post_id, $post, $update ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Avoid autosave and revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check if we already have a slug.
		$existing_slug = get_post_meta( $post_id, 'pbc_variation_slug', true );
		if ( ! empty( $existing_slug ) ) {
			return;
		}

		// Generate slug from title + SKU or post_id.
		$sku  = get_post_meta( $post_id, 'pbc_sku', true );
		$slug = 'var-' . sanitize_title( $post->post_title );

		if ( ! empty( $sku ) ) {
			$slug .= '-' . sanitize_title( $sku );
		} else {
			$slug .= '-' . $post_id;
		}

		update_post_meta( $post_id, 'pbc_variation_slug', $slug );
	}

	/**
	 * Export all phases and variations
	 *
	 * @return array Export data.
	 */
	public function export_all_data() {
		$export_data = array(
			'version'     => WPPBC_VERSION,
			'export_date' => current_time( 'mysql' ),
			'phases'      => array(),
			'variations'  => array(),
		);

		// Export Phases.
		$phases = get_posts(
			array(
				'post_type'      => 'phases',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		foreach ( $phases as $phase ) {
			$phase_slug = get_post_meta( $phase->ID, 'pbc_phase_slug', true );

			// Generate slug if missing.
			if ( empty( $phase_slug ) ) {
				$phase_slug = 'phase-' . sanitize_title( $phase->post_title ) . '-' . $phase->ID;
				update_post_meta( $phase->ID, 'pbc_phase_slug', $phase_slug );
			}

			$parent_slug = '';
			if ( $phase->post_parent > 0 ) {
				$parent_slug = get_post_meta( $phase->post_parent, 'pbc_phase_slug', true );
			}

			$export_data['phases'][] = array(
				'slug'        => $phase_slug,
				'title'       => $phase->post_title,
				'content'     => $phase->post_content,
				'menu_order'  => $phase->menu_order,
				'parent_slug' => $parent_slug,
			);
		}

		// Export Variations.
		$variations = get_posts(
			array(
				'post_type'      => 'variation',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		foreach ( $variations as $variation ) {
			$var_slug = get_post_meta( $variation->ID, 'pbc_variation_slug', true );

			// Generate slug if missing.
			if ( empty( $var_slug ) ) {
				$sku      = get_post_meta( $variation->ID, 'pbc_sku', true );
				$var_slug = 'var-' . sanitize_title( $variation->post_title );

				if ( ! empty( $sku ) ) {
					$var_slug .= '-' . sanitize_title( $sku );
				} else {
					$var_slug .= '-' . $variation->ID;
				}

				update_post_meta( $variation->ID, 'pbc_variation_slug', $var_slug );
			}

			// Get phase slug.
			$phase_id   = get_post_meta( $variation->ID, 'pbc_phase', true );
			$phase_slug = '';
			if ( $phase_id ) {
				$phase_slug = get_post_meta( $phase_id, 'pbc_phase_slug', true );
			}

			// Get meta data.
			$sku          = get_post_meta( $variation->ID, 'pbc_sku', true );
			$field_type   = get_post_meta( $variation->ID, 'pbc_field_type', true );
			$imgicon      = get_post_meta( $variation->ID, 'pbc_imgicon', true );
			$depends      = get_post_meta( $variation->ID, 'pbc_depends', true );
			$imgprodgroup = get_post_meta( $variation->ID, 'pbc_imgprodgroup', true );
			$pricegroup   = get_post_meta( $variation->ID, 'pbc_pricegroup', true );
			$descopt      = get_post_meta( $variation->ID, 'pbc_descopt', true );
			$descvar      = get_post_meta( $variation->ID, 'pbc_descvar', true );

			// Convert dependencies to slugs.
			$depends_slugs = $this->convert_depends_to_slugs( $depends );

			// Convert image dependencies to slugs.
			$imgprodgroup_slugs = $this->convert_imgprodgroup_to_slugs( $imgprodgroup );

			// Get taxonomy terms.
			$terms      = wp_get_post_terms( $variation->ID, 'variation_tag', array( 'fields' => 'slugs' ) );
			$term_slugs = is_array( $terms ) ? $terms : array();

			$export_data['variations'][] = array(
				'slug'         => $var_slug,
				'title'        => $variation->post_title,
				'phase_slug'   => $phase_slug,
				'sku'          => $sku,
				'field_type'   => $field_type,
				'imgicon'      => $imgicon,
				'depends'      => $depends_slugs,
				'imgprodgroup' => $imgprodgroup_slugs,
				'pricegroup'   => $pricegroup,
				'descopt'      => $descopt,
				'descvar'      => $descvar,
				'term_slugs'   => $term_slugs,
			);
		}

		return $export_data;
	}

	/**
	 * Convert depends array to use slugs instead of IDs
	 *
	 * @param array $depends Depends array.
	 * @return array
	 */
	private function convert_depends_to_slugs( $depends ) {
		if ( empty( $depends ) || ! is_array( $depends ) ) {
			return array();
		}

		$depends_slugs = array();

		foreach ( $depends as $depend ) {
			if ( empty( $depend['pbc_depvar'] ) ) {
				continue;
			}

			// Extract variation ID from format "order|ID".
			$depvar_parts = explode( '|', $depend['pbc_depvar'] );
			$var_id       = isset( $depvar_parts[1] ) ? (int) $depvar_parts[1] : 0;

			if ( $var_id ) {
				$var_slug = get_post_meta( $var_id, 'pbc_variation_slug', true );
				if ( $var_slug ) {
					$depends_slugs[] = array(
						'pbc_depvar_slug' => $var_slug,
					);
				}
			}
		}

		return $depends_slugs;
	}

	/**
	 * Convert imgprodgroup array to use slugs instead of IDs
	 *
	 * @param array $imgprodgroup Image product group array.
	 * @return array
	 */
	private function convert_imgprodgroup_to_slugs( $imgprodgroup ) {
		if ( empty( $imgprodgroup ) || ! is_array( $imgprodgroup ) ) {
			return array();
		}

		$imgprodgroup_slugs = array();

		foreach ( $imgprodgroup as $imgprod ) {
			$depvar_slugs = array();

			if ( ! empty( $imgprod['pbc_depvarimgprod'] ) && is_array( $imgprod['pbc_depvarimgprod'] ) ) {
				foreach ( $imgprod['pbc_depvarimgprod'] as $depvar ) {
					// Extract variation ID from format "order|ID".
					$depvar_parts = explode( '|', $depvar );
					$var_id       = isset( $depvar_parts[1] ) ? (int) $depvar_parts[1] : 0;

					if ( $var_id ) {
						$var_slug = get_post_meta( $var_id, 'pbc_variation_slug', true );
						if ( $var_slug ) {
							$depvar_slugs[] = $var_slug;
						}
					}
				}
			}

			$imgprodgroup_slugs[] = array(
				'pbc_depvarimgprod_slugs' => $depvar_slugs,
				'pbc_imgprod'             => isset( $imgprod['pbc_imgprod'] ) ? $imgprod['pbc_imgprod'] : array(),
			);
		}

		return $imgprodgroup_slugs;
	}

	/**
	 * Import phases and variations from export data
	 *
	 * @param array $import_data Import data.
	 * @return array Result with success/error info.
	 */
	public function import_data( $import_data ) {
		$result = array(
			'success'            => false,
			'message'            => '',
			'phases_created'     => 0,
			'phases_updated'     => 0,
			'variations_created' => 0,
			'variations_updated' => 0,
			'errors'             => array(),
		);

		// Validate import data.
		if ( empty( $import_data['phases'] ) && empty( $import_data['variations'] ) ) {
			$result['message'] = __( 'No data to import.', 'pbc' );
			return $result;
		}

		// Maps for slug to new post ID.
		$phase_map = array();
		$var_map   = array();

		// Pre-populate phase_map with ALL existing phases.
		$existing_phases = get_posts(
			array(
				'post_type'      => 'phases',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		foreach ( $existing_phases as $phase ) {
			$phase_slug = get_post_meta( $phase->ID, 'pbc_phase_slug', true );
			if ( ! empty( $phase_slug ) ) {
				$phase_map[ $phase_slug ] = $phase->ID;
			}
		}

		// Pre-populate var_map with ALL existing variations.
		$existing_vars = get_posts(
			array(
				'post_type'      => 'variation',
				'posts_per_page' => -1,
				'post_status'    => 'any',
			)
		);

		foreach ( $existing_vars as $var ) {
			$var_slug = get_post_meta( $var->ID, 'pbc_variation_slug', true );
			if ( ! empty( $var_slug ) ) {
				$var_map[ $var_slug ] = $var->ID;
			}
		}

		// Import Phases.
		if ( ! empty( $import_data['phases'] ) ) {
			foreach ( $import_data['phases'] as $phase_data ) {
				$is_update         = isset( $phase_map[ $phase_data['slug'] ] );
				$imported_phase_id = $this->import_phase( $phase_data, $phase_map );

				if ( $imported_phase_id ) {
					$phase_map[ $phase_data['slug'] ] = $imported_phase_id;
					if ( $is_update ) {
						++$result['phases_updated'];
					} else {
						++$result['phases_created'];
					}
				} else {
					// translators: %s is the phase title that failed to import.
					$result['errors'][] = sprintf( __( 'Failed to import phase: %s', 'pbc' ), $phase_data['title'] );
				}
			}

			// Second pass to set parent phases.
			foreach ( $import_data['phases'] as $phase_data ) {
				if ( ! empty( $phase_data['parent_slug'] ) && isset( $phase_map[ $phase_data['slug'] ] ) && isset( $phase_map[ $phase_data['parent_slug'] ] ) ) {
					wp_update_post(
						array(
							'ID'          => $phase_map[ $phase_data['slug'] ],
							'post_parent' => $phase_map[ $phase_data['parent_slug'] ],
						)
					);
				}
			}
		}

		// Import Variations.
		if ( ! empty( $import_data['variations'] ) ) {
			foreach ( $import_data['variations'] as $var_data ) {
				$is_update       = isset( $var_map[ $var_data['slug'] ] );
				$imported_var_id = $this->import_variation( $var_data, $phase_map, $var_map );

				if ( $imported_var_id ) {
					$var_map[ $var_data['slug'] ] = $imported_var_id;
					if ( $is_update ) {
						++$result['variations_updated'];
					} else {
						++$result['variations_created'];
					}
				} else {
					// translators: %s is the variation title that failed to import.
					$result['errors'][] = sprintf( __( 'Failed to import variation: %s', 'pbc' ), $var_data['title'] );
				}
			}

			// Second pass to update dependencies now that all variations exist.
			foreach ( $import_data['variations'] as $var_data ) {
				if ( ! isset( $var_map[ $var_data['slug'] ] ) ) {
					continue;
				}

				$post_id = $var_map[ $var_data['slug'] ];

				// Update dependencies with complete var_map.
				if ( ! empty( $var_data['depends'] ) ) {
					$depends = $this->convert_depends_from_slugs( $var_data['depends'], $var_map );
					update_post_meta( $post_id, 'pbc_depends', $depends );
				}

				// Update image product group with complete var_map.
				if ( ! empty( $var_data['imgprodgroup'] ) ) {
					$imgprodgroup = $this->convert_imgprodgroup_from_slugs( $var_data['imgprodgroup'], $var_map );
					update_post_meta( $post_id, 'pbc_imgprodgroup', $imgprodgroup );
				}
			}
		}

		$result['success'] = true;
		$result['message'] = sprintf(
			// translators: %1$d phases created, %2$d phases updated, %3$d variations created, %4$d variations updated.
			__( 'Import completed. Phases: %1$d created, %2$d updated. Variations: %3$d created, %4$d updated.', 'pbc' ),
			$result['phases_created'],
			$result['phases_updated'],
			$result['variations_created'],
			$result['variations_updated']
		);

		return $result;
	}

	/**
	 * Import single phase
	 *
	 * @param array $phase_data Phase data.
	 * @param array $phase_map Phase slug to ID map.
	 * @return int|false New post ID or false on failure.
	 */
	private function import_phase( $phase_data, $phase_map ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		// Check if phase with this slug already exists.
		$existing_phases = get_posts(
			array(
				'post_type'      => 'phases',
				'posts_per_page' => 1,
				'meta_key'       => 'pbc_phase_slug',
				'meta_value'     => $phase_data['slug'],
			)
		);

		if ( ! empty( $existing_phases ) ) {
			// Phase already exists, return existing ID.
			return $existing_phases[0]->ID;
		}

		// Create new phase.
		$post_id = wp_insert_post(
			array(
				'post_title'   => $phase_data['title'],
				'post_content' => isset( $phase_data['content'] ) ? $phase_data['content'] : '',
				'post_status'  => 'publish',
				'post_type'    => 'phases',
				'menu_order'   => isset( $phase_data['menu_order'] ) ? $phase_data['menu_order'] : 0,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Save slug.
		update_post_meta( $post_id, 'pbc_phase_slug', $phase_data['slug'] );

		return $post_id;
	}

	/**
	 * Import single variation
	 *
	 * @param array $var_data Variation data.
	 * @param array $phase_map Phase slug to ID map.
	 * @param array $var_map Variation slug to ID map.
	 * @return int|false New post ID or false on failure.
	 */
	private function import_variation( $var_data, $phase_map, $var_map ) {
		// Check if variation with this slug already exists.
		$existing_vars = get_posts(
			array(
				'post_type'      => 'variation',
				'posts_per_page' => 1,
				'meta_key'       => 'pbc_variation_slug',
				'meta_value'     => $var_data['slug'],
			)
		);

		if ( ! empty( $existing_vars ) ) {
			// Variation already exists, update the phase relationship and return ID.
			$post_id = $existing_vars[0]->ID;

			// Update phase reference for existing variation.
			if ( ! empty( $var_data['phase_slug'] ) && isset( $phase_map[ $var_data['phase_slug'] ] ) ) {
				update_post_meta( $post_id, 'pbc_phase', $phase_map[ $var_data['phase_slug'] ] );
			}

			return $post_id;
		}

		// Create new variation.
		$post_id = wp_insert_post(
			array(
				'post_title'  => $var_data['title'],
				'post_status' => 'publish',
				'post_type'   => 'variation',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Save slug.
		update_post_meta( $post_id, 'pbc_variation_slug', $var_data['slug'] );

		// Save phase reference.
		if ( ! empty( $var_data['phase_slug'] ) && isset( $phase_map[ $var_data['phase_slug'] ] ) ) {
			update_post_meta( $post_id, 'pbc_phase', $phase_map[ $var_data['phase_slug'] ] );
		}

		// Save basic meta.
		if ( ! empty( $var_data['sku'] ) ) {
			update_post_meta( $post_id, 'pbc_sku', $var_data['sku'] );
		}

		if ( ! empty( $var_data['field_type'] ) ) {
			update_post_meta( $post_id, 'pbc_field_type', $var_data['field_type'] );
		}

		if ( ! empty( $var_data['imgicon'] ) ) {
			update_post_meta( $post_id, 'pbc_imgicon', $var_data['imgicon'] );
		}

		if ( ! empty( $var_data['pricegroup'] ) ) {
			update_post_meta( $post_id, 'pbc_pricegroup', $var_data['pricegroup'] );
		}

		if ( ! empty( $var_data['descopt'] ) ) {
			update_post_meta( $post_id, 'pbc_descopt', $var_data['descopt'] );
		}

		if ( ! empty( $var_data['descvar'] ) ) {
			update_post_meta( $post_id, 'pbc_descvar', $var_data['descvar'] );
		}

		// Convert dependencies back to IDs.
		if ( ! empty( $var_data['depends'] ) ) {
			$depends = $this->convert_depends_from_slugs( $var_data['depends'], $var_map );
			update_post_meta( $post_id, 'pbc_depends', $depends );
		}

		// Convert image product group back to IDs.
		if ( ! empty( $var_data['imgprodgroup'] ) ) {
			$imgprodgroup = $this->convert_imgprodgroup_from_slugs( $var_data['imgprodgroup'], $var_map );
			update_post_meta( $post_id, 'pbc_imgprodgroup', $imgprodgroup );
		}

		// Set taxonomy terms.
		if ( ! empty( $var_data['term_slugs'] ) ) {
			wp_set_object_terms( $post_id, $var_data['term_slugs'], 'variation_tag' );
		}

		return $post_id;
	}

	/**
	 * Convert depends slugs back to IDs
	 *
	 * @param array $depends_slugs Depends slugs array.
	 * @param array $var_map Variation slug to ID map.
	 * @return array
	 */
	private function convert_depends_from_slugs( $depends_slugs, $var_map ) {
		$depends = array();

		foreach ( $depends_slugs as $depend ) {
			if ( empty( $depend['pbc_depvar_slug'] ) ) {
				continue;
			}

			$var_slug = $depend['pbc_depvar_slug'];

			if ( isset( $var_map[ $var_slug ] ) ) {
				$var_id     = $var_map[ $var_slug ];
				$phase_id   = get_post_meta( $var_id, 'pbc_phase', true );
				$phase_post = get_post( $phase_id );

				if ( $phase_post ) {
					$phase_order = str_pad( $phase_post->menu_order, 2, '0', STR_PAD_LEFT );
					$depends[]   = array(
						'pbc_depvar' => $phase_order . '|' . $var_id,
					);
				}
			}
		}

		return $depends;
	}

	/**
	 * Convert imgprodgroup slugs back to IDs
	 *
	 * @param array $imgprodgroup_slugs Image product group slugs array.
	 * @param array $var_map Variation slug to ID map.
	 * @return array
	 */
	private function convert_imgprodgroup_from_slugs( $imgprodgroup_slugs, $var_map ) {
		$imgprodgroup = array();

		foreach ( $imgprodgroup_slugs as $imgprod ) {
			$depvar_values = array();

			if ( ! empty( $imgprod['pbc_depvarimgprod_slugs'] ) && is_array( $imgprod['pbc_depvarimgprod_slugs'] ) ) {
				foreach ( $imgprod['pbc_depvarimgprod_slugs'] as $var_slug ) {
					if ( isset( $var_map[ $var_slug ] ) ) {
						$var_id     = $var_map[ $var_slug ];
						$phase_id   = get_post_meta( $var_id, 'pbc_phase', true );
						$phase_post = get_post( $phase_id );

						if ( $phase_post ) {
							$phase_order     = str_pad( $phase_post->menu_order, 2, '0', STR_PAD_LEFT );
							$depvar_values[] = $phase_order . '|' . $var_id;
						}
					}
				}
			}

			$imgprodgroup[] = array(
				'pbc_depvarimgprod' => $depvar_values,
				'pbc_imgprod'       => isset( $imgprod['pbc_imgprod'] ) ? $imgprod['pbc_imgprod'] : array(),
			);
		}

		return $imgprodgroup;
	}

	/**
	 * Convert depends array to CSV format
	 *
	 * @param  array $depends Depends array.
	 * @return string CSV formatted string.
	 */
	private function depends_to_csv( $depends ) {
		if ( empty( $depends ) || ! is_array( $depends ) ) {
			return '';
		}

		$slugs = array();
		foreach ( $depends as $depend ) {
			if ( ! empty( $depend['pbc_depvar_slug'] ) ) {
				$slugs[] = $depend['pbc_depvar_slug'];
			}
		}

		return implode( '|', $slugs );
	}

	/**
	 * Convert imgprodgroup array to CSV format
	 *
	 * @param  array $imgprodgroup Image product group array.
	 * @return string CSV formatted string.
	 */
	private function imgprodgroup_to_csv( $imgprodgroup ) {
		if ( empty( $imgprodgroup ) || ! is_array( $imgprodgroup ) ) {
			return '';
		}

		$groups = array();
		foreach ( $imgprodgroup as $group ) {
			$dep_slugs = ! empty( $group['pbc_depvarimgprod_slugs'] ) ? implode( ',', $group['pbc_depvarimgprod_slugs'] ) : '';
			$img_ids   = ! empty( $group['pbc_imgprod'] ) ? implode( ',', $group['pbc_imgprod'] ) : '';
			$groups[]  = $dep_slugs . ':' . $img_ids;
		}

		return implode( '|', $groups );
	}

	/**
	 * Convert pricegroup array to CSV format
	 *
	 * @param array $pricegroup Price group array.
	 * @return string CSV formatted string.
	 */
	private function pricegroup_to_csv( $pricegroup ) {
		if ( empty( $pricegroup ) || ! is_array( $pricegroup ) ) {
			return '';
		}

		$prices = array();
		foreach ( $pricegroup as $price ) {
			$option_name = ! empty( $price['pbc_meaprice'] ) ? $price['pbc_meaprice'] : '';
			$price_value = ! empty( $price['pbc_pricem'] ) ? $price['pbc_pricem'] : '';
			$prices[]    = $option_name . ':' . $price_value;
		}

		return implode( '|', $prices );
	}

	/**
	 * Convert phases to CSV format
	 *
	 * @param array  $phases  Phases array.
	 * @param string $version Version string.
	 * @param string $date Export date.
	 * @return string CSV content.
	 */
	private function phases_to_csv( $phases, $version, $date ) {
		$csv_output = '';

		// CSV Header.
		$csv_output .= "sep=,\n";
		$csv_output .= '# PBC Phases Export - Version: ' . $version . ' - Date: ' . $date . "\n";
		$csv_output .= '"Slug","Title","Content","Menu Order","Parent Slug"' . "\n";

		foreach ( $phases as $phase ) {
			$csv_output .= '"' . $this->escape_csv( $phase['slug'] ) . '",';
			$csv_output .= '"' . $this->escape_csv( $phase['title'] ) . '",';
			$csv_output .= '"' . $this->escape_csv( $phase['content'] ) . '",';
			$csv_output .= '"' . $phase['menu_order'] . '",';
			$csv_output .= '"' . $this->escape_csv( $phase['parent_slug'] ) . '"';
			$csv_output .= "\n";
		}

		return $csv_output;
	}

	/**
	 * Convert variations to CSV format
	 *
	 * @param array  $variations Variations array.
	 * @param string $version    Version string.
	 * @param string $date Export date.
	 * @return string CSV content.
	 */
	private function variations_to_csv( $variations, $version, $date ) {
		$csv_output = '';

		// CSV Header.
		$csv_output .= "sep=,\n";
		$csv_output .= '# PBC Variations Export - Version: ' . $version . ' - Date: ' . $date . "\n";
		$csv_output .= '"Slug","Title","Phase Slug","SKU","Field Type","Icon ID","Depends","Image Groups","Price Groups","Desc Option","Desc Variation","Terms"' . "\n";

		foreach ( $variations as $variation ) {
			$csv_output .= '"' . $this->escape_csv( $variation['slug'] ) . '",';
			$csv_output .= '"' . $this->escape_csv( $variation['title'] ) . '",';
			$csv_output .= '"' . $this->escape_csv( $variation['phase_slug'] ) . '",';
			$csv_output .= '"' . $this->escape_csv( $variation['sku'] ) . '",';
			$csv_output .= '"' . $this->escape_csv( $variation['field_type'] ) . '",';
			$csv_output .= '"' . $variation['imgicon'] . '",';
			$csv_output .= '"' . $this->escape_csv( $this->depends_to_csv( $variation['depends'] ) ) . '",';
			$csv_output .= '"' . $this->escape_csv( $this->imgprodgroup_to_csv( $variation['imgprodgroup'] ) ) . '",';
			$csv_output .= '"' . $this->escape_csv( $this->pricegroup_to_csv( $variation['pricegroup'] ) ) . '",';
			$csv_output .= '"' . $this->escape_csv( $variation['descopt'] ) . '",';
			$csv_output .= '"' . $this->escape_csv( $variation['descvar'] ) . '",';
			$csv_output .= '"' . $this->escape_csv( implode( '|', $variation['term_slugs'] ) ) . '"';
			$csv_output .= "\n";
		}

		return $csv_output;
	}

	/**
	 * Escape CSV field
	 *
	 * @param string $value Value to escape.
	 * @return string Escaped value.
	 */
	private function escape_csv( $value ) {
		return str_replace( '"', '""', $value );
	}

	/**
	 * AJAX handler for export
	 *
	 * @return void
	 */
	public function export_data_ajax() {
		// Security check.
		if ( ! check_ajax_referer( 'pbc_export_import_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'pbc' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pbc' ) ) );
		}

		$export_data    = $this->export_all_data();
		$timestamp      = gmdate( 'Y-m-d-His' );
		$csv_phases     = $this->phases_to_csv( $export_data['phases'], $export_data['version'], $export_data['export_date'] );
		$csv_variations = $this->variations_to_csv( $export_data['variations'], $export_data['version'], $export_data['export_date'] );

		wp_send_json_success(
			array(
				'data'                => $export_data,
				'csv_phases'          => $csv_phases,
				'csv_variations'      => $csv_variations,
				'filename_phases'     => 'pbc-phases-' . $timestamp . '.csv',
				'filename_variations' => 'pbc-variations-' . $timestamp . '.csv',
				'total_phases'        => count( $export_data['phases'] ),
				'total_variations'    => count( $export_data['variations'] ),
				'message'             => sprintf(
					// translators: %1$d is phases count, %2$d is variations count.
					__( 'Exported %1$d phases and %2$d variations.', 'pbc' ),
					count( $export_data['phases'] ),
					count( $export_data['variations'] )
				),
			)
		);
	}

	/**
	 * Parse depends from CSV format
	 *
	 * @param string $csv_depends CSV formatted depends string.
	 * @return array Depends array.
	 */
	private function csv_to_depends( $csv_depends ) {
		if ( empty( $csv_depends ) ) {
			return array();
		}

		$slugs   = explode( '|', $csv_depends );
		$depends = array();

		foreach ( $slugs as $slug ) {
			if ( ! empty( trim( $slug ) ) ) {
				$depends[] = array(
					'pbc_depvar_slug' => trim( $slug ),
				);
			}
		}

		return $depends;
	}

	/**
	 * Parse imgprodgroup from CSV format
	 *
	 * @param string $csv_imgprodgroup CSV formatted imgprodgroup string.
	 * @return array Imgprodgroup array.
	 */
	private function csv_to_imgprodgroup( $csv_imgprodgroup ) {
		if ( empty( $csv_imgprodgroup ) ) {
			return array();
		}

		$groups       = explode( '|', $csv_imgprodgroup );
		$imgprodgroup = array();

		foreach ( $groups as $group ) {
			if ( empty( trim( $group ) ) ) {
				continue;
			}

			$parts     = explode( ':', $group );
			$dep_slugs = ! empty( $parts[0] ) ? explode( ',', $parts[0] ) : array();
			$img_ids   = ! empty( $parts[1] ) ? explode( ',', $parts[1] ) : array();

			$imgprodgroup[] = array(
				'pbc_depvarimgprod_slugs' => array_map( 'trim', $dep_slugs ),
				'pbc_imgprod'             => array_map( 'intval', $img_ids ),
			);
		}

		return $imgprodgroup;
	}

	/**
	 * Parse pricegroup from CSV format
	 *
	 * @param string $csv_pricegroup CSV formatted pricegroup string.
	 * @return array Pricegroup array.
	 */
	private function csv_to_pricegroup( $csv_pricegroup ) {
		if ( empty( $csv_pricegroup ) ) {
			return array();
		}

		$prices     = explode( '|', $csv_pricegroup );
		$pricegroup = array();

		foreach ( $prices as $price ) {
			if ( empty( trim( $price ) ) ) {
				continue;
			}

			$parts       = explode( ':', $price );
			$option_name = ! empty( $parts[0] ) ? trim( $parts[0] ) : '';
			$price_value = ! empty( $parts[1] ) ? trim( $parts[1] ) : '';

			$price_item = array();
			if ( ! empty( $option_name ) ) {
				$price_item['pbc_meaprice'] = $option_name;
			}
			$price_item['pbc_pricem'] = $price_value;

			$pricegroup[] = $price_item;
		}

		return $pricegroup;
	}

	/**
	 * Parse phases CSV content
	 *
	 * @param string $csv_content CSV content.
	 * @return array Phases array.
	 */
	private function parse_phases_csv( $csv_content ) {
		$phases = array();
		$rows   = $this->parse_csv_multiline( $csv_content );

		foreach ( $rows as $data ) {
			// Skip if not enough columns.
			if ( ! is_array( $data ) || count( $data ) < 5 ) {
				continue;
			}

			$first_field = isset( $data[0] ) ? trim( $data[0] ) : '';

			// Skip empty lines, comments, headers, and sep declarations.
			if ( empty( $first_field ) || strpos( $first_field, '#' ) === 0 || strpos( $first_field, 'sep=' ) === 0 || 'Slug' === $first_field ) {
				continue;
			}

			$phases[] = array(
				'slug'        => $data[0],
				'title'       => $data[1],
				'content'     => $data[2],
				'menu_order'  => (int) $data[3],
				'parent_slug' => $data[4],
			);
		}

		return $phases;
	}

	/**
	 * Parse CSV content handling multiline fields properly.
	 *
	 * @param string $csv_content CSV content.
	 * @return array Array of rows, each row is an array of fields.
	 */
	private function parse_csv_multiline( $csv_content ) {
		$rows = array();

		// Create temp file for proper CSV parsing.
		$temp_file = wp_tempnam( 'pbc_csv_' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $temp_file, $csv_content );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$handle = fopen( $temp_file, 'r' );
		if ( false !== $handle ) {
			while ( ( $data = fgetcsv( $handle ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition, Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
				$rows[] = $data;
			}
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
			fclose( $handle );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
		unlink( $temp_file );

		return $rows;
	}

	/**
	 * Parse variations CSV content
	 *
	 * @param string $csv_content CSV content.
	 * @return array Variations array.
	 */
	private function parse_variations_csv( $csv_content ) {
		$variations = array();
		$rows       = $this->parse_csv_multiline( $csv_content );

		foreach ( $rows as $data ) {
			// Skip if not enough columns.
			if ( ! is_array( $data ) || count( $data ) < 12 ) {
				continue;
			}

			$first_field = isset( $data[0] ) ? trim( $data[0] ) : '';

			// Skip empty lines, comments, headers, and sep declarations.
			if ( empty( $first_field ) || strpos( $first_field, '#' ) === 0 || strpos( $first_field, 'sep=' ) === 0 || 'Slug' === $first_field ) {
				continue;
			}

			$terms = ! empty( $data[11] ) ? explode( '|', $data[11] ) : array();

			$variations[] = array(
				'slug'         => $data[0],
				'title'        => $data[1],
				'phase_slug'   => $data[2],
				'sku'          => $data[3],
				'field_type'   => $data[4],
				'imgicon'      => (int) $data[5],
				'depends'      => $this->csv_to_depends( $data[6] ),
				'imgprodgroup' => $this->csv_to_imgprodgroup( $data[7] ),
				'pricegroup'   => $this->csv_to_pricegroup( $data[8] ),
				'descopt'      => $data[9],
				'descvar'      => $data[10],
				'term_slugs'   => array_map( 'trim', $terms ),
			);
		}

		return $variations;
	}

	/**
	 * Sanitize CSV content preserving structure.
	 *
	 * @param string $content CSV content to sanitize.
	 * @return string Sanitized CSV content.
	 */
	private function sanitize_csv_content( $content ) {
		// Remove null bytes and normalize line endings.
		$content = str_replace( "\0", '', $content );
		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );

		// Process line by line to preserve CSV structure.
		$lines     = explode( "\n", $content );
		$sanitized = array();

		foreach ( $lines as $line ) {
			// Skip completely empty lines.
			if ( '' === trim( $line ) ) {
				continue;
			}

			// Basic sanitization: remove potentially dangerous characters.
			$line = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $line );

			$sanitized[] = $line;
		}

		return implode( "\n", $sanitized );
	}

	/**
	 * AJAX handler for import
	 *
	 * @return void
	 */
	public function import_data_ajax() {
		// Security check.
		if ( ! check_ajax_referer( 'pbc_export_import_nonce', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'pbc' ) ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pbc' ) ) );
		}

		// Increase limits for large imports.
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 );
		}
		wp_raise_memory_limit( 'admin' );

		// Get import data from request - use custom sanitization to preserve CSV structure.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Custom sanitization applied below.
		$import_phases_csv = isset( $_POST['import_phases'] ) ? wp_unslash( $_POST['import_phases'] ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Custom sanitization applied below.
		$import_variations_csv = isset( $_POST['import_variations'] ) ? wp_unslash( $_POST['import_variations'] ) : '';

		// Apply custom CSV sanitization.
		$import_phases_csv     = $this->sanitize_csv_content( $import_phases_csv );
		$import_variations_csv = $this->sanitize_csv_content( $import_variations_csv );

		if ( empty( $import_phases_csv ) && empty( $import_variations_csv ) ) {
			wp_send_json_error( array( 'message' => __( 'No import data provided.', 'pbc' ) ) );
		}

		// Parse CSVs to import data format.
		$import_data = array(
			'phases'     => array(),
			'variations' => array(),
		);

		if ( ! empty( $import_phases_csv ) ) {
			$import_data['phases'] = $this->parse_phases_csv( $import_phases_csv );
		}

		if ( ! empty( $import_variations_csv ) ) {
			$import_data['variations'] = $this->parse_variations_csv( $import_variations_csv );
		}

		if ( empty( $import_data['phases'] ) && empty( $import_data['variations'] ) ) {
			wp_send_json_error(
				array(
					'message'          => __( 'Invalid CSV format or no data found.', 'pbc' ),
					'phases_lines'     => substr_count( $import_phases_csv, "\n" ),
					'variations_lines' => substr_count( $import_variations_csv, "\n" ),
				)
			);
		}

		$result = $this->import_data( $import_data );

		// Add debug info.
		$result['total_phases_in_csv']     = count( $import_data['phases'] );
		$result['total_variations_in_csv'] = count( $import_data['variations'] );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}
}

new PBC_Export_Import();
