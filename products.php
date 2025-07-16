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

// Get category filter
$category_id = isset($_GET['category']) ? $_GET['category'] : 'all';

// Fetch products from database with category info and variants
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

if ($category_id !== 'all') {
    $sql .= " AND p.category_id = :category_id";
}

$sql .= " GROUP BY p.product_id ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);

if ($category_id !== 'all') {
    $stmt->bindParam(':category_id', $category_id);
}
$stmt->execute();
$products = $stmt->fetchAll();
?>

    <!-- Products Section -->
    <section class="products-section">
        <div class="container">
            <h1 class="section-title serif">Our Products</h1>
              <!-- Filter Section -->
            <div class="filter-section">
                <h2 class="filter-title">Browse By Category</h2>
                <div class="filter-buttons">
                    <button class="filter-btn <?php echo $category_id === 'all' ? 'active' : ''; ?>"
                            data-category="all">
                        All Products
                    </button>
                    <?php
                    // Define specific categories to show in the order from the image
                    $displayCategories = [
                        'Gainer' => 'Gainer',
                        'Pre-Workout' => 'Pre-Workout',
                        'Supplements' => 'Supplements',
                        'Tablets' => 'Tablets'
                    ];

                    // Include category mapping to find actual category IDs
                    include_once 'includes/category-mapping.php';

                    foreach ($displayCategories as $displayName => $searchName):
                        $categoryId = getMappedCategoryId($searchName);
                        if ($categoryId) {
                            $isActive = $category_id === $categoryId;
                        } else {
                            $isActive = false;
                        }
                    ?>
                        <button class="filter-btn <?php echo $isActive ? 'active' : ''; ?>"
                                data-category="<?php echo $categoryId ? htmlspecialchars($categoryId) : 'all'; ?>">
                            <?php echo htmlspecialchars($displayName); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card" data-product-id="<?php echo htmlspecialchars($product['product_id']); ?>">
                    <a href="product-detail.php?id=<?php echo htmlspecialchars($product['product_id']); ?>" style="text-decoration: none; color: inherit;">
                        <div class="product-image-container">
                            <div class="discount-badge">
                                Save 30%
                            </div>
                            <button class="wishlist-heart" onclick="toggleProductWishlist(event, '<?php echo $product['product_id']; ?>')" title="Add to Wishlist">
                                <i class="far fa-heart"></i>
                            </button>
                            <?php if (!empty($product['image_url'])): ?>
                                <?php
                                    $imgUrl = $product['image_url'];
                                    // Always extract just the filename, then prepend assets/ (no leading slash)
                                    $imgFile = basename($imgUrl);
                                    $imgUrl = 'assets/' . $imgFile;
                                ?>
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="product-image"
                                     onerror="this.onerror=null;this.style.display='none';this.parentNode.innerHTML='<div class=\'product-image placeholder-image\'>No Image</div>';">
                            <?php else: ?>
                                <div class="product-image placeholder-image">No Image</div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="product-info">
                        <div class="product-brand">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'Brand'); ?>
                        </div>

                        <div class="product-rating-new">
                            <div class="rating-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <span class="rating-number">4.6</span>
                        </div>

                        <a href="product-detail.php?id=<?php echo htmlspecialchars($product['product_id']); ?>" style="text-decoration: none; color: inherit;">
                            <h3 class="product-title-new"><?php echo htmlspecialchars($product['name']); ?></h3>
                        </a>

                        <div class="product-price-new">
                            <?php
                            $basePrice = $product['price'] ?? 0;
                            $originalPrice = $basePrice * 1.43; // Calculate original price (30% discount)
                            ?>
                            <span class="price-current">From ₹ <?php echo number_format($basePrice, 2); ?></span>
                            <span class="price-original">₹ <?php echo number_format($originalPrice, 2); ?></span>
                        </div>

                        <div class="product-actions">
                            <div class="product-action-buttons" style="display:flex;gap:8px;margin-top:10px;">
                                <button class="add-to-cart" style="flex:1;">Add to cart</button>
                                <a href="product-detail.php?id=<?php echo htmlspecialchars($product['product_id']); ?>"
                                   class="view-details">
                                   View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>            </div>
        </div>
    </section>

    <style>
    /* Remove all borders and shadows from product cards */
    .product-card {
        border: none !important;
        box-shadow: 0 10px 15px -3px rgba(37, 102, 64, 0.1);
    }

    .product-image-container {
        border: none !important;
        border-bottom: none !important;
        position: relative;
        box-shadow: none !important;
    }

    .product-image {
        border: none !important;
        box-shadow: none !important;
        filter: none !important;
    }

    .product-card:hover .product-image {
        filter: none !important;
        box-shadow: none !important;
    }

    /* Product Card Background */
    .product-card {
        background: white !important;
    }

    /* Discount Badge Styles - Keep unchanged */
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
        box-shadow: none;
    }

    /* Wishlist Heart Button */
    .wishlist-heart {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 4;
        backdrop-filter: blur(10px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .wishlist-heart:hover {
        background: rgba(255, 255, 255, 1);
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .wishlist-heart i {
        font-size: 1.1rem;
        color: #666;
        transition: all 0.3s ease;
    }

    .wishlist-heart:hover i {
        color: #dc3545;
        transform: scale(1.1);
    }

    .wishlist-heart.in-wishlist {
        background: #dc3545;
    }

    .wishlist-heart.in-wishlist i {
        color: white;
        animation: heartBeat 0.6s ease-in-out;
    }

    .wishlist-heart.in-wishlist:hover {
        background: #c82333;
    }

    @keyframes heartBeat {
        0% { transform: scale(1); }
        25% { transform: scale(1.2); }
        50% { transform: scale(1); }
        75% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    /* Product Info Updates */
    .product-info {
        text-align: left !important;
    }

    .product-brand {
        color: #666;
        font-size: 0.9rem;
        font-weight: 500;
        margin-bottom: 0.3rem;
    }

    .product-rating-new {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
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

    .product-title-new {
        font-size: 1.1rem !important;
        font-weight: 600 !important;
        color: #333 !important;
        margin-bottom: 0.8rem !important;
        line-height: 1.3 !important;
    }

    .product-price-new {
        margin-bottom: 1rem;
    }

    .price-current {
        font-size: 1.2rem;
        font-weight: 700;
        color: #0e0d0dff;
        margin-right: 0.5rem;
    }

    .price-original {
        font-size: 1rem;
        color: #999;
        text-decoration: line-through;
    }

    /* Mobile Responsive Styles for Products Page */
    @media (max-width: 768px) {
        .products-section {
            padding: 2rem 0;
        }

        .section-title {
            font-size: 2rem;
            margin-bottom: 2rem;
        }

        .filter-section {
            margin-bottom: 2rem;
        }

        .filter-title {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .filter-buttons {
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }

        .filter-btn {
            padding: 8px 16px;
            font-size: 0.85rem;
            border-radius: 20px;
            white-space: nowrap;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            padding: 1rem;
            border-radius: 8px;
        }

        .product-image-container {
            height: 180px;
            margin-bottom: 1rem;
        }

        .product-image {
            max-width: 90%;
            max-height: 90%;
        }

        .discount-badge {
            top: 0.5rem;
            left: 0.5rem;
            padding: 0.3rem 0.6rem;
            font-size: 0.7rem;
        }

        .product-brand {
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }

        .product-title-new {
            font-size: 1rem !important;
            line-height: 1.3 !important;
            margin-bottom: 0.8rem !important;
        }

        .product-rating-new {
            margin-bottom: 0.8rem;
        }

        .rating-stars {
            font-size: 0.8rem;
        }

        .rating-number {
            font-size: 0.8rem;
        }

        .price-current {
            font-size: 1.1rem;
        }

        .price-original {
            font-size: 0.9rem;
        }

        .product-action-buttons {
            flex-direction: column;
            gap: 0.5rem;
        }

        .add-to-cart,
        .view-details {
            padding: 10px 16px;
            font-size: 0.9rem;
            border-radius: 4px;
            text-align: center;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 0 15px;
        }

        .section-title {
            font-size: 1.8rem;
        }

        .products-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .product-card {
            max-width: 100%;
            margin: 0 auto;
        }

        .filter-buttons {
            gap: 0.3rem;
        }

        .filter-btn {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize wishlist status for all products
        initializeWishlistStatus();

        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                if (this.disabled) return;

                const productCard = this.closest('.product-card');
                const productId = productCard.dataset.productId;

                // Default quantity is 1 since there's no quantity selector on product cards
                const quantity = 1;

                // No variant selection on product cards - will be handled on product detail page
                const variantId = null;

                // Add visual feedback
                const originalText = this.textContent;
                this.textContent = 'Adding...';
                this.disabled = true;
                this.style.background = '#ccc';

                // Make AJAX request to add item to cart
                fetch('cart-handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'add_to_cart',
                        product_id: productId,
                        quantity: quantity,
                        variant_id: variantId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success feedback
                        this.textContent = 'Added!';
                        this.style.background = '#28a745';
                        this.style.color = 'white';

                        // Update cart count if cart count element exists
                        const cartCount = document.querySelector('.cart-count');
                        if (cartCount && data.cart_count !== undefined) {
                            cartCount.textContent = data.cart_count;
                            cartCount.style.transform = 'scale(1.3)';
                            setTimeout(() => {
                                cartCount.style.transform = 'scale(1)';
                            }, 300);
                        }

                        // Update cart sidebar if it's open, or show preview
                        const cartSidebar = document.getElementById('cartSidebar');
                        if (cartSidebar && cartSidebar.classList.contains('active')) {
                            loadCartContents();
                        } else {
                            // Show cart preview for 3 seconds
                            showCartPreview();
                        }

                        // Reset button after 2 seconds
                        setTimeout(() => {
                            this.textContent = originalText;
                            this.disabled = false;
                            this.style.background = '';
                            this.style.color = '';
                        }, 2000);

                    } else {
                        // Error feedback
                        this.textContent = 'Error!';
                        this.style.background = '#dc3545';
                        this.style.color = 'white';

                        // Reset button after 2 seconds
                        setTimeout(() => {
                            this.textContent = originalText;
                            this.disabled = false;
                            this.style.background = '';
                            this.style.color = '';
                        }, 2000);

                        console.error('Failed to add product to cart:', data.message);
                    }
                })
                .catch(error => {
                    // Network error feedback
                    this.textContent = 'Error!';
                    this.style.background = '#dc3545';
                    this.style.color = 'white';

                    // Reset button after 2 seconds
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.disabled = false;
                        this.style.background = '';
                        this.style.color = '';
                    }, 2000);

                    console.error('Network error:', error);
                });
            });
        });
    });

    // Product Filtering functionality
    const filterBtns = document.querySelectorAll('.filter-btn');
    console.log('Found filter buttons:', filterBtns.length);

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const selectedCategory = this.getAttribute('data-category');
            console.log('Filter button clicked:', selectedCategory);

            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Update URL with selected category
            const url = new URL(window.location);
            if (selectedCategory === 'all') {
                url.searchParams.delete('category');
            } else {
                url.searchParams.set('category', selectedCategory);
            }

            console.log('Redirecting to:', url.toString());

            // Redirect to the new URL (don't use reload, use direct navigation)
            window.location.href = url.toString();
        });
    });

    // Wishlist functionality
    function toggleProductWishlist(event, productId) {
        event.preventDefault();
        event.stopPropagation();

        const button = event.currentTarget;
        const icon = button.querySelector('i');
        const isInWishlist = button.classList.contains('in-wishlist');

        // Show loading state
        button.disabled = true;
        icon.className = 'fas fa-spinner fa-spin';

        const action = isInWishlist ? 'remove_from_wishlist' : 'add_to_wishlist';

        fetch('api/wishlist-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: action,
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            button.disabled = false;

            if (data.success) {
                // Update button state
                if (isInWishlist) {
                    button.classList.remove('in-wishlist');
                    icon.className = 'far fa-heart';
                    button.title = 'Add to Wishlist';
                } else {
                    button.classList.add('in-wishlist');
                    icon.className = 'fas fa-heart';
                    button.title = 'Remove from Wishlist';
                }

                // Update wishlist count in header
                if (data.data && data.data.wishlist_count !== undefined) {
                    updateWishlistCount(data.data.wishlist_count);
                }

                // Show success message
                const message = isInWishlist ? 'Removed from wishlist!' : 'Added to wishlist!';
                showWishlistNotification(message, 'success');
            } else {
                // Reset icon
                icon.className = isInWishlist ? 'fas fa-heart' : 'far fa-heart';

                // Check if user needs to login
                if (data.message.includes('log in')) {
                    if (confirm('Please log in to add items to your wishlist. Would you like to login now?')) {
                        window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                    }
                } else {
                    showWishlistNotification(data.message, 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            button.disabled = false;
            icon.className = isInWishlist ? 'fas fa-heart' : 'far fa-heart';
            showWishlistNotification('Failed to update wishlist', 'error');
        });
    }

    function initializeWishlistStatus() {
        // Get all product IDs on the page
        const productCards = document.querySelectorAll('.product-card');
        const productIds = Array.from(productCards).map(card => card.dataset.productId);

        if (productIds.length === 0) return;

        // Check wishlist status for each product
        productIds.forEach(productId => {
            fetch(`api/wishlist-handler.php?action=check_wishlist_status&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.in_wishlist) {
                        const button = document.querySelector(`[onclick*="${productId}"]`);
                        if (button) {
                            button.classList.add('in-wishlist');
                            const icon = button.querySelector('i');
                            if (icon) {
                                icon.className = 'fas fa-heart';
                            }
                            button.title = 'Remove from Wishlist';
                        }
                    }

                    // Update wishlist count (only once)
                    if (productId === productIds[0] && data.data.wishlist_count !== undefined) {
                        updateWishlistCount(data.data.wishlist_count);
                    }
                })
                .catch(error => {
                    console.error('Error checking wishlist status:', error);
                });
        });
    }

    function updateWishlistCount(count) {
        const wishlistCount = document.querySelector('.wishlist-count');
        if (wishlistCount) {
            wishlistCount.textContent = count;
            wishlistCount.style.transform = 'scale(1.3)';
            setTimeout(() => {
                wishlistCount.style.transform = 'scale(1)';
            }, 300);
        }
    }

    function showWishlistNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            z-index: 10001;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    </script>

    <?php include 'includes/footer.php'; ?>