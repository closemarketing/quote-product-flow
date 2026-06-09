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

		add_action( 'wp_ajax_pbc_restart_process', array( $this, 'pbc_restart_process' ) );
		add_action( 'wp_ajax_nopriv_pbc_restart_process', array( $this, 'pbc_restart_process' ) );
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
		if ( ! empty( $screen ) && (
			'pbc_menu' === $screen->parent_base ||
			in_array( $screen->post_type, array( 'variation', 'phases' ), true )
		) ) {
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
				'no_image_selected' => __( 'Please select an image file (jpeg, png) only', 'product-budget-configurator' ),
			)
		);
		wp_register_style( 'pbc-admin', WPPBC_PLUGIN_URL . 'includes/assets/admin.css', array(), WPPBC_VERSION );

		wp_enqueue_script( 'media-upload' );
		wp_enqueue_media();
		wp_enqueue_script(
			'pbc-admin-scripts',
			WPPBC_PLUGIN_URL . 'includes/assets/admin-scripts.js',
			array( 'jquery', 'media-upload' ),
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
			__( 'Product Budget Configurator', 'product-budget-configurator' ),
			'PBC',
			'manage_options',
			'pbc_menu',
			array( $this, 'pbc_display_admin_page' ),
			'dashicons-tagcloud',
			99
		);

		$submenu_pages = array(
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Variations in Phases', 'product-budget-configurator' ),
				'menu_title'  => __( 'Variations', 'product-budget-configurator' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=variation',
				'function'    => null,
			),
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Phases of Configurator', 'product-budget-configurator' ),
				'menu_title'  => __( 'Phases', 'product-budget-configurator' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=phases',
				'function'    => null,
			),
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Sections in variations', 'product-budget-configurator' ),
				'menu_title'  => __( 'Sections', 'product-budget-configurator' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit-tags.php?taxonomy=variation_tag',
				'function'    => null,
			),
			array(
				'parent_slug' => 'pbc_menu',
				'page_title'  => __( 'Product Budget Configurator', 'product-budget-configurator' ),
				'menu_title'  => __( 'Settings', 'product-budget-configurator' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'pbc_menu',
				'function'    => array( $this, 'pbc_display_admin_page' ),
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

		do_action( 'pbc_admin_register_menus' );
	}

	/**
	 * Page settings with metaboxes
	 *
	 * @return void
	 */
	public function pbc_display_admin_page() {
		$return = $this->save_post_options();
		if ( 'ok' === $return ) {
			$update = __( 'Successfully Saved!', 'product-budget-configurator' );
		} elseif ( 'error' === $return ) {
			$error = __( 'Error saving settings', 'product-budget-configurator' );
		}
		?>
		<div class='wrap pbc-settings-wrap'>
			<div class="pbc-settings-header">
				<h1><span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html( $GLOBALS['title'] ); ?></h1>
				<p class="pbc-settings-subtitle"><?php esc_html_e( 'Configure your product budget configurator global settings', 'product-budget-configurator' ); ?></p>
			</div>

			<?php if ( isset( $update ) ) { ?>
				<div id="message" class="notice notice-success is-dismissible"><p><?php echo esc_html( $update ); ?></p></div>
			<?php } ?>
			<?php if ( isset( $error ) ) { ?>
				<div id="message" class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
			<?php } ?>

			<form action="" method="post" enctype="multipart/form-data" id="pbc_general_settings_form">
				<div class="pbc-settings-container pbc-two-columns">
					<!-- Left Column -->
					<div class="pbc-column-left">
						<?php $this->render_general_configuration_section(); ?>
						<?php
						if ( has_action( 'pbc_admin_settings_left_column' ) ) {
							do_action( 'pbc_admin_settings_left_column' );
						} else {
							$this->render_pro_locked_card(
								'dashicons-phone',
								__( 'Support Contact', 'product-budget-configurator' ),
								array(
									array( 'type' => 'checkbox', 'label' => __( 'Enable support contact buttons in configurator', 'product-budget-configurator' ) ),
									array( 'type' => 'text', 'label' => __( 'Support Phone Number', 'product-budget-configurator' ) ),
									array( 'type' => 'text', 'label' => __( 'Support Email Address', 'product-budget-configurator' ) ),
								)
							);
						}
						?>
					</div>

					<!-- Right Column -->
					<div class="pbc-column-right">
						<?php $this->render_pdf_configuration_section(); ?>
						<?php
						if ( has_action( 'pbc_admin_settings_right_column' ) ) {
							do_action( 'pbc_admin_settings_right_column' );
						} else {
							$this->render_pro_locked_card(
								'dashicons-groups',
								__( 'User Role Specific Options', 'product-budget-configurator' ),
								array(
									array( 'type' => 'table', 'label' => __( 'Role discounts and price visibility per user role', 'product-budget-configurator' ) ),
								)
							);
							$this->render_pro_locked_card(
								'dashicons-tag',
								__( 'Bulk Price Updater', 'product-budget-configurator' ),
								array(
									array( 'type' => 'text', 'label' => __( 'Set the percentage to bulk update prices', 'product-budget-configurator' ) ),
								)
							);
						}
						?>
					</div>
				</div>

				<!-- Upgrade to Pro Card -->
				<div class="pbc-settings-container" style="margin-top: 20px;">
					<div class="pbc-settings-card">
						<div class="pbc-card-header">
							<h2><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Upgrade to Pro', 'product-budget-configurator' ); ?></h2>
							<p class="description"><?php esc_html_e( 'Unlock powerful features with PBC Pro', 'product-budget-configurator' ); ?></p>
						</div>
						<div class="pbc-card-body">
							<p><?php esc_html_e( 'Pro features include: PDF branding (logo, header, footer, custom colors), email notifications, support buttons, WhatsApp/email sharing, shareable URLs, role discounts, role price visibility, recommendations, import/export, bulk price updater, and enquiry management.', 'product-budget-configurator' ); ?></p>
							<a href="https://close.technology/wordpress-plugins/product-budget-configurator/" target="_blank" class="button button-primary">
								<?php esc_html_e( 'Upgrade to Pro', 'product-budget-configurator' ); ?>
							</a>
						</div>
					</div>
				</div>

				<!-- Save Button -->
				<div class="pbc-settings-footer">
					<input type="hidden" name="form_submit" value="true"/>
					<input type="hidden" name="pbc_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pbc_nonce' ) ); ?>"/>
					<button type="submit" class="button button-primary button-hero">
						<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save All Settings', 'product-budget-configurator' ); ?>
					</button>
				</div>
			</form>
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
			wp_die( esc_html__( 'Security check failed. Please try again.', 'product-budget-configurator' ) );
			return;
		}
		if ( isset( $_POST['form_submit'] ) ) {
			$status = 'ok';
			$fields = array(
				'option_show_final_button_pdf'   => 'pbc_budget_show_button_pdf',
				'option_show_final_button_email' => 'pbc_budget_show_button_email',
				'option_show_prices_global'      => 'pbc_show_prices_global',
				'preview_width'                  => 'pbc_preview_width',
			);
			foreach ( $fields as $field_key => $field ) {
				if ( isset( $_POST[ $field_key ] ) ) {
					update_option( $field, trim( sanitize_text_field( wp_unslash( $_POST[ $field_key ] ) ) ) );
				}
			}

				do_action( 'pbc_save_admin_settings', $_POST );
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
				<th class="order-col"><?php esc_html_e( 'Order', 'product-budget-configurator' ); ?></th>
				<th class="phases-col"><?php esc_html_e( 'Phases', 'product-budget-configurator' ); ?></th>
				<th class="variations-col"><?php esc_html_e( 'Number of Variations', 'product-budget-configurator' ); ?></th>
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
				<td colspan="2" ><?php esc_html_e( 'Total: ', 'product-budget-configurator' ); ?></td>
				<td><?php echo (int) $total_count; ?></td>
			</tr>
		</table>
		<?php
	}



	/**
	 * Render General Configuration Section
	 *
	 * @return void
	 */
	public function render_general_configuration_section() {
		wp_enqueue_media();
		?>
		<!-- General Configuration Card -->
		<div class="pbc-settings-card">
			<div class="pbc-card-header">
				<h2><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'General Configuration', 'product-budget-configurator' ); ?></h2>
			</div>
			<div class="pbc-card-body pbc-form-grid">
				<fieldset>
					<label class="block" for="preview_width"><?php esc_html_e( 'Preview Width', 'product-budget-configurator' ); ?></label>
					<?php $preview_width = get_option( 'pbc_preview_width' ); ?>
					<input class="pbc_field" type="text" name="preview_width" value="<?php echo $preview_width ? esc_attr( $preview_width ) : ''; ?>" placeholder="<?php esc_html_e( 'default: 570', 'product-budget-configurator' ); ?>" />
					<p class="description"><?php esc_html_e( 'Width in pixels for product preview images', 'product-budget-configurator' ); ?></p>
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_final_button_pdf"><?php esc_html_e( 'Show PDF Download Button', 'product-budget-configurator' ); ?></label>
					<?php
					$show_button_pdf = get_option( 'pbc_budget_show_button_pdf' );
					$pages           = get_pages();
					if ( ! empty( $pages ) ) {
						echo '<select name="option_show_final_button_pdf" class="pbc-select">';
						echo '<option value="yes" ' . selected( $show_button_pdf, 'yes' ) . '>' . esc_html__( 'Yes', 'product-budget-configurator' ) . '</option>';
						echo '<option value="no" ' . selected( $show_button_pdf, 'no' ) . '>' . esc_html__( 'No', 'product-budget-configurator' ) . '</option>';
						echo '</select>';
					}
					?>
					<p class="description"><?php esc_html_e( 'Display PDF download button on final step', 'product-budget-configurator' ); ?></p>
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_final_button_email"><?php esc_html_e( 'Show Email Enquiry Button', 'product-budget-configurator' ); ?></label>
					<?php
					$show_button_email = get_option( 'pbc_budget_show_button_email' );
					$pages             = get_pages();
					if ( ! empty( $pages ) ) {
						echo '<select name="option_show_final_button_email" class="pbc-select">';
						echo '<option value="yes" ' . selected( $show_button_email, 'yes' ) . '>' . esc_html__( 'Yes', 'product-budget-configurator' ) . '</option>';
						echo '<option value="no" ' . selected( $show_button_email, 'no' ) . '>' . esc_html__( 'No', 'product-budget-configurator' ) . '</option>';
						echo '</select>';
					}
					?>
					<p class="description"><?php esc_html_e( 'Display email enquiry button on final step', 'product-budget-configurator' ); ?></p>
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_prices_global"><?php esc_html_e( 'Show Prices (Global)', 'product-budget-configurator' ); ?></label>
					<?php
					$show_prices_global = get_option( 'pbc_show_prices_global', 'yes' );
					?>
					<select name="option_show_prices_global" class="pbc-select">
						<option value="yes" <?php selected( $show_prices_global, 'yes' ); ?>><?php esc_html_e( 'Yes', 'product-budget-configurator' ); ?></option>
						<option value="no" <?php selected( $show_prices_global, 'no' ); ?>><?php esc_html_e( 'No', 'product-budget-configurator' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Global configuration to show prices. Can be customized by user role below.', 'product-budget-configurator' ); ?></p>
				</fieldset>
				<?php
				if ( has_action( 'pbc_admin_general_settings_extra' ) ) {
					do_action( 'pbc_admin_general_settings_extra' );
				} else {
					?>
					<div class="pbc-pro-overlay-wrap">
						<div class="pbc-pro-fields-preview" aria-hidden="true">
							<fieldset><label class="block"><?php esc_html_e( 'Flip Images Horizontal', 'product-budget-configurator' ); ?></label><input type="text" disabled style="width:100%;" /></fieldset>
							<fieldset><label class="block"><?php esc_html_e( 'Email Notification', 'product-budget-configurator' ); ?></label><input type="text" disabled style="width:100%;" /></fieldset>
						</div>
						<div class="pbc-pro-overlay">
							<span class="dashicons dashicons-lock pbc-pro-lock-icon"></span>
							<p><?php esc_html_e( 'These options require Pro', 'product-budget-configurator' ); ?></p>
							<a href="https://close.technology/wordpress-plugins/product-budget-configurator/" target="_blank" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'product-budget-configurator' ); ?></a>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a pro-locked settings card with blurred field preview and upgrade overlay.
	 *
	 * @param string $icon   Dashicons class.
	 * @param string $title  Card title.
	 * @param array  $fields Array of field descriptors: ['type' => 'text|checkbox|table', 'label' => '...'].
	 * @return void
	 */
	private function render_pro_locked_card( $icon, $title, $fields ) {
		?>
		<div class="pbc-settings-card pbc-pro-locked">
			<div class="pbc-card-header">
				<h2>
					<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
					<?php echo esc_html( $title ); ?>
					<span class="pbc-pro-badge"><?php esc_html_e( 'PRO', 'product-budget-configurator' ); ?></span>
				</h2>
			</div>
			<div class="pbc-card-body">
				<div class="pbc-pro-overlay-wrap">
					<div class="pbc-pro-fields-preview pbc-form-grid" aria-hidden="true">
						<?php foreach ( $fields as $field ) : ?>
							<fieldset>
								<label class="block"><?php echo esc_html( $field['label'] ); ?></label>
								<?php if ( 'checkbox' === $field['type'] ) : ?>
									<input type="checkbox" disabled />
								<?php elseif ( 'table' === $field['type'] ) : ?>
									<div style="background:#f0f0f0;height:60px;border-radius:4px;"></div>
								<?php else : ?>
									<input type="text" disabled style="width:100%;" />
								<?php endif; ?>
							</fieldset>
						<?php endforeach; ?>
					</div>
					<div class="pbc-pro-overlay">
						<span class="dashicons dashicons-lock pbc-pro-lock-icon"></span>
						<p><?php esc_html_e( 'This feature requires Pro', 'product-budget-configurator' ); ?></p>
						<a href="https://close.technology/wordpress-plugins/product-budget-configurator/" target="_blank" class="button button-primary">
							<?php esc_html_e( 'Upgrade to Pro', 'product-budget-configurator' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render PDF Configuration Section
	 *
	 * @return void
	 */
	public function render_pdf_configuration_section() {
		if ( has_action( 'pbc_admin_pdf_settings' ) ) {
			?>
			<div class="pbc-settings-card">
				<div class="pbc-card-header">
					<h2><span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'PDF Configuration', 'product-budget-configurator' ); ?></h2>
				</div>
				<div class="pbc-card-body">
					<?php do_action( 'pbc_admin_pdf_settings' ); ?>
				</div>
			</div>
			<?php
		} else {
			$this->render_pro_locked_card(
				'dashicons-media-document',
				__( 'PDF Configuration', 'product-budget-configurator' ),
				array(
					array( 'type' => 'text', 'label' => __( 'PDF Image Logo', 'product-budget-configurator' ) ),
					array( 'type' => 'text', 'label' => __( 'PDF Image Header', 'product-budget-configurator' ) ),
					array( 'type' => 'text', 'label' => __( 'PDF Image Footer', 'product-budget-configurator' ) ),
					array( 'type' => 'text', 'label' => __( 'Color for Odd Rows', 'product-budget-configurator' ) ),
					array( 'type' => 'text', 'label' => __( 'Color for Total', 'product-budget-configurator' ) ),
				)
			);
		}
	}


	/**
	 * General Settings Meta Box Callback (deprecated, kept for compatibility)
	 *
	 * @return void
	 */
	public function general_settings_meta_box_callback() {
		// This method is now deprecated but kept for compatibility.
		// The rendering is now done through individual section methods.
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
	 * Restart process - Clean session and start over
	 *
	 * @return void
	 */
	public function pbc_restart_process() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'pbc-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'product-budget-configurator' ) ) );
		}

		// Clear ALL PBC session data.
		if ( isset( $_SESSION ) && is_array( $_SESSION ) ) {
			$keys_to_remove = array();

			// Find all PBC related session keys.
			foreach ( $_SESSION as $key => $value ) {
				if ( strpos( $key, 'pbc_' ) === 0 ) {
					$keys_to_remove[] = $key;
				}
			}

			// Remove all found keys.
			foreach ( $keys_to_remove as $key ) {
				unset( $_SESSION[ $key ] );
			}
		}

		// Clear user meta for logged in users.
		$user_id = get_current_user_id();
		if ( $user_id ) {
			global $wpdb;
			// Delete all pbc_phase_* user meta.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
					$user_id,
					'pbc_phase_%'
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Process restarted successfully.', 'product-budget-configurator' ),
			)
		);
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
