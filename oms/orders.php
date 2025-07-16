<?php
session_start();
require_once '../includes/db_connection.php';

// Check if OMS admin is logged in
if (!isset($_SESSION['oms_admin_id'])) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';

// Handle API requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    if ($_GET['api'] === 'get_order_details' && isset($_GET['order_id'])) {
        try {
            $orderId = $_GET['order_id'];

            // Get order details
            $stmt = $pdo->prepare("
                SELECT co.*,
                       CONCAT(co.first_name, ' ', co.last_name) as customer_name
                FROM checkout_orders co
                WHERE co.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
                exit;
            }

            // Get order items
            $stmt = $pdo->prepare("
                SELECT oi.*,
                       p.name as product_name_from_db,
                       COALESCE(pi.image_url, '') as product_image
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE oi.order_id = ?
                ORDER BY oi.created_at
            ");
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Use stored product name if available, fallback to database name
            foreach ($items as &$item) {
                if (empty($item['product_name']) && !empty($item['product_name_from_db'])) {
                    $item['product_name'] = $item['product_name_from_db'];
                }
            }

            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid API request']);
    exit;
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        // Build the same query as the main orders list
        $whereConditions = [];
        $params = [];

        $statusFilter = $_GET['status'] ?? '';
        $dateFilter = $_GET['date'] ?? '';
        $search = $_GET['search'] ?? '';

        if ($statusFilter) {
            $whereConditions[] = "co.order_status = ?";
            $params[] = $statusFilter;
        }

        if ($dateFilter) {
            $whereConditions[] = "DATE(co.created_at) = ?";
            $params[] = $dateFilter;
        }

        if ($search) {
            $whereConditions[] = "(co.order_number LIKE ? OR co.first_name LIKE ? OR co.last_name LIKE ? OR co.email LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $query = "
            SELECT co.*,
                   CONCAT(co.first_name, ' ', co.last_name) as customer_name,
                   COUNT(oi.order_item_id) as item_count,
                   MAX(da.delivery_status) as delivery_status,
                   MAX(dp.partner_name) as partner_name
            FROM checkout_orders co
            LEFT JOIN order_items oi ON co.order_id = oi.order_id
            LEFT JOIN delivery_assignments da ON co.order_id = da.order_id
            LEFT JOIN delivery_partners dp ON da.partner_id = dp.partner_id
            $whereClause
            GROUP BY co.order_id
            ORDER BY co.created_at DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d_H-i-s') . '.csv"');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, [
            'Order ID', 'Order Number', 'Customer Name', 'Email', 'Phone',
            'Total Amount', 'Order Status', 'Payment Status', 'Payment Method',
            'Items Count', 'Delivery Partner', 'Delivery Status', 'Order Date'
        ]);

        // CSV data
        foreach ($orders as $order) {
            fputcsv($output, [
                $order['order_id'],
                $order['order_number'] ?? 'ORD-' . substr($order['order_id'], 0, 8),
                $order['customer_name'],
                $order['email'],
                $order['phone'],
                $order['total_amount'],
                $order['order_status'],
                $order['payment_status'],
                $order['payment_method'],
                $order['item_count'],
                $order['partner_name'] ?? 'Not Assigned',
                $order['delivery_status'] ?? 'Not Assigned',
                $order['created_at']
            ]);
        }

        fclose($output);
        exit;
    } catch (Exception $e) {
        header('Location: orders.php?error=' . urlencode('Export failed: ' . $e->getMessage()));
        exit;
    }
}

// Handle bulk operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_update_status'])) {
    try {
        $selectedOrders = $_POST['selected_orders'] ?? [];
        $newStatus = $_POST['new_status'];

        if (empty($selectedOrders)) {
            throw new Exception('No orders selected');
        }

        $placeholders = str_repeat('?,', count($selectedOrders) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE checkout_orders SET order_status = ? WHERE order_id IN ($placeholders)");
        $params = array_merge([$newStatus], $selectedOrders);
        $stmt->execute($params);

        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (log_id, admin_id, action_type, action_description)
            VALUES (?, ?, 'bulk_status_update', ?)
        ");
        $stmt->execute([
            bin2hex(random_bytes(16)),
            $_SESSION['oms_admin_id'],
            "Bulk updated " . count($selectedOrders) . " orders to $newStatus status"
        ]);

        $success = count($selectedOrders) . ' orders updated successfully';
    } catch (Exception $e) {
        $error = 'Bulk operation failed: ' . $e->getMessage();
    }
}

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = $_POST['order_id'] ?? '';
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'update_status':
                $newStatus = $_POST['new_status'];
                $notes = $_POST['notes'] ?? '';
                
                // Update order status
                $stmt = $pdo->prepare("UPDATE checkout_orders SET order_status = ?, updated_at = NOW() WHERE order_id = ?");
                $stmt->execute([$newStatus, $orderId]);
                
                // Log status change (if order_status_history table exists)
                try {
                    $historyId = bin2hex(random_bytes(18));
                    $stmt = $pdo->prepare("
                        INSERT INTO order_status_history (history_id, order_id, new_status, changed_by, notes)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$historyId, $orderId, $newStatus, $_SESSION['oms_admin_id'], $notes]);
                } catch (Exception $e) {
                    // Table might not exist yet, continue without logging
                }
                
                $success = 'Order status updated successfully';
                break;
                
            case 'assign_delivery':
                $partnerId = $_POST['partner_id'];
                $estimatedDelivery = $_POST['estimated_delivery'];
                
                $assignmentId = bin2hex(random_bytes(18));
                $stmt = $pdo->prepare("
                    INSERT INTO delivery_assignments (assignment_id, order_id, partner_id, assigned_by, estimated_delivery) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$assignmentId, $orderId, $partnerId, $_SESSION['oms_admin_id'], $estimatedDelivery]);
                
                $success = 'Delivery partner assigned successfully';
                break;
        }
    } catch (Exception $e) {
        $error = 'Operation failed: ' . $e->getMessage();
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter) {
    $whereConditions[] = "co.order_status = ?";
    $params[] = $statusFilter;
}

if ($dateFilter) {
    $whereConditions[] = "DATE(co.created_at) = ?";
    $params[] = $dateFilter;
}

if ($search) {
    $whereConditions[] = "(co.order_number LIKE ? OR co.first_name LIKE ? OR co.last_name LIKE ? OR co.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get orders
$query = "
    SELECT co.*,
           CONCAT(co.first_name, ' ', co.last_name) as customer_name,
           COUNT(oi.order_item_id) as item_count,
           MAX(da.delivery_status) as delivery_status,
           MAX(dp.partner_name) as partner_name
    FROM checkout_orders co
    LEFT JOIN order_items oi ON co.order_id = oi.order_id
    LEFT JOIN delivery_assignments da ON co.order_id = da.order_id
    LEFT JOIN delivery_partners dp ON da.partner_id = dp.partner_id
    $whereClause
    GROUP BY co.order_id
    ORDER BY co.created_at DESC
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$countQuery = "SELECT COUNT(DISTINCT co.order_id) FROM checkout_orders co $whereClause";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);

// Get delivery partners for assignment
$stmt = $pdo->query("SELECT * FROM delivery_partners WHERE is_active = 1 ORDER BY partner_name");
$deliveryPartners = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Alpha OMS</title>
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
                <a href="orders.php" class="nav-item active">
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
                <h1>Orders Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="exportOrders()">
                        <i class="fas fa-download"></i>
                        Export Orders
                    </button>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filters-section">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Search orders..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                    
                    <a href="orders.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                </form>
            </div>

            <!-- Bulk Operations -->
            <div class="bulk-operations" style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); border: 1px solid #e0e0e0; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <label style="font-weight: 600; color: #333;">Bulk Actions:</label>
                        <select id="bulkStatus" style="padding: 8px 12px; border: 2px solid #333; border-radius: 6px; font-size: 0.9rem;">
                            <option value="">Select Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button type="button" class="btn btn-primary" onclick="bulkUpdateStatus()">
                            <i class="fas fa-edit"></i>
                            Update Selected
                        </button>
                    </div>
                    <div style="margin-left: auto;">
                        <button type="button" class="btn btn-secondary" onclick="exportOrders()">
                            <i class="fas fa-download"></i>
                            Export CSV
                        </button>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="table-container">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAll" onchange="selectAllOrders()" style="transform: scale(1.2);">
                            </th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Delivery</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_orders[]" value="<?php echo $order['order_id']; ?>" style="transform: scale(1.2);">
                                </td>
                                <td>
                                    <strong>#<?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . $order['order_id']); ?></strong>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                        <br>
                                        <small><?php echo htmlspecialchars($order['email']); ?></small>
                                    </div>
                                </td>
                                <td><?php echo $order['item_count']; ?> items</td>
                                <td>₹<?php echo number_format($order['total_amount'], 0); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $order['order_status']; ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['partner_name']): ?>
                                        <div class="delivery-info">
                                            <strong><?php echo htmlspecialchars($order['partner_name']); ?></strong>
                                            <br>
                                            <span class="delivery-status <?php echo $order['delivery_status']; ?>">
                                                <?php echo ucfirst($order['delivery_status']); ?>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Not assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-xs btn-info" onclick="viewOrder('<?php echo $order['order_id']; ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-xs btn-primary" onclick="updateStatus('<?php echo $order['order_id']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if (!$order['partner_name']): ?>
                                            <button class="btn btn-xs btn-success" onclick="assignDelivery('<?php echo $order['order_id']; ?>')">
                                                <i class="fas fa-truck"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($statusFilter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($search); ?>" class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <span class="pagination-info">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                        (<?php echo number_format($totalOrders); ?> total orders)
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($statusFilter); ?>&date=<?php echo urlencode($dateFilter); ?>&search=<?php echo urlencode($search); ?>" class="btn btn-secondary">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Status Update Modal -->
    <div id="statusUpdateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Order Status</h3>
                <span class="close" onclick="document.getElementById('statusUpdateModal').style.display='none'">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="orders.php">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="statusOrderId">

                    <div class="form-group">
                        <label for="newStatus">New Status:</label>
                        <select name="new_status" id="newStatus" required>
                            <option value="">Select Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="statusNotes">Notes (Optional):</label>
                        <textarea name="notes" id="statusNotes" rows="3" placeholder="Add any notes about this status change..."></textarea>
                    </div>

                    <div class="settings-actions">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('statusUpdateModal').style.display='none'">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delivery Assignment Modal -->
    <div id="deliveryAssignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Delivery Partner</h3>
                <span class="close" onclick="document.getElementById('deliveryAssignModal').style.display='none'">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" action="orders.php">
                    <input type="hidden" name="action" value="assign_delivery">
                    <input type="hidden" name="order_id" id="deliveryOrderId">

                    <div class="form-group">
                        <label for="partnerId">Delivery Partner:</label>
                        <select name="partner_id" id="partnerId" required>
                            <option value="">Select Partner</option>
                            <?php foreach ($deliveryPartners as $partner): ?>
                                <option value="<?php echo $partner['partner_id']; ?>">
                                    <?php echo htmlspecialchars($partner['partner_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="estimatedDelivery">Estimated Delivery:</label>
                        <input type="datetime-local" name="estimated_delivery" id="estimatedDelivery" required>
                    </div>

                    <div class="form-group">
                        <label for="deliveryCharges">Delivery Charges (₹):</label>
                        <input type="number" name="delivery_charges" id="deliveryCharges" step="0.01" min="0" value="50.00">
                    </div>

                    <div class="settings-actions">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('deliveryAssignModal').style.display='none'">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Partner</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="modal">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h3>Order Details</h3>
                <span class="close" onclick="closeOrderModal()">&times;</span>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading order details...
                </div>
            </div>
        </div>
    </div>

    <script>
    function viewOrder(orderId) {
        console.log('viewOrder called with orderId:', orderId);
        alert('Button clicked! Order ID: ' + orderId); // Temporary test
        // Open order details modal
        const modal = document.getElementById('orderDetailsModal');
        if (modal) {
            modal.style.display = 'block';
            loadOrderDetails(orderId);
        } else {
            console.error('Modal element not found');
            alert('Modal element not found!');
        }
    }

    function updateStatus(orderId) {
        // Open status update modal
        const modal = document.getElementById('statusUpdateModal');
        document.getElementById('statusOrderId').value = orderId;
        modal.style.display = 'block';
    }

    function assignDelivery(orderId) {
        // Open delivery assignment modal
        const modal = document.getElementById('deliveryAssignModal');
        document.getElementById('deliveryOrderId').value = orderId;
        modal.style.display = 'block';
    }

    function exportOrders() {
        // Export orders functionality
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('export', 'csv');
        window.location.href = currentUrl.toString();
    }

    function bulkUpdateStatus() {
        const selectedOrders = Array.from(document.querySelectorAll('input[name="selected_orders[]"]:checked'))
            .map(cb => cb.value);

        if (selectedOrders.length === 0) {
            showNotification('Please select at least one order', 'error');
            return;
        }

        const newStatus = document.getElementById('bulkStatus').value;
        if (!newStatus) {
            showNotification('Please select a status', 'error');
            return;
        }

        if (confirm(`Update ${selectedOrders.length} order(s) to ${newStatus} status?`)) {
            const formData = new FormData();
            formData.append('bulk_update_status', '1');
            formData.append('new_status', newStatus);
            selectedOrders.forEach(orderId => {
                formData.append('selected_orders[]', orderId);
            });

            fetch('orders.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                showNotification('Orders updated successfully', 'success');
                setTimeout(() => location.reload(), 1500);
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error updating orders', 'error');
            });
        }
    }

    function selectAllOrders() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('input[name="selected_orders[]"]');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
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

    function loadOrderDetails(orderId) {
        console.log('loadOrderDetails called with orderId:', orderId);
        fetch(`orders.php?api=get_order_details&order_id=${orderId}`)
            .then(response => {
                console.log('Response received:', response);
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data);
                if (data.success) {
                    displayOrderDetails(data.order, data.items);
                } else {
                    document.getElementById('orderDetailsContent').innerHTML =
                        '<div class="error-message">Failed to load order details: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                console.error('Error loading order details:', error);
                document.getElementById('orderDetailsContent').innerHTML =
                    '<div class="error-message">Error loading order details. Please try again.</div>';
            });
    }

    function displayOrderDetails(order, items) {
        const content = `
            <div class="order-details-container">
                <div class="order-summary">
                    <div class="summary-row">
                        <div class="summary-item">
                            <label>Order Number:</label>
                            <span class="order-number">#${order.order_number || 'ORD-' + order.order_id.substring(0, 8)}</span>
                        </div>
                        <div class="summary-item">
                            <label>Order Date:</label>
                            <span>${new Date(order.created_at).toLocaleDateString('en-IN', {
                                year: 'numeric', month: 'long', day: 'numeric',
                                hour: '2-digit', minute: '2-digit'
                            })}</span>
                        </div>
                        <div class="summary-item">
                            <label>Status:</label>
                            <span class="status-badge ${order.order_status}">${order.order_status.charAt(0).toUpperCase() + order.order_status.slice(1)}</span>
                        </div>
                    </div>
                </div>

                <div class="customer-details">
                    <h4>Customer Information</h4>
                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Name:</label>
                            <span>${order.first_name} ${order.last_name}</span>
                        </div>
                        <div class="detail-item">
                            <label>Email:</label>
                            <span>${order.email}</span>
                        </div>
                        <div class="detail-item">
                            <label>Phone:</label>
                            <span>${order.phone}</span>
                        </div>
                        <div class="detail-item full-width">
                            <label>Address:</label>
                            <span>${order.address}, ${order.city}, ${order.state} - ${order.pincode}</span>
                        </div>
                    </div>
                </div>

                <div class="order-items">
                    <h4>Order Items</h4>
                    <div class="items-list">
                        ${items.map(item => `
                            <div class="item-row">
                                <div class="item-info">
                                    <strong>${item.product_name}</strong>
                                    ${item.variant_name ? `<br><small>Variant: ${item.variant_name}</small>` : ''}
                                </div>
                                <div class="item-quantity">Qty: ${item.quantity}</div>
                                <div class="item-price">₹${parseFloat(item.price).toFixed(0)}</div>
                                <div class="item-total">₹${parseFloat(item.total).toFixed(0)}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <span>₹${parseFloat(order.subtotal || order.total_amount).toFixed(0)}</span>
                    </div>
                    ${order.shipping_cost > 0 ? `
                        <div class="total-row">
                            <span>Shipping:</span>
                            <span>₹${parseFloat(order.shipping_cost).toFixed(0)}</span>
                        </div>
                    ` : ''}
                    ${order.tax_amount > 0 ? `
                        <div class="total-row">
                            <span>Tax:</span>
                            <span>₹${parseFloat(order.tax_amount).toFixed(0)}</span>
                        </div>
                    ` : ''}
                    <div class="total-row final-total">
                        <span>Total Amount:</span>
                        <span>₹${parseFloat(order.total_amount).toFixed(0)}</span>
                    </div>
                </div>

                <div class="payment-info">
                    <h4>Payment Information</h4>
                    <div class="details-grid">
                        <div class="detail-item">
                            <label>Payment Method:</label>
                            <span>${order.payment_method.toUpperCase()}</span>
                        </div>
                        <div class="detail-item">
                            <label>Payment Status:</label>
                            <span class="payment-badge ${order.payment_status}">${order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1)}</span>
                        </div>
                    </div>
                </div>

                ${order.notes ? `
                    <div class="order-notes">
                        <h4>Order Notes</h4>
                        <p>${order.notes}</p>
                    </div>
                ` : ''}
            </div>
        `;

        document.getElementById('orderDetailsContent').innerHTML = content;
    }

    function closeOrderModal() {
        document.getElementById('orderDetailsModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('orderDetailsModal');
        if (event.target == modal) {
            closeOrderModal();
        }
    }
    </script>
</body>
</html>
