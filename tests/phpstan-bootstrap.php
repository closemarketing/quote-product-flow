<?php
/**
 * PHPStan Bootstrap File
 * 
 * This file defines constants and functions that PHPStan needs to understand
 * but are not available during static analysis.
 */

// Define plugin constants that are used throughout the codebase
if (!defined('WPPBC_PLUGIN_URL')) {
    define('WPPBC_PLUGIN_URL', 'http://localhost/wp-content/plugins/product-budget-configurator/');
}

if ( !defined('WPPBC_PLUGIN_PATH') ) {
    define('WPPBC_PLUGIN_PATH', '/path/to/wp-content/plugins/product-budget-configurator/');
}

if (!defined('WPPBC_VERSION')) {
    define('WPPBC_VERSION', '1.0.0');
}

if (!defined('WPPBC_PLUGIN')) {
    define('WPPBC_PLUGIN', __FILE__);
}

if (!defined('WPPBC_ITEM_NAME')) {
    define('WPPBC_ITEM_NAME', 'Product Budget Configurator');
}

if (!defined('WPPBC_URL_API')) {
    define('WPPBC_URL_API', 'https://close.technology/');
}


// Define WordPress constants that might be missing
if (!defined('DOING_AJAX')) {
    define('DOING_AJAX', false);
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/path/to/wordpress/');
}

// Mock WordPress functions that PHPStan can't find
if (!function_exists('wp_doing_ajax')) {
    function wp_doing_ajax() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
}

// Mock Action Scheduler function
if (!function_exists('as_schedule_recurring_action')) {
    function as_schedule_recurring_action($timestamp, $interval_in_seconds, $hook, $args = [], $group = '') {
        return true;
    }
}

// Mock PBC license functions
if (!function_exists('pbc_is_license_active')) {
    /**
     * Check if PBC license is active.
     * Mock pbc_is_license_active function for PHPStan analysis.
     *
     * @return bool
     */
    function pbc_is_license_active() {
        return true;
    }
}

if (!function_exists('pbc_is_license_registered')) {
    /**
     * Check if PBC license is registered.
     *
     * @return bool
     */
    function pbc_is_license_registered() {
        return true;
    }
}

// Mock License Manager classes - using spl_autoload_register
spl_autoload_register(function ($class) {
    if ($class === 'Closemarketing\WPLicenseManager\FormsCRMSettings') {
        eval('
        namespace Closemarketing\WPLicenseManager;
        class FormsCRMSettings {
            public function __construct($license = null, array $args = []) {}
            public function render() {}
        }
        ');
    }
    if ($class === 'Closemarketing\WPLicenseManager\License') {
        eval('
        namespace Closemarketing\WPLicenseManager;
        class License {
            public function __construct(array $args = []) {}
            public function is_license_active() { return true; }
            public function get_api_key_status() { return true; }
            public function get_option_value($key) { return ""; }
            public function validate_license($input) {}
        }
        ');
    }
});
