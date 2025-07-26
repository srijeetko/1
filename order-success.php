<?php
session_start();
require_once 'includes/db_connection.php';

// Get order ID from URL parameter or session
$order_id = $_GET['order_id'] ?? null;
$recovered = $_GET['recovered'] ?? false;

if (!$order_id) {
    // Fallback to session data if available
    if (isset($_SESSION['order_success'])) {
        $orderData = $_SESSION['order_success'];
        unset($_SESSION['order_success']);
    } else {
        header('Location: index.php');
        exit();
    }
} else {
    // Get order data from database
    try {
        $stmt = $pdo->prepare("
            SELECT
                co.*,
                pt.transaction_status,
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

        // Convert to expected format
        $orderData = [
            'order_id' => $order['order_id'],
            'order_number' => $order['order_number'],
            'customer_name' => $order['first_name'] . ' ' . $order['last_name'],
            'email' => $order['email'],
            'phone' => $order['phone'],
            'payment_method' => $order['payment_method'],
            'total_amount' => $order['total_amount'],
            'order_status' => $order['order_status'],
            'payment_status' => $order['payment_status']
        ];

    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}

$pageTitle = "Order Confirmation - Alpha Nutrition";
include 'includes/header.php';
?>

<style>
.success-container {
    max-width: 800px;
    margin: 50px auto;
    padding: 0 20px;
}

.success-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    padding: 40px;
    text-align: center;
}

.success-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #4CAF50, #45a049);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 30px;
    font-size: 40px;
    color: white;
    animation: successPulse 2s ease-in-out infinite;
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes confetti {
    0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
    100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
}

.success-card {
    animation: slideInUp 0.8s ease-out;
}

.confetti {
    position: fixed;
    width: 10px;
    height: 10px;
    background: #f39c12;
    animation: confetti 3s linear infinite;
    z-index: 1000;
}

.confetti:nth-child(1) { left: 10%; animation-delay: 0s; background: #e74c3c; }
.confetti:nth-child(2) { left: 20%; animation-delay: 0.2s; background: #3498db; }
.confetti:nth-child(3) { left: 30%; animation-delay: 0.4s; background: #2ecc71; }
.confetti:nth-child(4) { left: 40%; animation-delay: 0.6s; background: #f39c12; }
.confetti:nth-child(5) { left: 50%; animation-delay: 0.8s; background: #9b59b6; }
.confetti:nth-child(6) { left: 60%; animation-delay: 1s; background: #e67e22; }
.confetti:nth-child(7) { left: 70%; animation-delay: 1.2s; background: #1abc9c; }
.confetti:nth-child(8) { left: 80%; animation-delay: 1.4s; background: #e91e63; }
.confetti:nth-child(9) { left: 90%; animation-delay: 1.6s; background: #34495e; }

.success-title {
    font-size: 2.5em;
    color: #2c3e50;
    margin-bottom: 15px;
    font-weight: bold;
}

.success-subtitle {
    font-size: 1.2em;
    color: #666;
    margin-bottom: 30px;
}

.order-details {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 30px;
    margin: 30px 0;
    text-align: left;
}

.order-details h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    text-align: center;
    font-size: 1.5em;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
}

.detail-row:last-child {
    border-bottom: none;
    font-weight: bold;
    font-size: 1.1em;
    color: #2c3e50;
}

.detail-label {
    font-weight: 600;
    color: #555;
}

.detail-value {
    color: #2c3e50;
}

.order-number {
    font-family: 'Courier New', monospace;
    background: #e3f2fd;
    padding: 5px 10px;
    border-radius: 5px;
    font-weight: bold;
}

.payment-method {
    text-transform: uppercase;
    font-weight: bold;
}

.next-steps {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 10px;
    padding: 20px;
    margin: 30px 0;
}

.next-steps h4 {
    color: #856404;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
}

.next-steps h4 i {
    margin-right: 10px;
}

.next-steps ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.next-steps li {
    padding: 8px 0;
    color: #856404;
    display: flex;
    align-items: center;
}

.next-steps li i {
    margin-right: 10px;
    width: 20px;
}

.action-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-top: 30px;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #f8f9fa;
    color: #495057;
    border: 2px solid #dee2e6;
}

.btn-secondary:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.contact-info {
    background: #e8f5e9;
    border-radius: 10px;
    padding: 20px;
    margin-top: 30px;
}

.contact-info h4 {
    color: #2e7d32;
    margin-bottom: 15px;
}

.contact-info p {
    margin: 5px 0;
    color: #2e7d32;
}

@media (max-width: 768px) {
    .success-container {
        margin: 20px auto;
        padding: 0 15px;
    }
    
    .success-card {
        padding: 30px 20px;
    }
    
    .success-title {
        font-size: 2em;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
}
</style>

<!-- Confetti Animation -->
<div class="confetti"></div>
<div class="confetti"></div>
<div class="confetti"></div>
<div class="confetti"></div>
<div class="confetti"></div>
<div class="confetti"></div>
<div class="confetti"></div>
<div class="confetti"></div>
<div class="confetti"></div>

<div class="success-container">
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h1 class="success-title">
            <?php if ($recovered): ?>
                Order Recovered Successfully! 🎉
            <?php else: ?>
                Order Placed Successfully! 🎉
            <?php endif; ?>
        </h1>
        <p class="success-subtitle">
            <?php if ($recovered): ?>
                Your payment was successful and we've recovered your order details. Thank you for your patience!
            <?php else: ?>
                Thank you for your order. We'll process it shortly and keep you updated.
            <?php endif; ?>
        </p>
        
        <div class="order-details">
            <h3><i class="fas fa-receipt"></i> Order Details</h3>
            
            <div class="detail-row">
                <span class="detail-label">Order Number:</span>
                <span class="detail-value order-number"><?php echo htmlspecialchars($orderData['order_number']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Customer Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($orderData['customer_name']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?php echo htmlspecialchars($orderData['email']); ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value payment-method">
                    <?php 
                    $paymentLabels = [
                        'cod' => 'Cash on Delivery',
                        'razorpay' => 'Online Payment (Razorpay)',
                        'cashfree' => 'Online Payment (Cashfree)'
                    ];
                    echo $paymentLabels[$orderData['payment_method']] ?? 'Unknown';
                    ?>
                </span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value">₹<?php echo number_format($orderData['total_amount'], 0); ?></span>
            </div>
        </div>
        
        <?php if ($orderData['payment_method'] === 'cod'): ?>
        <div class="next-steps">
            <h4><i class="fas fa-info-circle"></i> What's Next?</h4>
            <ul>
                <li><i class="fas fa-check-circle"></i> Your order has been confirmed</li>
                <li><i class="fas fa-box"></i> We'll prepare your items for shipment</li>
                <li><i class="fas fa-truck"></i> You'll receive tracking information via email/SMS</li>
                <li><i class="fas fa-money-bill-wave"></i> Pay cash when your order is delivered</li>
            </ul>
        </div>
        <?php else: ?>
        <div class="next-steps">
            <h4><i class="fas fa-info-circle"></i> What's Next?</h4>
            <ul>
                <li><i class="fas fa-credit-card"></i> Complete your payment to confirm the order</li>
                <li><i class="fas fa-box"></i> We'll prepare your items after payment confirmation</li>
                <li><i class="fas fa-truck"></i> You'll receive tracking information via email/SMS</li>
                <li><i class="fas fa-shield-alt"></i> Your payment is secure and protected</li>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="order-details.php?order_id=<?php echo urlencode($orderData['order_id']); ?>" class="btn btn-primary">
                <i class="fas fa-receipt"></i>
                View Order Details
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i>
                Continue Shopping
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="my-orders.php" class="btn btn-secondary">
                    <i class="fas fa-list"></i>
                    View All Orders
                </a>
            <?php endif; ?>
        </div>
        
        <div class="contact-info">
            <h4><i class="fas fa-headset"></i> Need Help?</h4>
            <p><strong>Email:</strong> support@alphanutrition.com</p>
            <p><strong>Phone:</strong> +91-9876543210</p>
            <p><strong>Hours:</strong> Monday - Saturday, 9 AM - 6 PM</p>
        </div>
    </div>
</div>

<script>
// Auto-scroll to top
window.scrollTo(0, 0);

// Optional: Send order confirmation email (would be implemented server-side)
console.log('Order placed successfully:', <?php echo json_encode($orderData); ?>);
</script>

<?php include 'includes/footer.php'; ?>
