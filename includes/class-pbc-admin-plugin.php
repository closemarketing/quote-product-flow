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
	 * Error message for license activation
	 *
	 * @var string
	 */
	private $license_error_message = '';

	/**
	 * Construct and intialize
	 */
	public function __construct() {
		// Initial stuff.
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'register_license_settings' ) );
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
	 * Register license settings.
	 *
	 * @return void
	 */
	public function register_license_settings() {
		global $pbc_license;

		if ( ! $pbc_license || ! class_exists( '\Closemarketing\WPLicenseManager\License' ) ) {
			return;
		}

		// Register each individual license field.
		register_setting(
			'product-budget-configurator_license',
			'product-budget-configurator_license_apikey',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'product-budget-configurator_license',
			'product-budget-configurator_license_deactivate_checkbox',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		// Hook into admin_init to process license activation/deactivation.
		add_action(
			'admin_init',
			function () use ( $pbc_license ) {
				// Check if license form was submitted and verify nonce.
				if ( isset( $_POST['option_page'], $_POST['_wpnonce'] ) && 'product-budget-configurator_license' === $_POST['option_page'] && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'product-budget-configurator_license-options' ) ) {
					if ( isset( $_POST['submit_license'] ) ) {
						// Build input array for validate_license.
						$input = array(
							'product-budget-configurator_license_apikey'              => isset( $_POST['product-budget-configurator_license_apikey'] ) ? sanitize_text_field( wp_unslash( $_POST['product-budget-configurator_license_apikey'] ) ) : '',
							'product-budget-configurator_license_deactivate_checkbox' => isset( $_POST['product-budget-configurator_license_deactivate_checkbox'] ) ? sanitize_text_field( wp_unslash( $_POST['product-budget-configurator_license_deactivate_checkbox'] ) ) : '',
						);

						// Call the license validation.
						$pbc_license->validate_license( $input );
					}
				}
			},
			15
		);
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
		$this->license_error_message = ''; // Reset error message.
		$return                      = $this->save_post_options();
		if ( 'ok' === $return ) {
			$update = __( 'Successfully Saved!', 'pbc' );
		} elseif ( 'error' === $return ) {
			// Use license error message if available, otherwise generic error.
			$error = ! empty( $this->license_error_message ) ? $this->license_error_message : __( 'Error saving settings', 'pbc' );
		}
		?>
		<div class='wrap pbc-settings-wrap'>
			<div class="pbc-settings-header">
				<h1><span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html( $GLOBALS['title'] ); ?></h1>
				<p class="pbc-settings-subtitle"><?php esc_html_e( 'Configure your product budget configurator global settings', 'pbc' ); ?></p>
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
						<?php do_action( 'pbc_admin_settings_left_column' ); ?>
					</div>

					<!-- Right Column -->
					<div class="pbc-column-right">
						<?php $this->render_pdf_configuration_section(); ?>
						<?php do_action( 'pbc_admin_settings_right_column' ); ?>
					</div>
				</div>

				<!-- Upgrade to Pro Card -->
				<div class="pbc-settings-container" style="margin-top: 20px;">
					<div class="pbc-settings-card">
						<div class="pbc-card-header">
							<h2><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Upgrade to Pro', 'pbc' ); ?></h2>
							<p class="description"><?php esc_html_e( 'Unlock powerful features with PBC Pro', 'pbc' ); ?></p>
						</div>
						<div class="pbc-card-body">
							<p><?php esc_html_e( 'Pro features include: PDF branding (logo, header, footer, custom colors), email notifications, support buttons, WhatsApp/email sharing, shareable URLs, role discounts, role price visibility, recommendations, import/export, bulk price updater, and enquiry management.', 'pbc' ); ?></p>
							<a href="https://close.technology/wordpress-plugins/product-budget-configurator/" target="_blank" class="button button-primary">
								<?php esc_html_e( 'Upgrade to Pro', 'pbc' ); ?>
							</a>
						</div>
					</div>
				</div>

				<!-- Save Button -->
				<div class="pbc-settings-footer">
					<input type="hidden" name="form_submit" value="true"/>
					<input type="hidden" name="pbc_nonce" value="<?php echo esc_attr( wp_create_nonce( 'pbc_nonce' ) ); ?>"/>
					<button type="submit" class="button button-primary button-hero">
						<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save All Settings', 'pbc' ); ?>
					</button>
				</div>
			</form>

			<!-- License Section (separate form, after main form - full width) -->
			<div style="margin-top: 20px;">
				<?php $this->render_license_section(); ?>
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
				'preview_width'                  => 'pbc_preview_width',
			);
			foreach ( $fields as $field_key => $field ) {
				if ( isset( $_POST[ $field_key ] ) ) {
					update_option( $field, trim( sanitize_text_field( wp_unslash( $_POST[ $field_key ] ) ) ) );
				}
			}

			// License management is now handled by License Manager via options.php.
			// No manual handling needed here.

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
				<h2><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'General Configuration', 'pbc' ); ?></h2>
			</div>
			<div class="pbc-card-body pbc-form-grid">
				<fieldset>
					<label class="block" for="preview_width"><?php esc_html_e( 'Preview Width', 'pbc' ); ?></label>
					<?php $preview_width = get_option( 'pbc_preview_width' ); ?>
					<input class="pbc_field" type="text" name="preview_width" value="<?php echo $preview_width ? esc_attr( $preview_width ) : ''; ?>" placeholder="<?php esc_html_e( 'default: 570', 'pbc' ); ?>" />
					<p class="description"><?php esc_html_e( 'Width in pixels for product preview images', 'pbc' ); ?></p>
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_final_button_pdf"><?php esc_html_e( 'Show PDF Download Button', 'pbc' ); ?></label>
					<?php
					$show_button_pdf = get_option( 'pbc_budget_show_button_pdf' );
					$pages           = get_pages();
					if ( ! empty( $pages ) ) {
						echo '<select name="option_show_final_button_pdf" class="pbc-select">';
						echo '<option value="yes" ' . selected( $show_button_pdf, 'yes' ) . '>' . esc_html__( 'Yes', 'pbc' ) . '</option>';
						echo '<option value="no" ' . selected( $show_button_pdf, 'no' ) . '>' . esc_html__( 'No', 'pbc' ) . '</option>';
						echo '</select>';
					}
					?>
					<p class="description"><?php esc_html_e( 'Display PDF download button on final step', 'pbc' ); ?></p>
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_final_button_email"><?php esc_html_e( 'Show Email Enquiry Button', 'pbc' ); ?></label>
					<?php
					$show_button_email = get_option( 'pbc_budget_show_button_email' );
					$pages             = get_pages();
					if ( ! empty( $pages ) ) {
						echo '<select name="option_show_final_button_email" class="pbc-select">';
						echo '<option value="yes" ' . selected( $show_button_email, 'yes' ) . '>' . esc_html__( 'Yes', 'pbc' ) . '</option>';
						echo '<option value="no" ' . selected( $show_button_email, 'no' ) . '>' . esc_html__( 'No', 'pbc' ) . '</option>';
						echo '</select>';
					}
					?>
					<p class="description"><?php esc_html_e( 'Display email enquiry button on final step', 'pbc' ); ?></p>
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_prices_global"><?php esc_html_e( 'Show Prices (Global)', 'pbc' ); ?></label>
					<?php
					$show_prices_global = get_option( 'pbc_show_prices_global', 'yes' );
					?>
					<select name="option_show_prices_global" class="pbc-select">
						<option value="yes" <?php selected( $show_prices_global, 'yes' ); ?>><?php esc_html_e( 'Yes', 'pbc' ); ?></option>
						<option value="no" <?php selected( $show_prices_global, 'no' ); ?>><?php esc_html_e( 'No', 'pbc' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Global configuration to show prices. Can be customized by user role below.', 'pbc' ); ?></p>
				</fieldset>
				<?php do_action( 'pbc_admin_general_settings_extra' ); ?>
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
		?>
		<div class="pbc-settings-card">
			<div class="pbc-card-header">
				<h2><span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'PDF Configuration', 'pbc' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Basic PDF export is available. Upgrade to Pro for branding (logo, header, footer, custom colors).', 'pbc' ); ?></p>
			</div>
			<div class="pbc-card-body">
				<?php do_action( 'pbc_admin_pdf_settings' ); ?>
			</div>
		</div>
		<?php
	}


	/**
	 * Render License Section
	 *
	 * @return void
	 */
	public function render_license_section() {
		global $pbc_license;

		// Check if license instance exists.
		if ( empty( $pbc_license ) || ! is_object( $pbc_license ) ) {
			?>
			<div class="pbc-settings-card pbc-license-card">
				<div class="pbc-card-header">
					<h2><span class="dashicons dashicons-admin-network"></span> <?php esc_html_e( 'License', 'pbc' ); ?></h2>
				</div>
				<div class="pbc-card-body">
					<div class="notice notice-error inline">
						<p><?php esc_html_e( 'License Manager is not available. Please ensure wp-plugin-license-manager is installed.', 'pbc' ); ?></p>
					</div>
				</div>
			</div>
			<?php
			return;
		}

		// Render inline license settings.
		$this->render_inline_license_settings( $pbc_license );
	}

	/**
	 * Render inline license settings.
	 *
	 * @param \Closemarketing\WPLicenseManager\License $license License instance.
	 * @return void
	 */
	private function render_inline_license_settings( $license ) {
		// Get license data.
		$license_key    = $license->get_option_value( 'apikey' );
		$is_active      = $license->is_license_active();
		$license_status = get_option( 'product-budget-configurator_license_activated', 'Deactivated' );

		?>
		<div class="pbc-settings-card pbc-license-card">
			<!-- Header -->
			<div class="pbc-card-header">
				<h2><span class="dashicons dashicons-admin-network"></span> <?php esc_html_e( 'Product Budget Configurator License', 'pbc' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Manage your license to receive automatic updates and support.', 'pbc' ); ?></p>
			</div>

			<!-- License Status -->
			<div class="pbc-card-body">
				<div style="margin-bottom: 20px;">
					<?php if ( $is_active ) : ?>
						<div style="padding: 15px; border-radius: 4px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; display: flex; align-items: center;">
							<span style="font-size: 24px; margin-right: 10px;">✓</span>
							<div>
								<strong><?php esc_html_e( 'License Active', 'pbc' ); ?></strong>
								<p style="margin: 5px 0 0 0; font-size: 13px;"><?php esc_html_e( 'Your license is active and you will receive automatic updates.', 'pbc' ); ?></p>
							</div>
						</div>
					<?php else : ?>
						<div style="padding: 15px; border-radius: 4px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; display: flex; align-items: center;">
							<span style="font-size: 24px; margin-right: 10px;">✗</span>
							<div>
								<strong><?php esc_html_e( 'License Inactive', 'pbc' ); ?></strong>
								<p style="margin: 5px 0 0 0; font-size: 13px;"><?php esc_html_e( 'Please enter your license key to enable updates and support.', 'pbc' ); ?></p>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<!-- License Form -->
				<form method="post" action="options.php" style="margin-top: 20px;">
					<?php settings_fields( 'product-budget-configurator_license' ); ?>

					<!-- License Key Field -->
					<fieldset style="margin-bottom: 20px;">
						<label class="block" for="product-budget-configurator_license_apikey" style="font-weight: 600; margin-bottom: 8px; display: block;">
							<?php esc_html_e( 'License Key', 'pbc' ); ?>
						</label>
						<div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
							<input 
								type="text" 
								id="product-budget-configurator_license_apikey" 
								name="product-budget-configurator_license_apikey" 
								value="<?php echo esc_attr( $license_key ); ?>" 
								style="flex: 1; min-width: 300px; font-family: monospace; padding: 8px 12px;"
								placeholder="<?php esc_attr_e( 'CTECH-XXXXX-XXXXX-XXXXX-XXXXX', 'pbc' ); ?>"
								<?php echo $is_active ? 'readonly' : ''; ?>
							/>
							<?php if ( $is_active ) : ?>
								<label style="display: flex; align-items: center; gap: 5px; white-space: nowrap;">
									<input type="checkbox" name="product-budget-configurator_license_deactivate_checkbox" value="on" />
									<span><?php esc_html_e( 'Deactivate', 'pbc' ); ?></span>
								</label>
							<?php endif; ?>
						</div>
						<p class="description" style="margin-top: 5px;">
							<?php
							printf(
								/* translators: %s: Purchase URL */
								esc_html__( 'Enter your license key. You can find it in %s.', 'pbc' ),
								'<a href="https://close.technology/my-account/" target="_blank">' . esc_html__( 'your account', 'pbc' ) . '</a>'
							);
							?>
						</p>
					</fieldset>

					<!-- Submit Button -->
					<div style="padding-top: 15px; border-top: 1px solid #ddd;">
						<button type="submit" name="submit_license" class="button button-primary button-large">
							<span class="dashicons dashicons-update" style="margin-top: 4px;"></span>
							<?php echo $is_active ? esc_html__( 'Update License', 'pbc' ) : esc_html__( 'Activate License', 'pbc' ); ?>
						</button>
					</div>
				</form>

				<!-- License Benefits -->
				<div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 4px;">
					<h3 style="margin-top: 0;"><?php esc_html_e( 'License Benefits', 'pbc' ); ?></h3>
					<p><?php esc_html_e( 'An active license provides the following benefits:', 'pbc' ); ?></p>
					<ul style="list-style: none; padding-left: 0;">
						<li style="padding: 5px 0;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span> <?php esc_html_e( 'Automatic plugin updates', 'pbc' ); ?></li>
						<li style="padding: 5px 0;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span> <?php esc_html_e( 'Access to new features', 'pbc' ); ?></li>
						<li style="padding: 5px 0;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span> <?php esc_html_e( 'Priority support', 'pbc' ); ?></li>
						<li style="padding: 5px 0;"><span class="dashicons dashicons-yes" style="color: #46b450;"></span> <?php esc_html_e( 'Security patches', 'pbc' ); ?></li>
					</ul>
					<hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">
					<div style="font-size: 0.9em;">
						<p><strong><?php esc_html_e( 'Need Help?', 'pbc' ); ?></strong></p>
						<p>
							<a href="https://close.technology/wordpress-plugins/product-budget-configurator/" target="_blank"><?php esc_html_e( 'Purchase License', 'pbc' ); ?> →</a><br>
							<a href="https://close.technology/my-account/" target="_blank"><?php esc_html_e( 'My Account', 'pbc' ); ?> →</a><br>
							<a href="https://close.technology/support/" target="_blank"><?php esc_html_e( 'Support', 'pbc' ); ?> →</a>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
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
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'pbc' ) ) );
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
				'message' => __( 'Process restarted successfully.', 'pbc' ),
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
