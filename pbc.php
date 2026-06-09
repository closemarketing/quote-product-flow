<?php
/**
 * Plugin Name: Product Budget Configurator
 * Plugin URI:  https://close.technology/wordpress-plugins/product-budget-configurator/
 * Description: Creates a configurator for complex products and makes a budget.
 * Version:     2.0.0-beta.1
 * Author:      Closetechnology
 * Author URI:  https://close.technology
 * Text Domain: product-budget-configurator
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
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

if ( ! defined( 'WPPBC_ITEM_NAME' ) ) {
	define( 'WPPBC_ITEM_NAME', 'Product Budget Configurator' );
}
if ( ! defined( 'WPPBC_VERSION' ) ) {
	define( 'WPPBC_VERSION', '2.0.0-beta.1' );
}
define( 'WPPBC_PLUGIN', __FILE__ );
define( 'WPPBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPPBC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'WPPBC_URL_API' ) ) {
	define( 'WPPBC_URL_API', 'https://close.technology/' );
}


if ( file_exists( WPPBC_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
	require_once WPPBC_PLUGIN_PATH . 'vendor/autoload.php';
}

/**
 * Check if PBC Pro features are available.
 *
 * @return bool
 */
function pbc_is_pro() {
	return (bool) apply_filters( 'pbc_is_pro', false );
}

// Upgrade to Pro notice for free plugin users.
add_action(
	'admin_notices',
	function () {
		if ( pbc_is_pro() ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return;
		}

		$is_pbc_screen = 'pbc_menu' === $screen->parent_base
			|| in_array( $screen->id, array( 'plugins', 'pbc_menu' ), true )
			|| in_array( $screen->post_type, array( 'phases', 'variation' ), true );

		if ( ! $is_pbc_screen || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Product Budget Configurator', 'product-budget-configurator' ); ?></strong> &mdash;
				<?php
				printf(
					/* translators: %s: Upgrade URL */
					esc_html__( 'Unlock recommendations, PDF branding, role discounts, import/export and more. %s', 'product-budget-configurator' ),
					'<a href="https://close.technology/wordpress-plugins/product-budget-configurator/" target="_blank">' . esc_html__( 'Upgrade to Pro', 'product-budget-configurator' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
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
			if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/class-pbc-svg-support.php' ) ) {
				require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-svg-support.php';
			}
			if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/class-pbc-admin-plugin.php' ) ) {
				require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-admin-plugin.php';
			}
			if ( file_exists( WPPBC_PLUGIN_PATH . 'includes/class-pbc-helper-posttypes.php' ) ) {
				require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-helper-posttypes.php';
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

