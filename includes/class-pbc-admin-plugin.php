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
				<input type="text" name="pdf_color_total" value="<?php if ( $pdf_color_total ) { echo esc_url( $pdf_color_total ); } ?>" class="pbc_color_picker" />
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
						<?php esc_html_e( 'Important Information', 'pbc' ); ?>
					</h3>
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
}

new PBC_Admin_Plugin();
