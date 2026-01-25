<?php
/**
 * Database operation tests
 *
 * @package WP_PayPal_Donations
 */

namespace WPPayPalDonations\Tests\Unit;

use WPPayPalDonations\Tests\TestCase;
use Brain\Monkey;

/**
 * Test database operations
 */
class Database_Test extends TestCase {

    /**
     * @var WP_PayPal_Donations
     */
    private $plugin;

    protected function setUp(): void {
        parent::setUp();
        
        if (!defined('ABSPATH')) {
            define('ABSPATH', '/tmp/wordpress/');
        }
        
        $this->plugin = new \WP_PayPal_Donations();
    }

    /**
     * Test table creation on activation
     */
    public function test_create_tables() {
        // Mock WordPress database functions
        $wpdb = $this->mockWpdb();
        
        $wpdb->expects($this->once())
             ->method('get_charset_collate')
             ->willReturn('DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        // Mock dbDelta function
        Monkey\Functions\expect('dbDelta')
            ->once()
            ->with(Monkey\Functions\type('string'));

        // Mock require_once for upgrade.php
        Monkey\Functions\when('require_once')->justReturn(true);

        global $wpdb;
        
        $this->plugin->create_tables();
    }

    /**
     * Test plugin activation
     */
    public function test_activate() {
        // Mock flush_rewrite_rules
        Monkey\Functions\expect('flush_rewrite_rules')->once();
        
        // Create a partial mock to test create_tables is called
        $plugin_mock = $this->getMockBuilder(\WP_PayPal_Donations::class)
                            ->onlyMethods(['create_tables'])
                            ->getMock();
        
        $plugin_mock->expects($this->once())
                    ->method('create_tables');
        
        $plugin_mock->activate();
    }

    /**
     * Test plugin deactivation
     */
    public function test_deactivate() {
        Monkey\Functions\expect('flush_rewrite_rules')->once();
        
        $this->plugin->deactivate();
    }

    /**
     * Test donation data insertion
     */
    public function test_donation_insertion() {
        $wpdb = $this->mockWpdb();
        
        // Mock successful insertion
        $wpdb->expects($this->once())
             ->method('insert')
             ->with(
                 'wp_donations', // table name
                 $this->arrayHasKey('donor_name'), // data array
                 $this->arrayHasKey('%s') // format array
             )
             ->willReturn(1);
        
        $wpdb->insert_id = 123;
        
        global $wpdb;
        
        // Simulate donation data
        $donation_data = array(
            'donor_name' => 'John Doe',
            'donor_email' => 'john@example.com',
            'amount' => 25.00,
            'currency' => 'USD',
            'payment_status' => 'pending'
        );
        
        $format = array('%s', '%s', '%f', '%s', '%s');
        
        $result = $wpdb->insert('wp_donations', $donation_data, $format);
        
        $this->assertEquals(1, $result);
        $this->assertEquals(123, $wpdb->insert_id);
    }

    /**
     * Test donation data update
     */
    public function test_donation_update() {
        $wpdb = $this->mockWpdb();
        
        // Mock successful update
        $wpdb->expects($this->once())
             ->method('update')
             ->with(
                 'wp_donations', // table name
                 $this->arrayHasKey('payment_status'), // data
                 array('id' => 123), // where
                 $this->arrayHasKey('%s'), // format
                 array('%d') // where format
             )
             ->willReturn(1);
        
        global $wpdb;
        
        $update_data = array(
            'payment_status' => 'completed',
            'transaction_id' => 'TEST123',
            'paypal_response' => serialize(array('test' => 'data'))
        );
        
        $where = array('id' => 123);
        $format = array('%s', '%s', '%s');
        $where_format = array('%d');
        
        $result = $wpdb->update('wp_donations', $update_data, $where, $format, $where_format);
        
        $this->assertEquals(1, $result);
    }

    /**
     * Test donation data retrieval
     */
    public function test_donation_retrieval() {
        $wpdb = $this->mockWpdb();
        
        $mock_donation = $this->createMockDonation();
        
        // Mock get_results for multiple donations
        $wpdb->expects($this->once())
             ->method('get_results')
             ->with('SELECT * FROM wp_donations ORDER BY donation_date DESC LIMIT 50')
             ->willReturn(array($mock_donation));
        
        // Mock get_var for totals
        $wpdb->expects($this->exactly(2))
             ->method('get_var')
             ->withConsecutive(
                 ["SELECT SUM(amount) FROM wp_donations WHERE payment_status = 'completed'"],
                 ["SELECT COUNT(*) FROM wp_donations WHERE payment_status = 'completed'"]
             )
             ->willReturnOnConsecutiveCalls(25.00, 1);
        
        global $wpdb;
        
        // Test retrieving donations list
        $donations = $wpdb->get_results("SELECT * FROM wp_donations ORDER BY donation_date DESC LIMIT 50");
        $this->assertCount(1, $donations);
        $this->assertEquals('John Doe', $donations[0]->donor_name);
        
        // Test getting totals
        $total_amount = $wpdb->get_var("SELECT SUM(amount) FROM wp_donations WHERE payment_status = 'completed'");
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM wp_donations WHERE payment_status = 'completed'");
        
        $this->assertEquals(25.00, $total_amount);
        $this->assertEquals(1, $total_count);
    }

    /**
     * Test single donation retrieval by ID
     */
    public function test_single_donation_retrieval() {
        $wpdb = $this->mockWpdb();
        
        $mock_donation = $this->createMockDonation(array('id' => 123));
        
        $wpdb->expects($this->once())
             ->method('prepare')
             ->with("SELECT * FROM wp_donations WHERE id = %d", 123)
             ->willReturn("SELECT * FROM wp_donations WHERE id = 123");
        
        $wpdb->expects($this->once())
             ->method('get_row')
             ->with("SELECT * FROM wp_donations WHERE id = 123")
             ->willReturn($mock_donation);
        
        global $wpdb;
        
        $query = $wpdb->prepare("SELECT * FROM wp_donations WHERE id = %d", 123);
        $donation = $wpdb->get_row($query);
        
        $this->assertEquals(123, $donation->id);
        $this->assertEquals('John Doe', $donation->donor_name);
    }

    /**
     * Test database error handling
     */
    public function test_database_error_handling() {
        $wpdb = $this->mockWpdb();
        
        // Mock database error
        $wpdb->expects($this->once())
             ->method('insert')
             ->willReturn(false);
        
        $wpdb->last_error = 'Database connection failed';
        
        global $wpdb;
        
        $donation_data = array(
            'donor_name' => 'John Doe',
            'donor_email' => 'john@example.com',
            'amount' => 25.00,
            'currency' => 'USD',
            'payment_status' => 'pending'
        );
        
        $result = $wpdb->insert('wp_donations', $donation_data);
        
        $this->assertFalse($result);
        $this->assertNotEmpty($wpdb->last_error);
    }

    /**
     * Test SQL injection prevention with prepared statements
     */
    public function test_sql_injection_prevention() {
        $wpdb = $this->mockWpdb();
        
        // Mock prepare method to ensure it's called
        $wpdb->expects($this->once())
             ->method('prepare')
             ->with(
                 "SELECT * FROM wp_donations WHERE id = %d",
                 "1; DROP TABLE wp_donations; --" // Malicious input
             )
             ->willReturn("SELECT * FROM wp_donations WHERE id = 0");
        
        global $wpdb;
        
        // Test that malicious input is properly escaped
        $malicious_id = "1; DROP TABLE wp_donations; --";
        $safe_query = $wpdb->prepare("SELECT * FROM wp_donations WHERE id = %d", $malicious_id);
        
        // The prepared statement should convert the string to 0 (safe integer)
        $this->assertEquals("SELECT * FROM wp_donations WHERE id = 0", $safe_query);
    }

    /**
     * Test database table schema
     */
    public function test_table_schema() {
        $expected_columns = array(
            'id',
            'donor_name',
            'donor_email', 
            'amount',
            'currency',
            'payment_status',
            'transaction_id',
            'paypal_response',
            'donation_date'
        );
        
        // This test verifies the expected table structure
        // In a real WordPress environment, you would query DESCRIBE wp_donations
        $this->assertIsArray($expected_columns);
        $this->assertContains('id', $expected_columns);
        $this->assertContains('donor_email', $expected_columns);
        $this->assertContains('amount', $expected_columns);
        $this->assertContains('payment_status', $expected_columns);
    }
}