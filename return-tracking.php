<?php
include 'includes/header.php';
include 'includes/db_connection.php';

$return_number = $_GET['return_number'] ?? '';
$return_request = null;
$return_items = [];
$status_history = [];

if ($return_number) {
    // Get return request details
    $stmt = $pdo->prepare("
        SELECT rr.*, rrs.reason_name, co.order_number, co.order_id
        FROM return_requests rr
        LEFT JOIN return_reasons rrs ON rr.return_reason_id = rrs.reason_id
        LEFT JOIN checkout_orders co ON rr.order_id = co.order_id
        WHERE rr.return_number = ?
    ");
    $stmt->execute([$return_number]);
    $return_request = $stmt->fetch();
    
    if ($return_request) {
        // Get return items
        $stmt = $pdo->prepare("
            SELECT ri.*, p.name as product_name
            FROM return_items ri
            LEFT JOIN products p ON ri.product_id = p.product_id
            WHERE ri.return_id = ?
        ");
        $stmt->execute([$return_request['return_id']]);
        $return_items = $stmt->fetchAll();
        
        // Get status history
        $stmt = $pdo->prepare("
            SELECT * FROM return_status_history 
            WHERE return_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$return_request['return_id']]);
        $status_history = $stmt->fetchAll();
    }
}

// Define status steps for progress tracking
$status_steps = [
    'pending' => ['label' => 'Pending Review', 'icon' => 'clock', 'description' => 'Your return request is being reviewed'],
    'approved' => ['label' => 'Approved', 'icon' => 'check', 'description' => 'Return request approved, pickup scheduled'],
    'items_received' => ['label' => 'Items Received', 'icon' => 'box', 'description' => 'We have received your returned items'],
    'inspecting' => ['label' => 'Inspecting', 'icon' => 'search', 'description' => 'Items are being inspected'],
    'processed' => ['label' => 'Processed', 'icon' => 'cog', 'description' => 'Return processed, refund initiated'],
    'refunded' => ['label' => 'Refunded', 'icon' => 'money-bill', 'description' => 'Refund completed'],
    'rejected' => ['label' => 'Rejected', 'icon' => 'times', 'description' => 'Return request rejected'],
    'cancelled' => ['label' => 'Cancelled', 'icon' => 'ban', 'description' => 'Return request cancelled']
];

function getStatusColor($status) {
    switch ($status) {
        case 'pending': return '#ffc107';
        case 'approved': return '#17a2b8';
        case 'items_received': return '#6f42c1';
        case 'inspecting': return '#fd7e14';
        case 'processed': return '#20c997';
        case 'refunded': return '#28a745';
        case 'rejected': return '#dc3545';
        case 'cancelled': return '#6c757d';
        default: return '#6c757d';
    }
}
?>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
.tracking-container {
    max-width: 900px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.tracking-header {
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
    padding: 2rem;
    border-radius: 12px 12px 0 0;
    text-align: center;
}

.tracking-content {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 0 0 12px 12px;
    padding: 2rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.search-section {
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    text-align: center;
}

.search-form {
    display: flex;
    gap: 1rem;
    max-width: 400px;
    margin: 1rem auto 0;
}

.search-input {
    flex: 1;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.search-btn {
    background: #ff6b35;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
}

.return-summary {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.summary-item {
    text-align: center;
    padding: 1rem;
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

.summary-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.summary-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
}

.progress-section {
    margin-bottom: 2rem;
}

.progress-timeline {
    position: relative;
    padding: 2rem 0;
}

.timeline-item {
    display: flex;
    align-items: center;
    margin-bottom: 2rem;
    position: relative;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    margin-right: 1.5rem;
    position: relative;
    z-index: 2;
}

.timeline-content {
    flex: 1;
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.timeline-title {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.timeline-description {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.timeline-date {
    font-size: 0.8rem;
    color: #adb5bd;
}

.timeline-line {
    position: absolute;
    left: 24px;
    top: 50px;
    bottom: -50px;
    width: 2px;
    background: #e9ecef;
    z-index: 1;
}

.timeline-item:last-child .timeline-line {
    display: none;
}

.timeline-item.completed .timeline-icon {
    background: #28a745;
}

.timeline-item.current .timeline-icon {
    background: #ff6b35;
    animation: pulse 2s infinite;
}

.timeline-item.pending .timeline-icon {
    background: #e9ecef;
    color: #adb5bd;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(255, 107, 53, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 107, 53, 0); }
}

.items-section {
    margin-bottom: 2rem;
}

.items-grid {
    display: grid;
    gap: 1rem;
}

.item-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 500;
    color: #212529;
    margin-bottom: 0.25rem;
}

.item-details {
    font-size: 0.9rem;
    color: #6c757d;
}

.item-amount {
    font-weight: 600;
    color: #ff6b35;
}

.status-history {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
}

.history-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 6px;
    margin-bottom: 1rem;
    border: 1px solid #e9ecef;
}

.history-item:last-child {
    margin-bottom: 0;
}

.history-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: white;
}

.history-content {
    flex: 1;
}

.history-status {
    font-weight: 500;
    color: #212529;
    margin-bottom: 0.25rem;
}

.history-date {
    font-size: 0.9rem;
    color: #6c757d;
}

@media (max-width: 768px) {
    .tracking-container {
        margin: 1rem auto;
        padding: 0 0.5rem;
    }
    
    .tracking-content {
        padding: 1rem;
    }
    
    .search-form {
        flex-direction: column;
    }
    
    .summary-grid {
        grid-template-columns: 1fr;
    }
    
    .timeline-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .timeline-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .timeline-line {
        display: none;
    }
    
    .item-card {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="tracking-container">
    <div class="tracking-header">
        <h1><i class="fas fa-search"></i> Track Return Status</h1>
        <p>Monitor your return request progress</p>
    </div>
    
    <div class="tracking-content">
        <?php if (!$return_number || !$return_request): ?>
            <div class="search-section">
                <h2>Enter Return Number</h2>
                <p>Enter your return number to track the status of your return request</p>
                <form method="GET" action="" class="search-form">
                    <input type="text" name="return_number" class="search-input" 
                           placeholder="Enter return number (e.g., RET20241201ABC123)" 
                           value="<?php echo htmlspecialchars($return_number); ?>" required>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Track
                    </button>
                </form>
                
                <?php if ($return_number && !$return_request): ?>
                    <div style="color: #dc3545; margin-top: 1rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Return request not found. Please check your return number and try again.
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Return Summary -->
            <div class="return-summary">
                <h3>Return Summary</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Return Number</div>
                        <div class="summary-value"><?php echo htmlspecialchars($return_request['return_number']); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Order Number</div>
                        <div class="summary-value"><?php echo htmlspecialchars($return_request['order_number']); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Return Amount</div>
                        <div class="summary-value">₹<?php echo number_format($return_request['total_return_amount'], 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Expected Refund</div>
                        <div class="summary-value">₹<?php echo number_format($return_request['refund_amount'], 2); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Current Status</div>
                        <div class="summary-value" style="color: <?php echo getStatusColor($return_request['return_status']); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $return_request['return_status'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Progress Timeline -->
            <div class="progress-section">
                <h3>Return Progress</h3>
                <div class="progress-timeline">
                    <?php
                    $current_status = $return_request['return_status'];
                    $status_order = ['pending', 'approved', 'items_received', 'inspecting', 'processed', 'refunded'];
                    $current_index = array_search($current_status, $status_order);
                    
                    foreach ($status_order as $index => $status):
                        if ($status === 'rejected' || $status === 'cancelled') continue;
                        
                        $is_completed = $index < $current_index || ($current_status === 'refunded' && $status === 'refunded');
                        $is_current = $status === $current_status;
                        $is_pending = $index > $current_index && !$is_current;
                        
                        $class = $is_completed ? 'completed' : ($is_current ? 'current' : 'pending');
                    ?>
                        <div class="timeline-item <?php echo $class; ?>">
                            <div class="timeline-icon">
                                <i class="fas fa-<?php echo $status_steps[$status]['icon']; ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-title"><?php echo $status_steps[$status]['label']; ?></div>
                                <div class="timeline-description"><?php echo $status_steps[$status]['description']; ?></div>
                                <?php if ($is_completed || $is_current): ?>
                                    <div class="timeline-date">
                                        <?php
                                        // Find the date for this status from history
                                        foreach ($status_history as $history) {
                                            if ($history['new_status'] === $status) {
                                                echo date('M j, Y \a\t g:i A', strtotime($history['created_at']));
                                                break;
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-line"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Return Items -->
            <div class="items-section">
                <h3>Returned Items</h3>
                <div class="items-grid">
                    <?php foreach ($return_items as $item): ?>
                        <div class="item-card">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="item-details">
                                    <?php if ($item['variant_name']): ?>
                                        Variant: <?php echo htmlspecialchars($item['variant_name']); ?><br>
                                    <?php endif; ?>
                                    Quantity: <?php echo $item['quantity_returned']; ?> | 
                                    Unit Price: ₹<?php echo number_format($item['unit_price'], 2); ?>
                                </div>
                            </div>
                            <div class="item-amount">₹<?php echo number_format($item['total_amount'], 2); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Status History -->
            <div class="status-history">
                <h3>Status History</h3>
                <?php foreach ($status_history as $history): ?>
                    <div class="history-item">
                        <div class="history-icon" style="background: <?php echo getStatusColor($history['new_status']); ?>">
                            <i class="fas fa-<?php echo $status_steps[$history['new_status']]['icon'] ?? 'info'; ?>"></i>
                        </div>
                        <div class="history-content">
                            <div class="history-status">
                                <?php echo ucfirst(str_replace('_', ' ', $history['new_status'])); ?>
                            </div>
                            <div class="history-date">
                                <?php echo date('M j, Y \a\t g:i A', strtotime($history['created_at'])); ?>
                            </div>
                            <?php if ($history['notes']): ?>
                                <div style="font-size: 0.9rem; color: #6c757d; margin-top: 0.25rem;">
                                    <?php echo htmlspecialchars($history['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 2rem;">
                <a href="return-request.php" class="btn" style="background: #ff6b35; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 6px; display: inline-block;">
                    <i class="fas fa-plus"></i> Submit New Return Request
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
