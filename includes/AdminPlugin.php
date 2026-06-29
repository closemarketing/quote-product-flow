<?php
/**
 * Class Admin
 *
 * @package    WordPress
 * @author     David Perez <david@closemarketing.es>
 * @copyright  2022 Closemarketing
 * @version    1.0
 */

namespace CLOSE\QProductFlow;

defined( 'ABSPATH' ) || exit;

use CLOSE\QProductFlow\Helpers\PDF;

/**
 * Class for admin
 */
class AdminPlugin {
	/**
	 * Construct and intialize
	 */
	public function __construct() {
		// Initial stuff.
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Custom Post types stuff.
		add_action( 'admin_menu', array( $this, 'qpfw_add_admin_menus' ), 1 );
		add_filter( 'parent_file', array( $this, 'qpfw_fix_parent_file' ) );
		add_filter( 'submenu_file', array( $this, 'qpfw_fix_submenu_file' ) );

		add_filter( 'disable_months_dropdown', array( $this, 'disable_months_dropdown' ), 10, 2 );

		add_action( 'wp_ajax_qpfw_restart_process', array( $this, 'qpfw_restart_process' ) );
		add_action( 'wp_ajax_nopriv_qpfw_restart_process', array( $this, 'qpfw_restart_process' ) );

		add_action( 'admin_post_qpfw_run_migration', array( $this, 'handle_run_migration' ) );
		add_action( 'admin_post_qpfw_run_repair', array( $this, 'handle_run_repair' ) );
	}

	/**
	 * Initialize
	 */
	public function init() {
		/**
		* Image Sizes
		*/
		add_image_size( 'qpfw_icon', 150, 230, false );
		add_image_size( 'qpfw_product', 570, 460, true );
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return;
		}

		$pro_post_types = apply_filters( 'qpfw_is_pro', false ) ? array( 'qpfw_enquiry' ) : array();
		$is_qpfw_screen = 'qpfw_menu' === $screen->parent_base
			|| 'toplevel_page_qpfw_menu' === $screen->base
			|| in_array( $screen->post_type, array_merge( array( 'qpfw_variation', 'qpfw_phases' ), $pro_post_types ), true );

		if ( ! $is_qpfw_screen ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'qpfw-admin', QPFW_PLUGIN_URL . 'includes/assets/admin.css', array(), QPFW_VERSION );

		wp_enqueue_script( 'post' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_media();

		wp_enqueue_script(
			'qpfw-media',
			QPFW_PLUGIN_URL . 'includes/assets/qpfw-media.js',
			array( 'jquery', 'wp-color-picker' ),
			QPFW_VERSION,
			true
		);
		wp_localize_script(
			'qpfw-media',
			'qpfw_media_strings',
			array(
				'no_image_selected' => __( 'Please select an image file (jpeg, png) only', 'quote-product-flow' ),
			)
		);

		wp_enqueue_script(
			'qpfw-admin-scripts',
			QPFW_PLUGIN_URL . 'includes/assets/admin-scripts.js',
			array( 'jquery', 'media-upload' ),
			QPFW_VERSION,
			true
		);
		wp_localize_script(
			'qpfw-admin-scripts',
			'qpfwAjaxAction',
			array(
				'url'       => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'qpfw_admin_nonce' ),
				'pdf_nonce' => wp_create_nonce( 'qpfw_enquiry_pdf_nonce' ),
			)
		);
		wp_localize_script(
			'qpfw-admin-scripts',
			'qpfwAjaxActionPrice',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'qpfw_price_updater_nonce' ),
			)
		);
	}

	/**
	 * Registers menu admin
	 *
	 * @return void
	 */
	public function qpfw_add_admin_menus() {
		// Add custom admin menu.
		add_menu_page(
			__( 'Quote Product Flow', 'quote-product-flow' ),
			'QuoteProduct',
			'manage_options',
			'qpfw_menu',
			array( $this, 'qpfw_display_admin_page' ),
			'dashicons-tagcloud',
			99
		);

		$submenu_pages = array(
			array(
				'parent_slug' => 'qpfw_menu',
				'page_title'  => __( 'Variations in Phases', 'quote-product-flow' ),
				'menu_title'  => __( 'Variations', 'quote-product-flow' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=qpfw_variation',
				'function'    => null,
			),
			array(
				'parent_slug' => 'qpfw_menu',
				'page_title'  => __( 'Phases of Configurator', 'quote-product-flow' ),
				'menu_title'  => __( 'Phases', 'quote-product-flow' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit.php?post_type=qpfw_phases',
				'function'    => null,
			),
			array(
				'parent_slug' => 'qpfw_menu',
				'page_title'  => __( 'Sections in variations', 'quote-product-flow' ),
				'menu_title'  => __( 'Sections', 'quote-product-flow' ),
				'capability'  => 'manage_options',
				'menu_slug'   => 'edit-tags.php?taxonomy=qpfw_variation_tag',
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

		do_action( 'qpfw_admin_register_menus' );

		// Rename the auto-added first submenu (parent clone) to "Settings".
		global $submenu;
		if ( isset( $submenu['qpfw_menu'][0] ) ) {
			$submenu['qpfw_menu'][0][0] = __( 'Settings', 'quote-product-flow' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Page settings with metaboxes
	 *
	 * @return void
	 */
	public function qpfw_display_admin_page() {
		$return = $this->save_post_options();
		if ( 'ok' === $return ) {
			$update = __( 'Successfully Saved!', 'quote-product-flow' );
		} elseif ( 'error' === $return ) {
			$error = __( 'Error saving settings', 'quote-product-flow' );
		}
		?>
		<div class='wrap qpfw-settings-wrap'>
			<div class="qpfw-settings-header">
				<h1><span class="dashicons dashicons-admin-settings"></span> <?php echo esc_html( $GLOBALS['title'] ); ?></h1>
				<p class="qpfw-settings-subtitle"><?php esc_html_e( 'Configure your product budget configurator global settings', 'quote-product-flow' ); ?></p>
			</div>

			<?php if ( isset( $update ) ) { ?>
				<div id="message" class="notice notice-success is-dismissible"><p><?php echo esc_html( $update ); ?></p></div>
			<?php } ?>
			<?php if ( isset( $error ) ) { ?>
				<div id="message" class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
			<?php } ?>

			<form action="" method="post" enctype="multipart/form-data" id="qpfw_general_settings_form">
				<div class="qpfw-settings-container qpfw-two-columns">
					<!-- Left Column -->
					<div class="qpfw-column-left">
						<?php $this->render_general_configuration_section(); ?>
						<?php
						if ( has_action( 'qpfw_admin_settings_left_column' ) ) {
							do_action( 'qpfw_admin_settings_left_column' );
						} else {
							$this->render_pro_locked_card(
								'dashicons-phone',
								__( 'Support Contact', 'quote-product-flow' ),
								array(
									array(
										'type' => 'checkbox',
										'label' => __( 'Enable support contact buttons in configurator', 'quote-product-flow' ),
									),
									array(
										'type' => 'text',
										'label' => __( 'Support Phone Number', 'quote-product-flow' ),
									),
									array(
										'type' => 'text',
										'label' => __( 'Support Email Address', 'quote-product-flow' ),
									),
								)
							);
						}
						?>
					</div>

					<!-- Right Column -->
					<div class="qpfw-column-right">
						<?php $this->render_pdf_configuration_section(); ?>
						<?php
						if ( has_action( 'qpfw_admin_settings_right_column' ) ) {
							do_action( 'qpfw_admin_settings_right_column' );
						} else {
							$this->render_pro_locked_card(
								'dashicons-groups',
								__( 'User Role Specific Options', 'quote-product-flow' ),
								array(
									array(
										'type' => 'table',
										'label' => __( 'Role discounts and price visibility per user role', 'quote-product-flow' ),
									),
								)
							);
							$this->render_pro_locked_card(
								'dashicons-tag',
								__( 'Bulk Price Updater', 'quote-product-flow' ),
								array(
									array(
										'type' => 'text',
										'label' => __( 'Set the percentage to bulk update prices', 'quote-product-flow' ),
									),
								)
							);
						}
						?>
					</div>
				</div>

				<!-- Upgrade to Pro Card -->
				<?php if ( ! apply_filters( 'qpfw_is_pro', false ) ) : ?>
				<div class="qpfw-settings-container" style="margin-top: 20px;">
					<div class="qpfw-settings-card">
						<div class="qpfw-card-header">
							<h2><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Upgrade to Pro', 'quote-product-flow' ); ?></h2>
							<p class="description"><?php esc_html_e( 'Unlock powerful features with QPFW Pro', 'quote-product-flow' ); ?></p>
						</div>
						<div class="qpfw-card-body">
							<p><?php esc_html_e( 'Pro features include: PDF branding (logo, header, footer, custom colors), email notifications, support buttons, WhatsApp/email sharing, shareable URLs, role discounts, role price visibility, recommendations, bulk price updater, and enquiry management.', 'quote-product-flow' ); ?></p>
							<a href="https://close.technology/wordpress-plugins/quote-product-flow/" target="_blank" class="button button-primary">
								<?php esc_html_e( 'Upgrade to Pro', 'quote-product-flow' ); ?>
							</a>
						</div>
					</div>
				</div>
				<?php endif; ?>

				<!-- Save Button -->
				<div class="qpfw-settings-footer">
					<input type="hidden" name="form_submit" value="true"/>
					<input type="hidden" name="qpfw_nonce" value="<?php echo esc_attr( wp_create_nonce( 'qpfw_nonce' ) ); ?>"/>
					<button type="submit" class="button button-primary button-hero">
						<span class="dashicons dashicons-saved"></span> <?php esc_html_e( 'Save All Settings', 'quote-product-flow' ); ?>
					</button>
				</div>
			</form>

			<?php do_action( 'qpfw_admin_license_section' ); ?>

			<!-- Data Migration -->
			<div class="qpfw-settings-container" style="margin-top: 20px;">
				<div class="qpfw-settings-card">
					<div class="qpfw-card-header">
						<h2><span class="dashicons dashicons-database-import"></span> <?php esc_html_e( 'Data Migration', 'quote-product-flow' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Migrate legacy post types (phases, variation, enquiry) to prefixed slugs (qpfw_phases, qpfw_variation, qpfw_enquiry).', 'quote-product-flow' ); ?></p>
					</div>
					<div class="qpfw-card-body">
						<?php if ( isset( $_GET['qpfw_migration'] ) && '1' === $_GET['qpfw_migration'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
							<div class="notice notice-success inline"><p><?php esc_html_e( 'Migration completed successfully.', 'quote-product-flow' ); ?></p></div>
						<?php endif; ?>
						<p><?php esc_html_e( 'Current migration status:', 'quote-product-flow' ); ?>
							<strong>
								<?php
								$done = get_option( 'qpfw_cpt_migration_done' );
								// translators: %s is the plugin version number when migration ran.
								echo $done ? esc_html( sprintf( __( 'Done (v%s)', 'quote-product-flow' ), $done ) ) : esc_html__( 'Pending', 'quote-product-flow' );
								?>
							</strong>
						</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="qpfw_run_migration" />
							<?php wp_nonce_field( 'qpfw_run_migration' ); ?>
							<button type="submit" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'This will migrate legacy post type data. Continue?', 'quote-product-flow' ); ?>');">
								<span class="dashicons dashicons-database-import" style="vertical-align: middle;"></span>
								<?php esc_html_e( 'Run Migration Now', 'quote-product-flow' ); ?>
							</button>
						</form>
					</div>
				</div>
			</div>

			<!-- Repair Phase References -->
			<div class="qpfw-settings-container" style="margin-top: 20px;">
				<div class="qpfw-settings-card">
					<div class="qpfw-card-header">
						<h2><span class="dashicons dashicons-tools"></span> <?php esc_html_e( 'Repair Phase References', 'quote-product-flow' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Use this if phases were deleted and recreated after migration (variations show wrong or missing phase). It detects broken phase references, maps them to the correct current phases using enquiry history, creates missing phases, removes duplicate meta entries and clears dependency references to deleted variations.', 'quote-product-flow' ); ?></p>
					</div>
					<div class="qpfw-card-body">
						<?php
						// phpcs:disable WordPress.Security.NonceVerification.Recommended
						if ( isset( $_GET['qpfw_repair'] ) && '1' === $_GET['qpfw_repair'] ) :
							$r_phases  = isset( $_GET['qpfw_repair_phases'] ) ? (int) $_GET['qpfw_repair_phases'] : 0;
							$r_created = isset( $_GET['qpfw_repair_created'] ) ? (int) $_GET['qpfw_repair_created'] : 0;
							$r_deduped = isset( $_GET['qpfw_repair_deduped'] ) ? (int) $_GET['qpfw_repair_deduped'] : 0;
							$r_depends = isset( $_GET['qpfw_repair_depends'] ) ? (int) $_GET['qpfw_repair_depends'] : 0;
							// phpcs:enable WordPress.Security.NonceVerification.Recommended
							?>
							<div class="notice notice-success inline">
								<p>
									<?php esc_html_e( 'Repair completed:', 'quote-product-flow' ); ?>
									<strong><?php echo (int) $r_phases; ?></strong> <?php esc_html_e( 'phase references fixed', 'quote-product-flow' ); ?>,
									<strong><?php echo (int) $r_created; ?></strong> <?php esc_html_e( 'new phases created', 'quote-product-flow' ); ?>,
									<strong><?php echo (int) $r_deduped; ?></strong> <?php esc_html_e( 'duplicate meta rows removed', 'quote-product-flow' ); ?>,
									<strong><?php echo (int) $r_depends; ?></strong> <?php esc_html_e( 'dependency entries remapped to current IDs', 'quote-product-flow' ); ?>.
								</p>
							</div>
						<?php endif; ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="qpfw_run_repair" />
							<?php wp_nonce_field( 'qpfw_run_repair' ); ?>
							<button type="submit" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'This will repair broken phase references, remove duplicate meta data, and remap dependency IDs from old production IDs to current local IDs. Continue?', 'quote-product-flow' ); ?>');">
								<span class="dashicons dashicons-tools" style="vertical-align: middle;"></span>
								<?php esc_html_e( 'Run Repair Now', 'quote-product-flow' ); ?>
							</button>
						</form>
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
		if ( ! isset( $_POST['form_submit'] ) ) {
			return;
		}
		$status = '';
		// Verify nonce.
		if ( ! isset( $_POST['qpfw_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['qpfw_nonce'] ), 'qpfw_nonce' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'quote-product-flow' ) );
		}
		if ( isset( $_POST['form_submit'] ) ) {
			$status = 'ok';
			$fields = array(
				'option_show_final_button_pdf'   => 'qpfw_budget_show_button_pdf',
				'option_show_final_button_email' => 'qpfw_budget_show_button_email',
				'option_show_prices_global'      => 'qpfw_show_prices_global',
				'preview_width'                  => 'qpfw_preview_width',
			);
			foreach ( $fields as $field_key => $field ) {
				if ( isset( $_POST[ $field_key ] ) ) {
					update_option( $field, trim( sanitize_text_field( wp_unslash( $_POST[ $field_key ] ) ) ) );
				}
			}

				do_action( 'qpfw_save_admin_settings', map_deep( wp_unslash( $_POST ), 'sanitize_text_field' ) );
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
				<th class="order-col"><?php esc_html_e( 'Order', 'quote-product-flow' ); ?></th>
				<th class="phases-col"><?php esc_html_e( 'Phases', 'quote-product-flow' ); ?></th>
				<th class="variations-col"><?php esc_html_e( 'Number of Variations', 'quote-product-flow' ); ?></th>
			</tr>
			<?php
			$phases = get_posts( 'posts_per_page=-1&post_type=qpfw_phases&orderby=menu_order&order=ASC' );
			if ( ! empty( $phases ) ) {
				foreach ( $phases as $phase ) {
					?>
					<tr>
						<td class="order-col"><?php echo esc_html( $phase->menu_order ); ?></td>
						<td class="phases-col"><?php echo esc_html( $phase->post_title ); ?></td>
						<td class="variations-col">
							<?php
							$variations = get_posts( 'posts_per_page=-1&post_type=qpfw_variation&meta_key=qpfw_phase&meta_value=' . $phase->ID . '&fields=ids' );
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
				<td colspan="2" ><?php esc_html_e( 'Total: ', 'quote-product-flow' ); ?></td>
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
		<div class="qpfw-settings-card">
			<div class="qpfw-card-header">
				<h2><span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e( 'General Configuration', 'quote-product-flow' ); ?></h2>
			</div>
			<div class="qpfw-card-body qpfw-form-grid">
				<fieldset>
					<label class="block" for="preview_width"><?php esc_html_e( 'Preview Width', 'quote-product-flow' ); ?></label>
					<?php $preview_width = get_option( 'qpfw_preview_width' ); ?>
					<input class="qpfw_field" type="text" name="preview_width" value="<?php echo $preview_width ? esc_attr( $preview_width ) : ''; ?>" placeholder="<?php esc_html_e( 'default: 570', 'quote-product-flow' ); ?>" />
					<p class="description"><?php esc_html_e( 'Width in pixels for product preview images', 'quote-product-flow' ); ?></p>
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_final_button_pdf"><?php esc_html_e( 'Show PDF Download Button', 'quote-product-flow' ); ?></label>
					<?php
					$show_button_pdf = get_option( 'qpfw_budget_show_button_pdf' );
					$pages           = get_pages();
					if ( ! empty( $pages ) ) {
						echo '<select name="option_show_final_button_pdf" class="qpfw-select">';
						echo '<option value="yes" ' . selected( $show_button_pdf, 'yes' ) . '>' . esc_html__( 'Yes', 'quote-product-flow' ) . '</option>';
						echo '<option value="no" ' . selected( $show_button_pdf, 'no' ) . '>' . esc_html__( 'No', 'quote-product-flow' ) . '</option>';
						echo '</select>';
					}
					?>
					<p class="description"><?php esc_html_e( 'Display PDF download button on final step', 'quote-product-flow' ); ?></p>
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_final_button_email"><?php esc_html_e( 'Show Email Enquiry Button', 'quote-product-flow' ); ?></label>
					<?php
					$show_button_email = get_option( 'qpfw_budget_show_button_email' );
					$pages             = get_pages();
					if ( ! empty( $pages ) ) {
						echo '<select name="option_show_final_button_email" class="qpfw-select">';
						echo '<option value="yes" ' . selected( $show_button_email, 'yes' ) . '>' . esc_html__( 'Yes', 'quote-product-flow' ) . '</option>';
						echo '<option value="no" ' . selected( $show_button_email, 'no' ) . '>' . esc_html__( 'No', 'quote-product-flow' ) . '</option>';
						echo '</select>';
					}
					?>
					<p class="description"><?php esc_html_e( 'Display email enquiry button on final step', 'quote-product-flow' ); ?></p>
				</fieldset>
				<fieldset>
					<label class="block" for="option_show_prices_global"><?php esc_html_e( 'Show Prices (Global)', 'quote-product-flow' ); ?></label>
					<?php
					$show_prices_global = get_option( 'qpfw_show_prices_global', 'yes' );
					?>
					<select name="option_show_prices_global" class="qpfw-select">
						<option value="yes" <?php selected( $show_prices_global, 'yes' ); ?>><?php esc_html_e( 'Yes', 'quote-product-flow' ); ?></option>
						<option value="no" <?php selected( $show_prices_global, 'no' ); ?>><?php esc_html_e( 'No', 'quote-product-flow' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Global configuration to show prices. Can be customized by user role below.', 'quote-product-flow' ); ?></p>
				</fieldset>
				<?php
				if ( has_action( 'qpfw_admin_general_settings_extra' ) ) {
					do_action( 'qpfw_admin_general_settings_extra' );
				} else {
					?>
					<div class="qpfw-pro-overlay-wrap">
						<div class="qpfw-pro-fields-preview" aria-hidden="true">
							<fieldset><label class="block"><?php esc_html_e( 'Flip Images Horizontal', 'quote-product-flow' ); ?></label><input type="text" disabled style="width:100%;" /></fieldset>
							<fieldset><label class="block"><?php esc_html_e( 'Email Notification', 'quote-product-flow' ); ?></label><input type="text" disabled style="width:100%;" /></fieldset>
						</div>
						<div class="qpfw-pro-overlay">
							<span class="dashicons dashicons-lock qpfw-pro-lock-icon"></span>
							<p><?php esc_html_e( 'These options require Pro', 'quote-product-flow' ); ?></p>
							<a href="https://close.technology/wordpress-plugins/quote-product-flow/" target="_blank" class="button button-primary"><?php esc_html_e( 'Upgrade to Pro', 'quote-product-flow' ); ?></a>
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
		<div class="qpfw-settings-card qpfw-pro-locked">
			<div class="qpfw-card-header">
				<h2>
					<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
					<?php echo esc_html( $title ); ?>
					<span class="qpfw-pro-badge"><?php esc_html_e( 'PRO', 'quote-product-flow' ); ?></span>
				</h2>
			</div>
			<div class="qpfw-card-body">
				<div class="qpfw-pro-overlay-wrap">
					<div class="qpfw-pro-fields-preview qpfw-form-grid" aria-hidden="true">
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
					<div class="qpfw-pro-overlay">
						<span class="dashicons dashicons-lock qpfw-pro-lock-icon"></span>
						<p><?php esc_html_e( 'This feature requires Pro', 'quote-product-flow' ); ?></p>
						<a href="https://close.technology/wordpress-plugins/quote-product-flow/" target="_blank" class="button button-primary">
							<?php esc_html_e( 'Upgrade to Pro', 'quote-product-flow' ); ?>
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
		if ( has_action( 'qpfw_admin_pdf_settings' ) ) {
			?>
			<div class="qpfw-settings-card">
				<div class="qpfw-card-header">
					<h2><span class="dashicons dashicons-media-document"></span> <?php esc_html_e( 'PDF Configuration', 'quote-product-flow' ); ?></h2>
				</div>
				<div class="qpfw-card-body">
					<?php do_action( 'qpfw_admin_pdf_settings' ); ?>
				</div>
			</div>
			<?php
		} else {
			$this->render_pro_locked_card(
				'dashicons-media-document',
				__( 'PDF Configuration', 'quote-product-flow' ),
				array(
					array(
						'type' => 'text',
						'label' => __( 'PDF Image Logo', 'quote-product-flow' ),
					),
					array(
						'type' => 'text',
						'label' => __( 'PDF Image Header', 'quote-product-flow' ),
					),
					array(
						'type' => 'text',
						'label' => __( 'PDF Image Footer', 'quote-product-flow' ),
					),
					array(
						'type' => 'text',
						'label' => __( 'Color for Odd Rows', 'quote-product-flow' ),
					),
					array(
						'type' => 'text',
						'label' => __( 'Color for Total', 'quote-product-flow' ),
					),
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
		$disable_post_types = array( 'qpfw_phases' );

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
	public function qpfw_restart_process() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'qpfw-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed.', 'quote-product-flow' ) ) );
		}

		// Clear ALL QPFW session data.
		if ( isset( $_SESSION ) && is_array( $_SESSION ) ) {
			$keys_to_remove = array();

			// Find all QPFW related session keys.
			foreach ( $_SESSION as $key => $value ) {
				if ( strpos( $key, 'qpfw_' ) === 0 ) {
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
			// Delete all qpfw_phase_* user meta.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
					$user_id,
					'qpfw_phase_%'
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Process restarted successfully.', 'quote-product-flow' ),
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

	/**
	 * Keep QuoteProduct menu open when on taxonomy pages.
	 *
	 * @param string $parent_file Current parent file.
	 * @return string
	 */
	public function qpfw_fix_parent_file( $parent_file ) {
		$screen = get_current_screen();
		if ( ! empty( $screen ) && 'qpfw_variation_tag' === $screen->taxonomy ) {
			$parent_file = 'qpfw_menu';
		}
		return $parent_file;
	}

	/**
	 * Highlight correct submenu item on taxonomy pages.
	 *
	 * @param string $submenu_file Current submenu file.
	 * @return string
	 */
	public function qpfw_fix_submenu_file( $submenu_file ) {
		$screen = get_current_screen();
		if ( ! empty( $screen ) && 'qpfw_variation_tag' === $screen->taxonomy ) {
			$submenu_file = 'edit-tags.php?taxonomy=qpfw_variation_tag';
		}
		return $submenu_file;
	}

	/**
	 * Handle manual migration button submission.
	 */
	public function handle_run_migration() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'quote-product-flow' ) );
		}
		check_admin_referer( 'qpfw_run_migration' );

		delete_option( 'qpfw_cpt_migration_done' );
		qpfw_migrate_cpt_slugs();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'qpfw_menu',
					'qpfw_migration' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle repair button submission.
	 */
	public function handle_run_repair() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'quote-product-flow' ) );
		}
		check_admin_referer( 'qpfw_run_repair' );

		$stats = qpfw_repair_stale_phase_refs();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                => 'qpfw_menu',
					'qpfw_repair'         => '1',
					'qpfw_repair_phases'  => $stats['fixed_phases'],
					'qpfw_repair_created' => $stats['created_phases'],
					'qpfw_repair_deduped' => $stats['deduped_rows'],
					'qpfw_repair_depends' => $stats['fixed_depends'],
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}

