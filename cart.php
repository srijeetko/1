<?php
include 'includes/header.php';
include 'includes/db_connection.php';

// Get cart items from session
$cartItems = $_SESSION['cart'] ?? [];
$cartCount = 0;
$totalAmount = 0;
$cartProducts = [];

// Debug: Check if cart items exist
echo "<!-- DEBUG: Cart items: " . print_r($cartItems, true) . " -->";

if (!empty($cartItems)) {
    // Fetch product details for cart items
    $productIds = array_values(array_map(function($item) { return $item['product_id']; }, $cartItems));
    echo "<!-- DEBUG: Product IDs: " . print_r($productIds, true) . " -->";
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';

    $sql = "
        SELECT p.*,
               COALESCE(
                   (SELECT pi1.image_url FROM product_images pi1 WHERE pi1.product_id = p.product_id AND pi1.is_primary = 1 LIMIT 1),
                   (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.product_id LIMIT 1)
               ) AS image_url,
               sc.name as category_name
        FROM products p
        LEFT JOIN sub_category sc ON p.category_id = sc.category_id
        WHERE p.product_id IN ($placeholders) AND p.is_active = 1
    ";

    echo "<!-- DEBUG: SQL: $sql -->";
    echo "<!-- DEBUG: Placeholders: $placeholders -->";
    echo "<!-- DEBUG: Product IDs count: " . count($productIds) . " -->";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<!-- DEBUG: Products from DB: " . print_r($products, true) . " -->";

    // Combine cart items with product details
    foreach ($cartItems as $cartKey => $cartItem) {
        foreach ($products as $product) {
            if ($product['product_id'] === $cartItem['product_id']) {
                $cartProducts[] = array_merge($product, $cartItem, ['cart_key' => $cartKey]);
                $cartCount += $cartItem['quantity'];
                $totalAmount += $product['price'] * $cartItem['quantity'];
                break;
            }
        }
    }
}

$originalAmount = $totalAmount * 1.43; // Calculate original price (30% discount)
$discount = $originalAmount - $totalAmount;
?>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<div class="flipkart-cart-container">
    <div class="flipkart-cart-main">
        <div class="flipkart-cart-left">
            <h1 class="flipkart-cart-title">My Cart (<?php echo $cartCount; ?>)</h1>

            <?php if (empty($cartProducts)): ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h2>Your cart is empty</h2>
                    <p>Add some products to get started!</p>
                    <a href="products.php" class="continue-shopping-btn">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($cartProducts as $item): ?>
                    <div class="flipkart-cart-item" data-cart-key="<?php echo htmlspecialchars($item['cart_key']); ?>">
                        <?php
                        $imgUrl = $item['image_url'] ?? '';
                        if (!empty($imgUrl)) {
                            $imgFile = basename($imgUrl);
                            $imgUrl = 'assets/' . $imgFile;
                        }
                        ?>

                        <?php if (!empty($imgUrl)): ?>
                            <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 class="flipkart-cart-img"
                                 onerror="this.src='assets/placeholder.jpg';">
                        <?php else: ?>
                            <div class="flipkart-cart-img" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #666;">
                                <i class="fas fa-image" style="font-size: 3rem;"></i>
                            </div>
                        <?php endif; ?>

                        <div class="flipkart-cart-details">
                            <div class="flipkart-cart-product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="flipkart-cart-category"><?php echo htmlspecialchars($item['category_name'] ?? 'Supplements'); ?></div>

                            <?php if (!empty($item['description'])): ?>
                                <div class="flipkart-cart-ingredients">
                                    <strong>Description:</strong> <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?><?php echo strlen($item['description']) > 100 ? '...' : ''; ?>
                                </div>
                            <?php endif; ?>

                            <div class="flipkart-cart-rating">
                                <div class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                                <span class="rating-number">4.5</span>
                                <div class="review-badge">124</div>
                            </div>

                            <div class="flipkart-cart-price-row">
                                <span class="flipkart-cart-price">₹<?php echo number_format($item['price'], 2); ?></span>
                                <span class="flipkart-cart-mrp">₹<?php echo number_format($item['price'] * 1.43, 2); ?></span>
                                <span class="flipkart-cart-discount">30% off</span>
                            </div>

                            <div class="quantity-controls">
                                <label>Quantity:</label>
                                <div class="quantity-selector">
                                    <button class="quantity-btn minus" onclick="updateQuantity('<?php echo $item['cart_key']; ?>', -1)">-</button>
                                    <span class="quantity-display"><?php echo $item['quantity']; ?></span>
                                    <button class="quantity-btn plus" onclick="updateQuantity('<?php echo $item['cart_key']; ?>', 1)">+</button>
                                </div>
                            </div>

                            <div class="flipkart-cart-actions">
                                <button class="flipkart-cart-btn remove-btn" onclick="removeFromCart('<?php echo $item['cart_key']; ?>')">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                                <a href="product-detail.php?id=<?php echo $item['product_id']; ?>" class="flipkart-cart-btn view-btn">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="flipkart-cart-right">
            <?php if (!empty($cartProducts)): ?>
                <div class="flipkart-cart-summary">
                    <div class="flipkart-cart-summary-title">PRICE DETAILS</div>
                    <div class="flipkart-cart-summary-row">
                        <span>Price (<?php echo $cartCount; ?> item<?php echo $cartCount > 1 ? 's' : ''; ?>)</span>
                        <span>₹<?php echo number_format($originalAmount, 2); ?></span>
                    </div>
                    <div class="flipkart-cart-summary-row">
                        <span>Discount</span>
                        <span class="flipkart-cart-discount">-₹<?php echo number_format($discount, 2); ?></span>
                    </div>
                    <div class="flipkart-cart-summary-row">
                        <span>Delivery Charges</span>
                        <span class="flipkart-cart-free">FREE</span>
                    </div>
                    <div class="flipkart-cart-summary-total">
                        <span>Total Amount</span>
                        <span>₹<?php echo number_format($totalAmount, 2); ?></span>
                    </div>
                    <div class="flipkart-cart-summary-savings">You will save ₹<?php echo number_format($discount, 2); ?> on this order</div>
                    <button class="flipkart-cart-checkout" onclick="proceedToCheckout()">Place Order</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.flipkart-cart-container {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 40px 20px;
}
.flipkart-cart-main {
    display: flex;
    max-width: 1200px;
    margin: 0 auto;
    gap: 32px;
}
.flipkart-cart-left {
    flex: 2;
    background: transparent;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
}
.flipkart-cart-title {
    font-size: 1.7rem;
    font-weight: 600;
    margin-bottom: 24px;
}
.flipkart-cart-item {
    display: flex;
    border: none;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    align-items: flex-start;
    background: #fff;
    border: 1px solid #e0e0e0;
    transition: transform 0.2s ease;
}
.flipkart-cart-item:hover {
    transform: translateY(-2px);
}
.flipkart-cart-img {
    width: 280px;
    height: 280px;
    object-fit: contain;
    border-radius: 12px;
    background: #f7f7f7;
    margin-right: 24px;
    border: none;
}
.flipkart-cart-details {
    flex: 1;
}
.flipkart-cart-product-name {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
    line-height: 1.3;
}
.flipkart-cart-category {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.flipkart-cart-ingredients {
    color: #555;
    font-size: 0.9rem;
    margin-bottom: 12px;
    line-height: 1.4;
}
.flipkart-cart-rating {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}
.rating-stars {
    display: flex;
    gap: 2px;
}
.rating-stars i {
    color: #ffa500;
    font-size: 0.9rem;
}
.rating-number {
    font-weight: 600;
    font-size: 1.1rem;
    color: #333;
}
.review-badge {
    background: #333;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}
.flipkart-cart-price-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.flipkart-cart-price {
    font-size: 1.2rem;
    font-weight: 600;
}
.flipkart-cart-mrp {
    text-decoration: line-through;
    color: #878787;
    font-size: 1rem;
}
.flipkart-cart-discount {
    color: #388e3c;
    font-weight: 500;
    font-size: 1rem;
}
.flipkart-cart-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 16px;
}
.flipkart-cart-btn {
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    min-width: 120px;
}
.add-to-cart-btn {
    background: #333;
    color: white;
}
.add-to-cart-btn:hover {
    background: #555;
    transform: translateY(-1px);
}
.buy-now-btn {
    background: #ff6b35;
    color: white;
}
.buy-now-btn:hover {
    background: #e55a2b;
    transform: translateY(-1px);
}
.flipkart-cart-right {
    flex: 1;
}
.flipkart-cart-summary {
    background: #fff;
    border-radius: 8px;
    padding: 24px;
    border: 1px solid #e0e0e0;
    font-size: 1.1rem;
}
.flipkart-cart-summary-title {
    font-weight: 600;
    color: #878787;
    margin-bottom: 18px;
    font-size: 1.1rem;
}
.flipkart-cart-summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
}
.flipkart-cart-summary-total {
    display: flex;
    justify-content: space-between;
    font-weight: 700;
    font-size: 1.2rem;
    border-top: 1px dashed #ddd;
    padding-top: 14px;
    margin-top: 14px;
    margin-bottom: 10px;
}
.flipkart-cart-summary-savings {
    color: #388e3c;
    font-size: 1rem;
    margin-bottom: 18px;
}
.flipkart-cart-free {
    color: #388e3c;
    font-weight: 500;
}
.flipkart-cart-checkout {
    width: 100%;
    background:rgb(8, 8, 8);
    color: #fff;
    border: none;
    padding: 14px 0;
    border-radius: 4px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.flipkart-cart-checkout:hover {
    background:rgb(10, 10, 10);
}

/* Empty Cart Styles */
.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
}

.empty-cart-icon {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.empty-cart h2 {
    color: #333;
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
}

.empty-cart p {
    color: #666;
    margin-bottom: 2rem;
}

.continue-shopping-btn {
    display: inline-block;
    background: #333;
    color: white;
    padding: 12px 24px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.continue-shopping-btn:hover {
    background: #555;
    transform: translateY(-2px);
    text-decoration: none;
    color: white;
}

/* Quantity Controls */
.quantity-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 12px 0;
}

.quantity-controls label {
    font-weight: 600;
    color: #333;
}

.quantity-selector {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.quantity-btn {
    background: #f8f9fa;
    border: none;
    padding: 8px 12px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.2s;
}

.quantity-btn:hover {
    background: #e9ecef;
}

.quantity-display {
    padding: 8px 16px;
    background: white;
    border-left: 1px solid #ddd;
    border-right: 1px solid #ddd;
    font-weight: 600;
    min-width: 40px;
    text-align: center;
}

/* Updated Cart Actions */
.remove-btn {
    background: #dc3545 !important;
    color: white !important;
}

.remove-btn:hover {
    background: #c82333 !important;
}

.view-btn {
    background: #6c757d !important;
    color: white !important;
    text-decoration: none;
}

.view-btn:hover {
    background: #5a6268 !important;
    text-decoration: none;
    color: white !important;
}

/* Enhanced Mobile Responsive Styles for Cart */
@media (max-width: 900px) {
    .flipkart-cart-main {
        flex-direction: column;
        gap: 20px;
    }

    .flipkart-cart-right {
        margin-top: 0;
    }

    .flipkart-cart-item {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }

    .flipkart-cart-img {
        margin-right: 0;
        margin-bottom: 1rem;
        width: 200px;
        height: 200px;
        align-self: center;
    }

    .quantity-controls {
        justify-content: center;
    }

    .flipkart-cart-actions {
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }
}

@media (max-width: 768px) {
    .flipkart-cart-container {
        padding: 20px 15px;
    }

    .flipkart-cart-title {
        font-size: 1.5rem;
        margin-bottom: 20px;
        text-align: center;
    }

    .flipkart-cart-item {
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 8px;
    }

    .flipkart-cart-img {
        width: 160px;
        height: 160px;
        border-radius: 8px;
    }

    .flipkart-cart-product-name {
        font-size: 1.2rem;
        margin-bottom: 8px;
    }

    .flipkart-cart-category {
        font-size: 0.8rem;
        margin-bottom: 8px;
    }

    .flipkart-cart-ingredients {
        font-size: 0.85rem;
        margin-bottom: 10px;
    }

    .flipkart-cart-rating {
        margin-bottom: 10px;
        justify-content: center;
    }

    .rating-stars i {
        font-size: 0.8rem;
    }

    .rating-number {
        font-size: 1rem;
    }

    .review-badge {
        font-size: 0.75rem;
        padding: 3px 6px;
    }

    .flipkart-cart-price-row {
        justify-content: center;
        margin-bottom: 15px;
    }

    .flipkart-cart-price {
        font-size: 1.1rem;
    }

    .flipkart-cart-mrp {
        font-size: 0.9rem;
    }

    .flipkart-cart-discount {
        font-size: 0.9rem;
    }

    .quantity-controls {
        margin: 15px 0;
    }

    .quantity-controls label {
        font-size: 0.9rem;
        margin-bottom: 5px;
        display: block;
    }

    .quantity-btn {
        padding: 6px 10px;
        font-size: 0.9rem;
    }

    .quantity-display {
        padding: 6px 12px;
        font-size: 0.9rem;
        min-width: 35px;
    }

    .flipkart-cart-btn {
        padding: 10px 16px;
        font-size: 0.85rem;
        min-width: 100px;
        border-radius: 4px;
    }

    .flipkart-cart-summary {
        padding: 20px;
        border-radius: 8px;
        margin-top: 20px;
    }

    .flipkart-cart-summary-title {
        font-size: 1rem;
        margin-bottom: 15px;
        text-align: center;
    }

    .flipkart-cart-summary-row {
        margin-bottom: 10px;
        font-size: 0.9rem;
    }

    .flipkart-cart-summary-total {
        font-size: 1.1rem;
        padding-top: 12px;
        margin-top: 12px;
    }

    .flipkart-cart-summary-savings {
        font-size: 0.9rem;
        margin-bottom: 15px;
        text-align: center;
    }

    .flipkart-cart-checkout {
        padding: 12px 0;
        font-size: 1rem;
        border-radius: 6px;
    }

    /* Empty Cart Mobile */
    .empty-cart {
        padding: 3rem 1rem;
        margin: 20px 0;
    }

    .empty-cart-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .empty-cart h2 {
        font-size: 1.3rem;
        margin-bottom: 0.5rem;
    }

    .empty-cart p {
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }

    .continue-shopping-btn {
        padding: 10px 20px;
        font-size: 0.9rem;
        border-radius: 4px;
    }
}

@media (max-width: 480px) {
    .flipkart-cart-container {
        padding: 15px 10px;
    }

    .flipkart-cart-title {
        font-size: 1.3rem;
    }

    .flipkart-cart-item {
        padding: 12px;
    }

    .flipkart-cart-img {
        width: 140px;
        height: 140px;
    }

    .flipkart-cart-product-name {
        font-size: 1.1rem;
    }

    .flipkart-cart-actions {
        flex-direction: column;
        gap: 8px;
    }

    .flipkart-cart-btn {
        width: 100%;
        padding: 8px 12px;
        font-size: 0.8rem;
    }

    .flipkart-cart-summary {
        padding: 15px;
    }
}
</style>

<script>
// Update quantity function
function updateQuantity(cartKey, change) {
    const cartItem = document.querySelector(`[data-cart-key="${cartKey}"]`);
    const quantityDisplay = cartItem.querySelector('.quantity-display');
    let currentQuantity = parseInt(quantityDisplay.textContent);
    let newQuantity = currentQuantity + change;

    if (newQuantity < 1) {
        if (confirm('Remove this item from cart?')) {
            removeFromCart(cartKey);
        }
        return;
    }

    // Extract product ID and variant ID from cart key
    const [productId, variantId] = cartKey.split('_');

    // Update quantity via AJAX
    fetch('cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_quantity',
            product_id: productId,
            variant_id: variantId === 'default' ? null : variantId,
            quantity: newQuantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update display
            quantityDisplay.textContent = newQuantity;

            // Update cart count in header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount && data.cart_count !== undefined) {
                cartCount.textContent = data.cart_count;
            }

            // Reload page to update totals
            location.reload();
        } else {
            alert('Failed to update quantity: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update quantity');
    });
}

// Remove from cart function
function removeFromCart(cartKey) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }

    // Extract product ID and variant ID from cart key
    const [productId, variantId] = cartKey.split('_');

    // Remove item via AJAX
    fetch('cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove_from_cart',
            product_id: productId,
            variant_id: variantId === 'default' ? null : variantId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount && data.cart_count !== undefined) {
                cartCount.textContent = data.cart_count;
            }

            // Reload page to update display
            location.reload();
        } else {
            alert('Failed to remove item: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove item');
    });
}

// Proceed to checkout function
function proceedToCheckout() {
    // Check if cart has items
    const cartItems = document.querySelectorAll('.flipkart-cart-item');
    if (cartItems.length === 0) {
        alert('Your cart is empty. Please add some items before checkout.');
        return;
    }

    // Redirect to checkout page
    window.location.href = 'checkout.php';
}

// Load cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_cart_count'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
            }
        }
    })
    .catch(error => {
        console.error('Error loading cart count:', error);
    });
});
</script>

<?php
include 'includes/footer.php';
?>
