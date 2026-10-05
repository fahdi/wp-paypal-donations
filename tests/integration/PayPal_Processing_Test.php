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
            ->with(\Mockery::type('array'));

        $this->plugin->process_donation();
    }

    /**
     * Test donation processing with missing required fields
     */
    public function test_process_donation_missing_fields() {
        // Missing donor name
        $_POST = array(
            'amount' => '25.00',
            'donor_email' => 'john@example.com',
            'nonce' => 'test_nonce'
        );

        Monkey\Functions\when('wp_privacy_anonymize_data')->justReturn('***');
        Monkey\Functions\when('wp_strip_all_tags')->returnArg();
        Monkey\Functions\when('is_email')->alias(function ($email) {
            return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
        });

        Monkey\Functions\expect('wp_send_json_error')
            ->once()
            ->with(\Mockery::type('array'))
            ->andReturnUsing(function () {
                throw new \RuntimeException('json_error');
            });
        Monkey\Functions\expect('wp_send_json_success')->never();

        $this->expectException(\RuntimeException::class);
        $this->plugin->process_donation();
    }

    /**
     * Build the raw body PayPal would post for an IPN array.
     */
    private function ipnBody(array $ipn) {
        return http_build_query($ipn);
    }

    /**
     * Mock the sandbox-mode option used to pick the PayPal host.
     */
    private function mockSandboxOption() {
        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                if ($option === 'wpd_sandbox_mode') {
                    return true;
                }
                return $default;
            });
    }

    /**
     * Test IPN handling with valid PayPal response
     */
    public function test_handle_ipn_valid_response() {
        $ipn = $this->createMockIPNRequest();
        $this->mockSandboxOption();

        Monkey\Functions\when('wp_remote_post')
            ->justReturn(array('body' => 'VERIFIED', 'response' => array('code' => 200)));

        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->once())
             ->method('update')
             ->with(
                 'wp_donations',
                 $this->callback(function ($data) {
                     return $data['payment_status'] === 'completed' && $data['transaction_id'] === 'TEST123';
                 }),
                 array('id' => 1)
             )
             ->willReturn(1);
        // send_donation_notifications() loads the row; return nothing so no mail is sent.
        $wpdb->method('get_row')->willReturn(null);

        $this->assertTrue($this->plugin->process_ipn($this->ipnBody($ipn), $ipn));
    }

    /**
     * Test IPN handling with invalid PayPal response
     */
    public function test_handle_ipn_invalid_response() {
        $ipn = $this->createMockIPNRequest();
        $this->mockSandboxOption();

        Monkey\Functions\when('wp_remote_post')
            ->justReturn(array('body' => 'INVALID', 'response' => array('code' => 200)));

        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->never())->method('update');

        $this->assertFalse($this->plugin->process_ipn($this->ipnBody($ipn), $ipn));
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
            ->alias(function($args, $url = '', $extra = null) {
                // add_query_arg('key', 'value', $url) form
                if (!is_array($args)) {
                    return $extra . '?' . $args . '=' . $url;
                }
                // Check if recurring parameters are included
                if (isset($args['cmd'])) {
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
            ->with(\Mockery::type('array'));

        $this->plugin->process_donation();
    }

    /**
     * Test PayPal success return handling
     */
    public function test_handle_paypal_success_return() {
        $_GET['donation_success'] = '1';

        $this->plugin->handle_paypal_return();

        $this->assertNotFalse(has_action('wp_footer', array($this->plugin, 'show_success_message')));
    }

    /**
     * Test PayPal cancelled return handling
     */
    public function test_handle_paypal_cancelled_return() {
        $_GET['donation_cancelled'] = '1';

        $this->plugin->handle_paypal_return();

        $this->assertNotFalse(has_action('wp_footer', array($this->plugin, 'show_cancelled_message')));
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
        $ipn = $this->createMockIPNRequest();
        $this->mockSandboxOption();

        Monkey\Functions\when('wp_remote_post')
            ->justReturn(new \WP_Error('http_request_failed', 'Connection failed'));

        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->never())->method('update');

        $this->assertFalse($this->plugin->process_ipn($this->ipnBody($ipn), $ipn));
    }
}
