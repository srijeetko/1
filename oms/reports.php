<?php
session_start();
require_once '../includes/db_connection.php';

// Check if OMS admin is logged in
if (!isset($_SESSION['oms_admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle export requests
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to = $_GET['date_to'] ?? date('Y-m-d');

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $export_type . '_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    switch ($export_type) {
        case 'sales_overview':
            // Export sales overview
            $stmt = $pdo->prepare("
                SELECT
                    DATE(created_at) as date,
                    COUNT(*) as total_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(total_amount) as avg_order_value,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders
                FROM checkout_orders
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ");
            $stmt->execute([$date_from, $date_to]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            fputcsv($output, ['Date', 'Total Orders', 'Total Revenue', 'Avg Order Value', 'Paid Orders', 'Delivered Orders']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['date'],
                    $row['total_orders'],
                    $row['total_revenue'],
                    round($row['avg_order_value'], 2),
                    $row['paid_orders'],
                    $row['delivered_orders']
                ]);
            }
            break;

        case 'transaction_performance':
            // Export transaction performance
            $stmt = $pdo->prepare("
                SELECT
                    payment_gateway,
                    COUNT(*) as transaction_count,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN transaction_status = 'success' THEN 1 ELSE 0 END) as successful_transactions,
                    SUM(CASE WHEN transaction_status = 'failed' THEN 1 ELSE 0 END) as failed_transactions
                FROM payment_transactions
                WHERE DATE(created_at) BETWEEN ? AND ?
                GROUP BY payment_gateway
            ");
            $stmt->execute([$date_from, $date_to]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            fputcsv($output, ['Payment Gateway', 'Transaction Count', 'Total Amount', 'Successful', 'Failed']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['payment_gateway'],
                    $row['transaction_count'],
                    $row['total_amount'],
                    $row['successful_transactions'],
                    $row['failed_transactions']
                ]);
            }
            break;

        case 'delivery_performance':
            // Export delivery performance
            $stmt = $pdo->prepare("
                SELECT
                    dp.partner_name,
                    COUNT(da.assignment_id) as total_assignments,
                    SUM(CASE WHEN da.delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
                    SUM(CASE WHEN da.delivery_status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                    AVG(da.delivery_charges) as avg_delivery_charge
                FROM delivery_assignments da
                JOIN delivery_partners dp ON da.partner_id = dp.partner_id
                WHERE DATE(da.created_at) BETWEEN ? AND ?
                GROUP BY dp.partner_id, dp.partner_name
            ");
            $stmt->execute([$date_from, $date_to]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            fputcsv($output, ['Delivery Partner', 'Total Assignments', 'Delivered', 'Failed', 'Avg Delivery Charge']);
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['partner_name'],
                    $row['total_assignments'],
                    $row['delivered_count'],
                    $row['failed_count'],
                    round($row['avg_delivery_charge'], 2)
                ]);
            }
            break;
    }

    fclose($output);
    exit;
}

// Handle API requests for chart data
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    if ($_GET['api'] === 'chart_data') {
        $chart_type = $_GET['chart_type'];
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-d');

        switch ($chart_type) {
            case 'daily_sales':
                $stmt = $pdo->prepare("
                    SELECT
                        DATE(created_at) as date,
                        SUM(total_amount) as revenue,
                        COUNT(*) as orders
                    FROM checkout_orders
                    WHERE DATE(created_at) BETWEEN ? AND ?
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC
                ");
                $stmt->execute([$date_from, $date_to]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'payment_methods':
                $stmt = $pdo->prepare("
                    SELECT
                        payment_method,
                        COUNT(*) as count,
                        SUM(total_amount) as total
                    FROM checkout_orders
                    WHERE DATE(created_at) BETWEEN ? AND ?
                    GROUP BY payment_method
                ");
                $stmt->execute([$date_from, $date_to]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                break;

            case 'order_status':
                $stmt = $pdo->prepare("
                    SELECT
                        order_status,
                        COUNT(*) as count
                    FROM checkout_orders
                    WHERE DATE(created_at) BETWEEN ? AND ?
                    GROUP BY order_status
                ");
                $stmt->execute([$date_from, $date_to]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $data]);
                break;
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid API request']);
    exit;
}

// Get date range for reports
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today
$report_type = $_GET['report_type'] ?? 'overview';

// Sales Overview Report
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_order_value,
        SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    FROM checkout_orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$date_from, $date_to]);
$sales_overview = $stmt->fetch(PDO::FETCH_ASSOC);

// Daily Sales Trend
$stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as order_date,
        COUNT(*) as orders_count,
        SUM(total_amount) as daily_revenue
    FROM checkout_orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY order_date DESC
    LIMIT 30
");
$stmt->execute([$date_from, $date_to]);
$daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Transaction Performance
$stmt = $pdo->prepare("
    SELECT 
        payment_gateway,
        COUNT(*) as transaction_count,
        SUM(amount) as total_amount,
        SUM(CASE WHEN transaction_status = 'success' THEN 1 ELSE 0 END) as successful_transactions,
        SUM(CASE WHEN transaction_status = 'failed' THEN 1 ELSE 0 END) as failed_transactions,
        ROUND(AVG(CASE WHEN transaction_status = 'success' THEN amount END), 2) as avg_success_amount
    FROM payment_transactions 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_gateway
");
$stmt->execute([$date_from, $date_to]);
$transaction_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Delivery Performance
$stmt = $pdo->prepare("
    SELECT 
        dp.partner_name,
        COUNT(da.assignment_id) as total_assignments,
        SUM(CASE WHEN da.delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
        SUM(CASE WHEN da.delivery_status = 'failed' THEN 1 ELSE 0 END) as failed_count,
        AVG(da.delivery_charges) as avg_delivery_charge,
        AVG(TIMESTAMPDIFF(HOUR, da.created_at, da.actual_delivery)) as avg_delivery_time_hours
    FROM delivery_assignments da
    JOIN delivery_partners dp ON da.partner_id = dp.partner_id
    WHERE DATE(da.created_at) BETWEEN ? AND ?
    GROUP BY dp.partner_id, dp.partner_name
");
$stmt->execute([$date_from, $date_to]);
$delivery_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top Products (if order_items table exists)
$top_products = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            oi.product_name,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.price * oi.quantity) as total_revenue,
            COUNT(DISTINCT oi.order_id) as order_count
        FROM order_items oi
        JOIN checkout_orders co ON oi.order_id = co.order_id
        WHERE DATE(co.created_at) BETWEEN ? AND ?
        GROUP BY oi.product_name
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    $stmt->execute([$date_from, $date_to]);
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist
}

// Customer Analytics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT email) as unique_customers,
        COUNT(*) as total_orders,
        ROUND(COUNT(*) / COUNT(DISTINCT email), 2) as avg_orders_per_customer,
        MAX(total_amount) as highest_order_value,
        MIN(total_amount) as lowest_order_value
    FROM checkout_orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$date_from, $date_to]);
$customer_analytics = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Alpha Nutrition OMS</title>
    <link rel="stylesheet" href="oms-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="oms-container">
        <!-- Sidebar -->
        <aside class="oms-sidebar">
            <div class="oms-logo">
                <i class="fas fa-shopping-cart"></i>
                <h2>Alpha OMS</h2>
            </div>
            
            <nav class="oms-nav">
                <a href="index.php" class="nav-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Orders</span>
                </a>
                <a href="transactions.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Transactions</span>
                </a>
                <a href="delivery.php" class="nav-item">
                    <i class="fas fa-truck"></i>
                    <span>Delivery</span>
                </a>
                <a href="reports.php" class="nav-item active">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="activity-log.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>Activity Log</span>
                </a>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="nav-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="oms-main">
            <div class="oms-header">
                <h1>Reports & Analytics</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="exportReport()">
                        <i class="fas fa-download"></i>
                        Export Report
                    </button>
                    <button class="btn btn-outline" onclick="printReport()">
                        <i class="fas fa-print"></i>
                        Print
                    </button>
                </div>
            </div>

            <!-- Report Filters -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Report Filters</h3>
                </div>
                <div class="card-content">
                    <form method="GET" class="filter-form">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label for="report_type">Report Type</label>
                                <select name="report_type" id="report_type">
                                    <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                                    <option value="sales" <?php echo $report_type === 'sales' ? 'selected' : ''; ?>>Sales Analysis</option>
                                    <option value="transactions" <?php echo $report_type === 'transactions' ? 'selected' : ''; ?>>Transaction Analysis</option>
                                    <option value="delivery" <?php echo $report_type === 'delivery' ? 'selected' : ''; ?>>Delivery Performance</option>
                                    <option value="products" <?php echo $report_type === 'products' ? 'selected' : ''; ?>>Product Performance</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_from">From Date</label>
                                <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_to">To Date</label>
                                <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-chart-line"></i>
                                    Generate Report
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sales Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($sales_overview['total_orders'] ?? 0); ?></h3>
                        <p>Total Orders</p>
                        <span class="stat-change neutral"><?php echo $date_from; ?> to <?php echo $date_to; ?></span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($sales_overview['total_revenue'] ?? 0, 0); ?></h3>
                        <p>Total Revenue</p>
                        <span class="stat-change positive">Avg: ₹<?php echo number_format($sales_overview['avg_order_value'] ?? 0, 0); ?></span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($sales_overview['paid_orders'] ?? 0); ?></h3>
                        <p>Paid Orders</p>
                        <span class="stat-change positive"><?php echo $sales_overview['total_orders'] > 0 ? round(($sales_overview['paid_orders'] / $sales_overview['total_orders']) * 100, 1) : 0; ?>% success rate</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($customer_analytics['unique_customers'] ?? 0); ?></h3>
                        <p>Unique Customers</p>
                        <span class="stat-change neutral">Avg: <?php echo $customer_analytics['avg_orders_per_customer'] ?? 0; ?> orders/customer</span>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="dashboard-grid">
                <!-- Daily Sales Chart -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Daily Sales Trend</h3>
                    </div>
                    <div class="card-content">
                        <canvas id="dailySalesChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Transaction Performance -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Payment Gateway Performance</h3>
                    </div>
                    <div class="card-content">
                        <canvas id="gatewayChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Delivery Performance Table -->
            <?php if (!empty($delivery_performance)): ?>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Delivery Partner Performance</h3>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="oms-table">
                            <thead>
                                <tr>
                                    <th>Partner</th>
                                    <th>Total Assignments</th>
                                    <th>Delivered</th>
                                    <th>Failed</th>
                                    <th>Success Rate</th>
                                    <th>Avg Delivery Charge</th>
                                    <th>Avg Delivery Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($delivery_performance as $partner): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($partner['partner_name']); ?></strong></td>
                                        <td><?php echo number_format($partner['total_assignments']); ?></td>
                                        <td><span class="text-success"><?php echo number_format($partner['delivered_count']); ?></span></td>
                                        <td><span class="text-danger"><?php echo number_format($partner['failed_count']); ?></span></td>
                                        <td>
                                            <?php
                                            $success_rate = $partner['total_assignments'] > 0 ?
                                                round(($partner['delivered_count'] / $partner['total_assignments']) * 100, 1) : 0;
                                            ?>
                                            <span class="<?php echo $success_rate >= 90 ? 'text-success' : ($success_rate >= 70 ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo $success_rate; ?>%
                                            </span>
                                        </td>
                                        <td>₹<?php echo number_format($partner['avg_delivery_charge'] ?? 0, 2); ?></td>
                                        <td>
                                            <?php
                                            $avg_hours = $partner['avg_delivery_time_hours'] ?? 0;
                                            if ($avg_hours > 0) {
                                                echo round($avg_hours, 1) . ' hours';
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Top Products -->
            <?php if (!empty($top_products)): ?>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Top Performing Products</h3>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="oms-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Total Quantity Sold</th>
                                    <th>Total Revenue</th>
                                    <th>Number of Orders</th>
                                    <th>Avg Revenue per Order</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                        <td><?php echo number_format($product['total_quantity']); ?></td>
                                        <td><strong>₹<?php echo number_format($product['total_revenue'], 0); ?></strong></td>
                                        <td><?php echo number_format($product['order_count']); ?></td>
                                        <td>₹<?php echo number_format($product['total_revenue'] / $product['order_count'], 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Transaction Performance Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Transaction Analysis</h3>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="oms-table">
                            <thead>
                                <tr>
                                    <th>Payment Gateway</th>
                                    <th>Total Transactions</th>
                                    <th>Successful</th>
                                    <th>Failed</th>
                                    <th>Success Rate</th>
                                    <th>Total Amount</th>
                                    <th>Avg Success Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaction_performance as $gateway): ?>
                                    <tr>
                                        <td>
                                            <span class="gateway-badge <?php echo strtolower($gateway['payment_gateway'] ?? 'unknown'); ?>">
                                                <?php echo ucfirst($gateway['payment_gateway'] ?? 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($gateway['transaction_count']); ?></td>
                                        <td><span class="text-success"><?php echo number_format($gateway['successful_transactions']); ?></span></td>
                                        <td><span class="text-danger"><?php echo number_format($gateway['failed_transactions']); ?></span></td>
                                        <td>
                                            <?php
                                            $success_rate = $gateway['transaction_count'] > 0 ?
                                                round(($gateway['successful_transactions'] / $gateway['transaction_count']) * 100, 1) : 0;
                                            ?>
                                            <span class="<?php echo $success_rate >= 95 ? 'text-success' : ($success_rate >= 85 ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo $success_rate; ?>%
                                            </span>
                                        </td>
                                        <td><strong>₹<?php echo number_format($gateway['total_amount'], 0); ?></strong></td>
                                        <td>₹<?php echo number_format($gateway['avg_success_amount'] ?? 0, 0); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Daily Sales Chart
    const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
    const dailySalesData = <?php echo json_encode(array_reverse($daily_sales)); ?>;

    new Chart(dailySalesCtx, {
        type: 'line',
        data: {
            labels: dailySalesData.map(item => new Date(item.order_date).toLocaleDateString()),
            datasets: [{
                label: 'Daily Revenue',
                data: dailySalesData.map(item => item.daily_revenue),
                borderColor: '#4ECDC4',
                backgroundColor: 'rgba(78, 205, 196, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Order Count',
                data: dailySalesData.map(item => item.orders_count),
                borderColor: '#FF6B6B',
                backgroundColor: 'rgba(255, 107, 107, 0.1)',
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue (₹)'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Order Count'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });

    // Gateway Performance Chart
    const gatewayCtx = document.getElementById('gatewayChart').getContext('2d');
    const gatewayData = <?php echo json_encode($transaction_performance); ?>;

    new Chart(gatewayCtx, {
        type: 'doughnut',
        data: {
            labels: gatewayData.map(item => item.payment_gateway || 'Unknown'),
            datasets: [{
                data: gatewayData.map(item => item.total_amount),
                backgroundColor: [
                    '#FF6B6B',
                    '#4ECDC4',
                    '#45B7D1',
                    '#96CEB4',
                    '#FFEAA7'
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
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ₹' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    function exportReport(type) {
        const params = new URLSearchParams(window.location.search);
        params.set('export', type);
        window.location.href = 'reports.php?' + params.toString();
    }

    function printReport() {
        window.print();
    }

    function refreshCharts() {
        const dateFrom = document.querySelector('input[name="date_from"]').value;
        const dateTo = document.querySelector('input[name="date_to"]').value;

        // Refresh daily sales chart
        updateDailySalesChart(dateFrom, dateTo);

        // Refresh payment methods chart
        updatePaymentMethodsChart(dateFrom, dateTo);

        // Refresh order status chart
        updateOrderStatusChart(dateFrom, dateTo);

        showNotification('Charts updated successfully', 'success');
    }

    function updateDailySalesChart(dateFrom, dateTo) {
        fetch(`reports.php?api=chart_data&chart_type=daily_sales&date_from=${dateFrom}&date_to=${dateTo}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const chart = Chart.getChart('dailySalesChart');
                    chart.data.labels = data.data.map(item => new Date(item.date).toLocaleDateString());
                    chart.data.datasets[0].data = data.data.map(item => item.revenue);
                    chart.data.datasets[1].data = data.data.map(item => item.orders);
                    chart.update();
                }
            })
            .catch(error => console.error('Error updating daily sales chart:', error));
    }

    function updatePaymentMethodsChart(dateFrom, dateTo) {
        fetch(`reports.php?api=chart_data&chart_type=payment_methods&date_from=${dateFrom}&date_to=${dateTo}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const chart = Chart.getChart('gatewayChart');
                    chart.data.labels = data.data.map(item => item.payment_method);
                    chart.data.datasets[0].data = data.data.map(item => item.total);
                    chart.update();
                }
            })
            .catch(error => console.error('Error updating payment methods chart:', error));
    }

    function updateOrderStatusChart(dateFrom, dateTo) {
        fetch(`reports.php?api=chart_data&chart_type=order_status&date_from=${dateFrom}&date_to=${dateTo}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && document.getElementById('orderStatusChart')) {
                    const chart = Chart.getChart('orderStatusChart');
                    chart.data.labels = data.data.map(item => item.order_status);
                    chart.data.datasets[0].data = data.data.map(item => item.count);
                    chart.update();
                }
            })
            .catch(error => console.error('Error updating order status chart:', error));
    }

    function exportChart(chartId, filename) {
        const chart = Chart.getChart(chartId);
        const url = chart.toBase64Image();
        const link = document.createElement('a');
        link.download = filename + '.png';
        link.href = url;
        link.click();
        showNotification('Chart exported successfully', 'success');
    }

    function generateCustomReport() {
        const reportType = document.getElementById('custom_report_type').value;
        const dateFrom = document.querySelector('input[name="date_from"]').value;
        const dateTo = document.querySelector('input[name="date_to"]').value;

        if (!reportType) {
            showNotification('Please select a report type', 'error');
            return;
        }

        showLoader();

        // Simulate report generation
        setTimeout(() => {
            hideLoader();
            showNotification('Custom report generated successfully', 'success');

            // Download the report
            const params = new URLSearchParams();
            params.set('export', reportType);
            params.set('date_from', dateFrom);
            params.set('date_to', dateTo);
            window.location.href = 'reports.php?' + params.toString();
        }, 2000);
    }

    function showLoader() {
        if (!document.querySelector('.loader-overlay')) {
            const loader = document.createElement('div');
            loader.className = 'loader-overlay';
            loader.innerHTML = '<div class="loader"><i class="fas fa-spinner fa-spin"></i> Generating Report...</div>';
            document.body.appendChild(loader);
        }
    }

    function hideLoader() {
        const loader = document.querySelector('.loader-overlay');
        if (loader) {
            loader.remove();
        }
    }

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    // Add export buttons to charts
    document.addEventListener('DOMContentLoaded', function() {
        // Add export buttons to chart headers
        const chartHeaders = document.querySelectorAll('.dashboard-card .card-header');
        chartHeaders.forEach((header, index) => {
            if (header.querySelector('canvas')) return; // Skip if already has canvas

            const chartContainer = header.parentElement;
            const canvas = chartContainer.querySelector('canvas');

            if (canvas) {
                const exportBtn = document.createElement('button');
                exportBtn.className = 'btn btn-sm btn-outline';
                exportBtn.innerHTML = '<i class="fas fa-download"></i>';
                exportBtn.onclick = () => exportChart(canvas.id, canvas.id.replace('Chart', '_chart'));

                const actionsDiv = header.querySelector('.card-actions') || document.createElement('div');
                actionsDiv.className = 'card-actions';
                actionsDiv.appendChild(exportBtn);

                if (!header.querySelector('.card-actions')) {
                    header.appendChild(actionsDiv);
                }
            }
        });
    });
    </script>
</body>
</html>
