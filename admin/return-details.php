<?php
// Start session and include database connection
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$return_id = $_GET['id'] ?? '';

if (empty($return_id)) {
    header('Location: returns.php');
    exit();
}

// Get return details
$query = "
    SELECT 
        rr.*,
        co.order_number,
        co.first_name,
        co.last_name,
        co.phone,
        co.address,
        co.city,
        co.state,
        co.pincode,
        rr_reason.reason_name,
        rr_reason.description as reason_description
    FROM return_requests rr
    LEFT JOIN checkout_orders co ON rr.order_id = co.order_id
    LEFT JOIN return_reasons rr_reason ON rr.return_reason_id = rr_reason.reason_id
    WHERE rr.return_id = ?
";

$stmt = $pdo->prepare($query);
$stmt->execute([$return_id]);
$return = $stmt->fetch();

if (!$return) {
    header('Location: returns.php');
    exit();
}

// Get return items
$items_query = "
    SELECT 
        ri.*,
        p.name as product_name,
        p.image_url
    FROM return_items ri
    LEFT JOIN products p ON ri.product_id = p.product_id
    WHERE ri.return_id = ?
";

$stmt = $pdo->prepare($items_query);
$stmt->execute([$return_id]);
$return_items = $stmt->fetchAll();

// Get status history
$history_query = "
    SELECT 
        rsh.*,
        au.name as changed_by_name
    FROM return_status_history rsh
    LEFT JOIN admin_users au ON rsh.changed_by = au.admin_id
    WHERE rsh.return_id = ?
    ORDER BY rsh.created_at DESC
";

$stmt = $pdo->prepare($history_query);
$stmt->execute([$return_id]);
$status_history = $stmt->fetchAll();

// Get refund transactions
$refund_query = "
    SELECT *
    FROM refund_transactions
    WHERE return_id = ?
    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($refund_query);
$stmt->execute([$return_id]);
$refund_transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Details - <?php echo htmlspecialchars($return['return_number']); ?> - Alpha Nutrition Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
    <style>
        .return-details-container {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .return-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }

        .return-title {
            font-size: 2rem;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .return-title i {
            color: #ff6b35;
        }

        .back-btn {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .details-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .card-header h3 {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .info-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .info-value {
            font-size: 1rem;
            color: #333;
            font-weight: 600;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
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

        .items-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .item-card {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            background: #e9ecef;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .item-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .timeline {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .timeline-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-left: 3px solid #e0e0e0;
            position: relative;
        }

        .timeline-item.current {
            border-left-color: #ff6b35;
            background: #fff5f2;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .timeline-item.current .timeline-icon {
            background: #ff6b35;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
        }

        .timeline-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .timeline-date {
            font-size: 0.8rem;
            color: #999;
        }

        @media (max-width: 768px) {
            .return-details-container {
                padding: 1rem;
            }

            .return-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .item-card {
                flex-direction: column;
            }

            .item-image {
                width: 100%;
                height: 200px;
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
            <div class="return-details-container">
                <!-- Header -->
                <div class="return-header">
                    <h1 class="return-title">
                        <i class="fas fa-undo"></i>
                        Return Details - <?php echo htmlspecialchars($return['return_number']); ?>
                    </h1>
                    <a href="returns.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Returns
                    </a>
                </div>

                <!-- Main Details Grid -->
                <div class="details-grid">
                    <!-- Left Column - Return Information -->
                    <div class="details-card">
                        <div class="card-header">
                            <h3><i class="fas fa-info-circle"></i> Return Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Return Number</span>
                                    <span class="info-value"><?php echo htmlspecialchars($return['return_number']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Order Number</span>
                                    <span class="info-value"><?php echo htmlspecialchars($return['order_number'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Status</span>
                                    <span class="status-badge status-<?php echo $return['return_status']; ?>">
                                        <?php echo ucfirst($return['return_status']); ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Return Amount</span>
                                    <span class="info-value">₹<?php echo number_format($return['total_return_amount'], 2); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Refund Amount</span>
                                    <span class="info-value">₹<?php echo number_format($return['refund_amount'], 2); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Created Date</span>
                                    <span class="info-value"><?php echo date('M j, Y g:i A', strtotime($return['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Customer Information -->
                    <div class="details-card">
                        <div class="card-header">
                            <h3><i class="fas fa-user"></i> Customer Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Customer Name</span>
                                    <span class="info-value"><?php echo htmlspecialchars($return['first_name'] . ' ' . $return['last_name']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?php echo htmlspecialchars($return['customer_email']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Phone</span>
                                    <span class="info-value"><?php echo htmlspecialchars($return['customer_phone'] ?? $return['phone'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Address</span>
                                    <span class="info-value">
                                        <?php
                                        $address_parts = array_filter([
                                            $return['address'],
                                            $return['city'],
                                            $return['state'],
                                            $return['pincode']
                                        ]);
                                        echo htmlspecialchars(implode(', ', $address_parts));
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Return Reason -->
                <div class="details-card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3><i class="fas fa-comment"></i> Return Reason</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Reason</span>
                                <span class="info-value"><?php echo htmlspecialchars($return['reason_name'] ?? 'Other'); ?></span>
                            </div>
                            <?php if (!empty($return['custom_reason'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Custom Reason</span>
                                    <span class="info-value"><?php echo htmlspecialchars($return['custom_reason']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($return['customer_notes'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Customer Notes</span>
                                    <span class="info-value"><?php echo htmlspecialchars($return['customer_notes']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($return['admin_notes'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Admin Notes</span>
                                    <span class="info-value"><?php echo htmlspecialchars($return['admin_notes']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Return Items -->
                <div class="details-card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3><i class="fas fa-box"></i> Return Items</h3>
                    </div>
                    <div class="card-body">
                        <div class="items-list">
                            <?php foreach ($return_items as $item): ?>
                                <div class="item-card">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? '../assets/placeholder.jpg'); ?>"
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="item-image">
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="item-meta">
                                            <span>Qty: <?php echo $item['quantity_returned']; ?></span>
                                            <span>Unit Price: ₹<?php echo number_format($item['unit_price'], 2); ?></span>
                                            <span>Total: ₹<?php echo number_format($item['total_amount'], 2); ?></span>
                                            <?php if ($item['condition_received']): ?>
                                                <span>Condition: <?php echo ucfirst($item['condition_received']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($item['inspection_notes'])): ?>
                                            <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                                                <strong>Inspection Notes:</strong> <?php echo htmlspecialchars($item['inspection_notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Status History -->
                <div class="details-grid">
                    <div class="details-card">
                        <div class="card-header">
                            <h3><i class="fas fa-history"></i> Status History</h3>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php foreach ($status_history as $index => $history): ?>
                                    <div class="timeline-item <?php echo $index === 0 ? 'current' : ''; ?>">
                                        <div class="timeline-icon">
                                            <?php
                                            $icons = [
                                                'pending' => 'fas fa-clock',
                                                'approved' => 'fas fa-check',
                                                'rejected' => 'fas fa-times',
                                                'refunded' => 'fas fa-money-bill',
                                                'cancelled' => 'fas fa-ban'
                                            ];
                                            $icon = $icons[$history['new_status']] ?? 'fas fa-info';
                                            ?>
                                            <i class="<?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="timeline-title">
                                                Status changed to: <?php echo ucfirst($history['new_status']); ?>
                                            </div>
                                            <?php if (!empty($history['change_reason'])): ?>
                                                <div class="timeline-description">
                                                    <?php echo htmlspecialchars($history['change_reason']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="timeline-date">
                                                <?php echo date('M j, Y g:i A', strtotime($history['created_at'])); ?>
                                                <?php if ($history['changed_by_name']): ?>
                                                    by <?php echo htmlspecialchars($history['changed_by_name']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Refund Transactions -->
                    <?php if (!empty($refund_transactions)): ?>
                        <div class="details-card">
                            <div class="card-header">
                                <h3><i class="fas fa-money-bill"></i> Refund Transactions</h3>
                            </div>
                            <div class="card-body">
                                <?php foreach ($refund_transactions as $refund): ?>
                                    <div class="item-card" style="margin-bottom: 1rem;">
                                        <div class="item-details">
                                            <div class="item-name">Refund ID: <?php echo htmlspecialchars($refund['refund_id']); ?></div>
                                            <div class="item-meta">
                                                <span>Amount: ₹<?php echo number_format($refund['refund_amount'], 2); ?></span>
                                                <span>Method: <?php echo ucfirst(str_replace('_', ' ', $refund['refund_method'])); ?></span>
                                                <span>Status: <?php echo ucfirst($refund['refund_status']); ?></span>
                                            </div>
                                            <div style="margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                                                Processed: <?php echo date('M j, Y g:i A', strtotime($refund['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
