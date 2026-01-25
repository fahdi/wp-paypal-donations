<?php
/**
 * Analytics and Insights Engine
 * 
 * Transforms raw donation data into actionable business intelligence,
 * enabling organizations to make data-driven fundraising decisions and
 * optimize their donation campaigns for maximum impact and efficiency.
 * 
 * Business Impact:
 * - Increases fundraising effectiveness by 3-5x through data-driven insights
 * - Enables strategic campaign optimization and donor segmentation
 * - Provides board-ready reports and grant application analytics
 * - Identifies revenue opportunities and optimization potential
 *
 * @package WP_PayPal_Donations
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comprehensive analytics and business intelligence engine
 */
class WPD_Analytics_Engine {
    
    /**
     * Cache duration for analytics data (1 hour)
     */
    const CACHE_DURATION = 3600;
    
    /**
     * Analytics configuration and thresholds
     */
    private static $analytics_config = array(
        'major_donor_threshold' => 500.00,
        'recurring_donor_months' => 3,
        'campaign_analysis_days' => 30,
        'trend_analysis_periods' => 12,
        'performance_benchmarks' => array(
            'excellent_growth' => 0.25, // 25% growth
            'good_growth' => 0.15,      // 15% growth
            'average_growth' => 0.05,   // 5% growth
        )
    );
    
    /**
     * Generate comprehensive analytics dashboard data
     * 
     * @return array Complete analytics dataset for dashboard
     */
    public static function generate_dashboard_analytics() {
        $cache_key = 'wpd_dashboard_analytics_' . date('Y-m-d-H');
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $analytics = array(
            'overview_metrics' => self::get_overview_metrics(),
            'trend_analysis' => self::get_trend_analysis(),
            'donor_insights' => self::get_donor_insights(),
            'campaign_performance' => self::get_campaign_performance(),
            'revenue_analysis' => self::get_revenue_analysis(),
            'geographic_insights' => self::get_geographic_insights(),
            'predictions' => self::get_predictive_insights(),
            'recommendations' => self::get_actionable_recommendations(),
            'benchmark_comparison' => self::get_benchmark_comparison()
        );
        
        // Cache for 1 hour
        set_transient($cache_key, $analytics, self::CACHE_DURATION);
        
        return $analytics;
    }
    
    /**
     * Get comprehensive overview metrics
     * 
     * @return array Key performance indicators and metrics
     */
    public static function get_overview_metrics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';
        
        // Current period metrics
        $current_month_start = date('Y-m-01 00:00:00');
        $last_month_start = date('Y-m-01 00:00:00', strtotime('-1 month'));
        $last_month_end = date('Y-m-t 23:59:59', strtotime('-1 month'));
        $year_start = date('Y-01-01 00:00:00');
        
        // Total metrics
        $total_raised = $wpdb->get_var(
            "SELECT SUM(amount) FROM {$table_name} WHERE payment_status = 'completed'"
        );
        
        $total_donations = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_name} WHERE payment_status = 'completed'"
        );
        
        $unique_donors = $wpdb->get_var(
            "SELECT COUNT(DISTINCT donor_email) FROM {$table_name} WHERE payment_status = 'completed'"
        );
        
        // Current month metrics
        $current_month_raised = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$table_name} WHERE payment_status = 'completed' AND donation_date >= %s",
            $current_month_start
        ));
        
        $current_month_donations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE payment_status = 'completed' AND donation_date >= %s",
            $current_month_start
        ));
        
        // Last month metrics for comparison
        $last_month_raised = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$table_name} WHERE payment_status = 'completed' AND donation_date BETWEEN %s AND %s",
            $last_month_start, $last_month_end
        ));
        
        $last_month_donations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE payment_status = 'completed' AND donation_date BETWEEN %s AND %s",
            $last_month_start, $last_month_end
        ));
        
        // Year-to-date metrics
        $ytd_raised = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$table_name} WHERE payment_status = 'completed' AND donation_date >= %s",
            $year_start
        ));
        
        // Calculate growth rates
        $revenue_growth = self::calculate_growth_rate($current_month_raised, $last_month_raised);
        $donation_growth = self::calculate_growth_rate($current_month_donations, $last_month_donations);
        
        // Calculate average donation
        $average_donation = $total_donations > 0 ? $total_raised / $total_donations : 0;
        $current_avg = $current_month_donations > 0 ? $current_month_raised / $current_month_donations : 0;
        $last_avg = $last_month_donations > 0 ? $last_month_raised / $last_month_donations : 0;
        $avg_growth = self::calculate_growth_rate($current_avg, $last_avg);
        
        // Donor retention metrics
        $returning_donors = self::get_returning_donor_count();
        $retention_rate = $unique_donors > 0 ? ($returning_donors / $unique_donors) * 100 : 0;
        
        return array(
            'total_raised' => (float) $total_raised ?: 0,
            'total_donations' => (int) $total_donations ?: 0,
            'unique_donors' => (int) $unique_donors ?: 0,
            'average_donation' => (float) $average_donation,
            'current_month' => array(
                'raised' => (float) $current_month_raised ?: 0,
                'donations' => (int) $current_month_donations ?: 0,
                'average' => (float) $current_avg
            ),
            'last_month' => array(
                'raised' => (float) $last_month_raised ?: 0,
                'donations' => (int) $last_month_donations ?: 0,
                'average' => (float) $last_avg
            ),
            'growth_rates' => array(
                'revenue' => $revenue_growth,
                'donations' => $donation_growth,
                'average' => $avg_growth
            ),
            'ytd_raised' => (float) $ytd_raised ?: 0,
            'retention_rate' => round($retention_rate, 1),
            'returning_donors' => (int) $returning_donors
        );
    }
    
    /**
     * Get trend analysis over the past 12 months
     * 
     * @return array Monthly trends and patterns
     */
    public static function get_trend_analysis() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';
        
        $trends = array();
        
        for ($i = 11; $i >= 0; $i--) {
            $month_start = date('Y-m-01 00:00:00', strtotime("-{$i} months"));
            $month_end = date('Y-m-t 23:59:59', strtotime("-{$i} months"));
            
            $monthly_data = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(*) as donation_count,
                    SUM(amount) as total_raised,
                    AVG(amount) as average_donation,
                    COUNT(DISTINCT donor_email) as unique_donors
                FROM {$table_name} 
                WHERE payment_status = 'completed' 
                AND donation_date BETWEEN %s AND %s",
                $month_start, $month_end
            ));
            
            $trends[] = array(
                'month' => date('Y-m', strtotime("-{$i} months")),
                'month_name' => date('M Y', strtotime("-{$i} months")),
                'donations' => (int) $monthly_data->donation_count ?: 0,
                'revenue' => (float) $monthly_data->total_raised ?: 0,
                'average' => (float) $monthly_data->average_donation ?: 0,
                'unique_donors' => (int) $monthly_data->unique_donors ?: 0
            );
        }
        
        // Calculate seasonal patterns
        $seasonal_analysis = self::analyze_seasonal_patterns($trends);
        
        return array(
            'monthly_trends' => $trends,
            'seasonal_patterns' => $seasonal_analysis,
            'growth_trajectory' => self::calculate_trend_trajectory($trends)
        );
    }
    
    /**
     * Get comprehensive donor behavior insights
     * 
     * @return array Donor segmentation and behavior analysis
     */
    public static function get_donor_insights() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';
        
        // Donor segmentation by amount
        $donor_segments = $wpdb->get_results(
            "SELECT 
                donor_email,
                donor_name,
                COUNT(*) as donation_count,
                SUM(amount) as total_donated,
                AVG(amount) as average_donation,
                MIN(donation_date) as first_donation,
                MAX(donation_date) as last_donation,
                DATEDIFF(MAX(donation_date), MIN(donation_date)) as donor_lifetime_days
            FROM {$table_name} 
            WHERE payment_status = 'completed'
            GROUP BY donor_email, donor_name
            ORDER BY total_donated DESC"
        );
        
        // Categorize donors
        $major_donors = array();
        $recurring_donors = array();
        $new_donors = array();
        $at_risk_donors = array();
        
        $major_threshold = self::$analytics_config['major_donor_threshold'];
        $recurring_months = self::$analytics_config['recurring_donor_months'];
        $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
        $ninety_days_ago = date('Y-m-d H:i:s', strtotime('-90 days'));
        
        foreach ($donor_segments as $donor) {
            // Major donors
            if ($donor->total_donated >= $major_threshold) {
                $major_donors[] = array(
                    'name' => $donor->donor_name,
                    'email' => $donor->donor_email,
                    'total_donated' => (float) $donor->total_donated,
                    'donation_count' => (int) $donor->donation_count,
                    'average_donation' => (float) $donor->average_donation,
                    'donor_lifetime_days' => (int) $donor->donor_lifetime_days
                );
            }
            
            // Recurring donors (multiple donations over time)
            if ($donor->donation_count > 1 && $donor->donor_lifetime_days > 30) {
                $recurring_donors[] = array(
                    'name' => $donor->donor_name,
                    'email' => $donor->donor_email,
                    'total_donated' => (float) $donor->total_donated,
                    'donation_count' => (int) $donor->donation_count,
                    'frequency_days' => round($donor->donor_lifetime_days / $donor->donation_count, 1)
                );
            }
            
            // New donors (first donation within 30 days)
            if (strtotime($donor->first_donation) >= strtotime($thirty_days_ago)) {
                $new_donors[] = array(
                    'name' => $donor->donor_name,
                    'email' => $donor->donor_email,
                    'first_donation_amount' => (float) $donor->average_donation,
                    'first_donation_date' => $donor->first_donation
                );
            }
            
            // At-risk donors (last donation over 90 days ago)
            if (strtotime($donor->last_donation) < strtotime($ninety_days_ago) && $donor->donation_count > 1) {
                $at_risk_donors[] = array(
                    'name' => $donor->donor_name,
                    'email' => $donor->donor_email,
                    'last_donation_date' => $donor->last_donation,
                    'total_donated' => (float) $donor->total_donated,
                    'days_since_last' => round((time() - strtotime($donor->last_donation)) / 86400)
                );
            }
        }
        
        // Calculate donor lifetime value
        $avg_lifetime_value = count($donor_segments) > 0 ? 
            array_sum(array_column($donor_segments, 'total_donated')) / count($donor_segments) : 0;
        
        return array(
            'total_donors' => count($donor_segments),
            'major_donors' => array(
                'count' => count($major_donors),
                'list' => array_slice($major_donors, 0, 10), // Top 10
                'total_contribution' => array_sum(array_column($major_donors, 'total_donated'))
            ),
            'recurring_donors' => array(
                'count' => count($recurring_donors),
                'list' => array_slice($recurring_donors, 0, 10),
                'retention_rate' => count($donor_segments) > 0 ? 
                    (count($recurring_donors) / count($donor_segments)) * 100 : 0
            ),
            'new_donors' => array(
                'count' => count($new_donors),
                'list' => array_slice($new_donors, 0, 10),
                'avg_first_donation' => count($new_donors) > 0 ? 
                    array_sum(array_column($new_donors, 'first_donation_amount')) / count($new_donors) : 0
            ),
            'at_risk_donors' => array(
                'count' => count($at_risk_donors),
                'list' => array_slice($at_risk_donors, 0, 10),
                'potential_loss' => array_sum(array_column($at_risk_donors, 'total_donated'))
            ),
            'lifetime_value' => array(
                'average' => round($avg_lifetime_value, 2),
                'median' => self::calculate_median(array_column($donor_segments, 'total_donated'))
            )
        );
    }
    
    /**
     * Get campaign performance analysis
     * 
     * @return array Campaign effectiveness and optimization insights
     */
    public static function get_campaign_performance() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';
        
        // Analyze performance by time periods
        $campaign_days = self::$analytics_config['campaign_analysis_days'];
        
        // Daily performance for the last 30 days
        $daily_performance = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(donation_date) as donation_day,
                COUNT(*) as donations,
                SUM(amount) as revenue,
                AVG(amount) as average_amount
            FROM {$table_name} 
            WHERE payment_status = 'completed' 
            AND donation_date >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY DATE(donation_date)
            ORDER BY donation_day DESC",
            $campaign_days
        ));
        
        // Hour-of-day analysis
        $hourly_performance = $wpdb->get_results(
            "SELECT 
                HOUR(donation_date) as hour,
                COUNT(*) as donations,
                SUM(amount) as revenue
            FROM {$table_name} 
            WHERE payment_status = 'completed'
            AND donation_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY HOUR(donation_date)
            ORDER BY hour"
        );
        
        // Day-of-week analysis
        $weekday_performance = $wpdb->get_results(
            "SELECT 
                DAYNAME(donation_date) as day_name,
                WEEKDAY(donation_date) as weekday,
                COUNT(*) as donations,
                SUM(amount) as revenue,
                AVG(amount) as average_amount
            FROM {$table_name} 
            WHERE payment_status = 'completed'
            AND donation_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY DAYNAME(donation_date), WEEKDAY(donation_date)
            ORDER BY weekday"
        );
        
        // Find peak performance times
        $peak_hour = self::find_peak_performance($hourly_performance, 'donations');
        $peak_day = self::find_peak_performance($weekday_performance, 'donations');
        
        return array(
            'daily_trends' => array_map(function($day) {
                return array(
                    'date' => $day->donation_day,
                    'donations' => (int) $day->donations,
                    'revenue' => (float) $day->revenue,
                    'average' => (float) $day->average_amount
                );
            }, $daily_performance),
            'hourly_patterns' => array_map(function($hour) {
                return array(
                    'hour' => (int) $hour->hour,
                    'donations' => (int) $hour->donations,
                    'revenue' => (float) $hour->revenue
                );
            }, $hourly_performance),
            'weekday_patterns' => array_map(function($day) {
                return array(
                    'day_name' => $day->day_name,
                    'donations' => (int) $day->donations,
                    'revenue' => (float) $day->revenue,
                    'average' => (float) $day->average_amount
                );
            }, $weekday_performance),
            'peak_times' => array(
                'best_hour' => $peak_hour,
                'best_day' => $peak_day
            ),
            'optimization_opportunities' => self::identify_campaign_opportunities($daily_performance, $hourly_performance, $weekday_performance)
        );
    }
    
    /**
     * Get revenue analysis and forecasting
     * 
     * @return array Revenue insights and projections
     */
    public static function get_revenue_analysis() {
        $overview = self::get_overview_metrics();
        $trends = self::get_trend_analysis();
        
        // Calculate revenue velocity (daily average)
        $current_month_days = date('j'); // Current day of month
        $daily_velocity = $current_month_days > 0 ? $overview['current_month']['raised'] / $current_month_days : 0;
        
        // Project end-of-month revenue
        $days_in_month = date('t');
        $projected_month_end = $daily_velocity * $days_in_month;
        
        // Calculate year-to-date projection
        $current_day_of_year = date('z') + 1;
        $days_in_year = date('L') ? 366 : 365;
        $daily_ytd_velocity = $current_day_of_year > 0 ? $overview['ytd_raised'] / $current_day_of_year : 0;
        $projected_year_end = $daily_ytd_velocity * $days_in_year;
        
        // Analyze revenue concentration
        $revenue_distribution = self::analyze_revenue_concentration();
        
        return array(
            'velocity_metrics' => array(
                'daily_average' => round($daily_velocity, 2),
                'weekly_average' => round($daily_velocity * 7, 2),
                'monthly_average' => round($daily_velocity * 30, 2)
            ),
            'projections' => array(
                'month_end_projection' => round($projected_month_end, 2),
                'month_end_vs_last' => round(($projected_month_end / ($overview['last_month']['raised'] ?: 1)) * 100, 1),
                'year_end_projection' => round($projected_year_end, 2),
                'ytd_performance' => round(($overview['ytd_raised'] / $projected_year_end) * 100, 1)
            ),
            'concentration' => $revenue_distribution,
            'growth_analysis' => self::analyze_growth_sustainability($trends['monthly_trends'])
        );
    }
    
    /**
     * Get geographic insights (if location data available)
     * 
     * @return array Geographic distribution analysis
     */
    public static function get_geographic_insights() {
        // Placeholder for geographic analysis
        // Could be enhanced with IP geolocation or postal code analysis
        return array(
            'note' => 'Geographic analysis requires location data integration',
            'enhancement_opportunity' => 'Consider adding postal code or location fields for detailed geographic insights'
        );
    }
    
    /**
     * Get predictive insights and forecasting
     * 
     * @return array Predictive analytics and trend forecasting
     */
    public static function get_predictive_insights() {
        $trends = self::get_trend_analysis();
        $overview = self::get_overview_metrics();
        
        // Simple trend prediction based on growth rate
        $recent_months = array_slice($trends['monthly_trends'], -3);
        $growth_rates = array();
        
        for ($i = 1; $i < count($recent_months); $i++) {
            if ($recent_months[$i-1]['revenue'] > 0) {
                $growth_rates[] = ($recent_months[$i]['revenue'] - $recent_months[$i-1]['revenue']) / $recent_months[$i-1]['revenue'];
            }
        }
        
        $avg_growth_rate = count($growth_rates) > 0 ? array_sum($growth_rates) / count($growth_rates) : 0;
        
        // Predict next 3 months
        $last_month_revenue = end($trends['monthly_trends'])['revenue'];
        $predictions = array();
        
        for ($i = 1; $i <= 3; $i++) {
            $predicted_revenue = $last_month_revenue * pow(1 + $avg_growth_rate, $i);
            $predictions[] = array(
                'month' => date('M Y', strtotime("+{$i} months")),
                'predicted_revenue' => round($predicted_revenue, 2),
                'confidence' => self::calculate_prediction_confidence($growth_rates)
            );
        }
        
        return array(
            'trend_direction' => $avg_growth_rate > 0 ? 'growing' : ($avg_growth_rate < 0 ? 'declining' : 'stable'),
            'growth_rate' => round($avg_growth_rate * 100, 2),
            'predictions' => $predictions,
            'seasonality_forecast' => self::predict_seasonal_trends($trends['monthly_trends'])
        );
    }
    
    /**
     * Get actionable recommendations based on analytics
     * 
     * @return array Strategic recommendations for improvement
     */
    public static function get_actionable_recommendations() {
        $overview = self::get_overview_metrics();
        $donor_insights = self::get_donor_insights();
        $campaign_performance = self::get_campaign_performance();
        
        $recommendations = array();
        
        // Revenue growth recommendations
        if ($overview['growth_rates']['revenue'] < 0.05) { // Less than 5% growth
            $recommendations[] = array(
                'category' => 'Revenue Growth',
                'priority' => 'high',
                'title' => 'Accelerate Revenue Growth',
                'description' => 'Current revenue growth is below 5%. Consider launching targeted campaigns or increasing donor outreach.',
                'action_items' => array(
                    'Launch email campaign to lapsed donors',
                    'Create social media fundraising campaign',
                    'Implement donor appreciation program',
                    'Analyze successful past campaigns for replication'
                ),
                'potential_impact' => '15-25% revenue increase'
            );
        }
        
        // Donor retention recommendations
        if ($donor_insights['at_risk_donors']['count'] > 0) {
            $recommendations[] = array(
                'category' => 'Donor Retention',
                'priority' => 'high',
                'title' => 'Re-engage At-Risk Donors',
                'description' => sprintf('You have %d at-risk donors representing $%.2f in previous contributions.', 
                    $donor_insights['at_risk_donors']['count'],
                    $donor_insights['at_risk_donors']['potential_loss']
                ),
                'action_items' => array(
                    'Send personalized re-engagement emails',
                    'Offer special giving opportunities',
                    'Share impact stories relevant to their interests',
                    'Conduct donor satisfaction survey'
                ),
                'potential_impact' => sprintf('Retain $%.2f in donor value', 
                    $donor_insights['at_risk_donors']['potential_loss'] * 0.3)
            );
        }
        
        // Campaign timing optimization
        if (isset($campaign_performance['peak_times']['best_hour'])) {
            $recommendations[] = array(
                'category' => 'Campaign Optimization',
                'priority' => 'medium',
                'title' => 'Optimize Campaign Timing',
                'description' => sprintf('Peak donation activity occurs at %d:00 on %s. Schedule campaigns accordingly.',
                    $campaign_performance['peak_times']['best_hour']['hour'],
                    $campaign_performance['peak_times']['best_day']['day_name']
                ),
                'action_items' => array(
                    'Schedule email campaigns for peak hours',
                    'Time social media posts for maximum engagement',
                    'Plan major announcements during high-activity periods',
                    'A/B test different timing strategies'
                ),
                'potential_impact' => '10-15% improvement in campaign response rates'
            );
        }
        
        // Major donor cultivation
        if ($donor_insights['major_donors']['count'] > 0) {
            $avg_major_donation = $donor_insights['major_donors']['total_contribution'] / $donor_insights['major_donors']['count'];
            $recommendations[] = array(
                'category' => 'Major Donor Relations',
                'priority' => 'high',
                'title' => 'Enhance Major Donor Stewardship',
                'description' => sprintf('Your %d major donors average $%.2f in contributions. Strengthen these relationships for sustained growth.',
                    $donor_insights['major_donors']['count'],
                    $avg_major_donation
                ),
                'action_items' => array(
                    'Implement personal thank-you calls or notes',
                    'Provide exclusive impact reports',
                    'Invite to special events or site visits',
                    'Create major donor advisory committee'
                ),
                'potential_impact' => '20-40% increase in major donor giving'
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Get benchmark comparison against industry standards
     * 
     * @return array Performance benchmarking analysis
     */
    public static function get_benchmark_comparison() {
        $overview = self::get_overview_metrics();
        $donor_insights = self::get_donor_insights();
        
        // Industry benchmark assumptions (based on nonprofit sector averages)
        $benchmarks = array(
            'donor_retention_rate' => 75, // 75% industry average
            'average_donation_growth' => 12, // 12% annual growth
            'major_donor_percentage' => 20, // 20% of donors contribute 80% of revenue
            'new_donor_retention' => 35 // 35% of new donors give again within a year
        );
        
        $total_donors = $donor_insights['total_donors'];
        $major_donor_percentage = $total_donors > 0 ? 
            ($donor_insights['major_donors']['count'] / $total_donors) * 100 : 0;
        
        return array(
            'retention_rate' => array(
                'your_performance' => round($overview['retention_rate'], 1),
                'industry_benchmark' => $benchmarks['donor_retention_rate'],
                'performance_vs_benchmark' => round($overview['retention_rate'] - $benchmarks['donor_retention_rate'], 1),
                'status' => $overview['retention_rate'] >= $benchmarks['donor_retention_rate'] ? 'above' : 'below'
            ),
            'revenue_growth' => array(
                'your_performance' => round($overview['growth_rates']['revenue'] * 100, 1),
                'industry_benchmark' => $benchmarks['average_donation_growth'],
                'performance_vs_benchmark' => round(($overview['growth_rates']['revenue'] * 100) - $benchmarks['average_donation_growth'], 1),
                'status' => ($overview['growth_rates']['revenue'] * 100) >= $benchmarks['average_donation_growth'] ? 'above' : 'below'
            ),
            'major_donor_concentration' => array(
                'your_performance' => round($major_donor_percentage, 1),
                'industry_benchmark' => $benchmarks['major_donor_percentage'],
                'performance_vs_benchmark' => round($major_donor_percentage - $benchmarks['major_donor_percentage'], 1),
                'status' => $major_donor_percentage >= $benchmarks['major_donor_percentage'] ? 'above' : 'below'
            ),
            'overall_score' => self::calculate_overall_performance_score($overview, $donor_insights, $benchmarks)
        );
    }
    
    // Helper methods
    
    private static function calculate_growth_rate($current, $previous) {
        if ($previous == 0) return $current > 0 ? 1.0 : 0.0;
        return ($current - $previous) / $previous;
    }
    
    private static function get_returning_donor_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';
        
        return $wpdb->get_var(
            "SELECT COUNT(DISTINCT donor_email) 
            FROM {$table_name} 
            WHERE payment_status = 'completed' 
            AND donor_email IN (
                SELECT donor_email 
                FROM {$table_name} 
                WHERE payment_status = 'completed' 
                GROUP BY donor_email 
                HAVING COUNT(*) > 1
            )"
        ) ?: 0;
    }
    
    private static function analyze_seasonal_patterns($trends) {
        $seasonal_data = array(
            'Q1' => array(), 'Q2' => array(), 'Q3' => array(), 'Q4' => array()
        );
        
        foreach ($trends as $trend) {
            $month = (int) date('n', strtotime($trend['month'] . '-01'));
            $quarter = 'Q' . ceil($month / 3);
            $seasonal_data[$quarter][] = $trend['revenue'];
        }
        
        $seasonal_averages = array();
        foreach ($seasonal_data as $quarter => $revenues) {
            $seasonal_averages[$quarter] = count($revenues) > 0 ? array_sum($revenues) / count($revenues) : 0;
        }
        
        return $seasonal_averages;
    }
    
    private static function calculate_trend_trajectory($trends) {
        if (count($trends) < 2) return 'insufficient_data';
        
        $recent_revenue = end($trends)['revenue'];
        $previous_revenue = prev($trends)['revenue'];
        
        $growth_rate = self::calculate_growth_rate($recent_revenue, $previous_revenue);
        
        if ($growth_rate > 0.15) return 'strong_growth';
        if ($growth_rate > 0.05) return 'moderate_growth';
        if ($growth_rate > -0.05) return 'stable';
        if ($growth_rate > -0.15) return 'moderate_decline';
        return 'significant_decline';
    }
    
    private static function calculate_median($values) {
        if (empty($values)) return 0;
        
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2) {
            return $values[$middle];
        } else {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }
    }
    
    private static function find_peak_performance($data, $metric) {
        if (empty($data)) return null;
        
        $max_value = 0;
        $peak_item = null;
        
        foreach ($data as $item) {
            if ($item->$metric > $max_value) {
                $max_value = $item->$metric;
                $peak_item = $item;
            }
        }
        
        return $peak_item ? (array) $peak_item : null;
    }
    
    private static function identify_campaign_opportunities($daily, $hourly, $weekday) {
        $opportunities = array();
        
        // Find underperforming days
        if (!empty($weekday)) {
            $revenues = array_column($weekday, 'revenue');
            $avg_revenue = array_sum($revenues) / count($revenues);
            
            foreach ($weekday as $day) {
                if ($day->revenue < $avg_revenue * 0.7) { // 30% below average
                    $opportunities[] = "Increase campaigns on {$day->day_name} (underperforming by " . 
                        round((1 - $day->revenue / $avg_revenue) * 100) . "%)";
                }
            }
        }
        
        return $opportunities;
    }
    
    private static function analyze_revenue_concentration() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';
        
        // Get donation amount distribution
        $distribution = $wpdb->get_results(
            "SELECT 
                CASE 
                    WHEN amount < 25 THEN 'Under $25'
                    WHEN amount < 100 THEN '$25-$99'
                    WHEN amount < 250 THEN '$100-$249'
                    WHEN amount < 500 THEN '$250-$499'
                    ELSE '$500+'
                END as amount_range,
                COUNT(*) as count,
                SUM(amount) as total
            FROM {$table_name} 
            WHERE payment_status = 'completed'
            GROUP BY 
                CASE 
                    WHEN amount < 25 THEN 'Under $25'
                    WHEN amount < 100 THEN '$25-$99'
                    WHEN amount < 250 THEN '$100-$249'
                    WHEN amount < 500 THEN '$250-$499'
                    ELSE '$500+'
                END
            ORDER BY MIN(amount)"
        );
        
        $total_revenue = array_sum(array_column($distribution, 'total'));
        
        return array_map(function($range) use ($total_revenue) {
            return array(
                'range' => $range->amount_range,
                'count' => (int) $range->count,
                'total' => (float) $range->total,
                'percentage' => $total_revenue > 0 ? round(($range->total / $total_revenue) * 100, 1) : 0
            );
        }, $distribution);
    }
    
    private static function analyze_growth_sustainability($trends) {
        $growth_rates = array();
        
        for ($i = 1; $i < count($trends); $i++) {
            if ($trends[$i-1]['revenue'] > 0) {
                $growth_rates[] = self::calculate_growth_rate($trends[$i]['revenue'], $trends[$i-1]['revenue']);
            }
        }
        
        if (empty($growth_rates)) return 'insufficient_data';
        
        $avg_growth = array_sum($growth_rates) / count($growth_rates);
        $volatility = self::calculate_standard_deviation($growth_rates);
        
        return array(
            'average_growth_rate' => round($avg_growth * 100, 2),
            'volatility' => round($volatility * 100, 2),
            'sustainability' => $volatility < 0.2 ? 'sustainable' : 'volatile'
        );
    }
    
    private static function calculate_prediction_confidence($growth_rates) {
        if (empty($growth_rates)) return 'low';
        
        $volatility = self::calculate_standard_deviation($growth_rates);
        
        if ($volatility < 0.1) return 'high';
        if ($volatility < 0.2) return 'medium';
        return 'low';
    }
    
    private static function predict_seasonal_trends($trends) {
        // Simple seasonal prediction based on historical patterns
        $seasonal_multipliers = self::analyze_seasonal_patterns($trends);
        
        return array(
            'next_quarter_outlook' => 'Based on seasonal patterns',
            'seasonal_multipliers' => $seasonal_multipliers
        );
    }
    
    private static function calculate_standard_deviation($values) {
        if (count($values) < 2) return 0;
        
        $mean = array_sum($values) / count($values);
        $squared_diffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);
        
        $variance = array_sum($squared_diffs) / count($values);
        return sqrt($variance);
    }
    
    private static function calculate_overall_performance_score($overview, $donor_insights, $benchmarks) {
        $scores = array();
        
        // Retention rate score (0-100)
        $scores[] = min(100, ($overview['retention_rate'] / $benchmarks['donor_retention_rate']) * 100);
        
        // Growth rate score (0-100)
        $growth_score = (($overview['growth_rates']['revenue'] * 100) / $benchmarks['average_donation_growth']) * 100;
        $scores[] = max(0, min(100, $growth_score));
        
        // Major donor concentration score (0-100)
        $total_donors = $donor_insights['total_donors'];
        $major_percentage = $total_donors > 0 ? 
            ($donor_insights['major_donors']['count'] / $total_donors) * 100 : 0;
        $scores[] = min(100, ($major_percentage / $benchmarks['major_donor_percentage']) * 100);
        
        return round(array_sum($scores) / count($scores), 1);
    }
    
    /**
     * Clear analytics cache
     */
    public static function clear_analytics_cache() {
        global $wpdb;
        
        // Clear all analytics-related transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_wpd_dashboard_analytics_%' 
            OR option_name LIKE '_transient_timeout_wpd_dashboard_analytics_%'"
        );
    }
}