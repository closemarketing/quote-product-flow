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
				'no_image_selected' => __('Please select an image file (jpeg, png) only', 'pbc'),
			)
		);
		wp_register_style( 'pbc-admin', WPPBC_PLUGIN_URL . 'includes/assets/admin.css', array(), WPPBC_VERSION );

		wp_enqueue_script(
			'pbc-admin-scripts',
			WPPBC_PLUGIN_URL . 'includes/assets/admin-scripts.js',
			array( 'jquery' ),
			WPPBC_VERSION,
		);

		wp_localize_script(
			'pbc-admin-scripts',
			'ajaxAction',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'pbc_enquiry_pdf_nonce' ),
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
			// Post Type :: View All Posts.
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Enquiries Received', 'pbc' ),
				'menu_title'  => __( 'Enquiries', 'pbc' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=enquiry',
				'function'    => null, // Doesn't need a callback function.
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

						add_meta_box(
							'license_meta_box',
							__( 'License', 'pbc' ),
							array(
								$this,
								'license_meta_box_callback',
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
							array( $this, 'price_updater_meta_box_callback', ),
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
		
		// Handle license form submission.
		if ( isset( $_POST['license_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['license_nonce'] ), 'Update_License_Options' ) ) {
			global $pbc_license_manager;
			
			if ( $pbc_license_manager ) {
				// Get the option group.
				$option_group = $pbc_license_manager->get_option_group();
				
				// Process license settings.
				$apikey_key = $pbc_license_manager->get_option_key( 'apikey' );
				$product_key = $pbc_license_manager->get_option_key( 'product_id' );
				$deactivate_key = $pbc_license_manager->get_option_key( 'deactivate_checkbox' );
				
				// Save the values.
				if ( isset( $_POST[ $apikey_key ] ) ) {
					update_option( $apikey_key, sanitize_text_field( wp_unslash( $_POST[ $apikey_key ] ) ) );
				}
				
				if ( isset( $_POST[ $product_key ] ) ) {
					update_option( $product_key, sanitize_text_field( wp_unslash( $_POST[ $product_key ] ) ) );
				}
				
				// Trigger validation (activation/deactivation).
				$input = array(
					$apikey_key => isset( $_POST[ $apikey_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $apikey_key ] ) ) : '',
					$product_key => isset( $_POST[ $product_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $product_key ] ) ) : '',
					$deactivate_key => isset( $_POST[ $deactivate_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $deactivate_key ] ) ) : '',
				);
				
				// Call the library's validation method.
				$this->validate_pbc_license( $input );
				
				return 'ok';
			}
		}
		
		// Verify nonce for regular form.
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
			);
			foreach ( $fields as $field_key => $field ) {
				if ( isset( $_POST[ $field_key ] ) ) {
					update_option( $field, trim( sanitize_text_field( wp_unslash( $_POST[ $field_key ] ) ) ) );
				}
			}

			$variations_images_flipped = isset( $_POST['variations_images_flipped'] ) ? $_POST['variations_images_flipped'] : array( '' );
			$variations_images_flipped = array_map( 'intval', $variations_images_flipped );
			update_option( 'variations_images_flipped', $variations_images_flipped );

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
		$percentage = isset( $_POST['percentage'] ) ? (int) esc_attr( $_POST['percentage'] ) / 100 : '';

		if ( check_ajax_referer( 'pbc_price_updater_nonce', 'nonce' ) ) {
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
				__( 'Changed %s variation prices', 'pbc' ),
				$count,
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
					<label class="block" for="variations_images_flipped"><?php _e( 'Flip Images Horizontal', 'pbc' ); ?></label>
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
									if ( ! empty( $variations_images_flipped ) && in_array( $var->ID, $variations_images_flipped ) ) {
										$selected = 'selected="selected"';
									} else {
										$selected = '';
									}
									echo '<option value="' . $var->ID . '" ' . $selected . '>' . str_pad( $phase->menu_order, 2, '0', STR_PAD_LEFT ) . ' - ' . $phase->post_title . ' - ' . $var->post_title . '</option>';
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
					<input style="width:100%;" type="text" name="admin_email_notification" value="<?php if ( $admin_email_notification ) { echo esc_html( $admin_email_notification ); } ?>" placeholder="<?php esc_attr_e( 'separate multiple emails by comma', 'pbc' ); ?>" />
				</fieldset>
				<fieldset>
					<label class="block" for="preview_width"><?php esc_html_e( 'Preview width', 'pbc' ); ?></label>
					<?php
						$preview_width = get_option( 'pbc_preview_width' );
					?>
					<input class="pbc_field" type="text" name="preview_width" value="<?php if ( $preview_width ) { echo $preview_width; } ?>" placeholder="<?php esc_html_e( 'default: 570', 'pbc' ); ?>" />
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
					<div class="pbc_field_preview"><?php if ( $pdf_image_selected ) { ?><img src="<?php echo esc_url( $pdf_image_selected ); ?>" alt="Image Preview" /><span class="pbc_field_preview_remove">&times;</span><?php } ?></div><input id="select_PDF_image" type="hidden" name="pdf_image_selected" value="<?php if ( $pdf_image_selected ) {
									echo esc_url( $pdf_image_selected );
								} ?>" data-imageId="<?php echo $this->get_attachment_id( $pdf_image_selected ); ?>" />
					<button class="select-image button select-image-selected" data-name="pdf_image_selected"><?php esc_html_e( 'Select image', 'pbc' ); ?></button>
				</fieldset>
				<fieldset>
					<label class="block" for="select_pdf_image_header"><?php esc_html_e( 'Set PDF Image Header (1000px width) Height 75px optional', 'pbc' ); ?></label>
					<?php
						$pdf_image_header = get_option( 'pbc_pdf_image_header' );
					?>
					<div class="pbc_field_preview"><?php if ( $pdf_image_header ) { ?><img src="<?php echo esc_url( $pdf_image_header ); ?>" alt="Image Preview" /><span class="pbc_field_preview_remove">&times;</span><?php } ?></div>
					<input id="select_pdf_image_header" type="hidden" name="pdf_image_header" value="<?php if ( $pdf_image_header ) {
						echo esc_url( $pdf_image_header );
					} ?>" data-imageId="<?php echo $this->get_attachment_id( $pdf_image_header ); ?>" /><button class="select-image button select-image-selected" data-name="pdf_image_header"><?php esc_html_e( 'Select image', 'pbc' ); ?></button>
				</fieldset>
				<fieldset>
					<label class="block" for="select_pdf_image_footer"><?php esc_html_e( 'Set PDF Image Footer (1000px width) Height 75px optional', 'pbc' ); ?></label>
					<?php
						$pdf_image_footer = get_option( 'pbc_pdf_image_footer' );
					?>
					<div class="pbc_field_preview"><?php if ( $pdf_image_footer ) { ?><img src="<?php echo esc_url( $pdf_image_footer ); ?>" alt="Image Preview" /><span class="pbc_field_preview_remove">&times;</span><?php } ?></div>
					<input id="select_pdf_image_footer" type="hidden" name="pdf_image_footer" value="<?php if ( $pdf_image_footer ) {
						echo esc_url( $pdf_image_footer );
					} ?>" data-imageId="<?php echo $this->get_attachment_id( $pdf_image_footer ); ?>" /><button class="select-image button select-image-selected" data-name="pdf_image_footer"><?php esc_html_e( 'Select image', 'pbc' ); ?></button>
				</fieldset>
				<fieldset>
					<label class="block" for="select_pdf_color_odd"><?php esc_html_e( 'Color for odd entries (hex code)', 'pbc' ); ?></label><?php
						$pdf_color_odd = get_option( 'pbc_pdf_color_odd' );
					?>
					<input type="text" name="pdf_color_odd" value="<?php if ( $pdf_color_odd ) { echo esc_url( $pdf_color_odd ); } ?>" class="pbc_color_picker" />
				</fieldset>
				<fieldset>
					<label class="block" for="select_pdf_color_total"><?php esc_html_e( 'Color for total (hex code)', 'pbc' ); ?></label>
					<?php
						$pdf_color_total = get_option( 'pbc_pdf_color_total' );
					?>
					<input type="text" name="pdf_color_total" value="<?php if ( $pdf_color_total ) { echo esc_url( $pdf_color_total ); } ?>" class="pbc_color_picker" />
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
	 * License Meta Box Callback
	 *
	 * Renders license settings using the License Manager library
	 */
	public function license_meta_box_callback() {
		// Get the global license instance.
		global $pbc_license_manager;

		if ( ! $pbc_license_manager ) {
			echo '<p>' . esc_html__( 'License manager not initialized.', 'pbc' ) . '</p>';
			return;
		}
		?>
		<form action="" method="post" enctype="multipart/form-data" id="pbc_license_form">
			<div class="content">
				<?php
				// Render license fields manually.
				$apikey_key = $pbc_license_manager->get_option_key( 'apikey' );
				$product_key = $pbc_license_manager->get_option_key( 'product_id' );
				$deactivate_key = $pbc_license_manager->get_option_key( 'deactivate_checkbox' );
				
				$api_key = $pbc_license_manager->get_option_value( 'apikey' );
				$product_id = $pbc_license_manager->get_option_value( 'product_id' );
				$is_active = $pbc_license_manager->is_license_active();
				?>
				<fieldset>
					<label class="block" for="<?php echo esc_attr( $apikey_key ); ?>">
						<?php esc_html_e( 'License API Key', 'pbc' ); ?>
					</label>
					<input style="width:100%;" type="text" 
						   name="<?php echo esc_attr( $apikey_key ); ?>" 
						   id="<?php echo esc_attr( $apikey_key ); ?>"
						   value="<?php echo esc_attr( $api_key ); ?>" 
						   placeholder="<?php esc_html_e( 'Enter your API Key', 'pbc' ); ?>" />
					<p class="description">
						<?php esc_html_e( 'Enter your license API key. You can find this in your account dashboard.', 'pbc' ); ?>
					</p>
				</fieldset>
				
				<fieldset>
					<label class="block" for="<?php echo esc_attr( $product_key ); ?>">
						<?php esc_html_e( 'Product ID', 'pbc' ); ?>
					</label>
					<input style="width:100%;" type="text" 
						   name="<?php echo esc_attr( $product_key ); ?>" 
						   id="<?php echo esc_attr( $product_key ); ?>"
						   value="<?php echo esc_attr( $product_id ); ?>" 
						   placeholder="<?php esc_html_e( 'Enter Product ID', 'pbc' ); ?>" />
					<p class="description">
						<?php esc_html_e( 'Enter the product ID associated with your license.', 'pbc' ); ?>
					</p>
				</fieldset>
				
				<fieldset>
					<label class="block"><?php esc_html_e( 'License Status', 'pbc' ); ?></label>
					<p>
						<strong style="color: <?php echo $is_active ? '#00a32a' : '#d63638'; ?>;">
							<?php echo $is_active ? '✓ ' . esc_html__( 'Activated', 'pbc' ) : '✗ ' . esc_html__( 'Deactivated', 'pbc' ); ?>
						</strong>
					</p>
				</fieldset>
				
				<?php if ( $is_active ) : ?>
				<fieldset>
					<label>
						<input type="checkbox" 
							   name="<?php echo esc_attr( $deactivate_key ); ?>" 
							   value="on" />
						<?php esc_html_e( 'Deactivate license', 'pbc' ); ?>
					</label>
					<p class="description">
						<?php esc_html_e( 'Check this box to deactivate the license on this site.', 'pbc' ); ?>
					</p>
				</fieldset>
				<?php endif; ?>
			</div>

			<div class="save_bar">
				<?php wp_nonce_field( 'Update_License_Options', 'license_nonce' ); ?>
				<input type="submit" value="<?php esc_html_e( 'Save license', 'pbc' ); ?>" class="button button-primary submit-button" />
			</div>
		</form>
		<?php
		echo '<div class="settings">';
		echo '<h2>' . esc_html__( 'What is the license for?', 'pbc' ) . '</h2>';
		echo '<p>';
		printf(
			__( 'With the <a href="%s" target="_blank">Product Budget Configurator</a> license, you\'ll have updates and automatic fixes to what\'s new or change in your system, so you\'ll always have automatic translations working.', 'pbc' ),
			'https://close.technology/wordpress-plugins/product-budget-configurator/?utm_source=WordPress-Settings'
		);
		echo '</p>';
		echo '</div><div class="help">';
		echo '<h2>' . esc_html__( 'How do I get a license?', 'pbc' ) . '</h2>';
		echo '<p>';
		printf(
			__( 'Visit the <a href="%s" target="_blank">Product Budget Configurator</a> page and purchase the licenses you need, depending on the number of WordPress MultiSites you\'re using.', 'pbc' ),
			'https://close.technology/wordpress-plugins/product-budget-configurator/?utm_source=WordPress-Settings'
		);
		echo '</p>';
		echo '<p style="color:#F0F0F1;">' . esc_html__( 'Instance:', 'pbc' ) . ' ' . esc_html( $pbc_license_manager->get_option_value( 'instance' ) ) . '</p>';
		echo '</div>';
	}

	/**
	 * Validate PBC License
	 *
	 * Validates license using the library's validation method
	 *
	 * @param array $input Input data.
	 * @return void
	 */
	private function validate_pbc_license( $input ) {
		global $pbc_license_manager;
		
		if ( ! $pbc_license_manager ) {
			return;
		}
		
		$apikey_key = $pbc_license_manager->get_option_key( 'apikey' );
		$product_key = $pbc_license_manager->get_option_key( 'product_id' );
		$deactivate_key = $pbc_license_manager->get_option_key( 'deactivate_checkbox' );
		
		$api_key = isset( $input[ $apikey_key ] ) ? trim( $input[ $apikey_key ] ) : '';
		$api_key = sanitize_text_field( $api_key );
		$activation_status = get_option( $pbc_license_manager->get_option_key( 'activated' ) );
		$checkbox_status = get_option( $deactivate_key );
		$current_api_key = get_option( $apikey_key, '' );
		
		// Product ID.
		if ( isset( $input[ $product_key ] ) ) {
			$new_product_id = absint( $input[ $product_key ] );
			if ( ! empty( $new_product_id ) ) {
				update_option( $product_key, $new_product_id );
			}
		}
		
		// Deactivate License.
		if ( isset( $input[ $deactivate_key ] ) && 'on' === $input[ $deactivate_key ] ) {
			$args = array(
				'api_key' => ! empty( $api_key ) ? $api_key : '',
			);
			$deactivation_result = $pbc_license_manager->license_deactivate( $args );
			
			if ( ! empty( $deactivation_result ) && is_array( $deactivation_result ) ) {
				if ( true === $deactivation_result['success'] && true === $deactivation_result['deactivated'] ) {
					update_option( $pbc_license_manager->get_option_key( 'activated' ), 'Deactivated' );
					update_option( $apikey_key, '' );
					update_option( $product_key, '' );
					add_settings_error( 'license_deactivate', 'deactivate_msg', esc_html__( 'License deactivated successfully.', 'pbc' ), 'updated' );
					return;
				}
				
				if ( isset( $deactivation_result['data']['error_code'] ) ) {
					add_settings_error( 'license_error', 'license_client_error', esc_attr( $deactivation_result['data']['error'] ), 'error' );
					update_option( $pbc_license_manager->get_option_key( 'activated' ), 'Deactivated' );
				}
			}
			
			// Remove anyway.
			update_option( $pbc_license_manager->get_option_key( 'activated' ), 'Deactivated' );
			update_option( $apikey_key, '' );
			update_option( $product_key, '' );
			return;
		}
		
		// Activate License.
		if ( 'Deactivated' === $activation_status || '' === $activation_status || '' === $api_key || 'on' === $checkbox_status || $current_api_key !== $api_key ) {
			// Replace existing key if different.
			if ( ! empty( $current_api_key ) && $current_api_key !== $api_key ) {
				$pbc_license_manager->replace_license_key( $current_api_key );
			}
			
			$activation_result = $pbc_license_manager->license_activate( $api_key );
			
			if ( ! empty( $activation_result ) ) {
				$activate_results = json_decode( $activation_result, true );
				
				if ( true === $activate_results['success'] && true === $activate_results['activated'] ) {
					add_settings_error( 'activate_text', 'activate_msg', __( 'License activated successfully.', 'pbc' ) . ' ' . esc_attr( $activate_results['message'] ), 'updated' );
					update_option( $apikey_key, $api_key );
					update_option( $pbc_license_manager->get_option_key( 'activated' ), 'Activated' );
					update_option( $deactivate_key, 'off' );
				}
				
				if ( false === $activate_results && ! empty( get_option( $pbc_license_manager->get_option_key( 'activated' ) ) ) ) {
					add_settings_error( 'api_key_check', 'api_key_check_error', esc_html__( 'Connection failed to the License Key API server. Try again later.', 'pbc' ), 'error' );
					update_option( $pbc_license_manager->get_option_key( 'activated' ), 'Deactivated' );
				}
				
				if ( isset( $activate_results['data']['error_code'] ) ) {
					add_settings_error( 'license_error', 'license_client_error', esc_attr( $activate_results['data']['error'] ), 'error' );
					update_option( $pbc_license_manager->get_option_key( 'activated' ), 'Deactivated' );
				}
			} else {
				add_settings_error( 'not_activated', 'not_activated_error', esc_html__( 'The API Key activation could not be completed due to an unknown error.', 'pbc' ), 'error' );
			}
		}
	}

	/*
	 * Disables dropdown dates
	 */
	public function disable_months_dropdown( $false, $post_type ) {
		$disable_months_dropdown = $false;
		$disable_post_types      = array( 'variation', 'phases' );

		if ( in_array( $post_type, $disable_post_types ) ) {
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

		$item = [
			'pbc_date'         => get_the_date( 'd-m-Y', $post_id ),
			'pbc_parent_phase' => $parent_phase,
			'pbc_contact'      => [
				'name'     => get_post_meta( $post_id, 'pbc_enquiry_name', true ),
				'phone'    => get_post_meta( $post_id, 'pbc_enquiry_phone', true ),
				'email'    => get_post_meta( $post_id, 'pbc_enquiry_email', true ),
				'city'     => get_post_meta( $post_id, 'pbc_enquiry_city', true ),
				'state'    => get_post_meta( $post_id, 'pbc_enquiry_state', true ),
				'comments' => get_post_meta( $post_id, 'pbc_enquiry_comments', true ),
			],
			'pbc_enquiry'      => $post_id,
			'pbc_admin'        => true,
		];

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
		// only variation post type, exit earlier
		if ( 'variation' != $current_screen->post_type ) {
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
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
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
}

new PBC_Admin_Plugin();
