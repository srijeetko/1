<?php
session_start();
include 'includes/header.php';

// Get share token from URL
$shareToken = $_GET['token'] ?? '';
$wishlistData = null;
$error = '';

if (empty($shareToken)) {
    $error = 'Invalid share link';
} else {
    // Fetch shared wishlist data
    $apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
              '://' . $_SERVER['HTTP_HOST'] . 
              dirname($_SERVER['REQUEST_URI']) . 
              '/api/wishlist-sharing.php?action=get_shared_wishlist&token=' . urlencode($shareToken);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        $result = json_decode($response, true);
        if ($result && $result['success']) {
            $wishlistData = $result['data'];
        } else {
            $error = $result['message'] ?? 'Failed to load shared wishlist';
        }
    } else {
        $error = 'Failed to connect to wishlist service';
    }
}
?>

<style>
/* Shared Wishlist Styles */
.shared-wishlist-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.shared-wishlist-header {
    background: linear-gradient(135deg, #2874f0, #1e5bb8);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(40, 116, 240, 0.2);
}

.shared-wishlist-header h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
}

.shared-wishlist-header p {
    margin: 0;
    font-size: 1.1rem;
    opacity: 0.9;
}

.wishlist-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 1rem;
}

.stat-item {
    text-align: center;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

.error-message {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    margin: 2rem auto;
    max-width: 600px;
}

.error-message i {
    font-size: 2rem;
    margin-bottom: 1rem;
    display: block;
}

.shared-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.shared-item-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.shared-item-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.shared-item-image {
    width: 100%;
    height: 200px;
    object-fit: contain;
    background: #f8f9fa;
}

.shared-item-content {
    padding: 1.5rem;
}

.shared-item-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
}

.shared-item-description {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.shared-item-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2874f0;
    margin-bottom: 1rem;
}

.shared-item-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.stars {
    display: flex;
    gap: 2px;
}

.stars .fa-star {
    color: #ddd;
    font-size: 0.9rem;
}

.stars .fa-star.active {
    color: #ffc107;
}

.rating-text {
    font-size: 0.85rem;
    color: #666;
}

.shared-item-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-add-to-cart {
    flex: 1;
    background: #2874f0;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-add-to-cart:hover {
    background: #1e5bb8;
    transform: translateY(-1px);
}

.btn-view-product {
    flex: 1;
    background: transparent;
    color: #2874f0;
    border: 1px solid #2874f0;
    padding: 10px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    transition: all 0.3s ease;
}

.btn-view-product:hover {
    background: #2874f0;
    color: white;
    text-decoration: none;
}

.empty-shared-wishlist {
    text-align: center;
    padding: 3rem 2rem;
    color: #666;
}

.empty-shared-wishlist i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 1rem;
}

.back-to-products {
    text-align: center;
    margin-top: 2rem;
}

.btn-back {
    background: linear-gradient(135deg, #2874f0, #1e5bb8);
    color: white;
    text-decoration: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-back:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 116, 240, 0.3);
    text-decoration: none;
    color: white;
}

@media (max-width: 768px) {
    .shared-items-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .wishlist-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .shared-wishlist-header h1 {
        font-size: 1.5rem;
    }
}
</style>

<div class="shared-wishlist-container">
    <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Oops! Something went wrong</h3>
            <p><?php echo htmlspecialchars($error); ?></p>
            <div class="back-to-products">
                <a href="products.php" class="btn-back">
                    <i class="fas fa-shopping-bag"></i> Browse Products
                </a>
            </div>
        </div>
    <?php elseif ($wishlistData): ?>
        <div class="shared-wishlist-header">
            <h1><i class="fas fa-heart"></i> <?php echo htmlspecialchars($wishlistData['owner_name']); ?>'s Wishlist</h1>
            <p>Discover amazing products curated by <?php echo htmlspecialchars(explode(' ', $wishlistData['owner_name'])[0]); ?></p>
            
            <div class="wishlist-stats">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $wishlistData['total_items']; ?></span>
                    <span class="stat-label">Products</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?php echo date('M j, Y', strtotime($wishlistData['shared_at'])); ?></span>
                    <span class="stat-label">Shared</span>
                </div>
            </div>
        </div>

        <?php if (empty($wishlistData['items'])): ?>
            <div class="empty-shared-wishlist">
                <i class="fas fa-heart-broken"></i>
                <h3>This wishlist is empty</h3>
                <p>It looks like <?php echo htmlspecialchars(explode(' ', $wishlistData['owner_name'])[0]); ?> hasn't added any products yet.</p>
            </div>
        <?php else: ?>
            <div class="shared-items-grid">
                <?php foreach ($wishlistData['items'] as $item): ?>
                    <div class="shared-item-card">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="shared-item-image"
                             onerror="this.src='assets/placeholder.jpg'">
                        
                        <div class="shared-item-content">
                            <h3 class="shared-item-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                            
                            <?php if ($item['short_description']): ?>
                                <p class="shared-item-description"><?php echo htmlspecialchars($item['short_description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="shared-item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                            
                            <?php if ($item['avg_rating'] > 0): ?>
                                <div class="shared-item-rating">
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $item['avg_rating'] ? 'active' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-text"><?php echo $item['avg_rating']; ?> (<?php echo $item['review_count']; ?>)</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="shared-item-actions">
                                <button onclick="addToCartFromShared('<?php echo $item['product_id']; ?>')" class="btn-add-to-cart">
                                    <i class="fas fa-shopping-cart"></i> Add to Cart
                                </button>
                                <a href="product-detail.php?id=<?php echo $item['product_id']; ?>" class="btn-view-product">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="back-to-products">
            <a href="products.php" class="btn-back">
                <i class="fas fa-shopping-bag"></i> Browse More Products
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
function addToCartFromShared(productId) {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    button.disabled = true;

    fetch('cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add_to_cart',
            product_id: productId,
            quantity: 1,
            variant_id: null
        })
    })
    .then(response => response.json())
    .then(data => {
        button.innerHTML = originalText;
        button.disabled = false;

        if (data.success) {
            // Update cart count in header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount && data.cart_count !== undefined) {
                cartCount.textContent = data.cart_count;
                cartCount.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    cartCount.style.transform = 'scale(1)';
                }, 300);
            }

            // Show success feedback
            button.innerHTML = '<i class="fas fa-check"></i> Added!';
            button.style.background = '#28a745';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '';
            }, 2000);

            showNotification('Product added to cart successfully!', 'success');
        } else {
            alert('Failed to add item to cart: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.innerHTML = originalText;
        button.disabled = false;
        alert('Failed to add item to cart');
    });
}

function showNotification(message, type = 'info') {
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
