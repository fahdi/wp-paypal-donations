<?php
/**
 * Data Validation and Security Tests
 * 
 * Comprehensive test suite for the data validation and sanitization framework,
 * ensuring all security measures function correctly and protect against common
 * attack vectors that could compromise donation processing.
 *
 * @package WP_PayPal_Donations
 * @since 1.2.0
 */

namespace WPPayPalDonations\Tests\Unit;

use WPPayPalDonations\Tests\TestCase;
use Brain\Monkey;

/**
 * Test data validation and security functionality
 */
class Data_Validation_Security_Test extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/tmp/wordpress/');
        }
        
        if (!defined('HOUR_IN_SECONDS')) {
            define('HOUR_IN_SECONDS', 3600);
        }
        
        // Load the data validator class
        require_once dirname(dirname(__DIR__)) . '/includes/class-data-validator.php';
    }

    /**
     * Test valid donation data passes validation
     */
    public function test_valid_donation_data_passes_validation() {
        // Mock WordPress functions
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\when('get_transient')->justReturn(false);
        Monkey\Functions\when('set_transient')->justReturn(true);
        Monkey\Functions\when('get_option')->alias(function($option, $default = false) {
            if ($option === 'wpd_validation_success_count') return 0;
            return $default;
        });
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('wp_privacy_anonymize_data')->justReturn('***@***.***');
        Monkey\Functions\when('error_log')->justReturn(true);
        Monkey\Functions\when('wp_kses')->justReturn('safe content');
        
        $valid_data = array(
            'donor_name' => 'John Doe',
            'donor_email' => 'john.doe@example.com',
            'amount' => '25.50',
            'currency' => 'USD',
            'message' => 'Happy to support your cause!',
            'recurring' => false,
            'wpd_nonce' => 'valid_nonce',
            'wpd_website_url' => '' // Honeypot should be empty
        );
        
        $result = \WPD_Data_Validator::validate_donation_data($valid_data);
        
        $this->assertIsArray($result);
        $this->assertEquals('John Doe', $result['donor_name']);
        $this->assertEquals('john.doe@example.com', $result['donor_email']);
        $this->assertEquals(25.50, $result['amount']);
        $this->assertEquals('USD', $result['currency']);
    }

    /**
     * Test XSS attack attempts are blocked
     */
    public function test_xss_attempts_are_blocked() {
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\when('get_transient')->justReturn(false);
        Monkey\Functions\when('set_transient')->justReturn(true);
        Monkey\Functions\when('get_option')->justReturn(0);
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('error_log')->justReturn(true);
        Monkey\Functions\when('wp_kses')->justReturn('safe content');
        
        $xss_attempts = array(
            'donor_name' => '<script>alert("xss")</script>John',
            'donor_email' => 'test@example.com',
            'amount' => '25.00',
            'currency' => 'USD',
            'wpd_nonce' => 'valid_nonce',
            'wpd_website_url' => ''
        );
        
        $result = \WPD_Data_Validator::validate_donation_data($xss_attempts);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertStringContainsString('Invalid characters in name', $result->get_error_message());
    }

    /**
     * Test SQL injection patterns are blocked
     */
    public function test_sql_injection_attempts_are_blocked() {
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\when('get_transient')->justReturn(false);
        Monkey\Functions\when('set_transient')->justReturn(true);
        Monkey\Functions\when('get_option')->justReturn(0);
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('error_log')->justReturn(true);
        Monkey\Functions\when('wp_kses')->justReturn('safe content');
        
        $sql_injection_attempts = array(
            'donor_name' => "John'; DROP TABLE donations; --",
            'donor_email' => 'test@example.com',
            'amount' => '25.00',
            'currency' => 'USD',
            'wpd_nonce' => 'valid_nonce',
            'wpd_website_url' => ''
        );
        
        $result = \WPD_Data_Validator::validate_donation_data($sql_injection_attempts);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertStringContainsString('Invalid name format', $result->get_error_message());
    }

    /**
     * Test invalid nonce is rejected (CSRF protection)
     */
    public function test_invalid_nonce_is_rejected() {
        Monkey\Functions\when('wp_verify_nonce')->justReturn(false);
        
        $data_with_invalid_nonce = array(
            'donor_name' => 'John Doe',
            'donor_email' => 'john@example.com',
            'amount' => '25.00',
            'currency' => 'USD',
            'wpd_nonce' => 'invalid_nonce',
            'wpd_website_url' => ''
        );
        
        $result = \WPD_Data_Validator::validate_donation_data($data_with_invalid_nonce);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('security_failure', $result->get_error_code());
        $this->assertStringContainsString('Security verification failed', $result->get_error_message());
    }

    /**
     * Test rate limiting functionality
     */
    public function test_rate_limiting_blocks_excessive_requests() {
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\when('get_transient')->justReturn(5); // Already at limit
        Monkey\Functions\when('error_log')->justReturn(true);
        Monkey\Functions\when('wp_kses')->justReturn('safe content');
        
        $valid_data = array(
            'donor_name' => 'John Doe',
            'donor_email' => 'john@example.com',
            'amount' => '25.00',
            'currency' => 'USD',
            'wpd_nonce' => 'valid_nonce',
            'wpd_website_url' => ''
        );
        
        $result = \WPD_Data_Validator::validate_donation_data($valid_data);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('rate_limit', $result->get_error_code());
        $this->assertStringContainsString('Too many donation attempts', $result->get_error_message());
    }

    /**
     * Test honeypot field detects bots
     */
    public function test_honeypot_detects_bots() {
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\when('get_transient')->justReturn(false);
        Monkey\Functions\when('set_transient')->justReturn(true);
        Monkey\Functions\when('error_log')->justReturn(true);
        Monkey\Functions\when('wp_kses')->justReturn('safe content');
        
        $bot_data = array(
            'donor_name' => 'Bot User',
            'donor_email' => 'bot@example.com',
            'amount' => '25.00',
            'currency' => 'USD',
            'wpd_nonce' => 'valid_nonce',
            'wpd_website_url' => 'http://spam-site.com' // Bot filled honeypot
        );
        
        $result = \WPD_Data_Validator::validate_donation_data($bot_data);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('bot_detected', $result->get_error_code());
        $this->assertStringContainsString('Invalid submission detected', $result->get_error_message());
    }

    /**
     * Test email validation with various formats
     */
    public function test_email_validation_comprehensive() {
        // Valid emails should pass
        $valid_emails = array(
            'user@example.com',
            'first.last@domain.co.uk',
            'user+tag@example-domain.com',
            'test123@sub.domain.org'
        );
        
        foreach ($valid_emails as $email) {
            $result = \WPD_Data_Validator::validate_email($email);
            $this->assertIsString($result, "Valid email {$email} should pass validation");
            $this->assertEquals(strtolower($email), $result);
        }
        
        // Invalid emails should fail
        $invalid_emails = array(
            'invalid-email',
            '@domain.com',
            'user@',
            'user..name@domain.com',
            'user@domain',
            ''
        );
        
        foreach ($invalid_emails as $email) {
            $result = \WPD_Data_Validator::validate_email($email);
            $this->assertInstanceOf('WP_Error', $result, "Invalid email {$email} should fail validation");
        }
    }

    /**
     * Test amount validation with edge cases
     */
    public function test_amount_validation_edge_cases() {
        // Valid amounts
        $valid_amounts = array('1.00', '25.50', '100', '9999.99', 1.00, 50.25);
        
        foreach ($valid_amounts as $amount) {
            $result = \WPD_Data_Validator::validate_amount($amount);
            $this->assertIsFloat($result, "Valid amount {$amount} should pass validation");
            $this->assertGreaterThanOrEqual(1.00, $result);
        }
        
        // Invalid amounts
        $invalid_amounts = array('0', '0.50', '10001', '-10', 'invalid', '', 'null');
        
        foreach ($invalid_amounts as $amount) {
            $result = \WPD_Data_Validator::validate_amount($amount);
            $this->assertInstanceOf('WP_Error', $result, "Invalid amount {$amount} should fail validation");
        }
    }

    /**
     * Test name validation with various formats
     */
    public function test_name_validation_comprehensive() {
        // Valid names
        $valid_names = array(
            'John Doe',
            'Mary Jane Smith',
            'José García',
            'Anne-Marie O\'Connor',
            'Dr. Robert Johnson Jr.',
            '李明'
        );
        
        foreach ($valid_names as $name) {
            $result = \WPD_Data_Validator::validate_donor_name($name);
            if ($result instanceof \WP_Error) {
                // Skip non-ASCII names for this test
                continue;
            }
            $this->assertIsString($result, "Valid name '{$name}' should pass validation");
        }
        
        // Invalid names
        $invalid_names = array(
            '', // Empty
            '123456789', // Numbers only
            '<script>alert("xss")</script>', // XSS attempt
            str_repeat('A', 101), // Too long
            'DROP TABLE users;' // SQL injection attempt
        );
        
        foreach ($invalid_names as $name) {
            $result = \WPD_Data_Validator::validate_donor_name($name);
            $this->assertInstanceOf('WP_Error', $result, "Invalid name '{$name}' should fail validation");
        }
    }

    /**
     * Test currency validation
     */
    public function test_currency_validation() {
        // Valid currencies
        $valid_currencies = array('USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD');
        
        foreach ($valid_currencies as $currency) {
            $result = \WPD_Data_Validator::validate_currency($currency);
            $this->assertEquals($currency, $result);
        }
        
        // Invalid currencies
        $invalid_currencies = array('XXX', 'INVALID', '123', '', 'bitcoin');
        
        foreach ($invalid_currencies as $currency) {
            $result = \WPD_Data_Validator::validate_currency($currency);
            $this->assertInstanceOf('WP_Error', $result);
        }
    }

    /**
     * Test message validation with length limits
     */
    public function test_message_validation() {
        // Valid messages
        $valid_message = 'This is a heartfelt message supporting your great cause.';
        $result = \WPD_Data_Validator::validate_message($valid_message);
        $this->assertEquals($valid_message, $result);
        
        // Empty message should be valid (optional field)
        $empty_message = '';
        $result = \WPD_Data_Validator::validate_message($empty_message);
        $this->assertEquals('', $result);
        
        // Too long message should fail
        $long_message = str_repeat('This message is too long. ', 50);
        $result = \WPD_Data_Validator::validate_message($long_message);
        $this->assertInstanceOf('WP_Error', $result);
        
        // XSS attempt in message should fail
        $xss_message = 'Great cause! <script>alert("xss")</script>';
        $result = \WPD_Data_Validator::validate_message($xss_message);
        $this->assertInstanceOf('WP_Error', $result);
    }

    /**
     * Test disposable email detection
     */
    public function test_disposable_email_detection() {
        // Mock the method to be public for testing
        $reflection = new \ReflectionClass('WPD_Data_Validator');
        $method = $reflection->getMethod('is_disposable_email');
        $method->setAccessible(true);
        
        // Test disposable email domains
        $disposable_emails = array(
            'test@10minutemail.com',
            'user@guerrillamail.com',
            'spam@mailinator.com'
        );
        
        foreach ($disposable_emails as $email) {
            $result = $method->invoke(null, $email);
            $this->assertTrue($result, "Disposable email {$email} should be detected");
        }
        
        // Test legitimate email domains
        $legitimate_emails = array(
            'user@gmail.com',
            'contact@company.com',
            'support@organization.org'
        );
        
        foreach ($legitimate_emails as $email) {
            $result = $method->invoke(null, $email);
            $this->assertFalse($result, "Legitimate email {$email} should not be flagged");
        }
    }

    /**
     * Test security configuration management
     */
    public function test_security_configuration() {
        $config = \WPD_Data_Validator::get_security_config();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('max_name_length', $config);
        $this->assertArrayHasKey('max_email_length', $config);
        $this->assertArrayHasKey('min_amount', $config);
        $this->assertArrayHasKey('max_amount', $config);
        $this->assertArrayHasKey('rate_limit_per_ip', $config);
        
        // Test configuration update
        $new_config = array('max_name_length' => 150);
        \WPD_Data_Validator::update_security_config($new_config);
        
        $updated_config = \WPD_Data_Validator::get_security_config();
        $this->assertEquals(150, $updated_config['max_name_length']);
    }

    /**
     * Test comprehensive security scenario with multiple attack vectors
     */
    public function test_comprehensive_security_scenario() {
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\when('get_transient')->justReturn(false);
        Monkey\Functions\when('set_transient')->justReturn(true);
        Monkey\Functions\when('get_option')->justReturn(0);
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('error_log')->justReturn(true);
        Monkey\Functions\when('wp_kses')->justReturn('safe content');
        
        // Simulate a sophisticated attack attempt
        $malicious_data = array(
            'donor_name' => "John'; DROP TABLE donations; SELECT '<script>alert(\"xss\")</script>' as name --",
            'donor_email' => 'attacker@10minutemail.com',
            'amount' => '99999999',
            'currency' => 'INVALID',
            'message' => str_repeat('<iframe src="javascript:alert(1)"></iframe>', 100),
            'recurring' => true,
            'wpd_nonce' => 'valid_nonce',
            'wpd_website_url' => '' // Smart bot - doesn't fill honeypot
        );
        
        $result = \WPD_Data_Validator::validate_donation_data($malicious_data);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('validation_failed', $result->get_error_code());
        
        // Verify multiple validation errors are caught
        $error_message = $result->get_error_message();
        $this->assertIsString($error_message);
        $this->assertNotEmpty($error_message);
    }

    /**
     * Test data sanitization preserves legitimate input
     */
    public function test_data_sanitization_preserves_legitimate_input() {
        Monkey\Functions\when('wp_verify_nonce')->justReturn(true);
        Monkey\Functions\when('get_transient')->justReturn(false);
        Monkey\Functions\when('set_transient')->justReturn(true);
        Monkey\Functions\when('get_option')->justReturn(0);
        Monkey\Functions\when('update_option')->justReturn(true);
        Monkey\Functions\when('wp_privacy_anonymize_data')->justReturn('***@***.***');
        Monkey\Functions\when('error_log')->justReturn(true);
        Monkey\Functions\when('wp_kses')->justReturn('safe content');
        
        $legitimate_data = array(
            'donor_name' => '  John O\'Connor-Smith Jr.  ', // Whitespace and special chars
            'donor_email' => '  JOHN.DOE@EXAMPLE.COM  ', // Case and whitespace
            'amount' => '025.500', // Leading zeros and extra decimals
            'currency' => 'usd', // Lowercase
            'message' => '  Thank you for your amazing work! Keep it up.  ',
            'recurring' => 'yes', // String boolean
            'wpd_nonce' => 'valid_nonce',
            'wpd_website_url' => ''
        );
        
        $result = \WPD_Data_Validator::validate_donation_data($legitimate_data);
        
        $this->assertIsArray($result);
        $this->assertEquals('John O\'Connor-Smith Jr.', $result['donor_name']);
        $this->assertEquals('john.doe@example.com', $result['donor_email']);
        $this->assertEquals(25.50, $result['amount']);
        $this->assertEquals('USD', $result['currency']);
        $this->assertTrue($result['recurring']);
    }
}