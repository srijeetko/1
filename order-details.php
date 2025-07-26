<?php
session_start();
require_once 'includes/db_connection.php';

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    header('Location: index.php');
    exit();
}

// Get order details with items
try {
    $stmt = $pdo->prepare("
        SELECT 
            co.*,
            pt.transaction_id,
            pt.transaction_status,
            pt.gateway_transaction_id,
            pt.payment_gateway
        FROM checkout_orders co
        LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
        WHERE co.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT
            oi.*,
            p.description
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
        ORDER BY oi.created_at
    ");
    $stmt->execute([$order_id]);
    $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = "Order Details - " . ($order['order_number'] ?? 'Unknown');
include 'includes/header.php';
?>

<style>
.order-container {
    max-width: 1000px;
    margin: 30px auto;
    padding: 0 20px;
}

.order-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px 15px 0 0;
    text-align: center;
}

.order-content {
    background: white;
    border-radius: 0 0 15px 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.order-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    padding: 30px;
    border-bottom: 1px solid #e9ecef;
}

.info-section h3 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 1.2em;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f8f9fa;
}

.info-label {
    font-weight: 600;
    color: #666;
}

.info-value {
    color: #2c3e50;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #d4edda; color: #155724; }
.status-paid { background: #d1ecf1; color: #0c5460; }
.status-failed { background: #f8d7da; color: #721c24; }

.items-section {
    padding: 30px;
}

.items-section h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: 1.3em;
}

.item-card {
    display: flex;
    align-items: center;
    padding: 20px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.item-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.item-image {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    object-fit: cover;
    margin-right: 20px;
    background: #f8f9fa;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 5px;
}

.item-variant {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 5px;
}

.item-price {
    color: #28a745;
    font-weight: bold;
}

.item-quantity {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 8px;
    text-align: center;
    min-width: 60px;
    margin: 0 15px;
}

.item-total {
    font-weight: bold;
    color: #2c3e50;
    font-size: 1.1em;
    text-align: right;
    min-width: 80px;
}

.order-summary {
    background: #f8f9fa;
    padding: 20px 30px;
    border-top: 1px solid #e9ecef;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.summary-total {
    font-weight: bold;
    font-size: 1.2em;
    color: #2c3e50;
    border-top: 2px solid #dee2e6;
    padding-top: 15px;
    margin-top: 10px;
}

.action-buttons {
    padding: 30px;
    text-align: center;
    border-top: 1px solid #e9ecef;
}

.btn {
    padding: 12px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    margin: 0 10px;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-secondary {
    background: #f8f9fa;
    color: #495057;
    border: 2px solid #dee2e6;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.no-items {
    text-align: center;
    padding: 40px;
    color: #666;
}

@media (max-width: 768px) {
    .order-info {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .item-card {
        flex-direction: column;
        text-align: center;
    }
    
    .item-image {
        margin: 0 0 15px 0;
    }
    
    .item-quantity {
        margin: 10px 0;
    }
}
</style>

<div class="order-container">
    <?php if (isset($error)): ?>
        <div style="background: #f8d7da; padding: 20px; border-radius: 10px; text-align: center;">
            <h2>❌ Error</h2>
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="index.php" class="btn btn-primary">Go Home</a>
        </div>
    <?php else: ?>
        <div class="order-header">
            <h1>Order Details</h1>
            <p>Order Number: <strong><?php echo htmlspecialchars($order['order_number']); ?></strong></p>
            <p>Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
        </div>
        
        <div class="order-content">
            <div class="order-info">
                <div class="info-section">
                    <h3>📋 Order Information</h3>
                    <div class="info-row">
                        <span class="info-label">Order Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status:</span>
                        <span class="info-value">
                            <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value">
                            <?php 
                            $methods = ['cod' => 'Cash on Delivery', 'cashfree' => 'Online Payment', 'razorpay' => 'Online Payment'];
                            echo $methods[$order['payment_method']] ?? ucfirst($order['payment_method']);
                            ?>
                        </span>
                    </div>
                    <?php if ($order['gateway_transaction_id']): ?>
                    <div class="info-row">
                        <span class="info-label">Transaction ID:</span>
                        <span class="info-value" style="font-family: monospace; font-size: 0.9em;">
                            <?php echo htmlspecialchars($order['gateway_transaction_id']); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <h3>🚚 Delivery Information</h3>
                    <div class="info-row">
                        <span class="info-label">Customer:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['email']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['phone']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value">
                            <?php echo htmlspecialchars($order['address'] . ', ' . $order['city'] . ', ' . $order['state'] . ' - ' . $order['pincode']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="items-section">
                <h3>🛍️ Order Items</h3>
                
                <?php if (empty($orderItems)): ?>
                    <div class="no-items">
                        <p>⚠️ No items found for this order.</p>
                        <p>This might be a data issue. Please contact support.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($orderItems as $item): ?>
                        <div class="item-card">
                            <img src="assets/images/placeholder.jpg"
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                 class="item-image">
                            
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <?php if ($item['variant_name']): ?>
                                    <div class="item-variant">Variant: <?php echo htmlspecialchars($item['variant_name']); ?></div>
                                <?php endif; ?>
                                <div class="item-price">₹<?php echo number_format($item['price'], 0); ?> each</div>
                            </div>
                            
                            <div class="item-quantity">
                                <strong>Qty: <?php echo $item['quantity']; ?></strong>
                            </div>
                            
                            <div class="item-total">
                                ₹<?php echo number_format($item['total'], 0); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₹<?php echo number_format($order['total_amount'], 0); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>Free</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total:</span>
                    <span>₹<?php echo number_format($order['total_amount'], 0); ?></span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="my-orders.php" class="btn btn-primary">View All Orders</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
