<?php
/**
 * PHPUnit bootstrap file for Product Budget Configurator
 *
 * @package Product_Budget_Configurator
 */

define( 'TESTS_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'UNIT_TESTS_DATA_PLUGIN_DIR', TESTS_PLUGIN_DIR . '/tests/Data/' );

// Define WP_CORE_DIR if not already defined.
if ( ! defined( 'WP_CORE_DIR' ) ) {
	$_wp_core_dir = getenv( 'WP_CORE_DIR' );
	if ( ! $_wp_core_dir ) {
		$_wp_core_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress';
	}
	define( 'WP_CORE_DIR', $_wp_core_dir );
}

// Define plugin constants that are used throughout the codebase.
if ( ! defined( 'WPPBC_VERSION' ) ) {
	define( 'WPPBC_VERSION', '1.0.0' );
}

if ( ! defined( 'WPPBC_PLUGIN_URL' ) ) {
	define( 'WPPBC_PLUGIN_URL', 'http://localhost/wp-content/plugins/product-budget-configurator/' );
}

if ( ! defined( 'WPPBC_PLUGIN' ) ) {
	define( 'WPPBC_PLUGIN', TESTS_PLUGIN_DIR . '/pbc.php' );
}

if ( ! defined( 'WPPBC_PLUGIN_PATH' ) ) {
	define( 'WPPBC_PLUGIN_PATH', TESTS_PLUGIN_DIR . '/' );
}

// Give access to tests_add_filter() function.
require_once WP_CORE_DIR . '/wp-includes/plugin.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load composer autoloader.
	require TESTS_PLUGIN_DIR . '/vendor/autoload.php';

	// Load the plugin.
	require TESTS_PLUGIN_DIR . '/pbc.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';

