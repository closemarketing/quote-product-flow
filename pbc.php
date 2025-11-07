<?php
/**
 * Plugin Name: Product Budget Configurator
 * Plugin URI:  https://close.technology/wordpress-plugins/product-budget-configurator/
 * Description: Creates a configurator for complex products and makes a budget.
 * Version:     1.4.2-beta.1
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
define( 'WPPBC_VERSION', '1.4.2-beta.1' );
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

// Helpers.
require_once WPPBC_PLUGIN_PATH . 'includes/helpers/class-calculations.php';
require_once WPPBC_PLUGIN_PATH . 'includes/helpers/class-show-parts.php';
require_once WPPBC_PLUGIN_PATH . 'includes/helpers/class-show-template.php';
require_once WPPBC_PLUGIN_PATH . 'includes/helpers/class-generate-pdf.php';

// Include files.
require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-admin-plugin.php';
require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-request.php';
require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-helper-posttypes.php';
require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-admin-plugin.php';

// Public.
require_once WPPBC_PLUGIN_PATH . 'includes/class-pbc-public.php';
