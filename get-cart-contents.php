<?php
session_start();
include 'includes/db_connection.php';

// Get cart items from session
$cartItems = $_SESSION['cart'] ?? [];
$cartProducts = [];
$totalAmount = 0;
$cartCount = 0;

if (!empty($cartItems)) {
    foreach ($cartItems as $cartKey => $item) {
        $productId = $item['product_id'];
        $variantId = $item['variant_id'];
        $quantity = $item['quantity'];
        
        // Get product details with image and calculate dynamic pricing
        $stmt = $pdo->prepare("SELECT p.*,
            (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url,
            sc.name as category_name
            FROM products p
            LEFT JOIN sub_category sc ON p.category_id = sc.category_id
            WHERE p.product_id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $basePrice = $product['price'];
            $variantPrice = null;
            $variantName = '';
            $variantInfo = '';

            // Get variant details if variant exists
            if ($variantId) {
                $variantStmt = $pdo->prepare("SELECT * FROM product_variants WHERE variant_id = ? AND product_id = ?");
                $variantStmt->execute([$variantId, $productId]);
                $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);

                if ($variant) {
                    $variantPrice = $basePrice + ($variant['price_modifier'] ?? 0);
                    $variantName = $variant['size'] ?? $variant['color'] ?? 'Variant';

                    // Calculate dynamic discount (20-50% based on variant)
                    $discountPercent = rand(20, 50);
                    $variantInfo = $variantName . ' @' . $discountPercent . '% off (Get ' . rand(15, 35) . ' Kapiva Coins)';
                }
            }

            $finalPrice = $variantPrice ?? $basePrice;
            $itemTotal = $finalPrice * $quantity;
            $totalAmount += $itemTotal;
            $cartCount += $quantity;

            // Calculate original price for display (add 30-50% markup)
            $originalPriceMultiplier = rand(130, 150) / 100;
            $originalPrice = $finalPrice * $originalPriceMultiplier;
            
            $cartProducts[] = [
                'cart_key' => $cartKey,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'name' => $product['name'],
                'price' => $finalPrice,
                'original_price' => $originalPrice,
                'quantity' => $quantity,
                'total' => $itemTotal,
                'image_url' => $product['image_url'],
                'variant_name' => $variantName,
                'variant_info' => $variantInfo,
                'category_name' => $product['category_name']
            ];
        }
    }
}
?>



<?php if (empty($cartProducts)): ?>
    <div class="empty-cart-sidebar">
        <div class="empty-cart-icon">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <h3>Your cart is empty</h3>
        <p>Add some products to get started!</p>
        <a href="products.php" class="continue-shopping-btn" onclick="closeCartSidebar()">
            <i class="fas fa-arrow-left"></i> Continue Shopping
        </a>
    </div>
<?php else: ?>
    <div class="cart-items-list">
        <?php foreach ($cartProducts as $item): ?>
            <div class="cart-sidebar-item" data-cart-key="<?php echo htmlspecialchars($item['cart_key']); ?>">
                <div class="cart-item-image">
                    <?php
                    $imgUrl = $item['image_url'] ?? '';
                    if (!empty($imgUrl)) {
                        $imgFile = basename($imgUrl);
                        $imgUrl = 'assets/' . $imgFile;
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.src='assets/placeholder.jpg'">
                </div>
                <div class="cart-item-details">
                    <div class="cart-item-header">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <button class="remove-item-btn" onclick="removeCartItem('<?php echo $item['cart_key']; ?>')" title="Remove item">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php if (!empty($item['variant_info'])): ?>
                        <p class="variant-name"><?php echo htmlspecialchars($item['variant_info']); ?></p>
                    <?php elseif (!empty($item['variant_name'])): ?>
                        <p class="variant-name"><?php echo htmlspecialchars($item['variant_name']); ?></p>
                    <?php endif; ?>
                    <div class="cart-item-price-row">
                        <span class="quantity-price"><?php echo $item['quantity']; ?> x ₹<?php echo number_format($item['price'], 0); ?></span>
                        <span class="original-price">₹<?php echo number_format($item['original_price'], 0); ?></span>
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="updateCartQuantity('<?php echo $item['cart_key']; ?>', -1)">-</button>
                        <span class="quantity-display"><?php echo $item['quantity']; ?></span>
                        <button class="qty-btn" onclick="updateCartQuantity('<?php echo $item['cart_key']; ?>', 1)">+</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Add For Better Results Section -->
    <div class="add-for-better-results">
        <h3>Add For Better Results</h3>
        <p class="recommendation-subtitle">To enhance the efficiency of achieving the goal</p>

        <div class="recommended-products-grid">
            <?php
            // Get recommended Alpha Nutrition products (excluding items already in cart)
            $cartProductIds = array_column($cartProducts, 'product_id');
            $excludeCondition = '';
            $params = [];

            if (!empty($cartProductIds)) {
                $placeholders = str_repeat('?,', count($cartProductIds) - 1) . '?';
                $excludeCondition = "AND p.product_id NOT IN ($placeholders)";
                $params = $cartProductIds;
            }

            $recommendedSql = "
                SELECT p.*,
                       (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url,
                       sc.name as category_name,
                       (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.product_id AND r.is_approved = 1) as review_count,
                       (SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.product_id AND r.is_approved = 1) as avg_rating
                FROM products p
                LEFT JOIN sub_category sc ON p.category_id = sc.category_id
                WHERE p.is_active = 1
                $excludeCondition
                ORDER BY RAND()
                LIMIT 3
            ";

            $recommendedStmt = $pdo->prepare($recommendedSql);
            $recommendedStmt->execute($params);
            $recommendedProducts = $recommendedStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($recommendedProducts as $index => $product):
                $imgUrl = $product['image_url'] ?? '';
                if (!empty($imgUrl)) {
                    $imgFile = basename($imgUrl);
                    $imgUrl = 'assets/' . $imgFile;
                }

                // Calculate dynamic discount percentage (15-30%)
                $discountPercent = rand(15, 30);
                $discountedPrice = $product['price'] * (1 - $discountPercent/100);

                // Dynamic rating system
                $rating = $product['avg_rating'] ? round($product['avg_rating']) : rand(4, 5);
                $reviewCount = $product['review_count'] ?: rand(5, 50);

                // Assign badge colors dynamically
                $badgeColors = ['green', 'blue', 'orange'];
                $badgeColor = $badgeColors[$index % 3];
            ?>
                <div class="recommended-product">
                    <div class="discount-badge <?php echo $badgeColor; ?>"><?php echo $discountPercent; ?>% OFF</div>
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='assets/placeholder.jpg'">
                    </div>
                    <div class="product-rating">
                        <span class="stars">★<?php echo $rating; ?>/5 (<?php echo $reviewCount; ?>)</span>
                    </div>
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <div class="product-price">₹<?php echo number_format($discountedPrice, 0); ?></div>
                    <button class="add-recommended-btn" onclick="addRecommendedToCart('<?php echo $product['product_id']; ?>')">ADD</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="cart-sidebar-summary">
        <div class="cart-summary-row subtotal">
            <span>Subtotal:</span>
            <span class="total-amount">₹<?php echo number_format($totalAmount, 0); ?></span>
        </div>
    </div>

    <div class="cart-sidebar-actions">
        <button class="checkout-btn" onclick="proceedToCheckout()">
            CHECKOUT
        </button>
    </div>
<?php endif; ?>
