<?php
/**
 * Class Admin
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

defined( 'ABSPATH' ) || exit;

use Close\PBC\Helpers\PDF;

/**
 * Class for admin
 */
class PBC_Admin_Plugin {
	/**
	 * Construct and intialize
	 */
	public function __construct() {
		// Initial stuff.
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_footer', array( $this, 'pbc_admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Custom Post types stuff.
		add_action( 'admin_menu', array( $this, 'pbc_add_admin_menus' ), 1 );

		add_filter( 'disable_months_dropdown', array( $this, 'disable_months_dropdown' ), 10, 2 );

		add_action( 'wp_ajax_pbc_enquiry_pdf', array( $this, 'pbc_enquiry_pdf' ) );
		add_action( 'wp_ajax_nopriv_pbc_enquiry_pdf', array( $this, 'pbc_enquiry_pdf' ) );

		add_action( 'wp_ajax_price_updater', array( $this, 'price_updater_action_callback' ) );
		add_action( 'wp_ajax_nopriv_price_updater', array( $this, 'price_updater_action_callback' ) );

		add_action( 'wp_ajax_pbc_restart_process', array( $this, 'pbc_restart_process' ) );
		add_action( 'wp_ajax_nopriv_pbc_restart_process', array( $this, 'pbc_restart_process' ) );

		add_action( 'wp_ajax_pbc_get_recommendations', array( $this, 'pbc_get_recommendations' ) );
		add_action( 'wp_ajax_nopriv_pbc_get_recommendations', array( $this, 'pbc_get_recommendations' ) );

		add_action( 'wp_ajax_pbc_render_recommendation_group', array( $this, 'pbc_render_recommendation_group_ajax' ) );

		// On variation-lists admin screen.
		add_filter( 'views_edit-variation', array( $this, 'pbc_add_print_pdf_button' ) );
		add_action( 'admin_head-edit.php', array( $this, 'pbc_move_print_pdf_button' ) );
	}

	/**
	 * Initialize
	 */
	public function init() {
		/**
		* Image Sizes
		*/
		add_image_size( 'pbc_icon', 150, 230, false );
		add_image_size( 'pbc_product', 570, 460, true );
	}
	/**
	 * PBC Admin Scripts
	 */
	public function pbc_admin_scripts() {
		$screen = get_current_screen();
		if ( ! empty( $screen ) && ( 'pbc_menu' === $screen->parent_base ) ) {
			wp_enqueue_script( 'post' );
			wp_enqueue_script( 'pbc-media' );
			wp_enqueue_style( 'pbc-admin' );
		}
		return null;
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_style( 'wp-color-picker' );

		wp_register_script(
			'pbc-media',
			WPPBC_PLUGIN_URL . 'includes/assets/pbc-media.js',
			array( 'jquery', 'wp-color-picker' ),
			WPPBC_VERSION,
			true
		);
		wp_localize_script(
			'pbc-media',
			'pbc_media_strings',
			array(
				'no_image_selected' => __( 'Please select an image file (jpeg, png) only', 'pbc' ),
			)
		);
		wp_register_style( 'pbc-admin', WPPBC_PLUGIN_URL . 'includes/assets/admin.css', array(), WPPBC_VERSION );

		wp_enqueue_script(
			'pbc-admin-scripts',
			WPPBC_PLUGIN_URL . 'includes/assets/admin-scripts.js',
			array( 'jquery' ),
			WPPBC_VERSION,
			true
		);

		wp_localize_script(
			'pbc-admin-scripts',
			'ajaxAction',
			array(
				'url'       => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'pbc_admin_nonce' ),
				'pdf_nonce' => wp_create_nonce( 'pbc_enquiry_pdf_nonce' ),
			)
		);

		wp_localize_script(
			'pbc-admin-scripts',
			'ajaxActionPrice',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'pbc_price_updater_nonce' ),
			)
		);

		wp_localize_script(
			'pbc-admin-scripts',
			'ajaxActionExportImport',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'pbc_export_import_nonce' ),
			)
		);
	}

	/**
	 * Registers menu admin
	 *
	 * @return void
	 */
	public function pbc_add_admin_menus() {
		// Add custom admin menu.
		add_menu_page(
			__( 'Product Budget Configurator', 'pbc' ),
			'PBC',
			'manage_options',
			'pbc_menu',
			array( $this, 'pbc_display_admin_page' ),
			'dashicons-tagcloud',
			2
		);

		$submenu_pages = array(
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Product Budget Configurator', 'pbc' ),
				'menu_title'  => __( 'Settings', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'pbc_menu',
				'function'    => array( $this, 'pbc_display_admin_page' ),
			),
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Phases of Configurator', 'pbc' ),
				'menu_title'  => __( 'Phases', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=phases',
				'function'    => null,
			),
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Variations in Phases', 'pbc' ),
				'menu_title'  => __( 'Variations', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=variation',
				'function'    => null,
			),
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Sections in variations', 'pbc' ),
				'menu_title'  => __( 'Sections', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit-tags.php?taxonomy=variation_tag',
				'function'    => null,
			),
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Recommended Configurations', 'pbc' ),
				'menu_title'  => __( 'Recommendations', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'pbc_recommendations',
				'function'    => array( $this, 'pbc_display_recommendations_page' ),
			),
			// Post Type :: View All Posts.
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Enquiries Received', 'pbc' ),
				'menu_title'  => __( 'Enquiries', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=enquiry',
				'function'    => null, // Doesn't need a callback function.
			),
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Import / Export', 'pbc' ),
				'menu_title'  => __( 'Import / Export', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'pbc_import_export',
				'function'    => array( $this, 'pbc_display_import_export_page' ),
			),

		);

		// Add each submenu item to custom admin menu.
		foreach ( $submenu_pages as $submenu ) {
			add_submenu_page(
				$submenu['parent_slug'],
				$submenu['page_title'],
				$submenu['menu_title'],
				$submenu['capability'],
				$submenu['menu_slug'],
				$submenu['function']
			);
		}
	}

	/**
	 * Page settings with metaboxes
	 *
	 * @return void
	 */
	public function pbc_display_admin_page() {
		$return = $this->save_post_options();
		if ( 'ok' === $return ) {
			$update = __( 'Successfully Saved!', 'pbc' );
		} elseif ( 'error' === $return ) {
			$error = __( 'Error saving settings', 'pbc' );
		}
		?>
		<div class='wrap'>
			<h2><?php echo esc_html( $GLOBALS['title'] ); ?> - <?php esc_html_e( 'Global Settings', 'pbc' ); ?></h2>

			<?php if ( isset( $update ) ) { ?>
				<div id="message" class="updated fade"><?php echo esc_html( $update ); ?></div>
			<?php } ?>
			<?php if ( isset( $error ) ) { ?>
				<div id="message" class="error"><?php echo esc_html( $error ); ?></div>
			<?php } ?>

			<div id="poststuff">
				<div id="post-body">
					<div class="postcontent-left">
						<?php
						add_meta_box(
							'phases_lists_meta_box',
							__( 'All Phases Lists', 'pbc' ),
							array(
								$this,
								'phases_lists_meta_box_callback',
							),
							'pbc_import_left'
						);

						do_meta_boxes(
							'pbc_import_left',
							'advanced',
							null
						);
						?>
					</div>
					<div class="postcontent-right">
						<?php
						// Price Updater.
						add_meta_box(
							'price_updater_meta_box',
							__( 'Price Updater', 'pbc' ),
							array( $this, 'price_updater_meta_box_callback' ),
							'pbc_import_right'
						);
						// General Settings.
						add_meta_box(
							'general_settings_meta_box',
							__( 'General Settings', 'pbc' ),
							array(
								$this,
								'general_settings_meta_box_callback',
							),
							'pbc_import_right'
						);
						do_meta_boxes(
							'pbc_import_right',
							'advanced',
							null
						);
						?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save post options
	 *
	 * @return string ok|error
	 */
	private function save_post_options() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$status = '';
		// Verify nonce.
		if ( isset( $_POST['pbc_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['pbc_nonce'] ), 'pbc_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'pbc' ) );
			return;
		}
		if ( isset( $_POST['form_submit'] ) ) {
			$status = 'ok';
			$fields = array(
				'option_show_final_button_pdf'   => 'pbc_budget_show_button_pdf',
				'option_show_final_button_email' => 'pbc_budget_show_button_email',
				'option_show_prices_global'      => 'pbc_show_prices_global',
				'pdf_image_selected'             => 'pbc_pdf_image_selected',
				'pdf_image_header'               => 'pbc_pdf_image_header',
				'pdf_image_footer'               => 'pbc_pdf_image_footer',
				'pdf_color_odd'                  => 'pbc_pdf_color_odd',
				'pdf_color_total'                => 'pbc_pdf_color_total',
				'admin_email_notification'       => 'pbc_admin_email_notification',
				'preview_width'                  => 'pbc_preview_width',
				'support_enabled'                => 'pbc_support_enabled',
				'support_phone'                  => 'pbc_support_phone',
				'support_email'                  => 'pbc_support_email',
			);
			foreach ( $fields as $field_key => $field ) {
				if ( isset( $_POST[ $field_key ] ) ) {
					update_option( $field, trim( sanitize_text_field( wp_unslash( $_POST[ $field_key ] ) ) ) );
				}
			}

			$variations_images_flipped = isset( $_POST['variations_images_flipped'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['variations_images_flipped'] ) ) : array( '' );
			$variations_images_flipped = array_map( 'intval', $variations_images_flipped );
			update_option( 'variations_images_flipped', $variations_images_flipped );

		// Save recommended variations (now supports multiple groups).
		$variations_recommended = isset( $_POST['variations_recommended'] ) ? map_deep( wp_unslash( $_POST['variations_recommended'] ), 'sanitize_text_field' ) : array();

			// Sanitize the nested array structure.
			$sanitized_recommendations = array();
			foreach ( $variations_recommended as $first_var_id => $phases_config ) {
				$first_var_id = (int) $first_var_id;
				if ( is_array( $phases_config ) ) {
					foreach ( $phases_config as $phase_id => $variation_id ) {
						$phase_id     = (int) $phase_id;
						$variation_id = (int) $variation_id;
						// Only save if variation_id is not empty.
						if ( $variation_id > 0 ) {
							$sanitized_recommendations[ $first_var_id ][ $phase_id ] = $variation_id;
						}
					}
				}
			}
			update_option( 'pbc_variations_recommended', $sanitized_recommendations );

			// Roles discount.
			$roles = wp_roles()->roles;
			foreach ( $roles as $slug => $role ) {
				if ( isset( $_POST[ 'pbc_discount_user_' . $slug ] ) ) {
					update_option( 'pbc_discount_user_' . $slug, (int) $_POST[ 'pbc_discount_user_' . $slug ] );
				}
				$show_prices = isset( $_POST[ 'pbc_show_prices_user_' . $slug ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'pbc_show_prices_user_' . $slug ] ) ) : '';
				update_option( 'pbc_show_prices_user_' . $slug, $show_prices );
			}
		}

		return $status;
	}
	/**
	 * Import Meta Box Callback
	 *
	 * Callback function for add_meta_box import section
	 */
	public function phases_lists_meta_box_callback() {
		$total_count = 0;
		?>
		<table class="phases-lists-table">
			<tr>
				<th class="order-col"><?php esc_html_e( 'Order', 'pbc' ); ?></th>
				<th class="phases-col"><?php esc_html_e( 'Phases', 'pbc' ); ?></th>
				<th class="variations-col"><?php esc_html_e( 'Number of Variations', 'pbc' ); ?></th>
			</tr>
			<?php
			$phases = get_posts( 'posts_per_page=-1&post_type=phases&orderby=menu_order&order=ASC' );
			if ( ! empty( $phases ) ) {
				foreach ( $phases as $phase ) {
					?>
					<tr>
						<td class="order-col"><?php echo esc_html( $phase->menu_order ); ?></td>
						<td class="phases-col"><?php echo esc_html( $phase->post_title ); ?></td>
						<td class="variations-col">
							<?php
							$variations = get_posts( 'posts_per_page=-1&post_type=variation&meta_key=pbc_phase&meta_value=' . $phase->ID . '&fields=ids' );
							if ( ! empty( $variations ) ) {
								echo count( $variations );
							}
							?>
						</td>
					</tr>
					<?php
					$total_count = $total_count + count( $variations );
				}
			}
			?>
			<tr>
				<td colspan="2" ><?php esc_html_e( 'Total: ', 'pbc' ); ?></td>
				<td><?php echo (int) $total_count; ?></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * General Settings Meta Box Callback
	 *
	 * Callback function for add_meta_box import section
	 */
	public function price_updater_meta_box_callback() {
		?>
		<label class="block" for="select_percentage_price"><?php esc_html_e( 'Set the percentage to bulk update prices (you can use negative values)', 'pbc' ); ?></label>
		<input type="text" id="pbc-percentage-price" name="pbc_percentage_price" value="" /> %
		<button id="bulk-updater-prices" class="button button-primary submit-button"><?php esc_html_e( 'Update Prices', 'pbc' ); ?></button><span id="pbc-price-updater-button" class="spinner"></span><div class="price-updater-result"></div>
		<?php
	}

	/**
	 * Ajax function to load info
	 *
	 * @return void
	 */
	public function price_updater_action_callback() {
		if ( check_ajax_referer( 'pbc_price_updater_nonce', 'nonce' ) ) {
			$percentage = isset( $_POST['percentage'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['percentage'] ) ) / 100 : '';
			$html       = '';
			$count      = 0;
			$variations = get_posts( 'posts_per_page=-1&post_type=variation&fields=ids' );

			if ( 0 === $percentage ) {
				$html = __( 'Percentage cannot be 0.', 'pbc' );
				wp_send_json_success( $html );
			}

			foreach ( $variations as $variation_id ) {
				$price_group = get_post_meta( $variation_id, 'pbc_pricegroup', true );
				if ( ! empty( $price_group ) ) {
					foreach ( $price_group as $key => $price_simple ) {
						if ( empty( $price_simple ) ) {
							continue;
						}
						$new_price                         = $price_simple['pbc_pricem'] + ( $price_simple['pbc_pricem'] * $percentage );
						$price_group[ $key ]['pbc_pricem'] = str_replace( '.', ',', $new_price );
					}
					update_post_meta( $variation_id, 'pbc_pricegroup', $price_group );
					++$count;
				}
			}
			$html = sprintf(
				/* translators: %s: number of variations updated. */
				__( 'Changed %s variation prices', 'pbc' ),
				$count
			);

			wp_send_json_success( $html );
		} else {
			wp_send_json_error( __( 'Error', 'pbc' ) );
		}
	}

	/**
	 * General Settings Meta Box Callback
	 *
	 * Callback function for add_meta_box import section
	 */
	public function general_settings_meta_box_callback() {
		wp_enqueue_media();
		?>
		<form action="" method="post" enctype="multipart/form-data" id="pbc_general_settings_form">
			<div class="content">
				<fieldset>
					<br/>
					<label class="block" for="variations_images_flipped"><?php esc_html_e( 'Flip Images Horizontal', 'pbc' ); ?></label>
					<?php
					$variations_images_flipped = get_option( 'variations_images_flipped' );
					$phases                    = get_posts(
						array(
							'post_type'      => 'phases',
							'posts_per_page' => -1,
							'orderby'        => 'menu_order',
							'order'          => 'ASC',
						)
					);
					if ( ! empty( $phases ) ) {
						echo '<select multiple="multiple" name="variations_images_flipped[]" size="6" style="width:100%;">';
						foreach ( $phases as $phase ) {
							$variations = get_posts(
								array(
									'post_type'      => 'variation',
									'posts_per_page' => -1,
									'meta_key'       => 'pbc_phase',
									'meta_value'     => $phase->ID,
									'orderby'        => 'title',
									'order'          => 'ASC',
								)
							);
							if ( ! empty( $variations ) ) {
								foreach ( $variations as $var ) {
									if ( ! empty( $variations_images_flipped ) && in_array( $var->ID, $variations_images_flipped, true ) ) {
										$selected = 'selected="selected"';
									} else {
										$selected = '';
									}
									echo '<option value="' . esc_attr( $var->ID ) . '" ' . esc_attr( $selected ) . '>' . esc_html( str_pad( $phase->menu_order, 2, '0', STR_PAD_LEFT ) . ' - ' . $phase->post_title . ' - ' . $var->post_title ) . '</option>';
								}
							}
						}
						echo '</select>';
					}
					?>
				</fieldset>
			<fieldset>
				<label class="block" for="admin_email_notification"><?php esc_html_e( 'Email Notification', 'pbc' ); ?></label>
				<?php
					$admin_email_notification = get_option( 'pbc_admin_email_notification' );
				?>
				<input style="width:100%;" type="text" name="admin_email_notification" value="
				<?php
				if ( $admin_email_notification ) {
					echo esc_html( $admin_email_notification ); }
				?>
				" placeholder="<?php esc_attr_e( 'separate multiple emails by comma', 'pbc' ); ?>" />
			</fieldset>
				<fieldset>
					<label class="block" for="preview_width"><?php esc_html_e( 'Preview width', 'pbc' ); ?></label>
					<?php
						$preview_width = get_option( 'pbc_preview_width' );
					?>
					<input class="pbc_field" type="text" name="preview_width" value="
					<?php
					if ( $preview_width ) {
						echo esc_attr( $preview_width ); }
					?>
" placeholder="<?php esc_html_e( 'default: 570', 'pbc' ); ?>" />
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_final_button_pdf"><?php esc_html_e( 'Show final button PDF?', 'pbc' ); ?></label>
					<?php
					$show_button_pdf = get_option( 'pbc_budget_show_button_pdf' );
					$pages           = get_pages();
					if ( ! empty( $pages ) ) {
						echo '<select name="option_show_final_button_pdf">';
						echo '<option value="yes" ' . selected( $show_button_pdf, 'yes' ) . '>' . esc_html__( 'Yes', 'pbc' ) . '</option>';
						echo '<option value="no" ' . selected( $show_button_pdf, 'no' ) . '>' . esc_html__( 'No', 'pbc' ) . '</option>';
						echo '</select>';
					}
					?>
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_final_button_email"><?php esc_html_e( 'Show final button Email?', 'pbc' ); ?></label>
					<?php
					$show_button_email = get_option( 'pbc_budget_show_button_email' );
					$pages             = get_pages();
					if ( ! empty( $pages ) ) {
						echo '<select name="option_show_final_button_email">';
						echo '<option value="yes" ' . selected( $show_button_email, 'yes' ) . '>' . esc_html__( 'Yes', 'pbc' ) . '</option>';
						echo '<option value="no" ' . selected( $show_button_email, 'no' ) . '>' . esc_html__( 'No', 'pbc' ) . '</option>';
						echo '</select>';
					}
					?>
				</fieldset>
			<fieldset>
				<label class="block" for="option_show_prices_global"><?php esc_html_e( 'Show prices (Global)?', 'pbc' ); ?></label>
				<?php
				$show_prices_global = get_option( 'pbc_show_prices_global', 'yes' );
				?>
				<select name="option_show_prices_global">
					<option value="yes" <?php selected( $show_prices_global, 'yes' ); ?>><?php esc_html_e( 'Yes', 'pbc' ); ?></option>
					<option value="no" <?php selected( $show_prices_global, 'no' ); ?>><?php esc_html_e( 'No', 'pbc' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Global configuration to show prices. Can be customized by user role below.', 'pbc' ); ?></p>
			</fieldset>
				<h2><?php esc_html_e( 'Budget Options', 'pbc' ); ?></h2>
				<fieldset>
					<label class="block" for="select_PDF_image"><?php esc_html_e( 'Set PDF Image Logo (200px width)', 'pbc' ); ?></label>
					<?php
						$pdf_image_selected = get_option( 'pbc_pdf_image_selected' );
					?>
					<div class="pbc_field_preview">
					<?php
					if ( $pdf_image_selected ) {
						?>
<img src="<?php echo esc_url( $pdf_image_selected ); ?>" alt="Image Preview" /><span class="pbc_field_preview_remove">&times;</span><?php } ?></div><input id="select_PDF_image" type="hidden" name="pdf_image_selected" value="
		<?php
		if ( $pdf_image_selected ) {
			echo esc_url( $pdf_image_selected );
		}
		?>
								" data-imageId="<?php echo esc_attr( $this->get_attachment_id( $pdf_image_selected ) ); ?>" />
					<button class="select-image button select-image-selected" data-name="pdf_image_selected"><?php esc_html_e( 'Select image', 'pbc' ); ?></button>
				</fieldset>
				<fieldset>
					<label class="block" for="select_pdf_image_header"><?php esc_html_e( 'Set PDF Image Header (1000px width) Height 75px optional', 'pbc' ); ?></label>
					<?php
						$pdf_image_header = get_option( 'pbc_pdf_image_header' );
					?>
					<div class="pbc_field_preview">
					<?php
					if ( $pdf_image_header ) {
						?>
<img src="<?php echo esc_url( $pdf_image_header ); ?>" alt="Image Preview" /><span class="pbc_field_preview_remove">&times;</span><?php } ?></div>
					<input id="select_pdf_image_header" type="hidden" name="pdf_image_header" value="
					<?php
					if ( $pdf_image_header ) {
						echo esc_url( $pdf_image_header );
					}
					?>
					" data-imageId="<?php echo esc_attr( $this->get_attachment_id( $pdf_image_header ) ); ?>" /><button class="select-image button select-image-selected" data-name="pdf_image_header"><?php esc_html_e( 'Select image', 'pbc' ); ?></button>
				</fieldset>
				<fieldset>
					<label class="block" for="select_pdf_image_footer"><?php esc_html_e( 'Set PDF Image Footer (1000px width) Height 75px optional', 'pbc' ); ?></label>
					<?php
						$pdf_image_footer = get_option( 'pbc_pdf_image_footer' );
					?>
					<div class="pbc_field_preview">
					<?php
					if ( $pdf_image_footer ) {
						?>
<img src="<?php echo esc_url( $pdf_image_footer ); ?>" alt="Image Preview" /><span class="pbc_field_preview_remove">&times;</span><?php } ?></div>
					<input id="select_pdf_image_footer" type="hidden" name="pdf_image_footer" value="
					<?php
					if ( $pdf_image_footer ) {
						echo esc_url( $pdf_image_footer );
					}
					?>
					" data-imageId="<?php echo esc_attr( $this->get_attachment_id( $pdf_image_footer ) ); ?>" /><button class="select-image button select-image-selected" data-name="pdf_image_footer"><?php esc_html_e( 'Select image', 'pbc' ); ?></button>
				</fieldset>
				<fieldset>
					<label class="block" for="select_pdf_color_odd"><?php esc_html_e( 'Color for odd entries (hex code)', 'pbc' ); ?></label>
					<?php
						$pdf_color_odd = get_option( 'pbc_pdf_color_odd' );
					?>
					<input type="text" name="pdf_color_odd" value="
					<?php
					if ( $pdf_color_odd ) {
						echo esc_url( $pdf_color_odd ); }
					?>
" class="pbc_color_picker" />
				</fieldset>
				<fieldset>
					<label class="block" for="select_pdf_color_total"><?php esc_html_e( 'Color for total (hex code)', 'pbc' ); ?></label>
					<?php
					$pdf_color_total = get_option( 'pbc_pdf_color_total' );
					?>
				<input type="text" name="pdf_color_total" value="
				<?php
				if ( $pdf_color_total ) {
					echo esc_url( $pdf_color_total );
				}
				?>
				" class="pbc_color_picker" />
			</fieldset>

			<h2><?php esc_html_e( 'Support Contact', 'pbc' ); ?></h2>
			<fieldset>
				<label class="block">
					<input type="checkbox" name="support_enabled" value="yes" <?php checked( get_option( 'pbc_support_enabled' ), 'yes' ); ?> />
					<?php esc_html_e( 'Enable support contact buttons in configurator', 'pbc' ); ?>
				</label>
				<p class="description"><?php esc_html_e( 'When enabled, displays contact buttons for technical support.', 'pbc' ); ?></p>
			</fieldset>
			<fieldset>
				<label class="block" for="support_phone"><?php esc_html_e( 'Support Phone Number', 'pbc' ); ?></label>
				<?php $support_phone = get_option( 'pbc_support_phone' ); ?>
				<input style="width:100%;" type="text" name="support_phone" value="<?php echo esc_attr( $support_phone ); ?>" placeholder="<?php esc_attr_e( '+34 123 456 789', 'pbc' ); ?>" />
				<p class="description"><?php esc_html_e( 'Phone number for technical support.', 'pbc' ); ?></p>
			</fieldset>
			<fieldset>
				<label class="block" for="support_email"><?php esc_html_e( 'Support Email Address', 'pbc' ); ?></label>
				<?php $support_email = get_option( 'pbc_support_email' ); ?>
				<input style="width:100%;" type="email" name="support_email" value="<?php echo esc_attr( $support_email ); ?>" placeholder="<?php esc_attr_e( 'support@example.com', 'pbc' ); ?>" />
				<p class="description"><?php esc_html_e( 'Email address for technical support.', 'pbc' ); ?></p>
			</fieldset>

			<h2><?php esc_html_e( 'Set the role specific options', 'pbc' ); ?></h2>
				<fieldset>
					<?php
					$roles = wp_roles()->roles;
					?>
					<p></p>
				<table class="roles-table">
					<tr>
						<th><?php esc_html_e( 'Profile', 'pbc' ); ?></th>
						<th><?php esc_html_e( 'Discount', 'pbc' ); ?></th>
						<th><?php esc_html_e( 'Show prices?', 'pbc' ); ?></th>
					</tr>
					<?php
					foreach ( $roles as $slug => $role ) {
						$discount    = get_option( 'pbc_discount_user_' . $slug );
						$show_prices = get_option( 'pbc_show_prices_user_' . $slug );
						echo '<tr>';
						echo '<td><label class="block" for="pbc_discount_user_' . esc_html( $slug ) . '">' . esc_html( $role['name'] );
						echo '</label></td>';
						echo '<td><input type="text" id="pbc_discount_user_' . esc_html( $slug ) . '" name="pbc_discount_user_' . esc_html( $slug ) . '" value="' . (int) $discount . '" /> % </td>';
						echo '<td><select name="pbc_show_prices_user_' . esc_html( $slug ) . '">';
						echo '<option value=""' . selected( $show_prices, '', false ) . '>' . esc_html__( 'By default', 'pbc' ) . '</option>';
						echo '<option value="yes" ' . selected( $show_prices, 'yes', false ) . '>' . esc_html__( 'Yes', 'pbc' ) . '</option>';
						echo '<option value="no" ' . selected( $show_prices, 'no', false ) . '>' . esc_html__( 'No', 'pbc' ) . '</option>';
						echo '</select></td>';
						echo '</tr>';
					}
					?>
					</table>
				</fieldset>
			</div>

			<div class="save_bar">
				<input type="hidden" name="form_submit" value="true"/>
				<input type="hidden" name="pbc_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pbc_nonce' ) ); ?>"/>
				<input type="submit" value="<?php esc_html_e( 'Save', 'pbc' ); ?>" class="button button-primary submit-button" />
			</div>
		</form>
		<?php
	}

	/**
	 * Display recommendations admin page
	 *
	 * @return void
	 */
	public function pbc_display_recommendations_page() {
		$return = $this->save_post_options();
		if ( 'ok' === $return ) {
			$update = __( 'Successfully Saved!', 'pbc' );
		} elseif ( 'error' === $return ) {
			$update = __( 'Error', 'pbc' );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Recommended Configurations', 'pbc' ); ?></h1>
			
			<?php if ( isset( $update ) ) { ?>
				<div id="message" class="<?php echo 'ok' === $return ? 'updated' : 'error'; ?>">
					<p><?php echo esc_html( $update ); ?></p>
				</div>
			<?php } ?>

			<form method="post">
				<div class="pbc_settings_area">
					<p class="description"><?php esc_html_e( 'Configure different recommendations for each option in the first phase. For example, different recommendations for "Floor" vs "Ceiling".', 'pbc' ); ?></p>
					
					<?php
					$variations_recommended = get_option( 'pbc_variations_recommended', array() );
					$phases                 = get_posts(
						array(
							'post_type'      => 'phases',
							'posts_per_page' => -1,
							'orderby'        => 'menu_order',
							'order'          => 'ASC',
						)
					);

					// Get first phase to show its variations as recommendation groups.
					$first_phase      = null;
					$first_phase_vars = array();
					$other_phases     = array();

					foreach ( $phases as $phase ) {
						// Skip parent phases if they have children.
						$child_phases = get_posts(
							array(
								'post_type'      => 'phases',
								'post_parent'    => $phase->ID,
								'posts_per_page' => 1,
								'fields'         => 'ids',
							)
						);
						if ( ! empty( $child_phases ) ) {
							continue;
						}

						if ( null === $first_phase ) {
							$first_phase = $phase;
							// Get variations of first phase.
							$first_phase_vars = get_posts(
								array(
									'post_type'      => 'variation',
									'posts_per_page' => -1,
									'meta_key'       => 'pbc_phase',
									'meta_value'     => $phase->ID,
									'orderby'        => 'title',
									'order'          => 'ASC',
								)
							);
						} else {
							$other_phases[] = $phase;
						}
					}

					if ( empty( $first_phase_vars ) ) {
						echo '<p class="notice notice-warning"><strong>' . esc_html__( 'Note:', 'pbc' ) . '</strong> ' . esc_html__( 'Please add variations to the first phase to configure recommendations.', 'pbc' ) . '</p>';
					} elseif ( count( $other_phases ) === 0 ) {
						echo '<p class="notice notice-info"><strong>' . esc_html__( 'Note:', 'pbc' ) . '</strong> ' . esc_html__( 'Please add more phases after the first one to configure recommendations.', 'pbc' ) . '</p>';
					} else {
						?>
						<div class="pbc-recommendations-manager">
							<!-- Selector para añadir nueva recomendación -->
							<div style="margin-bottom:20px; padding:15px; background:#fff; border:1px solid #ddd;">
								<label for="pbc-add-recommendation-select" style="font-weight:bold; display:block; margin-bottom:10px;">
									<?php esc_html_e( 'Add recommendation for:', 'pbc' ); ?>
								</label>
								<select id="pbc-add-recommendation-select" style="width:300px; margin-right:10px;">
									<option value=""><?php esc_html_e( '-- Select option --', 'pbc' ); ?></option>
									<?php
									foreach ( $first_phase_vars as $first_var ) {
										// Solo mostrar si NO está ya configurada.
										if ( ! isset( $variations_recommended[ $first_var->ID ] ) || empty( $variations_recommended[ $first_var->ID ] ) ) {
											echo '<option value="' . esc_attr( $first_var->ID ) . '" data-name="' . esc_attr( $first_var->post_title ) . '">';
											echo esc_html( $first_var->post_title );
											echo '</option>';
										}
									}
									?>
								</select>
								<button type="button" id="pbc-add-recommendation-btn" class="button button-secondary">
									<?php esc_html_e( 'Add Configuration', 'pbc' ); ?>
								</button>
							</div>

							<!-- Contenedor para las recomendaciones existentes y nuevas -->
							<div id="pbc-recommendations-container">
								<?php
								// Mostrar solo las configuraciones que ya existen.
								foreach ( $first_phase_vars as $first_var ) {
									$first_var_id   = $first_var->ID;
									$first_var_name = $first_var->post_title;

									// Solo mostrar si tiene configuración.
									if ( ! isset( $variations_recommended[ $first_var_id ] ) || empty( $variations_recommended[ $first_var_id ] ) ) {
										continue;
									}

									$this->render_recommendation_group( $first_var_id, $first_var_name, $other_phases, $variations_recommended );
								}
								?>
							</div>
						</div>
						<?php
					}
					?>
				</div>

				<div class="save_bar">
					<input type="hidden" name="form_submit" value="true"/>
					<input type="hidden" name="pbc_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pbc_nonce' ) ); ?>"/>
					<input type="submit" value="<?php esc_html_e( 'Save', 'pbc' ); ?>" class="button button-primary submit-button" />
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * General Settings Meta Box Callback
	 *
	 * @param bool   $disabled     Whether to disable the dropdown.
	 * @param string $post_type    Post type.
	 * @return bool
	 */
	public function disable_months_dropdown( $disabled, $post_type ) {
		$disable_months_dropdown = $disabled;
		$disable_post_types      = array( 'variation', 'phases' );

		if ( in_array( $post_type, $disable_post_types, true ) ) {
			$disable_months_dropdown = true;
		}

		return $disable_months_dropdown;
	}
	/**
	 * Ajax function to load info
	 *
	 * @return void
	 */
	public function pbc_enquiry_pdf() {
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : '';

		if ( ! check_ajax_referer( 'pbc_enquiry_pdf_nonce', 'nonce' ) ) {
			wp_send_json_error( array( 'error' => 'Error' ) );
		}
		$parent_phase = (int) get_post_meta( $post_id, 'pbc_parent_phase', true );
		$item_key     = 'pbc_variation_' . $parent_phase;

		$item = array(
			'pbc_date'         => get_the_date( 'd-m-Y', $post_id ),
			'pbc_parent_phase' => $parent_phase,
			'pbc_contact'      => array(
				'name'     => get_post_meta( $post_id, 'pbc_enquiry_name', true ),
				'phone'    => get_post_meta( $post_id, 'pbc_enquiry_phone', true ),
				'email'    => get_post_meta( $post_id, 'pbc_enquiry_email', true ),
				'city'     => get_post_meta( $post_id, 'pbc_enquiry_city', true ),
				'state'    => get_post_meta( $post_id, 'pbc_enquiry_state', true ),
				'comments' => get_post_meta( $post_id, 'pbc_enquiry_comments', true ),
			),
			'pbc_enquiry'      => $post_id,
			'pbc_admin'        => true,
		);

		$total_vars = get_post_meta( $post_id, 'pbc_total_var', true );
		$total_vars = (int) $total_vars;
		$total_vars = 0 === $total_vars ? 30 : $total_vars;

		for ( $i = 0; $i < $total_vars; $i++ ) {
			$variation_name = get_post_meta( $post_id, 'pbc_phase_var_' . $i, true );
			if ( empty( $variation_name ) ) {
				continue;
			}
			$item[ $item_key ][ $i ]['phase']['name'] = get_post_meta( $post_id, 'pbc_phase_name_' . $i, true );
			$item[ $item_key ][ $i ]['var']['name']   = $variation_name;
			$item[ $item_key ][ $i ]['var']['price']  = get_post_meta( $post_id, 'pbc_price_' . $i, true );
			$item[ $item_key ][ $i ]['var']['type']   = get_post_meta( $post_id, 'pbc_type_' . $i, true );
		}

		$file_url = PDF::generate_engine_pdf( $item, 'url' );
		wp_send_json_success( $file_url );
	}

	/**
	 * Restart process - Clean session and start over
	 *
	 * @return void
	 */
	public function pbc_restart_process() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'pbc-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'pbc' ) ) );
		}

		// Get parent phase from session or POST.
		$pbc_session_key = '';
		if ( isset( $_SESSION['pbc_parent_phase'] ) ) {
			$pbc_session_key = 'pbc_variation_' . (int) $_SESSION['pbc_parent_phase'];
		}

		// Clear all PBC session data.
		if ( isset( $_SESSION ) ) {
			// Remove specific PBC keys.
			if ( ! empty( $pbc_session_key ) && isset( $_SESSION[ $pbc_session_key ] ) ) {
				unset( $_SESSION[ $pbc_session_key ] );
			}
			if ( isset( $_SESSION['pbc_parent_phase'] ) ) {
				unset( $_SESSION['pbc_parent_phase'] );
			}
			if ( isset( $_SESSION['pbc_output'] ) ) {
				unset( $_SESSION['pbc_output'] );
			}
		}

		wp_send_json_success(
			array(
				'message' => __( 'Process restarted successfully.', 'pbc' ),
			)
		);
	}

	/**
	 * Add print PDF button
	 *
	 * @param array $views Views.
	 * @return array
	 */
	public function pbc_add_print_pdf_button( $views ) {
		$views['pdf-button'] = '<button id="print-pdf" type="button" class="button" title="Print PDF" style="margin:0 5px"><span class="dashicons dashicons-media-spreadsheet"></span> ' . __( 'Create List Price', 'pbc' ) . '</button><span id="print-message"></span>';
		return $views;
	}

	/**
	 * Move print PDF button
	 *
	 * @return void
	 */
	public function pbc_move_print_pdf_button() {
		global $current_screen;
		// Only variation post type, exit earlier.
		if ( 'variation' !== $current_screen->post_type ) {
			return;
		}
		?>
		<script type="text/javascript">
			var ids = new Array();
			jQuery(function($){
				$('#print-pdf').insertAfter('#post-query-submit');
				$('#print-message').insertAfter('#print-pdf');
				$("#print-pdf").click(function(){
					$("input[name='post[]']:checked").each(function (index, element){
						ids.push($(element).val());
					});
				// if(ids ==''){
				// 	$('#print-message').html('Please select a post!').show().delay(3000).fadeOut(500);
				// 	return false;
				// }
				$('#print-message').html('<img src="<?php echo esc_url( WPPBC_PLUGIN_URL ); ?>/assets/loading.gif"/>');
				$.ajax({
					type: "POST",
					url: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
					data: 'action=print_pdf&ids='+ids,
					dataType: "html",
					success: function(result) {
						var resArr = result.split(';;--;;');
						var obj = jQuery.parseJSON(resArr[1]);
						if(obj.type=='success'){
							//window.prompt('PDF is generated, Please copy the link below!',obj.msg);
							window.open(obj.msg, '_blank');
							ids =[];
							$('#print-message').html('');
						}else{
							$('#print-message').html(obj.msg).show().delay(3000).fadeOut(500);
						}
					}
				});
				return false;
				});
			});

		</script>
		<?php
	}

	/**
	 * Get attachment ID from URL
	 *
	 * @param string $url Attachment URL.
	 * @return int|null Attachment ID or null if not found.
	 */
	public function get_attachment_id( $url ) {
		global $wpdb;
		$attachment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE guid = %s AND post_type = 'attachment'",
				$url
			)
		);

		return (int) $attachment_id;
	}

	/**
	 * Display Import/Export admin page
	 *
	 * @return void
	 */
	public function pbc_display_import_export_page() {
		?>
		<div class='wrap'>
			<h1><?php esc_html_e( 'Import / Export', 'pbc' ); ?></h1>
			<p><?php esc_html_e( 'Export phases and variations to separate CSV files, or import from previously exported files. The system uses unique slugs to maintain relationships between phases and variations, allowing you to move configurations between different WordPress installations.', 'pbc' ); ?></p>

			<div class="pbc-export-import-container" style="max-width: 100%;">
				<div style="display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
				
					<!-- Export Section -->
					<div class="pbc-export-section" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); flex: 1; min-width: 400px;">
						<h2 style="margin-top: 0;">
							<span class="dashicons dashicons-upload" style="font-size: 24px; width: 24px; height: 24px;"></span>
							<?php esc_html_e( 'Export Data', 'pbc' ); ?>
						</h2>
						<p><?php esc_html_e( 'Export all your phases and variations to two separate CSV files (one for phases, one for variations). These files can be imported on another WordPress installation.', 'pbc' ); ?></p>
						
						<button id="pbc-export-button" class="button button-primary button-hero" style="display: inline-flex; align-items: center; gap: 8px;">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Export All Data', 'pbc' ); ?>
						</button>
						<span id="pbc-export-spinner" class="spinner" style="float: none; margin: 5px 10px;"></span>

						<div id="pbc-export-log" class="pbc-log-container" style="display: none; margin-top: 20px; background: #f0f0f1; padding: 15px; border-left: 4px solid #2271b1; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; line-height: 1.6;">
						</div>
					</div>

					<!-- Import Section -->
					<div class="pbc-import-section" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); flex: 1; min-width: 400px;">
					<h2 style="margin-top: 0;">
						<span class="dashicons dashicons-download" style="font-size: 24px; width: 24px; height: 24px;"></span>
						<?php esc_html_e( 'Import Data', 'pbc' ); ?>
					</h2>
					<p><?php esc_html_e( 'Import phases and variations from previously exported CSV files. You can import both files together or one at a time.', 'pbc' ); ?></p>
					
					<div style="margin-bottom: 15px;">
						<label for="pbc-import-file-phases" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
							<span class="dashicons dashicons-media-default"></span>
							<?php esc_html_e( 'Choose Phases CSV', 'pbc' ); ?>
						</label>
						<input type="file" id="pbc-import-file-phases" accept=".csv" style="display: none;" />
						<span id="pbc-import-filename-phases" style="margin-left: 10px; font-weight: 600;"></span>
					</div>

					<div style="margin-bottom: 15px;">
						<label for="pbc-import-file-variations" class="button button-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
							<span class="dashicons dashicons-media-default"></span>
							<?php esc_html_e( 'Choose Variations CSV', 'pbc' ); ?>
						</label>
						<input type="file" id="pbc-import-file-variations" accept=".csv" style="display: none;" />
						<span id="pbc-import-filename-variations" style="margin-left: 10px; font-weight: 600;"></span>
					</div>

					<button id="pbc-import-button" class="button button-primary button-hero" style="display: inline-flex; align-items: center; gap: 8px;" disabled>
						<span class="dashicons dashicons-upload"></span>
						<?php esc_html_e( 'Import Data', 'pbc' ); ?>
					</button>
					<span id="pbc-import-spinner" class="spinner" style="float: none; margin: 5px 10px;"></span>

					<div id="pbc-import-log" class="pbc-log-container" style="display: none; margin-top: 20px; background: #f0f0f1; padding: 15px; border-left: 4px solid #2271b1; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; line-height: 1.6;">
					</div>
				</div>

				</div>

				<!-- Info Section -->
				<div class="pbc-info-section" style="background: #e7f5fe; padding: 15px; border-left: 4px solid #00a0d2;">
					<h3 style="margin-top: 0;">
						<span class="dashicons dashicons-info" style="color: #00a0d2;"></span>
						<?php esc_html_e( 'Important Information', 'pbc' ); ?></h3>
					<ul style="margin: 0;">
						<li><?php esc_html_e( 'Export generates two CSV files: one for phases and one for variations.', 'pbc' ); ?></li>
						<li><?php esc_html_e( 'Complex fields (dependencies, prices, image groups) are separated by pipes (|) and colons (:) instead of JSON.', 'pbc' ); ?></li>
						<li><?php esc_html_e( 'Items are identified by unique slugs, not post IDs, so they work across different installations.', 'pbc' ); ?></li>
						<li><?php esc_html_e( 'If an item with the same slug already exists, it will be skipped (no duplicates).', 'pbc' ); ?></li>
						<li><?php esc_html_e( 'You can import phases and variations independently or together.', 'pbc' ); ?></li>
						<li><?php esc_html_e( 'Image attachments are referenced by ID - you may need to migrate media files separately.', 'pbc' ); ?></li>
					</ul>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Render a recommendation group for a specific variation
	 *
	 * @param int    $first_var_id First variation ID.
	 * @param string $first_var_name First variation name.
	 * @param array  $other_phases Other phases.
	 * @param array  $variations_recommended Saved recommendations.
	 * @return void
	 */
	private function render_recommendation_group( $first_var_id, $first_var_name, $other_phases, $variations_recommended ) {
		// Get all phases including first to build phases_order.
		$all_phases = get_posts(
			array(
				'post_type'      => 'phases',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		$phases_order        = array();
		$prev_variations_ids = array();

		foreach ( $all_phases as $ph ) {
			// Skip parent phases.
			$child_phases = get_posts(
				array(
					'post_type'      => 'phases',
					'post_parent'    => $ph->ID,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( empty( $child_phases ) ) {
				$phases_order[] = $ph->menu_order;
			}
		}

		// Start with the first variation selected.
		$prev_variations_ids[0] = $first_var_id;

		?>
		<div class="recommendation-group" data-var-id="<?php echo (int) $first_var_id; ?>" style="margin-bottom:30px; border:1px solid #ddd; padding:15px; background:#f9f9f9; position:relative;">
			<button type="button" class="pbc-remove-recommendation button-link-delete" style="position:absolute; top:10px; right:10px; color:#a00; text-decoration:none;" title="<?php esc_attr_e( 'Remove this configuration', 'pbc' ); ?>">
				<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Remove', 'pbc' ); ?>
			</button>
		<h3 style="margin-top:0; padding-right:100px; cursor:pointer; user-select:none;" class="pbc-recommendation-toggle">
			<span class="dashicons dashicons-arrow-down-alt2" style="transition: transform 0.3s;"></span>
			<?php
			/* translators: %s: First variation name */
			echo esc_html( sprintf( __( 'Recommendations for: %s', 'pbc' ), $first_var_name ) );
			?>
		</h3>
			<div class="pbc-recommendation-content" style="display:none;">
			<table class="recommended-variations-table" style="width:100%; margin-top:10px; background:white;">
				<thead>
					<tr>
						<th style="text-align:left; width:30%; padding:10px; background:#f0f0f0;"><?php esc_html_e( 'Phase', 'pbc' ); ?></th>
						<th style="text-align:left; width:70%; padding:10px; background:#f0f0f0;"><?php esc_html_e( 'Recommended Variation', 'pbc' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$step = 1; // Step 0 is the first variation.
					foreach ( $other_phases as $phase ) {
						++$step;

						// Get all variations for this phase.
						$all_variations = get_posts(
							array(
								'post_type'      => 'variation',
								'posts_per_page' => -1,
								'meta_key'       => 'pbc_phase',
								'meta_value'     => $phase->ID,
								'orderby'        => 'title',
								'order'          => 'ASC',
								'fields'         => 'ids',
							)
						);

						if ( empty( $all_variations ) ) {
							continue;
						}

						// Build dependencies map.
						$variations_depends = array();
						foreach ( $all_variations as $variation_id ) {
							$depends = get_post_meta( $variation_id, 'pbc_depends', true );
							if ( ! empty( $depends ) && is_array( $depends ) ) {
								$variations_depends[ $variation_id ] = array();
								foreach ( $depends as $depend ) {
									if ( isset( $depend['pbc_depvar'] ) ) {
										$arr = explode( '|', $depend['pbc_depvar'] );
										if ( isset( $arr[0] ) && isset( $arr[1] ) ) {
											$order = array_search( (int) $arr[0], $phases_order, true );
											if ( false !== $order ) {
												$variations_depends[ $variation_id ][ $order ][] = (int) $arr[1];
											}
										}
									}
								}
							}
						}

						// Filter variations based on dependencies.
						$filtered_variations = array_filter(
							$all_variations,
							function ( $variation_id ) use ( $prev_variations_ids, $variations_depends, $step ) {
								if ( ! isset( $variations_depends[ $variation_id ] ) ) {
									return true; // No dependencies, always show.
								}
								$depends_ids = $variations_depends[ $variation_id ];
								for ( $i = 0; $i < $step - 1; $i++ ) {
									if ( isset( $prev_variations_ids[ $i ] ) && isset( $depends_ids[ $i ] ) ) {
										if ( ! in_array( $prev_variations_ids[ $i ], $depends_ids[ $i ], true ) ) {
											return false; // Dependency not met.
										}
									}
								}
								return true;
							}
						);

						// At this point, $all_variations is guaranteed to not be empty due to check above.
						$selected_var = isset( $variations_recommended[ $first_var_id ][ $phase->ID ] ) ? $variations_recommended[ $first_var_id ][ $phase->ID ] : '';

						// Store selected variation for next phase filtering.
						if ( $selected_var ) {
							$prev_variations_ids[ $step - 1 ] = $selected_var;
						}

						echo '<tr>';
						echo '<td style="padding:8px;"><strong>' . esc_html( $phase->menu_order . ' - ' . $phase->post_title ) . '</strong></td>';
						echo '<td style="padding:8px;">';
						echo '<select name="variations_recommended[' . (int) $first_var_id . '][' . (int) $phase->ID . ']" style="width:100%;" class="pbc-rec-select" data-phase-step="' . (int) $step . '" data-phase-id="' . (int) $phase->ID . '" data-first-var="' . (int) $first_var_id . '">';
						echo '<option value="">' . esc_html__( '-- No recommendation --', 'pbc' ) . '</option>';

						// Include ALL variations with dependency data attributes.
						foreach ( $all_variations as $var_id ) {
							$var_title = get_the_title( $var_id );
							$selected  = selected( $selected_var, $var_id, false );

						// Get dependencies for this variation.
						$var_depends  = isset( $variations_depends[ $var_id ] ) ? $variations_depends[ $var_id ] : array();
						$depends_json = ! empty( $var_depends ) ? wp_json_encode( $var_depends ) : '{}';

						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $selected is safely generated by selected() function.
						echo '<option value="' . (int) $var_id . '" ' . $selected . ' data-depends=\'' . esc_attr( $depends_json ) . '\'>' . esc_html( $var_title ) . '</option>';
						}
						echo '</select>';
						echo '<br/><small class="pbc-filtered-info" style="color:#666;"></small>';

						echo '</td>';
						echo '</tr>';
					}
					?>
				</tbody>
			</table>
			</div><!-- .pbc-recommendation-content -->
		</div>
		<?php
	}

	/**
	 * AJAX callback to render a recommendation group with filtered dependencies
	 *
	 * @return void
	 */
	public function pbc_render_recommendation_group_ajax() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'pbc_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'pbc' ) ) );
		}

		// Check capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'pbc' ) ) );
		}

		$var_id   = isset( $_POST['var_id'] ) ? (int) $_POST['var_id'] : 0;
		$var_name = isset( $_POST['var_name'] ) ? sanitize_text_field( wp_unslash( $_POST['var_name'] ) ) : '';

		if ( ! $var_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid variation ID.', 'pbc' ) ) );
		}

		// Get other phases.
		$phases = get_posts(
			array(
				'post_type'      => 'phases',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);

		$other_phases = array();
		$first_found  = false;

		foreach ( $phases as $phase ) {
			// Skip parent phases.
			$child_phases = get_posts(
				array(
					'post_type'      => 'phases',
					'post_parent'    => $phase->ID,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( ! empty( $child_phases ) ) {
				continue;
			}

			if ( ! $first_found ) {
				$first_found = true;
				continue; // Skip first phase.
			}

			$other_phases[] = $phase;
		}

		// Render the group with empty recommendations array.
		ob_start();
		$this->render_recommendation_group( $var_id, $var_name, $other_phases, array() );
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * Validate if a variation meets its dependencies
	 *
	 * @param int   $variation_id Variation ID to check.
	 * @param array $phases_order Array of phase menu orders.
	 * @param array $selected_variations Previously selected variations indexed by step.
	 * @param int   $current_step Current step being validated.
	 * @return bool True if dependencies are met, false otherwise.
	 */
	private function validate_variation_dependencies( $variation_id, $phases_order, $selected_variations, $current_step ) {
		$depends = get_post_meta( $variation_id, 'pbc_depends', true );

		// No dependencies = always valid.
		if ( empty( $depends ) || ! is_array( $depends ) ) {
			return true;
		}

		// Build dependency array.
		$variations_depends = array();
		foreach ( $depends as $depend ) {
			if ( ! isset( $depend['pbc_depvar'] ) ) {
				continue;
			}
			$arr = explode( '|', $depend['pbc_depvar'] );
			if ( isset( $arr[0] ) && isset( $arr[1] ) ) {
				$order                          = array_search( (int) $arr[0], $phases_order, true );
				$variations_depends[ $order ][] = (int) $arr[1];
			}
		}

		// Check each dependency.
		for ( $i = 0; $i < $current_step - 1; $i++ ) {
			if ( isset( $variations_depends[ $i ] ) && isset( $selected_variations[ $i ] ) ) {
				// This variation has a dependency on step $i.
				if ( ! in_array( $selected_variations[ $i ], $variations_depends[ $i ], true ) ) {
					// Dependency not met.
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * AJAX callback to get recommended variations
	 *
	 * @return void
	 */
	public function pbc_get_recommendations() {
		// Verify nonce.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in the next line.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'pbc_recommendation_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'pbc' ) ) );
		}

		$parent_phase       = isset( $_POST['parent_phase'] ) ? (int) $_POST['parent_phase'] : 0;
		$first_variation_id = isset( $_POST['first_variation'] ) ? (int) $_POST['first_variation'] : 0;

		// Validate required data.
		if ( ! $first_variation_id ) {
			wp_send_json_error( array( 'message' => __( 'No variation selected.', 'pbc' ) ) );
		}

		// Get all recommended variations configurations.
		$variations_recommended = get_option( 'pbc_variations_recommended', array() );

		if ( empty( $variations_recommended ) ) {
			wp_send_json_error( array( 'message' => __( 'No recommendations configured.', 'pbc' ) ) );
		}

		// If first_variation_id is provided, use that specific configuration.
		// Otherwise, try to auto-detect or use first available.
		if ( $first_variation_id && isset( $variations_recommended[ $first_variation_id ] ) ) {
			$selected_config = $variations_recommended[ $first_variation_id ];
	} elseif ( $first_variation_id && ! isset( $variations_recommended[ $first_variation_id ] ) ) {
		// First variation selected but no configuration exists for it.
		wp_send_json_error( array( 'message' => __( 'This option does not have a recommended configuration.', 'pbc' ) ) );
	} elseif ( 1 === count( $variations_recommended ) ) {
		// If no first variation specified, check if there's only one configuration.
		$selected_config = reset( $variations_recommended );
	} else {
		// Multiple configurations but no selection specified.
		wp_send_json_error( array( 'message' => __( 'Please select an option first before loading recommendations.', 'pbc' ) ) );
			}
		}

		// Get all phases for this parent phase.
		$args   = array(
			'numberposts' => -1,
			'post_type'   => 'phases',
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
			'post_parent' => $parent_phase,
		);
		$phases = get_posts( $args );

		// Build phases order array (including all phases, not just children).
		$all_phases   = get_posts(
			array(
				'post_type'      => 'phases',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
			)
		);
		$phases_order = array();
		foreach ( $all_phases as $ph ) {
			// Skip parent phases.
			$child_phases = get_posts(
				array(
					'post_type'      => 'phases',
					'post_parent'    => $ph->ID,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( empty( $child_phases ) ) {
				$phases_order[] = $ph->menu_order;
			}
		}

		$recommendations      = array();
		$selected_variations  = array(); // Indexed by step position (0-based).
		$step                 = 1;
		$invalid_dependencies = array();

		// Store the first variation as step 0.
		$selected_variations[0] = $first_variation_id;

		foreach ( $phases as $phase ) {
			$phase_id = $phase->ID;

			// Check if there's a recommendation for this phase in the selected config.
			if ( isset( $selected_config[ $phase_id ] ) && ! empty( $selected_config[ $phase_id ] ) ) {
				$variation_id = (int) $selected_config[ $phase_id ];

				// Get variation data.
				$variation = get_post( $variation_id );
				if ( ! $variation ) {
					continue;
				}

				// Validate dependencies BEFORE adding to recommendations.
				if ( ! $this->validate_variation_dependencies( $variation_id, $phases_order, $selected_variations, $step ) ) {
					$phase_name             = get_the_title( $phase_id );
					$variation_name         = get_the_title( $variation_id );
					$invalid_dependencies[] = sprintf(
						/* translators: %1$s: Phase name, %2$s: Variation name */
						__( '"%1$s" cannot use "%2$s" due to dependencies with previous selections.', 'pbc' ),
						$phase_name,
						$variation_name
					);
					// Don't add this to recommendations, skip it.
					++$step;
					continue;
				}

				// Get price.
				$price_group = get_post_meta( $variation_id, 'pbc_pricegroup', true );
				$price       = 0;
				$price_var   = '';

				if ( ! empty( $price_group ) && is_array( $price_group ) ) {
					// Use first price if available.
					$first_price = reset( $price_group );
					if ( isset( $first_price['pbc_pricem'] ) ) {
						$price = floatval( $first_price['pbc_pricem'] );
					}
					if ( isset( $first_price['pbc_meaprice'] ) ) {
						$price_var = $first_price['pbc_meaprice'];
					}
				}

				$recommendations[ $step ] = array(
					'phase_id'     => $phase_id,
					'variation_id' => $variation_id,
					'price'        => $price,
					'price_var'    => $price_var,
				);

				// Store this variation for dependency checking of next phases.
				$selected_variations[ $step ] = $variation_id;
			}
			++$step;
		}

	// If there are invalid dependencies, skip them and continue with valid recommendations.

		if ( empty( $recommendations ) ) {
			wp_send_json_error( array( 'message' => __( 'No recommendations found for this configuration.', 'pbc' ) ) );
		}

			wp_send_json_success(
				array(
					'recommendations' => $recommendations,
					'message'         => __( 'Recommendations loaded successfully.', 'pbc' ),
				)
			);
	}
}

new PBC_Admin_Plugin();
