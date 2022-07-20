<?php
/**
 * Plugin Name: Product Budget Configurator
 * Plugin URI:  https://close.technology/wordpress-plugins/product-budget-configurator/
 * Description: Creates a configurator for complex products and makes a budget.
 * Version:     1.1.0-rc.4
 * Author:      Closetechnology
 * Author URI:  https://close.technology
 * Text Domain: pbc
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

define( 'WPPBC_ITEM_NAME', 'Product Budget Configurator' );
define( 'WPPBC_VERSION', '1.1.0-rc.4' );
define( 'WPPBC_PLUGIN', __FILE__ );
define( 'WPPBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPPBC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPPBC_PLUGIN_DIR', untrailingslashit( dirname( WPPBC_PLUGIN ) ) );
define( 'WPPBC_URL_API', 'https://close.technology/' );

add_action( 'plugins_loaded', 'pbc_plugin_init' );
/**
 * Load localization files
 *
 * @return void
 */
function pbc_plugin_init() {
	load_plugin_textdomain( 'pbc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// Include files.
require_once WPPBC_PLUGIN_PATH . '/includes/class-admin-pbcplugin.php';
require_once WPPBC_PLUGIN_PATH . '/includes/helper-required-plugins.php';
