<?php
/**
 * Base test case for WP PayPal Donations plugin
 *
 * @package WP_PayPal_Donations
 */

namespace WPPayPalDonations\Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Brain\Monkey;

/**
 * Base test case class
 */
abstract class TestCase extends PHPUnitTestCase {

    /**
     * Setup before each test
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Initialize Brain Monkey
        Monkey\setUp();
        
        // Mock WordPress functions
        $this->mockWordPressFunctions();
        
        // Setup test data
        $this->setupTestData();
    }

    /**
     * Cleanup after each test
     */
    protected function tearDown(): void {
        parent::tearDown();
        
        // Clean up Brain Monkey
        Monkey\tearDown();
    }

    /**
     * Mock essential WordPress functions
     */
    protected function mockWordPressFunctions() {
        // Mock database functions
        Monkey\Functions\when('get_option')->returnArg(1);
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('delete_option')->justReturn(true);
        
        // Mock WordPress core functions
        Monkey\Functions\when('wp_create_nonce')->justReturn('test_nonce');
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\when('current_user_can')->justReturn(true);
        Monkey\Functions\when('is_admin')->justReturn(false);
        Monkey\Functions\when('admin_url')->returnArg();
        Monkey\Functions\when('plugin_dir_url')->returnArg();
        Monkey\Functions\when('plugin_dir_path')->returnArg();
        
        // Mock text functions
        Monkey\Functions\when('__')->returnArg();
        Monkey\Functions\when('_e')->returnArg();
        Monkey\Functions\when('esc_html')->returnArg();
        Monkey\Functions\when('esc_attr')->returnArg();
        Monkey\Functions\when('sanitize_text_field')->returnArg();
        Monkey\Functions\when('sanitize_email')->returnArg();
        
        // Mock HTTP functions
        Monkey\Functions\when('wp_remote_post')->justReturn(array(
            'response' => array('code' => 200),
            'body' => 'VERIFIED'
        ));
        
        // Mock email functions
        Monkey\Functions\when('wp_mail')->justReturn(true);
        
        // Mock blog info
        Monkey\Functions\when('get_bloginfo')->justReturn('Test Blog');
    }

    /**
     * Setup test data
     */
    protected function setupTestData() {
        // Override in child classes as needed
    }

    /**
     * Assert that a hook was added
     */
    protected function assertHookAdded($hook, $callback, $priority = 10) {
        $this->assertTrue(
            Monkey\Actions\has($hook) || Monkey\Filters\has($hook),
            "Hook '{$hook}' was not added"
        );
    }

    /**
     * Create a mock donation record
     */
    protected function createMockDonation($data = array()) {
        $defaults = array(
            'id' => 1,
            'donor_name' => 'John Doe',
            'donor_email' => 'john@example.com',
            'amount' => 25.00,
            'currency' => 'USD',
            'payment_status' => 'completed',
            'transaction_id' => 'TEST123',
            'donation_date' => '2024-01-01 12:00:00',
            'paypal_response' => serialize(array('test' => 'data'))
        );

        return (object) array_merge($defaults, $data);
    }

    /**
     * Create a mock PayPal IPN request
     */
    protected function createMockIPNRequest($data = array()) {
        $defaults = array(
            'payment_status' => 'Completed',
            'txn_id' => 'TEST123',
            'custom' => '1',
            'mc_gross' => '25.00',
            'mc_currency' => 'USD',
            'payer_email' => 'buyer@example.com'
        );

        return array_merge($defaults, $data);
    }

    /**
     * Mock WordPress global $wpdb
     */
    protected function mockWpdb() {
        global $wpdb;
        
        $wpdb = $this->createMock(\wpdb::class);
        $wpdb->prefix = 'wp_';
        
        return $wpdb;
    }
}