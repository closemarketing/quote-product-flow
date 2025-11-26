<?php
/**
 * Plugin Name: Product Budget Configurator
 * Plugin URI:  https://close.technology/wordpress-plugins/product-budget-configurator/
 * Description: Creates a configurator for complex products and makes a budget.
 * Version:     2.0.0
 * Author:      Closetechnology
 * Author URI:  https://close.technology
 * Text Domain: pbc
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * Requires plugins: duplicate-post
 *
 * @package     WordPress
 * @author      Closetechnology
 * @copyright   2022 Closemarketing
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 *
 * Prefix:      pbc
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'WPPBC_ITEM_NAME', 'Product Budget Configurator' );
define( 'WPPBC_VERSION', '1.5.0-beta.1' );
define( 'WPPBC_PLUGIN', __FILE__ );
define( 'WPPBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPPBC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPPBC_URL_API', 'https://close.technology/' );

add_action( 'plugins_loaded', 'pbc_plugin_init' );
/**
 * Load localization files
 *
 * @return void
 */
function pbc_plugin_init() {
	$base_path = dirname( plugin_basename( __FILE__ ) );
	load_plugin_textdomain( 'pbc', false, $base_path . '/languages' );
	load_plugin_textdomain( 'meta-box', false, $base_path . '/languages' );
}

if ( file_exists( WPPBC_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
	require_once WPPBC_PLUGIN_PATH . 'vendor/autoload.php';
}

// Initialize License Manager.
add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( 'Closemarketing\WPLicenseManager\License' ) && class_exists( 'Closemarketing\WPLicenseManager\Settings' ) ) {
			try {
				$license = new \Closemarketing\WPLicenseManager\License(
					array(
						'api_url'       => WPPBC_URL_API,
						'file'          => WPPBC_PLUGIN,
						'version'       => WPPBC_VERSION,
						'product_id'    => 2635,
						'slug'          => 'product-budget-configurator',
						'name'          => WPPBC_ITEM_NAME,
						'text_domain'   => 'pbc',
						'option_prefix' => 'pbc_license_',
					)
				);

				// Remove duplicate field registration from License class (Settings will handle it).
				remove_action( 'admin_init', array( $license, 'page_init' ) );

				// Set default Product ID if not already set.
				$product_id_key = $license->get_option_key( 'product_id' );
				if ( ! get_option( $product_id_key ) ) {
					update_option( $product_id_key, 2635 );
				}

				// License is now integrated into settings page, no separate menu needed.
				// Store license instance globally for settings page access.
				global $pbc_license_instance;
				$pbc_license_instance = $license;
			} catch ( Exception $e ) {
				add_action(
					'admin_notices',
					function () use ( $e ) {
						echo '<div class="notice notice-error"><p>' . esc_html( $e->getMessage() ) . '</p></div>';
					}
				);
			}
		}
	},
	20
);

// Initialize license instance on admin init.
add_action(
	'admin_init',
	function () {
		global $pbc_license_instance;
		if ( ! empty( $pbc_license_instance ) ) {
			$instance_key = $pbc_license_instance->get_option_key( 'instance' );
			if ( empty( get_option( $instance_key ) ) ) {
				$pbc_license_instance->license_instance_activation();
			}
		}
	},
	20
);

/**
 * Check if PBC license is active
 *
 * @return bool
 */
function pbc_is_license_active() {
	global $pbc_license_instance;
	
	// If license instance not available yet, return false.
	if ( empty( $pbc_license_instance ) || ! is_object( $pbc_license_instance ) ) {
		return false;
	}
	
	// Use try-catch to prevent fatal errors.
	try {
		// Check local activation status.
		$activated = get_option( $pbc_license_instance->get_option_key( 'activated' ), '' );
		
		// Must be locally activated.
		if ( 'Activated' !== $activated ) {
			return false;
		}
		
		// Verify against server periodically (cache for 12 hours).
		$last_check = get_transient( 'pbc_license_last_check' );
		if ( false === $last_check ) {
			$license_status = $pbc_license_instance->license_key_status();
			$is_really_active = ! empty( $license_status ) && 
			                    isset( $license_status['status_check'] ) && 
			                    'active' === $license_status['status_check'];
			
			if ( ! $is_really_active ) {
				// Deactivate locally if server says it's not active.
				update_option( $pbc_license_instance->get_option_key( 'activated' ), 'Deactivated' );
				return false;
			}
			
			// Cache the result for 12 hours.
			set_transient( 'pbc_license_last_check', time(), 12 * HOUR_IN_SECONDS );
		}
		
		return true;
	} catch ( Exception $e ) {
		// Log error and return false to be safe.
		error_log( 'PBC License Check Error: ' . $e->getMessage() );
		return false;
	}
}

// License activation notice.
add_action(
	'admin_notices',
	function () {
		// Only check if license instance is available.
		global $pbc_license_instance;
		if ( empty( $pbc_license_instance ) ) {
			return;
		}
		
		// Only show on PBC pages and plugins page.
		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return;
		}
		
		$show_on_screens = array( 'plugins', 'phases', 'variations', 'options', 'pbc_menu' );
		$is_pbc_screen = 'pbc_menu' === $screen->parent_base || in_array( $screen->id, $show_on_screens, true ) || in_array( $screen->post_type, array( 'phases', 'variations', 'options' ), true );
		
		if ( ! $is_pbc_screen ) {
			return;
		}
		
		if ( ! pbc_is_license_active() ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Product Budget Configurator:', 'pbc' ); ?></strong>
					<?php
					printf(
						/* translators: %s: Settings page URL */
						esc_html__( 'Your license is not active. The plugin will not work until you activate a valid license. %s', 'pbc' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=pbc_menu' ) ) . '">' . esc_html__( 'Activate License', 'pbc' ) . '</a>'
					);
					?>
				</p>
			</div>
			<?php
		}
	},
	99
);

// Only load plugin functionality if license is active OR in admin (to allow activation).
add_action(
	'plugins_loaded',
	function () {
		// Always load helpers as they are dependencies for other files.
		if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/helpers/class-calculations.php' ) ) {
			require_once WPPBC_PLUGIN_PATH . 'includes/helpers/class-calculations.php';
		}
		if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/helpers/class-show-parts.php' ) ) {
			require_once WPPBC_PLUGIN_PATH . 'includes/helpers/class-show-parts.php';
		}
		if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/helpers/class-show-template.php' ) ) {
			require_once WPPBC_PLUGIN_PATH . 'includes/helpers/class-show-template.php';
		}
		if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/helpers/class-generate-pdf.php' ) ) {
			require_once WPPBC_PLUGIN_PATH . 'includes/helpers/class-generate-pdf.php';
		}
		
		// Always load admin in case user needs to activate license.
		if ( is_admin() ) {
			if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/class-pbc-admin-plugin.php' ) ) {
				require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-admin-plugin.php';
			}
			if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/class-pbc-helper-posttypes.php' ) ) {
				require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-helper-posttypes.php';
			}
			if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/class-pbc-export-import.php' ) ) {
				require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-export-import.php';
			}
		}
		
		// Only load frontend and request functionality if license is active.
		if ( pbc_is_license_active() ) {
			// Include files.
			if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/class-pbc-request.php' ) ) {
				require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-request.php';
			}

			// Public.
			if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/class-pbc-public.php' ) ) {
				require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-public.php';
			}
		}
	},
	100
);
