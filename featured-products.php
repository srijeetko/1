<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db_connection.php';

// Get featured products with their details
$query = "
    SELECT p.*, c.name as category_name,
           (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url,
           (SELECT MIN(price_modifier) FROM product_variants pv WHERE pv.product_id = p.product_id) as min_variant_price
    FROM products p
    LEFT JOIN sub_category c ON p.category_id = c.category_id
    WHERE p.is_featured = 1 AND p.is_active = 1
    ORDER BY p.sr_no ASC
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$featuredProducts = $stmt->fetchAll();

// Get total count of featured products
$countQuery = "SELECT COUNT(*) as total FROM products WHERE is_featured = 1 AND is_active = 1";
$countStmt = $pdo->query($countQuery);
$totalFeatured = $countStmt->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Products - Alpha Nutrition</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        .featured-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6rem 0 4rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .featured-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="stars" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23stars)"/></svg>');
            opacity: 0.3;
        }
        .featured-header h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }
        .featured-header .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        .discount-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #ff4757;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 3;
            box-shadow: 0 2px 8px rgba(255,71,87,0.3);
        }
        .featured-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }
        .products-grid {
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
        .discount-badge {
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
            color: #ff4444;
            margin-right: 0.5rem;
        }
        .price-original {
            font-size: 1rem;
            color: #999;
            text-decoration: line-through;
        }
        .product-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-actions {
            display: flex;
            gap: 1rem;
        }
        .btn-primary {
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
        .btn-primary:hover {
            background: #222;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        .featured-stats {
            text-align: center;
            margin: 3rem 0;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
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

        /* Responsive Design */
        @media (max-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.8rem;
                max-width: 800px;
            }
        }

        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
                padding: 0 0.5rem;
                max-width: 600px;
            }
        }

        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                max-width: 300px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="featured-header">
        <div class="container">
            <h1><i class="fas fa-gem"></i> Featured Products</h1>
            <p class="subtitle">Discover our handpicked premium supplements</p>
        </div>
    </div>

    <div class="featured-container">
        <div class="featured-stats">
            <h3><i class="fas fa-gem"></i> <?php echo $totalFeatured; ?> Featured Products</h3>
            <p>Carefully selected products that represent the best of Alpha Nutrition</p>
        </div>

        <?php if (!empty($featuredProducts)): ?>
            <div class="products-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="product-card">
                        <div class="product-image-container">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="product-image">
                            <?php else: ?>
                                <div class="product-image" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #666;">
                                    <i class="fas fa-image" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>

                            <div class="discount-badge">
                                Save 30%
                            </div>
                        </div>

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
                                $basePrice = $product['min_variant_price'] ?? $product['price'];
                                $originalPrice = $basePrice * 1.43; // Calculate original price (30% discount)
                                ?>
                                <span class="price-current">From ₹ <?php echo number_format($basePrice, 2); ?></span>
                                <span class="price-original">₹ <?php echo number_format($originalPrice, 2); ?></span>
                            </div>

                            <?php if (!empty($product['short_description'])): ?>
                                <div class="product-description">
                                    <?php echo htmlspecialchars($product['short_description']); ?>
                                </div>
                            <?php endif; ?>

                            <a href="product-detail.php?id=<?php echo $product['product_id']; ?>"
                               class="btn-primary">
                                <i class="fas fa-eye"></i> View Product
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-products">
                <i class="fas fa-gem"></i>
                <h3>No Featured Products Yet</h3>
                <p>Check back soon for our featured product selections!</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
