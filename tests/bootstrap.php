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

// When the WordPress test library is unavailable the plugin must still be
// loadable: it guards itself with `if (!defined('ABSPATH')) exit;`, so define
// the essentials *before* requiring it.
if (!file_exists($_tests_dir . '/includes/bootstrap.php')) {
    if (!defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (!defined('HOUR_IN_SECONDS')) {
        define('HOUR_IN_SECONDS', 3600);
    }
    if (!defined('WEEK_IN_SECONDS')) {
        define('WEEK_IN_SECONDS', 604800);
    }
    if (!class_exists('WP_Error')) {
        require_once __DIR__ . '/mocks/class-wp-error-mock.php';
    }
    if (!class_exists('WP_List_Table')) {
        require_once __DIR__ . '/mocks/class-wp-list-table-mock.php';
    }
    if (!class_exists('wpdb')) {
        require_once __DIR__ . '/mocks/class-wpdb-mock.php';
    }
    if (!defined('OBJECT')) {
        define('OBJECT', 'OBJECT');
    }
    if (!defined('ARRAY_A')) {
        define('ARRAY_A', 'ARRAY_A');
    }
}

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    // The plugin defines WPD_PLUGIN_PATH / WPD_PLUGIN_URL / WPD_VERSION itself
    // (via the plugin_dir_* mocks below); do not predefine them here.

    // Without the WordPress test library the WP functions the plugin calls at
    // load time are stubbed through Brain Monkey (see below), never as plain PHP
    // functions: Patchwork cannot redefine those later in individual tests.
    require dirname(__DIR__) . '/wp-paypal-donations.php';
}
// With the WordPress test library, load the plugin on muplugins_loaded.
// Without it, our local tests_add_filter() stub only stores the callback and
// never runs it, so load the plugin right away.
if (file_exists($_tests_dir . '/includes/bootstrap.php')) {
    tests_add_filter('muplugins_loaded', '_manually_load_plugin');
} else {
    // Brain Monkey only defines add_action()/add_filter() once setUp() has run.
    \Brain\Monkey\setUp();
    \Brain\Monkey\Functions\when('plugin_dir_url')->justReturn('http://example.org/wp-content/plugins/wp-paypal-donations/');
    \Brain\Monkey\Functions\when('plugin_dir_path')->justReturn(dirname(__DIR__) . '/');
    \Brain\Monkey\Functions\when('plugin_basename')->justReturn('wp-paypal-donations/wp-paypal-donations.php');
    \Brain\Monkey\Functions\when('register_activation_hook')->justReturn(null);
    \Brain\Monkey\Functions\when('register_deactivation_hook')->justReturn(null);
    \Brain\Monkey\Functions\when('load_plugin_textdomain')->justReturn(true);
    // WPD_PayPal_REST_API reads its settings in the constructor.
    \Brain\Monkey\Functions\when('get_option')->justReturn(false);
    _manually_load_plugin();
    \Brain\Monkey\tearDown();
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