<?php
/**
 * Integration tests for PayPal processing
 *
 * @package WP_PayPal_Donations
 */

namespace WPPayPalDonations\Tests\Integration;

use WPPayPalDonations\Tests\TestCase;
use Brain\Monkey;

/**
 * Test PayPal payment processing integration
 */
class PayPal_Processing_Test extends TestCase {

    /**
     * @var WP_PayPal_Donations
     */
    private $plugin;

    protected function setUp(): void {
        parent::setUp();
        
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/tmp/wordpress/');
        }
        
        $this->plugin = new \WP_PayPal_Donations();
        
        // Mock $_POST global
        $_POST = array();
    }

    /**
     * Test donation processing with valid data
     */
    public function test_process_donation_valid_data() {
        // Mock nonce verification
        Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        Monkey\Functions\when('wp_create_nonce')->justReturn('test_nonce');
        
        // Mock form data
        $_POST = array(
            'amount' => '25.00',
            'donor_name' => 'John Doe',
            'donor_email' => 'john@example.com',
            'nonce' => 'test_nonce'
        );

        // Mock database operations
        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->once())
             ->method('insert')
             ->willReturn(1);
        $wpdb->insert_id = 1;

        global $wpdb;
        
        // Mock PayPal settings
        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                switch ($option) {
                    case 'wpd_paypal_email':
                        return 'business@example.com';
                    case 'wpd_currency':
                        return 'USD';
                    case 'wpd_sandbox_mode':
                        return true;
                    default:
                        return $default;
                }
            });

        Monkey\Functions\when('add_query_arg')->returnArg();
        Monkey\Functions\when('home_url')->justReturn('http://example.com');
        
        // Mock JSON response
        Monkey\Functions\expect('wp_send_json_success')
            ->once()
            ->with(Monkey\Functions\type('array'));

        $this->plugin->process_donation();
    }

    /**
     * Test donation processing with missing required fields
     */
    public function test_process_donation_missing_fields() {
        Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        
        // Missing donor name
        $_POST = array(
            'amount' => '25.00',
            'donor_email' => 'john@example.com',
            'nonce' => 'test_nonce'
        );

        Monkey\Functions\expect('wp_die')
            ->once()
            ->with('Please fill in all required fields.');

        $this->expectException(\Exception::class);
        $this->plugin->process_donation();
    }

    /**
     * Test IPN handling with valid PayPal response
     */
    public function test_handle_ipn_valid_response() {
        // Mock PayPal IPN data
        $_POST = $this->createMockIPNRequest();
        $_GET['wpd_ipn'] = '1';

        // Mock HTTP response from PayPal
        Monkey\Functions\when('wp_remote_post')
            ->justReturn(array(
                'body' => 'VERIFIED',
                'response' => array('code' => 200)
            ));

        Monkey\Functions\when('wp_remote_retrieve_body')
            ->justReturn('VERIFIED');

        // Mock settings
        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                if ($option === 'wpd_sandbox_mode') {
                    return true;
                }
                return $default;
            });

        // Mock database update
        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->once())
             ->method('update')
             ->willReturn(1);

        global $wpdb;

        // Mock notification sending
        $reflection = new \ReflectionClass($this->plugin);
        $sendNotifications = $reflection->getMethod('send_donation_notifications');
        $sendNotifications->setAccessible(true);

        // Create a mock that expects the notification method to be called
        $plugin_mock = $this->getMockBuilder(\WP_PayPal_Donations::class)
                            ->onlyMethods(['send_donation_notifications'])
                            ->getMock();
        
        $plugin_mock->expects($this->once())
                    ->method('send_donation_notifications')
                    ->with(1);

        // We can't easily test the private method, so we'll test the public interface
        $this->expectOutputString(''); // IPN handler should exit with no output
        
        // Mock the exit to prevent actual exit
        Monkey\Functions\when('exit')->justReturn(null);
        
        $this->plugin->handle_paypal_return();
    }

    /**
     * Test IPN handling with invalid PayPal response
     */
    public function test_handle_ipn_invalid_response() {
        $_POST = $this->createMockIPNRequest();
        $_GET['wpd_ipn'] = '1';

        // Mock HTTP response from PayPal - INVALID
        Monkey\Functions\when('wp_remote_post')
            ->justReturn(array(
                'body' => 'INVALID',
                'response' => array('code' => 200)
            ));

        Monkey\Functions\when('wp_remote_retrieve_body')
            ->justReturn('INVALID');

        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                if ($option === 'wpd_sandbox_mode') {
                    return true;
                }
                return $default;
            });

        // Database should not be updated for invalid IPN
        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->never())
             ->method('update');

        global $wpdb;

        Monkey\Functions\when('exit')->justReturn(null);
        
        $this->plugin->handle_paypal_return();
    }

    /**
     * Test recurring donation processing
     */
    public function test_process_recurring_donation() {
        Monkey\Functions\when('check_ajax_referer')->justReturn(true);
        
        $_POST = array(
            'amount' => '25.00',
            'donor_name' => 'John Doe',
            'donor_email' => 'john@example.com',
            'recurring' => 'true',
            'nonce' => 'test_nonce'
        );

        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->once())
             ->method('insert')
             ->willReturn(1);
        $wpdb->insert_id = 1;

        global $wpdb;

        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                switch ($option) {
                    case 'wpd_paypal_email':
                        return 'business@example.com';
                    case 'wpd_currency':
                        return 'USD';
                    case 'wpd_sandbox_mode':
                        return true;
                    default:
                        return $default;
                }
            });

        Monkey\Functions\when('add_query_arg')
            ->alias(function($args, $url) {
                // Check if recurring parameters are included
                if (is_array($args) && isset($args['cmd'])) {
                    $this->assertEquals('_xclick-subscriptions', $args['cmd']);
                    $this->assertEquals(1, $args['p3']);
                    $this->assertEquals('M', $args['t3']);
                    $this->assertEquals(1, $args['src']);
                }
                return $url . '?' . http_build_query($args);
            });

        Monkey\Functions\when('home_url')->justReturn('http://example.com');
        
        Monkey\Functions\expect('wp_send_json_success')
            ->once()
            ->with(Monkey\Functions\type('array'));

        $this->plugin->process_donation();
    }

    /**
     * Test PayPal success return handling
     */
    public function test_handle_paypal_success_return() {
        $_GET['donation_success'] = '1';

        Monkey\Functions\expect('add_action')
            ->once()
            ->with('wp_footer', array($this->plugin, 'show_success_message'));

        $this->plugin->handle_paypal_return();
    }

    /**
     * Test PayPal cancelled return handling
     */
    public function test_handle_paypal_cancelled_return() {
        $_GET['donation_cancelled'] = '1';

        Monkey\Functions\expect('add_action')
            ->once()
            ->with('wp_footer', array($this->plugin, 'show_cancelled_message'));

        $this->plugin->handle_paypal_return();
    }

    /**
     * Test success message display
     */
    public function test_show_success_message() {
        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                if ($option === 'wpd_success_message') {
                    return 'Thank you for your donation!';
                }
                return $default;
            });

        $this->expectOutputString('<div class="wpd-success-message">Thank you for your donation!</div>');
        
        $this->plugin->show_success_message();
    }

    /**
     * Test cancelled message display
     */
    public function test_show_cancelled_message() {
        $this->expectOutputString('<div class="wpd-error-message">Donation was cancelled.</div>');
        
        $this->plugin->show_cancelled_message();
    }

    /**
     * Test HTTP error during IPN verification
     */
    public function test_handle_ipn_http_error() {
        $_POST = $this->createMockIPNRequest();
        $_GET['wpd_ipn'] = '1';

        // Mock HTTP error
        Monkey\Functions\when('wp_remote_post')
            ->justReturn(new \WP_Error('http_request_failed', 'Connection failed'));

        Monkey\Functions\when('is_wp_error')
            ->justReturn(true);

        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                if ($option === 'wpd_sandbox_mode') {
                    return true;
                }
                return $default;
            });

        // Database should not be updated on HTTP error
        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->never())
             ->method('update');

        global $wpdb;

        Monkey\Functions\when('exit')->justReturn(null);
        
        $this->plugin->handle_paypal_return();
    }
}