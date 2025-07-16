<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/header.php';
include 'includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

// Get all active banner images
$bannerImages = $pdo->query("SELECT image_path FROM banner_images WHERE status='active' ORDER BY created_at DESC")->fetchAll();
$heroImages = array_merge([

], array_map(function($img) { return $img['image_path']; }, $bannerImages));

// Fetch best seller products for homepage
$bestSellersQuery = "
    SELECT p.*,
           COALESCE(
               (SELECT pi1.image_url FROM product_images pi1 WHERE pi1.product_id = p.product_id AND pi1.is_primary = 1 LIMIT 1),
               (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.product_id LIMIT 1)
           ) AS image_url,
           sc.name as category_name,
           bs.sales_count,
           MIN(pv.price_modifier) as min_price,
           MAX(pv.price_modifier) as max_price
    FROM products p
    INNER JOIN best_sellers bs ON p.product_id = bs.product_id
    LEFT JOIN sub_category sc ON p.category_id = sc.category_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE p.is_active = 1
    GROUP BY p.product_id
    ORDER BY bs.sales_count DESC, p.created_at DESC
    LIMIT 6
";

$bestSellerProducts = $pdo->query($bestSellersQuery)->fetchAll();

// Fetch featured products for homepage
$featuredQuery = "
    SELECT p.*,
           COALESCE(
               (SELECT pi1.image_url FROM product_images pi1 WHERE pi1.product_id = p.product_id AND pi1.is_primary = 1 LIMIT 1),
               (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.product_id LIMIT 1)
           ) AS image_url,
           sc.name as category_name,
           MIN(pv.price_modifier) as min_price,
           MAX(pv.price_modifier) as max_price
    FROM products p
    LEFT JOIN sub_category sc ON p.category_id = sc.category_id
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    WHERE p.is_featured = 1 AND p.is_active = 1
    GROUP BY p.product_id
    ORDER BY p.sr_no ASC
    LIMIT 4
";

$featuredProducts = $pdo->query($featuredQuery)->fetchAll();
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-image-slider" id="hero-image-slider">
        <!-- Image slides -->
        <?php foreach ($heroImages as $index => $image): ?>
        <div class="hero-slide <?php echo $index === 0 ? 'active' : ''; ?>" id="slide-<?php echo $index; ?>">
            <img src="assets/<?php echo htmlspecialchars($image); ?>" alt="Hero Image">
        </div>
        <?php endforeach; ?>

        <?php if (empty($heroImages)): ?>
        <!-- Fallback content when no images -->
        <div class="hero-slide active hero-fallback">
            <div class="hero-fallback-content">
                <h2 class="hero-fallback-title">Alpha Nutrition</h2>
                <p class="hero-fallback-text">Premium supplements for your health and wellness journey</p>
                <a href="products.php" class="hero-fallback-btn">Shop Now</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($heroImages) > 1): ?>
        <button id="hero-slider-left" class="hero-nav-btn left">&#8592;</button>
        <button id="hero-slider-right" class="hero-nav-btn right">&#8594;</button>
        <?php endif; ?>
    </div>
</section>

<!-- Vitamin Categories -->
<section class="vitamin-categories">
    <div class="container">
        <h2 class="section-title serif" style="text-align:center; font-size:2.5rem; margin-bottom:0.5rem;">Explore Premium Vitamins from CF</h2>
        <p style="text-align:center; color:#444; font-size:1.2rem; margin-bottom:2.5rem;">
            We have vitamins for every age and all kinds of lifestyles. We provide an easy way to fill your nutritional gaps so that you can achieve more from your day.
        </p>
        <div class="category-grid custom-vitamin-cards">
            <div class="category-card men custom-card men-bg">
                <div class="custom-card-inner">
                    <div class="custom-card-header">
                        <strong>Explore Premium Vitamins from CF</strong>
                        <div style="font-size:0.95rem; color:#333; margin-top:0.3rem;">
                            We have vitamins for every age and all kinds of lifestyles. We provide an easy way to fill your nutritional gaps so that you can achieve more from your day.
                        </div>
                    </div>
                    <div class="custom-card-image-row">
                        <img src="assets/men.jpg" alt="Men" class="custom-card-img">
                        <img src="assets/kids.jpg" alt="Kids" class="custom-card-img">
                    </div>
                    <div class="custom-card-label-row">
                        <span>WOMEN</span>
                        <span>KIDS</span>
                    </div>
                </div>
                <div class="custom-card-title">MEN</div>
            </div>
            <div class="category-card women custom-card women-bg">
                <div class="custom-card-inner">
                    <div class="custom-card-header">
                        <strong>Explore Premium Vitamins from CF</strong>
                        <div style="font-size:0.95rem; color:#333; margin-top:0.3rem;">
                            We have vitamins for every age and all kinds of lifestyles. We provide an easy way to fill your nutritional gaps so that you can achieve more from your day.
                        </div>
                    </div>
                    <div class="custom-card-image-row">
                        <img src="assets/women.jpg" alt="Women" class="custom-card-img">
                        <img src="assets/kids.jpg" alt="Kids" class="custom-card-img">
                    </div>
                    <div class="custom-card-label-row">
                        <span>WOMEN</span>
                        <span>KIDS</span>
                    </div>
                </div>
                <div class="custom-card-title">WOMEN</div>
            </div>
            <div class="category-card kids custom-card kids-bg">
                <div class="custom-card-inner">
                    <div class="custom-card-header">
                        <strong>Explore Premium Vitamins from CF</strong>
                        <div style="font-size:0.95rem; color:#333; margin-top:0.3rem;">
                            We have vitamins for every age and all kinds of lifestyles. We provide an easy way to fill your nutritional gaps so that you can achieve more from your day.
                        </div>
                    </div>
                    <div class="custom-card-image-row">
                        <img src="assets/women.jpg" alt="Women" class="custom-card-img">
                        <img src="assets/kids.jpg" alt="Kids" class="custom-card-img">
                    </div>
                    <div class="custom-card-label-row">
                        <span>WOMEN</span>
                        <span>KIDS</span>
                    </div>
                </div>
                <div class="custom-card-title">KIDS</div>
            </div>
            <div class="category-card more custom-card more-bg">
                <div class="custom-card-inner">
                    <div class="custom-card-header">
                        <strong>Explore Premium Vitamins from CF</strong>
                        <div style="font-size:0.95rem; color:#333; margin-top:0.3rem;">
                            We have vitamins for every age and all kinds of lifestyles. We provide an easy way to fill your nutritional gaps so that you can achieve more from your day.
                        </div>
                    </div>
                    <div class="custom-card-image-row">
                        <img src="assets/women.jpg" alt="Women" class="custom-card-img">
                        <img src="assets/kids.jpg" alt="Kids" class="custom-card-img">
                    </div>
                    <div class="custom-card-label-row">
                        <span>WOMEN</span>
                        <span>KIDS</span>
                    </div>
                </div>
                <div class="custom-card-title">MORE</div>
            </div>
        </div>
    </div>
</section>

<style>
.custom-vitamin-cards {
    display: flex;
    justify-content: center;
    align-items: stretch;
    gap: 32px;
    margin-top: 32px;
    flex-wrap: wrap;
}
.custom-card {
    border-radius: 22px;
    padding: 0;
    width: 370px;
    min-height: 500px;
    display: flex;
    flex-direction: column;
    align-items: center;
    box-shadow: 0 2px 16px rgba(40,116,240,0.10);
    position: relative;
    margin-bottom: 24px;
    background: #fff;
    overflow: hidden;
}
.men-bg { background: #4da3ff; }
.women-bg { background: #ff99cc; }
.kids-bg { background: #ffe066; }
.more-bg { background: #6ee6ff; }
.custom-card-inner {
    width: 100%;
    padding: 24px 0 0 0;
    background: #fff;
    border-radius: 18px 18px 0 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.custom-card-header {
    width: 90%;
    background: #fff;
    border-radius: 10px 10px 0 0;
    text-align: center;
    padding: 12px 0 8px 0;
    font-size: 1.15rem;
    font-weight: 600;
    margin-bottom: 10px;
}
.custom-card-image-row {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0;
    width: 90%;
    margin: 0 auto;
    margin-bottom: 0;
}
.custom-card-img {
    width: 48%;
    height: 210px;
    object-fit: cover;
    border-radius: 0;
    margin: 0 2px;
}
.custom-card-label-row {
    display: flex;
    justify-content: space-between;
    width: 90%;
    margin: 0 auto 10px auto;
    font-weight: 700;
    font-size: 1.1rem;
    color: #222;
}
.custom-card-title {
    width: 100%;
    text-align: center;
    font-size: 2rem;
    font-weight: bold;
    margin: 18px 0 18px 0;
    letter-spacing: 1px;
    color: #111;
    background: transparent;
}
@media (max-width: 1200px) {
    .custom-vitamin-cards {
        gap: 18px;
    }
    .custom-card {
        width: 320px;
        min-height: 440px;
    }
}
@media (max-width: 900px) {
    .custom-vitamin-cards {
        flex-direction: column;
        align-items: center;
        gap: 18px;
    }
    .custom-card {
        width: 95%;
        min-width: 220px;
    }
}
.hero {
    position: relative;
    overflow: hidden;
    min-height: 420px;
    background: #f8f8f8;
    width: 100%;
    max-width: 100vw;
}

.hero-image-slider {
    position: relative;
    width: 100%;
    height: 50vh;
    min-height: 500px;
    max-height: 650px;
    overflow: hidden;
    max-width: 100vw;
}

.hero-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.7s ease-in-out;
}

.hero-slide.active {
    opacity: 1;
}

.hero-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    transition: transform 0.3s ease;
}

/* Ensure images scale properly on different screen sizes */
@media (min-width: 1200px) {
    .hero-slide img {
        object-fit: cover;
        object-position: center center;
        max-width: 100%;
        height: auto;
        min-height: 100%;
    }
}

/* Hero Navigation Buttons */
.hero-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 3;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    touch-action: manipulation;
    -webkit-tap-highlight-color: transparent;
    user-select: none;
    color: #333;
}

.hero-nav-btn:hover {
    background: rgba(255, 255, 255, 1);
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.hero-nav-btn.left {
    left: 20px;
}

.hero-nav-btn.right {
    right: 20px;
}

/* Hero Fallback Content */
.hero-fallback {
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-fallback-content {
    text-align: center;
    color: #6c757d;
    max-width: 600px;
    padding: 2rem;
}

.hero-fallback-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #333;
}

.hero-fallback-text {
    font-size: 1.2rem;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.hero-fallback-btn {
    background: #007bff;
    color: white;
    padding: 12px 24px;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
}

.hero-fallback-btn:hover {
    background: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    color: white;
    text-decoration: none;
}



.btn-primary, .btn-secondary {
    display: inline-block;
    padding: 12px 30px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.btn-primary {
    background: #ff6b35;
    color: white;
    border-color: #ff6b35;
}

.btn-primary:hover {
    background: transparent;
    color: #ff6b35;
    border-color: #ff6b35;
}

.btn-secondary {
    background: transparent;
    color: white;
    border-color: white;
}

.btn-secondary:hover {
    background: white;
    color: #667eea;
}

/* Responsive Hero Styles */
/* Ultra-wide desktop screens */
@media (min-width: 1920px) {
    .hero-image-slider {
        height: 70vh;
        min-height: 600px;
        max-height: 800px;
    }

    .hero {
        min-height: 70vh;
        max-height: 800px;
    }

    .hero-slide img {
        object-fit: cover;
        object-position: center;
        width: 100%;
        height: 100%;
    }
}

/* Large desktop screens */
@media (min-width: 1600px) and (max-width: 1919px) {
    .hero-image-slider {
        height: 60vh;
        min-height: 550px;
        max-height: 700px;
    }

    .hero {
        min-height: 60vh;
        max-height: 700px;
    }

    .hero-slide img {
        object-fit: cover;
        object-position: center;
        width: 100%;
        height: 100%;
    }
}

/* Standard desktop/laptop screens */
@media (max-width: 1599px) and (min-width: 1401px) {
    .hero-image-slider {
        height: 55vh;
        min-height: 500px;
        max-height: 600px;
    }

    .hero {
        min-height: 55vh;
        max-height: 600px;
    }

    .hero-slide img {
        object-fit: cover;
        object-position: center;
        width: 100%;
        height: 100%;
    }
}

@media (max-width: 1400px) {
    .hero-image-slider {
        height: 450px;
        min-height: 450px;
    }

    .hero {
        min-height: 450px;
    }
}

@media (max-width: 1200px) {
    .hero-image-slider {
        height: 360px;
        min-height: 360px;
    }

    .hero {
        min-height: 360px;
    }
}

@media (max-width: 992px) {
    .hero-image-slider {
        height: 320px;
        min-height: 320px;
    }

    .hero {
        min-height: 320px;
    }

    .hero-nav-btn {
        width: 45px;
        height: 45px;
        font-size: 16px;
    }

    .hero-nav-btn.left {
        left: 15px;
    }

    .hero-nav-btn.right {
        right: 15px;
    }
}

@media (max-width: 768px) {
    .hero-image-slider {
        height: 250px;
        min-height: 250px;
    }

    .hero {
        min-height: 250px;
    }

    .hero-nav-btn {
        width: 40px;
        height: 40px;
        font-size: 14px;
    }

    .hero-nav-btn.left {
        left: 10px;
    }

    .hero-nav-btn.right {
        right: 10px;
    }

    .hero-title {
        font-size: 2rem;
    }

    .hero-description {
        font-size: 1rem;
    }

    .hero-buttons {
        flex-direction: column;
        align-items: center;
    }

    .hero-fallback-title {
        font-size: 2rem;
    }

    .hero-fallback-text {
        font-size: 1rem;
    }

    .hero-fallback-content {
        padding: 1.5rem;
    }
}

@media (max-width: 576px) {
    .hero-image-slider {
        height: 220px;
        min-height: 220px;
    }

    .hero {
        min-height: 220px;
    }

    .hero-nav-btn {
        width: 35px;
        height: 35px;
        font-size: 12px;
    }

    .hero-nav-btn.left {
        left: 8px;
    }

    .hero-nav-btn.right {
        right: 8px;
    }

    .hero-fallback-title {
        font-size: 1.8rem;
    }

    .hero-fallback-text {
        font-size: 0.9rem;
    }

    .hero-fallback-content {
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .hero-image-slider {
        height: 200px;
        min-height: 200px;
    }

    .hero {
        min-height: 200px;
    }

    .hero-nav-btn {
        width: 32px;
        height: 32px;
        font-size: 11px;
    }

    .hero-nav-btn.left {
        left: 5px;
    }

    .hero-nav-btn.right {
        right: 5px;
    }

    .hero-fallback-title {
        font-size: 1.5rem;
    }

    .hero-fallback-text {
        font-size: 0.85rem;
    }

    .hero-fallback-content {
        padding: 0.8rem;
    }

    .hero-fallback-btn {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}

@media (max-width: 360px) {
    .hero-image-slider {
        height: 180px;
        min-height: 180px;
    }

    .hero {
        min-height: 180px;
    }

    .hero-nav-btn {
        width: 28px;
        height: 28px;
        font-size: 10px;
    }

    .hero-nav-btn.left {
        left: 3px;
    }

    .hero-nav-btn.right {
        right: 3px;
    }

    .hero-fallback-title {
        font-size: 1.2rem;
    }

    .hero-fallback-text {
        font-size: 0.8rem;
    }

    .hero-fallback-content {
        padding: 0.6rem;
    }

    .hero-fallback-btn {
        padding: 8px 16px;
        font-size: 0.8rem;
    }
}
.hero-img-slide {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    display: block;
    position: absolute;
    left: 0;
    top: 0;
    transition: opacity 0.7s;
}

/* Responsive hero image adjustments */
@media (max-width: 768px) {
    .hero-img-slide {
        object-position: center center;
    }
}

@media (max-width: 576px) {
    .hero-img-slide {
        object-position: center top;
    }
}

@media (max-width: 360px) {
    .hero-img-slide {
        object-position: center top;
    }
}
</style>

<!-- Category Selection Section -->
<section class="category-selection">
    <div class="container">
        <div class="select-concern">
            <span class="filter-icon">🔍</span>
            <span class="select-label">SELECT CONCERN:</span>
            <div class="category-pills">
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
                <a href="Muscle Builder.php" class="pill">
                    <img src="assets/muscle.png" alt="Muscle Builder" class="category-icon">
                    <span>Muscle Builder</span>
                </a>
                <a href="Health and Beautyr.php" class="pill">
                    <img src="assets/beauty.jpg" alt="Health and Beauty" class="category-icon">
                    <span>Health and Beauty</span>
                </a>
                <a href="Tablets.php" class="pill">
                    <img src="assets/tab.jpg" alt="Tablets" class="category-icon">
                    <span>Tablets</span>
                </a>
            </div>
        </div>
    </div>
</section>



<!-- Best Selling Products -->
<section class="best-selling-products">
    <div class="container">
        <h2 class="section-title serif">BEST SELLING PRODUCTS</h2>

        <?php if (!empty($bestSellerProducts)): ?>
            <div class="best-seller-grid">
                <?php foreach ($bestSellerProducts as $product): ?>
                    <div class="product-card" onclick="window.location.href='product-detail.php?id=<?php echo $product['product_id']; ?>'">
                        <div class="product-image-container">
                            <?php
                            $imageUrl = $product['image_url'] ?? '';
                            $imagePath = $imageUrl;
                            ?>

                            <?php if (!empty($imageUrl) && file_exists($imagePath)): ?>
                                <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="product-image">
                            <?php else: ?>
                                <div class="product-image no-image">
                                    <i class="fas fa-image"></i>
                                    <span>No Image</span>
                                </div>
                            <?php endif; ?>

                            <div class="best-seller-badge">
                                <i class="fas fa-star"></i>
                                Best Seller
                            </div>
                        </div>

                        <div class="product-info">
                            <div class="product-category">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Product'); ?>
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

                            <div class="product-actions">
                                <button class="view-details-btn">View Details</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="view-all-container">
                <a href="best-sellers.php" class="view-all-btn">
                    <i class="fas fa-star"></i> View All Best Sellers
                </a>
            </div>

        <?php else: ?>
            <div class="no-best-sellers">
                <div class="no-products-message">
                    <i class="fas fa-star"></i>
                    <h3>No Best Sellers Yet</h3>
                    <p>We're working on curating our best products. Check back soon!</p>
                    <a href="products.php" class="browse-products-btn">
                        <i class="fas fa-shopping-bag"></i> Browse All Products
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Featured Products -->
<section class="featured-products">
    <div class="container">
        <h2 class="section-title serif">FEATURED PRODUCTS</h2>
        <p class="section-subtitle">Handpicked premium supplements for your wellness journey</p>

        <?php if (!empty($featuredProducts)): ?>
            <div class="featured-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="featured-card">
                        <div class="featured-badge">
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
                                $basePrice = $product['min_price'] ?? $product['price'];
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

            <div class="view-all-container">
                <a href="featured-products.php" class="view-all-btn featured-view-all">
                    <i class="fas fa-gem"></i> View All Featured Products
                </a>
            </div>

        <?php else: ?>
            <div class="no-featured">
                <div class="no-products-message">
                    <i class="fas fa-gem"></i>
                    <h3>No Featured Products Yet</h3>
                    <p>We're curating our premium product selection. Check back soon!</p>
                    <a href="products.php" class="browse-products-btn">
                        <i class="fas fa-shopping-bag"></i> Browse All Products
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Featured Products Section Styles */
.featured-products {
    padding: 4rem 0;
    background: #f8f9fa;
    color: #333;
}

.featured-products .section-title {
    color: #333;
    text-shadow: none;
}

.section-subtitle {
    text-align: center;
    font-size: 1.1rem;
    margin-bottom: 3rem;
    color: #666;
    font-style: italic;
}

.featured-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 3rem;
    margin-top: 2rem;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 2rem;
}

.featured-card {
    background: white;
    overflow: visible;
    transition: all 0.3s ease;
    position: relative;
    text-align: center;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
}

.featured-card:hover {
    transform: translateY(-5px);
}

.featured-badge {
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
}

.featured-card .product-image {
    width: 100%;
    height: 420px;
    object-fit: contain;
    object-position: center;
    background: white;
    transition: transform 0.3s ease;
    margin-bottom: 1rem;
    padding: 2rem;
    box-sizing: border-box;
    border-radius: 8px;
}

.featured-card:hover .product-image {
    transform: scale(1.02);
}

.featured-card .product-info {
    padding: 0 1rem 1rem;
    text-align: left;
}

.featured-card .product-brand {
    color: #666;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.3rem;
}

.featured-card .product-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.8rem;
}

.featured-card .rating-stars {
    color: #ffc107;
    font-size: 0.9rem;
}

.featured-card .rating-number {
    color: #666;
    font-size: 0.9rem;
    font-weight: 500;
}

.featured-card .product-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.featured-card .product-price {
    margin-bottom: 1.5rem;
}

.featured-card .price-current {
    font-size: 1.2rem;
    font-weight: 700;
    color: #000000ff;
    margin-right: 0.5rem;
}

.featured-card .price-original {
    font-size: 1rem;
    color: #999;
    text-decoration: line-through;
}

.featured-card .view-product-btn {
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

.featured-card .view-product-btn:hover {
    background: #222;
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

.featured-view-all {
    background: #2c3e50;
    color: white;
    border: 2px solid #2c3e50;
}

.featured-view-all:hover {
    background: transparent;
    color: #2c3e50;
    border-color: #2c3e50;
}

.no-featured {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
}

.no-featured .no-products-message i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.no-featured .browse-products-btn {
    background: #2c3e50;
    color: white;
    border: 2px solid #2c3e50;
}

.no-featured .browse-products-btn:hover {
    background: transparent;
    color: #2c3e50;
    border-color: #2c3e50;
}

/* Best Seller Section Styles */
.best-selling-products {
    padding: 4rem 0;
    background: #f8f9fa;
    border-bottom: 1px solid #999999;
}

.best-seller-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-top: 2rem;
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 1rem;
}

.product-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    border: none;
    text-align: center;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.product-image-container {
    position: relative;
    width: 100%;
    height: 260px;
    overflow: hidden;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    border-bottom: none;
}

.product-image {
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: all 0.3s ease;
}

.product-card:hover .product-image {
    transform: scale(1.02);
}

.product-image.no-image {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #666;
    font-size: 1rem;
}

.product-image.no-image i {
    font-size: 3rem;
    margin-bottom: 0.5rem;
}

.best-seller-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: #ffc107;
    color: #000;
    padding: -0.6rem 3.8rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    z-index: 3;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}

.best-seller-badge i {
    margin-right: 0.3rem;
    font-size: 0.6rem;
}

.product-info {
    padding: 1rem;
    text-align: left;
}

.product-category {
    color: #666;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.3rem;
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

.product-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.product-price {
    margin-bottom: 1.5rem;
}

.price-current {
    font-size: 1.2rem;
    font-weight: 700;
    color: #000000ff;
    margin-right: 0.5rem;
}

.price-original {
    font-size: 1rem;
    color: #999;
    text-decoration: line-through;
}

.product-actions {
    text-align: center;
    margin-top: auto;
}

.view-details-btn {
    width: 100%;
    padding: 0.85rem 1rem;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: #111;
    color: white;
}

.view-details-btn:hover {
    background: #222;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.view-all-container {
    text-align: center;
    margin-top: 3rem;
}

.view-all-btn {
    display: inline-block;
    background: linear-gradient(135deg, #333 0%, #555 100%);
    color: white;
    padding: 1rem 2rem;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.view-all-btn:hover {
    background: linear-gradient(135deg, #555 0%, #333 100%);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    color: white;
    text-decoration: none;
}

.no-best-sellers {
    text-align: center;
    padding: 4rem 2rem;
}

.no-products-message {
    max-width: 500px;
    margin: 0 auto;
}

.no-products-message i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.no-products-message h3 {
    font-size: 1.8rem;
    color: #666;
    margin-bottom: 1rem;
}

.no-products-message p {
    color: #888;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

.browse-products-btn {
    display: inline-block;
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    color: white;
    padding: 1rem 2rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.browse-products-btn:hover {
    background: linear-gradient(135deg, #f7931e 0%, #ff6b35 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
    color: white;
    text-decoration: none;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .best-seller-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.8rem;
        max-width: 800px;
    }

    .featured-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        max-width: 800px;
    }
}

@media (max-width: 768px) {
    .best-seller-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.8rem;
        padding: 0 0.5rem;
        max-width: 600px;
    }

    .featured-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        padding: 0 0.5rem;
        max-width: 600px;
    }

    .product-image-container {
        height: 150px;
        padding: 0.8rem;
    }

    .product-image {
        max-width: 85%;
        max-height: 85%;
    }

    .product-info {
        padding: 0.75rem;
    }

    .product-name {
        font-size: 0.95rem;
        min-height: 2.2rem;
    }

    .product-description {
        font-size: 0.7rem;
        min-height: 2.2rem;
    }

    .product-price {
        font-size: 1.1rem;
    }

    .view-product-btn {
        padding: 0.5rem 0.8rem;
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .best-seller-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        max-width: 300px;
    }

    .featured-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        max-width: 300px;
    }

    .product-image-container {
        height: 160px;
        padding: 1rem;
    }

    .product-image {
        max-width: 80%;
        max-height: 80%;
    }

    .product-name {
        font-size: 1rem;
    }

    .product-description {
        font-size: 0.75rem;
    }
}
</style>

<!-- Why Choose Us -->
<section class="why-choose-us">
    <div class="container">
        <h2 class="section-title serif">WHY CHOOSE ALPHA NUTRITION</h2>
        <div class="features-grid">
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <i class="fas fa-award"></i>
                </div>
                <h3 class="serif">PREMIUM QUALITY</h3>
                <p>Rigorously third-party tested supplements crafted with the finest ingredients and uncompromising manufacturing standards.</p>
            </div>
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <h3 class="serif">FAST SHIPPING</h3>
                <p>Complimentary expedited shipping on orders exceeding $50 with premium express delivery options nationwide.</p>
            </div>
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <h3 class="serif">EASY RETURNS</h3>
                <p>30-day satisfaction guarantee with effortless returns and exchanges on all premium products.</p>
            </div>
            <div class="feature-card animate-on-scroll">
                <div class="feature-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <h3 class="serif">EXPERT SUPPORT</h3>
                <p>Receive personalized guidance from our elite team of certified nutritionists and fitness professionals.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="testimonials">
    <div class="container">
        <h2 class="section-title serif">WHAT OUR CLIENTS SAY</h2>
        <div class="testimonials-slider">
            <div class="testimonial-card">
                <div class="testimonial-text">
                    "Alpha Nutrition's supplements have revolutionized my training regimen. The exceptional quality and remarkable results are unparalleled in the industry. An absolute game-changer."
                </div>
                <div class="testimonial-author serif">Michael Johnson</div>
                <div class="testimonial-role">Elite Fitness Trainer</div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-text">
                    "As a competitive athlete, I demand nothing but the best. Alpha Nutrition delivers premium, clean, and incredibly effective products that elevate my performance to new heights."
                </div>
                <div class="testimonial-author serif">Sarah Williams</div>
                <div class="testimonial-role">Professional CrossFit Athlete</div>
            </div>
            <div class="testimonial-card">
                <div class="testimonial-text">
                    "I exclusively recommend Alpha Nutrition to my discerning clientele. Their unwavering commitment to quality and transparency sets the gold standard in our industry."
                </div>
                <div class="testimonial-author serif">Dr. Emily Chen</div>
                <div class="testimonial-role">Clinical Nutritionist</div>
            </div>
        </div>
    </div>
</section>

<!-- Certifications -->
<section class="certifications">
    <div class="container">
        <div class="cert-grid">
            <div class="cert-item animate-on-scroll">
                <div class="cert-icon">
                    <img src="assets/Bpa free black.png" alt="BPA Free" style="width: 150px; height: auto;">
                </div>
            </div>
            <div class="cert-item animate-on-scroll">
                <div class="cert-icon">
                    <img src="assets/fssai black.png" alt="FSSAI Approved" style="width: 150px; height: auto;">
                </div>
            </div>
            <div class="cert-item animate-on-scroll">
                <div class="cert-icon">
                    <img src="assets/Gmp black.png" alt="GMP Certified" style="width: 150px; height: auto;">
                </div>
            </div>
            <div class="cert-item animate-on-scroll">
                <div class="cert-icon">
                    <img src="assets/Halal black.png" alt="Halal Certified" style="width: 150px; height: auto;">
                </div>
            </div>
            <div class="cert-item animate-on-scroll">
                <div class="cert-icon">
                    <img src="assets/Make In India black.png" alt="Make in India" style="width: 150px; height: auto;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter">
    <div class="container">
        <h2 class="serif">JOIN OUR EXCLUSIVE NEWSLETTER</h2>
        <p>Receive privileged access to new product launches, exclusive offers, and expert wellness insights.</p>
        <form class="newsletter-form">
            <input type="email" class="newsletter-input" placeholder="Enter your email address" required>
            <button type="submit" class="newsletter-btn">Subscribe</button>
        </form>
    </div>
</section>

<!-- Footer -->
<?php include 'includes/footer.php'; ?>
<script>
// Hero slider functionality
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.hero-slide');
    const leftBtn = document.getElementById('hero-slider-left');
    const rightBtn = document.getElementById('hero-slider-right');

    let currentSlide = 0;
    const totalSlides = slides.length;

    // Only initialize slider if there are multiple slides
    if (totalSlides > 1) {
        function showSlide(index) {
            // Remove active class from all slides
            slides.forEach(slide => slide.classList.remove('active'));

            // Add active class to current slide
            slides[index].classList.add('active');

            currentSlide = index;
        }

        function nextSlide() {
            const next = (currentSlide + 1) % totalSlides;
            showSlide(next);
        }

        function prevSlide() {
            const prev = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(prev);
        }

        // Event listeners (only if buttons exist)
        if (leftBtn && rightBtn) {
            leftBtn.addEventListener('click', prevSlide);
            rightBtn.addEventListener('click', nextSlide);

            // Auto-play slider (starts after 5 seconds, then every 5 seconds)
            setTimeout(() => {
                setInterval(nextSlide, 5000);
            }, 5000);
        }
    }
});
</script>
