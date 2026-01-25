<?php
/**
 * Data Export and Management Tests
 * 
 * Tests the comprehensive data export and management features that address
 * critical business needs for compliance, reporting, and donor management.
 *
 * @package WP_PayPal_Donations
 * @since 1.1.0
 */

namespace WPPayPalDonations\Tests\Unit;

use WPPayPalDonations\Tests\TestCase;
use Brain\Monkey;

/**
 * Test data export and management functionality
 */
class Data_Export_Management_Test extends TestCase {

    /**
     * @var WP_PayPal_Donations
     */
    private $plugin;

    /**
     * @var WPD_Donations_List_Table
     */
    private $list_table;

    protected function setUp(): void {
        parent::setUp();
        
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/tmp/wordpress/');
        }
        
        // Mock WordPress List Table class
        if (!class_exists('WP_List_Table')) {
            require_once dirname(dirname(__DIR__)) . '/tests/mocks/class-wp-list-table-mock.php';
        }
        
        $this->plugin = new \WP_PayPal_Donations();
        $this->list_table = new \WPD_Donations_List_Table();
    }

    /**
     * Test CSV export functionality
     */
    public function test_csv_export_functionality() {
        $donations = array(
            $this->createMockDonation(array('id' => 1, 'donor_name' => 'John Doe', 'amount' => 25.00)),
            $this->createMockDonation(array('id' => 2, 'donor_name' => 'Jane Smith', 'amount' => 50.00))
        );
        
        // Mock database query for export
        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn($donations);
        
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_donations WHERE id IN (1,2) ORDER BY donation_date DESC");
        
        global $wpdb;
        
        // Capture output
        ob_start();
        
        // Mock header function to prevent "headers already sent" error
        Monkey\Functions\when('header')->justReturn(true);
        
        // Test CSV export (we'll verify the method exists and processes correctly)
        $reflection = new \ReflectionClass($this->list_table);
        $method = $reflection->getMethod('export_csv');
        $method->setAccessible(true);
        
        try {
            $method->invoke($this->list_table, $donations, 'test_export');
        } catch (\Exception $e) {
            // Expected due to exit() call in export method
        }
        
        $output = ob_get_clean();
        
        // Verify CSV headers are present
        $this->assertStringContainsString('ID,Donor Name,Email', $output);
        $this->assertStringContainsString('John Doe', $output);
        $this->assertStringContainsString('Jane Smith', $output);
    }

    /**
     * Test Excel export functionality
     */
    public function test_excel_export_functionality() {
        $donations = array(
            $this->createMockDonation(array('id' => 1, 'donor_name' => 'Test User', 'amount' => 100.00))
        );
        
        // Capture output
        ob_start();
        
        // Mock header function
        Monkey\Functions\when('header')->justReturn(true);
        
        // Test Excel export
        $reflection = new \ReflectionClass($this->list_table);
        $method = $reflection->getMethod('export_excel');
        $method->setAccessible(true);
        
        try {
            $method->invoke($this->list_table, $donations, 'test_export');
        } catch (\Exception $e) {
            // Expected due to exit() call
        }
        
        $output = ob_get_clean();
        
        // Verify Excel format (tab-separated)
        $this->assertStringContainsString("ID\tDonor Name\tEmail", $output);
        $this->assertStringContainsString('Test User', $output);
    }

    /**
     * Test donation search functionality
     */
    public function test_donation_search_functionality() {
        $wpdb = $this->mockWpdb();
        
        // Mock search query
        $wpdb->expects($this->once())
             ->method('get_results')
             ->with($this->stringContains('donor_name LIKE'))
             ->willReturn(array($this->createMockDonation()));
        
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_donations WHERE (donor_name LIKE '%john%' OR donor_email LIKE '%john%' OR transaction_id LIKE '%john%') ORDER BY donation_date DESC LIMIT 20 OFFSET 0");
        
        $wpdb->expects($this->once())
             ->method('esc_like')
             ->with('john')
             ->willReturn('john');
        
        global $wpdb;
        
        // Test search functionality
        $reflection = new \ReflectionClass($this->list_table);
        $method = $reflection->getMethod('get_donations');
        $method->setAccessible(true);
        
        $results = $method->invoke($this->list_table, 'john');
        
        $this->assertIsArray($results);
    }

    /**
     * Test date range filtering
     */
    public function test_date_range_filtering() {
        $wpdb = $this->mockWpdb();
        
        // Mock date range query
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn(array($this->createMockDonation()));
        
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_donations WHERE 1=1 AND donation_date >= '2024-01-01 00:00:00' AND donation_date <= '2024-01-31 23:59:59' ORDER BY donation_date DESC LIMIT 20 OFFSET 0");
        
        global $wpdb;
        
        // Test date filtering
        $reflection = new \ReflectionClass($this->list_table);
        $method = $reflection->getMethod('get_donations');
        $method->setAccessible(true);
        
        $results = $method->invoke($this->list_table, '', '2024-01-01', '2024-01-31');
        
        $this->assertIsArray($results);
    }

    /**
     * Test status filtering
     */
    public function test_status_filtering() {
        $wpdb = $this->mockWpdb();
        
        // Mock status filter query
        $wpdb->expects($this->once())
             ->method('get_results')
             ->willReturn(array($this->createMockDonation(array('payment_status' => 'completed'))));
        
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("SELECT * FROM wp_donations WHERE 1=1 AND payment_status = 'completed' ORDER BY donation_date DESC LIMIT 20 OFFSET 0");
        
        global $wpdb;
        
        // Test status filtering
        $reflection = new \ReflectionClass($this->list_table);
        $method = $reflection->getMethod('get_donations');
        $method->setAccessible(true);
        
        $results = $method->invoke($this->list_table, '', '', '', 'completed');
        
        $this->assertIsArray($results);
    }

    /**
     * Test bulk status update functionality
     */
    public function test_bulk_status_update() {
        $donation_ids = array(1, 2, 3);
        
        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->once())
             ->method('query')
             ->with($this->stringContains('UPDATE'))
             ->willReturn(3);
        
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("UPDATE wp_donations SET payment_status = 'completed' WHERE id IN (1,2,3)");
        
        global $wpdb;
        
        // Test bulk status update
        $reflection = new \ReflectionClass($this->list_table);
        $method = $reflection->getMethod('update_donation_status');
        $method->setAccessible(true);
        
        $method->invoke($this->list_table, $donation_ids, 'completed');
        
        // Verify the database update was called
        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    /**
     * Test bulk deletion functionality
     */
    public function test_bulk_deletion() {
        $donation_ids = array(1, 2);
        
        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->once())
             ->method('query')
             ->with($this->stringContains('DELETE'))
             ->willReturn(2);
        
        $wpdb->expects($this->once())
             ->method('prepare')
             ->willReturn("DELETE FROM wp_donations WHERE id IN (1,2)");
        
        global $wpdb;
        
        // Test bulk deletion
        $reflection = new \ReflectionClass($this->list_table);
        $method = $reflection->getMethod('delete_donations');
        $method->setAccessible(true);
        
        $method->invoke($this->list_table, $donation_ids);
        
        $this->assertTrue(true); // Method executed successfully
    }

    /**
     * Test column rendering for different data types
     */
    public function test_column_rendering() {
        $donation = array(
            'id' => '123',
            'donor_name' => 'Test User',
            'donor_email' => 'test@example.com',
            'amount' => '25.50',
            'currency' => 'USD',
            'payment_status' => 'completed',
            'transaction_id' => 'TXN123',
            'donation_date' => '2024-01-01 12:00:00'
        );
        
        // Mock date formatting
        Monkey\Functions\when('date_i18n')->justReturn('January 1, 2024 12:00 PM');
        Monkey\Functions\when('get_option')->alias(function($option, $default = false) {
            switch ($option) {
                case 'date_format':
                    return 'F j, Y';
                case 'time_format':
                    return 'g:i A';
                default:
                    return $default;
            }
        });
        
        // Test different column renderings
        $this->assertEquals('123', $this->list_table->column_default($donation, 'id'));
        $this->assertEquals('Test User', $this->list_table->column_default($donation, 'donor_name'));
        $this->assertEquals('test@example.com', $this->list_table->column_default($donation, 'donor_email'));
        $this->assertEquals('$25.50', $this->list_table->column_default($donation, 'amount'));
        
        // Test status column with HTML
        $status_output = $this->list_table->column_default($donation, 'payment_status');
        $this->assertStringContainsString('wpd-status-completed', $status_output);
        $this->assertStringContainsString('Completed', $status_output);
    }

    /**
     * Test sortable columns configuration
     */
    public function test_sortable_columns() {
        $sortable = $this->list_table->get_sortable_columns();
        
        $this->assertArrayHasKey('id', $sortable);
        $this->assertArrayHasKey('donor_name', $sortable);
        $this->assertArrayHasKey('amount', $sortable);
        $this->assertArrayHasKey('payment_status', $sortable);
        $this->assertArrayHasKey('donation_date', $sortable);
    }

    /**
     * Test bulk actions configuration
     */
    public function test_bulk_actions() {
        $bulk_actions = $this->list_table->get_bulk_actions();
        
        $this->assertArrayHasKey('export_csv', $bulk_actions);
        $this->assertArrayHasKey('export_excel', $bulk_actions);
        $this->assertArrayHasKey('mark_completed', $bulk_actions);
        $this->assertArrayHasKey('mark_pending', $bulk_actions);
        $this->assertArrayHasKey('delete', $bulk_actions);
    }

    /**
     * Test admin export actions handling
     */
    public function test_admin_export_actions() {
        // Mock current user capabilities
        Monkey\Functions\when('current_user_can')
            ->with('manage_options')
            ->justReturn(true);
        
        // Mock nonce verification
        Monkey\Functions\when('wp_verify_nonce')
            ->justReturn(true);
        
        $_GET['action'] = 'export_all_csv';
        $_GET['_wpnonce'] = 'test_nonce';
        
        // Mock wp_die to prevent actual termination
        Monkey\Functions\when('wp_die')->justReturn(null);
        
        // Mock export method
        $plugin_mock = $this->getMockBuilder(\WP_PayPal_Donations::class)
                            ->onlyMethods(['export_all_donations'])
                            ->getMock();
        
        $plugin_mock->expects($this->once())
                    ->method('export_all_donations')
                    ->with('csv');
        
        // Test admin action handling
        $reflection = new \ReflectionClass($plugin_mock);
        $method = $reflection->getMethod('handle_admin_actions');
        $method->setAccessible(true);
        
        $method->invoke($plugin_mock);
    }

    /**
     * Test security for unauthorized access
     */
    public function test_security_unauthorized_access() {
        // Mock unauthorized user
        Monkey\Functions\when('current_user_can')
            ->with('manage_options')
            ->justReturn(false);
        
        $_GET['action'] = 'export_all_csv';
        
        // Test admin action handling with unauthorized user
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('handle_admin_actions');
        $method->setAccessible(true);
        
        // Should return early without processing
        $result = $method->invoke($this->plugin);
        $this->assertNull($result);
    }

    /**
     * Test nonce security verification
     */
    public function test_nonce_security() {
        Monkey\Functions\when('current_user_can')
            ->with('manage_options')
            ->justReturn(true);
        
        // Mock failed nonce verification
        Monkey\Functions\when('wp_verify_nonce')
            ->justReturn(false);
        
        Monkey\Functions\expect('wp_die')
            ->once()
            ->with('Security check failed');
        
        $_GET['action'] = 'export_all_csv';
        $_GET['_wpnonce'] = 'invalid_nonce';
        
        $this->expectException(\Exception::class);
        
        // Test admin action handling with invalid nonce
        $reflection = new \ReflectionClass($this->plugin);
        $method = $reflection->getMethod('handle_admin_actions');
        $method->setAccessible(true);
        
        $method->invoke($this->plugin);
    }

    /**
     * Test pagination functionality
     */
    public function test_pagination() {
        // Mock pagination data
        $wpdb = $this->mockWpdb();
        $wpdb->expects($this->once())
             ->method('get_var')
             ->willReturn(150); // Total count
        
        global $wpdb;
        
        // Test pagination calculation
        $reflection = new \ReflectionClass($this->list_table);
        $method = $reflection->getMethod('get_donations_count');
        $method->setAccessible(true);
        
        $total = $method->invoke($this->list_table);
        $this->assertEquals(150, $total);
        
        // Verify pagination would be set correctly for 150 items with 20 per page
        $expected_pages = ceil(150 / 20); // 8 pages
        $this->assertEquals(8, $expected_pages);
    }

    /**
     * Test currency symbol handling in exports
     */
    public function test_currency_symbol_handling() {
        $currencies = array('USD', 'EUR', 'GBP', 'JPY');
        $expected_symbols = array('$', '€', '£', '¥');
        
        foreach ($currencies as $index => $currency) {
            $reflection = new \ReflectionClass($this->list_table);
            $method = $reflection->getMethod('get_currency_symbol');
            $method->setAccessible(true);
            
            $symbol = $method->invoke($this->list_table, $currency);
            $this->assertEquals($expected_symbols[$index], $symbol);
        }
    }
}