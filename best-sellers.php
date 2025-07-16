<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/db_connection.php';
include 'includes/header.php';

// Fetch best seller products
$sql = "
    SELECT p.*,
           COALESCE(
               (SELECT pi1.image_url FROM product_images pi1 WHERE pi1.product_id = p.product_id AND pi1.is_primary = 1 LIMIT 1),
               (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.product_id LIMIT 1)
           ) AS image_url,
           sc.name as category_name,
           MIN(pv.price_modifier) as min_price,
           MAX(pv.price_modifier) as max_price,
           bs.sales_count
    FROM products p
    INNER JOIN best_sellers bs ON p.product_id = bs.product_id
    LEFT JOIN sub_category sc ON p.category_id = sc.category_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE p.is_active = 1
    GROUP BY p.product_id
    ORDER BY bs.sales_count DESC, p.created_at DESC
";

$bestSellers = $pdo->query($sql)->fetchAll();
?>

<style>
.best-sellers-hero {
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    color: white;
    padding: 6rem 0 4rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.best-sellers-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="stars" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23stars)"/></svg>');
    opacity: 0.3;
}

.best-sellers-hero h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    position: relative;
    z-index: 2;
}

.best-sellers-hero p {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
    position: relative;
    z-index: 2;
}

.best-sellers-container {
    max-width: 473px;
    margin: 0 auto;
    padding: 3rem 2rem;
}

.best-sellers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 3rem;
    margin-top: 2rem;
}

.best-seller-card {
    background: white;
    overflow: visible;
    transition: all 0.3s ease;
    position: relative;
    text-align: center;
    border: none;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.best-seller-card:hover {
    transform: translateY(-5px);
}

.best-seller-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: #ff4444;
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 3;
    box-shadow: 0 2px 8px rgba(255, 68, 68, 0.3);
}

.best-seller-badge i {
    margin-right: 0.3rem;
    color: #ff6b35;
}

.product-image {
    width: 100%;
    height: 350px;
    object-fit: contain;
    object-position: center;
    background: white;
    transition: transform 0.3s ease;
    margin-bottom: 1rem;
    padding: 2rem;
    box-sizing: border-box;
    border-radius: 8px;
}

.best-seller-card:hover .product-image {
    transform: scale(1.02);
}

.product-info {
    padding: 0 1rem 1rem;
    text-align: left;
}

.product-brand {
    color: #666;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.3rem;
}

.product-category {
    color: #ff6b35;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.product-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.8rem;
}

.rating-stars {
    color: #ffc107;
    font-size: 0.9rem;
}

.rating-number {
    color: #666;
    font-size: 0.9rem;
    font-weight: 500;
}

.product-price {
    margin-bottom: 1.5rem;
}

.price-current {
    font-size: 1.2rem;
    font-weight: 700;
    color: #ff4444;
    margin-right: 0.5rem;
}

.price-original {
    font-size: 1rem;
    color: #999;
    text-decoration: line-through;
}

.view-product-btn {
    display: inline-block;
    background: #111;
    color: white;
    padding: 0.85rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    text-align: center;
    width: 100%;
    box-sizing: border-box;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.view-product-btn:hover {
    background: #222;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    color: white;
    text-decoration: none;
}

.no-products {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
}

.no-products i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1rem;
}

/* Enhanced Mobile Responsive Styles for Best Sellers */
@media (max-width: 768px) {
    .best-sellers-hero {
        padding: 4rem 0 3rem;
    }

    .best-sellers-hero h1 {
        font-size: 2.2rem;
        margin-bottom: 1rem;
        line-height: 1.2;
    }

    .best-sellers-hero p {
        font-size: 1rem;
        line-height: 1.6;
        max-width: 90%;
    }

    .best-sellers-container {
        max-width: 100%;
        padding: 2rem 15px;
    }

    .best-sellers-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .best-seller-card {
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 1rem;
    }

    .best-seller-badge {
        top: 1rem;
        left: 1rem;
        padding: 0.3rem 0.6rem;
        font-size: 0.7rem;
        border-radius: 4px;
    }

    .product-image {
        height: 200px;
        margin-bottom: 1rem;
    }

    .product-brand {
        font-size: 0.8rem;
        margin-bottom: 0.5rem;
    }

    .product-name {
        font-size: 1.2rem;
        margin-bottom: 0.8rem;
        line-height: 1.3;
    }

    .product-rating {
        margin-bottom: 1rem;
        justify-content: center;
    }

    .rating-stars {
        font-size: 0.9rem;
    }

    .rating-number {
        font-size: 1rem;
    }

    .review-count {
        font-size: 0.8rem;
    }

    .product-price-container {
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .price-current {
        font-size: 1.3rem;
    }

    .price-original {
        font-size: 1rem;
    }

    .view-product-btn {
        padding: 12px 20px;
        font-size: 0.9rem;
        border-radius: 6px;
        height: auto;
        min-height: 44px;
    }

    .no-products {
        padding: 3rem 1rem;
    }

    .no-products i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .no-products h2 {
        font-size: 1.3rem;
        margin-bottom: 0.5rem;
    }

    .no-products p {
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }
}

@media (max-width: 480px) {
    .best-sellers-hero {
        padding: 3rem 0 2rem;
    }

    .best-sellers-hero h1 {
        font-size: 1.8rem;
    }

    .best-sellers-hero p {
        font-size: 0.9rem;
        max-width: 95%;
    }

    .best-sellers-container {
        padding: 1.5rem 10px;
    }

    .best-sellers-grid {
        gap: 1rem;
        margin-top: 1rem;
    }

    .best-seller-card {
        padding: 1rem;
        border-radius: 8px;
    }

    .best-seller-badge {
        top: 0.5rem;
        left: 0.5rem;
        padding: 0.2rem 0.5rem;
        font-size: 0.65rem;
    }

    .product-image {
        height: 180px;
        margin-bottom: 0.8rem;
    }

    .product-name {
        font-size: 1.1rem;
        margin-bottom: 0.6rem;
    }

    .product-rating {
        margin-bottom: 0.8rem;
    }

    .rating-stars {
        font-size: 0.8rem;
    }

    .rating-number {
        font-size: 0.9rem;
    }

    .review-count {
        font-size: 0.75rem;
    }

    .product-price-container {
        margin-bottom: 1rem;
    }

    .price-current {
        font-size: 1.2rem;
    }

    .price-original {
        font-size: 0.9rem;
    }

    .view-product-btn {
        padding: 10px 16px;
        font-size: 0.85rem;
        border-radius: 4px;
    }

    .no-products {
        padding: 2rem 0.5rem;
    }

    .no-products i {
        font-size: 2.5rem;
    }

    .no-products h2 {
        font-size: 1.2rem;
    }

    .no-products p {
        font-size: 0.85rem;
    }
}
</style>

<div class="best-sellers-hero">
    <div class="container">
        <h1><i class="fas fa-star"></i> Best Sellers</h1>
        <p>Discover our most popular and highly-rated products, loved by customers worldwide</p>
    </div>
</div>

<div class="best-sellers-container">
    <?php if (!empty($bestSellers)): ?>
        <div class="best-sellers-grid">
            <?php foreach ($bestSellers as $product): ?>
                <div class="best-seller-card">
                    <div class="best-seller-badge">
                        Save 30%
                    </div>
                    
                    <?php
                    $imageUrl = $product['image_url'] ?? '';
                    $imagePath = $imageUrl;
                    ?>
                    
                    <?php if (!empty($imageUrl) && file_exists($imagePath)): ?>
                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                    <?php else: ?>
                        <div class="product-image" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #666;">
                            <i class="fas fa-image" style="font-size: 3rem;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-info">
                        <div class="product-brand">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'Brand'); ?>
                        </div>

                        <div class="product-rating">
                            <div class="rating-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="rating-number">4.6</span>
                        </div>

                        <h3 class="product-name">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </h3>

                        <div class="product-price">
                            <?php
                            $basePrice = $product['price'] ?? 0;
                            $originalPrice = $basePrice * 1.43; // Calculate original price (30% discount)
                            ?>
                            <span class="price-current">From ₹ <?php echo number_format($basePrice, 2); ?></span>
                            <span class="price-original">₹ <?php echo number_format($originalPrice, 2); ?></span>
                        </div>
                        
                        <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" 
                           class="view-product-btn">
                            <i class="fas fa-eye"></i> View Product
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-products">
            <i class="fas fa-star"></i>
            <h2>No Best Sellers Yet</h2>
            <p>We're working on curating our best products. Check back soon!</p>
            <a href="products.php" class="view-product-btn" style="display: inline-block; width: auto; margin-top: 1rem;">
                <i class="fas fa-shopping-bag"></i> Browse All Products
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
