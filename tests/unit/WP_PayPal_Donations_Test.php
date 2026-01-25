<?php
/**
 * Unit tests for WP_PayPal_Donations main class
 *
 * @package WP_PayPal_Donations
 */

namespace WPPayPalDonations\Tests\Unit;

use WPPayPalDonations\Tests\TestCase;
use Brain\Monkey;

/**
 * Test the main plugin class
 */
class WP_PayPal_Donations_Test extends TestCase {

    /**
     * @var WP_PayPal_Donations
     */
    private $plugin;

    protected function setUp(): void {
        parent::setUp();
        
        // Mock WordPress constants
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/tmp/wordpress/');
        }
        
        $this->plugin = new \WP_PayPal_Donations();
    }

    /**
     * Test plugin initialization
     */
    public function test_plugin_initialization() {
        $this->assertInstanceOf(\WP_PayPal_Donations::class, $this->plugin);
    }

    /**
     * Test hooks are properly registered
     */
    public function test_init_hooks_registered() {
        // Mock WordPress actions
        Monkey\Actions\expectAdded('admin_menu');
        Monkey\Actions\expectAdded('admin_init');
        Monkey\Actions\expectAdded('wp_enqueue_scripts');
        Monkey\Actions\expectAdded('wp_ajax_process_donation');
        Monkey\Actions\expectAdded('wp_ajax_nopriv_process_donation');
        Monkey\Actions\expectAdded('init')->times(2); // handle_paypal_return and register_donation_block
        
        Monkey\Filters\expectAdded('init');
        
        $this->plugin->init();
        
        $this->assertTrue(true); // If we get here, all hooks were registered
    }

    /**
     * Test currency symbol method
     */
    public function test_get_currency_symbol() {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('get_currency_symbol');
        $method->setAccessible(true);

        $this->assertEquals('$', $method->invoke($this->plugin, 'USD'));
        $this->assertEquals('€', $method->invoke($this->plugin, 'EUR'));
        $this->assertEquals('£', $method->invoke($this->plugin, 'GBP'));
        $this->assertEquals('¥', $method->invoke($this->plugin, 'JPY'));
        $this->assertEquals('$', $method->invoke($this->plugin, 'UNKNOWN')); // Default
    }

    /**
     * Test PayPal URL generation
     */
    public function test_get_paypal_url() {
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('get_paypal_url');
        $method->setAccessible(true);

        // Test sandbox mode
        Monkey\Functions\when('get_option')
            ->justReturn(true)
            ->whenHappen(function($option) {
                if ($option === 'wpd_sandbox_mode') {
                    return true;
                }
                return false;
            });

        $sandbox_url = $method->invoke($this->plugin);
        $this->assertEquals('https://www.sandbox.paypal.com/cgi-bin/webscr', $sandbox_url);

        // Test live mode
        Monkey\Functions\when('get_option')
            ->justReturn(false)
            ->whenHappen(function($option) {
                if ($option === 'wpd_sandbox_mode') {
                    return false;
                }
                return false;
            });

        $live_url = $method->invoke($this->plugin);
        $this->assertEquals('https://www.paypal.com/cgi-bin/webscr', $live_url);
    }

    /**
     * Test shortcode attributes parsing
     */
    public function test_donation_form_shortcode_attributes() {
        // Mock shortcode_atts function
        Monkey\Functions\when('shortcode_atts')
            ->returnArg(2); // Return the provided attributes

        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                switch ($option) {
                    case 'wpd_default_amount':
                        return '25';
                    case 'wpd_currency':
                        return 'USD';
                    default:
                        return $default;
                }
            });

        // Test default attributes
        $atts = array();
        $result = $this->plugin->donation_form_shortcode($atts);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('wpd-donation-form-container', $result);
    }

    /**
     * Test donation form rendering
     */
    public function test_donation_form_rendering() {
        // Mock WordPress functions for template rendering
        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                switch ($option) {
                    case 'wpd_currency':
                        return 'USD';
                    case 'wpd_enable_recurring':
                        return false;
                    default:
                        return $default;
                }
            });

        $atts = array(
            'amount' => '50',
            'title' => 'Test Donation Form',
            'currency' => 'USD'
        );

        // Capture output
        ob_start();
        include WPD_PLUGIN_PATH . 'templates/donation-form.php';
        $output = ob_get_clean();

        $this->assertStringContainsString('Test Donation Form', $output);
        $this->assertStringContainsString('value="50"', $output);
        $this->assertStringContainsString('wpd-donation-form', $output);
    }

    /**
     * Test admin menu registration
     */
    public function test_admin_menu() {
        Monkey\Functions\expect('add_menu_page')
            ->once()
            ->with(
                'Donations',
                'Donations',
                'manage_options',
                'wp-paypal-donations',
                array($this->plugin, 'admin_page'),
                'dashicons-heart',
                30
            );

        Monkey\Functions\expect('add_submenu_page')
            ->once()
            ->with(
                'wp-paypal-donations',
                'Settings',
                'Settings',
                'manage_options',
                'wp-paypal-donations-settings',
                array($this->plugin, 'settings_page')
            );

        $this->plugin->admin_menu();
    }

    /**
     * Test settings registration
     */
    public function test_admin_init() {
        Monkey\Functions\expect('register_setting')
            ->times(6)
            ->with(
                Monkey\Functions\anyOf(
                    'wpd_settings',
                    'wpd_paypal_email',
                    'wpd_sandbox_mode',
                    'wpd_currency',
                    'wpd_default_amount',
                    'wpd_success_message',
                    'wpd_enable_recurring'
                )
            );

        $this->plugin->admin_init();
    }

    /**
     * Test script enqueuing
     */
    public function test_enqueue_scripts() {
        Monkey\Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'wpd-frontend',
                WPD_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                WPD_VERSION,
                true
            );

        Monkey\Functions\expect('wp_enqueue_style')
            ->once()
            ->with(
                'wpd-frontend',
                WPD_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                WPD_VERSION
            );

        Monkey\Functions\expect('wp_localize_script')
            ->once()
            ->with(
                'wpd-frontend',
                'wpd_ajax',
                Monkey\Functions\type('array')
            );

        $this->plugin->enqueue_scripts();
    }

    /**
     * Test block registration
     */
    public function test_register_donation_block() {
        // Mock Gutenberg functions
        Monkey\Functions\when('function_exists')
            ->justReturn(true)
            ->whenHappen(function($func) {
                return $func === 'register_block_type';
            });

        Monkey\Functions\expect('wp_register_script')
            ->once()
            ->with(
                'wp-paypal-donations-block',
                WPD_PLUGIN_URL . 'blocks/donation-form/build/index.js',
                array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-block-editor'),
                WPD_VERSION,
                true
            );

        Monkey\Functions\expect('wp_register_style')
            ->once()
            ->with(
                'wp-paypal-donations-block-editor',
                WPD_PLUGIN_URL . 'blocks/donation-form/build/editor.css',
                array('wp-edit-blocks'),
                WPD_VERSION
            );

        Monkey\Functions\expect('register_block_type')
            ->once()
            ->with(
                WPD_PLUGIN_PATH . 'blocks/donation-form/block.json',
                Monkey\Functions\type('array')
            );

        $this->plugin->register_donation_block();
    }

    /**
     * Test block registration when Gutenberg not available
     */
    public function test_register_donation_block_no_gutenberg() {
        Monkey\Functions\when('function_exists')
            ->justReturn(false)
            ->whenHappen(function($func) {
                return $func === 'register_block_type';
            });

        Monkey\Functions\expect('wp_register_script')->never();
        Monkey\Functions\expect('register_block_type')->never();

        $this->plugin->register_donation_block();
    }
}