<?php
session_start();
include 'includes/header.php';
require_once 'includes/auth.php';

// Check if user is logged in
$auth = new UserAuth($pdo);
$isLoggedIn = $auth->isLoggedIn();
$currentUser = $isLoggedIn ? $auth->getCurrentUser() : null;

// Initialize variables
$wishlistItems = [];
$categories = [];
$totalItems = 0;
$message = '';
$messageType = '';

if ($isLoggedIn) {
    try {
        // Fetch user's wishlist items
        $query = "
            SELECT
                w.wishlist_id,
                w.created_at as added_at,
                p.product_id,
                p.name,
                p.price,
                p.short_description,
                sc.name as category_name,
                COALESCE(
                    (SELECT pi1.image_url FROM product_images pi1 WHERE pi1.product_id = p.product_id AND pi1.is_primary = 1 LIMIT 1),
                    (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.product_id LIMIT 1)
                ) AS image_url,
                (SELECT AVG(rating) FROM reviews WHERE product_id = p.product_id AND status = 'approved') as avg_rating,
                (SELECT COUNT(*) FROM reviews WHERE product_id = p.product_id AND status = 'approved') as review_count
            FROM wishlists w
            JOIN products p ON w.product_id = p.product_id
            LEFT JOIN sub_category sc ON p.category_id = sc.category_id
            WHERE w.user_id = ? AND p.is_active = 1
            ORDER BY w.created_at DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$currentUser['user_id']]);
        $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process wishlist items and build categories
        foreach ($wishlistItems as &$item) {
            $item['avg_rating'] = $item['avg_rating'] ? round($item['avg_rating'], 1) : 0;
            $item['review_count'] = intval($item['review_count']);
            $item['price'] = floatval($item['price']);

            // Process image URL
            if ($item['image_url']) {
                $imgFile = basename($item['image_url']);
                $item['image_url'] = 'assets/' . $imgFile;
            } else {
                $item['image_url'] = 'assets/placeholder.jpg';
            }

            // Build categories array
            $categoryName = $item['category_name'] ?: 'Uncategorized';
            if (!isset($categories[$categoryName])) {
                $categories[$categoryName] = 0;
            }
            $categories[$categoryName]++;
        }

        $totalItems = count($wishlistItems);

    } catch (Exception $e) {
        $message = 'Error loading wishlist: ' . $e->getMessage();
        $messageType = 'error';
    }
} else {
    $message = 'Please log in to view your wishlist';
    $messageType = 'info';
}
?>

<style>
    body {
      background: #f7f8fa;
      font-family: 'Roboto', Arial, sans-serif;
      margin: 0;
      min-height: 100vh;
    }
    .wishlist-header {
      background: #fff4ee;
      padding: 32px 0 18px 0;
      text-align: center;
      border-radius: 18px 18px 0 0;
      border-bottom: 1.5px solid #f0e6e6;
    }
    .wishlist-header-logo {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
    }
    .wishlist-header-logo img {
      width: 54px;
      height: 54px;
      margin-bottom: 2px;
    }
    .wishlist-header-title {
      font-size: 2.3rem;
      font-weight: 900;
      color: #ff7043;
      letter-spacing: 1px;
      margin-bottom: 0;
      line-height: 1;
    }
    .wishlist-header-sub {
      font-size: 2.1rem;
      font-weight: 900;
      color: #2874f0;
      letter-spacing: 1px;
      margin-top: -8px;
      margin-bottom: 0;
      line-height: 1;
    }
    .wishlist-main {
      display: flex;
      max-width: 1200px;
      margin: 0 auto;
      background: #fff;
      border-radius: 0 0 18px 18px;
      box-shadow: 0 4px 24px rgba(40,116,240,0.08);
      padding: 0 0 32px 0;
      min-height: 600px;
    }
    .wishlist-sidebar {
      flex: 0 0 220px;
      border-right: 1.5px solid #f0e6e6;
      padding: 32px 18px 0 32px;
      background: #fff;
    }
    .wishlist-sidebar-title {
      font-size: 1.1rem;
      font-weight: 700;
      margin-bottom: 18px;
      color: #222;
    }
    .wishlist-categories {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .wishlist-category {
      font-size: 1rem;
      color: #444;
      margin-bottom: 14px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 7px 0;
      border-radius: 6px;
      transition: background 0.2s;
      cursor: pointer;
    }
    .wishlist-category:hover {
      background: #f1f3f6;
      color: #2874f0;
    }
    .wishlist-category-count {
      background: #e3e9ff;
      color: #2874f0;
      font-size: 0.95rem;
      font-weight: 600;
      border-radius: 8px;
      padding: 2px 10px;
      margin-left: 8px;
    }
    .wishlist-content {
      flex: 1;
      padding: 32px 36px 0 36px;
    }
    .wishlist-content-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 18px;
      color: #222;
    }
    .wishlist-highlight {
      background: #ffe3d3;
      color: #ff7043;
      font-weight: 700;
      font-size: 1.05rem;
      padding: 8px 0 8px 18px;
      border-radius: 8px;
      margin-bottom: 24px;
      letter-spacing: 1px;
      border-left: 5px solid #ff7043;
      display: inline-block;
    }
    .wishlist-items-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 24px;
      margin-top: 10px;
    }
    .wishlist-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(40,116,240,0.08);
      width: 280px;
      display: flex;
      flex-direction: column;
      position: relative;
      transition: all 0.3s ease;
      border: 1.5px solid #f0e6e6;
      overflow: hidden;
    }
    .wishlist-card:hover {
      box-shadow: 0 8px 32px rgba(40,116,240,0.13);
      transform: translateY(-4px);
      border: 1.5px solid #2874f0;
    }

    .wishlist-card-actions {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 10;
    }

    .btn-remove {
      background: rgba(255, 255, 255, 0.9);
      border: none;
      border-radius: 50%;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      color: #dc3545;
    }

    .btn-remove:hover {
      background: #dc3545;
      color: white;
      transform: scale(1.1);
    }

    .wishlist-card-content {
      padding: 18px 16px 16px 16px;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .product-link {
      text-decoration: none;
      color: inherit;
    }

    .product-link:hover {
      text-decoration: none;
      color: inherit;
    }
    .wishlist-card-img {
      width: 100%;
      height: 180px;
      object-fit: contain;
      background: #f7f9fa;
      transition: transform 0.3s ease;
    }

    .wishlist-card:hover .wishlist-card-img {
      transform: scale(1.05);
    }

    .wishlist-card-rating {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin: 0.5rem 0;
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

    .wishlist-card-buttons {
      display: flex;
      gap: 0.5rem;
      margin-top: auto;
      padding-top: 1rem;
    }

    .btn-move-to-cart {
      flex: 1;
      background: #2874f0;
      color: white;
      border: none;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }

    .btn-move-to-cart:hover {
      background: #1e5bb8;
      transform: translateY(-1px);
    }

    .btn-view-details {
      flex: 1;
      background: transparent;
      color: #2874f0;
      border: 1px solid #2874f0;
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 0.85rem;
      text-decoration: none;
      text-align: center;
      transition: all 0.3s ease;
    }

    .btn-view-details:hover {
      background: #2874f0;
      color: white;
      text-decoration: none;
    }

    .wishlist-card-date {
      padding: 8px 16px;
      background: #f8f9fa;
      font-size: 0.75rem;
      color: #666;
      text-align: center;
      border-top: 1px solid #e9ecef;
    }
    .wishlist-card-title {
      font-size: 1.08rem;
      font-weight: 700;
      margin-bottom: 4px;
      color: #222;
      letter-spacing: 0.2px;
      text-align: left;
    }
    .wishlist-card-desc {
      font-size: 0.97rem;
      color: #666;
      margin-bottom: 10px;
      min-height: 32px;
    }
    .wishlist-card-price-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
    }
    .wishlist-card-price {
      font-size: 1.08rem;
      font-weight: 700;
      color: #2874f0;
    }
    .wishlist-card-tag {
      background: #e3e9ff;
      color: #2874f0;
      font-size: 0.92rem;
      font-weight: 600;
      border-radius: 8px;
      padding: 2px 10px;
      margin-left: 0;
      margin-bottom: 0;
      display: inline-block;
    }
    @media (max-width: 1100px) {
      .wishlist-main { flex-direction: column; }
      .wishlist-sidebar { border-right: none; border-bottom: 1.5px solid #f0e6e6; padding: 24px 8vw 0 8vw; }
      .wishlist-content { padding: 24px 8vw 0 8vw; }
    }
    /* Enhanced Mobile Responsive Styles for Wishlist */
    @media (max-width: 768px) {
      .wishlist-header {
        padding: 20px 0 15px 0;
        margin: 0 10px;
        border-radius: 12px 12px 0 0;
      }

      .wishlist-header-logo img {
        width: 45px;
        height: 45px;
      }

      .wishlist-header-title {
        font-size: 1.8rem;
        margin-bottom: 4px;
      }

      .wishlist-header-sub {
        font-size: 1rem;
      }

      .wishlist-main {
        flex-direction: column;
        margin: 0 10px;
        border-radius: 0 0 12px 12px;
      }

      .wishlist-sidebar {
        border-right: none;
        border-bottom: 1.5px solid #f0e6e6;
        padding: 15px 20px;
      }

      .wishlist-content {
        padding: 15px 20px;
      }

      .wishlist-stats {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
      }

      .wishlist-stats-title {
        font-size: 1.1rem;
        margin-bottom: 10px;
      }

      .wishlist-stats-grid {
        gap: 15px;
      }

      .wishlist-stat-item {
        padding: 12px;
        border-radius: 6px;
      }

      .wishlist-stat-number {
        font-size: 1.3rem;
        margin-bottom: 4px;
      }

      .wishlist-stat-label {
        font-size: 0.8rem;
      }

      .wishlist-items-grid {
        flex-direction: column;
        align-items: center;
        gap: 15px;
        padding: 0 5px;
      }

      .wishlist-card {
        width: 100%;
        max-width: 350px;
        padding: 15px;
        border-radius: 8px;
      }

      .wishlist-card-image {
        width: 80px;
        height: 80px;
        border-radius: 6px;
      }

      .wishlist-card-content {
        flex: 1;
        padding-left: 12px;
      }

      .wishlist-card-name {
        font-size: 1rem;
        margin-bottom: 6px;
        line-height: 1.3;
      }

      .wishlist-card-desc {
        font-size: 0.8rem;
        margin-bottom: 8px;
        line-height: 1.4;
      }

      .wishlist-card-meta {
        gap: 8px;
        flex-wrap: wrap;
      }

      .wishlist-card-price {
        font-size: 1.1rem;
      }

      .wishlist-card-tag {
        padding: 3px 8px;
        font-size: 0.7rem;
        border-radius: 10px;
      }
    }

    @media (max-width: 480px) {
      .wishlist-header {
        padding: 15px 0 12px 0;
        margin: 0 5px;
      }

      .wishlist-header-title {
        font-size: 1.5rem;
      }

      .wishlist-header-sub {
        font-size: 0.9rem;
      }

      .wishlist-main {
        margin: 0 5px;
      }

      .wishlist-sidebar,
      .wishlist-content {
        padding: 12px 15px;
      }

      .wishlist-stats-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
      }

      .wishlist-stat-item {
        padding: 10px;
      }

      .wishlist-stat-number {
        font-size: 1.2rem;
      }

      .wishlist-stat-label {
        font-size: 0.75rem;
      }

      .wishlist-card {
        padding: 12px;
        flex-direction: column;
        text-align: center;
      }

      .wishlist-card-image {
        width: 100px;
        height: 100px;
        margin: 0 auto 10px auto;
      }

      .wishlist-card-content {
        padding-left: 0;
        text-align: center;
      }

      .wishlist-card-name {
        font-size: 0.95rem;
        margin-bottom: 8px;
      }

      .wishlist-card-desc {
        font-size: 0.75rem;
        margin-bottom: 10px;
      }

      .wishlist-card-meta {
        justify-content: center;
        gap: 6px;
      }

      .wishlist-card-price {
        font-size: 1rem;
      }

      .wishlist-card-tag {
        padding: 2px 6px;
        font-size: 0.65rem;
      }
    }

    /* Enhanced Wishlist Styles */
    .wishlist-content-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .wishlist-actions {
      display: flex;
      gap: 0.75rem;
    }

    .btn-share, .btn-move-all {
      background: linear-gradient(135deg, #2874f0, #1e5bb8);
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 8px;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 500;
    }

    .btn-share:hover, .btn-move-all:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(40, 116, 240, 0.3);
    }

    .btn-move-all {
      background: linear-gradient(135deg, #28a745, #1e7e34);
    }

    .btn-move-all:hover {
      box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .wishlist-message {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .wishlist-message.error {
      background: #f8d7da;
      border-color: #f5c6cb;
      color: #721c24;
    }

    .wishlist-message.info {
      background: #d1ecf1;
      border-color: #bee5eb;
      color: #0c5460;
    }

    .login-link {
      color: #2874f0;
      text-decoration: none;
      font-weight: 600;
      margin-left: 0.5rem;
    }

    .login-link:hover {
      text-decoration: underline;
    }

    .empty-wishlist {
      text-align: center;
      padding: 3rem 2rem;
      color: #666;
    }

    .empty-wishlist i {
      font-size: 4rem;
      color: #ddd;
      margin-bottom: 1rem;
    }

    .empty-wishlist h3 {
      font-size: 1.5rem;
      margin-bottom: 0.5rem;
      color: #333;
    }

    .empty-wishlist p {
      font-size: 1rem;
      margin-bottom: 1.5rem;
    }

    .btn-browse {
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

    .btn-browse:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(40, 116, 240, 0.3);
      text-decoration: none;
      color: white;
    }
</style>
<div class="wishlist-header">
  <div class="wishlist-header-logo">
    <img src="assets/Alpha-Logo.png" alt="Logo">
    <div class="wishlist-header-title">SHOPPING</div>
    <div class="wishlist-header-sub">WISHLIST</div>
  </div>
</div>
<div class="wishlist-main">
  <div class="wishlist-sidebar">
    <div class="wishlist-sidebar-title">Categories</div>
    <ul class="wishlist-categories">
      <?php foreach ($categories as $cat => $count): ?>
        <li class="wishlist-category">
          <span><?php echo htmlspecialchars($cat); ?></span>
          <span class="wishlist-category-count"><?php echo $count; ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="wishlist-content">
    <div class="wishlist-content-header">
      <div class="wishlist-content-title">
        <?php if ($isLoggedIn): ?>
          <?php echo htmlspecialchars($currentUser['first_name']); ?>'s Wishlist
        <?php else: ?>
          Your Wishlist
        <?php endif; ?>
      </div>
      <div class="wishlist-actions">
        <?php if ($isLoggedIn && $totalItems > 0): ?>
          <button onclick="shareWishlist()" class="btn-share">
            <i class="fas fa-share-alt"></i> Share Wishlist
          </button>
          <button onclick="moveAllToCart()" class="btn-move-all">
            <i class="fas fa-shopping-cart"></i> Move All to Cart
          </button>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($message): ?>
      <div class="wishlist-message <?php echo $messageType; ?>">
        <i class="fas fa-<?php echo $messageType === 'error' ? 'exclamation-triangle' : 'info-circle'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
        <?php if (!$isLoggedIn): ?>
          <a href="login.php" class="login-link">Login here</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($isLoggedIn && $totalItems > 0): ?>
      <div class="wishlist-highlight">
        <?php echo $totalItems; ?> item<?php echo $totalItems !== 1 ? 's' : ''; ?> in your wishlist
      </div>
      <div class="wishlist-items-grid">
        <?php foreach ($wishlistItems as $item): ?>
          <div class="wishlist-card" data-product-id="<?php echo $item['product_id']; ?>">
            <div class="wishlist-card-actions">
              <button onclick="removeFromWishlist('<?php echo $item['product_id']; ?>')" class="btn-remove" title="Remove from wishlist">
                <i class="fas fa-times"></i>
              </button>
            </div>

            <a href="product-detail.php?id=<?php echo $item['product_id']; ?>" class="product-link">
              <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                   alt="<?php echo htmlspecialchars($item['name']); ?>"
                   class="wishlist-card-img"
                   onerror="this.src='assets/placeholder.jpg'">
            </a>

            <div class="wishlist-card-content">
              <a href="product-detail.php?id=<?php echo $item['product_id']; ?>" class="product-link">
                <div class="wishlist-card-title"><?php echo htmlspecialchars($item['name']); ?></div>
              </a>

              <div class="wishlist-card-desc"><?php echo htmlspecialchars($item['short_description'] ?: 'Premium quality supplement'); ?></div>

              <?php if ($item['avg_rating'] > 0): ?>
                <div class="wishlist-card-rating">
                  <div class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="fas fa-star <?php echo $i <= $item['avg_rating'] ? 'active' : ''; ?>"></i>
                    <?php endfor; ?>
                  </div>
                  <span class="rating-text"><?php echo $item['avg_rating']; ?> (<?php echo $item['review_count']; ?>)</span>
                </div>
              <?php endif; ?>

              <div class="wishlist-card-price-row">
                <span class="wishlist-card-price">₹<?php echo number_format($item['price'], 2); ?></span>
                <span class="wishlist-card-category"><?php echo htmlspecialchars($item['category_name'] ?: 'Supplement'); ?></span>
              </div>

              <div class="wishlist-card-buttons">
                <button onclick="moveToCart('<?php echo $item['product_id']; ?>')" class="btn-move-to-cart">
                  <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
                <a href="product-detail.php?id=<?php echo $item['product_id']; ?>" class="btn-view-details">
                  View Details
                </a>
              </div>
            </div>

            <div class="wishlist-card-date">
              Added <?php echo date('M j, Y', strtotime($item['added_at'])); ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php elseif ($isLoggedIn && $totalItems === 0): ?>
      <div class="empty-wishlist">
        <i class="fas fa-heart-broken"></i>
        <h3>Your wishlist is empty</h3>
        <p>Start adding products you love to your wishlist!</p>
        <a href="products.php" class="btn-browse">
          <i class="fas fa-shopping-bag"></i> Browse Products
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
// Wishlist JavaScript Functions
function removeFromWishlist(productId) {
    if (!confirm('Are you sure you want to remove this item from your wishlist?')) {
        return;
    }

    fetch('api/wishlist-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'remove_from_wishlist',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the card from the page
            const card = document.querySelector(`[data-product-id="${productId}"]`);
            if (card) {
                card.style.transform = 'scale(0)';
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();

                    // Check if wishlist is now empty
                    const remainingCards = document.querySelectorAll('.wishlist-card');
                    if (remainingCards.length === 0) {
                        location.reload(); // Reload to show empty state
                    }
                }, 300);
            }

            // Update wishlist count in header if it exists
            updateWishlistCount(data.data.wishlist_count);

            // Show success message
            showNotification('Item removed from wishlist', 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove item from wishlist');
    });
}

function moveToCart(productId) {
    const button = event.target;
    const originalText = button.innerHTML;

    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    button.disabled = true;

    fetch('api/wishlist-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'move_to_cart',
            product_id: productId,
            quantity: 1,
            remove_from_wishlist: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count in header
            const cartCount = document.querySelector('.cart-count');
            if (cartCount && data.data.cart_count !== undefined) {
                cartCount.textContent = data.data.cart_count;
                cartCount.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    cartCount.style.transform = 'scale(1)';
                }, 300);
            }

            // Remove the card from wishlist
            const card = document.querySelector(`[data-product-id="${productId}"]`);
            if (card) {
                card.style.transform = 'scale(0)';
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();

                    // Check if wishlist is now empty
                    const remainingCards = document.querySelectorAll('.wishlist-card');
                    if (remainingCards.length === 0) {
                        location.reload(); // Reload to show empty state
                    }
                }, 300);
            }

            // Update wishlist count
            updateWishlistCount(data.data.wishlist_count);

            // Show success message
            showNotification(`${data.data.product_name} moved to cart!`, 'success');
        } else {
            button.innerHTML = originalText;
            button.disabled = false;
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.innerHTML = originalText;
        button.disabled = false;
        alert('Failed to move item to cart');
    });
}

function shareWishlist() {
    fetch('api/wishlist-sharing.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'create_share_link'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show share modal or copy to clipboard
            const shareUrl = data.data.share_url;

            // Try to copy to clipboard
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(() => {
                    showNotification('Share link copied to clipboard!', 'success');
                }).catch(() => {
                    showShareModal(shareUrl);
                });
            } else {
                showShareModal(shareUrl);
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create share link');
    });
}

function showShareModal(shareUrl) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
    `;

    modal.innerHTML = `
        <div style="
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        ">
            <h3 style="margin-bottom: 1rem;">Share Your Wishlist</h3>
            <p style="margin-bottom: 1rem; color: #666;">Copy this link to share your wishlist with friends:</p>
            <input type="text" value="${shareUrl}" readonly style="
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 6px;
                margin-bottom: 1rem;
                font-size: 0.9rem;
            " onclick="this.select()">
            <div style="display: flex; gap: 0.5rem; justify-content: center;">
                <button onclick="copyShareLink('${shareUrl}')" style="
                    background: #2874f0;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 6px;
                    cursor: pointer;
                ">Copy Link</button>
                <button onclick="this.closest('div').remove()" style="
                    background: #6c757d;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 6px;
                    cursor: pointer;
                ">Close</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Close on background click
    modal.onclick = function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    };
}

function copyShareLink(url) {
    const input = document.createElement('input');
    input.value = url;
    document.body.appendChild(input);
    input.select();
    document.execCommand('copy');
    document.body.removeChild(input);

    showNotification('Link copied to clipboard!', 'success');
    document.querySelector('[style*="position: fixed"]').remove();
}

function moveAllToCart() {
    if (!confirm('Move all items from wishlist to cart?')) {
        return;
    }

    const productIds = Array.from(document.querySelectorAll('.wishlist-card')).map(card =>
        card.getAttribute('data-product-id')
    );

    if (productIds.length === 0) {
        return;
    }

    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Moving...';
    button.disabled = true;

    // Move items one by one
    let completed = 0;
    let errors = 0;

    productIds.forEach(productId => {
        fetch('api/wishlist-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'move_to_cart',
                product_id: productId,
                quantity: 1,
                remove_from_wishlist: true
            })
        })
        .then(response => response.json())
        .then(data => {
            completed++;
            if (!data.success) {
                errors++;
            }

            if (completed === productIds.length) {
                // All requests completed
                if (errors === 0) {
                    showNotification('All items moved to cart successfully!', 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(`${productIds.length - errors} items moved, ${errors} failed`, 'warning');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                }
            }
        })
        .catch(error => {
            completed++;
            errors++;
            console.error('Error:', error);

            if (completed === productIds.length) {
                button.innerHTML = originalText;
                button.disabled = false;
                showNotification(`Failed to move some items. ${errors} errors occurred.`, 'error');
            }
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

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : type === 'warning' ? '#ffc107' : '#17a2b8'};
        color: ${type === 'warning' ? '#000' : '#fff'};
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
