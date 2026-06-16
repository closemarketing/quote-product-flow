<?php
/**
 * Plugin Name: Quote Product Flow
 * Plugin URI:  https://close.technology/wordpress-plugins/quote-product-flow/
 * Description: Creates a configurator for complex products and makes a budget.
 * Version:     2.0.0
 * Author:      Closetechnology
 * Author URI:  https://close.technology
 * Text Domain: quote-product-flow
 *
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
 * Prefix:      qpfw
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'QPFW_VERSION', '2.0.0' );
define( 'QPFW_PLUGIN', __FILE__ );
define( 'QPFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'QPFW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'QPFW_ITEM_NAME' ) ) {
	define( 'QPFW_ITEM_NAME', 'Quote Product Flow' );
}
if ( ! defined( 'QPFW_VERSION' ) ) {
	define( 'QPFW_VERSION', '2.0.0' );
}
if ( ! defined( 'QPFW_PLUGIN' ) ) {
	define( 'QPFW_PLUGIN', __FILE__ );
}
if ( ! defined( 'QPFW_PLUGIN_URL' ) ) {
	define( 'QPFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'QPFW_PLUGIN_PATH' ) ) {
	define( 'QPFW_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'QPFW_URL_API' ) ) {
	define( 'QPFW_URL_API', 'https://close.technology/' );
}


if ( file_exists( QPFW_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
	require_once QPFW_PLUGIN_PATH . 'vendor/autoload.php';
}

require_once QPFW_PLUGIN_PATH . 'includes/Migrate.php';

// Run migration on activation (fresh installs / manual reactivation).
register_activation_hook( __FILE__, 'qpfw_migrate_cpt_slugs' );

// Also run on plugins_loaded for updates that skip deactivation/activation.
add_action( 'plugins_loaded', 'qpfw_migrate_cpt_slugs', 1 );

/**
 * Check if QPFW Pro features are available.
 *
 * @return bool
 */
function qpfw_is_pro() {
	return (bool) apply_filters( 'qpfw_is_pro', false );
}

// Upgrade to Pro notice for free plugin users.
add_action(
	'admin_notices',
	function () {
		if ( qpfw_is_pro() ) {
			return;
		}

		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return;
		}

		$is_qpfw_screen = 'qpfw_menu' === $screen->parent_base
			|| in_array( $screen->id, array( 'plugins', 'qpfw_menu' ), true )
			|| in_array( $screen->post_type, array( 'qpfw_phases', 'qpfw_variation' ), true );

		if ( ! $is_qpfw_screen || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Quote Product Flow', 'quote-product-flow' ); ?></strong> &mdash;
				<?php
				printf(
					/* translators: %s: Upgrade URL */
					esc_html__( 'Unlock recommendations, PDF branding, role discounts and more. %s', 'quote-product-flow' ),
					'<a href="https://close.technology/wordpress-plugins/quote-product-flow/" target="_blank">' . esc_html__( 'Upgrade to Pro', 'quote-product-flow' ) . '</a>'
				);
				?>
			</p>
		</div>
		<?php
	},
	99
);

use CLOSE\QProductFlow\AdminPlugin;
use CLOSE\QProductFlow\HelperPostTypes;
use CLOSE\QProductFlow\PublicFront;
use CLOSE\QProductFlow\Requests;
use CLOSE\QProductFlow\SvgSupport;

// Load plugin functionality.
add_action(
	'plugins_loaded',
	function () {
		// Always load admin.
		if ( is_admin() ) {
			new SvgSupport();
			new AdminPlugin();
			new HelperPostTypes();
		}

		// Always load frontend files.
		new Requests();

		// Public.
		new PublicFront();
	},
	100
);

