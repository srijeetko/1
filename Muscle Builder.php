<?php
include 'includes/header.php';
include 'includes/db_connection.php';
include 'includes/category-mapping.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

// Get the category ID for Muscle Builder products
$muscle_builder_category_id = getMappedCategoryId('Muscle Builder');

// Fetch muscle builder products from database
$sql = "
    SELECT p.*,
           COALESCE(
               (SELECT pi1.image_url FROM product_images pi1 WHERE pi1.product_id = p.product_id AND pi1.is_primary = 1 LIMIT 1),
               (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.product_id LIMIT 1)
           ) AS image_url,
           sc.name as category_name,
           MIN(pv.price_modifier) as min_price,
           MAX(pv.price_modifier) as max_price,
           GROUP_CONCAT(DISTINCT CONCAT(pv.size, ':', pv.price_modifier, ':', pv.stock) SEPARATOR '|') as variants
    FROM products p
    LEFT JOIN sub_category sc ON p.category_id = sc.category_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE p.is_active = 1
";

// Add category filter if we found a muscle builder category
if ($muscle_builder_category_id) {
    $sql .= " AND p.category_id = :category_id";
}

$sql .= " GROUP BY p.product_id ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);

if ($muscle_builder_category_id) {
    $stmt->bindParam(':category_id', $muscle_builder_category_id);
}

$stmt->execute();
$products = $stmt->fetchAll();
?>

<!-- Category Selection Pills -->
<section class="category-selection">
    <div class="container">
        <div class="select-concern">
            <span class="filter-icon">🔍</span>
            <span class="select-label">SELECT CONCERN:</span>
            <div class="category-pills">
                <a href="workout-performance.php" class="pill">
                    <img src="assets/pre.jpg" alt="workout Performance" class="category-icon">
                    <span>workout Performance</span>
                </a>
                <a href="protien.php" class="pill">
                    <img src="assets/pro.jpg" alt="Protein" class="category-icon">
                    <span>Protein</span>
                </a>
                <a href="Gainer.php" class="pill">
                    <img src="assets/gainer.jpg" alt="Gainer" class="category-icon">
                    <span>Gainer</span>
                </a>
                <a href="Weight Management.php" class="pill">
                    <img src="assets/wei.jpg" alt="Weight Management" class="category-icon">
                    <span>Weight Management</span>
                </a>
                <a href="Muscle Builder.php" class="pill active">
                    <img src="assets/muscle.png" alt="Muscle Builder" class="category-icon">
                    <span>Muscle Builder</span>
                    <span class="check-icon">✓</span>
                </a>
                <a href="Health and Beautyr.php" class="pill">
                    <img src="assets/beauty.jpg" alt="Health and Beauty" class="category-icon">
                    <span>Health and Beauty</span>
                </a>
                <a href="Tablet.php" class="pill">
                    <img src="assets/tab.jpg" alt="Tablets" class="category-icon">
                    <span>Tablets</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Product Grid Section -->
<section class="products-section">
    <div class="container">
        <h2 class="section-title serif">Muscle Builder</h2>
        <div class="products-grid">
            <?php if (empty($products)): ?>
                <div class="no-products-message" style="text-align: center; padding: 40px; color: #666;">
                    <h3>No Muscle Builder products found</h3>
                    <p>We're working on adding more products to this category. Please check back soon!</p>
                    <a href="products.php" style="color: #8bc34a; text-decoration: none; font-weight: 600;">Browse All Products</a>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <?php
                    // Calculate display price
                    $displayPrice = $product['price'];
                    $originalPrice = null;

                    // If product has variants, use the minimum price
                    if (!empty($product['variants'])) {
                        $variants = explode('|', $product['variants']);
                        $prices = [];
                        foreach ($variants as $variant) {
                            $parts = explode(':', $variant);
                            if (count($parts) >= 2) {
                                $prices[] = floatval($parts[1]);
                            }
                        }
                        if (!empty($prices)) {
                            $displayPrice = min($prices);
                            if (count($prices) > 1) {
                                $originalPrice = max($prices);
                            }
                        }
                    }

                    // Format image URL
                    $imageUrl = !empty($product['image_url']) ? $product['image_url'] : 'assets/placeholder.jpg';
                    ?>
                    <div class="product-card"
                        style="display:flex;flex-direction:column;justify-content:space-between;height:420px;box-shadow:0 2px 12px #0001;border-radius:12px;background:#fff;">
                        <div style="position:relative;background:#f7f7f7;border-radius:12px 12px 0 0;overflow:hidden;">
                            <a href="product-detail.php?id=<?php echo htmlspecialchars($product['product_id']); ?>">
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                                     class="product-image"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="width:100%;height:200px;object-fit:contain;background:#fff;">
                            </a>
                        </div>
                        <div class="product-info"
                            style="padding:18px 18px 0 18px;flex:1;display:flex;flex-direction:column;justify-content:flex-start;">
                            <a href="product-detail.php?id=<?php echo htmlspecialchars($product['product_id']); ?>" style="text-decoration: none; color: inherit;">
                                <h3 class="product-title" style="font-size:1.15rem;font-weight:600;margin-bottom:8px;">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </h3>
                            </a>
                            <div class="product-price" style="font-size:1.35rem;font-weight:700;margin-bottom:6px;">
                                ₹<?php echo number_format($displayPrice, 0); ?>
                                <?php if ($originalPrice && $originalPrice > $displayPrice): ?>
                                    <span style="color:#888;font-size:1rem;text-decoration:line-through;">
                                        ₹<?php echo number_format($originalPrice, 0); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="margin-bottom:18px;color:#888;font-size:1rem;">
                                <?php if (!empty($product['category_name'])): ?>
                                    <span style="background:#f0f0f0;color:#666;padding:2px 8px;border-radius:12px;font-size:0.85rem;">
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top:auto;display:flex;align-items:center;gap:0.5rem;padding:0 18px 22px 18px;">
                            <button onclick="addToCart('<?php echo htmlspecialchars($product['product_id']); ?>')"
                                style="background:none;border:none;font-size:1.5rem;color:#222;cursor:pointer;padding:0 12px 0 0;">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                            <a href="product-detail.php?id=<?php echo htmlspecialchars($product['product_id']); ?>"
                                style="flex:1;background:#8bc34a;color:#fff;font-weight:700;font-size:1.1rem;padding:12px 0;border:none;border-radius:4px;cursor:pointer;text-decoration:none;text-align:center;display:block;">
                                VIEW DETAILS
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
// Add to cart functionality (placeholder for future implementation)
function addToCart(productId) {
    // This is a placeholder function for add to cart functionality
    // In a real implementation, this would make an AJAX call to add the product to cart
    alert('Add to cart functionality will be implemented soon!\nProduct ID: ' + productId);
}
</script>

<?php include 'includes/footer.php'; ?>