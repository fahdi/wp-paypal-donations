<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'donations';

$donations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY donation_date DESC LIMIT 50");
$total_donations = $wpdb->get_var("SELECT SUM(amount) FROM $table_name WHERE payment_status = 'completed'");
$total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE payment_status = 'completed'");
?>

<div class="wrap">
    <h1><?php _e('Donations Dashboard', 'wp-paypal-donations'); ?></h1>
    
    <div class="wpd-stats">
        <div class="wpd-stat-box">
            <h3><?php _e('Total Raised', 'wp-paypal-donations'); ?></h3>
            <p class="wpd-stat-number"><?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($total_donations, 2); ?></p>
        </div>
        <div class="wpd-stat-box">
            <h3><?php _e('Total Donations', 'wp-paypal-donations'); ?></h3>
            <p class="wpd-stat-number"><?php echo $total_count; ?></p>
        </div>
        <div class="wpd-stat-box">
            <h3><?php _e('Average Donation', 'wp-paypal-donations'); ?></h3>
            <p class="wpd-stat-number"><?php echo $total_count > 0 ? get_option('wpd_currency', 'USD') . ' ' . number_format($total_donations / $total_count, 2) : '0'; ?></p>
        </div>
    </div>
    
    <h2><?php _e('Recent Donations', 'wp-paypal-donations'); ?></h2>
    
    <?php if (empty($donations)): ?>
        <p><?php _e('No donations yet. Add the donation form to your website using [donation_form] shortcode.', 'wp-paypal-donations'); ?></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Donor Name', 'wp-paypal-donations'); ?></th>
                    <th><?php _e('Email', 'wp-paypal-donations'); ?></th>
                    <th><?php _e('Amount', 'wp-paypal-donations'); ?></th>
                    <th><?php _e('Status', 'wp-paypal-donations'); ?></th>
                    <th><?php _e('Transaction ID', 'wp-paypal-donations'); ?></th>
                    <th><?php _e('Date', 'wp-paypal-donations'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donations as $donation): ?>
                    <tr>
                        <td><?php echo esc_html($donation->donor_name); ?></td>
                        <td><?php echo esc_html($donation->donor_email); ?></td>
                        <td><?php echo esc_html($donation->currency . ' ' . number_format($donation->amount, 2)); ?></td>
                        <td>
                            <span class="wpd-status wpd-status-<?php echo esc_attr($donation->payment_status); ?>">
                                <?php echo esc_html(ucfirst($donation->payment_status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($donation->transaction_id ?: '-'); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($donation->donation_date))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.wpd-stats {
    display: flex;
    gap: 20px;
    margin: 20px 0;
    flex-wrap: wrap;
}

.wpd-stat-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    min-width: 200px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.wpd-stat-box h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
}

.wpd-stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #23282d;
    margin: 0;
}

.wpd-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.wpd-status-completed {
    background: #d4edda;
    color: #155724;
}

.wpd-status-pending {
    background: #fff3cd;
    color: #856404;
}

.wpd-status-failed {
    background: #f8d7da;
    color: #721c24;
}
</style>