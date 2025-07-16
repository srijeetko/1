<?php
session_start();
require_once '../includes/db_connection.php';

// Check if OMS admin is logged in
if (!isset($_SESSION['oms_admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle API requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    if ($_GET['api'] === 'refresh_stats') {
        try {
            // Get updated statistics
            $stmt = $pdo->query("SELECT COUNT(*) FROM checkout_orders");
            $totalOrders = $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM checkout_orders WHERE DATE(created_at) = CURDATE()");
            $todayOrders = $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COUNT(*) FROM checkout_orders WHERE order_status = 'pending'");
            $pendingOrders = $stmt->fetchColumn();

            $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM checkout_orders WHERE payment_status = 'paid'");
            $totalRevenue = $stmt->fetchColumn();

            // Get recent orders
            $stmt = $pdo->query("
                SELECT co.*,
                       CONCAT(co.first_name, ' ', co.last_name) as customer_name,
                       COUNT(oi.order_item_id) as item_count
                FROM checkout_orders co
                LEFT JOIN order_items oi ON co.order_id = oi.order_id
                GROUP BY co.order_id
                ORDER BY co.created_at DESC
                LIMIT 10
            ");
            $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_orders' => (int)$totalOrders,
                    'today_orders' => (int)$todayOrders,
                    'pending_orders' => (int)$pendingOrders,
                    'total_revenue' => (float)$totalRevenue
                ],
                'recent_orders' => $recentOrders
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}

// Get dashboard statistics
try {
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) FROM checkout_orders");
    $totalOrders = $stmt->fetchColumn();
    
    // Today's orders
    $stmt = $pdo->query("SELECT COUNT(*) FROM checkout_orders WHERE DATE(created_at) = CURDATE()");
    $todayOrders = $stmt->fetchColumn();
    
    // Pending orders
    $stmt = $pdo->query("SELECT COUNT(*) FROM checkout_orders WHERE order_status = 'pending'");
    $pendingOrders = $stmt->fetchColumn();
    
    // Total revenue
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM checkout_orders WHERE payment_status = 'paid'");
    $totalRevenue = $stmt->fetchColumn();
    
    // Recent orders
    $stmt = $pdo->query("
        SELECT co.*, 
               CONCAT(co.first_name, ' ', co.last_name) as customer_name,
               COUNT(oi.order_item_id) as item_count
        FROM checkout_orders co 
        LEFT JOIN order_items oi ON co.order_id = oi.order_id 
        GROUP BY co.order_id 
        ORDER BY co.created_at DESC 
        LIMIT 10
    ");
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Order status distribution
    $stmt = $pdo->query("
        SELECT order_status, COUNT(*) as count 
        FROM checkout_orders 
        GROUP BY order_status
    ");
    $orderStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $totalOrders = $todayOrders = $pendingOrders = $totalRevenue = 0;
    $recentOrders = $orderStats = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OMS Dashboard - Alpha Nutrition</title>
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
                <a href="index.php" class="nav-item active">
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
                <a href="reports.php" class="nav-item">
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
                <h1>Order Management Dashboard</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                    <div class="admin-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($_SESSION['oms_admin_name'] ?? 'Admin'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($totalOrders); ?></h3>
                        <p>Total Orders</p>
                        <span class="stat-change positive">+12% from last month</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon today">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($todayOrders); ?></h3>
                        <p>Today's Orders</p>
                        <span class="stat-change positive">+5 from yesterday</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($pendingOrders); ?></h3>
                        <p>Pending Orders</p>
                        <span class="stat-change neutral">Needs attention</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($totalRevenue, 0); ?></h3>
                        <p>Total Revenue</p>
                        <span class="stat-change positive">+18% from last month</span>
                    </div>
                </div>
            </div>

            <!-- Charts and Recent Orders -->
            <div class="dashboard-grid">
                <!-- Order Status Chart -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Order Status Distribution</h3>
                        <div class="card-actions">
                            <button class="btn btn-sm" onclick="exportChart()">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-content">
                        <canvas id="orderStatusChart" width="400" height="200"></canvas>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>Recent Orders</h3>
                        <div class="card-actions">
                            <a href="orders.php" class="btn btn-sm">
                                <i class="fas fa-eye"></i>
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="recent-orders">
                            <?php foreach ($recentOrders as $order): ?>
                                <div class="order-item">
                                    <div class="order-info">
                                        <h4>#<?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . $order['order_id']); ?></h4>
                                        <p><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                        <small><?php echo $order['item_count']; ?> items</small>
                                    </div>
                                    <div class="order-status">
                                        <span class="status-badge <?php echo $order['order_status']; ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                        <div class="order-amount">₹<?php echo number_format($order['total_amount'], 0); ?></div>
                                    </div>
                                    <div class="order-actions">
                                        <a href="orders.php?view=<?php echo $order['order_id']; ?>" class="btn btn-xs">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h3>Quick Actions</h3>
                <div class="action-grid">
                    <a href="orders.php?filter=pending" class="action-card">
                        <i class="fas fa-clock"></i>
                        <h4>Process Pending Orders</h4>
                        <p>Review and confirm pending orders</p>
                    </a>
                    
                    <a href="delivery.php?status=ready" class="action-card">
                        <i class="fas fa-truck"></i>
                        <h4>Ready for Delivery</h4>
                        <p>Assign delivery partners</p>
                    </a>
                    
                    <a href="transactions.php?filter=failed" class="action-card">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>Failed Payments</h4>
                        <p>Review failed transactions</p>
                    </a>
                    
                    <a href="reports.php" class="action-card">
                        <i class="fas fa-chart-line"></i>
                        <h4>Generate Reports</h4>
                        <p>Sales and performance reports</p>
                    </a>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Order Status Chart
    const ctx = document.getElementById('orderStatusChart').getContext('2d');
    const orderStatusData = <?php echo json_encode($orderStats); ?>;
    
    const labels = orderStatusData.map(item => item.order_status.charAt(0).toUpperCase() + item.order_status.slice(1));
    const data = orderStatusData.map(item => item.count);
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#FF6B6B',
                    '#4ECDC4',
                    '#45B7D1',
                    '#96CEB4',
                    '#FFEAA7',
                    '#DDA0DD'
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
                }
            }
        }
    });

    function refreshDashboard() {
        showLoader();
        fetch('index.php?api=refresh_stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateDashboardStats(data.stats);
                    updateRecentOrders(data.recent_orders);
                    showNotification('Dashboard refreshed successfully', 'success');
                } else {
                    showNotification('Failed to refresh dashboard', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error refreshing dashboard', 'error');
            })
            .finally(() => {
                hideLoader();
            });
    }

    function exportChart() {
        const canvas = document.getElementById('orderStatusChart');
        const url = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = 'order-status-chart.png';
        link.href = url;
        link.click();
        showNotification('Chart exported successfully', 'success');
    }

    function updateDashboardStats(stats) {
        document.querySelector('.stat-card:nth-child(1) h3').textContent = stats.total_orders.toLocaleString();
        document.querySelector('.stat-card:nth-child(2) h3').textContent = stats.today_orders.toLocaleString();
        document.querySelector('.stat-card:nth-child(3) h3').textContent = stats.pending_orders.toLocaleString();
        document.querySelector('.stat-card:nth-child(4) h3').textContent = '₹' + stats.total_revenue.toLocaleString();
    }

    function updateRecentOrders(orders) {
        const container = document.querySelector('.recent-orders');
        container.innerHTML = '';

        orders.forEach(order => {
            const orderItem = document.createElement('div');
            orderItem.className = 'order-item';
            orderItem.innerHTML = `
                <div class="order-info">
                    <h4>#${order.order_number || 'ORD-' + order.order_id}</h4>
                    <p>${order.customer_name}</p>
                    <small>${order.item_count} items</small>
                </div>
                <div class="order-status">
                    <span class="status-badge ${order.order_status}">
                        ${order.order_status.charAt(0).toUpperCase() + order.order_status.slice(1)}
                    </span>
                    <div class="order-amount">₹${order.total_amount.toLocaleString()}</div>
                </div>
                <div class="order-actions">
                    <a href="orders.php?view=${order.order_id}" class="btn btn-xs">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            `;
            container.appendChild(orderItem);
        });
    }

    function showLoader() {
        if (!document.querySelector('.loader-overlay')) {
            const loader = document.createElement('div');
            loader.className = 'loader-overlay';
            loader.innerHTML = '<div class="loader"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
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

    // Auto-refresh every 5 minutes
    setInterval(function() {
        refreshDashboard();
    }, 300000);
    </script>
</body>
</html>
