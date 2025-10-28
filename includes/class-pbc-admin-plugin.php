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

		// Creates license activation.
		register_activation_hook( WPPBC_PLUGIN, array( $this, 'license_instance_activation' ) );
		// Check for external connection blocking.
		add_action( 'admin_notices', array( $this, 'check_external_blocking' ) );

		if ( 'Activated' !== get_site_option( 'pbc_license_activated' ) ) {
			add_action( 'admin_notices', array( $this, 'inactive_notice' ) );
		}
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

		if ( isset( $_POST['submit_license'] ) ) {
			$license_apikey     = isset( $_POST['pbc_license_apikey'] ) ? sanitize_text_field( wp_unslash( $_POST['pbc_license_apikey'] ) ) : '';
			$license_product_id = isset( $_POST['pbc_license_product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['pbc_license_product_id'] ) ) : '';

			update_option( 'pbc_license_apikey', $license_apikey );
			update_option( 'pbc_license_product_id', $license_product_id );
			$this->validate_license( $_POST );
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
	 * General Settings Meta Box Callback
	 *
	 * Callback function for add_meta_box import section
	 */
	public function license_meta_box_callback() {
		?>
		<form action="" method="post" enctype="multipart/form-data" id="pbc_license_form">
			<div class="content">
				<fieldset>
					<label class="block" for="pbc_license_apikey"><?php esc_html_e( 'License API Key', 'pbc' ); ?></label>
					<?php
					$license_apikey = get_option( 'pbc_license_apikey' );
					?>
					<input style="width:100%;" type="text" name="pbc_license_apikey" value="
					<?php
					if ( $license_apikey ) {
						echo $license_apikey; }
					?>
					" placeholder="<?php esc_html_e( 'License API Key', 'pbc' ); ?>" />
				</fieldset>
				<fieldset>
					<label class="block" for="pbc_license_product_id"><?php esc_html_e( 'License Product ID', 'pbc' ); ?></label>
					<?php
					$license_product_id = get_option( 'pbc_license_product_id' );
					?>
					<input style="width:100%;" type="text" name="pbc_license_product_id" value="
					<?php
					if ( $license_product_id ) {
						echo $license_product_id; }
					?>
					" placeholder="<?php esc_html_e( 'License Product ID', 'pbc' ); ?>" />
				</fieldset>
				<fieldset>
					<label class="block" for="pbc_license_status"><?php esc_html_e( 'Status:', 'pbc' ); ?></label>
					<p><strong><?php $this->license_status_callback(); ?></strong></p>
				</fieldset>
			</div>

			<div class="save_bar">
				<input type="hidden" name="submit_license" value="true"/>
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
		echo '<p style="color:#F0F0F1;">' . esc_html__( 'Instance:', 'pbc' ) . ' ' . get_option( 'pbc_license_instance' ) . '</p>';
		echo '</div>';
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
				$('#print-message').html('<img src="<?php echo WPPBC_PLUGIN_URL; ?>/assets/loading.gif"/>');
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
	 * # LICENSE
	 * ---------------------------------------------------------------------------------------------------- */

	/**
	 * Displays an inactive notice when the software is inactive.
	 */
	public function inactive_notice() {
		/**
		 * @since 2.5.1
		 *
		 * Filter wc_am_client_inactive_notice_override
		 * If set to false inactive_notice() method will be disabled.
		 */
		if ( apply_filters( 'wpat_client_inactive_notice_override', true ) ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			if ( isset( $_GET['page'] ) && 'pbc' == $_GET['page'] ) {
				return;
			}
			echo '<div class="notice notice-error">';
			echo '<p>';
			printf(
				__( 'The <strong>%1$s</strong> License has not been activated, so the plugin is inactive! %2$sClick here%3$s to activate it.', 'pbc' ),
				esc_attr( WPPBC_ITEM_NAME ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=pbc_menu' ) ) . '">',
				'</a>'
			);
			echo '</p></div>';
		}
	}
	/**
	 * Callback for Setting license API key
	 *
	 * @return void
	 */
	public function license_status_callback() {
		if ( $this->get_api_key_status( true ) ) {
			$license_status_check = esc_html__( 'Activated', 'pbc' );
			update_option( 'pbc_license_activated', 'Activated' );
			update_option( 'pbc_license_deactivate_checkbox', 'off' );
		} else {
			$license_status_check = esc_html__( 'Deactivated', 'pbc' );
		}

		echo esc_attr( $license_status_check );
	}
	/**
	 * Validates license option
	 *
	 * @param array $input Settings input option.
	 * @return mixed|string
	 */
	public function validate_license( $input ) {
		// Load existing options, validate, and update with changes from input before returning.
		$api_key           = trim( $input['pbc_license_apikey'] );
		$activation_status = get_option( 'pbc_license_activated' );
		$checkbox_status   = get_option( 'pbc_license_deactivate_checkbox' );
		$current_api_key   = ! empty( get_option( 'pbc_license_apikey' ) ) ? get_option( 'pbc_license_apikey' ) : '';

		/**
		* @since 2.3
		*/
		if ( isset( $input['pbc_license_product_id'] ) ) {
			$new_product_id = absint( $input['pbc_license_product_id'] );

			if ( ! empty( $new_product_id ) ) {
				update_option( 'pbc_license_product_id', $new_product_id );
			}
		}

		// Deactivates API Key key activation.
		if ( isset( $input['pbc_license_deactivate_checkbox'] ) && 'on' === $input['pbc_license_deactivate_checkbox'] ) {
			$args                = array(
				'api_key' => ! empty( $api_key ) ? $api_key : '',
			);
			$deactivation_result = $this->license_deactivate( $args );

			if ( ! empty( $deactivation_result ) ) {

			if ( true === $deactivation_result['success'] && true === $deactivation_result['deactivated'] ) {
				update_option( 'pbc_license_activated', 'Deactivated' );
				update_option( 'pbc_license_apikey', '' );
				update_option( 'pbc_license_product_id', '' );
				add_settings_error( 'wc_am_deactivate_text', 'deactivate_msg', esc_html__( 'License AutoTranslate deactivated. ', 'pbc' ) . esc_attr( "{$deactivation_result['activations_remaining']}." ), 'updated' );

				return;
			}

			if ( isset( $deactivation_result['data'] ) && isset( $deactivation_result['data']['error_code'] ) && ! empty( $deactivation_result['data']['error_code'] ) ) {
				add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', esc_attr( "{$deactivation_result['data']['error']}" ), 'error' );
				update_option( 'pbc_license_activated', 'Deactivated' );
			}
			}
			return;
		}

		// Should match the settings_fields() value.
		if ( 'Deactivated' == $activation_status || '' == $activation_status || '' == $api_key || 'on' == $checkbox_status || $current_api_key != $api_key ) {

			/**
			* If this is a new key, and an existing key already exists in the database,
			* try to deactivate the existing key before activating the new key.
			*/
			if ( ! empty( $current_api_key ) && $current_api_key != $api_key ) {
				$this->replace_license_key( $current_api_key );
			}

			$activation_result = $this->license_activate( $api_key );

			if ( ! empty( $activation_result ) ) {
				$activate_results = json_decode( $activation_result, true );

				if ( true === $activate_results['success'] && true === $activate_results['activated'] ) {
					add_settings_error( 'activate_text', 'activate_msg', __( 'AutoTranslate activated. ', 'pbc' ) . esc_attr( "{$activate_results['message']}." ), 'updated' );

					update_option( 'pbc_license_apikey', $api_key );
					update_option( 'pbc_license_activated', 'Activated' );
					update_option( 'pbc_license_deactivate_checkbox', 'off' );
				}

				if ( false == $activate_results && ! empty( get_option( 'pbc_license_activated' ) ) ) {
					add_settings_error( 'api_key_check_text', 'api_key_check_error', esc_html__( 'Connection failed to the License Key API server. Try again later. There may be a problem on your server preventing outgoing requests, or the store is blocking your request to activate the plugin/theme.', 'pbc' ), 'error' );
					update_option( 'pbc_license_activated', 'Deactivated' );
				}

				if ( isset( $activate_results['data']['error_code'] ) && ! empty( get_option( 'pbc_license_activated' ) ) ) {
					add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', esc_attr( "{$activate_results['data']['error']}" ), 'error' );
					update_option( 'pbc_license_activated', 'Deactivated' );
				}
			} else {
				add_settings_error( 'not_activated_empty_response_text', 'not_activated_empty_response_error', esc_html__( 'The API Key activation could not be commpleted due to an unknown error possibly on the store server The activation results were empty.', 'pbc' ), 'updated' );
			}
		} // End Plugin Activation
	}
	/**
	 * Sends the request to activate to the API Manager.
	 *
	 * @param array $api_key API Key to activate.
	 *
	 * @return string
	 */
	public function license_activate( $api_key ) {
		if ( empty( $api_key ) ) {
			add_settings_error( 'not_activated_text', 'not_activated_error', esc_html__( 'The API Key is missing from the deactivation request.', 'pbc' ), 'updated' );

			return '';
		}

		$defaults            = $this->get_license_defaults( 'activate', true );
		$defaults['api_key'] = $api_key;
		$target_url          = esc_url_raw( $this->create_software_api_url( $defaults ) );
		$request             = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			// Request failed.
			return '';
		}

		return wp_remote_retrieve_body( $request );
	}

	/**
	 * Sends the request to deactivate to the API Manager.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function license_deactivate( $args ) {
		if ( empty( $args ) ) {
			add_settings_error( 'not_deactivated_text', 'not_deactivated_error', esc_html__( 'The API Key is missing from the deactivation request.', 'pbc' ), 'updated' );

			return '';
		}

		$defaults   = $this->get_license_defaults( 'deactivate' );
		$args       = wp_parse_args( $defaults, $args );
		$target_url = esc_url_raw( $this->create_software_api_url( $args ) );
		$request    = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );
		$body_json  = wp_remote_retrieve_body( $request );
		$result_api = json_decode( $body_json, true );

		$error = ! empty( $result_api['error'] ) ? $result_api['error'] : '';

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 || $error ) {
			// Request failed.
			add_settings_error(
				'not_deactivated_empty_response_text',
				'not_deactivated_empty_response_error',
				$error,
				'error'
			);
			return;
		}

		return $result_api;
	}
	/**
	 * Returns true if the API Key status is Activated.
	 *
	 * @since 2.1
	 *
	 * @param bool $live Do not set to true if using to activate software. True is for live status checks after activation.
	 *
	 * @return bool
	 */
	public function get_api_key_status( $live = false ) {
		/**
		 * Real-time result.
		 *
		 * @since 2.5.1
		 */
		if ( $live ) {
			$license_status = $this->license_key_status();

			return ! empty( $license_status ) && ! empty( $license_status['data']['activated'] ) && $license_status['data']['activated'];
		}

		/**
		 * If $live === false.
		 *
		 * Stored result when first activating software.
		 */
		return 'Activated' === get_option( 'pbc_license_activated' );
	}

	/**
	 * Returns the API Key status by querying the Status API function from the WooCommerce API Manager on the server.
	 *
	 * @return array|mixed|object
	 */
	public function license_key_status() {
		$status = $this->status();

		return ! empty( $status ) ? json_decode( $this->status(), true ) : $status;
	}

	/**
	 * Sends the status check request to the API Manager.
	 *
	 * @return bool|string
	 */
	public function status() {
		if ( empty( get_option( 'pbc_license_apikey' ) ) ) {
			return '';
		}

		$defaults   = $this->get_license_defaults( 'status' );
		$target_url = esc_url_raw( $this->create_software_api_url( $defaults ) );
		$request    = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			// Request failed.
			return '';
		}

		return wp_remote_retrieve_body( $request );
	}

	/**
	 * Get license defaults
	 *
	 * @param [type] $action
	 * @return array
	 */
	private function get_license_defaults( $action, $software_version = false ) {
		$api_key    = get_option( 'pbc_license_apikey' );
		$product_id = get_option( 'pbc_license_product_id' );

		$defaults = array(
			'wc_am_action' => $action,
			'api_key'      => $api_key,
			'product_id'   => $product_id,
			'instance'     => get_option( 'pbc_license_instance' ),
			'object'       => str_ireplace( array( 'http://', 'https://' ), '', home_url() ),
		);

		if ( $software_version ) {
			$defaults['software_version'] = WPPBC_VERSION;
		}

		return $defaults;
	}

	/**
	 * Builds the URL containing the API query string for activation, deactivation, and status requests.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function create_software_api_url( $args ) {
		return add_query_arg( 'wc-api', 'wc-am-api', WPPBC_URL_API ) . '&' . http_build_query( $args );
	}

	/**
	 * Generate the default data.
	 */
	public function license_instance_activation() {
		$instance_exists = get_option( 'pbc_license_instance' );

		if ( false === $instance_exists ) {
			update_option( 'pbc_license_instance', wp_generate_password( 12, false ) );
		}
	}

	/**
	 * Deactivate the current API Key before activating the new API Key
	 *
	 * @param string $current_api_key
	 */
	public function replace_license_key( $current_api_key ) {
		$args = array(
			'api_key' => $current_api_key,
		);

		$this->license_deactivate( $args );
	}

	/**
	 * Sends and receives data to and from the server API
	 *
	 * @since  2.0
	 *
	 * @param array $args
	 *
	 * @return bool|string $response
	 */
	public function send_query( $args ) {
		$target_url = esc_url_raw( add_query_arg( 'wc-api', 'wc-am-api', WPPBC_URL_API ) . '&' . http_build_query( $args ) );
		error_log( 'target_url:' . $target_url );
		$request = wp_safe_remote_post( $target_url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
			return false;
		}

		$response = wp_remote_retrieve_body( $request );

		return ! empty( $response ) ? $response : false;
	}

	/**
	 * Check for updates against the remote server.
	 *
	 * @since  2.0
	 *
	 * @param object $transient Transient plugins.
	 *
	 * @return object
	 */
	public function update_check( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$args = array(
			'wc_am_action' => 'update',
			'slug'         => 'pbc',
			'plugin_name'  => 'pbc',
			'version'      => WPPBC_VERSION,
			'product_id'   => get_option( 'pbc_license_product_id' ),
			'api_key'      => get_option( 'pbc_license_apikey' ),
			'instance'     => get_option( 'pbc_license_instance' ),
		);

		// Check for a plugin update.
		$response = json_decode( $this->send_query( $args ), true );

		if ( isset( $response['data']['error_code'] ) ) {
			add_settings_error( 'wc_am_client_error_text', 'wc_am_client_error', "{$response['data']['error']}", 'error' );
		}

		if ( false !== $response && true === $response['success'] ) {
			$new_version  = (string) $response['data']['package']['new_version'];
			$curr_version = (string) WPPBC_VERSION;

			$package = array(
				'id'             => $response['data']['package']['id'],
				'slug'           => $response['data']['package']['slug'],
				'plugin'         => $response['data']['package']['plugin'],
				'new_version'    => $response['data']['package']['new_version'],
				'url'            => $response['data']['package']['url'],
				'tested'         => $response['data']['package']['tested'],
				'package'        => $response['data']['package']['package'],
				'upgrade_notice' => $response['data']['package']['upgrade_notice'],
			);

			if ( version_compare( $new_version, $curr_version, '>' ) ) {
				$transient->response['pbc'] = (object) $package;
				unset( $transient->no_update['pbc'] );
			}
		}

		return $transient;
	}

	/**
	 * API request for informatin.
	 *
	 * If `$action` is 'query_plugins' or 'plugin_information', an object MUST be passed.
	 * If `$action` is 'hot_tags` or 'hot_categories', an array should be passed.
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string             $action The type of information being requested from the Plugin Install API.
	 * @param object             $args
	 *
	 * @return object
	 */
	public function information_request( $result, $action, $args ) {
		// Check if this plugins API is about this plugin.
		if ( isset( $args->slug ) ) {
			if ( 'pbc' !== $args->slug ) {
				return $result;
			}
		} else {
			return $result;
		}

		$args = array(
			'wc_am_action' => 'plugininformation',
			'plugin_name'  => 'pbc',
			'version'      => WPPBC_VERSION,
			'product_id'   => get_option( 'pbc_license_product_id' ),
			'api_key'      => get_option( 'pbc_license_apikey' ),
			'instance'     => get_option( 'pbc_license_instance' ),
			'object'       => str_ireplace( array( 'http://', 'https://' ), '', home_url() ),
		);

		$response = unserialize( $this->send_query( $args ) );

		if ( isset( $response ) && is_object( $response ) && false !== $response ) {
			return $response;
		}

		return $result;
	}

	/**
	 * Check for external blocking contstant.
	 */
	public function check_external_blocking() {
		// show notice if external requests are blocked through the WP_HTTP_BLOCK_EXTERNAL constant.
		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && true === WP_HTTP_BLOCK_EXTERNAL ) {
			// check if our API endpoint is in the allowed hosts.
			$host = parse_url( WPPBC_URL_API, PHP_URL_HOST );

			if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) || stristr( WP_ACCESSIBLE_HOSTS, $host ) === false ) {
				?>
				<div class="notice notice-error">
					<p>
						<?php
						printf(
							esc_html__( '<b>Warning!</b> You\'re blocking external requests which means you won\'t be able to get %1$s updates. Please add %2$s to %3$s.', 'pbc' ),
							'AutoTranslate',
							'<strong>' . esc_html( $host ) . '</strong>',
							'<code>WP_ACCESSIBLE_HOSTS</code>'
						);
						?>
					</p>
				</div>
				<?php
			}
		}
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
