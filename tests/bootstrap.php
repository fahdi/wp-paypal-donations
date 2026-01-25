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
if (file_exists($_tests_dir . '/includes/functions.php')) {
    require_once $_tests_dir . '/includes/functions.php';
} else {
    // Mock tests_add_filter function if WordPress test environment not available
    if (!function_exists('tests_add_filter')) {
        function tests_add_filter($hook, $callback) {
            // Store callbacks for later execution if needed
            global $wp_filter_callbacks;
            if (!isset($wp_filter_callbacks)) {
                $wp_filter_callbacks = array();
            }
            $wp_filter_callbacks[$hook] = $callback;
            return true;
        }
    }
}

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    // Set up WordPress environment for testing
    if (!defined('WPD_PLUGIN_PATH')) {
        define('WPD_PLUGIN_PATH', dirname(__DIR__) . '/');
    }
    if (!defined('WPD_PLUGIN_URL')) {
        define('WPD_PLUGIN_URL', 'http://example.org/wp-content/plugins/wp-paypal-donations/');
    }
    if (!defined('WPD_VERSION')) {
        define('WPD_VERSION', '1.0.0');
    }
    
    // Mock WordPress functions that the plugin uses
    if (!function_exists('plugin_dir_url')) {
        function plugin_dir_url($file) {
            return 'http://example.org/wp-content/plugins/wp-paypal-donations/';
        }
    }
    
    if (!function_exists('plugin_dir_path')) {
        function plugin_dir_path($file) {
            return dirname(__DIR__) . '/';
        }
    }
    
    if (!function_exists('plugin_basename')) {
        function plugin_basename($file) {
            return 'wp-paypal-donations/wp-paypal-donations.php';
        }
    }
    
    if (!function_exists('register_activation_hook')) {
        function register_activation_hook($file, $callback) {
            // Mock activation hook
        }
    }
    
    if (!function_exists('register_deactivation_hook')) {
        function register_deactivation_hook($file, $callback) {
            // Mock deactivation hook
        }
    }
    
    if (!function_exists('load_plugin_textdomain')) {
        function load_plugin_textdomain($domain, $deprecated, $plugin_rel_path) {
            return true;
        }
    }
    
    require dirname(__DIR__) . '/wp-paypal-donations.php';
}
// Execute callback immediately if WordPress test environment not available
if (function_exists('tests_add_filter')) {
    tests_add_filter('muplugins_loaded', '_manually_load_plugin');
} else {
    // Execute manually if WordPress test environment not available
    _manually_load_plugin();
}

// WordPress functions will be mocked by Brain Monkey in individual test files

// Start up the WP testing environment.
if (file_exists($_tests_dir . '/includes/bootstrap.php')) {
    require $_tests_dir . '/includes/bootstrap.php';
} else {
    // Fallback for when WordPress test environment is not available
    echo "Warning: WordPress test environment not found. Using Brain Monkey for mocking.\n";
    
    // Define essential WordPress constants if not already defined
    if (!defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    
    if (!defined('WP_CONTENT_DIR')) {
        define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
    }
    
    if (!defined('WP_PLUGIN_DIR')) {
        define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
    }
}