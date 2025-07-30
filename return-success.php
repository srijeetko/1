<?php
include 'includes/header.php';
include 'includes/db_connection.php';

$return_number = $_GET['return_number'] ?? '';
$return_request = null;

if ($return_number) {
    // Get return request details
    $stmt = $pdo->prepare("
        SELECT rr.*, rrs.reason_name, co.order_number
        FROM return_requests rr
        LEFT JOIN return_reasons rrs ON rr.return_reason_id = rrs.reason_id
        LEFT JOIN checkout_orders co ON rr.order_id = co.order_id
        WHERE rr.return_number = ?
    ");
    $stmt->execute([$return_number]);
    $return_request = $stmt->fetch();
}
?>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
.success-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.success-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.success-header {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 2rem;
    text-align: center;
}

.success-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    animation: checkmark 0.6s ease-in-out;
}

@keyframes checkmark {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.success-title {
    font-size: 2rem;
    font-weight: 600;
    margin: 0 0 0.5rem 0;
}

.success-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.success-content {
    padding: 2rem;
}

.return-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e9ecef;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 500;
    color: #495057;
}

.detail-value {
    color: #212529;
    font-weight: 600;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-approved {
    background: #d1edff;
    color: #0c5460;
}

.next-steps {
    background: #e7f3ff;
    border: 1px solid #b8daff;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.next-steps h3 {
    color: #004085;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.next-steps ul {
    margin: 0;
    padding-left: 1.5rem;
    color: #004085;
}

.next-steps li {
    margin-bottom: 0.5rem;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    text-align: center;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary {
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 53, 0.3);
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
    color: white;
}

.btn-outline {
    background: transparent;
    border: 2px solid #ff6b35;
    color: #ff6b35;
}

.btn-outline:hover {
    background: #ff6b35;
    color: white;
}

.contact-info {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    margin-top: 2rem;
}

.contact-info h4 {
    margin: 0 0 1rem 0;
    color: #495057;
}

.contact-methods {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.contact-method {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
}

.contact-method i {
    color: #ff6b35;
}

@media (max-width: 768px) {
    .success-container {
        margin: 1rem auto;
        padding: 0 0.5rem;
    }
    
    .success-content {
        padding: 1rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .contact-methods {
        flex-direction: column;
        gap: 1rem;
    }
    
    .detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>

<div class="success-container">
    <?php if ($return_request): ?>
        <div class="success-card">
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="success-title">Return Request Submitted!</h1>
                <p class="success-subtitle">Your return request has been successfully submitted</p>
            </div>
            
            <div class="success-content">
                <div class="return-details">
                    <div class="detail-row">
                        <span class="detail-label">Return Number:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($return_request['return_number']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order Number:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($return_request['order_number']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Return Amount:</span>
                        <span class="detail-value">₹<?php echo number_format($return_request['total_return_amount'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Expected Refund:</span>
                        <span class="detail-value">₹<?php echo number_format($return_request['refund_amount'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Return Reason:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($return_request['reason_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge status-<?php echo $return_request['return_status']; ?>">
                                <i class="fas fa-clock"></i>
                                <?php echo ucfirst($return_request['return_status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Submitted On:</span>
                        <span class="detail-value"><?php echo date('M j, Y \a\t g:i A', strtotime($return_request['created_at'])); ?></span>
                    </div>
                </div>
                
                <div class="next-steps">
                    <h3><i class="fas fa-list-check"></i> What Happens Next?</h3>
                    <?php if ($return_request['return_status'] === 'approved'): ?>
                        <ul>
                            <li>Your return has been automatically approved</li>
                            <li>We will schedule a pickup within 2-3 business days</li>
                            <li>You'll receive a pickup confirmation via email/SMS</li>
                            <li>Once we receive and inspect the items, your refund will be processed</li>
                            <li>Refund will be credited to your original payment method within 5-7 business days</li>
                        </ul>
                    <?php else: ?>
                        <ul>
                            <li>Our team will review your return request within 24 hours</li>
                            <li>You'll receive an email notification once your request is approved</li>
                            <li>After approval, we'll schedule a pickup within 2-3 business days</li>
                            <li>Once we receive and inspect the items, your refund will be processed</li>
                            <li>Refund will be credited to your original payment method within 5-7 business days</li>
                        </ul>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <a href="return-tracking.php?return_number=<?php echo urlencode($return_request['return_number']); ?>" class="btn btn-primary">
                        <i class="fas fa-search"></i> Track Return Status
                    </a>
                    <a href="products.php" class="btn btn-outline">
                        <i class="fas fa-shopping-bag"></i> Continue Shopping
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
                
                <div class="contact-info">
                    <h4>Need Help?</h4>
                    <div class="contact-methods">
                        <div class="contact-method">
                            <i class="fas fa-envelope"></i>
                            <span>returns@alphanutrition.com</span>
                        </div>
                        <div class="contact-method">
                            <i class="fas fa-phone"></i>
                            <span>+91 98765 43210</span>
                        </div>
                        <div class="contact-method">
                            <i class="fas fa-clock"></i>
                            <span>Mon-Sat: 9 AM - 6 PM</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="success-card">
            <div class="success-content">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ffc107; margin-bottom: 1rem;"></i>
                    <h2>Return Request Not Found</h2>
                    <p>The return request you're looking for could not be found.</p>
                    <div class="action-buttons" style="justify-content: center; margin-top: 2rem;">
                        <a href="return-request.php" class="btn btn-primary">
                            <i class="fas fa-undo"></i> Submit New Return Request
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-home"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
