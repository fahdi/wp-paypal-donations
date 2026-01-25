<?php
/**
 * Enhanced Donations Dashboard with Data Export & Management
 * 
 * Provides comprehensive donation management with advanced filtering,
 * search capabilities, bulk operations, and data export functionality.
 * 
 * Business Impact: Enables organizations to fulfill compliance requirements,
 * perform financial reporting, and manage donor relationships effectively.
 *
 * @package WP_PayPal_Donations
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'donations';

// Calculate key metrics
$total_donations = $wpdb->get_var("SELECT SUM(amount) FROM $table_name WHERE payment_status = 'completed'");
$total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE payment_status = 'completed'");
$pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE payment_status = 'pending'");
$this_month_total = $wpdb->get_var($wpdb->prepare(
    "SELECT SUM(amount) FROM $table_name WHERE payment_status = 'completed' AND donation_date >= %s",
    date('Y-m-01 00:00:00')
));

// Initialize and prepare the enhanced list table
$donations_table = new WPD_Donations_List_Table();
$donations_table->prepare_items();

?>

<div class="wrap">
    <h1><?php _e('Donations Dashboard', 'wp-paypal-donations'); ?>
        <span class="page-title-action">
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wp-paypal-donations&action=export_all_csv'), 'export_all_csv')); ?>" class="page-title-action">
                <?php _e('Export All (CSV)', 'wp-paypal-donations'); ?>
            </a>
        </span>
    </h1>
    
    <!-- Enhanced Statistics Dashboard -->
    <div class="wpd-stats">
        <div class="wpd-stat-box wpd-stat-primary">
            <h3><?php _e('Total Raised', 'wp-paypal-donations'); ?></h3>
            <p class="wpd-stat-number"><?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($total_donations ?: 0, 2); ?></p>
            <span class="wpd-stat-label"><?php _e('All time', 'wp-paypal-donations'); ?></span>
        </div>
        
        <div class="wpd-stat-box wpd-stat-success">
            <h3><?php _e('Total Donations', 'wp-paypal-donations'); ?></h3>
            <p class="wpd-stat-number"><?php echo number_format($total_count ?: 0); ?></p>
            <span class="wpd-stat-label"><?php _e('Completed', 'wp-paypal-donations'); ?></span>
        </div>
        
        <div class="wpd-stat-box wpd-stat-info">
            <h3><?php _e('Average Donation', 'wp-paypal-donations'); ?></h3>
            <p class="wpd-stat-number"><?php echo $total_count > 0 ? get_option('wpd_currency', 'USD') . ' ' . number_format($total_donations / $total_count, 2) : '0'; ?></p>
            <span class="wpd-stat-label"><?php _e('Per donation', 'wp-paypal-donations'); ?></span>
        </div>
        
        <div class="wpd-stat-box wpd-stat-warning">
            <h3><?php _e('This Month', 'wp-paypal-donations'); ?></h3>
            <p class="wpd-stat-number"><?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($this_month_total ?: 0, 2); ?></p>
            <span class="wpd-stat-label"><?php echo date('F Y'); ?></span>
        </div>
        
        <?php if ($pending_count > 0): ?>
        <div class="wpd-stat-box wpd-stat-pending">
            <h3><?php _e('Pending', 'wp-paypal-donations'); ?></h3>
            <p class="wpd-stat-number"><?php echo number_format($pending_count); ?></p>
            <span class="wpd-stat-label"><?php _e('Awaiting payment', 'wp-paypal-donations'); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Enhanced Donations Management Table -->
    <div class="wpd-donations-management">
        <h2><?php _e('Donation Management', 'wp-paypal-donations'); ?></h2>
        
        <?php if ($donations_table->has_items()): ?>
            <form id="donations-filter" method="post">
                <?php $donations_table->display(); ?>
            </form>
        <?php else: ?>
            <div class="wpd-no-donations">
                <h3><?php _e('No donations yet', 'wp-paypal-donations'); ?></h3>
                <p><?php _e('Get started by adding the donation form to your website:', 'wp-paypal-donations'); ?></p>
                <code>[donation_form]</code>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=wp-paypal-donations-settings'); ?>" class="button button-primary">
                        <?php _e('Configure Settings', 'wp-paypal-donations'); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Styles for Professional Data Management Interface -->
<style>
.wpd-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0 30px 0;
}

.wpd-stat-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,.04);
    transition: transform 0.2s, box-shadow 0.2s;
    border-left: 4px solid #0073aa;
}

.wpd-stat-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,.1);
}

.wpd-stat-box.wpd-stat-primary { border-left-color: #0073aa; }
.wpd-stat-box.wpd-stat-success { border-left-color: #00a32a; }
.wpd-stat-box.wpd-stat-info { border-left-color: #72aee6; }
.wpd-stat-box.wpd-stat-warning { border-left-color: #dba617; }
.wpd-stat-box.wpd-stat-pending { border-left-color: #d63638; }

.wpd-stat-box h3 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.wpd-stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #23282d;
    margin: 0 0 8px 0;
    line-height: 1.2;
}

.wpd-stat-label {
    font-size: 12px;
    color: #666;
    font-style: italic;
}

.wpd-donations-management {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 0;
    box-shadow: 0 1px 3px rgba(0,0,0,.04);
}

.wpd-donations-management h2 {
    margin: 0;
    padding: 20px 24px;
    border-bottom: 1px solid #ccd0d4;
    background: #f9f9f9;
    border-radius: 8px 8px 0 0;
}

.wpd-donations-management .tablenav {
    padding: 12px 20px;
    background: #f9f9f9;
    border-bottom: 1px solid #ccd0d4;
}

.wpd-donations-management .wp-list-table {
    border: none;
    border-radius: 0;
}

.wpd-no-donations {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.wpd-no-donations h3 {
    color: #23282d;
    margin-bottom: 16px;
}

.wpd-no-donations code {
    background: #f1f1f1;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 14px;
    margin: 16px 0;
    display: inline-block;
}

/* Enhanced status styles */
.wpd-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
    min-width: 80px;
    text-align: center;
}

.wpd-status-completed {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

.wpd-status-pending {
    background: #fff3cd;
    color: #664d03;
    border: 1px solid #ffecb5;
}

.wpd-status-failed {
    background: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
}

/* Responsive adjustments */
@media (max-width: 782px) {
    .wpd-stats {
        grid-template-columns: 1fr;
    }
    
    .wpd-stat-box {
        padding: 16px;
    }
    
    .wpd-stat-number {
        font-size: 24px;
    }
    
    .wpd-donations-management {
        margin: 0 -20px;
        border-left: none;
        border-right: none;
        border-radius: 0;
    }
}

/* Data export enhancement styles */
.wpd-export-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.wpd-export-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.wpd-export-actions .button::before {
    content: "↓";
    font-weight: bold;
}

/* Table enhancements */
.wp-list-table th, 
.wp-list-table td {
    padding: 12px 10px;
}

.wp-list-table .column-amount {
    font-weight: 600;
    font-family: 'Monaco', 'Menlo', monospace;
}

.wp-list-table .column-id {
    font-family: 'Monaco', 'Menlo', monospace;
    color: #666;
}

/* Filter controls styling */
.tablenav .actions input[type="date"],
.tablenav .actions select {
    margin-right: 8px;
    vertical-align: middle;
}

.alignleft.actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
</style>