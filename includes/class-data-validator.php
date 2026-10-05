<?php
/**
 * Data Validation and Sanitization Framework
 * 
 * Provides comprehensive security controls for donor data handling,
 * addressing critical business risks around data security, compliance,
 * and customer trust in donation processing.
 * 
 * Business Impact:
 * - Prevents XSS/SQL injection attacks that could compromise entire organization
 * - Ensures GDPR/privacy compliance to avoid $20M+ regulatory fines
 * - Maintains data quality for reliable email delivery and reporting
 * - Builds customer trust through secure donation processing
 *
 * @package WP_PayPal_Donations
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comprehensive data validation and sanitization framework
 */
class WPD_Data_Validator {
    
    /**
     * Validation error messages
     */
    private static $errors = array();
    
    /**
     * Security configuration
     */
    private static $security_config = array(
        'max_name_length' => 100,
        'max_email_length' => 254, // RFC 5321 standard
        'min_amount' => 1.00,
        'max_amount' => 10000.00,
        'allowed_currencies' => array('USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD'),
        'rate_limit_per_ip' => 5, // Max donations per IP per hour
        'honeypot_field' => 'wpd_website_url' // Bot detection
    );
    
    /**
     * Validate and sanitize donation form data
     * 
     * @param array $data Raw form data
     * @return array|WP_Error Sanitized data or error object
     */
    public static function validate_donation_data($data) {
        self::$errors = array();
        
        // Security: Verify nonce first
        if (!self::verify_donation_nonce($data)) {
            return new WP_Error('security_failure', __('Security verification failed. Please try again.', 'wp-paypal-donations'));
        }
        
        // Security: Check rate limiting
        if (!self::check_rate_limit()) {
            return new WP_Error('rate_limit', __('Too many donation attempts. Please wait before trying again.', 'wp-paypal-donations'));
        }
        
        // Security: Honeypot bot detection
        if (!self::check_honeypot($data)) {
            return new WP_Error('bot_detected', __('Invalid submission detected.', 'wp-paypal-donations'));
        }
        
        // Validate and sanitize each field
        $validated_data = array();
        
        // Donor name validation
        $validated_data['donor_name'] = self::validate_donor_name($data['donor_name'] ?? '');
        if (is_wp_error($validated_data['donor_name'])) {
            self::$errors[] = $validated_data['donor_name']->get_error_message();
        }
        
        // Email validation (critical for notifications)
        $validated_data['donor_email'] = self::validate_email($data['donor_email'] ?? '');
        if (is_wp_error($validated_data['donor_email'])) {
            self::$errors[] = $validated_data['donor_email']->get_error_message();
        }
        
        // Amount validation (critical for payment processing)
        $validated_data['amount'] = self::validate_amount($data['amount'] ?? '');
        if (is_wp_error($validated_data['amount'])) {
            self::$errors[] = $validated_data['amount']->get_error_message();
        }
        
        // Currency validation
        $validated_data['currency'] = self::validate_currency($data['currency'] ?? '');
        if (is_wp_error($validated_data['currency'])) {
            self::$errors[] = $validated_data['currency']->get_error_message();
        }
        
        // Optional message validation
        if (!empty($data['message'])) {
            $validated_data['message'] = self::validate_message($data['message']);
            if (is_wp_error($validated_data['message'])) {
                self::$errors[] = $validated_data['message']->get_error_message();
            }
        }
        
        // Recurring donation validation
        $validated_data['recurring'] = self::validate_boolean($data['recurring'] ?? false);
        
        // Return errors if any validation failed
        if (!empty(self::$errors)) {
            return new WP_Error('validation_failed', implode(' ', self::$errors));
        }
        
        // Log successful validation for security monitoring
        self::log_validation_success($validated_data);
        
        return $validated_data;
    }
    
    /**
     * Validate donor name with comprehensive security checks
     * 
     * @param string $name Raw name input
     * @return string|WP_Error Sanitized name or error
     */
    public static function validate_donor_name($name) {
        if (empty($name)) {
            return new WP_Error('name_required', __('Donor name is required.', 'wp-paypal-donations'));
        }
        
        // Security: Check for potential XSS patterns
        if (self::contains_xss_patterns($name)) {
            self::log_security_violation('XSS attempt in donor name', $name);
            return new WP_Error('name_invalid', __('Invalid characters in name.', 'wp-paypal-donations'));
        }
        
        // Sanitize: Remove HTML tags and scripts
        $sanitized = wp_strip_all_tags($name);
        $sanitized = sanitize_text_field($sanitized);
        
        // Validation: Length check
        if (strlen($sanitized) > self::$security_config['max_name_length']) {
            return new WP_Error('name_too_long', 
                sprintf(__('Name must be less than %d characters.', 'wp-paypal-donations'), 
                self::$security_config['max_name_length']));
        }
        
        // Validation: Must contain at least one letter
        if (!preg_match('/[a-zA-Z]/', $sanitized)) {
            return new WP_Error('name_invalid', __('Name must contain at least one letter.', 'wp-paypal-donations'));
        }
        
        // Security: Block suspicious patterns
        if (self::contains_suspicious_patterns($sanitized)) {
            self::log_security_violation('Suspicious name pattern', $sanitized);
            return new WP_Error('name_invalid', __('Invalid name format.', 'wp-paypal-donations'));
        }
        
        return trim($sanitized);
    }
    
    /**
     * Validate email with enhanced security and deliverability checks
     * 
     * @param string $email Raw email input
     * @return string|WP_Error Sanitized email or error
     */
    public static function validate_email($email) {
        if (empty($email)) {
            return new WP_Error('email_required', __('Email address is required.', 'wp-paypal-donations'));
        }
        
        // Security: Length check to prevent buffer overflow
        if (strlen($email) > self::$security_config['max_email_length']) {
            return new WP_Error('email_too_long', __('Email address is too long.', 'wp-paypal-donations'));
        }
        
        // Sanitize: WordPress built-in email sanitization
        $sanitized_email = sanitize_email($email);
        
        // Validation: WordPress email validation (RFC compliant)
        if (!is_email($sanitized_email)) {
            return new WP_Error('email_invalid', __('Please enter a valid email address.', 'wp-paypal-donations'));
        }
        
        // Security: Check for malicious patterns in email
        if (self::contains_xss_patterns($sanitized_email)) {
            self::log_security_violation('XSS attempt in email', $email);
            return new WP_Error('email_invalid', __('Invalid email format.', 'wp-paypal-donations'));
        }
        
        // Business Logic: Check against disposable email providers
        if (self::is_disposable_email($sanitized_email)) {
            return new WP_Error('email_disposable', 
                __('Please use a permanent email address for donation receipts.', 'wp-paypal-donations'));
        }
        
        // Business Logic: Domain validation for deliverability
        if (!self::validate_email_domain($sanitized_email)) {
            return new WP_Error('email_domain_invalid', 
                __('Email domain appears to be invalid. Please check your email address.', 'wp-paypal-donations'));
        }
        
        return strtolower(trim($sanitized_email));
    }
    
    /**
     * Validate donation amount with business rules and security checks
     * 
     * @param string|float $amount Raw amount input
     * @return float|WP_Error Validated amount or error
     */
    public static function validate_amount($amount) {
        if (empty($amount) && $amount !== '0') {
            return new WP_Error('amount_required', __('Donation amount is required.', 'wp-paypal-donations'));
        }
        
        // Security: Check for injection patterns in numeric input
        if (!is_numeric($amount) || self::contains_suspicious_patterns((string)$amount)) {
            self::log_security_violation('Invalid amount format', $amount);
            return new WP_Error('amount_invalid', __('Please enter a valid donation amount.', 'wp-paypal-donations'));
        }
        
        // Convert to float for validation
        $float_amount = floatval($amount);
        
        // Business Rule: Minimum amount
        if ($float_amount < self::$security_config['min_amount']) {
            return new WP_Error('amount_too_low', 
                sprintf(__('Minimum donation amount is %s%.2f', 'wp-paypal-donations'),
                get_option('wpd_currency', 'USD'), self::$security_config['min_amount']));
        }
        
        // Business Rule: Maximum amount (anti-fraud)
        if ($float_amount > self::$security_config['max_amount']) {
            return new WP_Error('amount_too_high', 
                sprintf(__('Maximum donation amount is %s%.2f. Please contact us for larger donations.', 'wp-paypal-donations'),
                get_option('wpd_currency', 'USD'), self::$security_config['max_amount']));
        }
        
        // Format to 2 decimal places for PayPal compatibility
        return round($float_amount, 2);
    }
    
    /**
     * Validate currency code
     * 
     * @param string $currency Raw currency input
     * @return string|WP_Error Valid currency code or error
     */
    public static function validate_currency($currency) {
        if (empty($currency)) {
            $currency = get_option('wpd_currency', 'USD');
        }
        
        $currency = strtoupper(sanitize_text_field($currency));
        
        if (!in_array($currency, self::$security_config['allowed_currencies'])) {
            return new WP_Error('currency_invalid', 
                sprintf(__('Currency %s is not supported.', 'wp-paypal-donations'), $currency));
        }
        
        return $currency;
    }
    
    /**
     * Validate optional message field
     * 
     * @param string $message Raw message input
     * @return string|WP_Error Sanitized message or error
     */
    public static function validate_message($message) {
        // Security: Check for XSS and injection attempts
        if (self::contains_xss_patterns($message)) {
            self::log_security_violation('XSS attempt in message', $message);
            return new WP_Error('message_invalid', __('Message contains invalid characters.', 'wp-paypal-donations'));
        }
        
        // Sanitize: WordPress sanitization for textarea
        $sanitized = sanitize_textarea_field($message);
        
        // Business Rule: Length limit
        if (strlen($sanitized) > 500) {
            return new WP_Error('message_too_long', __('Message must be less than 500 characters.', 'wp-paypal-donations'));
        }
        
        return trim($sanitized);
    }
    
    /**
     * Validate boolean fields
     * 
     * @param mixed $value Raw boolean input
     * @return bool Validated boolean
     */
    public static function validate_boolean($value) {
        return (bool) $value;
    }
    
    /**
     * Verify donation form nonce for CSRF protection
     * 
     * @param array $data Form data containing nonce
     * @return bool True if nonce is valid
     */
    private static function verify_donation_nonce($data) {
        $nonce = $data['wpd_nonce'] ?? '';
        return wp_verify_nonce($nonce, 'wpd_donation_form');
    }
    
    /**
     * Check rate limiting to prevent abuse
     * 
     * @return bool True if within rate limits
     */
    private static function check_rate_limit() {
        $ip = self::get_client_ip();
        $transient_key = 'wpd_rate_limit_' . md5($ip);
        
        $attempts = get_transient($transient_key);
        if ($attempts === false) {
            $attempts = 0;
        }
        
        if ($attempts >= self::$security_config['rate_limit_per_ip']) {
            self::log_security_violation('Rate limit exceeded', $ip);
            return false;
        }
        
        // Increment attempt counter
        set_transient($transient_key, $attempts + 1, HOUR_IN_SECONDS);
        
        return true;
    }
    
    /**
     * Check honeypot field for bot detection
     * 
     * @param array $data Form data
     * @return bool True if not a bot
     */
    private static function check_honeypot($data) {
        $honeypot_field = self::$security_config['honeypot_field'];
        
        // If honeypot field is filled, it's likely a bot
        if (!empty($data[$honeypot_field])) {
            self::log_security_violation('Honeypot triggered', self::get_client_ip());
            return false;
        }
        
        return true;
    }
    
    /**
     * Check for XSS attack patterns
     * 
     * @param string $input Input string to check
     * @return bool True if XSS patterns detected
     */
    private static function contains_xss_patterns($input) {
        $xss_patterns = array(
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/data:text\/html/i',
            '/vbscript:/i'
        );
        
        foreach ($xss_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for suspicious patterns that might indicate attacks
     * 
     * @param string $input Input string to check
     * @return bool True if suspicious patterns detected
     */
    private static function contains_suspicious_patterns($input) {
        $suspicious_patterns = array(
            '/\bunion\s+select/i',
            '/\bselect\s+.*\bfrom/i',
            '/\binsert\s+into/i',
            '/\bdelete\s+from/i',
            '/\bdrop\s+table/i',
            '/\bupdate\s+.*\bset/i',
            '/<\?php/i',
            '/<%/i',
            '/\$_/i'
        );
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if email is from a disposable email provider
     * 
     * @param string $email Email to check
     * @return bool True if disposable
     */
    private static function is_disposable_email($email) {
        // Common disposable email domains
        $disposable_domains = array(
            '10minutemail.com', 'guerrillamail.com', 'mailinator.com',
            'tempmail.org', 'temp-mail.org', 'throwaway.email'
        );
        
        $domain = substr(strrchr($email, "@"), 1);
        return in_array(strtolower($domain), $disposable_domains);
    }
    
    /**
     * Basic domain validation for email deliverability
     * 
     * @param string $email Email to validate
     * @return bool True if domain appears valid
     */
    private static function validate_email_domain($email) {
        $domain = substr(strrchr($email, "@"), 1);
        
        // Check if domain has valid format
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN)) {
            return false;
        }
        
        // Check if domain has MX record (basic deliverability check)
        if (function_exists('checkdnsrr') && !checkdnsrr($domain, 'MX')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get client IP address for security logging
     * 
     * @return string Client IP address
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Log security violations for monitoring
     * 
     * @param string $violation_type Type of security violation
     * @param string $data Violation data (sanitized)
     */
    private static function log_security_violation($violation_type, $data) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'ip' => self::get_client_ip(),
            'violation' => $violation_type,
            'data' => wp_kses($data, array()), // Strip all HTML for logging
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        
        // Log to WordPress error log
        error_log('[WPD Security] ' . $violation_type . ' from IP: ' . $log_entry['ip']);
        
        // Store in option for admin review (limit to 100 entries)
        $security_log = get_option('wpd_security_log', array());
        array_unshift($security_log, $log_entry);
        $security_log = array_slice($security_log, 0, 100);
        update_option('wpd_security_log', $security_log);
    }
    
    /**
     * Log successful validation for security monitoring
     * 
     * @param array $data Validated donation data
     */
    private static function log_validation_success($data) {
        // Update success counter for monitoring
        $success_count = get_option('wpd_validation_success_count', 0);
        update_option('wpd_validation_success_count', $success_count + 1);
        
        // Log for debugging if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WPD] Successful validation for email: ' . wp_privacy_anonymize_data('email', $data['donor_email']));
        }
    }
    
    /**
     * Get current security configuration
     * 
     * @return array Security configuration
     */
    public static function get_security_config() {
        return self::$security_config;
    }
    
    /**
     * Update security configuration (for admin settings)
     * 
     * @param array $config New configuration
     */
    public static function update_security_config($config) {
        self::$security_config = array_merge(self::$security_config, $config);
    }
}