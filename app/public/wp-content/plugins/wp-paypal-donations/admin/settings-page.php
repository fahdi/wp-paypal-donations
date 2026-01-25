<?php
if (!defined('ABSPATH')) {
    exit;
}

if (isset($_POST['submit'])) {
    update_option('wpd_paypal_email', sanitize_email($_POST['wpd_paypal_email']));
    update_option('wpd_sandbox_mode', isset($_POST['wpd_sandbox_mode']) ? 1 : 0);
    update_option('wpd_currency', sanitize_text_field($_POST['wpd_currency']));
    update_option('wpd_default_amount', floatval($_POST['wpd_default_amount']));
    update_option('wpd_success_message', sanitize_textarea_field($_POST['wpd_success_message']));
    update_option('wpd_enable_recurring', isset($_POST['wpd_enable_recurring']) ? 1 : 0);
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'wp-paypal-donations') . '</p></div>';
}

$paypal_email = get_option('wpd_paypal_email', '');
$sandbox_mode = get_option('wpd_sandbox_mode', 0);
$currency = get_option('wpd_currency', 'USD');
$default_amount = get_option('wpd_default_amount', '25');
$success_message = get_option('wpd_success_message', __('Thank you for your donation!', 'wp-paypal-donations'));
$enable_recurring = get_option('wpd_enable_recurring', 0);
?>

<div class="wrap">
    <h1><?php _e('Donation Settings', 'wp-paypal-donations'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wpd_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wpd_paypal_email"><?php _e('PayPal Email', 'wp-paypal-donations'); ?></label>
                </th>
                <td>
                    <input type="email" id="wpd_paypal_email" name="wpd_paypal_email" value="<?php echo esc_attr($paypal_email); ?>" class="regular-text" required />
                    <p class="description"><?php _e('Enter your PayPal email address to receive donations.', 'wp-paypal-donations'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wpd_sandbox_mode"><?php _e('Sandbox Mode', 'wp-paypal-donations'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="wpd_sandbox_mode" name="wpd_sandbox_mode" value="1" <?php checked($sandbox_mode, 1); ?> />
                    <label for="wpd_sandbox_mode"><?php _e('Enable PayPal Sandbox for testing', 'wp-paypal-donations'); ?></label>
                    <p class="description"><?php _e('Check this to use PayPal Sandbox for testing. Uncheck for live payments.', 'wp-paypal-donations'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wpd_currency"><?php _e('Currency', 'wp-paypal-donations'); ?></label>
                </th>
                <td>
                    <select id="wpd_currency" name="wpd_currency">
                        <option value="USD" <?php selected($currency, 'USD'); ?>>USD - US Dollar</option>
                        <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR - Euro</option>
                        <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP - British Pound</option>
                        <option value="CAD" <?php selected($currency, 'CAD'); ?>>CAD - Canadian Dollar</option>
                        <option value="AUD" <?php selected($currency, 'AUD'); ?>>AUD - Australian Dollar</option>
                        <option value="JPY" <?php selected($currency, 'JPY'); ?>>JPY - Japanese Yen</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wpd_default_amount"><?php _e('Default Amount', 'wp-paypal-donations'); ?></label>
                </th>
                <td>
                    <input type="number" id="wpd_default_amount" name="wpd_default_amount" value="<?php echo esc_attr($default_amount); ?>" step="0.01" min="0.01" />
                    <p class="description"><?php _e('Default donation amount to show in the form.', 'wp-paypal-donations'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wpd_success_message"><?php _e('Success Message', 'wp-paypal-donations'); ?></label>
                </th>
                <td>
                    <textarea id="wpd_success_message" name="wpd_success_message" rows="3" cols="50"><?php echo esc_textarea($success_message); ?></textarea>
                    <p class="description"><?php _e('Message to show after successful donation.', 'wp-paypal-donations'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wpd_enable_recurring"><?php _e('Recurring Donations', 'wp-paypal-donations'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="wpd_enable_recurring" name="wpd_enable_recurring" value="1" <?php checked($enable_recurring, 1); ?> />
                    <label for="wpd_enable_recurring"><?php _e('Allow recurring monthly donations', 'wp-paypal-donations'); ?></label>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <hr>
    
    <h2><?php _e('How to Use', 'wp-paypal-donations'); ?></h2>
    <p><?php _e('Add the donation form to any page or post using this shortcode:', 'wp-paypal-donations'); ?></p>
    <code>[donation_form]</code>
    
    <p><?php _e('You can also customize the form:', 'wp-paypal-donations'); ?></p>
    <code>[donation_form amount="50" title="Support Our Cause"]</code>
    
    <h3><?php _e('Testing with PayPal Sandbox', 'wp-paypal-donations'); ?></h3>
    <ol>
        <li><?php _e('Enable Sandbox Mode above', 'wp-paypal-donations'); ?></li>
        <li><?php _e('Use your PayPal Sandbox business account email', 'wp-paypal-donations'); ?></li>
        <li><?php _e('Test with PayPal Sandbox buyer accounts', 'wp-paypal-donations'); ?></li>
        <li><?php _e('Disable Sandbox Mode when ready for live donations', 'wp-paypal-donations'); ?></li>
    </ol>
</div>