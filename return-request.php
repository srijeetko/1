<?php
include 'includes/header.php';
include 'includes/db_connection.php';

// Check if order_id is provided
$order_id = $_GET['order_id'] ?? '';
$order = null;
$order_items = [];

if ($order_id) {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT co.*, CONCAT(co.first_name, ' ', co.last_name) as customer_name
        FROM checkout_orders co 
        WHERE co.order_id = ? AND co.payment_status = 'paid'
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if ($order) {
        // Get order items
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name as product_name,
                   CASE
                       WHEN pv.size IS NOT NULL AND pv.color IS NOT NULL THEN CONCAT(pv.size, ' - ', pv.color)
                       WHEN pv.size IS NOT NULL THEN pv.size
                       WHEN pv.color IS NOT NULL THEN pv.color
                       ELSE NULL
                   END as variant_name
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.product_id
            LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    }
}

// Get return reasons
$stmt = $pdo->query("SELECT * FROM return_reasons WHERE is_active = 1 ORDER BY reason_name");
$return_reasons = $stmt->fetchAll();
?>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
.return-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.return-header {
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
    padding: 2rem;
    border-radius: 12px 12px 0 0;
    text-align: center;
}

.return-header h1 {
    margin: 0;
    font-size: 2rem;
    font-weight: 600;
}

.return-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

.return-form {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 0 0 12px 12px;
    padding: 2rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.form-section {
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #f0f0f0;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.section-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title i {
    color: #ff6b35;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    font-weight: 500;
    color: #333;
    margin-bottom: 0.5rem;
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: #ff6b35;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
}

.order-info {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
}

.order-info h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.order-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e0e0e0;
}

.detail-label {
    font-weight: 500;
    color: #666;
}

.detail-value {
    color: #333;
}

.items-list {
    margin-top: 1rem;
}

.item-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 1rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.item-checkbox {
    margin-right: 0.5rem;
}

.item-info {
    flex: 1;
}

.item-name {
    font-weight: 500;
    color: #333;
    margin-bottom: 0.25rem;
}

.item-details {
    font-size: 0.9rem;
    color: #666;
}

.item-price {
    font-weight: 600;
    color: #ff6b35;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.quantity-input {
    width: 60px;
    text-align: center;
}

.submit-btn {
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 6px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 53, 0.3);
}

.submit-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.error-message {
    background: #fee;
    color: #c33;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    border: 1px solid #fcc;
}

.success-message {
    background: #efe;
    color: #363;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    border: 1px solid #cfc;
}

@media (max-width: 768px) {
    .return-container {
        margin: 1rem auto;
        padding: 0 0.5rem;
    }
    
    .return-form {
        padding: 1rem;
    }
    
    .order-details {
        grid-template-columns: 1fr;
    }
    
    .item-card {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<div class="return-container">
    <div class="return-header">
        <h1><i class="fas fa-undo"></i> Return Request</h1>
        <p>Request a return for your order items</p>
    </div>
    
    <div class="return-form">
        <?php if (!$order_id): ?>
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-search"></i>
                    Find Your Order
                </div>
                <form method="GET" action="">
                    <div class="form-group">
                        <label class="form-label">Order ID or Order Number</label>
                        <input type="text" name="order_id" class="form-input" placeholder="Enter your order ID or order number" required>
                    </div>
                    <button type="submit" class="submit-btn">Find Order</button>
                </form>
            </div>
        <?php elseif (!$order): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                Order not found or not eligible for return. Please check your order ID and ensure the order has been paid.
            </div>
            <a href="return-request.php" class="submit-btn" style="display: inline-block; text-decoration: none; text-align: center;">Try Again</a>
        <?php else: ?>
            <form id="returnForm" method="POST" action="process-return-request.php">
                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                
                <!-- Order Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-receipt"></i>
                        Order Information
                    </div>
                    <div class="order-info">
                        <h3>Order #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                        <div class="order-details">
                            <div class="detail-item">
                                <span class="detail-label">Customer:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Order Date:</span>
                                <span class="detail-value"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Total Amount:</span>
                                <span class="detail-value">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Items to Return -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-box"></i>
                        Select Items to Return
                    </div>
                    <div class="items-list">
                        <?php foreach ($order_items as $item): ?>
                            <div class="item-card">
                                <input type="checkbox" name="return_items[]" value="<?php echo $item['order_item_id']; ?>" class="item-checkbox" onchange="updateReturnTotal()">
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="item-details">
                                        <?php if ($item['variant_name']): ?>
                                            Variant: <?php echo htmlspecialchars($item['variant_name']); ?><br>
                                        <?php endif; ?>
                                        Quantity Ordered: <?php echo $item['quantity']; ?> | 
                                        Unit Price: ₹<?php echo number_format($item['price'], 2); ?>
                                    </div>
                                    <div class="quantity-selector">
                                        <label>Return Quantity:</label>
                                        <input type="number" name="return_quantity[<?php echo $item['order_item_id']; ?>]" 
                                               min="1" max="<?php echo $item['quantity']; ?>" value="<?php echo $item['quantity']; ?>" 
                                               class="quantity-input" onchange="updateReturnTotal()">
                                    </div>
                                </div>
                                <div class="item-price">₹<?php echo number_format($item['total'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Return Reason -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-comment"></i>
                        Return Reason
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reason for Return *</label>
                        <select name="return_reason_id" class="form-select" required>
                            <option value="">Select a reason</option>
                            <?php foreach ($return_reasons as $reason): ?>
                                <option value="<?php echo $reason['reason_id']; ?>">
                                    <?php echo htmlspecialchars($reason['reason_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Additional Details</label>
                        <textarea name="custom_reason" class="form-textarea" placeholder="Please provide any additional details about your return request..."></textarea>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-phone"></i>
                        Contact Information
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="customer_email" class="form-input" value="<?php echo htmlspecialchars($order['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="customer_phone" class="form-input" value="<?php echo htmlspecialchars($order['phone']); ?>">
                    </div>
                </div>
                
                <button type="submit" class="submit-btn" id="submitBtn" disabled>
                    <i class="fas fa-paper-plane"></i> Submit Return Request
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function updateReturnTotal() {
    const checkboxes = document.querySelectorAll('input[name="return_items[]"]:checked');
    const submitBtn = document.getElementById('submitBtn');
    
    if (checkboxes.length > 0) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateReturnTotal();
});
</script>

<?php include 'includes/footer.php'; ?>
