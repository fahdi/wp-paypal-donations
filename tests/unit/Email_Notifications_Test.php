<?php
/**
 * Email notification tests
 *
 * @package WP_PayPal_Donations
 */

namespace WPPayPalDonations\Tests\Unit;

use WPPayPalDonations\Tests\TestCase;
use Brain\Monkey;

/**
 * Test email notification functionality
 */
class Email_Notifications_Test extends TestCase {

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
    }

    /**
     * Test sending donation notifications
     */
    public function test_send_donation_notifications() {
        $donation = $this->createMockDonation();
        
        // Mock database query
        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->once())
             ->method('get_row')
             ->willReturn($donation);
        
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_donations WHERE id = 1");
        
        global $wpdb;
        
        // Create partial mock to test individual email methods
        $plugin_mock = $this->getMockBuilder(\WP_PayPal_Donations::class)
                            ->onlyMethods(['send_donor_thank_you_email', 'send_admin_notification_email'])
                            ->getMock();
        
        $plugin_mock->expects($this->once())
                    ->method('send_donor_thank_you_email')
                    ->with($donation);
        
        $plugin_mock->expects($this->once())
                    ->method('send_admin_notification_email')
                    ->with($donation);
        
        $plugin_mock->send_donation_notifications(1);
    }

    /**
     * Test donor thank you email
     */
    public function test_send_donor_thank_you_email() {
        $donation = $this->createMockDonation();
        
        Monkey\Functions\when('get_bloginfo')
            ->justReturn('Test Website');
        
        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                if ($option === 'admin_email') {
                    return 'admin@example.com';
                }
                return $default;
            });
        
        Monkey\Functions\when('date_i18n')
            ->justReturn('January 1, 2024');
        
        Monkey\Functions\expect('wp_mail')
            ->once()
            ->with(
                $donation->donor_email,
                Monkey\Functions\type('string'), // subject
                Monkey\Functions\type('string'), // message
                Monkey\Functions\type('array')   // headers
            )
            ->andReturn(true);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('send_donor_thank_you_email');
        $method->setAccessible(true);
        
        $method->invoke($this->plugin, $donation);
    }

    /**
     * Test admin notification email
     */
    public function test_send_admin_notification_email() {
        $donation = $this->createMockDonation();
        
        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                if ($option === 'admin_email') {
                    return 'admin@example.com';
                }
                return $default;
            });
        
        Monkey\Functions\when('get_bloginfo')
            ->justReturn('Test Website');
        
        Monkey\Functions\when('date_i18n')
            ->justReturn('January 1, 2024 12:00 PM');
        
        Monkey\Functions\expect('wp_mail')
            ->once()
            ->with(
                'admin@example.com',
                Monkey\Functions\type('string'), // subject
                Monkey\Functions\type('string'), // message
                Monkey\Functions\type('array')   // headers
            )
            ->andReturn(true);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('send_admin_notification_email');
        $method->setAccessible(true);
        
        $method->invoke($this->plugin, $donation);
    }

    /**
     * Test email sending failure
     */
    public function test_email_sending_failure() {
        $donation = $this->createMockDonation();
        
        Monkey\Functions\when('get_bloginfo')
            ->justReturn('Test Website');
        
        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                if ($option === 'admin_email') {
                    return 'admin@example.com';
                }
                return $default;
            });
        
        Monkey\Functions\when('date_i18n')
            ->justReturn('January 1, 2024');
        
        // Mock wp_mail to return false (failure)
        Monkey\Functions\expect('wp_mail')
            ->once()
            ->andReturn(false);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('send_donor_thank_you_email');
        $method->setAccessible(true);
        
        // Should not throw an exception even if email fails
        $method->invoke($this->plugin, $donation);
        
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    /**
     * Test email template formatting
     */
    public function test_email_template_formatting() {
        $donation = $this->createMockDonation(array(
            'donor_name' => 'Jane Smith',
            'amount' => 50.00,
            'currency' => 'EUR',
            'transaction_id' => 'EUR123'
        ));
        
        Monkey\Functions\when('get_bloginfo')
            ->justReturn('Test Charity');
        
        Monkey\Functions\when('get_option')
            ->alias(function($option, $default = false) {
                if ($option === 'admin_email') {
                    return 'admin@testcharity.org';
                }
                return $default;
            });
        
        Monkey\Functions\when('date_i18n')
            ->justReturn('February 15, 2024');
        
        // Capture the email content
        $email_subject = '';
        $email_message = '';
        
        Monkey\Functions\expect('wp_mail')
            ->once()
            ->with(
                $donation->donor_email,
                Monkey\Functions\anyOf(Monkey\Functions\type('string')),
                Monkey\Functions\anyOf(Monkey\Functions\type('string')),
                Monkey\Functions\type('array')
            )
            ->andReturnUsing(function($to, $subject, $message, $headers) use (&$email_subject, &$email_message) {
                $email_subject = $subject;
                $email_message = $message;
                return true;
            });
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('send_donor_thank_you_email');
        $method->setAccessible(true);
        
        $method->invoke($this->plugin, $donation);
        
        // Verify email contains expected content
        $this->assertStringContainsString('Jane Smith', $email_message);
        $this->assertStringContainsString('€50.00', $email_message);
        $this->assertStringContainsString('EUR123', $email_message);
        $this->assertStringContainsString('Test Charity', $email_subject);
    }

    /**
     * Test handling donation with missing data
     */
    public function test_send_notifications_missing_donation() {
        // Mock database to return null
        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->once())
             ->method('get_row')
             ->willReturn(null);
        
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_donations WHERE id = 999");
        
        global $wpdb;
        
        // Should handle missing donation gracefully
        $this->plugin->send_donation_notifications(999);
        
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    /**
     * Test currency symbol in emails
     */
    public function test_currency_symbols_in_emails() {
        $currencies = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥'
        );
        
        foreach ($currencies as $currency => $symbol) {
            $donation = $this->createMockDonation(array(
                'amount' => 25.00,
                'currency' => $currency
            ));
            
            Monkey\Functions\when('get_bloginfo')
                ->justReturn('Test Site');
            
            Monkey\Functions\when('get_option')
                ->alias(function($option, $default = false) {
                    if ($option === 'admin_email') {
                        return 'admin@example.com';
                    }
                    return $default;
                });
            
            Monkey\Functions\when('date_i18n')
                ->justReturn('January 1, 2024');
            
            $email_message = '';
            
            Monkey\Functions\expect('wp_mail')
                ->once()
                ->andReturnUsing(function($to, $subject, $message, $headers) use (&$email_message) {
                    $email_message = $message;
                    return true;
                });
            
            // Use reflection to test private method
            $reflection = new \ReflectionClass($this->plugin);
            $method = $reflection->getMethod('send_donor_thank_you_email');
            $method->setAccessible(true);
            
            $method->invoke($this->plugin, $donation);
            
            // Verify correct currency symbol is used
            $this->assertStringContainsString($symbol . '25.00', $email_message);
        }
    }
}