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

    if ($_GET['api'] === 'process_refund' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $transaction_id = $_POST['transaction_id'];
            $refund_amount = floatval($_POST['refund_amount']);
            $refund_reason = $_POST['refund_reason'] ?? '';

            // Get transaction details
            $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE transaction_id = ?");
            $stmt->execute([$transaction_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new Exception('Transaction not found');
            }

            if ($transaction['transaction_status'] !== 'success') {
                throw new Exception('Can only refund successful transactions');
            }

            if ($refund_amount > $transaction['amount']) {
                throw new Exception('Refund amount cannot exceed transaction amount');
            }

            // Process refund (simulate gateway API call)
            $refund_id = 'REF_' . bin2hex(random_bytes(8));

            // Update transaction with refund info
            $stmt = $pdo->prepare("
                UPDATE payment_transactions
                SET transaction_status = 'refunded',
                    refund_amount = ?,
                    refund_date = NOW(),
                    failure_reason = ?,
                    updated_at = NOW()
                WHERE transaction_id = ?
            ");
            $stmt->execute([$refund_amount, $refund_reason, $transaction_id]);

            // Update order status if full refund
            if ($refund_amount >= $transaction['amount']) {
                $stmt = $pdo->prepare("UPDATE checkout_orders SET payment_status = 'refunded' WHERE order_id = ?");
                $stmt->execute([$transaction['order_id']]);
            }

            // Log activity
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (log_id, admin_id, action_type, action_description, affected_table, affected_record_id)
                VALUES (?, ?, 'refund_processed', ?, 'payment_transactions', ?)
            ");
            $stmt->execute([
                bin2hex(random_bytes(16)), $_SESSION['oms_admin_id'],
                "Refund processed: ₹$refund_amount for transaction $transaction_id. Reason: $refund_reason", $transaction_id
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Refund processed successfully',
                'refund_id' => $refund_id
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['api'] === 'retry_payment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $transaction_id = $_POST['transaction_id'];

            // Get transaction details
            $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE transaction_id = ?");
            $stmt->execute([$transaction_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new Exception('Transaction not found');
            }

            if ($transaction['transaction_status'] !== 'failed') {
                throw new Exception('Can only retry failed transactions');
            }

            // Simulate payment retry (in real implementation, call payment gateway API)
            $success = rand(1, 10) > 3; // 70% success rate for simulation

            if ($success) {
                $stmt = $pdo->prepare("
                    UPDATE payment_transactions
                    SET transaction_status = 'success',
                        processed_at = NOW(),
                        gateway_transaction_id = ?,
                        updated_at = NOW()
                    WHERE transaction_id = ?
                ");
                $stmt->execute(['TXN_' . bin2hex(random_bytes(8)), $transaction_id]);

                // Update order payment status
                $stmt = $pdo->prepare("UPDATE checkout_orders SET payment_status = 'paid' WHERE order_id = ?");
                $stmt->execute([$transaction['order_id']]);

                echo json_encode(['success' => true, 'message' => 'Payment retry successful']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Payment retry failed. Please try again.']);
            }
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($_GET['api'] === 'get_transaction_stats') {
        try {
            $stmt = $pdo->query("
                SELECT
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN transaction_status = 'success' THEN amount ELSE 0 END) as successful_amount,
                    SUM(CASE WHEN transaction_status = 'success' THEN 1 ELSE 0 END) as successful_count,
                    SUM(CASE WHEN transaction_status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                    SUM(CASE WHEN transaction_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN transaction_status = 'refunded' THEN amount ELSE 0 END) as refunded_amount
                FROM payment_transactions
                WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
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

// Handle transaction status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_transaction'])) {
    try {
        $transaction_id = $_POST['transaction_id'];
        $new_status = $_POST['transaction_status'];
        $notes = $_POST['notes'] ?? '';
        
        // Update transaction status
        $stmt = $pdo->prepare("UPDATE payment_transactions SET transaction_status = ?, updated_at = NOW() WHERE transaction_id = ?");
        $stmt->execute([$new_status, $transaction_id]);
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (log_id, admin_id, action_type, action_description, affected_table, affected_record_id) 
            VALUES (?, ?, 'transaction_updated', ?, 'payment_transactions', ?)
        ");
        $stmt->execute([
            bin2hex(random_bytes(16)), $_SESSION['oms_admin_id'], 
            "Transaction status updated to $new_status. Notes: $notes", $transaction_id
        ]);
        
        $success_message = "Transaction updated successfully!";
    } catch (Exception $e) {
        $error_message = "Error updating transaction: " . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$gateway_filter = $_GET['gateway'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "pt.transaction_status = ?";
    $params[] = $status_filter;
}

if ($gateway_filter) {
    $where_conditions[] = "pt.payment_gateway = ?";
    $params[] = $gateway_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(pt.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(pt.created_at) <= ?";
    $params[] = $date_to;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get transactions with order details
$stmt = $pdo->prepare("
    SELECT pt.*, 
           co.order_number,
           CONCAT(co.first_name, ' ', co.last_name) as customer_name,
           co.email as customer_email,
           co.phone as customer_phone
    FROM payment_transactions pt
    LEFT JOIN checkout_orders co ON pt.order_id = co.order_id
    $where_clause
    ORDER BY pt.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get transaction statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN transaction_status = 'success' THEN amount ELSE 0 END) as successful_amount,
        SUM(CASE WHEN transaction_status = 'success' THEN 1 ELSE 0 END) as successful_count,
        SUM(CASE WHEN transaction_status = 'failed' THEN 1 ELSE 0 END) as failed_count,
        SUM(CASE WHEN transaction_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN transaction_status = 'refunded' THEN amount ELSE 0 END) as refunded_amount
    FROM payment_transactions
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get gateway distribution
$stmt = $pdo->query("
    SELECT payment_gateway, COUNT(*) as count, SUM(amount) as total_amount
    FROM payment_transactions 
    WHERE transaction_status = 'success'
    GROUP BY payment_gateway
    ORDER BY total_amount DESC
");
$gateway_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management - Alpha Nutrition OMS</title>
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
                <a href="transactions.php" class="nav-item active">
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
                <h1>Transaction Management</h1>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="refreshPage()">
                        <i class="fas fa-sync-alt"></i>
                        Refresh
                    </button>
                    <button class="btn btn-outline" onclick="exportTransactions()">
                        <i class="fas fa-download"></i>
                        Export
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

            <!-- Transaction Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_transactions'] ?? 0); ?></h3>
                        <p>Total Transactions</p>
                        <span class="stat-change neutral">Last 30 days</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($stats['successful_amount'] ?? 0, 0); ?></h3>
                        <p>Successful Amount</p>
                        <span class="stat-change positive"><?php echo $stats['successful_count'] ?? 0; ?> transactions</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['failed_count'] ?? 0); ?></h3>
                        <p>Failed Transactions</p>
                        <span class="stat-change negative">Needs attention</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon warning">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div class="stat-info">
                        <h3>₹<?php echo number_format($stats['refunded_amount'] ?? 0, 0); ?></h3>
                        <p>Refunded Amount</p>
                        <span class="stat-change neutral">Total refunds</span>
                    </div>
                </div>
            </div>

            <!-- Gateway Statistics -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Payment Gateway Performance</h3>
                </div>
                <div class="card-content">
                    <div class="gateway-stats">
                        <?php foreach ($gateway_stats as $gateway): ?>
                            <div class="gateway-card">
                                <div class="gateway-info">
                                    <h4><?php echo htmlspecialchars($gateway['payment_gateway'] ?: 'Unknown'); ?></h4>
                                    <p><?php echo number_format($gateway['count']); ?> transactions</p>
                                </div>
                                <div class="gateway-amount">
                                    <strong>₹<?php echo number_format($gateway['total_amount'], 0); ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Filter Transactions</h3>
                </div>
                <div class="card-content">
                    <form method="GET" class="filter-form">
                        <div class="filter-grid">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="success" <?php echo $status_filter === 'success' ? 'selected' : ''; ?>>Success</option>
                                    <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="gateway">Payment Gateway</label>
                                <select name="gateway" id="gateway">
                                    <option value="">All Gateways</option>
                                    <option value="razorpay" <?php echo $gateway_filter === 'razorpay' ? 'selected' : ''; ?>>Razorpay</option>
                                    <option value="cashfree" <?php echo $gateway_filter === 'cashfree' ? 'selected' : ''; ?>>Cashfree</option>
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
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                                Apply Filters
                            </button>
                            <a href="transactions.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3>Transaction History</h3>
                    <div class="card-actions">
                        <span class="record-count"><?php echo count($transactions); ?> transactions</span>
                    </div>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="oms-table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Gateway</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars(substr($transaction['transaction_id'], 0, 8)); ?></strong>
                                            <br>
                                            <?php if ($transaction['gateway_transaction_id']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($transaction['gateway_transaction_id']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($transaction['order_number']): ?>
                                                <strong>#<?php echo htmlspecialchars($transaction['order_number']); ?></strong>
                                            <?php else: ?>
                                                <span class="text-muted">No Order</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($transaction['customer_name']): ?>
                                                <?php echo htmlspecialchars($transaction['customer_name']); ?>
                                                <br>
                                                <small><?php echo htmlspecialchars($transaction['customer_email']); ?></small>
                                            <?php else: ?>
                                                <span class="text-muted">Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="gateway-badge <?php echo strtolower($transaction['payment_gateway'] ?? 'unknown'); ?>">
                                                <?php echo ucfirst($transaction['payment_gateway'] ?? 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="method-badge <?php echo $transaction['payment_method']; ?>">
                                                <?php echo ucfirst($transaction['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong>₹<?php echo number_format($transaction['amount'], 2); ?></strong>
                                            <br>
                                            <small><?php echo $transaction['currency']; ?></small>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $transaction['transaction_status']; ?>">
                                                <?php echo ucfirst($transaction['transaction_status']); ?>
                                            </span>
                                            <?php if ($transaction['refund_amount'] > 0): ?>
                                                <br>
                                                <small class="text-warning">Refund: ₹<?php echo number_format($transaction['refund_amount'], 2); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($transaction['created_at'])); ?>
                                            <br>
                                            <small><?php echo date('H:i', strtotime($transaction['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-outline" onclick="viewTransaction('<?php echo $transaction['transaction_id']; ?>')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (in_array($transaction['transaction_status'], ['pending', 'processing'])): ?>
                                                    <button class="btn btn-sm btn-primary" onclick="updateTransactionStatus('<?php echo $transaction['transaction_id']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($transaction['transaction_status'] === 'success'): ?>
                                                    <button class="btn btn-sm btn-warning" onclick="initiateRefund('<?php echo $transaction['transaction_id']; ?>')">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($transaction['transaction_status'] === 'failed'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="retryPayment('<?php echo $transaction['transaction_id']; ?>')">
                                                        <i class="fas fa-redo"></i>
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

    <!-- Transaction Update Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Transaction Status</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="update_transaction" value="1">
                <input type="hidden" name="transaction_id" id="modal_transaction_id">

                <div class="form-group">
                    <label for="transaction_status">Transaction Status</label>
                    <select name="transaction_status" id="transaction_status" required>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea name="notes" id="notes" placeholder="Add any notes about this status change..."></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Refund Modal -->
    <div id="refundModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Process Refund</h3>
                <span class="close" onclick="closeRefundModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="refundForm">
                    <input type="hidden" id="refund_transaction_id">

                    <div class="form-group">
                        <label for="refund_amount">Refund Amount (₹):</label>
                        <input type="number" id="refund_amount" name="refund_amount" step="0.01" min="0" required>
                        <small class="form-help">Maximum refundable amount: ₹<span id="max_refund_amount">0</span></small>
                    </div>

                    <div class="form-group">
                        <label for="refund_reason">Refund Reason:</label>
                        <select id="refund_reason" name="refund_reason" required>
                            <option value="">Select Reason</option>
                            <option value="customer_request">Customer Request</option>
                            <option value="order_cancelled">Order Cancelled</option>
                            <option value="product_defective">Product Defective</option>
                            <option value="duplicate_payment">Duplicate Payment</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="refund_notes">Additional Notes:</label>
                        <textarea id="refund_notes" name="refund_notes" rows="3"
                                  placeholder="Add any additional notes about this refund..."></textarea>
                    </div>

                    <div class="settings-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeRefundModal()">Cancel</button>
                        <button type="submit" class="btn btn-warning">Process Refund</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Retry Payment Modal -->
    <div id="retryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Retry Payment</h3>
                <span class="close" onclick="closeRetryModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="retry-info">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <span>This will attempt to process the failed payment again using the same payment details.</span>
                    </div>

                    <div class="transaction-details" id="retry_transaction_details">
                        <!-- Transaction details will be loaded here -->
                    </div>
                </div>

                <div class="settings-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeRetryModal()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmRetryPayment()">
                        <i class="fas fa-redo"></i>
                        Retry Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function refreshPage() {
        location.reload();
    }

    function exportTransactions() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', '1');
        window.location.href = 'transactions.php?' + params.toString();
    }

    function viewTransaction(transactionId) {
        // Open transaction details in a new window or modal
        window.open(`transaction-details.php?id=${transactionId}`, '_blank', 'width=800,height=600');
    }

    function updateTransactionStatus(transactionId) {
        document.getElementById('modal_transaction_id').value = transactionId;
        document.getElementById('updateModal').style.display = 'block';
    }

    function initiateRefund(transactionId) {
        // Open refund modal
        document.getElementById('refund_transaction_id').value = transactionId;

        // Load transaction details to set max refund amount
        loadTransactionForRefund(transactionId);

        document.getElementById('refundModal').style.display = 'block';
    }

    function loadTransactionForRefund(transactionId) {
        // Find transaction in current page data
        const rows = document.querySelectorAll('tbody tr');
        for (let row of rows) {
            const txnIdCell = row.querySelector('td:first-child strong');
            if (txnIdCell && txnIdCell.textContent.includes(transactionId.substring(0, 8))) {
                const amountCell = row.querySelector('td:nth-child(6)');
                if (amountCell) {
                    const amount = amountCell.textContent.replace('₹', '').replace(',', '');
                    document.getElementById('max_refund_amount').textContent = amount;
                    document.getElementById('refund_amount').max = amount;
                    document.getElementById('refund_amount').value = amount;
                }
                break;
            }
        }
    }

    function retryPayment(transactionId) {
        // Open retry payment modal
        document.getElementById('retryModal').style.display = 'block';

        // Load transaction details
        loadTransactionForRetry(transactionId);
    }

    function loadTransactionForRetry(transactionId) {
        document.getElementById('retry_transaction_details').innerHTML = `
            <div class="detail-item">
                <label>Transaction ID:</label>
                <span>${transactionId.substring(0, 8)}...</span>
            </div>
            <div class="detail-item">
                <label>Status:</label>
                <span class="status-badge failed">Failed</span>
            </div>
        `;
    }

    function confirmRetryPayment() {
        const transactionId = document.querySelector('#retry_transaction_details').dataset.transactionId;

        if (confirm('Are you sure you want to retry this payment?')) {
            showLoader();

            const formData = new FormData();
            formData.append('transaction_id', transactionId);

            fetch('transactions.php?api=retry_payment', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showNotification('Payment retry successful', 'success');
                    closeRetryModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Payment retry failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                hideLoader();
                console.error('Error:', error);
                showNotification('Error retrying payment', 'error');
            });
        }
    }

    function closeModal() {
        document.getElementById('updateModal').style.display = 'none';
    }

    function closeRefundModal() {
        document.getElementById('refundModal').style.display = 'none';
    }

    function closeRetryModal() {
        document.getElementById('retryModal').style.display = 'none';
    }

    // Handle refund form submission
    document.getElementById('refundForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData();
        formData.append('transaction_id', document.getElementById('refund_transaction_id').value);
        formData.append('refund_amount', document.getElementById('refund_amount').value);
        formData.append('refund_reason', document.getElementById('refund_reason').value);
        formData.append('refund_notes', document.getElementById('refund_notes').value);

        if (confirm('Are you sure you want to process this refund? This action cannot be undone.')) {
            showLoader();

            fetch('transactions.php?api=process_refund', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showNotification('Refund processed successfully', 'success');
                    closeRefundModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Refund failed: ' + data.message, 'error');
                }
            })
            .catch(error => {
                hideLoader();
                console.error('Error:', error);
                showNotification('Error processing refund', 'error');
            });
        }
    });

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

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('updateModal');
        if (event.target === modal) {
            closeModal();
        }
    }

    // Auto-refresh every 2 minutes for real-time updates
    setInterval(function() {
        if (!document.querySelector('.modal').style.display || document.querySelector('.modal').style.display === 'none') {
            location.reload();
        }
    }, 120000);
    </script>
</body>
</html>
