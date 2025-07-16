<?php
include 'includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

// Get product ID from URL parameter
$product_id = isset($_GET['id']) ? trim($_GET['id']) : null;

if (!$product_id || empty($product_id)) {
    header('Location: products.php?error=invalid_product');
    exit();
}

// Fetch basic product details (only using core products table)
$sql = "
    SELECT p.*, 
           sc.name as category_name
    FROM products p 
    LEFT JOIN sub_category sc ON p.category_id = sc.category_id
    WHERE p.product_id = :product_id AND p.is_active = 1
";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':product_id', $product_id);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php?error=product_not_found');
    exit();
}

// Include header after all potential redirects
include 'includes/header.php';

// Fetch usage steps from database
$usageSteps = [];
try {
    $usageStepsSQL = "
        SELECT step_number, step_title, step_description, step_image
        FROM product_usage_steps
        WHERE product_id = :product_id AND is_active = 1
        ORDER BY step_number ASC
    ";
    $usageStepsStmt = $pdo->prepare($usageStepsSQL);
    $usageStepsStmt->bindParam(':product_id', $product_id);
    $usageStepsStmt->execute();
    $usageSteps = $usageStepsStmt->fetchAll();
} catch (PDOException $e) {
    // product_usage_steps table might not exist, use default steps
    $usageSteps = [];
}

// Fetch product images (if table exists)
$images = [];
try {
    $imagesSql = "
        SELECT image_url, alt_text, is_primary 
        FROM product_images 
        WHERE product_id = :product_id 
        ORDER BY is_primary DESC, image_id ASC
    ";
    $imagesStmt = $pdo->prepare($imagesSql);
    $imagesStmt->bindParam(':product_id', $product_id);
    $imagesStmt->execute();
    $images = $imagesStmt->fetchAll();
} catch (PDOException $e) {
    // product_images table might not exist
    $images = [];
}

// Fetch product variants (if table exists)
$variants = [];
try {
    $variantsSql = "
        SELECT variant_id, size, color, price_modifier, stock 
        FROM product_variants 
        WHERE product_id = :product_id 
        ORDER BY price_modifier ASC
    ";
    $variantsStmt = $pdo->prepare($variantsSql);
    $variantsStmt->bindParam(':product_id', $product_id);
    $variantsStmt->execute();
    $variants = $variantsStmt->fetchAll();
} catch (PDOException $e) {
    // product_variants table might not exist
    $variants = [];
}

// Process primary image
$primaryImage = null;
if (!empty($images)) {
    foreach ($images as $image) {
        if ($image['is_primary']) {
            $primaryImage = $image;
            break;
        }
    }
    if (!$primaryImage) {
        $primaryImage = $images[0];
    }
}
?>

<style>
.product-detail-section {
    padding: 2rem 0;
    background: #fff;
}

.product-detail-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

.product-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-bottom: 3rem;
}

.product-images {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.image-gallery-container {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.vertical-thumbnails {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    flex-shrink: 0;
}

.vertical-thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    border: 2px solid #ddd;
    cursor: pointer;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    position: relative;
}

.vertical-thumbnail:hover {
    border-color: #2874f0;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(40, 116, 240, 0.2);
}

.vertical-thumbnail.active {
    border-color: #2874f0;
    box-shadow: 0 0 0 1px #2874f0;
    background: #fff;
}

.vertical-thumbnail.active::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border: 2px solid #2874f0;
    border-radius: 8px;
    pointer-events: none;
}

.vertical-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 8px;
}

.main-image {
    flex: 1;
    height: 400px;
    border-radius: 8px;
    border: 1px solid #ddd;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
}

.main-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 20px;
}

.product-info h1 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.product-category {
    color: #666;
    font-size: 1rem;
    margin-bottom: 1rem;
}

.product-price {
    display: flex;
    align-items: baseline;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.current-price {
    font-size: 2rem;
    font-weight: 700;
    color: #000;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    color: #ffa41c;
    font-size: 1.1rem;
}

.variant-selection {
    margin-bottom: 1.5rem;
}

.variant-label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: block;
}

.variant-options {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.variant-option {
    padding: 0.5rem 1rem;
    border: 2px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
    background: #fff;
}

.variant-option:hover,
.variant-option.selected {
    border-color: #2874f0;
    background: #2874f0;
    color: #fff;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.quantity-controls {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.quantity-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: #f8f9fa;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: 600;
}

.quantity-input {
    width: 60px;
    height: 40px;
    border: none;
    text-align: center;
    font-size: 1rem;
    font-weight: 600;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
}

.product-detail-section .btn-primary, 
.product-detail-section .btn-secondary {
    flex: 1;
    padding: 1rem 2rem;
    border: none;
    border-radius: 4px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.product-detail-section .btn-primary {
    background: #2874f0;
    color: #fff;
}

.product-detail-section .btn-primary:hover {
    background: #1e5bb8;
}

.product-detail-section .btn-secondary {
    background: #fff;
    color: #2874f0;
    border: 2px solid #2874f0;
}

.product-detail-section .btn-secondary:hover {
    background: #2874f0;
    color: #fff;
}

/* Simple Usage Steps */
.simple-usage-steps {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.simple-step {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: #fff;
    border-radius: 8px;
    border-left: 4px solid #2874f0;
}

.simple-step-number {
    width: 30px;
    height: 30px;
    background: #2874f0;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1rem;
    flex-shrink: 0;
}

.simple-step-text {
    color: #666;
    line-height: 1.5;
}

.simple-step-text strong {
    color: #333;
}

@media (max-width: 768px) {
    .product-detail-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .action-buttons {
        flex-direction: column;
    }

    /* Mobile layout for image gallery */
    .image-gallery-container {
        flex-direction: column;
        gap: 1rem;
    }

    .vertical-thumbnails {
        flex-direction: row;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }

    .vertical-thumbnail {
        width: 60px;
        height: 60px;
        flex-shrink: 0;
    }

    .main-image {
        height: 300px;
    }
}

@media (max-width: 480px) {
    .vertical-thumbnail {
        width: 50px;
        height: 50px;
    }

    .main-image {
        height: 250px;
    }

    .vertical-thumbnails {
        gap: 0.25rem;
    }
}
</style>

<!-- Product Detail Section -->
<section class="product-detail-section">
    <div class="product-detail-container">
        <!-- Breadcrumb -->
        <nav style="margin-bottom: 2rem;">
            <a href="index.php" style="color: #666; text-decoration: none;">Home</a>
            <span style="margin: 0 0.5rem; color: #999;">/</span>
            <a href="products.php" style="color: #666; text-decoration: none;">Products</a>
            <span style="margin: 0 0.5rem; color: #999;">/</span>
            <span style="color: #333;"><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>

        <div class="product-detail-grid">
            <!-- Product Images -->
            <div class="product-images">
                <div class="image-gallery-container">
                    <!-- Vertical Thumbnail Gallery -->
                    <?php if (count($images) > 1): ?>
                    <div class="vertical-thumbnails">
                        <?php foreach ($images as $index => $image): ?>
                            <?php
                                $imgUrl = $image['image_url'];
                                $imgFile = basename($imgUrl);
                                $imgUrl = 'assets/' . $imgFile;
                            ?>
                            <div class="vertical-thumbnail <?php echo $image['is_primary'] ? 'active' : ''; ?>"
                                 onclick="changeMainImage('<?php echo htmlspecialchars($imgUrl); ?>', this)">
                                <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                                     alt="<?php echo htmlspecialchars($image['alt_text'] ?: $product['name']); ?>"
                                     onerror="this.style.display='none';">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Main Product Image -->
                    <div class="main-image" id="mainImage">
                        <?php if ($primaryImage): ?>
                            <?php
                                $imgUrl = $primaryImage['image_url'];
                                $imgFile = basename($imgUrl);
                                $imgUrl = 'assets/' . $imgFile;
                            ?>
                            <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                                 alt="<?php echo htmlspecialchars($primaryImage['alt_text'] ?: $product['name']); ?>"
                                 onerror="this.onerror=null;this.parentNode.innerHTML='<div style=\'color:#666;font-size:1rem;\'>No Image Available</div>';">
                        <?php else: ?>
                            <div style="color: #666; font-size: 1rem;">No Image Available</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Product Information -->
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-category">
                    Category: <?php echo htmlspecialchars($product['category_name'] ?: 'General'); ?>
                </div>

                <div class="product-rating">
                    <span style="font-weight:600; color:#222;">4.5</span>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star"></i>
                    <i class="fas fa-star-half-alt"></i>
                    <span style="color: #666; margin-left: 0.5rem;">(127 reviews)</span>
                </div>

                <div class="product-price">
                    <span class="current-price" id="currentPrice">₹<?php echo number_format($product['price'], 2); ?></span>
                </div>

                <?php if (!empty($variants)): ?>
                <div class="variant-selection">
                    <label class="variant-label">Size:</label>
                    <div class="variant-options">
                        <?php foreach ($variants as $variant): ?>
                            <div class="variant-option" 
                                 data-variant-id="<?php echo $variant['variant_id']; ?>"
                                 data-price="<?php echo $product['price'] + $variant['price_modifier']; ?>"
                                 data-stock="<?php echo $variant['stock']; ?>"
                                 onclick="selectVariant(this)">
                                <?php echo htmlspecialchars($variant['size']); ?>
                                <?php if ($variant['color']): ?>
                                    - <?php echo htmlspecialchars($variant['color']); ?>
                                <?php endif; ?>
                                <br><small>₹<?php echo number_format($product['price'] + $variant['price_modifier'], 2); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="quantity-selector">
                    <label style="font-weight: 600;">Quantity:</label>
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                        <input type="number" class="quantity-input" id="quantity" value="1" min="1" max="10">
                        <button class="quantity-btn" onclick="changeQuantity(1)">+</button>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn-primary" onclick="addToCart()">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button class="btn-secondary" onclick="buyNow()">
                        <i class="fas fa-bolt"></i> Buy Now
                    </button>
                </div>

                <!-- Product Description -->
                <?php if ($product['description']): ?>
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; color: #333;">Product Description:</h4>
                    <p style="margin: 0; color: #666; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- How to Use Section -->
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; color: #333;">How to Use:</h4>
                    <div class="simple-usage-steps">
                        <?php if (!empty($usageSteps)): ?>
                            <?php foreach ($usageSteps as $step): ?>
                                <div class="simple-step">
                                    <div class="simple-step-number"><?php echo $step['step_number']; ?></div>
                                    <div class="simple-step-text">
                                        <strong><?php echo htmlspecialchars($step['step_title']); ?>:</strong>
                                        <?php echo htmlspecialchars($step['step_description']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Default steps if no database entries -->
                            <div class="simple-step">
                                <div class="simple-step-number">1</div>
                                <div class="simple-step-text">
                                    <strong>Mix with Water:</strong> Add 1 scoop (30g) to 200-250ml of cold water
                                </div>
                            </div>
                            <div class="simple-step">
                                <div class="simple-step-number">2</div>
                                <div class="simple-step-text">
                                    <strong>Shake Well:</strong> Shake vigorously for 30 seconds until completely mixed
                                </div>
                            </div>
                            <div class="simple-step">
                                <div class="simple-step-number">3</div>
                                <div class="simple-step-text">
                                    <strong>Consume Immediately:</strong> Drink immediately after mixing for best results
                                </div>
                            </div>
                            <div class="simple-step">
                                <div class="simple-step-number">4</div>
                                <div class="simple-step-text">
                                    <strong>Best Time:</strong> Take 30 minutes before workout or as directed
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Additional Info -->
                <?php if (isset($product['ingredients']) && $product['ingredients']): ?>
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 1rem; color: #333;">Ingredients:</h4>
                    <p style="margin: 0; color: #666; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($product['ingredients'])); ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
// Global variables
let selectedVariantId = null;
let currentStock = 10; // Default stock
let basePrice = <?php echo $product['price']; ?>;

// Image gallery functionality
function changeMainImage(imageSrc, thumbnail) {
    const mainImage = document.getElementById('mainImage');
    mainImage.innerHTML = `<img src="${imageSrc}" alt="Product Image" style="width: 100%; height: 100%; object-fit: contain; padding: 20px;">`;

    // Update active thumbnail (both vertical and horizontal thumbnails)
    document.querySelectorAll('.thumbnail, .vertical-thumbnail').forEach(thumb => thumb.classList.remove('active'));
    thumbnail.classList.add('active');
}

// Variant selection functionality
function selectVariant(variantElement) {
    // Remove active class from all variants
    document.querySelectorAll('.variant-option').forEach(variant => {
        variant.classList.remove('selected');
    });

    // Add active class to selected variant
    variantElement.classList.add('selected');

    // Update global variables
    selectedVariantId = variantElement.dataset.variantId;
    currentStock = parseInt(variantElement.dataset.stock);

    // Update price
    const newPrice = parseFloat(variantElement.dataset.price);
    document.getElementById('currentPrice').textContent = '₹' + newPrice.toFixed(2);

    // Update quantity max
    const quantityInput = document.getElementById('quantity');
    quantityInput.setAttribute('max', currentStock);
    if (parseInt(quantityInput.value) > currentStock) {
        quantityInput.value = currentStock;
    }

    // Update button states based on stock
    updateButtonStates();
}

// Quantity management
function changeQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let currentQuantity = parseInt(quantityInput.value);
    let newQuantity = currentQuantity + change;

    if (newQuantity < 1) newQuantity = 1;
    if (newQuantity > currentStock) newQuantity = currentStock;

    quantityInput.value = newQuantity;
}

// Update button states based on stock
function updateButtonStates() {
    const addToCartBtn = document.querySelector('.product-detail-section .btn-primary');
    const buyNowBtn = document.querySelector('.product-detail-section .btn-secondary');

    if (currentStock <= 0) {
        addToCartBtn.disabled = true;
        addToCartBtn.innerHTML = '<i class="fas fa-times"></i> Out of Stock';
        buyNowBtn.disabled = true;
        buyNowBtn.style.opacity = '0.5';
    } else {
        addToCartBtn.disabled = false;
        addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
        buyNowBtn.disabled = false;
        buyNowBtn.style.opacity = '1';
    }
}

// Add to cart functionality
function addToCart() {
    const quantity = document.getElementById('quantity').value;
    const productId = '<?php echo $product_id; ?>';

    if (currentStock <= 0) {
        alert('Sorry, this product is out of stock.');
        return;
    }

    // Show loading state
    const addToCartBtn = document.querySelector('.product-detail-section .btn-primary');
    const originalText = addToCartBtn.innerHTML;
    addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    addToCartBtn.disabled = true;

    // Make AJAX call to add item to cart
    fetch('cart-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add_to_cart',
            product_id: productId,
            quantity: parseInt(quantity),
            variant_id: null
        })
    })
    .then(response => response.json())
    .then(data => {
        addToCartBtn.innerHTML = originalText;
        addToCartBtn.disabled = false;

        if (data.success) {
            // Show success message
            alert(`Added ${quantity} item(s) to cart successfully!`);

            // Update cart count in header
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
        } else {
            alert('Failed to add item to cart: ' + data.message);
        }
    })
    .catch(error => {
        addToCartBtn.innerHTML = originalText;
        addToCartBtn.disabled = false;
        console.error('Error:', error);
        alert('Failed to add item to cart');
    });
}

// Buy now functionality
function buyNow() {
    const quantity = document.getElementById('quantity').value;
    const productId = '<?php echo $product_id; ?>';

    if (currentStock <= 0) {
        alert('Sorry, this product is out of stock.');
        return;
    }

    // Redirect to checkout or cart page
    alert(`Proceeding to checkout with ${quantity} item(s)`);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Select first variant by default if variants exist
    const firstVariant = document.querySelector('.variant-option');
    if (firstVariant) {
        selectVariant(firstVariant);
    }

    // Initialize button states
    updateButtonStates();

    // Quantity input validation
    const quantityInput = document.getElementById('quantity');
    quantityInput.addEventListener('change', function() {
        let value = parseInt(this.value);
        if (value < 1) this.value = 1;
        if (value > currentStock) this.value = currentStock;
    });
});
</script>

<?php include 'includes/footer.php'; ?>
