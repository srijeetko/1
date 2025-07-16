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

    if ($_GET['api'] === 'update_tracking' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $assignment_id = $_POST['assignment_id'];
            $tracking_number = $_POST['tracking_number'];
            $delivery_status = $_POST['delivery_status'];
            $notes = $_POST['notes'] ?? '';

            // Update delivery assignment
            $stmt = $pdo->prepare("
                UPDATE delivery_assignments
                SET tracking_number = ?, delivery_status = ?, delivery_notes = ?, updated_at = NOW()
                WHERE assignment_id = ?
            ");
            $stmt->execute([$tracking_number, $delivery_status, $notes, $assignment_id]);

            // Update actual delivery time if delivered
            if ($delivery_status === 'delivered') {
                $stmt = $pdo->prepare("
                    UPDATE delivery_assignments
                    SET actual_delivery = NOW()
                    WHERE assignment_id = ?
                ");
                $stmt->execute([$assignment_id]);

                // Update order status to delivered
                $stmt = $pdo->prepare("
                    UPDATE checkout_orders
                    SET order_status = 'delivered'
                    WHERE order_id = (SELECT order_id FROM delivery_assignments WHERE assignment_id = ?)
                ");
                $stmt->execute([$assignment_id]);
            }

            // Log activity
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (log_id, admin_id, action_type, action_description, affected_table, affected_record_id)
                VALUES (?, ?, 'tracking_updated', ?, 'delivery_assignments', ?)
            ");
            $stmt->execute([
                bin2hex(random_bytes(16)), $_SESSION['oms_admin_id'],
                "Tracking updated: $tracking_number - Status: $delivery_status", $assignment_id
            ]);

            echo json_encode(['success' => true, 'message' => 'Tracking updated successfully']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['api'] === 'get_delivery_stats') {
        try {
            $stmt = $pdo->query("
                SELECT
                    COUNT(*) as total_deliveries,
                    SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN delivery_status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
                    SUM(CASE WHEN delivery_status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                    SUM(CASE WHEN delivery_status = 'picked_up' THEN 1 ELSE 0 END) as picked_up,
                    SUM(CASE WHEN delivery_status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM delivery_assignments
            ");
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

// Handle delivery assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_delivery'])) {
    try {
        $order_id = $_POST['order_id'];
        $partner_id = $_POST['partner_id'];
        $pickup_address = $_POST['pickup_address'];
        $delivery_address = $_POST['delivery_address'];
        $estimated_delivery = $_POST['estimated_delivery'];
        $delivery_charges = $_POST['delivery_charges'];
        
        // Generate assignment ID
        $assignment_id = bin2hex(random_bytes(16));
        
        // Insert delivery assignment
        $stmt = $pdo->prepare("
            INSERT INTO delivery_assignments 
            (assignment_id, order_id, partner_id, assigned_by, pickup_address, delivery_address, estimated_delivery, delivery_charges) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $assignment_id, $order_id, $partner_id, $_SESSION['oms_admin_id'], 
            $pickup_address, $delivery_address, $estimated_delivery, $delivery_charges
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
            "Delivery assigned for order #$order_id", $assignment_id
        ]);
        
        $success_message = "Delivery assigned successfully!";
    } catch (Exception $e) {
        $error_message = "Error assigning delivery: " . $e->getMessage();
    }
}

// Get delivery partners
$stmt = $pdo->query("SELECT * FROM delivery_partners WHERE is_active = 1 ORDER BY partner_name");
$delivery_partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get orders ready for delivery
$stmt = $pdo->query("
    SELECT co.*, 
           CONCAT(co.first_name, ' ', co.last_name) as customer_name,
           da.assignment_id,
           da.delivery_status,
           da.tracking_number,
           dp.partner_name
    FROM checkout_orders co 
    LEFT JOIN delivery_assignments da ON co.order_id = da.order_id
    LEFT JOIN delivery_partners dp ON da.partner_id = dp.partner_id
    WHERE co.payment_status = 'paid' 
    ORDER BY co.created_at DESC
");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get delivery statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(CASE WHEN delivery_status = 'in_transit' THEN 1 ELSE 0 END) as in_transit,
        SUM(CASE WHEN delivery_status = 'assigned' THEN 1 ELSE 0 END) as assigned
    FROM delivery_assignments
");
$delivery_stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Management - Alpha Nutrition OMS</title>
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
            <div class="oms-header">
                <h1>Delivery Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="refreshPage()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
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

            <!-- Delivery Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($delivery_stats['total_deliveries'] ?? 0); ?></h3>
                        <p>Total Deliveries</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($delivery_stats['delivered'] ?? 0); ?></h3>
                        <p>Delivered</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($delivery_stats['in_transit'] ?? 0); ?></h3>
                        <p>In Transit</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($delivery_stats['assigned'] ?? 0); ?></h3>
                        <p>Assigned</p>
                    </div>
                </div>
            </div>

            <!-- Delivery Provider Selection -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Choose Delivery Partner</h3>
                    <p class="card-subtitle">Select a delivery partner to manage orders and shipments</p>
                </div>
                <div class="card-content">
                    <style>
                        .provider-selection-grid {
                            display: grid;
                            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                            gap: 20px;
                            margin-top: 20px;
                        }
                        .provider-card {
                            background: white;
                            border-radius: 15px;
                            padding: 25px;
                            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                            cursor: pointer;
                            transition: all 0.3s ease;
                            border: 2px solid transparent;
                        }
                        .provider-card:hover {
                            transform: translateY(-5px);
                            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
                        }
                        .provider-card.delhivery {
                            border-left: 5px solid #e74c3c;
                        }
                        .provider-card.delhivery:hover {
                            border-color: #e74c3c;
                        }
                        .provider-card.shiprocket {
                            border-left: 5px solid #3498db;
                        }
                        .provider-card.shiprocket:hover {
                            border-color: #3498db;
                        }
                        .provider-card.rapidshyp {
                            border-left: 5px solid #9b59b6;
                        }
                        .provider-card.rapidshyp:hover {
                            border-color: #9b59b6;
                        }
                        .provider-header {
                            text-align: center;
                            margin-bottom: 20px;
                        }
                        .provider-logo {
                            width: 60px;
                            height: 60px;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            margin: 0 auto 15px;
                            font-size: 24px;
                            color: white;
                        }
                        .provider-card.delhivery .provider-logo {
                            background: linear-gradient(135deg, #e74c3c, #c0392b);
                        }
                        .provider-card.shiprocket .provider-logo {
                            background: linear-gradient(135deg, #3498db, #2980b9);
                        }
                        .provider-card.rapidshyp .provider-logo {
                            background: linear-gradient(135deg, #9b59b6, #8e44ad);
                        }
                        .provider-header h3 {
                            margin: 0 0 5px 0;
                            font-size: 1.5em;
                            font-weight: bold;
                        }
                        .provider-tagline {
                            color: #666;
                            font-size: 0.9em;
                            margin: 0;
                        }
                        .provider-features {
                            margin: 20px 0;
                        }
                        .feature {
                            display: flex;
                            align-items: center;
                            margin: 8px 0;
                            font-size: 0.9em;
                            color: #555;
                        }
                        .feature i {
                            width: 20px;
                            margin-right: 10px;
                            color: #666;
                        }
                        .provider-stats {
                            display: flex;
                            justify-content: space-between;
                            margin: 20px 0;
                            padding: 15px;
                            background: #f8f9fa;
                            border-radius: 8px;
                        }
                        .stat {
                            text-align: center;
                        }
                        .stat strong {
                            display: block;
                            font-size: 1.2em;
                            margin-bottom: 5px;
                        }
                        .stat span {
                            font-size: 0.8em;
                            color: #666;
                        }
                        .provider-action {
                            text-align: center;
                            margin-top: 20px;
                        }
                        .provider-action .btn {
                            width: 100%;
                            padding: 12px;
                            font-weight: bold;
                            border-radius: 8px;
                        }
                    </style>
                    <div class="provider-selection-grid">
                        <!-- Delhivery -->
                        <div class="provider-card delhivery" onclick="redirectToProvider('delhivery')">
                            <div class="provider-header">
                                <div class="provider-logo">
                                    <i class="fas fa-shipping-fast"></i>
                                </div>
                                <h3>Delhivery</h3>
                                <p class="provider-tagline">India's largest logistics company</p>
                            </div>
                            <div class="provider-features">
                                <div class="feature">
                                    <i class="fas fa-globe"></i>
                                    <span>All India + International</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-clock"></i>
                                    <span>Same Day Delivery</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>COD Available</span>
                                </div>
                            </div>
                            <div class="provider-stats">
                                <div class="stat">
                                    <strong><?php echo $delivery_stats['assigned'] ?? 0; ?></strong>
                                    <span>Pending Orders</span>
                                </div>
                                <div class="stat">
                                    <strong>₹40-200</strong>
                                    <span>Delivery Charges</span>
                                </div>
                            </div>
                            <div class="provider-action">
                                <button class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i>
                                    Manage Orders
                                </button>
                            </div>
                        </div>

                        <!-- Shiprocket -->
                        <div class="provider-card shiprocket" onclick="redirectToProvider('shiprocket')">
                            <div class="provider-header">
                                <div class="provider-logo">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <h3>Shiprocket</h3>
                                <p class="provider-tagline">Fastest growing logistics platform</p>
                            </div>
                            <div class="provider-features">
                                <div class="feature">
                                    <i class="fas fa-globe"></i>
                                    <span>All India + International</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Express Delivery</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-mobile-alt"></i>
                                    <span>Real-time Tracking</span>
                                </div>
                            </div>
                            <div class="provider-stats">
                                <div class="stat">
                                    <strong><?php echo $delivery_stats['in_transit'] ?? 0; ?></strong>
                                    <span>In Transit</span>
                                </div>
                                <div class="stat">
                                    <strong>₹35-140</strong>
                                    <span>Delivery Charges</span>
                                </div>
                            </div>
                            <div class="provider-action">
                                <button class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i>
                                    Manage Orders
                                </button>
                            </div>
                        </div>

                        <!-- RapidShyp -->
                        <div class="provider-card rapidshyp" onclick="redirectToProvider('rapidshyp')">
                            <div class="provider-header">
                                <div class="provider-logo">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <h3>RapidShyp</h3>
                                <p class="provider-tagline">Rapid & reliable shipping solutions</p>
                            </div>
                            <div class="provider-features">
                                <div class="feature">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>All India Coverage</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-percentage"></i>
                                    <span>Best Rates</span>
                                </div>
                                <div class="feature">
                                    <i class="fas fa-headset"></i>
                                    <span>24/7 Support</span>
                                </div>
                            </div>
                            <div class="provider-stats">
                                <div class="stat">
                                    <strong><?php echo $delivery_stats['delivered'] ?? 0; ?></strong>
                                    <span>Delivered</span>
                                </div>
                                <div class="stat">
                                    <strong>₹38-145</strong>
                                    <span>Delivery Charges</span>
                                </div>
                            </div>
                            <div class="provider-action">
                                <button class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i>
                                    Manage Orders
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Orders & Delivery Status</h3>
                    <div class="card-actions">
                        <button class="btn btn-primary" onclick="showAssignModal()">
                            <i class="fas fa-plus"></i>
                            Assign Delivery
                        </button>
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
                                    <th>Order Status</th>
                                    <th>Delivery Partner</th>
                                    <th>Delivery Status</th>
                                    <th>Tracking</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?php echo htmlspecialchars($order['order_number'] ?? 'ORD-' . substr($order['order_id'], 0, 8)); ?></strong>
                                            <br>
                                            <small><?php echo date('M j, Y', strtotime($order['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($order['customer_name']); ?>
                                            <br>
                                            <small><?php echo htmlspecialchars($order['email']); ?></small>
                                        </td>
                                        <td>₹<?php echo number_format($order['total_amount'], 0); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $order['order_status']; ?>">
                                                <?php echo ucfirst($order['order_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($order['partner_name']): ?>
                                                <strong><?php echo htmlspecialchars($order['partner_name']); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($order['delivery_status']): ?>
                                                <span class="status-badge <?php echo $order['delivery_status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $order['delivery_status'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($order['tracking_number']): ?>
                                                <code><?php echo htmlspecialchars($order['tracking_number']); ?></code>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if (!$order['assignment_id']): ?>
                                                    <button class="btn btn-sm btn-primary" onclick="assignDelivery('<?php echo $order['order_id']; ?>')">
                                                        <i class="fas fa-truck"></i>
                                                        Assign
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" onclick="updateDelivery('<?php echo $order['assignment_id']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                        Update
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline" onclick="viewOrder('<?php echo $order['order_id']; ?>')">
                                                    <i class="fas fa-eye"></i>
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
        </main>
    </div>

    <!-- Delivery Assignment Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Assign Delivery Partner</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="assign_delivery" value="1">
                <input type="hidden" name="order_id" id="modal_order_id">

                <div class="form-group">
                    <label for="partner_id">Delivery Partner</label>
                    <select name="partner_id" id="partner_id" required>
                        <option value="">Select Delivery Partner</option>
                        <?php foreach ($delivery_partners as $partner): ?>
                            <option value="<?php echo $partner['partner_id']; ?>"
                                    data-charges='<?php echo htmlspecialchars($partner['delivery_charges']); ?>'>
                                <?php echo htmlspecialchars($partner['partner_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="pickup_address">Pickup Address</label>
                    <textarea name="pickup_address" id="pickup_address" required>Alpha Nutrition Warehouse, Mumbai, Maharashtra, India</textarea>
                </div>

                <div class="form-group">
                    <label for="delivery_address">Delivery Address</label>
                    <textarea name="delivery_address" id="delivery_address" required></textarea>
                </div>

                <div class="form-group">
                    <label for="estimated_delivery">Estimated Delivery Date</label>
                    <input type="datetime-local" name="estimated_delivery" id="estimated_delivery" required>
                </div>

                <div class="form-group">
                    <label for="delivery_charges">Delivery Charges (₹)</label>
                    <input type="number" name="delivery_charges" id="delivery_charges" step="0.01" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Delivery</button>
                </div>
            </form>
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

    <!-- Tracking Update Modal -->
    <div id="trackingModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Tracking Information</h3>
                <span class="close" onclick="closeTrackingModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="trackingForm">
                    <input type="hidden" id="tracking_assignment_id">

                    <div class="form-group">
                        <label for="tracking_number">Tracking Number:</label>
                        <input type="text" id="tracking_number" name="tracking_number" required
                               placeholder="Enter tracking number">
                    </div>

                    <div class="form-group">
                        <label for="delivery_status">Delivery Status:</label>
                        <select id="delivery_status" name="delivery_status" required>
                            <option value="">Select Status</option>
                            <option value="assigned">Assigned</option>
                            <option value="picked_up">Picked Up</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                            <option value="failed">Failed</option>
                            <option value="returned">Returned</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tracking_notes">Notes (Optional):</label>
                        <textarea id="tracking_notes" name="notes" rows="3"
                                  placeholder="Add any delivery notes..."></textarea>
                    </div>

                    <div class="settings-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeTrackingModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Tracking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function refreshPage() {
        location.reload();
    }

    function redirectToProvider(provider) {
        window.location.href = `delivery-${provider}.php`;
    }

    function showAssignModal() {
        document.getElementById('assignModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('assignModal').style.display = 'none';
    }

    function assignDelivery(orderId) {
        document.getElementById('modal_order_id').value = orderId;

        // Get order details and populate delivery address
        fetch(`orders.php?api=get_order&order_id=${orderId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const address = `${data.order.address}, ${data.order.city}, ${data.order.state} - ${data.order.pincode}`;
                    document.getElementById('delivery_address').value = address;
                }
            })
            .catch(error => {
                console.error('Error fetching order details:', error);
            });

        // Set default estimated delivery (2 days from now)
        const estimatedDate = new Date();
        estimatedDate.setDate(estimatedDate.getDate() + 2);
        document.getElementById('estimated_delivery').value = estimatedDate.toISOString().slice(0, 16);

        showAssignModal();
    }

    function updateDelivery(assignmentId) {
        // Open tracking update modal
        document.getElementById('tracking_assignment_id').value = assignmentId;
        document.getElementById('trackingModal').style.display = 'block';

        // Load current tracking info
        loadCurrentTracking(assignmentId);
    }

    function loadCurrentTracking(assignmentId) {
        // You can add an API call here to load current tracking info
        // For now, we'll just clear the form
        document.getElementById('tracking_number').value = '';
        document.getElementById('delivery_status').value = '';
        document.getElementById('tracking_notes').value = '';
    }

    function closeTrackingModal() {
        document.getElementById('trackingModal').style.display = 'none';
    }

    // Handle tracking form submission
    document.getElementById('trackingForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('assignment_id', document.getElementById('tracking_assignment_id').value);
        formData.append('tracking_number', document.getElementById('tracking_number').value);
        formData.append('delivery_status', document.getElementById('delivery_status').value);
        formData.append('notes', document.getElementById('tracking_notes').value);

        showLoader();

        fetch('delivery.php?api=update_tracking', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoader();
            if (data.success) {
                showNotification('Tracking updated successfully', 'success');
                closeTrackingModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Error updating tracking: ' + data.message, 'error');
            }
        })
        .catch(error => {
            hideLoader();
            console.error('Error:', error);
            showNotification('Error updating tracking', 'error');
        });
    });

    function refreshDeliveryStats() {
        fetch('delivery.php?api=get_delivery_stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStatsDisplay(data.stats);
                }
            })
            .catch(error => {
                console.error('Error refreshing stats:', error);
            });
    }

    function updateStatsDisplay(stats) {
        // Update the stats cards if they exist
        const totalElement = document.querySelector('.stat-card:nth-child(1) .stat-number');
        const deliveredElement = document.querySelector('.stat-card:nth-child(2) .stat-number');
        const transitElement = document.querySelector('.stat-card:nth-child(3) .stat-number');
        const assignedElement = document.querySelector('.stat-card:nth-child(4) .stat-number');

        if (totalElement) totalElement.textContent = stats.total_deliveries;
        if (deliveredElement) deliveredElement.textContent = stats.delivered;
        if (transitElement) transitElement.textContent = stats.in_transit;
        if (assignedElement) assignedElement.textContent = stats.assigned;
    }

    function showLoader() {
        if (!document.querySelector('.loader-overlay')) {
            const loader = document.createElement('div');
            loader.className = 'loader-overlay';
            loader.innerHTML = '<div class="loader"><i class="fas fa-spinner fa-spin"></i> Processing...</div>';
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

    function viewOrder(orderId) {
        console.log('viewOrder called with orderId:', orderId);
        // Open order details modal
        const modal = document.getElementById('orderDetailsModal');
        if (modal) {
            modal.style.display = 'block';
            loadOrderDetails(orderId);
        } else {
            console.error('Modal element not found');
        }
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

    // Update delivery charges when partner is selected
    document.getElementById('partner_id').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const charges = JSON.parse(selectedOption.dataset.charges || '{}');
            // Set standard delivery charge as default
            document.getElementById('delivery_charges').value = charges.surface || charges.standard || 50;
        }
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
        const assignModal = document.getElementById('assignModal');
        const orderModal = document.getElementById('orderDetailsModal');

        if (event.target === assignModal) {
            closeModal();
        } else if (event.target === orderModal) {
            closeOrderModal();
        }
    }
    </script>
</body>
</html>
