<?php
/**
 * Plugin Name: Product Budget Configurator
 * Plugin URI:  https://close.technology/wordpress-plugins/product-budget-configurator/
 * Description: Creates a configurator for complex products and makes a budget.
 * Version:     1.5.1
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
define( 'WPPBC_VERSION', '1.5.1' );
define( 'WPPBC_PLUGIN', __FILE__ );
define( 'WPPBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPPBC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPPBC_URL_API', 'https://close.technology/' );

// License Manager Configuration.
define( 'WPPBC_LICENSE_API_URL', 'https://close.technology/' );
define( 'WPPBC_LICENSE_API_KEY', 'ck_857ef2cf419641b2741ed4ea4d5a750aa979113a' );
define( 'WPPBC_LICENSE_API_SECRET', 'cs_851fd6126de05a967fc8abb949afe74344faee71' );
define( 'WPPBC_LICENSE_PRODUCT_UUID', 'PBC-5E973533-1688-43CD-B151-ABC2C639B336' );

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

/**
 * License Manager instance.
 *
 * @var \Closemarketing\WPLicenseManager\License|null
 */
$pbc_license = null;

/**
 * Initialize License Manager.
 */
add_action(
	'plugins_loaded',
	function () {
		global $pbc_license;

		if ( ! class_exists( '\Closemarketing\WPLicenseManager\License' ) ) {
			return;
		}

		try {
			$pbc_license = new \Closemarketing\WPLicenseManager\License(
				array(
					'api_url'          => WPPBC_LICENSE_API_URL,
					'rest_api_key'     => WPPBC_LICENSE_API_KEY,
					'rest_api_secret'  => WPPBC_LICENSE_API_SECRET,
					'product_uuid'     => WPPBC_LICENSE_PRODUCT_UUID,
					'file'             => WPPBC_PLUGIN,
					'version'          => WPPBC_VERSION,
					'slug'             => 'product-budget-configurator',
					'name'             => WPPBC_ITEM_NAME,
					'text_domain'      => 'pbc',
					'settings_page'    => 'pbc_menu',
					'settings_tabs'    => 'pbc_license_tabs_disabled',    // Disabled - UI handled by PBC Admin.
					'settings_content' => 'pbc_license_content_disabled', // Disabled - UI handled by PBC Admin.
				)
			);
		} catch ( \Exception $e ) {
			add_action(
				'admin_notices',
				function () use ( $e ) {
					echo '<div class="notice notice-error"><p>Product Budget Configurator: ' . esc_html( $e->getMessage() ) . '</p></div>';
				}
			);
		}
	},
	5
);


/**
 * Check if PBC license is active (valid and not expired).
 *
 * @since 1.0.0
 * @return bool True if license is valid and active, false otherwise.
 */
function pbc_is_license_active() {
	// Bypass for development.
	if ( defined( 'PBC_BYPASS_LICENSE' ) && PBC_BYPASS_LICENSE ) {
		return true;
	}

	global $pbc_license;

	if ( null === $pbc_license ) {
		return false;
	}

	return $pbc_license->is_license_active();
}

/**
 * Check if PBC license is registered.
 *
 * @since 2.0.0
 * @return bool True if license is registered, false otherwise.
 */
function pbc_is_license_registered() {
	global $pbc_license;

	if ( empty( $pbc_license ) ) {
		return false;
	}

	return $pbc_license->get_api_key_status();
}

/**
 * Check if PBC license has expired.
 *
 * @since 2.0.0
 * @return bool True if license has expired, false otherwise.
 */
function pbc_has_license_expired() {
	global $pbc_license;

	if ( null === $pbc_license ) {
		return true;
	}

	$status = get_option( 'product-budget-configurator_license_activated', 'Deactivated' );
	return 'Expired' === $status;
}

/**
 * Get PBC license status.
 *
 * @since 2.0.0
 * @return string License status (active, inactive, expired).
 */
function pbc_get_license_status() {
	// Check for bypass first.
	if ( defined( 'PBC_BYPASS_LICENSE' ) && PBC_BYPASS_LICENSE ) {
		return 'active';
	}

	global $pbc_license;

	if ( null === $pbc_license ) {
		return 'inactive';
	}

	$status = get_option( 'product-budget-configurator_license_activated', 'Deactivated' );

	if ( 'Activated' === $status ) {
		return 'active';
	} elseif ( 'Expired' === $status ) {
		return 'expired';
	}

	return 'inactive';
}

/**
 * Get stored license key.
 *
 * @since 2.0.0
 * @return string License key or empty string.
 */
function pbc_get_stored_license_key() {
	return get_option( 'product-budget-configurator_license_apikey', '' );
}

/**
 * Get license data.
 *
 * @since 2.0.0
 * @return array|false License data or false.
 */
function pbc_get_license_data() {
	$status  = get_option( 'product-budget-configurator_license_activated', 'Deactivated' );
	$api_key = get_option( 'product-budget-configurator_license_apikey', '' );

	if ( empty( $api_key ) ) {
		return false;
	}

	return array(
		'status'  => $status,
		'key'     => $api_key,
		'expires' => '', // Expires is fetched from API on status check.
	);
}

// License activation notice.
add_action(
	'admin_notices',
	function () {
		// Only check if license instance is available.
		global $pbc_license;
		if ( empty( $pbc_license ) ) {
			return;
		}

		// Only show on PBC pages and plugins page.
		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return;
		}

		$show_on_screens = array( 'plugins', 'phases', 'variations', 'options', 'pbc_menu' );
		$is_pbc_screen   = 'pbc_menu' === $screen->parent_base || in_array( $screen->id, $show_on_screens, true ) || in_array( $screen->post_type, array( 'phases', 'variations', 'options' ), true );

		if ( ! $is_pbc_screen ) {
			return;
		}

		$license_status = pbc_get_license_status();

		if ( 'active' !== $license_status ) {
			$message = '';
			$type    = 'warning';

			if ( 'expired' === $license_status ) {
				$message = sprintf(
				/* translators: %s: Settings page URL */
					esc_html__( 'Your license has expired. Please renew your license to continue receiving updates and support. %s', 'pbc' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=pbc_menu' ) ) . '">' . esc_html__( 'Renew License', 'pbc' ) . '</a>'
				);
			$type = 'error';
		} else {
			$message = sprintf(
				/* translators: %s: Settings page URL */
				esc_html__( 'Please activate your license to receive updates and support. %s', 'pbc' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=pbc_menu' ) ) . '">' . esc_html__( 'Activate License', 'pbc' ) . '</a>'
			);
			}
			?>
			<div class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
				<p>
					<strong><?php esc_html_e( 'Product Budget Configurator:', 'pbc' ); ?></strong>
					<?php echo wp_kses_post( $message ); ?>
				</p>
			</div>
			<?php
		}
	},
	99
);

// Load plugin functionality.
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

		// Always load admin.
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

		// Always load frontend files.
		if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/class-pbc-request.php' ) ) {
			require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-request.php';
		}

		// Public.
		if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/class-pbc-public.php' ) ) {
			require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-public.php';
		}
	},
	100
);

/**
 * Conditionally load premium features based on license status.
 *
 * This allows the plugin to work but shows license warnings when invalid.
 *
 * @since 2.0.0
 */
add_action(
	'plugins_loaded',
	function () {
		/**
		 * Filter to bypass license check for premium features.
		 *
		 * @since 2.0.0
		 * @param bool $bypass Whether to bypass license check. Default false.
		 */
		$bypass_license = apply_filters( 'pbc_bypass_license_check', false );

		if ( $bypass_license || pbc_is_license_active() ) {
			/**
			 * Action fired when premium features should be loaded.
			 *
			 * Use this hook to conditionally load premium-only features.
			 *
			 * @since 2.0.0
			 */
			do_action( 'pbc_load_premium_features' );
		}
	},
	110
);
