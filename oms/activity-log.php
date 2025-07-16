<?php
session_start();
require_once '../includes/db_connection.php';

// Check if OMS admin is logged in
if (!isset($_SESSION['oms_admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle export request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $admin_filter = $_GET['admin_filter'] ?? '';
    $action_filter = $_GET['action_filter'] ?? '';
    $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $date_to = $_GET['date_to'] ?? date('Y-m-d');

    // Build query with filters
    $where_conditions = ["DATE(al.created_at) BETWEEN ? AND ?"];
    $params = [$date_from, $date_to];

    if ($admin_filter) {
        $where_conditions[] = "al.admin_id = ?";
        $params[] = $admin_filter;
    }

    if ($action_filter) {
        $where_conditions[] = "al.action_type LIKE ?";
        $params[] = "%$action_filter%";
    }

    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

    // Get activity logs for export
    $stmt = $pdo->prepare("
        SELECT al.*,
               au.name as admin_name,
               au.email as admin_email
        FROM activity_log al
        LEFT JOIN admin_users au ON al.admin_id = au.admin_id
        $where_clause
        ORDER BY al.created_at DESC
        LIMIT 1000
    ");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_log_' . date('Y-m-d_H-i-s') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, [
        'Date/Time', 'Admin Name', 'Admin Email', 'Action Type', 'Description',
        'Affected Table', 'Affected Record ID', 'IP Address'
    ]);

    // CSV data
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['created_at'],
            $log['admin_name'] ?? 'Unknown',
            $log['admin_email'] ?? 'Unknown',
            $log['action_type'],
            $log['action_description'],
            $log['affected_table'] ?? '',
            $log['affected_record_id'] ?? '',
            $log['ip_address'] ?? ''
        ]);
    }

    fclose($output);
    exit;
}

// Handle API requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    if ($_GET['api'] === 'recent_activities') {
        try {
            $stmt = $pdo->prepare("
                SELECT al.*,
                       au.name as admin_name
                FROM activity_log al
                LEFT JOIN admin_users au ON al.admin_id = au.admin_id
                ORDER BY al.created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'activities' => $recent_logs]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['api'] === 'activity_stats') {
        try {
            $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
            $date_to = $_GET['date_to'] ?? date('Y-m-d');

            $stmt = $pdo->prepare("
                SELECT
                    COUNT(*) as total_activities,
                    COUNT(DISTINCT admin_id) as active_admins,
                    COUNT(DISTINCT action_type) as unique_actions,
                    COUNT(DISTINCT DATE(created_at)) as active_days
                FROM activity_log
                WHERE DATE(created_at) BETWEEN ? AND ?
            ");
            $stmt->execute([$date_from, $date_to]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'stats' => $stats]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid API request']);
    exit;
}

// Get filter parameters
$admin_filter = $_GET['admin_filter'] ?? '';
$action_filter = $_GET['action_filter'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Build query with filters
$where_conditions = ["DATE(al.created_at) BETWEEN ? AND ?"];
$params = [$date_from, $date_to];

if ($admin_filter) {
    $where_conditions[] = "al.admin_id = ?";
    $params[] = $admin_filter;
}

if ($action_filter) {
    $where_conditions[] = "al.action_type LIKE ?";
    $params[] = "%$action_filter%";
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get activity logs with admin details
$stmt = $pdo->prepare("
    SELECT al.*, 
           au.name as admin_name,
           au.email as admin_email
    FROM activity_log al
    LEFT JOIN admin_users au ON al.admin_id = au.admin_id
    $where_clause
    ORDER BY al.created_at DESC
    LIMIT 500
");
$stmt->execute($params);
$activity_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get admin users for filter
$stmt = $pdo->query("SELECT admin_id, name, email FROM admin_users ORDER BY name");
$admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get activity statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_activities,
        COUNT(DISTINCT admin_id) as active_admins,
        COUNT(DISTINCT action_type) as unique_actions,
        COUNT(DISTINCT DATE(created_at)) as active_days
    FROM activity_log
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$date_from, $date_to]);
$activity_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get top actions
$stmt = $pdo->prepare("
    SELECT action_type, COUNT(*) as count
    FROM activity_log
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY action_type
    ORDER BY count DESC
    LIMIT 10
");
$stmt->execute([$date_from, $date_to]);
$top_actions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Alpha Nutrition OMS</title>
    <link rel="stylesheet" href="oms-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <a href="reports.php" class="nav-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
                <a href="activity-log.php" class="nav-item active">
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
                <h1>Activity Log</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="refreshPage()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                    <button class="btn btn-outline" onclick="exportLog()">
                        <i class="fas fa-download"></i>
                        Export Log
                    </button>
                </div>
            </div>

            <!-- Activity Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($activity_stats['total_activities'] ?? 0); ?></h3>
                        <p>Total Activities</p>
                        <span class="stat-change neutral"><?php echo $date_from; ?> to <?php echo $date_to; ?></span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon info">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($activity_stats['active_admins'] ?? 0); ?></h3>
                        <p>Active Admins</p>
                        <span class="stat-change positive">Unique users</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($activity_stats['unique_actions'] ?? 0); ?></h3>
                        <p>Action Types</p>
                        <span class="stat-change neutral">Different actions</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($activity_stats['active_days'] ?? 0); ?></h3>
                        <p>Active Days</p>
                        <span class="stat-change positive">Days with activity</span>
                    </div>
                </div>
            </div>

            <!-- Top Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Most Frequent Actions</h3>
                </div>
                <div class="card-content">
                    <div class="top-actions-grid">
                        <?php foreach ($top_actions as $action): ?>
                            <div class="action-item">
                                <div class="action-info">
                                    <h4><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $action['action_type']))); ?></h4>
                                    <p><?php echo number_format($action['count']); ?> times</p>
                                </div>
                                <div class="action-icon">
                                    <i class="fas fa-<?php 
                                        echo match($action['action_type']) {
                                            'order_created', 'order_updated' => 'shopping-bag',
                                            'delivery_assigned', 'delivery_updated' => 'truck',
                                            'transaction_updated' => 'credit-card',
                                            'login', 'logout' => 'sign-in-alt',
                                            default => 'cog'
                                        };
                                    ?>"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Filter Activity Log</h3>
                </div>
                <div class="card-content">
                    <form method="GET" class="filter-form">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label for="admin_filter">Admin User</label>
                                <select name="admin_filter" id="admin_filter">
                                    <option value="">All Admins</option>
                                    <?php foreach ($admin_users as $admin): ?>
                                        <option value="<?php echo $admin['admin_id']; ?>" 
                                                <?php echo $admin_filter === $admin['admin_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($admin['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="action_filter">Action Type</label>
                                <input type="text" name="action_filter" id="action_filter" 
                                       value="<?php echo htmlspecialchars($action_filter); ?>"
                                       placeholder="e.g., order, delivery, transaction">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_from">From Date</label>
                                <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_to">To Date</label>
                                <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                                Apply Filters
                            </button>
                            <a href="activity-log.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activity Log Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Activity History</h3>
                    <div class="card-actions">
                        <span class="record-count"><?php echo count($activity_logs); ?> activities</span>
                    </div>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="oms-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Affected Table</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activity_logs as $log): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo date('M j, Y', strtotime($log['created_at'])); ?></strong>
                                            <br>
                                            <small><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($log['admin_name']): ?>
                                                <strong><?php echo htmlspecialchars($log['admin_name']); ?></strong>
                                                <br>
                                                <small><?php echo htmlspecialchars($log['admin_email']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">System</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="action-badge <?php echo $log['action_type']; ?>">
                                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $log['action_type']))); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="activity-description">
                                                <?php echo htmlspecialchars($log['action_description']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($log['affected_table']): ?>
                                                <code><?php echo htmlspecialchars($log['affected_table']); ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($log['ip_address']): ?>
                                                <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($log['old_values'] || $log['new_values']): ?>
                                                    <button class="btn btn-sm btn-outline" onclick="viewLogDetails('<?php echo $log['log_id']; ?>')">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($log['affected_record_id']): ?>
                                                    <button class="btn btn-sm btn-secondary" onclick="viewRecord('<?php echo $log['affected_table']; ?>', '<?php echo $log['affected_record_id']; ?>')">
                                                        <i class="fas fa-external-link-alt"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Log Details Modal -->
    <div id="logDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Activity Log Details</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div id="logDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
    function refreshPage() {
        location.reload();
    }

    function exportLog() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', '1');
        window.location.href = 'activity-log.php?' + params.toString();
    }

    function viewLogDetails(logId) {
        // For now, show a simple alert - can be enhanced later
        alert('Log details functionality will be implemented');
    }

    function viewRecord(table, recordId) {
        // Navigate to the appropriate page based on table
        switch(table) {
            case 'checkout_orders':
                window.location.href = `orders.php?view=${recordId}`;
                break;
            case 'delivery_assignments':
                window.location.href = `delivery.php?assignment=${recordId}`;
                break;
            case 'payment_transactions':
                window.location.href = `transactions.php?transaction=${recordId}`;
                break;
            default:
                alert('Record view not available for this table');
        }
    }

    function closeModal() {
        document.getElementById('logDetailsModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('logDetailsModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    // Auto-refresh every 30 seconds
    setInterval(function() {
        if (!document.querySelector('.modal').style.display || document.querySelector('.modal').style.display === 'none') {
            location.reload();
        }
    }, 30000);
    </script>
</body>
</html>
