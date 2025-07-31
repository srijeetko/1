<?php
// Start session and include database connection
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $return_id = $_POST['return_id'] ?? '';
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE return_requests SET return_status = 'approved', processed_by = ?, processed_at = NOW() WHERE return_id = ?");
            $stmt->execute([$_SESSION['admin_id'], $return_id]);
            
            // Add to status history
            $stmt = $pdo->prepare("INSERT INTO return_status_history (history_id, return_id, previous_status, new_status, changed_by, change_reason) VALUES (REPLACE(UUID(), '-', ''), ?, 'pending', 'approved', ?, 'Approved by admin')");
            $stmt->execute([$return_id, $_SESSION['admin_id']]);
            
            $success_message = "Return request approved successfully!";
        } elseif ($action === 'reject') {
            $reason = $_POST['rejection_reason'] ?? 'No reason provided';
            $stmt = $pdo->prepare("UPDATE return_requests SET return_status = 'rejected', processed_by = ?, processed_at = NOW(), admin_notes = ? WHERE return_id = ?");
            $stmt->execute([$_SESSION['admin_id'], $reason, $return_id]);
            
            // Add to status history
            $stmt = $pdo->prepare("INSERT INTO return_status_history (history_id, return_id, previous_status, new_status, changed_by, change_reason) VALUES (REPLACE(UUID(), '-', ''), ?, 'pending', 'rejected', ?, ?)");
            $stmt->execute([$return_id, $_SESSION['admin_id'], $reason]);
            
            $success_message = "Return request rejected successfully!";
        } elseif ($action === 'process_refund') {
            $refund_amount = $_POST['refund_amount'] ?? 0;
            $refund_method = $_POST['refund_method'] ?? 'original_payment';
            
            // Update return status
            $stmt = $pdo->prepare("UPDATE return_requests SET return_status = 'refunded', refund_amount = ?, processed_by = ?, processed_at = NOW() WHERE return_id = ?");
            $stmt->execute([$refund_amount, $_SESSION['admin_id'], $return_id]);
            
            // Create refund transaction
            $refund_id = str_replace('-', '', uuid_create());
            $stmt = $pdo->prepare("INSERT INTO refund_transactions (refund_id, return_id, refund_method, refund_amount, net_refund_amount, refund_status, processed_by, processed_at) VALUES (?, ?, ?, ?, ?, 'completed', ?, NOW())");
            $stmt->execute([$refund_id, $return_id, $refund_method, $refund_amount, $refund_amount]);
            
            // Add to status history
            $stmt = $pdo->prepare("INSERT INTO return_status_history (history_id, return_id, previous_status, new_status, changed_by, change_reason) VALUES (REPLACE(UUID(), '-', ''), ?, 'approved', 'refunded', ?, 'Refund processed')");
            $stmt->execute([$return_id, $_SESSION['admin_id']]);
            
            $success_message = "Refund processed successfully!";
        }
    } catch (Exception $e) {
        $error_message = "Error processing request: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "rr.return_status = ?";
    $params[] = $status_filter;
}

if ($date_filter !== 'all') {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(rr.created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "rr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "rr.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

if (!empty($search)) {
    $where_conditions[] = "(rr.return_number LIKE ? OR rr.customer_email LIKE ? OR co.order_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get returns data
$query = "
    SELECT 
        rr.*,
        co.order_number,
        co.first_name,
        co.last_name,
        rr_reason.reason_name,
        COUNT(ri.return_item_id) as item_count
    FROM return_requests rr
    LEFT JOIN checkout_orders co ON rr.order_id = co.order_id
    LEFT JOIN return_reasons rr_reason ON rr.return_reason_id = rr_reason.reason_id
    LEFT JOIN return_items ri ON rr.return_id = ri.return_id
    $where_clause
    GROUP BY rr.return_id
    ORDER BY rr.created_at DESC
    LIMIT 50
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$returns = $stmt->fetchAll();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_returns,
        SUM(CASE WHEN return_status = 'pending' THEN 1 ELSE 0 END) as pending_returns,
        SUM(CASE WHEN return_status = 'approved' THEN 1 ELSE 0 END) as approved_returns,
        SUM(CASE WHEN return_status = 'refunded' THEN 1 ELSE 0 END) as refunded_returns,
        SUM(CASE WHEN return_status = 'rejected' THEN 1 ELSE 0 END) as rejected_returns,
        SUM(refund_amount) as total_refunded
    FROM return_requests
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
";
$stmt = $pdo->query($stats_query);
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns & Refunds Management - Alpha Nutrition Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
    <style>
        .returns-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .returns-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .returns-title {
            font-size: 2rem;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .returns-title i {
            color: #ff6b35;
        }

        .returns-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        .filters-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-group label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #ff6b35;
        }

        .filter-btn {
            background: #ff6b35;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .filter-btn:hover {
            background: #e55a2b;
        }

        .returns-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .returns-table {
            width: 100%;
            border-collapse: collapse;
        }

        .returns-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        .returns-table td {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: top;
        }

        .returns-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .status-refunded {
            background: #d4edda;
            color: #155724;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-view {
            background: #6c757d;
            color: white;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-refund {
            background: #007bff;
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .returns-container {
                padding: 1rem;
            }

            .returns-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .returns-table-container {
                overflow-x: auto;
            }

            .returns-table {
                min-width: 800px;
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
            <div class="returns-container">
                <!-- Header -->
                <div class="returns-header">
                    <h1 class="returns-title">
                        <i class="fas fa-undo"></i>
                        Returns & Refunds Management
                    </h1>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="returns-stats">
                    <div class="stat-card">
                        <h3><?php echo number_format($stats['total_returns']); ?></h3>
                        <p>Total Returns (30 days)</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo number_format($stats['pending_returns']); ?></h3>
                        <p>Pending Returns</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo number_format($stats['approved_returns']); ?></h3>
                        <p>Approved Returns</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo number_format($stats['refunded_returns']); ?></h3>
                        <p>Refunded Returns</p>
                    </div>
                    <div class="stat-card">
                        <h3>₹<?php echo number_format($stats['total_refunded'], 2); ?></h3>
                        <p>Total Refunded (30 days)</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filters-section">
                    <form method="GET" class="filters-grid">
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="date">Date Range</label>
                            <select name="date" id="date">
                                <option value="all" <?php echo $date_filter === 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="search">Search</label>
                            <input type="text" name="search" id="search" placeholder="Return number, email, order..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="filter-group">
                            <button type="submit" class="filter-btn">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Returns Table -->
                <div class="returns-table-container">
                    <table class="returns-table">
                        <thead>
                            <tr>
                                <th>Return #</th>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Reason</th>
                                <th>Items</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($returns)): ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 2rem; color: #666;">
                                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                                        No returns found matching your criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($returns as $return): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($return['return_number']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($return['order_number'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($return['first_name'] . ' ' . $return['last_name']); ?></strong>
                                                <br>
                                                <small style="color: #666;"><?php echo htmlspecialchars($return['customer_email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($return['reason_name'] ?? 'Other'); ?>
                                            <?php if (!empty($return['custom_reason'])): ?>
                                                <br><small style="color: #666;"><?php echo htmlspecialchars($return['custom_reason']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge"><?php echo $return['item_count']; ?> item(s)</span>
                                        </td>
                                        <td>
                                            <strong>₹<?php echo number_format($return['total_return_amount'], 2); ?></strong>
                                            <?php if ($return['refund_amount'] > 0): ?>
                                                <br><small style="color: #28a745;">Refunded: ₹<?php echo number_format($return['refund_amount'], 2); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $return['return_status']; ?>">
                                                <?php echo ucfirst($return['return_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($return['created_at'])); ?>
                                            <br>
                                            <small style="color: #666;"><?php echo date('g:i A', strtotime($return['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="return-details.php?id=<?php echo $return['return_id']; ?>" class="action-btn btn-view">
                                                    <i class="fas fa-eye"></i> View
                                                </a>

                                                <?php if ($return['return_status'] === 'pending'): ?>
                                                    <button onclick="approveReturn('<?php echo $return['return_id']; ?>')" class="action-btn btn-approve">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                    <button onclick="rejectReturn('<?php echo $return['return_id']; ?>')" class="action-btn btn-reject">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                <?php elseif ($return['return_status'] === 'approved'): ?>
                                                    <button onclick="processRefund('<?php echo $return['return_id']; ?>', '<?php echo $return['total_return_amount']; ?>')" class="action-btn btn-refund">
                                                        <i class="fas fa-money-bill"></i> Process Refund
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <!-- Approve Return Modal -->
    <div id="approveModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-check-circle"></i> Approve Return Request</h3>
                <span class="close" onclick="closeModal('approveModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve this return request?</p>
                <form id="approveForm" method="POST">
                    <input type="hidden" name="return_id" id="approve_return_id">
                    <input type="hidden" name="action" value="approve">
                    <div class="modal-actions">
                        <button type="button" onclick="closeModal('approveModal')" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary">Approve Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Return Modal -->
    <div id="rejectModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle"></i> Reject Return Request</h3>
                <span class="close" onclick="closeModal('rejectModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="rejectForm" method="POST">
                    <input type="hidden" name="return_id" id="reject_return_id">
                    <input type="hidden" name="action" value="reject">
                    <div class="form-group">
                        <label for="rejection_reason">Rejection Reason:</label>
                        <textarea name="rejection_reason" id="rejection_reason" rows="4" placeholder="Please provide a reason for rejection..." required></textarea>
                    </div>
                    <div class="modal-actions">
                        <button type="button" onclick="closeModal('rejectModal')" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-danger">Reject Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Process Refund Modal -->
    <div id="refundModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-money-bill"></i> Process Refund</h3>
                <span class="close" onclick="closeModal('refundModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="refundForm" method="POST">
                    <input type="hidden" name="return_id" id="refund_return_id">
                    <input type="hidden" name="action" value="process_refund">
                    <div class="form-group">
                        <label for="refund_amount">Refund Amount (₹):</label>
                        <input type="number" name="refund_amount" id="refund_amount" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="refund_method">Refund Method:</label>
                        <select name="refund_method" id="refund_method" required>
                            <option value="original_payment">Original Payment Method</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="store_credit">Store Credit</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" onclick="closeModal('refundModal')" class="btn-secondary">Cancel</button>
                        <button type="submit" class="btn-primary">Process Refund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #ff6b35;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #ff6b35;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn-primary,
        .btn-secondary,
        .btn-danger {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #ff6b35;
            color: white;
        }

        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .badge {
            background: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>

    <script>
        // Modal Functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Return Management Functions
        function approveReturn(returnId) {
            document.getElementById('approve_return_id').value = returnId;
            openModal('approveModal');
        }

        function rejectReturn(returnId) {
            document.getElementById('reject_return_id').value = returnId;
            document.getElementById('rejection_reason').value = '';
            openModal('rejectModal');
        }

        function processRefund(returnId, amount) {
            document.getElementById('refund_return_id').value = returnId;
            document.getElementById('refund_amount').value = amount;
            openModal('refundModal');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        }

        // Auto-refresh functionality
        function autoRefresh() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('no_refresh')) {
                setTimeout(() => {
                    window.location.reload();
                }, 300000); // Refresh every 5 minutes
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            autoRefresh();

            // Add loading states to action buttons
            document.querySelectorAll('.action-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.tagName === 'BUTTON') {
                        this.style.opacity = '0.7';
                        this.style.pointerEvents = 'none';

                        setTimeout(() => {
                            this.style.opacity = '1';
                            this.style.pointerEvents = 'auto';
                        }, 2000);
                    }
                });
            });
        });
    </script>
</body>
</html>
