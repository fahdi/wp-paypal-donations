<?php
/**
 * Analytics and Insights Dashboard
 * 
 * Interactive business intelligence dashboard that transforms raw donation data
 * into actionable insights, enabling data-driven fundraising decisions and
 * strategic campaign optimization for maximum organizational impact.
 *
 * @package WP_PayPal_Donations
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Generate analytics data
$analytics = WPD_Analytics_Engine::generate_dashboard_analytics();
$overview = $analytics['overview_metrics'];
$trends = $analytics['trend_analysis'];
$donors = $analytics['donor_insights'];
$campaigns = $analytics['campaign_performance'];
$revenue = $analytics['revenue_analysis'];
$predictions = $analytics['predictions'];
$recommendations = $analytics['recommendations'];
$benchmarks = $analytics['benchmark_comparison'];

?>

<div class="wrap wpd-analytics-dashboard">
    <h1><?php _e('Analytics & Insights Dashboard', 'wp-paypal-donations'); ?>
        <div class="wpd-dashboard-actions">
            <button id="wpd-refresh-analytics" class="button">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Refresh Data', 'wp-paypal-donations'); ?>
            </button>
            <button id="wpd-export-report" class="button button-secondary">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Report', 'wp-paypal-donations'); ?>
            </button>
        </div>
    </h1>
    
    <!-- Key Performance Indicators -->
    <div class="wpd-kpi-grid">
        <div class="wpd-kpi-card wpd-kpi-primary">
            <div class="wpd-kpi-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="wpd-kpi-content">
                <h3><?php _e('Total Raised', 'wp-paypal-donations'); ?></h3>
                <div class="wpd-kpi-value"><?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($overview['total_raised'], 2); ?></div>
                <div class="wpd-kpi-growth <?php echo $overview['growth_rates']['revenue'] >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="dashicons <?php echo $overview['growth_rates']['revenue'] >= 0 ? 'dashicons-arrow-up-alt' : 'dashicons-arrow-down-alt'; ?>"></span>
                    <?php echo abs(round($overview['growth_rates']['revenue'] * 100, 1)); ?>% <?php _e('vs last month', 'wp-paypal-donations'); ?>
                </div>
            </div>
        </div>
        
        <div class="wpd-kpi-card wpd-kpi-success">
            <div class="wpd-kpi-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="wpd-kpi-content">
                <h3><?php _e('Total Donors', 'wp-paypal-donations'); ?></h3>
                <div class="wpd-kpi-value"><?php echo number_format($overview['unique_donors']); ?></div>
                <div class="wpd-kpi-sub"><?php echo number_format($overview['total_donations']); ?> <?php _e('donations', 'wp-paypal-donations'); ?></div>
            </div>
        </div>
        
        <div class="wpd-kpi-card wpd-kpi-info">
            <div class="wpd-kpi-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="wpd-kpi-content">
                <h3><?php _e('Average Donation', 'wp-paypal-donations'); ?></h3>
                <div class="wpd-kpi-value"><?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($overview['average_donation'], 2); ?></div>
                <div class="wpd-kpi-growth <?php echo $overview['growth_rates']['average'] >= 0 ? 'positive' : 'negative'; ?>">
                    <span class="dashicons <?php echo $overview['growth_rates']['average'] >= 0 ? 'dashicons-arrow-up-alt' : 'dashicons-arrow-down-alt'; ?>"></span>
                    <?php echo abs(round($overview['growth_rates']['average'] * 100, 1)); ?>% <?php _e('vs last month', 'wp-paypal-donations'); ?>
                </div>
            </div>
        </div>
        
        <div class="wpd-kpi-card wpd-kpi-warning">
            <div class="wpd-kpi-icon">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <div class="wpd-kpi-content">
                <h3><?php _e('Donor Retention', 'wp-paypal-donations'); ?></h3>
                <div class="wpd-kpi-value"><?php echo $overview['retention_rate']; ?>%</div>
                <div class="wpd-kpi-sub"><?php echo number_format($overview['returning_donors']); ?> <?php _e('returning donors', 'wp-paypal-donations'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Revenue Trends Chart -->
    <div class="wpd-analytics-section">
        <div class="wpd-section-header">
            <h2><?php _e('Revenue Trends', 'wp-paypal-donations'); ?></h2>
            <div class="wpd-chart-controls">
                <select id="wpd-trend-period">
                    <option value="12months"><?php _e('Last 12 Months', 'wp-paypal-donations'); ?></option>
                    <option value="6months"><?php _e('Last 6 Months', 'wp-paypal-donations'); ?></option>
                    <option value="3months"><?php _e('Last 3 Months', 'wp-paypal-donations'); ?></option>
                </select>
            </div>
        </div>
        <div class="wpd-chart-container">
            <canvas id="wpd-revenue-trend-chart" width="800" height="300"></canvas>
        </div>
        
        <!-- Revenue Projections -->
        <div class="wpd-projections">
            <h3><?php _e('Revenue Projections', 'wp-paypal-donations'); ?></h3>
            <div class="wpd-projection-grid">
                <div class="wpd-projection-item">
                    <span class="wpd-projection-label"><?php _e('This Month Projection', 'wp-paypal-donations'); ?></span>
                    <span class="wpd-projection-value"><?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($revenue['projections']['month_end_projection'], 2); ?></span>
                    <span class="wpd-projection-change"><?php echo $revenue['projections']['month_end_vs_last']; ?>% <?php _e('vs last month', 'wp-paypal-donations'); ?></span>
                </div>
                <div class="wpd-projection-item">
                    <span class="wpd-projection-label"><?php _e('Year-End Projection', 'wp-paypal-donations'); ?></span>
                    <span class="wpd-projection-value"><?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($revenue['projections']['year_end_projection'], 2); ?></span>
                    <span class="wpd-projection-change"><?php echo $revenue['projections']['ytd_performance']; ?>% <?php _e('of goal achieved', 'wp-paypal-donations'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Two-column layout for detailed insights -->
    <div class="wpd-analytics-columns">
        <!-- Left Column: Donor Insights -->
        <div class="wpd-analytics-column">
            <!-- Donor Segmentation -->
            <div class="wpd-analytics-widget">
                <h3><?php _e('Donor Insights', 'wp-paypal-donations'); ?></h3>
                
                <div class="wpd-donor-segments">
                    <div class="wpd-segment">
                        <div class="wpd-segment-header">
                            <span class="wpd-segment-title"><?php _e('Major Donors', 'wp-paypal-donations'); ?></span>
                            <span class="wpd-segment-count"><?php echo $donors['major_donors']['count']; ?></span>
                        </div>
                        <div class="wpd-segment-value">
                            <?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($donors['major_donors']['total_contribution'], 2); ?>
                        </div>
                        <div class="wpd-segment-percentage">
                            <?php 
                            $major_percentage = $overview['total_raised'] > 0 ? 
                                ($donors['major_donors']['total_contribution'] / $overview['total_raised']) * 100 : 0;
                            echo round($major_percentage, 1); 
                            ?>% <?php _e('of total revenue', 'wp-paypal-donations'); ?>
                        </div>
                    </div>
                    
                    <div class="wpd-segment">
                        <div class="wpd-segment-header">
                            <span class="wpd-segment-title"><?php _e('Recurring Donors', 'wp-paypal-donations'); ?></span>
                            <span class="wpd-segment-count"><?php echo $donors['recurring_donors']['count']; ?></span>
                        </div>
                        <div class="wpd-segment-value">
                            <?php echo round($donors['recurring_donors']['retention_rate'], 1); ?>% <?php _e('retention rate', 'wp-paypal-donations'); ?>
                        </div>
                    </div>
                    
                    <div class="wpd-segment">
                        <div class="wpd-segment-header">
                            <span class="wpd-segment-title"><?php _e('New Donors', 'wp-paypal-donations'); ?></span>
                            <span class="wpd-segment-count"><?php echo $donors['new_donors']['count']; ?></span>
                        </div>
                        <div class="wpd-segment-value">
                            <?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($donors['new_donors']['avg_first_donation'], 2); ?>
                        </div>
                        <div class="wpd-segment-percentage"><?php _e('avg first donation', 'wp-paypal-donations'); ?></div>
                    </div>
                    
                    <?php if ($donors['at_risk_donors']['count'] > 0): ?>
                    <div class="wpd-segment wpd-segment-warning">
                        <div class="wpd-segment-header">
                            <span class="wpd-segment-title"><?php _e('At-Risk Donors', 'wp-paypal-donations'); ?></span>
                            <span class="wpd-segment-count"><?php echo $donors['at_risk_donors']['count']; ?></span>
                        </div>
                        <div class="wpd-segment-value">
                            <?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($donors['at_risk_donors']['potential_loss'], 2); ?>
                        </div>
                        <div class="wpd-segment-percentage"><?php _e('potential value at risk', 'wp-paypal-donations'); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Campaign Performance -->
            <div class="wpd-analytics-widget">
                <h3><?php _e('Campaign Performance', 'wp-paypal-donations'); ?></h3>
                
                <div class="wpd-performance-insights">
                    <div class="wpd-insight-item">
                        <span class="wpd-insight-label"><?php _e('Peak Donation Day', 'wp-paypal-donations'); ?></span>
                        <span class="wpd-insight-value">
                            <?php echo isset($campaigns['peak_times']['best_day']) ? $campaigns['peak_times']['best_day']['day_name'] : __('N/A', 'wp-paypal-donations'); ?>
                        </span>
                    </div>
                    <div class="wpd-insight-item">
                        <span class="wpd-insight-label"><?php _e('Peak Donation Hour', 'wp-paypal-donations'); ?></span>
                        <span class="wpd-insight-value">
                            <?php echo isset($campaigns['peak_times']['best_hour']) ? $campaigns['peak_times']['best_hour']['hour'] . ':00' : __('N/A', 'wp-paypal-donations'); ?>
                        </span>
                    </div>
                    <div class="wpd-insight-item">
                        <span class="wpd-insight-label"><?php _e('Daily Velocity', 'wp-paypal-donations'); ?></span>
                        <span class="wpd-insight-value">
                            <?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($revenue['velocity_metrics']['daily_average'], 2); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Weekly Performance Chart -->
                <div class="wpd-mini-chart-container">
                    <h4><?php _e('Weekly Performance Pattern', 'wp-paypal-donations'); ?></h4>
                    <canvas id="wpd-weekly-pattern-chart" width="300" height="150"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Benchmarks & Recommendations -->
        <div class="wpd-analytics-column">
            <!-- Performance Benchmarks -->
            <div class="wpd-analytics-widget">
                <h3><?php _e('Performance vs Industry Benchmarks', 'wp-paypal-donations'); ?></h3>
                
                <div class="wpd-benchmarks">
                    <div class="wpd-benchmark-item">
                        <div class="wpd-benchmark-header">
                            <span class="wpd-benchmark-title"><?php _e('Donor Retention Rate', 'wp-paypal-donations'); ?></span>
                            <span class="wpd-benchmark-status wpd-status-<?php echo $benchmarks['retention_rate']['status']; ?>">
                                <?php echo ucfirst($benchmarks['retention_rate']['status']); ?> <?php _e('Benchmark', 'wp-paypal-donations'); ?>
                            </span>
                        </div>
                        <div class="wpd-benchmark-comparison">
                            <span class="wpd-your-performance"><?php echo $benchmarks['retention_rate']['your_performance']; ?>%</span>
                            <span class="wpd-vs">vs</span>
                            <span class="wpd-industry-benchmark"><?php echo $benchmarks['retention_rate']['industry_benchmark']; ?>% <?php _e('industry avg', 'wp-paypal-donations'); ?></span>
                        </div>
                    </div>
                    
                    <div class="wpd-benchmark-item">
                        <div class="wpd-benchmark-header">
                            <span class="wpd-benchmark-title"><?php _e('Revenue Growth Rate', 'wp-paypal-donations'); ?></span>
                            <span class="wpd-benchmark-status wpd-status-<?php echo $benchmarks['revenue_growth']['status']; ?>">
                                <?php echo ucfirst($benchmarks['revenue_growth']['status']); ?> <?php _e('Benchmark', 'wp-paypal-donations'); ?>
                            </span>
                        </div>
                        <div class="wpd-benchmark-comparison">
                            <span class="wpd-your-performance"><?php echo $benchmarks['revenue_growth']['your_performance']; ?>%</span>
                            <span class="wpd-vs">vs</span>
                            <span class="wpd-industry-benchmark"><?php echo $benchmarks['revenue_growth']['industry_benchmark']; ?>% <?php _e('industry avg', 'wp-paypal-donations'); ?></span>
                        </div>
                    </div>
                    
                    <div class="wpd-overall-score">
                        <h4><?php _e('Overall Performance Score', 'wp-paypal-donations'); ?></h4>
                        <div class="wpd-score-display">
                            <span class="wpd-score-number"><?php echo $benchmarks['overall_score']; ?></span>
                            <span class="wpd-score-total">/100</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Strategic Recommendations -->
            <div class="wpd-analytics-widget">
                <h3><?php _e('Strategic Recommendations', 'wp-paypal-donations'); ?></h3>
                
                <div class="wpd-recommendations">
                    <?php foreach ($recommendations as $recommendation): ?>
                    <div class="wpd-recommendation wpd-priority-<?php echo esc_attr($recommendation['priority']); ?>">
                        <div class="wpd-recommendation-header">
                            <h4><?php echo esc_html($recommendation['title']); ?></h4>
                            <span class="wpd-recommendation-category"><?php echo esc_html($recommendation['category']); ?></span>
                        </div>
                        <p><?php echo esc_html($recommendation['description']); ?></p>
                        
                        <div class="wpd-action-items">
                            <h5><?php _e('Action Items:', 'wp-paypal-donations'); ?></h5>
                            <ul>
                                <?php foreach ($recommendation['action_items'] as $action): ?>
                                <li><?php echo esc_html($action); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="wpd-potential-impact">
                            <strong><?php _e('Potential Impact:', 'wp-paypal-donations'); ?></strong>
                            <?php echo esc_html($recommendation['potential_impact']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Predictive Analytics Section -->
    <div class="wpd-analytics-section">
        <h2><?php _e('Predictive Analytics', 'wp-paypal-donations'); ?></h2>
        
        <div class="wpd-predictions-grid">
            <div class="wpd-prediction-card">
                <h3><?php _e('Trend Direction', 'wp-paypal-donations'); ?></h3>
                <div class="wpd-trend-indicator wpd-trend-<?php echo esc_attr($predictions['trend_direction']); ?>">
                    <span class="wpd-trend-icon"></span>
                    <span class="wpd-trend-text"><?php echo ucfirst(str_replace('_', ' ', $predictions['trend_direction'])); ?></span>
                </div>
                <div class="wpd-trend-rate">
                    <?php echo abs($predictions['growth_rate']); ?>% <?php echo $predictions['growth_rate'] >= 0 ? __('growth rate', 'wp-paypal-donations') : __('decline rate', 'wp-paypal-donations'); ?>
                </div>
            </div>
            
            <div class="wpd-prediction-card">
                <h3><?php _e('3-Month Forecast', 'wp-paypal-donations'); ?></h3>
                <div class="wpd-forecast-list">
                    <?php foreach (array_slice($predictions['predictions'], 0, 3) as $prediction): ?>
                    <div class="wpd-forecast-item">
                        <span class="wpd-forecast-month"><?php echo esc_html($prediction['month']); ?></span>
                        <span class="wpd-forecast-amount"><?php echo get_option('wpd_currency', 'USD') . ' ' . number_format($prediction['predicted_revenue'], 2); ?></span>
                        <span class="wpd-forecast-confidence wpd-confidence-<?php echo esc_attr($prediction['confidence']); ?>">
                            <?php echo ucfirst($prediction['confidence']); ?> <?php _e('confidence', 'wp-paypal-donations'); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
jQuery(document).ready(function($) {
    // Revenue Trend Chart
    const revenueCtx = document.getElementById('wpd-revenue-trend-chart').getContext('2d');
    const trendData = <?php echo json_encode($trends['monthly_trends']); ?>;
    
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: trendData.map(item => item.month_name),
            datasets: [{
                label: '<?php esc_js_e('Revenue', 'wp-paypal-donations'); ?>',
                data: trendData.map(item => item.revenue),
                borderColor: '#0073aa',
                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: '<?php esc_js_e('Donations', 'wp-paypal-donations'); ?>',
                data: trendData.map(item => item.donations),
                borderColor: '#00a32a',
                backgroundColor: 'rgba(0, 163, 42, 0.1)',
                fill: false,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: '<?php esc_js_e('Revenue', 'wp-paypal-donations'); ?>'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: '<?php esc_js_e('Donations', 'wp-paypal-donations'); ?>'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
    
    // Weekly Pattern Chart
    <?php if (!empty($campaigns['weekday_patterns'])): ?>
    const weeklyCtx = document.getElementById('wpd-weekly-pattern-chart').getContext('2d');
    const weeklyData = <?php echo json_encode($campaigns['weekday_patterns']); ?>;
    
    new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: weeklyData.map(item => item.day_name),
            datasets: [{
                label: '<?php esc_js_e('Revenue', 'wp-paypal-donations'); ?>',
                data: weeklyData.map(item => item.revenue),
                backgroundColor: '#dba617',
                borderColor: '#c09c00',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php endif; ?>
    
    // Refresh analytics
    $('#wpd-refresh-analytics').click(function() {
        // Clear cache and reload page
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'wpd_refresh_analytics',
                nonce: '<?php echo wp_create_nonce('wpd_refresh_analytics'); ?>'
            },
            success: function() {
                location.reload();
            }
        });
    });
    
    // Export report functionality
    $('#wpd-export-report').click(function() {
        window.open('<?php echo admin_url('admin.php?page=wp-paypal-donations-analytics&action=export_report'); ?>', '_blank');
    });
});
</script>

<?php
// Add custom CSS for the analytics dashboard
?>
<style>
.wpd-analytics-dashboard {
    max-width: 1400px;
}

.wpd-dashboard-actions {
    float: right;
    margin-top: -10px;
}

.wpd-dashboard-actions .button {
    margin-left: 10px;
}

.wpd-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin: 20px 0 40px 0;
}

.wpd-kpi-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.wpd-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.wpd-kpi-primary { border-left: 4px solid #0073aa; }
.wpd-kpi-success { border-left: 4px solid #00a32a; }
.wpd-kpi-info { border-left: 4px solid #72aee6; }
.wpd-kpi-warning { border-left: 4px solid #dba617; }

.wpd-kpi-icon {
    margin-right: 20px;
    font-size: 32px;
    color: #666;
}

.wpd-kpi-content h3 {
    margin: 0 0 8px 0;
    font-size: 14px;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
}

.wpd-kpi-value {
    font-size: 28px;
    font-weight: bold;
    color: #23282d;
    margin: 0 0 4px 0;
    line-height: 1.2;
}

.wpd-kpi-growth, .wpd-kpi-sub {
    font-size: 12px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 4px;
}

.wpd-kpi-growth.positive { color: #00a32a; }
.wpd-kpi-growth.negative { color: #d63638; }

.wpd-analytics-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 20px 0;
    padding: 0;
}

.wpd-section-header {
    padding: 20px 30px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9f9f9;
    border-radius: 8px 8px 0 0;
}

.wpd-section-header h2 {
    margin: 0;
    font-size: 20px;
}

.wpd-chart-container {
    padding: 30px;
}

.wpd-projections {
    padding: 20px 30px;
    border-top: 1px solid #eee;
    background: #f9f9f9;
}

.wpd-projection-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.wpd-projection-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.wpd-projection-label {
    font-size: 12px;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
}

.wpd-projection-value {
    font-size: 20px;
    font-weight: bold;
    color: #23282d;
}

.wpd-projection-change {
    font-size: 12px;
    color: #666;
}

.wpd-analytics-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.wpd-analytics-widget {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.wpd-analytics-widget h3 {
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 18px;
}

.wpd-donor-segments {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.wpd-segment {
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 6px;
    background: #fafafa;
}

.wpd-segment-warning {
    background: #fff3cd;
    border-color: #ffeaa7;
}

.wpd-segment-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.wpd-segment-title {
    font-weight: 600;
    color: #23282d;
}

.wpd-segment-count {
    background: #0073aa;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.wpd-segment-value {
    font-size: 18px;
    font-weight: bold;
    color: #23282d;
    margin-bottom: 4px;
}

.wpd-segment-percentage {
    font-size: 12px;
    color: #666;
}

.wpd-performance-insights {
    margin-bottom: 20px;
}

.wpd-insight-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.wpd-insight-item:last-child {
    border-bottom: none;
}

.wpd-insight-label {
    color: #666;
    font-size: 14px;
}

.wpd-insight-value {
    font-weight: bold;
    color: #23282d;
}

.wpd-mini-chart-container {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.wpd-benchmarks {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.wpd-benchmark-item {
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 6px;
}

.wpd-benchmark-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.wpd-benchmark-title {
    font-weight: 600;
    color: #23282d;
}

.wpd-benchmark-status {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    text-transform: uppercase;
}

.wpd-status-above {
    background: #d1e7dd;
    color: #0f5132;
}

.wpd-status-below {
    background: #f8d7da;
    color: #842029;
}

.wpd-benchmark-comparison {
    display: flex;
    align-items: center;
    gap: 10px;
}

.wpd-your-performance {
    font-size: 18px;
    font-weight: bold;
    color: #0073aa;
}

.wpd-vs {
    color: #666;
    font-size: 12px;
}

.wpd-industry-benchmark {
    font-size: 14px;
    color: #666;
}

.wpd-overall-score {
    text-align: center;
    padding: 20px;
    background: #f0f8ff;
    border-radius: 8px;
    margin-top: 20px;
}

.wpd-score-display {
    font-size: 36px;
    font-weight: bold;
    color: #0073aa;
}

.wpd-score-total {
    color: #666;
    font-size: 18px;
}

.wpd-recommendations {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.wpd-recommendation {
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
}

.wpd-priority-high {
    border-left: 4px solid #d63638;
    background: #fef7f7;
}

.wpd-priority-medium {
    border-left: 4px solid #dba617;
    background: #fffbf0;
}

.wpd-priority-low {
    border-left: 4px solid #72aee6;
    background: #f0f8ff;
}

.wpd-recommendation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.wpd-recommendation-header h4 {
    margin: 0;
    color: #23282d;
}

.wpd-recommendation-category {
    background: #666;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    text-transform: uppercase;
    font-weight: bold;
}

.wpd-action-items {
    margin: 15px 0;
}

.wpd-action-items h5 {
    margin: 0 0 8px 0;
    color: #23282d;
}

.wpd-action-items ul {
    margin: 0;
    padding-left: 20px;
}

.wpd-action-items li {
    margin-bottom: 4px;
    color: #666;
}

.wpd-potential-impact {
    background: rgba(0, 115, 170, 0.1);
    padding: 10px;
    border-radius: 4px;
    border-left: 3px solid #0073aa;
    font-size: 14px;
}

.wpd-predictions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    padding: 30px;
}

.wpd-prediction-card {
    text-align: center;
    padding: 20px;
    border: 1px solid #eee;
    border-radius: 8px;
    background: #fafafa;
}

.wpd-trend-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin: 15px 0;
}

.wpd-trend-growing .wpd-trend-icon::before {
    content: "↗";
    color: #00a32a;
    font-size: 24px;
    font-weight: bold;
}

.wpd-trend-stable .wpd-trend-icon::before {
    content: "→";
    color: #dba617;
    font-size: 24px;
    font-weight: bold;
}

.wpd-trend-declining .wpd-trend-icon::before {
    content: "↘";
    color: #d63638;
    font-size: 24px;
    font-weight: bold;
}

.wpd-trend-text {
    font-size: 18px;
    font-weight: bold;
    text-transform: capitalize;
}

.wpd-trend-rate {
    font-size: 14px;
    color: #666;
    margin-top: 8px;
}

.wpd-forecast-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 15px;
}

.wpd-forecast-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: white;
    border-radius: 4px;
    border: 1px solid #eee;
}

.wpd-forecast-month {
    font-weight: bold;
    color: #23282d;
}

.wpd-forecast-amount {
    color: #0073aa;
    font-weight: bold;
}

.wpd-forecast-confidence {
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 3px;
    text-transform: uppercase;
    font-weight: bold;
}

.wpd-confidence-high {
    background: #d1e7dd;
    color: #0f5132;
}

.wpd-confidence-medium {
    background: #fff3cd;
    color: #664d03;
}

.wpd-confidence-low {
    background: #f8d7da;
    color: #842029;
}

@media (max-width: 1200px) {
    .wpd-analytics-columns {
        grid-template-columns: 1fr;
    }
    
    .wpd-kpi-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
}

@media (max-width: 782px) {
    .wpd-analytics-dashboard {
        margin: 0 -20px;
    }
    
    .wpd-kpi-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .wpd-predictions-grid {
        grid-template-columns: 1fr;
        gap: 20px;
        padding: 20px;
    }
    
    .wpd-dashboard-actions {
        float: none;
        margin: 10px 0;
    }
}
</style>