<?php
/**
 * Analytics Engine Test Suite
 * 
 * Comprehensive unit tests for the WPD_Analytics_Engine class to ensure
 * accurate analytics calculations, data processing, and business intelligence
 * generation for donation management and fundraising optimization.
 * 
 * @package WP_PayPal_Donations
 * @subpackage Tests
 * @since 1.3.0
 */

class Analytics_Engine_Test extends WP_UnitTestCase {
    
    private $analytics_engine;
    private $sample_donations;
    private $table_name;
    
    public function setUp(): void {
        parent::setUp();
        
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'donations';
        
        // Clean existing data
        $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        // Create sample donation data for comprehensive testing
        $this->create_sample_donations();
        
        // Clear any existing analytics cache
        \WPD_Analytics_Engine::clear_analytics_cache();
    }
    
    public function tearDown(): void {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        \WPD_Analytics_Engine::clear_analytics_cache();
        parent::tearDown();
    }
    
    /**
     * Create comprehensive sample donation data for testing
     */
    private function create_sample_donations() {
        global $wpdb;
        
        // Current month donations (January 2026)
        $current_month_donations = [
            ['John Smith', 'john@example.com', 100.00, '2026-01-15 10:30:00'],
            ['Jane Doe', 'jane@example.com', 250.00, '2026-01-18 14:45:00'],
            ['Bob Wilson', 'bob@example.com', 50.00, '2026-01-20 09:15:00'],
            ['Alice Brown', 'alice@example.com', 500.00, '2026-01-22 16:20:00'],
            ['John Smith', 'john@example.com', 75.00, '2026-01-24 11:10:00'], // Recurring donor
        ];
        
        // Last month donations (December 2025)
        $last_month_donations = [
            ['John Smith', 'john@example.com', 150.00, '2025-12-10 10:00:00'],
            ['Sarah Connor', 'sarah@example.com', 200.00, '2025-12-15 13:30:00'],
            ['Mike Johnson', 'mike@example.com', 300.00, '2025-12-20 15:45:00'],
            ['Alice Brown', 'alice@example.com', 600.00, '2025-12-22 14:10:00'], // Major donor
        ];
        
        // Historical donations for trend analysis (past 12 months)
        $historical_donations = [
            ['David Lee', 'david@example.com', 80.00, '2025-11-15 12:00:00'],
            ['Emma Davis', 'emma@example.com', 120.00, '2025-10-12 10:30:00'],
            ['Chris Taylor', 'chris@example.com', 90.00, '2025-09-08 14:20:00'],
            ['Lisa White', 'lisa@example.com', 180.00, '2025-08-25 16:45:00'],
            ['Tom Anderson', 'tom@example.com', 220.00, '2025-07-18 11:15:00'],
            ['Mary Garcia', 'mary@example.com', 160.00, '2025-06-22 09:30:00'],
            ['Kevin Martinez', 'kevin@example.com', 140.00, '2025-05-14 13:40:00'],
            ['Nina Rodriguez', 'nina@example.com', 110.00, '2025-04-09 15:25:00'],
            ['Paul Wilson', 'paul@example.com', 190.00, '2025-03-17 12:50:00'],
            ['Grace Kim', 'grace@example.com', 170.00, '2025-02-11 10:10:00'],
        ];
        
        // At-risk donors (no donations in last 90 days but were active before)
        $at_risk_donations = [
            ['James Miller', 'james@example.com', 250.00, '2025-09-15 11:00:00'],
            ['Jennifer Lopez', 'jennifer@example.com', 180.00, '2025-08-20 14:30:00'],
            ['James Miller', 'james@example.com', 300.00, '2025-07-10 10:45:00'], // Multiple donations
        ];
        
        // Combine all sample data
        $all_donations = array_merge(
            $current_month_donations,
            $last_month_donations,
            $historical_donations,
            $at_risk_donations
        );
        
        // Insert sample donations
        foreach ($all_donations as $donation) {
            $wpdb->insert(
                $this->table_name,
                [
                    'donor_name' => $donation[0],
                    'donor_email' => $donation[1],
                    'amount' => $donation[2],
                    'payment_status' => 'completed',
                    'donation_date' => $donation[3],
                    'transaction_id' => 'TEST_' . uniqid(),
                    'created_at' => $donation[3]
                ]
            );
        }
        
        // Add some pending/failed donations for comprehensive testing
        $wpdb->insert($this->table_name, [
            'donor_name' => 'Failed Donation',
            'donor_email' => 'failed@example.com',
            'amount' => 100.00,
            'payment_status' => 'failed',
            'donation_date' => '2026-01-20 12:00:00',
            'transaction_id' => 'FAILED_TEST',
            'created_at' => '2026-01-20 12:00:00'
        ]);
        
        $this->sample_donations = $all_donations;
    }
    
    /**
     * Test main dashboard analytics generation
     */
    public function test_generate_dashboard_analytics() {
        $analytics = \WPD_Analytics_Engine::generate_dashboard_analytics();
        
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('overview_metrics', $analytics);
        $this->assertArrayHasKey('trend_analysis', $analytics);
        $this->assertArrayHasKey('donor_insights', $analytics);
        $this->assertArrayHasKey('campaign_performance', $analytics);
        $this->assertArrayHasKey('revenue_analysis', $analytics);
        $this->assertArrayHasKey('geographic_insights', $analytics);
        $this->assertArrayHasKey('predictions', $analytics);
        $this->assertArrayHasKey('recommendations', $analytics);
        $this->assertArrayHasKey('benchmark_comparison', $analytics);
        
        // Verify non-empty results
        $this->assertNotEmpty($analytics['overview_metrics']);
        $this->assertNotEmpty($analytics['trend_analysis']);
        $this->assertNotEmpty($analytics['donor_insights']);
    }
    
    /**
     * Test analytics caching functionality
     */
    public function test_analytics_caching() {
        // First call should generate fresh data
        $analytics1 = \WPD_Analytics_Engine::generate_dashboard_analytics();
        
        // Second call should return cached data
        $analytics2 = \WPD_Analytics_Engine::generate_dashboard_analytics();
        
        $this->assertEquals($analytics1, $analytics2);
        
        // Clear cache and verify fresh data is generated
        \WPD_Analytics_Engine::clear_analytics_cache();
        $analytics3 = \WPD_Analytics_Engine::generate_dashboard_analytics();
        
        $this->assertEquals($analytics1, $analytics3);
    }
    
    /**
     * Test overview metrics calculations
     */
    public function test_get_overview_metrics() {
        $metrics = \WPD_Analytics_Engine::get_overview_metrics();
        
        $this->assertIsArray($metrics);
        
        // Test basic metric structure
        $this->assertArrayHasKey('total_raised', $metrics);
        $this->assertArrayHasKey('total_donations', $metrics);
        $this->assertArrayHasKey('unique_donors', $metrics);
        $this->assertArrayHasKey('average_donation', $metrics);
        $this->assertArrayHasKey('current_month', $metrics);
        $this->assertArrayHasKey('last_month', $metrics);
        $this->assertArrayHasKey('growth_rates', $metrics);
        $this->assertArrayHasKey('ytd_raised', $metrics);
        $this->assertArrayHasKey('retention_rate', $metrics);
        
        // Test data types
        $this->assertIsFloat($metrics['total_raised']);
        $this->assertIsInt($metrics['total_donations']);
        $this->assertIsInt($metrics['unique_donors']);
        $this->assertIsFloat($metrics['average_donation']);
        
        // Test current month structure
        $this->assertArrayHasKey('raised', $metrics['current_month']);
        $this->assertArrayHasKey('donations', $metrics['current_month']);
        $this->assertArrayHasKey('average', $metrics['current_month']);
        
        // Test growth rates structure
        $this->assertArrayHasKey('revenue', $metrics['growth_rates']);
        $this->assertArrayHasKey('donations', $metrics['growth_rates']);
        $this->assertArrayHasKey('average', $metrics['growth_rates']);
        
        // Verify calculations with known data
        $this->assertGreaterThan(0, $metrics['total_raised']);
        $this->assertGreaterThan(0, $metrics['total_donations']);
        $this->assertGreaterThan(0, $metrics['unique_donors']);
        
        // Verify average donation calculation
        $expected_average = $metrics['total_raised'] / $metrics['total_donations'];
        $this->assertEquals($expected_average, $metrics['average_donation'], '', 0.01);
    }
    
    /**
     * Test trend analysis over 12 months
     */
    public function test_get_trend_analysis() {
        $trends = \WPD_Analytics_Engine::get_trend_analysis();
        
        $this->assertIsArray($trends);
        $this->assertArrayHasKey('monthly_trends', $trends);
        $this->assertArrayHasKey('seasonal_patterns', $trends);
        $this->assertArrayHasKey('growth_trajectory', $trends);
        
        // Test monthly trends structure
        $monthly_trends = $trends['monthly_trends'];
        $this->assertCount(12, $monthly_trends); // 12 months of data
        
        foreach ($monthly_trends as $month_data) {
            $this->assertArrayHasKey('month', $month_data);
            $this->assertArrayHasKey('month_name', $month_data);
            $this->assertArrayHasKey('donations', $month_data);
            $this->assertArrayHasKey('revenue', $month_data);
            $this->assertArrayHasKey('average', $month_data);
            $this->assertArrayHasKey('unique_donors', $month_data);
            
            // Test data types
            $this->assertIsInt($month_data['donations']);
            $this->assertIsFloat($month_data['revenue']);
            $this->assertIsFloat($month_data['average']);
            $this->assertIsInt($month_data['unique_donors']);
        }
        
        // Test seasonal patterns
        $seasonal = $trends['seasonal_patterns'];
        $this->assertArrayHasKey('Q1', $seasonal);
        $this->assertArrayHasKey('Q2', $seasonal);
        $this->assertArrayHasKey('Q3', $seasonal);
        $this->assertArrayHasKey('Q4', $seasonal);
        
        // Test growth trajectory
        $trajectory = $trends['growth_trajectory'];
        $valid_trajectories = ['insufficient_data', 'strong_growth', 'moderate_growth', 'stable', 'moderate_decline', 'significant_decline'];
        $this->assertContains($trajectory, $valid_trajectories);
    }
    
    /**
     * Test comprehensive donor insights and segmentation
     */
    public function test_get_donor_insights() {
        $insights = \WPD_Analytics_Engine::get_donor_insights();
        
        $this->assertIsArray($insights);
        $this->assertArrayHasKey('total_donors', $insights);
        $this->assertArrayHasKey('major_donors', $insights);
        $this->assertArrayHasKey('recurring_donors', $insights);
        $this->assertArrayHasKey('new_donors', $insights);
        $this->assertArrayHasKey('at_risk_donors', $insights);
        $this->assertArrayHasKey('lifetime_value', $insights);
        
        // Test major donors
        $major_donors = $insights['major_donors'];
        $this->assertArrayHasKey('count', $major_donors);
        $this->assertArrayHasKey('list', $major_donors);
        $this->assertArrayHasKey('total_contribution', $major_donors);
        
        $this->assertIsInt($major_donors['count']);
        $this->assertIsArray($major_donors['list']);
        $this->assertIsFloat($major_donors['total_contribution']);
        
        // Test recurring donors
        $recurring_donors = $insights['recurring_donors'];
        $this->assertArrayHasKey('count', $recurring_donors);
        $this->assertArrayHasKey('list', $recurring_donors);
        $this->assertArrayHasKey('retention_rate', $recurring_donors);
        
        $this->assertIsInt($recurring_donors['count']);
        $this->assertIsArray($recurring_donors['list']);
        $this->assertIsFloat($recurring_donors['retention_rate']);
        
        // Test new donors
        $new_donors = $insights['new_donors'];
        $this->assertArrayHasKey('count', $new_donors);
        $this->assertArrayHasKey('list', $new_donors);
        $this->assertArrayHasKey('avg_first_donation', $new_donors);
        
        // Test at-risk donors
        $at_risk_donors = $insights['at_risk_donors'];
        $this->assertArrayHasKey('count', $at_risk_donors);
        $this->assertArrayHasKey('list', $at_risk_donors);
        $this->assertArrayHasKey('potential_loss', $at_risk_donors);
        
        $this->assertIsInt($at_risk_donors['count']);
        $this->assertIsArray($at_risk_donors['list']);
        $this->assertIsFloat($at_risk_donors['potential_loss']);
        
        // Test lifetime value
        $lifetime_value = $insights['lifetime_value'];
        $this->assertArrayHasKey('average', $lifetime_value);
        $this->assertArrayHasKey('median', $lifetime_value);
        
        // Verify at-risk donors identification (James Miller should be identified)
        $this->assertGreaterThan(0, $at_risk_donors['count']);
    }
    
    /**
     * Test campaign performance analysis
     */
    public function test_get_campaign_performance() {
        $performance = \WPD_Analytics_Engine::get_campaign_performance();
        
        $this->assertIsArray($performance);
        $this->assertArrayHasKey('daily_trends', $performance);
        $this->assertArrayHasKey('hourly_patterns', $performance);
        $this->assertArrayHasKey('weekday_patterns', $performance);
        $this->assertArrayHasKey('peak_times', $performance);
        $this->assertArrayHasKey('optimization_opportunities', $performance);
        
        // Test daily trends structure
        $daily_trends = $performance['daily_trends'];
        $this->assertIsArray($daily_trends);
        
        foreach ($daily_trends as $day) {
            $this->assertArrayHasKey('date', $day);
            $this->assertArrayHasKey('donations', $day);
            $this->assertArrayHasKey('revenue', $day);
            $this->assertArrayHasKey('average', $day);
        }
        
        // Test hourly patterns (24 hours)
        $hourly_patterns = $performance['hourly_patterns'];
        $this->assertIsArray($hourly_patterns);
        $this->assertLessThanOrEqual(24, count($hourly_patterns));
        
        foreach ($hourly_patterns as $hour) {
            $this->assertArrayHasKey('hour', $hour);
            $this->assertArrayHasKey('donations', $hour);
            $this->assertArrayHasKey('revenue', $hour);
            $this->assertGreaterThanOrEqual(0, $hour['hour']);
            $this->assertLessThanOrEqual(23, $hour['hour']);
        }
        
        // Test weekday patterns (7 days)
        $weekday_patterns = $performance['weekday_patterns'];
        $this->assertIsArray($weekday_patterns);
        $this->assertLessThanOrEqual(7, count($weekday_patterns));
        
        // Test peak times
        $peak_times = $performance['peak_times'];
        $this->assertArrayHasKey('best_hour', $peak_times);
        $this->assertArrayHasKey('best_day', $peak_times);
        
        // Test optimization opportunities
        $opportunities = $performance['optimization_opportunities'];
        $this->assertIsArray($opportunities);
    }
    
    /**
     * Test revenue analysis and projections
     */
    public function test_get_revenue_analysis() {
        $revenue = \WPD_Analytics_Engine::get_revenue_analysis();
        
        $this->assertIsArray($revenue);
        $this->assertArrayHasKey('velocity_metrics', $revenue);
        $this->assertArrayHasKey('projections', $revenue);
        $this->assertArrayHasKey('concentration', $revenue);
        $this->assertArrayHasKey('growth_analysis', $revenue);
        
        // Test velocity metrics
        $velocity = $revenue['velocity_metrics'];
        $this->assertArrayHasKey('daily_average', $velocity);
        $this->assertArrayHasKey('weekly_average', $velocity);
        $this->assertArrayHasKey('monthly_average', $velocity);
        
        $this->assertIsFloat($velocity['daily_average']);
        $this->assertIsFloat($velocity['weekly_average']);
        $this->assertIsFloat($velocity['monthly_average']);
        
        // Verify weekly = daily * 7, monthly = daily * 30
        $this->assertEquals($velocity['daily_average'] * 7, $velocity['weekly_average'], '', 0.01);
        $this->assertEquals($velocity['daily_average'] * 30, $velocity['monthly_average'], '', 0.01);
        
        // Test projections
        $projections = $revenue['projections'];
        $this->assertArrayHasKey('month_end_projection', $projections);
        $this->assertArrayHasKey('month_end_vs_last', $projections);
        $this->assertArrayHasKey('year_end_projection', $projections);
        $this->assertArrayHasKey('ytd_performance', $projections);
        
        // Test concentration analysis
        $concentration = $revenue['concentration'];
        $this->assertIsArray($concentration);
        
        foreach ($concentration as $range) {
            $this->assertArrayHasKey('range', $range);
            $this->assertArrayHasKey('count', $range);
            $this->assertArrayHasKey('total', $range);
            $this->assertArrayHasKey('percentage', $range);
        }
        
        // Test growth analysis
        $growth_analysis = $revenue['growth_analysis'];
        if ($growth_analysis !== 'insufficient_data') {
            $this->assertArrayHasKey('average_growth_rate', $growth_analysis);
            $this->assertArrayHasKey('volatility', $growth_analysis);
            $this->assertArrayHasKey('sustainability', $growth_analysis);
        }
    }
    
    /**
     * Test predictive insights and forecasting
     */
    public function test_get_predictive_insights() {
        $predictions = \WPD_Analytics_Engine::get_predictive_insights();
        
        $this->assertIsArray($predictions);
        $this->assertArrayHasKey('trend_direction', $predictions);
        $this->assertArrayHasKey('growth_rate', $predictions);
        $this->assertArrayHasKey('predictions', $predictions);
        $this->assertArrayHasKey('seasonality_forecast', $predictions);
        
        // Test trend direction
        $valid_directions = ['growing', 'declining', 'stable'];
        $this->assertContains($predictions['trend_direction'], $valid_directions);
        
        // Test growth rate
        $this->assertIsFloat($predictions['growth_rate']);
        
        // Test predictions structure
        $future_predictions = $predictions['predictions'];
        $this->assertIsArray($future_predictions);
        $this->assertCount(3, $future_predictions); // 3 months ahead
        
        foreach ($future_predictions as $prediction) {
            $this->assertArrayHasKey('month', $prediction);
            $this->assertArrayHasKey('predicted_revenue', $prediction);
            $this->assertArrayHasKey('confidence', $prediction);
            
            $valid_confidence = ['high', 'medium', 'low'];
            $this->assertContains($prediction['confidence'], $valid_confidence);
        }
        
        // Test seasonality forecast
        $seasonality = $predictions['seasonality_forecast'];
        $this->assertIsArray($seasonality);
    }
    
    /**
     * Test actionable recommendations generation
     */
    public function test_get_actionable_recommendations() {
        $recommendations = \WPD_Analytics_Engine::get_actionable_recommendations();
        
        $this->assertIsArray($recommendations);
        
        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('category', $recommendation);
            $this->assertArrayHasKey('priority', $recommendation);
            $this->assertArrayHasKey('title', $recommendation);
            $this->assertArrayHasKey('description', $recommendation);
            $this->assertArrayHasKey('action_items', $recommendation);
            $this->assertArrayHasKey('potential_impact', $recommendation);
            
            // Test data types
            $this->assertIsString($recommendation['category']);
            $this->assertIsString($recommendation['priority']);
            $this->assertIsString($recommendation['title']);
            $this->assertIsString($recommendation['description']);
            $this->assertIsArray($recommendation['action_items']);
            $this->assertIsString($recommendation['potential_impact']);
            
            // Test priority values
            $valid_priorities = ['high', 'medium', 'low'];
            $this->assertContains($recommendation['priority'], $valid_priorities);
            
            // Test action items are not empty
            $this->assertNotEmpty($recommendation['action_items']);
        }
    }
    
    /**
     * Test benchmark comparison against industry standards
     */
    public function test_get_benchmark_comparison() {
        $benchmarks = \WPD_Analytics_Engine::get_benchmark_comparison();
        
        $this->assertIsArray($benchmarks);
        $this->assertArrayHasKey('retention_rate', $benchmarks);
        $this->assertArrayHasKey('revenue_growth', $benchmarks);
        $this->assertArrayHasKey('major_donor_concentration', $benchmarks);
        $this->assertArrayHasKey('overall_score', $benchmarks);
        
        // Test retention rate benchmark
        $retention = $benchmarks['retention_rate'];
        $this->assertArrayHasKey('your_performance', $retention);
        $this->assertArrayHasKey('industry_benchmark', $retention);
        $this->assertArrayHasKey('performance_vs_benchmark', $retention);
        $this->assertArrayHasKey('status', $retention);
        
        $valid_statuses = ['above', 'below'];
        $this->assertContains($retention['status'], $valid_statuses);
        
        // Test revenue growth benchmark
        $growth = $benchmarks['revenue_growth'];
        $this->assertArrayHasKey('your_performance', $growth);
        $this->assertArrayHasKey('industry_benchmark', $growth);
        $this->assertArrayHasKey('performance_vs_benchmark', $growth);
        $this->assertArrayHasKey('status', $growth);
        
        $this->assertContains($growth['status'], $valid_statuses);
        
        // Test major donor concentration
        $concentration = $benchmarks['major_donor_concentration'];
        $this->assertArrayHasKey('your_performance', $concentration);
        $this->assertArrayHasKey('industry_benchmark', $concentration);
        $this->assertArrayHasKey('performance_vs_benchmark', $concentration);
        $this->assertArrayHasKey('status', $concentration);
        
        // Test overall score
        $overall_score = $benchmarks['overall_score'];
        $this->assertIsFloat($overall_score);
        $this->assertGreaterThanOrEqual(0, $overall_score);
        $this->assertLessThanOrEqual(100, $overall_score);
    }
    
    /**
     * Test growth rate calculation helper method
     */
    public function test_calculate_growth_rate() {
        // Use reflection to access private method
        $reflection = new ReflectionClass('\WPD_Analytics_Engine');
        $method = $reflection->getMethod('calculate_growth_rate');
        $method->setAccessible(true);
        
        // Test normal growth calculation
        $growth = $method->invoke(null, 120, 100);
        $this->assertEquals(0.2, $growth, '', 0.001); // 20% growth
        
        // Test negative growth
        $decline = $method->invoke(null, 80, 100);
        $this->assertEquals(-0.2, $decline, '', 0.001); // 20% decline
        
        // Test zero previous value (should return 1.0 for any current > 0)
        $new_growth = $method->invoke(null, 100, 0);
        $this->assertEquals(1.0, $new_growth);
        
        // Test zero current and previous (should return 0)
        $no_change = $method->invoke(null, 0, 0);
        $this->assertEquals(0.0, $no_change);
    }
    
    /**
     * Test returning donor count calculation
     */
    public function test_get_returning_donor_count() {
        $reflection = new ReflectionClass('\WPD_Analytics_Engine');
        $method = $reflection->getMethod('get_returning_donor_count');
        $method->setAccessible(true);
        
        $returning_count = $method->invoke(null);
        $this->assertIsInt($returning_count);
        $this->assertGreaterThan(0, $returning_count); // We have John Smith with multiple donations
    }
    
    /**
     * Test median calculation helper method
     */
    public function test_calculate_median() {
        $reflection = new ReflectionClass('\WPD_Analytics_Engine');
        $method = $reflection->getMethod('calculate_median');
        $method->setAccessible(true);
        
        // Test odd number of values
        $median_odd = $method->invoke(null, [1, 2, 3, 4, 5]);
        $this->assertEquals(3, $median_odd);
        
        // Test even number of values
        $median_even = $method->invoke(null, [1, 2, 3, 4]);
        $this->assertEquals(2.5, $median_even);
        
        // Test single value
        $median_single = $method->invoke(null, [42]);
        $this->assertEquals(42, $median_single);
        
        // Test empty array
        $median_empty = $method->invoke(null, []);
        $this->assertEquals(0, $median_empty);
    }
    
    /**
     * Test standard deviation calculation
     */
    public function test_calculate_standard_deviation() {
        $reflection = new ReflectionClass('\WPD_Analytics_Engine');
        $method = $reflection->getMethod('calculate_standard_deviation');
        $method->setAccessible(true);
        
        // Test known values
        $std_dev = $method->invoke(null, [2, 4, 4, 4, 5, 5, 7, 9]);
        $this->assertEquals(2, $std_dev, '', 0.1); // Approximately 2
        
        // Test single value (should return 0)
        $std_dev_single = $method->invoke(null, [5]);
        $this->assertEquals(0, $std_dev_single);
        
        // Test empty array
        $std_dev_empty = $method->invoke(null, []);
        $this->assertEquals(0, $std_dev_empty);
    }
    
    /**
     * Test seasonal pattern analysis
     */
    public function test_analyze_seasonal_patterns() {
        $reflection = new ReflectionClass('\WPD_Analytics_Engine');
        $method = $reflection->getMethod('analyze_seasonal_patterns');
        $method->setAccessible(true);
        
        // Create test trend data
        $test_trends = [
            ['month' => '2025-01', 'revenue' => 1000],
            ['month' => '2025-02', 'revenue' => 1200],
            ['month' => '2025-03', 'revenue' => 1100],
            ['month' => '2025-04', 'revenue' => 900],
            ['month' => '2025-05', 'revenue' => 950],
            ['month' => '2025-06', 'revenue' => 1050],
            ['month' => '2025-07', 'revenue' => 800],
            ['month' => '2025-08', 'revenue' => 850],
            ['month' => '2025-09', 'revenue' => 900],
            ['month' => '2025-10', 'revenue' => 1300],
            ['month' => '2025-11', 'revenue' => 1400],
            ['month' => '2025-12', 'revenue' => 1500],
        ];
        
        $seasonal = $method->invoke(null, $test_trends);
        
        $this->assertArrayHasKey('Q1', $seasonal);
        $this->assertArrayHasKey('Q2', $seasonal);
        $this->assertArrayHasKey('Q3', $seasonal);
        $this->assertArrayHasKey('Q4', $seasonal);
        
        // Verify Q1 average (Jan, Feb, Mar)
        $expected_q1 = (1000 + 1200 + 1100) / 3;
        $this->assertEquals($expected_q1, $seasonal['Q1'], '', 0.01);
    }
    
    /**
     * Test cache clearing functionality
     */
    public function test_clear_analytics_cache() {
        // Generate analytics to create cache
        $analytics = \WPD_Analytics_Engine::generate_dashboard_analytics();
        
        // Verify cache exists by checking transient
        $cache_key = 'wpd_dashboard_analytics_' . date('Y-m-d-H');
        $cached_data = get_transient($cache_key);
        $this->assertNotFalse($cached_data);
        
        // Clear cache
        \WPD_Analytics_Engine::clear_analytics_cache();
        
        // Verify cache is cleared
        $cached_data_after = get_transient($cache_key);
        $this->assertFalse($cached_data_after);
    }
    
    /**
     * Test analytics with no data (edge case)
     */
    public function test_analytics_with_no_data() {
        // Clear all data
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        \WPD_Analytics_Engine::clear_analytics_cache();
        
        $analytics = \WPD_Analytics_Engine::generate_dashboard_analytics();
        
        // Should not crash and return valid structure
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('overview_metrics', $analytics);
        
        $overview = $analytics['overview_metrics'];
        $this->assertEquals(0, $overview['total_raised']);
        $this->assertEquals(0, $overview['total_donations']);
        $this->assertEquals(0, $overview['unique_donors']);
    }
    
    /**
     * Test analytics performance under load
     */
    public function test_analytics_performance() {
        $start_time = microtime(true);
        
        // Generate analytics multiple times
        for ($i = 0; $i < 5; $i++) {
            \WPD_Analytics_Engine::clear_analytics_cache();
            $analytics = \WPD_Analytics_Engine::generate_dashboard_analytics();
        }
        
        $execution_time = microtime(true) - $start_time;
        
        // Should complete within reasonable time (less than 5 seconds for 5 runs)
        $this->assertLessThan(5.0, $execution_time, 
            'Analytics generation should be performant');
    }
    
    /**
     * Test data integrity and consistency
     */
    public function test_analytics_data_integrity() {
        $analytics = \WPD_Analytics_Engine::generate_dashboard_analytics();
        
        $overview = $analytics['overview_metrics'];
        $trends = $analytics['trend_analysis'];
        $insights = $analytics['donor_insights'];
        
        // Total donors should match between overview and insights
        $this->assertEquals($overview['unique_donors'], $insights['total_donors']);
        
        // Monthly trend data should be consistent
        $monthly_trends = $trends['monthly_trends'];
        $current_month_trend = end($monthly_trends);
        
        // Current month data should be reasonable (not exact due to date calculations)
        $this->assertGreaterThanOrEqual(0, $current_month_trend['revenue']);
        $this->assertGreaterThanOrEqual(0, $current_month_trend['donations']);
        
        // Revenue concentration should sum to 100%
        $revenue_analysis = $analytics['revenue_analysis'];
        if (isset($revenue_analysis['concentration'])) {
            $total_percentage = array_sum(array_column($revenue_analysis['concentration'], 'percentage'));
            $this->assertEquals(100, $total_percentage, '', 0.1);
        }
    }
}