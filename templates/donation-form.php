<?php
if (!defined('ABSPATH')) {
    exit;
}

$currency = get_option('wpd_currency', 'USD');
$currency_symbol = $this->get_currency_symbol($currency);
$enable_recurring = get_option('wpd_enable_recurring', 0);
$show_quick_amounts = isset($atts['showQuickAmounts']) ? $atts['showQuickAmounts'] : true;
$button_text = isset($atts['buttonText']) ? $atts['buttonText'] : __('Donate with PayPal', 'wp-paypal-donations');
?>

<div class="wpd-donation-form-container">
    <form id="wpd-donation-form" class="wpd-donation-form">
        <?php wp_nonce_field('wpd_donation_form', 'wpd_nonce'); ?>
        
        <!-- Honeypot field for bot detection - hidden from users -->
        <input type="text" name="wpd_website_url" style="display: none !important; visibility: hidden !important; position: absolute !important; left: -9999px !important;" tabindex="-1" autocomplete="off" />
        
        <h3 class="wpd-form-title"><?php echo esc_html($atts['title']); ?></h3>
        
        <div class="wpd-form-row">
            <label for="wpd-donor-name"><?php _e('Full Name', 'wp-paypal-donations'); ?> <span class="required">*</span></label>
            <input type="text" id="wpd-donor-name" name="donor_name" maxlength="100" pattern="[A-Za-z\s\-\.]+" title="<?php esc_attr_e('Please enter a valid name (letters, spaces, hyphens, and periods only)', 'wp-paypal-donations'); ?>" required />
        </div>
        
        <div class="wpd-form-row">
            <label for="wpd-donor-email"><?php _e('Email Address', 'wp-paypal-donations'); ?> <span class="required">*</span></label>
            <input type="email" id="wpd-donor-email" name="donor_email" maxlength="254" title="<?php esc_attr_e('Please enter a valid email address', 'wp-paypal-donations'); ?>" required />
            <small class="wpd-field-help"><?php _e('We\'ll send your donation receipt to this email', 'wp-paypal-donations'); ?></small>
        </div>
        
        <div class="wpd-form-row">
            <label for="wpd-amount"><?php _e('Donation Amount', 'wp-paypal-donations'); ?> <span class="required">*</span></label>
            <div class="wpd-amount-container">
                <span class="wpd-currency-symbol"><?php echo esc_html($currency_symbol); ?></span>
                <input type="number" id="wpd-amount" name="amount" value="<?php echo esc_attr($atts['amount']); ?>" min="1.00" max="10000.00" step="0.01" title="<?php esc_attr_e('Please enter an amount between $1.00 and $10,000.00', 'wp-paypal-donations'); ?>" required />
            </div>
            <small class="wpd-field-help"><?php _e('Minimum $1.00, maximum $10,000.00', 'wp-paypal-donations'); ?></small>
        </div>
        
        <div class="wpd-form-row">
            <label for="wpd-message"><?php _e('Optional Message', 'wp-paypal-donations'); ?></label>
            <textarea id="wpd-message" name="message" maxlength="500" placeholder="<?php esc_attr_e('Share why you\'re donating (optional)', 'wp-paypal-donations'); ?>"></textarea>
            <small class="wpd-field-help"><?php _e('Maximum 500 characters', 'wp-paypal-donations'); ?></small>
        </div>
        
        <?php if ($show_quick_amounts): ?>
        <div class="wpd-quick-amounts">
            <button type="button" class="wpd-quick-amount" data-amount="10">$10</button>
            <button type="button" class="wpd-quick-amount" data-amount="25">$25</button>
            <button type="button" class="wpd-quick-amount" data-amount="50">$50</button>
            <button type="button" class="wpd-quick-amount" data-amount="100">$100</button>
        </div>
        <?php endif; ?>
        
        <?php if ($enable_recurring): ?>
        <div class="wpd-form-row">
            <label>
                <input type="checkbox" id="wpd-recurring" name="recurring" />
                <?php _e('Make this a monthly recurring donation', 'wp-paypal-donations'); ?>
            </label>
        </div>
        <?php endif; ?>
        
        <div class="wpd-form-row">
            <button type="submit" class="wpd-donate-btn">
                <span class="wpd-btn-text"><?php echo esc_html($button_text); ?></span>
                <span class="wpd-btn-loading" style="display: none;"><?php _e('Processing...', 'wp-paypal-donations'); ?></span>
            </button>
        </div>
        
        <div class="wpd-security-info">
            <small>
                <span class="dashicons dashicons-lock"></span>
                <?php _e('Secure payment processed by PayPal', 'wp-paypal-donations'); ?>
            </small>
        </div>
    </form>
    
    <div id="wpd-message" class="wpd-message" style="display: none;"></div>
</div>

<?php
// Add this method to the main plugin class
if (!method_exists('WP_PayPal_Donations', 'get_currency_symbol')) {
?>
<script>
// Add currency symbol method inline for template
window.wpdCurrencySymbols = {
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'CAD': '$',
    'AUD': '$',
    'JPY': '¥'
};
</script>
<?php
}
?>