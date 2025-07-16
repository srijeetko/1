<?php
session_start();

// Check if order success data exists
if (!isset($_SESSION['order_success'])) {
    header('Location: index.php');
    exit();
}

$orderData = $_SESSION['order_success'];

// Clear the session data after retrieving it
unset($_SESSION['order_success']);

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

<div class="success-container">
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h1 class="success-title">Order Placed Successfully!</h1>
        <p class="success-subtitle">Thank you for your order. We'll process it shortly and keep you updated.</p>
        
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
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Continue Shopping
            </a>
            <a href="my-orders.php" class="btn btn-secondary">
                <i class="fas fa-list"></i>
                View My Orders
            </a>
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
