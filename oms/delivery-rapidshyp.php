<?php
session_start();
require_once '../includes/db_connection.php';

// Check if OMS admin is logged in
if (!isset($_SESSION['oms_admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle order assignment to RapidShyp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_to_rapidshyp'])) {
    try {
        $selected_orders = $_POST['selected_orders'] ?? [];
        $pickup_address = $_POST['pickup_address'] ?? 'Alpha Nutrition Warehouse, Mumbai, Maharashtra, India';
        
        if (empty($selected_orders)) {
            throw new Exception('Please select at least one order to assign.');
        }
        
        // Get RapidShyp partner ID
        $stmt = $pdo->prepare("SELECT partner_id FROM delivery_partners WHERE partner_name = 'RapidShyp' LIMIT 1");
        $stmt->execute();
        $rapidshyp_partner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rapidshyp_partner) {
            throw new Exception('RapidShyp partner not found in system.');
        }
        
        $success_count = 0;
        foreach ($selected_orders as $order_id) {
            // Get order details
            $stmt = $pdo->prepare("SELECT * FROM checkout_orders WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                // Generate assignment ID
                $assignment_id = bin2hex(random_bytes(16));
                
                // Create delivery address
                $delivery_address = $order['address'] . ', ' . $order['city'] . ', ' . $order['state'] . ' - ' . $order['pincode'];
                
                // Calculate estimated delivery (2.5 days from now - RapidShyp standard)
                $estimated_delivery = date('Y-m-d H:i:s', strtotime('+60 hours'));
                
                // Insert delivery assignment
                $stmt = $pdo->prepare("
                    INSERT INTO delivery_assignments 
                    (assignment_id, order_id, partner_id, assigned_by, pickup_address, delivery_address, estimated_delivery, delivery_charges, delivery_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'assigned')
                ");
                $stmt->execute([
                    $assignment_id, $order_id, $rapidshyp_partner['partner_id'], 
                    $_SESSION['oms_admin_id'], $pickup_address, $delivery_address, 
                    $estimated_delivery, 42.00 // Default RapidShyp charge
                ]);
                
                // Update order status
                $stmt = $pdo->prepare("UPDATE checkout_orders SET order_status = 'processing' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                
                // Log activity
                $stmt = $pdo->prepare("
                    INSERT INTO activity_log (log_id, admin_id, action_type, action_description, affected_table, affected_record_id) 
                    VALUES (?, ?, 'delivery_assigned', ?, 'delivery_assignments', ?)
                ");
                $stmt->execute([
                    bin2hex(random_bytes(16)), $_SESSION['oms_admin_id'], 
                    "Order #{$order['order_number']} assigned to RapidShyp", $assignment_id
                ]);
                
                $success_count++;
            }
        }
        
        $success_message = "$success_count order(s) successfully assigned to RapidShyp!";
    } catch (Exception $e) {
        $error_message = "Error assigning orders: " . $e->getMessage();
    }
}

// Get orders ready for delivery (not yet assigned)
$stmt = $pdo->query("
    SELECT co.*, 
           CONCAT(co.first_name, ' ', co.last_name) as customer_name,
           COUNT(oi.order_item_id) as item_count
    FROM checkout_orders co 
    LEFT JOIN order_items oi ON co.order_id = oi.order_id
    LEFT JOIN delivery_assignments da ON co.order_id = da.order_id
    WHERE co.payment_status = 'paid' 
    AND co.order_status IN ('confirmed', 'pending', 'processing')
    AND da.assignment_id IS NULL
    GROUP BY co.order_id 
    ORDER BY co.created_at DESC
");
$available_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get orders already assigned to RapidShyp
$stmt = $pdo->query("
    SELECT co.*, 
           CONCAT(co.first_name, ' ', co.last_name) as customer_name,
           da.assignment_id,
           da.delivery_status,
           da.tracking_number,
           da.estimated_delivery,
           da.created_at as assigned_at
    FROM checkout_orders co 
    JOIN delivery_assignments da ON co.order_id = da.order_id
    JOIN delivery_partners dp ON da.partner_id = dp.partner_id
    WHERE dp.partner_name = 'RapidShyp'
    ORDER BY da.created_at DESC
");
$rapidshyp_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get RapidShyp statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_assigned,
        SUM(CASE WHEN da.delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN da.delivery_status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
        SUM(CASE WHEN da.delivery_status = 'assigned' THEN 1 ELSE 0 END) as pending,
        AVG(da.delivery_charges) as avg_charges
    FROM delivery_assignments da
    JOIN delivery_partners dp ON da.partner_id = dp.partner_id
    WHERE dp.partner_name = 'RapidShyp'
");
$rapidshyp_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RapidShyp Orders - Alpha Nutrition OMS</title>
    <link rel="stylesheet" href="oms-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .rapidshyp-header {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .rapidshyp-logo {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .provider-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .provider-stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #9b59b6;
        }
        .order-selection-card {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .order-checkbox {
            transform: scale(1.2);
            margin-right: 10px;
        }
        .selected-order {
            background-color: #f3e5f5;
            border-color: #9c27b0;
        }
        .rapidshyp-feature {
            background: linear-gradient(45deg, #9b59b6, #8e44ad);
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
            text-align: center;
        }
        .cost-effective-badge {
            background: #4caf50;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            margin-left: 10px;
        }
    </style>
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
                <a href="delivery.php" class="nav-item active">
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
            <!-- RapidShyp Header -->
            <div class="rapidshyp-header">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div class="rapidshyp-logo">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h1>RapidShyp Order Management</h1>
                        <p>Rapid & reliable shipping solutions with best rates <span class="cost-effective-badge">Cost Effective</span></p>
                        <div class="rapidshyp-feature">
                            <i class="fas fa-percentage"></i> Best Rates • <i class="fas fa-headset"></i> 24/7 Support • <i class="fas fa-map-marker-alt"></i> All India Coverage
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <a href="delivery.php" class="btn btn-light">
                            <i class="fas fa-arrow-left"></i>
                            Back to Delivery
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- RapidShyp Statistics -->
            <div class="provider-stats-grid">
                <div class="provider-stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($rapidshyp_stats['total_assigned'] ?? 0); ?></h3>
                        <p>Total Assigned</p>
                    </div>
                </div>
                
                <div class="provider-stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($rapidshyp_stats['delivered'] ?? 0); ?></h3>
                        <p>Delivered</p>
                    </div>

            <!-- Order Selection for Assignment -->
            <?php if (!empty($available_orders)): ?>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Available Orders for RapidShyp Assignment</h3>
                    <p class="card-subtitle">Select orders to assign to RapidShyp cost-effective delivery</p>
                </div>
                <div class="card-content">
                    <form method="POST" id="rapidshypAssignmentForm">
                        <input type="hidden" name="assign_to_rapidshyp" value="1">

                        <div class="order-selection-card">
                            <h4><i class="fas fa-bolt"></i> Cost-Effective Delivery Assignment</h4>
                            <p>Choose orders for RapidShyp's reliable and affordable delivery service</p>
                            <div style="margin: 15px 0;">
                                <button type="button" class="btn btn-outline" onclick="selectAllOrders()">
                                    <i class="fas fa-check-double"></i> Select All
                                </button>
                                <button type="button" class="btn btn-outline" onclick="clearAllOrders()">
                                    <i class="fas fa-times"></i> Clear All
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="pickup_address">Pickup Address</label>
                            <textarea name="pickup_address" id="pickup_address" required>Alpha Nutrition Warehouse, Mumbai, Maharashtra, India</textarea>
                        </div>

                        <div class="table-responsive">
                            <table class="oms-table">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAll" onchange="toggleAllOrders()">
                                        </th>
                                        <th>Order Details</th>
                                        <th>Customer</th>
                                        <th>Delivery Address</th>
                                        <th>Amount</th>
                                        <th>Items</th>
                                        <th>Order Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($available_orders as $order): ?>
                                        <tr class="order-row" data-order-id="<?php echo $order['order_id']; ?>">
                                            <td>
                                                <input type="checkbox" name="selected_orders[]" value="<?php echo $order['order_id']; ?>"
                                                       class="order-checkbox" onchange="updateOrderSelection(this)">
                                            </td>
                                            <td>
                                                <strong>#<?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . substr($order['order_id'], 0, 8)); ?></strong>
                                                <br>
                                                <span class="status-badge <?php echo $order['order_status']; ?>">
                                                    <?php echo ucfirst($order['order_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                                <br>
                                                <small><?php echo htmlspecialchars($order['email']); ?></small>
                                                <br>
                                                <small><?php echo htmlspecialchars($order['phone']); ?></small>
                                            </td>
                                            <td>
                                                <div class="delivery-address">
                                                    <?php echo htmlspecialchars($order['address']); ?><br>
                                                    <?php echo htmlspecialchars($order['city'] . ', ' . $order['state']); ?><br>
                                                    <strong><?php echo htmlspecialchars($order['pincode']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <strong>₹<?php echo number_format($order['total_amount'], 0); ?></strong>
                                                <br>
                                                <small><?php echo ucfirst($order['payment_status']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge"><?php echo $order['item_count']; ?> items</span>
                                            </td>
                                            <td>
                                                <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                                <br>
                                                <small><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 20px; text-align: center;">
                            <button type="submit" class="btn btn-primary btn-lg" id="assignButton" disabled>
                                <i class="fas fa-bolt"></i>
                                Assign Selected Orders to RapidShyp
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="dashboard-card">
                <div class="card-content">
                    <div class="order-selection-card">
                        <h4><i class="fas fa-info-circle"></i> No Orders Available</h4>
                        <p>All paid orders have already been assigned to delivery partners.</p>
                        <a href="orders.php" class="btn btn-primary">
                            <i class="fas fa-eye"></i>
                            View All Orders
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- RapidShyp Assigned Orders -->
            <?php if (!empty($rapidshyp_orders)): ?>
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Orders Assigned to RapidShyp</h3>
                    <div class="card-actions">
                        <span class="record-count"><?php echo count($rapidshyp_orders); ?> orders</span>
                    </div>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="oms-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Delivery Status</th>
                                    <th>Tracking</th>
                                    <th>Assigned Date</th>
                                    <th>Est. Delivery</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rapidshyp_orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . substr($order['order_id'], 0, 8)); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($order['customer_name']); ?>
                                            <br>
                                            <small><?php echo htmlspecialchars($order['email']); ?></small>
                                        </td>
                                        <td>₹<?php echo number_format($order['total_amount'], 0); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $order['delivery_status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['delivery_status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($order['tracking_number']): ?>
                                                <code><?php echo htmlspecialchars($order['tracking_number']); ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">Not Available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($order['assigned_at'])); ?>
                                            <br>
                                            <small><?php echo date('H:i', strtotime($order['assigned_at'])); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($order['estimated_delivery']): ?>
                                                <?php echo date('M j, Y', strtotime($order['estimated_delivery'])); ?>
                                            <?php else: ?>
                                                <span class="text-muted">TBD</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline" onclick="trackOrder('<?php echo $order['assignment_id']; ?>')">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    Track
                                                </button>
                                                <button class="btn btn-sm btn-secondary" onclick="updateStatus('<?php echo $order['assignment_id']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                    Update
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
    function selectAllOrders() {
        const checkboxes = document.querySelectorAll('input[name="selected_orders[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
            updateOrderSelection(checkbox);
        });
        document.getElementById('selectAll').checked = true;
        updateAssignButton();
    }

    function clearAllOrders() {
        const checkboxes = document.querySelectorAll('input[name="selected_orders[]"]');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            updateOrderSelection(checkbox);
        });
        document.getElementById('selectAll').checked = false;
        updateAssignButton();
    }

    function toggleAllOrders() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('input[name="selected_orders[]"]');

        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            updateOrderSelection(checkbox);
        });
        updateAssignButton();
    }

    function updateOrderSelection(checkbox) {
        const row = checkbox.closest('tr');
        if (checkbox.checked) {
            row.classList.add('selected-order');
        } else {
            row.classList.remove('selected-order');
        }
        updateAssignButton();
    }

    function updateAssignButton() {
        const selectedOrders = document.querySelectorAll('input[name="selected_orders[]"]:checked');
        const assignButton = document.getElementById('assignButton');

        if (selectedOrders.length > 0) {
            assignButton.disabled = false;
            assignButton.innerHTML = `<i class="fas fa-bolt"></i> Assign ${selectedOrders.length} Order(s) to RapidShyp`;
        } else {
            assignButton.disabled = true;
            assignButton.innerHTML = '<i class="fas fa-bolt"></i> Assign Selected Orders to RapidShyp';
        }
    }

    function trackOrder(assignmentId) {
        alert('Tracking functionality will be implemented with RapidShyp API integration');
    }

    function updateStatus(assignmentId) {
        window.location.href = `delivery-update.php?assignment_id=${assignmentId}`;
    }

    // Form submission confirmation
    document.getElementById('rapidshypAssignmentForm').addEventListener('submit', function(e) {
        const selectedOrders = document.querySelectorAll('input[name="selected_orders[]"]:checked');
        if (selectedOrders.length === 0) {
            e.preventDefault();
            alert('Please select at least one order to assign to RapidShyp.');
            return false;
        }

        const confirmation = confirm(`Are you sure you want to assign ${selectedOrders.length} order(s) to RapidShyp?`);
        if (!confirmation) {
            e.preventDefault();
            return false;
        }
    });
    </script>
</body>
</html>
                </div>
                
                <div class="provider-stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($rapidshyp_stats['in_transit'] ?? 0); ?></h3>
                        <p>In Transit</p>
                    </div>
                </div>
                
                <div class="provider-stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($rapidshyp_stats['pending'] ?? 0); ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
            </div>
