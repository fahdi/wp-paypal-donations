<?php
/**
 * PHPUnit bootstrap file
 *
 * @package WP_PayPal_Donations
 */

// Composer autoloader
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills
if (!class_exists('PHPUnit\Framework\TestCase')) {
    class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    // Set up WordPress environment for testing
    define('WPD_PLUGIN_PATH', dirname(__DIR__) . '/');
    define('WPD_PLUGIN_URL', 'http://example.org/wp-content/plugins/wp-paypal-donations/');
    define('WPD_VERSION', '1.0.0');
    
    require dirname(__DIR__) . '/wp-paypal-donations.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Mock WordPress functions for isolated unit tests
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        throw new Exception($message);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        return json_encode(array('success' => true, 'data' => $data));
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        return json_encode(array('success' => false, 'data' => $data));
    }
}

// Start up the WP testing environment.
if (file_exists($_tests_dir . '/includes/bootstrap.php')) {
    require $_tests_dir . '/includes/bootstrap.php';
} else {
    // Fallback for when WordPress test environment is not available
    echo "Warning: WordPress test environment not found. Some tests may fail.\n";
    
    // Mock essential WordPress functions
    if (!function_exists('add_action')) {
        function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
            return true;
        }
    }
    
    if (!function_exists('add_filter')) {
        function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
            return true;
        }
    }
    
    if (!function_exists('get_option')) {
        function get_option($option, $default = false) {
            return $default;
        }
    }
    
    if (!function_exists('update_option')) {
        function update_option($option, $value, $autoload = null) {
            return true;
        }
    }
}