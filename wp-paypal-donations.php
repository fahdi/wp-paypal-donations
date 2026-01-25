<?php
/**
 * Plugin Name: WP PayPal Donations
 * Plugin URI: https://example.com
 * Description: Simple and secure donation collection plugin with PayPal integration and sandbox testing support.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-paypal-donations
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WPD_VERSION', '1.0.0');

class WP_PayPal_Donations {
    
    private $paypal_api;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Include the PayPal REST API class
        require_once WPD_PLUGIN_PATH . 'includes/class-paypal-rest-api.php';
        $this->paypal_api = new WPD_PayPal_REST_API();
        
        // Include the enhanced donations list table class
        require_once WPD_PLUGIN_PATH . 'includes/class-donations-list-table.php';
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('donation_form', array($this, 'donation_form_shortcode'));
        add_action('wp_ajax_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
        add_action('init', array($this, 'handle_paypal_return'));
        add_action('init', array($this, 'register_donation_block'));
        
        load_plugin_textdomain('wp-paypal-donations', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        $this->create_tables();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'donations';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            donor_name varchar(100) NOT NULL,
            donor_email varchar(100) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            payment_status varchar(50) NOT NULL,
            transaction_id varchar(100),
            paypal_response text,
            donation_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function admin_menu() {
        add_menu_page(
            __('Donations', 'wp-paypal-donations'),
            __('Donations', 'wp-paypal-donations'),
            'manage_options',
            'wp-paypal-donations',
            array($this, 'admin_page'),
            'dashicons-heart',
            30
        );
        
        add_submenu_page(
            'wp-paypal-donations',
            __('Settings', 'wp-paypal-donations'),
            __('Settings', 'wp-paypal-donations'),
            'manage_options',
            'wp-paypal-donations-settings',
            array($this, 'settings_page')
        );
    }
    
    public function admin_init() {
        register_setting('wpd_settings', 'wpd_paypal_email');
        register_setting('wpd_settings', 'wpd_sandbox_mode');
        register_setting('wpd_settings', 'wpd_currency');
        register_setting('wpd_settings', 'wpd_default_amount');
        register_setting('wpd_settings', 'wpd_success_message');
        register_setting('wpd_settings', 'wpd_enable_recurring');
        register_setting('wpd_settings', 'wpd_paypal_client_id');
        register_setting('wpd_settings', 'wpd_paypal_client_secret');
        register_setting('wpd_settings', 'wpd_payment_method');
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('wpd-frontend', WPD_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WPD_VERSION, true);
        wp_enqueue_style('wpd-frontend', WPD_PLUGIN_URL . 'assets/css/frontend.css', array(), WPD_VERSION);
        
        wp_localize_script('wpd-frontend', 'wpd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpd_nonce'),
            'processing_text' => __('Processing...', 'wp-paypal-donations'),
            'currency' => get_option('wpd_currency', 'USD')
        ));
    }
    
    public function donation_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => get_option('wpd_default_amount', '25'),
            'currency' => get_option('wpd_currency', 'USD'),
            'title' => __('Make a Donation', 'wp-paypal-donations')
        ), $atts);
        
        ob_start();
        include WPD_PLUGIN_PATH . 'templates/donation-form.php';
        return ob_get_clean();
    }
    
    public function process_donation() {
        check_ajax_referer('wpd_nonce', 'nonce');
        
        $amount = sanitize_text_field($_POST['amount']);
        $donor_name = sanitize_text_field($_POST['donor_name']);
        $donor_email = sanitize_email($_POST['donor_email']);
        $currency = get_option('wpd_currency', 'USD');
        $recurring = isset($_POST['recurring']) ? true : false;
        $payment_method = get_option('wpd_payment_method', 'standard');
        
        if (empty($amount) || empty($donor_name) || empty($donor_email)) {
            wp_die(__('Please fill in all required fields.', 'wp-paypal-donations'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';
        
        $donation_id = $wpdb->insert(
            $table_name,
            array(
                'donor_name' => $donor_name,
                'donor_email' => $donor_email,
                'amount' => $amount,
                'currency' => $currency,
                'payment_status' => 'pending'
            ),
            array('%s', '%s', '%f', '%s', '%s')
        );
        
        $return_url = add_query_arg(array('donation_success' => '1', 'donation_id' => $wpdb->insert_id), home_url());
        $cancel_url = add_query_arg('donation_cancelled', '1', home_url());
        
        if ($payment_method === 'rest_api') {
            // Use PayPal REST API
            $description = sprintf(__('Donation to %s', 'wp-paypal-donations'), get_bloginfo('name'));
            
            if ($recurring) {
                $result = $this->paypal_api->create_subscription($amount, $currency, $return_url, $cancel_url);
            } else {
                $result = $this->paypal_api->create_payment($amount, $currency, $return_url, $cancel_url, $description);
            }
            
            if ($result['success']) {
                // Store PayPal order ID for later reference
                $wpdb->update(
                    $table_name,
                    array('transaction_id' => $result[$recurring ? 'subscription_id' : 'order_id']),
                    array('id' => $wpdb->insert_id),
                    array('%s'),
                    array('%d')
                );
                
                wp_send_json_success(array('redirect_url' => $result['approval_url']));
            } else {
                wp_send_json_error(array('message' => $result['message']));
            }
        } else {
            // Use PayPal Standard (legacy)
            $paypal_url = $this->get_paypal_url();
            $paypal_email = get_option('wpd_paypal_email');
            
            $paypal_args = array(
                'business' => $paypal_email,
                'cmd' => $recurring ? '_xclick-subscriptions' : '_donations',
                'item_name' => __('Donation to ', 'wp-paypal-donations') . get_bloginfo('name'),
                'amount' => $amount,
                'currency_code' => $currency,
                'return' => $return_url,
                'cancel_return' => $cancel_url,
                'notify_url' => add_query_arg('wpd_ipn', '1', home_url()),
                'custom' => $wpdb->insert_id
            );
            
            if ($recurring) {
                $paypal_args['p3'] = 1;
                $paypal_args['t3'] = 'M';
                $paypal_args['src'] = 1;
                $paypal_args['no_note'] = 1;
            }
            
            $paypal_redirect = add_query_arg($paypal_args, $paypal_url);
            
            wp_send_json_success(array('redirect_url' => $paypal_redirect));
        }
    }
    
    private function get_paypal_url() {
        $sandbox_mode = get_option('wpd_sandbox_mode', false);
        return $sandbox_mode ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
    }
    
    public function handle_paypal_return() {
        if (isset($_GET['donation_success'])) {
            // Handle REST API return
            if (isset($_GET['token']) && isset($_GET['PayerID'])) {
                $this->handle_rest_api_return();
            }
            add_action('wp_footer', array($this, 'show_success_message'));
        }
        
        if (isset($_GET['donation_cancelled'])) {
            add_action('wp_footer', array($this, 'show_cancelled_message'));
        }
        
        if (isset($_GET['wpd_ipn'])) {
            $this->handle_ipn();
        }
    }
    
    private function handle_rest_api_return() {
        $token = sanitize_text_field($_GET['token']);
        $payer_id = sanitize_text_field($_GET['PayerID']);
        $donation_id = isset($_GET['donation_id']) ? intval($_GET['donation_id']) : 0;
        
        if (!$token || !$payer_id) {
            return;
        }
        
        // For PayPal REST API, the token is the order ID
        $result = $this->paypal_api->capture_payment($token);
        
        if ($result['success']) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'donations';
            
            $wpdb->update(
                $table_name,
                array(
                    'payment_status' => 'completed',
                    'transaction_id' => $result['transaction_id'],
                    'paypal_response' => serialize($result['data'])
                ),
                array('id' => $donation_id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            // Send notification emails
            $this->send_donation_notifications($donation_id);
        }
    }
    
    public function show_success_message() {
        $message = get_option('wpd_success_message', __('Thank you for your donation!', 'wp-paypal-donations'));
        echo '<div class="wpd-success-message">' . esc_html($message) . '</div>';
    }
    
    public function show_cancelled_message() {
        echo '<div class="wpd-error-message">' . __('Donation was cancelled.', 'wp-paypal-donations') . '</div>';
    }
    
    private function handle_ipn() {
        $postdata = file_get_contents('php://input');
        $req = 'cmd=_notify-validate&' . $postdata;
        
        $paypal_url = $this->get_paypal_url();
        
        $response = wp_remote_post($paypal_url, array(
            'body' => $req,
            'timeout' => 30,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return;
        }
        
        if (wp_remote_retrieve_body($response) === 'VERIFIED') {
            $payment_status = sanitize_text_field($_POST['payment_status']);
            $txn_id = sanitize_text_field($_POST['txn_id']);
            $custom = intval($_POST['custom']);
            
            if ($payment_status === 'Completed') {
                global $wpdb;
                $table_name = $wpdb->prefix . 'donations';
                
                $wpdb->update(
                    $table_name,
                    array(
                        'payment_status' => 'completed',
                        'transaction_id' => $txn_id,
                        'paypal_response' => serialize($_POST)
                    ),
                    array('id' => $custom),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
                
                // Send notification emails
                $this->send_donation_notifications($custom);
            }
        }
        
        exit;
    }
    
    public function admin_page() {
        // Handle export actions
        $this->handle_admin_actions();
        
        include WPD_PLUGIN_PATH . 'admin/donations-list.php';
    }
    
    private function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

        switch ($action) {
            case 'export_all_csv':
                if (!wp_verify_nonce($_GET['_wpnonce'], 'export_all_csv')) {
                    wp_die(__('Security check failed', 'wp-paypal-donations'));
                }
                $this->export_all_donations('csv');
                break;

            case 'export_all_excel':
                if (!wp_verify_nonce($_GET['_wpnonce'], 'export_all_excel')) {
                    wp_die(__('Security check failed', 'wp-paypal-donations'));
                }
                $this->export_all_donations('excel');
                break;
        }
    }

    private function export_all_donations($format = 'csv') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';

        // Get all donations
        $donations = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY donation_date DESC", ARRAY_A);

        if (empty($donations)) {
            wp_die(__('No donations found for export.', 'wp-paypal-donations'));
        }

        $filename = 'all_donations_' . date('Y-m-d_H-i-s');

        if ($format === 'csv') {
            $this->generate_csv_export($donations, $filename);
        } else {
            $this->generate_excel_export($donations, $filename);
        }
    }

    private function generate_csv_export($donations, $filename) {
        $filename .= '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Add CSV headers
        $headers = array(
            'ID',
            'Donor Name',
            'Email',
            'Amount',
            'Currency',
            'Status',
            'Transaction ID',
            'Date'
        );
        fputcsv($output, $headers);

        // Add data rows
        foreach ($donations as $donation) {
            $row = array(
                $donation['id'],
                $donation['donor_name'],
                $donation['donor_email'],
                $donation['amount'],
                $donation['currency'],
                $donation['payment_status'],
                $donation['transaction_id'],
                $donation['donation_date']
            );
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    private function generate_excel_export($donations, $filename) {
        $filename .= '.xls';

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF"; // UTF-8 BOM

        // Add headers
        echo "ID\tDonor Name\tEmail\tAmount\tCurrency\tStatus\tTransaction ID\tDate\n";

        // Add data rows
        foreach ($donations as $donation) {
            echo $donation['id'] . "\t" .
                 $donation['donor_name'] . "\t" .
                 $donation['donor_email'] . "\t" .
                 $donation['amount'] . "\t" .
                 $donation['currency'] . "\t" .
                 $donation['payment_status'] . "\t" .
                 $donation['transaction_id'] . "\t" .
                 $donation['donation_date'] . "\n";
        }

        exit;
    }
    
    public function settings_page() {
        include WPD_PLUGIN_PATH . 'admin/settings-page.php';
    }
    
    public function get_currency_symbol($currency = 'USD') {
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => '$',
            'AUD' => '$',
            'JPY' => '¥'
        );
        
        return isset($symbols[$currency]) ? $symbols[$currency] : '$';
    }
    
    public function send_donation_notifications($donation_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';
        
        $donation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", $donation_id
        ));
        
        if (!$donation) {
            return;
        }
        
        // Send thank you email to donor
        $this->send_donor_thank_you_email($donation);
        
        // Send notification to admin
        $this->send_admin_notification_email($donation);
    }
    
    private function send_donor_thank_you_email($donation) {
        $subject = sprintf(__('Thank you for your donation to %s', 'wp-paypal-donations'), get_bloginfo('name'));
        $currency_symbol = $this->get_currency_symbol($donation->currency);
        
        $message = sprintf(
            __('Dear %s,

Thank you for your generous donation of %s%s to %s!

Your donation will help us continue our important work. We truly appreciate your support.

Donation Details:
- Amount: %s%s
- Transaction ID: %s
- Date: %s

Best regards,
%s Team', 'wp-paypal-donations'),
            $donation->donor_name,
            $currency_symbol,
            number_format($donation->amount, 2),
            get_bloginfo('name'),
            $currency_symbol,
            number_format($donation->amount, 2),
            $donation->transaction_id,
            date_i18n(get_option('date_format'), strtotime($donation->donation_date)),
            get_bloginfo('name')
        );
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($donation->donor_email, $subject, $message, $headers);
    }
    
    private function send_admin_notification_email($donation) {
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('New donation received on %s', 'wp-paypal-donations'), get_bloginfo('name'));
        $currency_symbol = $this->get_currency_symbol($donation->currency);
        
        $message = sprintf(
            __('You have received a new donation!

Donor Details:
- Name: %s
- Email: %s
- Amount: %s%s
- Transaction ID: %s
- Date: %s

You can view all donations in your WordPress admin dashboard under Donations.', 'wp-paypal-donations'),
            $donation->donor_name,
            $donation->donor_email,
            $currency_symbol,
            number_format($donation->amount, 2),
            $donation->transaction_id,
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($donation->donation_date))
        );
        
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8'
        );
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    public function register_donation_block() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        wp_register_script(
            'wp-paypal-donations-block',
            WPD_PLUGIN_URL . 'blocks/donation-form/build/index.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-block-editor'),
            WPD_VERSION,
            true
        );
        
        wp_register_style(
            'wp-paypal-donations-block-editor',
            WPD_PLUGIN_URL . 'blocks/donation-form/build/editor.css',
            array('wp-edit-blocks'),
            WPD_VERSION
        );
        
        register_block_type(WPD_PLUGIN_PATH . 'blocks/donation-form/block.json', array(
            'render_callback' => array($this, 'render_donation_block')
        ));
    }
    
    public function render_donation_block($attributes) {
        return $this->donation_form_shortcode($attributes);
    }
}

new WP_PayPal_Donations();