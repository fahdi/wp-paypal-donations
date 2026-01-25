<?php
/**
 * PayPal REST API Handler
 *
 * @package WP_PayPal_Donations
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPD_PayPal_REST_API {
    
    private $client_id;
    private $client_secret;
    private $sandbox_mode;
    private $access_token;
    private $base_url;
    
    public function __construct() {
        $this->client_id = get_option('wpd_paypal_client_id');
        $this->client_secret = get_option('wpd_paypal_client_secret');
        $this->sandbox_mode = get_option('wpd_sandbox_mode', false);
        
        $this->base_url = $this->sandbox_mode 
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }
    
    /**
     * Get access token for PayPal API
     */
    public function get_access_token() {
        // Check if we have a cached token
        $cached_token = get_transient('wpd_paypal_access_token');
        if ($cached_token && !empty($cached_token['access_token'])) {
            return $cached_token['access_token'];
        }
        
        $url = $this->base_url . '/v1/oauth2/token';
        
        $headers = array(
            'Accept' => 'application/json',
            'Accept-Language' => 'en_US',
            'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
        );
        
        $body = 'grant_type=client_credentials';
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => $body,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('PayPal API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['access_token'])) {
            // Cache the token (expires in 1 hour, cache for 55 minutes)
            $cache_duration = isset($data['expires_in']) ? ($data['expires_in'] - 300) : 3300;
            set_transient('wpd_paypal_access_token', $data, $cache_duration);
            
            return $data['access_token'];
        }
        
        error_log('PayPal API Error: Unable to get access token - ' . $body);
        return false;
    }
    
    /**
     * Create a PayPal payment
     */
    public function create_payment($amount, $currency, $return_url, $cancel_url, $description = '') {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return array('success' => false, 'message' => 'Unable to authenticate with PayPal');
        }
        
        $url = $this->base_url . '/v2/checkout/orders';
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
            'PayPal-Request-Id' => uniqid('wpd_', true)
        );
        
        $payment_data = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'amount' => array(
                        'currency_code' => $currency,
                        'value' => number_format($amount, 2, '.', '')
                    ),
                    'description' => $description ?: sprintf(__('Donation to %s', 'wp-paypal-donations'), get_bloginfo('name'))
                )
            ),
            'application_context' => array(
                'return_url' => $return_url,
                'cancel_url' => $cancel_url,
                'brand_name' => get_bloginfo('name'),
                'landing_page' => 'NO_PREFERENCE',
                'user_action' => 'PAY_NOW'
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($payment_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['id']) && isset($data['links'])) {
            // Find the approval URL
            $approval_url = '';
            foreach ($data['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approval_url = $link['href'];
                    break;
                }
            }
            
            return array(
                'success' => true,
                'order_id' => $data['id'],
                'approval_url' => $approval_url,
                'data' => $data
            );
        }
        
        return array('success' => false, 'message' => 'Invalid response from PayPal', 'debug' => $data);
    }
    
    /**
     * Capture a PayPal payment
     */
    public function capture_payment($order_id) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return array('success' => false, 'message' => 'Unable to authenticate with PayPal');
        }
        
        $url = $this->base_url . '/v2/checkout/orders/' . $order_id . '/capture';
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
            'PayPal-Request-Id' => uniqid('wpd_capture_', true)
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => '{}',
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['status']) && $data['status'] === 'COMPLETED') {
            return array(
                'success' => true,
                'status' => 'completed',
                'transaction_id' => $data['id'],
                'data' => $data
            );
        }
        
        return array('success' => false, 'message' => 'Payment capture failed', 'debug' => $data);
    }
    
    /**
     * Get payment details
     */
    public function get_payment_details($order_id) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return array('success' => false, 'message' => 'Unable to authenticate with PayPal');
        }
        
        $url = $this->base_url . '/v2/checkout/orders/' . $order_id;
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token
        );
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['id'])) {
            return array(
                'success' => true,
                'data' => $data
            );
        }
        
        return array('success' => false, 'message' => 'Unable to retrieve payment details', 'debug' => $data);
    }
    
    /**
     * Create a subscription for recurring donations
     */
    public function create_subscription($amount, $currency, $return_url, $cancel_url) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return array('success' => false, 'message' => 'Unable to authenticate with PayPal');
        }
        
        // First create a billing plan
        $plan_id = $this->create_billing_plan($amount, $currency, $access_token);
        if (!$plan_id) {
            return array('success' => false, 'message' => 'Unable to create billing plan');
        }
        
        $url = $this->base_url . '/v1/billing/subscriptions';
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
            'PayPal-Request-Id' => uniqid('wpd_sub_', true)
        );
        
        $subscription_data = array(
            'plan_id' => $plan_id,
            'application_context' => array(
                'return_url' => $return_url,
                'cancel_url' => $cancel_url,
                'brand_name' => get_bloginfo('name'),
                'user_action' => 'SUBSCRIBE_NOW'
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($subscription_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['id']) && isset($data['links'])) {
            // Find the approval URL
            $approval_url = '';
            foreach ($data['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approval_url = $link['href'];
                    break;
                }
            }
            
            return array(
                'success' => true,
                'subscription_id' => $data['id'],
                'approval_url' => $approval_url,
                'data' => $data
            );
        }
        
        return array('success' => false, 'message' => 'Invalid response from PayPal', 'debug' => $data);
    }
    
    /**
     * Create a billing plan for subscriptions
     */
    private function create_billing_plan($amount, $currency, $access_token) {
        $url = $this->base_url . '/v1/billing/plans';
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
            'PayPal-Request-Id' => uniqid('wpd_plan_', true)
        );
        
        $plan_data = array(
            'product_id' => $this->get_or_create_product($access_token),
            'name' => sprintf(__('Monthly Donation - %s', 'wp-paypal-donations'), get_bloginfo('name')),
            'description' => sprintf(__('Monthly recurring donation to %s', 'wp-paypal-donations'), get_bloginfo('name')),
            'status' => 'ACTIVE',
            'billing_cycles' => array(
                array(
                    'frequency' => array(
                        'interval_unit' => 'MONTH',
                        'interval_count' => 1
                    ),
                    'tenure_type' => 'REGULAR',
                    'sequence' => 1,
                    'total_cycles' => 0, // Infinite
                    'pricing_scheme' => array(
                        'fixed_price' => array(
                            'value' => number_format($amount, 2, '.', ''),
                            'currency_code' => $currency
                        )
                    )
                )
            ),
            'payment_preferences' => array(
                'auto_bill_outstanding' => true,
                'setup_fee_failure_action' => 'CONTINUE',
                'payment_failure_threshold' => 3
            )
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($plan_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return isset($data['id']) ? $data['id'] : false;
    }
    
    /**
     * Get or create a product for billing plans
     */
    private function get_or_create_product($access_token) {
        // Try to get cached product ID
        $product_id = get_transient('wpd_paypal_product_id');
        if ($product_id) {
            return $product_id;
        }
        
        $url = $this->base_url . '/v1/catalogs/products';
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $access_token,
            'PayPal-Request-Id' => uniqid('wpd_product_', true)
        );
        
        $product_data = array(
            'name' => sprintf(__('Donations - %s', 'wp-paypal-donations'), get_bloginfo('name')),
            'description' => sprintf(__('Recurring donations to %s', 'wp-paypal-donations'), get_bloginfo('name')),
            'type' => 'SERVICE',
            'category' => 'NONPROFIT'
        );
        
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($product_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['id'])) {
            // Cache the product ID for 1 week
            set_transient('wpd_paypal_product_id', $data['id'], WEEK_IN_SECONDS);
            return $data['id'];
        }
        
        return false;
    }
    
    /**
     * Verify webhook signature
     */
    public function verify_webhook($headers, $body) {
        // Implementation for webhook verification
        // This would be used for handling PayPal webhooks for payment notifications
        return true; // Placeholder
    }
}