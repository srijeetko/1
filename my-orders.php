<?php
require_once 'includes/auth.php';

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php?redirect=my-orders.php');
    exit();
}

$currentUser = $auth->getCurrentUser();
$pageTitle = "My Orders - Alpha Nutrition";

// Handle API requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    if ($_GET['api'] === 'get_order_details' && isset($_GET['order_id'])) {
        try {
            $orderId = $_GET['order_id'];

            // Get order details - ensure user can only access their own orders
            $stmt = $pdo->prepare("
                SELECT co.*
                FROM checkout_orders co
                WHERE co.order_id = ? AND (co.user_id = ? OR co.email = ?)
            ");
            $stmt->execute([$orderId, $currentUser['user_id'], $currentUser['email']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
                exit;
            }

            // Get order items
            $stmt = $pdo->prepare("
                SELECT oi.*,
                       p.name as product_name_from_db,
                       COALESCE(pi.image_url, '') as product_image
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE oi.order_id = ?
                ORDER BY oi.created_at
            ");
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Use stored product name if available, fallback to database name
            foreach ($items as &$item) {
                if (empty($item['product_name']) && !empty($item['product_name_from_db'])) {
                    $item['product_name'] = $item['product_name_from_db'];
                }
            }

            echo json_encode([
                'success' => true,
                'order' => $order,
                'items' => $items
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid API request']);
    exit;
}

// Get user's orders
try {
    $stmt = $pdo->prepare("
        SELECT co.*, 
               COUNT(oi.order_item_id) as item_count,
               pt.transaction_status,
               pt.payment_gateway
        FROM checkout_orders co
        LEFT JOIN order_items oi ON co.order_id = oi.order_id
        LEFT JOIN payment_transactions pt ON co.order_id = pt.order_id
        WHERE co.user_id = ? OR co.email = ?
        GROUP BY co.order_id
        ORDER BY co.created_at DESC
    ");
    $stmt->execute([$currentUser['user_id'], $currentUser['email']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $orders = [];
    $error_message = "Unable to load orders at this time.";
}

include 'includes/header.php';
?>

<style>
.orders-container {
    max-width: 1200px;
    margin: 50px auto;
    padding: 0 20px;
}

.orders-header {
    text-align: center;
    margin-bottom: 40px;
}

.orders-header h1 {
    font-size: 2.5em;
    color: #2c3e50;
    margin-bottom: 10px;
}

.orders-header p {
    color: #666;
    font-size: 1.1em;
}

.orders-grid {
    display: grid;
    gap: 20px;
}

.order-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 25px;
    transition: transform 0.3s ease;
}

.order-card:hover {
    transform: translateY(-2px);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f8f9fa;
}

.order-number {
    font-size: 1.2em;
    font-weight: bold;
    color: #2c3e50;
    font-family: 'Courier New', monospace;
}

.order-date {
    color: #666;
    font-size: 0.9em;
}

.order-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.detail-item {
    text-align: center;
}

.detail-label {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.detail-value {
    font-weight: bold;
    color: #2c3e50;
}

.status-badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-badge.confirmed {
    background: #d4edda;
    color: #155724;
}

.status-badge.processing {
    background: #cce5ff;
    color: #004085;
}

.status-badge.shipped {
    background: #e2e3e5;
    color: #383d41;
}

.status-badge.delivered {
    background: #d1ecf1;
    color: #0c5460;
}

.status-badge.cancelled {
    background: #f8d7da;
    color: #721c24;
}

.payment-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75em;
    font-weight: bold;
}

.payment-badge.paid {
    background: #d4edda;
    color: #155724;
}

.payment-badge.pending {
    background: #f3f4f6;
    color: #374151;
}

.payment-badge.failed {
    background: #f8d7da;
    color: #721c24;
}

.order-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 20px;
}

.btn {
    padding: 8px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9em;
    font-weight: bold;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-secondary {
    background: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
}

.btn:hover {
    transform: translateY(-1px);
}

.empty-orders {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.empty-orders i {
    font-size: 4em;
    color: #dee2e6;
    margin-bottom: 20px;
}

.empty-orders h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.empty-orders p {
    color: #666;
    margin-bottom: 30px;
}

@media (max-width: 768px) {
    .orders-container {
        margin: 20px auto;
        padding: 0 15px;
    }
    
    .order-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .order-details {
        grid-template-columns: 1fr 1fr;
    }
    
    .order-actions {
        flex-direction: column;
    }
}
</style>

<div class="orders-container">
    <div class="orders-header">
        <h1>My Orders</h1>
        <p>Track and manage your Alpha Nutrition orders</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="empty-orders">
            <i class="fas fa-shopping-bag"></i>
            <h3>No Orders Yet</h3>
            <p>You haven't placed any orders with us yet. Start shopping to see your orders here!</p>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-shopping-cart"></i>
                Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="orders-grid">
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number"><?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div class="order-date"><?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div>
                            <span class="status-badge <?php echo $order['order_status']; ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Total Amount</div>
                            <div class="detail-value">₹<?php echo number_format($order['total_amount'], 0); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Items</div>
                            <div class="detail-value"><?php echo $order['item_count']; ?> item(s)</div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Payment Method</div>
                            <div class="detail-value"><?php echo strtoupper($order['payment_method']); ?></div>
                        </div>
                        
                        <div class="detail-item">
                            <div class="detail-label">Payment Status</div>
                            <div class="detail-value">
                                <span class="payment-badge <?php echo $order['payment_status']; ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="order-actions">
                        <button onclick="viewOrderDetails('<?php echo $order['order_id']; ?>')" class="btn btn-primary">
                            <i class="fas fa-eye"></i>
                            View Details
                        </button>
                        
                        <?php if ($order['order_status'] === 'pending' && $order['payment_status'] === 'pending'): ?>
                            <a href="cancel-order.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                Cancel Order
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['order_status'], ['shipped', 'delivered'])): ?>
                            <a href="track-order.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-map-marker-alt"></i>
                                Track Order
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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

<script>
function viewOrderDetails(orderId) {
    console.log('viewOrderDetails called with orderId:', orderId);
    alert('Button clicked! Order ID: ' + orderId); // Temporary test
    const modal = document.getElementById('orderDetailsModal');
    if (modal) {
        modal.style.display = 'block';
        loadOrderDetails(orderId);
    } else {
        console.error('Modal element not found');
        alert('Modal element not found!');
    }
}

function loadOrderDetails(orderId) {
    console.log('loadOrderDetails called with orderId:', orderId);
    fetch(`my-orders.php?api=get_order_details&order_id=${orderId}`)
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
                        <span class="order-number">#${order.order_number}</span>
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

            <div class="shipping-details">
                <h4>Shipping Information</h4>
                <div class="details-grid">
                    <div class="detail-item full-width">
                        <label>Delivery Address:</label>
                        <span>${order.address}, ${order.city}, ${order.state} - ${order.pincode}</span>
                    </div>
                    <div class="detail-item">
                        <label>Contact Phone:</label>
                        <span>${order.phone}</span>
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

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('orderDetailsModal');
    if (event.target == modal) {
        closeOrderModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
