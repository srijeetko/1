<?php
// Start session and include database connection
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get date range filter
$date_range = $_GET['range'] ?? '30';
$start_date = date('Y-m-d', strtotime("-{$date_range} days"));

// Get overall statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_returns,
        SUM(CASE WHEN return_status = 'pending' THEN 1 ELSE 0 END) as pending_returns,
        SUM(CASE WHEN return_status = 'approved' THEN 1 ELSE 0 END) as approved_returns,
        SUM(CASE WHEN return_status = 'rejected' THEN 1 ELSE 0 END) as rejected_returns,
        SUM(CASE WHEN return_status = 'refunded' THEN 1 ELSE 0 END) as refunded_returns,
        SUM(total_return_amount) as total_return_amount,
        SUM(refund_amount) as total_refunded,
        AVG(total_return_amount) as avg_return_amount,
        AVG(CASE WHEN return_status = 'refunded' THEN TIMESTAMPDIFF(HOUR, created_at, processed_at) ELSE NULL END) as avg_processing_time_hours
    FROM return_requests
    WHERE created_at >= ?
";

$stmt = $pdo->prepare($stats_query);
$stmt->execute([$start_date]);
$overall_stats = $stmt->fetch();

// Get daily return trends
$daily_trends_query = "
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as returns_count,
        SUM(total_return_amount) as total_amount,
        SUM(CASE WHEN return_status = 'refunded' THEN refund_amount ELSE 0 END) as refunded_amount
    FROM return_requests
    WHERE created_at >= ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC
    LIMIT 30
";

$stmt = $pdo->prepare($daily_trends_query);
$stmt->execute([$start_date]);
$daily_trends = $stmt->fetchAll();

// Get return reasons analysis
$reasons_query = "
    SELECT 
        rr.reason_name,
        COUNT(*) as count,
        SUM(ret.total_return_amount) as total_amount,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM return_requests WHERE created_at >= ?)), 2) as percentage
    FROM return_requests ret
    LEFT JOIN return_reasons rr ON ret.return_reason_id = rr.reason_id
    WHERE ret.created_at >= ?
    GROUP BY ret.return_reason_id, rr.reason_name
    ORDER BY count DESC
";

$stmt = $pdo->prepare($reasons_query);
$stmt->execute([$start_date, $start_date]);
$return_reasons = $stmt->fetchAll();

// Get top returned products
$products_query = "
    SELECT 
        ri.product_name,
        COUNT(*) as return_count,
        SUM(ri.quantity_returned) as total_quantity,
        SUM(ri.total_amount) as total_amount,
        AVG(ri.unit_price) as avg_price
    FROM return_items ri
    JOIN return_requests rr ON ri.return_id = rr.return_id
    WHERE rr.created_at >= ?
    GROUP BY ri.product_id, ri.product_name
    ORDER BY return_count DESC
    LIMIT 10
";

$stmt = $pdo->prepare($products_query);
$stmt->execute([$start_date]);
$top_returned_products = $stmt->fetchAll();

// Get refund method distribution
$refund_methods_query = "
    SELECT 
        refund_method,
        COUNT(*) as count,
        SUM(refund_amount) as total_amount,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM refund_transactions WHERE created_at >= ?)), 2) as percentage
    FROM refund_transactions
    WHERE created_at >= ?
    GROUP BY refund_method
    ORDER BY count DESC
";

$stmt = $pdo->prepare($refund_methods_query);
$stmt->execute([$start_date, $start_date]);
$refund_methods = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Analytics - Alpha Nutrition Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .analytics-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .analytics-title {
            font-size: 2rem;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .analytics-title i {
            color: #ff6b35;
        }

        .date-filter {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .date-filter select {
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
            background: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #ff6b35;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-card h3 {
            font-size: 2rem;
            margin: 0 0 0.5rem 0;
            color: #ff6b35;
        }

        .stat-card p {
            margin: 0;
            color: #666;
            font-weight: 500;
        }

        .stat-card .stat-change {
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .stat-change.positive {
            color: #28a745;
        }

        .stat-change.negative {
            color: #dc3545;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .chart-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .chart-header h3 {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .chart-body {
            padding: 1.5rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .data-table tr:hover {
            background: #f8f9fa;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background: #ff6b35;
            transition: width 0.3s ease;
        }

        .metric-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
        }

        .metric-badge.high {
            background: #f8d7da;
            color: #721c24;
        }

        .metric-badge.medium {
            background: #fff3cd;
            color: #856404;
        }

        .metric-badge.low {
            background: #d4edda;
            color: #155724;
        }

        @media (max-width: 768px) {
            .analytics-container {
                padding: 1rem;
            }

            .analytics-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .charts-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-container">
        <div class="admin-sidebar">
            <?php include 'includes/admin-sidebar.php'; ?>
        </div>
        
        <main class="admin-main">
            <div class="analytics-container">
                <!-- Header -->
                <div class="analytics-header">
                    <h1 class="analytics-title">
                        <i class="fas fa-chart-line"></i>
                        Refund Analytics
                    </h1>
                    <div class="date-filter">
                        <label for="dateRange">Date Range:</label>
                        <select id="dateRange" onchange="updateDateRange()">
                            <option value="7" <?php echo $date_range === '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $date_range === '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $date_range === '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                            <option value="365" <?php echo $date_range === '365' ? 'selected' : ''; ?>>Last Year</option>
                        </select>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo number_format($overall_stats['total_returns']); ?></h3>
                        <p>Total Returns</p>
                        <div class="stat-change">
                            Last <?php echo $date_range; ?> days
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>₹<?php echo number_format($overall_stats['total_refunded'], 2); ?></h3>
                        <p>Total Refunded</p>
                        <div class="stat-change">
                            <?php 
                            $refund_rate = $overall_stats['total_return_amount'] > 0 ? 
                                ($overall_stats['total_refunded'] / $overall_stats['total_return_amount']) * 100 : 0;
                            echo number_format($refund_rate, 1) . '% of return amount';
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>₹<?php echo number_format($overall_stats['avg_return_amount'], 2); ?></h3>
                        <p>Average Return Value</p>
                        <div class="stat-change">
                            Per return request
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo number_format($overall_stats['avg_processing_time_hours'] ?? 0, 1); ?>h</h3>
                        <p>Avg Processing Time</p>
                        <div class="stat-change">
                            From request to refund
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo number_format($overall_stats['pending_returns']); ?></h3>
                        <p>Pending Returns</p>
                        <div class="stat-change">
                            Awaiting approval
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3><?php 
                        $approval_rate = $overall_stats['total_returns'] > 0 ? 
                            (($overall_stats['approved_returns'] + $overall_stats['refunded_returns']) / $overall_stats['total_returns']) * 100 : 0;
                        echo number_format($approval_rate, 1) . '%';
                        ?></h3>
                        <p>Approval Rate</p>
                        <div class="stat-change">
                            Approved + Refunded
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="charts-grid">
                    <!-- Daily Trends Chart -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-line"></i> Daily Return Trends</h3>
                        </div>
                        <div class="chart-body">
                            <div class="chart-container">
                                <canvas id="dailyTrendsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Return Reasons Pie Chart -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-chart-pie"></i> Return Reasons</h3>
                        </div>
                        <div class="chart-body">
                            <div class="chart-container">
                                <canvas id="returnReasonsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Tables -->
                <div class="charts-grid">
                    <!-- Top Returned Products -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-box"></i> Top Returned Products</h3>
                        </div>
                        <div class="chart-body">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Returns</th>
                                        <th>Quantity</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_returned_products as $product): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="metric-badge <?php
                                                    echo $product['return_count'] > 10 ? 'high' :
                                                        ($product['return_count'] > 5 ? 'medium' : 'low');
                                                ?>">
                                                    <?php echo $product['return_count']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $product['total_quantity']; ?></td>
                                            <td>₹<?php echo number_format($product['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Return Reasons Breakdown -->
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3><i class="fas fa-list"></i> Return Reasons Breakdown</h3>
                        </div>
                        <div class="chart-body">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Reason</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($return_reasons as $reason): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($reason['reason_name'] ?? 'Other'); ?></strong>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $reason['percentage']; ?>%"></div>
                                                </div>
                                            </td>
                                            <td><?php echo $reason['count']; ?></td>
                                            <td><?php echo $reason['percentage']; ?>%</td>
                                            <td>₹<?php echo number_format($reason['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Refund Methods -->
                <?php if (!empty($refund_methods)): ?>
                    <div class="chart-card" style="margin-top: 2rem;">
                        <div class="chart-header">
                            <h3><i class="fas fa-credit-card"></i> Refund Methods Distribution</h3>
                        </div>
                        <div class="chart-body">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($refund_methods as $method): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo ucfirst(str_replace('_', ' ', $method['refund_method'])); ?></strong>
                                                <div class="progress-bar">
                                                    <div class="progress-fill" style="width: <?php echo $method['percentage']; ?>%"></div>
                                                </div>
                                            </td>
                                            <td><?php echo $method['count']; ?></td>
                                            <td><?php echo $method['percentage']; ?>%</td>
                                            <td>₹<?php echo number_format($method['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Update date range
        function updateDateRange() {
            const range = document.getElementById('dateRange').value;
            window.location.href = `?range=${range}`;
        }

        // Chart data from PHP
        const dailyTrendsData = <?php echo json_encode(array_reverse($daily_trends)); ?>;
        const returnReasonsData = <?php echo json_encode($return_reasons); ?>;

        // Daily Trends Chart
        const dailyTrendsCtx = document.getElementById('dailyTrendsChart').getContext('2d');
        new Chart(dailyTrendsCtx, {
            type: 'line',
            data: {
                labels: dailyTrendsData.map(item => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Returns Count',
                    data: dailyTrendsData.map(item => item.returns_count),
                    borderColor: '#ff6b35',
                    backgroundColor: 'rgba(255, 107, 53, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Refunded Amount (₹)',
                    data: dailyTrendsData.map(item => item.refunded_amount),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Returns Count'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Amount (₹)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });

        // Return Reasons Pie Chart
        const returnReasonsCtx = document.getElementById('returnReasonsChart').getContext('2d');
        new Chart(returnReasonsCtx, {
            type: 'doughnut',
            data: {
                labels: returnReasonsData.map(item => item.reason_name || 'Other'),
                datasets: [{
                    data: returnReasonsData.map(item => item.count),
                    backgroundColor: [
                        '#ff6b35',
                        '#28a745',
                        '#007bff',
                        '#ffc107',
                        '#dc3545',
                        '#6c757d',
                        '#17a2b8',
                        '#6f42c1'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Auto-refresh functionality
        function autoRefresh() {
            setTimeout(() => {
                window.location.reload();
            }, 300000); // Refresh every 5 minutes
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            autoRefresh();

            // Add hover effects to stat cards
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(-4px)';
                });
            });
        });
    </script>
</body>
</html>
