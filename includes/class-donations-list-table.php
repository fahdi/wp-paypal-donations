<?php
/**
 * Donations List Table for WordPress Admin
 * 
 * Provides advanced data management, filtering, search, and export capabilities
 * for donation records. Addresses critical business need for data accessibility
 * and compliance reporting.
 *
 * @package WP_PayPal_Donations
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WPD_Donations_List_Table extends WP_List_Table {

    private $table_data;
    private $total_items;

    public function __construct() {
        parent::__construct(array(
            'singular' => 'donation',
            'plural' => 'donations',
            'ajax' => true
        ));
    }

    /**
     * Get column headers
     */
    public function get_columns() {
        return array(
            'cb' => '<input type="checkbox" />',
            'id' => __('ID', 'wp-paypal-donations'),
            'donor_name' => __('Donor Name', 'wp-paypal-donations'),
            'donor_email' => __('Email', 'wp-paypal-donations'),
            'amount' => __('Amount', 'wp-paypal-donations'),
            'currency' => __('Currency', 'wp-paypal-donations'),
            'payment_status' => __('Status', 'wp-paypal-donations'),
            'transaction_id' => __('Transaction ID', 'wp-paypal-donations'),
            'donation_date' => __('Date', 'wp-paypal-donations')
        );
    }

    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        return array(
            'id' => array('id', true),
            'donor_name' => array('donor_name', false),
            'amount' => array('amount', false),
            'payment_status' => array('payment_status', false),
            'donation_date' => array('donation_date', false)
        );
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions() {
        return array(
            'export_csv' => __('Export Selected (CSV)', 'wp-paypal-donations'),
            'export_excel' => __('Export Selected (Excel)', 'wp-paypal-donations'),
            'mark_completed' => __('Mark as Completed', 'wp-paypal-donations'),
            'mark_pending' => __('Mark as Pending', 'wp-paypal-donations'),
            'delete' => __('Delete', 'wp-paypal-donations')
        );
    }

    /**
     * Handle bulk actions
     */
    public function process_bulk_action() {
        if (!isset($_POST['donation']) || !is_array($_POST['donation'])) {
            return;
        }

        $donation_ids = array_map('intval', $_POST['donation']);
        $action = $this->current_action();

        if (empty($donation_ids) || empty($action)) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'bulk-donations')) {
            wp_die(__('Security check failed', 'wp-paypal-donations'));
        }

        switch ($action) {
            case 'export_csv':
                $this->export_donations($donation_ids, 'csv');
                break;
            
            case 'export_excel':
                $this->export_donations($donation_ids, 'excel');
                break;

            case 'mark_completed':
                $this->update_donation_status($donation_ids, 'completed');
                break;

            case 'mark_pending':
                $this->update_donation_status($donation_ids, 'pending');
                break;

            case 'delete':
                $this->delete_donations($donation_ids);
                break;
        }
    }

    /**
     * Column checkbox for bulk actions
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="donation[]" value="%d" />', $item['id']);
    }

    /**
     * Default column renderer
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'id':
            case 'donor_name':
            case 'donor_email':
            case 'currency':
            case 'transaction_id':
                return esc_html($item[$column_name]);

            case 'amount':
                $currency_symbol = $this->get_currency_symbol($item['currency']);
                return esc_html($currency_symbol . number_format($item[$column_name], 2));

            case 'payment_status':
                $status_class = 'wpd-status-' . strtolower($item[$column_name]);
                return sprintf(
                    '<span class="wpd-status %s">%s</span>',
                    esc_attr($status_class),
                    esc_html(ucfirst($item[$column_name]))
                );

            case 'donation_date':
                return esc_html(date_i18n(
                    get_option('date_format') . ' ' . get_option('time_format'),
                    strtotime($item[$column_name])
                ));

            default:
                return print_r($item, true);
        }
    }

    /**
     * Prepare table data
     */
    public function prepare_items() {
        $this->process_bulk_action();

        // Handle search
        $search = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';

        // Handle date filtering
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';

        // Handle status filtering
        $status_filter = isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : '';

        // Pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Sorting
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'donation_date';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

        // Get data
        $data = $this->get_donations($search, $date_from, $date_to, $status_filter, $orderby, $order, $per_page, $offset);
        $total_items = $this->get_donations_count($search, $date_from, $date_to, $status_filter);

        // Set up pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));

        $this->items = $data;
    }

    /**
     * Get donations from database
     */
    private function get_donations($search = '', $date_from = '', $date_to = '', $status = '', $orderby = 'donation_date', $order = 'DESC', $limit = 20, $offset = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';

        $where_clauses = array('1=1');
        $where_values = array();

        // Search functionality
        if (!empty($search)) {
            $where_clauses[] = "(donor_name LIKE %s OR donor_email LIKE %s OR transaction_id LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values = array_merge($where_values, array($search_term, $search_term, $search_term));
        }

        // Date filtering
        if (!empty($date_from)) {
            $where_clauses[] = "donation_date >= %s";
            $where_values[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $where_clauses[] = "donation_date <= %s";
            $where_values[] = $date_to . ' 23:59:59';
        }

        // Status filtering
        if (!empty($status)) {
            $where_clauses[] = "payment_status = %s";
            $where_values[] = $status;
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Validate orderby to prevent SQL injection
        $allowed_orderby = array('id', 'donor_name', 'amount', 'payment_status', 'donation_date');
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'donation_date';
        }

        // Validate order
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $where_values = array_merge($where_values, array($limit, $offset));

        if (!empty($where_values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $where_values), ARRAY_A);
        } else {
            return $wpdb->get_results($sql, ARRAY_A);
        }
    }

    /**
     * Get total donations count
     */
    private function get_donations_count($search = '', $date_from = '', $date_to = '', $status = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';

        $where_clauses = array('1=1');
        $where_values = array();

        if (!empty($search)) {
            $where_clauses[] = "(donor_name LIKE %s OR donor_email LIKE %s OR transaction_id LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_values = array_merge($where_values, array($search_term, $search_term, $search_term));
        }

        if (!empty($date_from)) {
            $where_clauses[] = "donation_date >= %s";
            $where_values[] = $date_from . ' 00:00:00';
        }

        if (!empty($date_to)) {
            $where_clauses[] = "donation_date <= %s";
            $where_values[] = $date_to . ' 23:59:59';
        }

        if (!empty($status)) {
            $where_clauses[] = "payment_status = %s";
            $where_values[] = $status;
        }

        $where_sql = implode(' AND ', $where_clauses);
        $sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";

        if (!empty($where_values)) {
            return $wpdb->get_var($wpdb->prepare($sql, $where_values));
        } else {
            return $wpdb->get_var($sql);
        }
    }

    /**
     * Export donations to CSV/Excel
     */
    private function export_donations($donation_ids, $format = 'csv') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';

        // Get donations data
        $placeholders = implode(',', array_fill(0, count($donation_ids), '%d'));
        $sql = "SELECT * FROM {$table_name} WHERE id IN ({$placeholders}) ORDER BY donation_date DESC";
        $donations = $wpdb->get_results($wpdb->prepare($sql, $donation_ids), ARRAY_A);

        if (empty($donations)) {
            wp_die(__('No donations found for export.', 'wp-paypal-donations'));
        }

        // Generate filename
        $filename = 'donations_export_' . date('Y-m-d_H-i-s');

        if ($format === 'csv') {
            $this->export_csv($donations, $filename);
        } else {
            $this->export_excel($donations, $filename);
        }
    }

    /**
     * Export to CSV
     */
    private function export_csv($donations, $filename) {
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

    /**
     * Export to Excel (TSV format for compatibility)
     */
    private function export_excel($donations, $filename) {
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

    /**
     * Update donation status
     */
    private function update_donation_status($donation_ids, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';

        $placeholders = implode(',', array_fill(0, count($donation_ids), '%d'));
        $sql = "UPDATE {$table_name} SET payment_status = %s WHERE id IN ({$placeholders})";
        $params = array_merge(array($status), $donation_ids);

        $result = $wpdb->query($wpdb->prepare($sql, $params));

        if ($result !== false) {
            $message = sprintf(
                __('%d donations updated successfully.', 'wp-paypal-donations'),
                count($donation_ids)
            );
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
            });
        }
    }

    /**
     * Delete donations
     */
    private function delete_donations($donation_ids) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';

        $placeholders = implode(',', array_fill(0, count($donation_ids), '%d'));
        $sql = "DELETE FROM {$table_name} WHERE id IN ({$placeholders})";

        $result = $wpdb->query($wpdb->prepare($sql, $donation_ids));

        if ($result !== false) {
            $message = sprintf(
                __('%d donations deleted successfully.', 'wp-paypal-donations'),
                count($donation_ids)
            );
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
            });
        }
    }

    /**
     * Get currency symbol
     */
    private function get_currency_symbol($currency) {
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

    /**
     * Display table navigation and filters
     */
    public function display_tablenav($which) {
        if ('top' === $which) {
            $this->search_box(__('Search donations', 'wp-paypal-donations'), 'donation');
            $this->extra_tablenav($which);
        }
        parent::display_tablenav($which);
    }

    /**
     * Add extra table navigation (filters and export options)
     */
    protected function extra_tablenav($which) {
        if ('top' === $which) {
            echo '<div class="alignleft actions">';
            
            // Date range filter
            echo '<input type="date" name="date_from" value="' . esc_attr(isset($_POST['date_from']) ? $_POST['date_from'] : '') . '" placeholder="' . __('From Date', 'wp-paypal-donations') . '" />';
            echo '<input type="date" name="date_to" value="' . esc_attr(isset($_POST['date_to']) ? $_POST['date_to'] : '') . '" placeholder="' . __('To Date', 'wp-paypal-donations') . '" />';

            // Status filter
            $current_status = isset($_POST['status_filter']) ? $_POST['status_filter'] : '';
            echo '<select name="status_filter">';
            echo '<option value="">' . __('All Statuses', 'wp-paypal-donations') . '</option>';
            echo '<option value="pending"' . selected($current_status, 'pending', false) . '>' . __('Pending', 'wp-paypal-donations') . '</option>';
            echo '<option value="completed"' . selected($current_status, 'completed', false) . '>' . __('Completed', 'wp-paypal-donations') . '</option>';
            echo '<option value="failed"' . selected($current_status, 'failed', false) . '>' . __('Failed', 'wp-paypal-donations') . '</option>';
            echo '</select>';

            submit_button(__('Filter', 'wp-paypal-donations'), 'secondary', 'filter_action', false);

            // Export all button
            echo '<a href="' . wp_nonce_url(admin_url('admin.php?page=wp-paypal-donations&action=export_all_csv'), 'export_all_csv') . '" class="button">' . __('Export All (CSV)', 'wp-paypal-donations') . '</a>';
            echo '<a href="' . wp_nonce_url(admin_url('admin.php?page=wp-paypal-donations&action=export_all_excel'), 'export_all_excel') . '" class="button">' . __('Export All (Excel)', 'wp-paypal-donations') . '</a>';

            echo '</div>';
        }
    }
}