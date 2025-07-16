<?php
// Start session before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

// First, fetch basic product details with all available fields including extended product information
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

// Check if product exists
if (!$product) {
    header('Location: products.php?error=product_not_found');
    exit();
}

// Initialize supplement details with default values for supplement-specific fields only
$supplementDetails = [
    'serving_size' => null,
    'servings_per_container' => null,
    'calories' => null,
    'protein' => null,
    'carbs' => null,
    'fats' => null,
    'fiber' => null,
    'sodium' => null,
    'directions' => null,
    'warnings' => null,
    'weight_value' => null,
    'weight_unit' => null
];

// Try to fetch supplement details if table exists
try {
    $supplementSql = "
        SELECT * FROM supplement_details
        WHERE product_id = :product_id
    ";
    $supplementStmt = $pdo->prepare($supplementSql);
    $supplementStmt->bindParam(':product_id', $product_id);
    $supplementStmt->execute();
    $supplementData = $supplementStmt->fetch();

    if ($supplementData) {
        // Merge supplement data with defaults, only using existing columns
        foreach ($supplementDetails as $key => $defaultValue) {
            if (isset($supplementData[$key])) {
                $supplementDetails[$key] = $supplementData[$key];
            }
        }

        // For fields that exist in both tables, only use supplement data if product data is empty
        $overlappingFields = ['ingredients'];
        foreach ($overlappingFields as $field) {
            if (isset($supplementData[$field]) && !empty($supplementData[$field]) && empty($product[$field])) {
                $supplementDetails[$field] = $supplementData[$field];
            }
        }
    }
} catch (PDOException $e) {
    // supplement_details table might not exist or have different structure
    // Continue with default values
}

// Merge supplement details into product array (only supplement-specific fields)
// Ensure both variables are arrays before merging
if (is_array($product) && is_array($supplementDetails)) {
    $product = array_merge($product, $supplementDetails);
} elseif (!is_array($product)) {
    // This shouldn't happen due to the check above, but just in case
    header('Location: products.php?error=product_data_error');
    exit();
}

// Ensure product table fields are not overridden - set defaults if they don't exist
$productTableFields = ['short_description', 'long_description', 'key_benefits', 'how_to_use', 'how_to_use_images', 'ingredients'];
foreach ($productTableFields as $field) {
    if (!isset($product[$field])) {
        $product[$field] = null;
    }
}

if (!$product) {
    header('Location: products.php?error=product_not_found');
    exit();
}

// Include header after all potential redirects
include 'includes/header.php';

// Add JavaScript functions early so they're available for buttons
?>
<script>
// Review System JavaScript Functions - Defined early for button access
function openReviewModal() {
    console.log('Opening review modal...');
    const modal = document.getElementById('reviewFormModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Reset form if it exists
        const form = document.getElementById('reviewForm');
        if (form) {
            form.reset();
        }
    } else {
        console.error('Review modal not found');
    }
}

function closeReviewModal() {
    console.log('Closing review modal...');
    const modal = document.getElementById('reviewFormModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Enhanced loadProductReviews function for both containers
function loadProductReviews(productId, page = 1, limit = 10) {
    console.log('Loading reviews for product:', productId);
    const reviewsContainer = document.getElementById('reviewsContainer');
    const mainReviewsContainer = document.getElementById('mainReviewsContainer');

    // Set loading state for both containers
    const loadingHTML = `
        <div style="text-align: center; padding: 2rem; color: #6b7280;">
            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem; color: #3b82f6;"></i>
            <p style="font-size: 1.1rem; font-weight: 500;">Loading reviews...</p>
        </div>
    `;

    if (reviewsContainer) {
        reviewsContainer.innerHTML = loadingHTML;
    }
    if (mainReviewsContainer) {
        mainReviewsContainer.innerHTML = loadingHTML;
    }

    // Fetch reviews
    fetch(`api/get-reviews.php?product_id=${productId}&page=${page}&limit=${limit}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                displayReviews(result.data.reviews, result.data.stats);
                displayMainReviews(result.data.reviews, result.data.stats);
            } else {
                const errorHTML = `
                    <div style="text-align: center; padding: 2rem; color: #ef4444;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                        <p>Error loading reviews: ${result.message}</p>
                    </div>
                `;
                if (reviewsContainer) reviewsContainer.innerHTML = errorHTML;
                if (mainReviewsContainer) mainReviewsContainer.innerHTML = errorHTML;
            }
        })
        .catch(error => {
            console.error('Error loading reviews:', error);
            const errorHTML = `
                <div style="text-align: center; padding: 2rem; color: #ef4444;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Error loading reviews. Please try again later.</p>
                </div>
            `;
            if (reviewsContainer) reviewsContainer.innerHTML = errorHTML;
            if (mainReviewsContainer) mainReviewsContainer.innerHTML = errorHTML;
        });
}

// Simple displayReviews function
function displayReviews(reviews, stats) {
    const reviewsContainer = document.getElementById('reviewsContainer');
    if (!reviewsContainer) return;

    let reviewsHTML = '';

    if (reviews.length === 0) {
        reviewsHTML = `
            <div style="text-align: center; padding: 3rem 2rem; color: #666;">
                <i class="fas fa-comment-alt" style="font-size: 3rem; margin-bottom: 1rem; color: #ddd;"></i>
                <h3>No reviews yet</h3>
                <p>Be the first to review this product!</p>
            </div>
        `;
    } else {
        reviews.forEach(review => {
            const stars = Array.from({
                    length: 5
                }, (_, i) =>
                `<i class="fas fa-star" style="color: ${i < review.rating ? '#ffc107' : '#ddd'}"></i>`
            ).join('');

            reviewsHTML += `
                <div style="background: white; border: 1px solid #e1e5e9; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <div>
                            <div style="font-weight: 600; color: #333; margin-bottom: 8px;">
                                ${review.reviewer_name}
                                ${review.verified_purchase ? '<span style="background: #28a745; color: white; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; margin-left: 10px;">Verified Purchase</span>' : ''}
                            </div>
                            <div style="margin-bottom: 8px;">${stars}</div>
                        </div>
                        <div style="color: #666; font-size: 0.9rem;">
                            ${new Date(review.created_at).toLocaleDateString()}
                        </div>
                    </div>

                    <div>
                        <h4 style="font-size: 1.1rem; font-weight: 600; color: #333; margin: 0 0 10px 0;">
                            ${review.title}
                        </h4>
                        <p style="color: #555; line-height: 1.6; margin: 0;">
                            ${review.content}
                        </p>
                    </div>
                </div>
            `;
        });
    }

    reviewsContainer.innerHTML = reviewsHTML;
}

// Enhanced displayMainReviews function for the main reviews section
function displayMainReviews(reviews, stats) {
    const mainReviewsContainer = document.getElementById('mainReviewsContainer');
    if (!mainReviewsContainer) return;

    let reviewsHTML = '';

    if (reviews.length === 0) {
        reviewsHTML = `
            <div style="text-align: center; padding: 3rem 2rem; color: #6b7280; background: #f8fafc; border-radius: 8px; border: 1px dashed #d1d5db;">
                <i class="fas fa-comment-alt" style="font-size: 3rem; margin-bottom: 1rem; color: #d1d5db;"></i>
                <h3 style="color: #374151; margin-bottom: 1rem; font-size: 1.5rem; font-weight: 600;">No reviews yet</h3>
                <p style="font-size: 1.1rem; margin-bottom: 1.5rem;">Be the first to review this product!</p>
                <button onclick="openReviewModal()" style="
                    background: linear-gradient(135deg, #3b82f6, #2563eb);
                    color: white;
                    border: none;
                    padding: 12px 24px;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                ">
                    <i class="fas fa-edit"></i> Write the First Review
                </button>
            </div>
        `;
    } else {
        reviews.forEach(review => {
            const stars = Array.from({
                    length: 5
                }, (_, i) =>
                `<i class="fas fa-star" style="color: ${i < review.rating ? '#fbbf24' : '#e5e7eb'}; font-size: 1rem;"></i>`
            ).join('');

            // Parse review images if available
            let reviewImages = '';
            if (review.review_images) {
                try {
                    const images = JSON.parse(review.review_images);
                    if (images && images.images && images.images.length > 0) {
                        reviewImages = `
                            <div style="margin-top: 1rem;">
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    ${images.images.map(img => `
                                        <img src="${img}" alt="Review image" style="
                                            width: 80px;
                                            height: 80px;
                                            object-fit: cover;
                                            border-radius: 6px;
                                            border: 1px solid #e5e7eb;
                                            cursor: pointer;
                                        " onclick="openImageModal(\`${img}\`)">
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }
                } catch (e) {
                    console.error('Error parsing review images:', e);
                }
            }

            reviewsHTML += `
                <div style="
                    background: white;
                    border: 1px solid #e2e8f0;
                    border-radius: 12px;
                    padding: 1.5rem;
                    margin-bottom: 1.5rem;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
                    transition: all 0.3s ease;
                " onmouseover="this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.1)'"
                   onmouseout="this.style.boxShadow='0 2px 4px rgba(0, 0, 0, 0.05)'">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                                <div style="font-weight: 600; color: #1f2937; font-size: 1rem;">
                                    ${review.reviewer_name}
                                </div>
                                ${review.verified_purchase ? `
                                    <span style="
                                        background: linear-gradient(135deg, #10b981, #059669);
                                        color: white;
                                        font-size: 0.75rem;
                                        padding: 4px 8px;
                                        border-radius: 12px;
                                        font-weight: 600;
                                        text-transform: uppercase;
                                        letter-spacing: 0.025em;
                                    ">Verified Purchase</span>
                                ` : ''}
                            </div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                <div>${stars}</div>
                                <span style="color: #6b7280; font-size: 0.9rem; font-weight: 500;">
                                    ${review.rating}/5
                                </span>
                            </div>
                        </div>
                        <div style="color: #6b7280; font-size: 0.9rem; font-weight: 500;">
                            ${new Date(review.created_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            })}
                        </div>
                    </div>

                    <div>
                        <h4 style="font-size: 1.1rem; font-weight: 600; color: #1f2937; margin: 0 0 0.75rem 0; line-height: 1.4;">
                            ${review.title}
                        </h4>
                        <p style="color: #4b5563; line-height: 1.6; margin: 0; font-size: 0.95rem;">
                            ${review.content}
                        </p>
                        ${reviewImages}
                    </div>

                    ${review.admin_response ? `
                        <div style="
                            margin-top: 1rem;
                            padding: 1rem;
                            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
                            border-radius: 8px;
                            border-left: 4px solid #3b82f6;
                        ">
                            <div style="font-weight: 600; color: #1e40af; margin-bottom: 0.5rem; font-size: 0.9rem;">
                                <i class="fas fa-reply" style="margin-right: 0.5rem;"></i>
                                Response from Alpha Nutrition
                            </div>
                            <p style="color: #1e40af; margin: 0; font-size: 0.9rem; line-height: 1.5;">
                                ${review.admin_response}
                            </p>
                        </div>
                    ` : ''}
                </div>
            `;
        });
    }

    mainReviewsContainer.innerHTML = reviewsHTML;
}

// Image modal function for review images
function openImageModal(imageSrc) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('reviewImageModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'reviewImageModal';
        modal.className = 'image-modal';
        modal.innerHTML = `
            <div class="close-modal" onclick="closeImageModal()">&times;</div>
            <img src="" alt="Review Image">
        `;
        document.body.appendChild(modal);
    }

    // Set image source and show modal
    const img = modal.querySelector('img');
    img.src = imageSrc;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Close on background click
    modal.onclick = function(e) {
        if (e.target === modal) {
            closeImageModal();
        }
    };
}

function closeImageModal() {
    const modal = document.getElementById('reviewImageModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

console.log('Review functions defined early');

// Initialize product ID for reviews
let currentProductId = '<?php echo $product_id; ?>';

// Wishlist functionality
let isInWishlist = false;

function toggleWishlist() {
    const wishlistBtn = document.getElementById('wishlistBtn');
    const wishlistText = document.getElementById('wishlistText');
    const icon = wishlistBtn.querySelector('i');

    // Show loading state
    const originalText = wishlistText.textContent;
    wishlistText.textContent = 'Loading...';
    wishlistBtn.disabled = true;

    const action = isInWishlist ? 'remove_from_wishlist' : 'add_to_wishlist';

    fetch('api/wishlist-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: action,
                product_id: currentProductId
            })
        })
        .then(response => response.json())
        .then(data => {
            wishlistBtn.disabled = false;

            if (data.success) {
                isInWishlist = !isInWishlist;
                updateWishlistButton();

                // Update wishlist count in header
                if (data.data && data.data.wishlist_count !== undefined) {
                    updateWishlistCount(data.data.wishlist_count);
                }

                // Show success message
                const message = isInWishlist ? 'Added to wishlist!' : 'Removed from wishlist!';
                showWishlistNotification(message, 'success');
            } else {
                wishlistText.textContent = originalText;

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
            wishlistBtn.disabled = false;
            wishlistText.textContent = originalText;
            showWishlistNotification('Failed to update wishlist', 'error');
        });
}

function updateWishlistButton() {
    const wishlistBtn = document.getElementById('wishlistBtn');
    const wishlistText = document.getElementById('wishlistText');
    const icon = wishlistBtn.querySelector('i');

    if (isInWishlist) {
        wishlistBtn.classList.add('in-wishlist');
        icon.className = 'fas fa-heart';
        wishlistText.textContent = 'In Wishlist';
    } else {
        wishlistBtn.classList.remove('in-wishlist');
        icon.className = 'far fa-heart';
        wishlistText.textContent = 'Add to Wishlist';
    }
}

function checkWishlistStatus() {
    fetch(`api/wishlist-handler.php?action=check_wishlist_status&product_id=${currentProductId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                isInWishlist = data.data.in_wishlist;
                updateWishlistButton();

                // Update wishlist count in header
                if (data.data.wishlist_count !== undefined) {
                    updateWishlistCount(data.data.wishlist_count);
                }
            }
        })
        .catch(error => {
            console.error('Error checking wishlist status:', error);
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

// Handle review form submission
function handleReviewSubmission(event) {
    event.preventDefault();
    console.log('Handling review submission...');

    const form = document.getElementById('reviewForm');
    const formData = new FormData(form);

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    // Submit review
    console.log('Submitting review to API...');
    fetch('api/submit-review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text(); // Get as text first to see what we're getting
        })
        .then(text => {
            console.log('Raw response:', text);
            try {
                const result = JSON.parse(text);
                console.log('Parsed result:', result);
                if (result.success) {
                    alert('Review submitted successfully! It will be published after moderation.');
                    closeReviewModal();
                    // Reload reviews
                    loadProductReviews(currentProductId);
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text);
                alert('Server returned invalid response. Please check console for details.');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Network error occurred while submitting your review. Please try again.');
        })
        .finally(() => {
            // Reset button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
}

// Image preview functionality
let selectedFiles = [];
const maxFiles = 5;

function handleImageSelection(event) {
    const files = Array.from(event.target.files);
    selectedFiles = files.slice(0, maxFiles); // Limit to max files
    updateImagePreview();
}

function updateImagePreview() {
    const previewContainer = document.getElementById('image-preview');
    if (!previewContainer) return;

    previewContainer.innerHTML = '';

    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewDiv = document.createElement('div');
            previewDiv.className = 'image-preview';
            previewDiv.innerHTML = `
                <img src="${e.target.result}" alt="Preview ${index + 1}" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                <button type="button" onclick="removeImage(${index})" style="
                    position: absolute;
                    top: -5px;
                    right: -5px;
                    background: #dc3545;
                    color: white;
                    border: none;
                    border-radius: 50%;
                    width: 20px;
                    height: 20px;
                    font-size: 12px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">×</button>
            `;
            previewDiv.style.position = 'relative';
            previewDiv.style.display = 'inline-block';
            previewDiv.style.margin = '5px';
            previewContainer.appendChild(previewDiv);
        };
        reader.readAsDataURL(file);
    });
}

function removeImage(index) {
    selectedFiles.splice(index, 1);
    updateImagePreview();

    // Update the file input
    const fileInput = document.getElementById('review_images');
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    fileInput.files = dt.files;
}

// Load reviews when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing reviews for product:', currentProductId);
    if (currentProductId) {
        loadProductReviews(currentProductId);
    }

    // Check wishlist status
    checkWishlistStatus();

    // Attach form submission handler
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', handleReviewSubmission);
    }

    // Attach image selection handler
    const imageInput = document.getElementById('review_images');
    if (imageInput) {
        imageInput.addEventListener('change', handleImageSelection);
    }
});
</script>
<?php

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

// Fetch detailed usage instructions from database
$usageInstructions = [];
try {
    $usageInstructionsSQL = "
        SELECT instruction_type, instruction_title, instruction_content, display_order
        FROM product_usage_instructions
        WHERE product_id = :product_id AND is_active = 1
        ORDER BY display_order ASC
    ";
    $usageInstructionsStmt = $pdo->prepare($usageInstructionsSQL);
    $usageInstructionsStmt->bindParam(':product_id', $product_id);
    $usageInstructionsStmt->execute();
    $usageInstructions = $usageInstructionsStmt->fetchAll();
} catch (PDOException $e) {
    // product_usage_instructions table might not exist
    $usageInstructions = [];
}

// Fetch product images
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

// Fetch product variants
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

// Fetch related products from same category
$relatedSql = "
    SELECT p.product_id, p.name, p.price,
           COALESCE(
               (SELECT pi1.image_url FROM product_images pi1 WHERE pi1.product_id = p.product_id AND pi1.is_primary = 1 LIMIT 1),
               (SELECT pi2.image_url FROM product_images pi2 WHERE pi2.product_id = p.product_id LIMIT 1)
           ) AS image_url
    FROM products p 
    WHERE p.category_id = :category_id 
    AND p.product_id != :product_id 
    AND p.is_active = 1
    LIMIT 4
";
$relatedStmt = $pdo->prepare($relatedSql);
$relatedStmt->bindParam(':category_id', $product['category_id']);
$relatedStmt->bindParam(':product_id', $product_id);
$relatedStmt->execute();
$relatedProducts = $relatedStmt->fetchAll();

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
    gap: 1.5rem;
    align-items: flex-start;
}

.left-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    flex-shrink: 0;
    width: 90px;
}

.vertical-thumbnails {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex-shrink: 0;
    width: 90px;
}

.usage-images-sidebar {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex-shrink: 0;
    width: 90px;
}

.vertical-thumbnail {
    width: 90px;
    height: 90px;
    border-radius: 6px;
    border: 2px solid #e0e0e0;
    cursor: pointer;
    overflow: hidden;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    position: relative;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.vertical-thumbnail:hover {
    border-color: #ff6b35;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.2);
}

.vertical-thumbnail.active {
    border-color: #ff6b35;
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
    background: #fff;
    transform: translateY(-2px);
}

.vertical-thumbnail.active::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border: 2px solid #ff6b35;
    border-radius: 6px;
    pointer-events: none;
}

.vertical-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 6px;
}

.usage-thumbnail {
    width: 90px;
    height: 90px;
    border-radius: 6px;
    border: 2px solid #e0e0e0;
    cursor: pointer;
    overflow: hidden;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    position: relative;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.usage-thumbnail:hover {
    border-color: #28a745;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
}

.usage-thumbnail.active {
    border-color: #28a745;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    background: #fff;
    transform: translateY(-2px);
}

.usage-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
}

.usage-step-number {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #28a745;
    color: white;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    z-index: 2;
}

.main-image {
    flex: 1;
    height: 450px;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    overflow: hidden;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    position: relative;
}

/* Slider Navigation Buttons */
.slider-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #e0e0e0;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
    opacity: 0;
    visibility: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.main-image:hover .slider-nav {
    opacity: 1;
    visibility: visible;
}

.slider-nav:hover {
    background: #ff6b35;
    color: white;
    border-color: #ff6b35;
    transform: translateY(-50%) scale(1.1);
}

.slider-nav.prev {
    left: 15px;
}

.slider-nav.next {
    right: 15px;
}

.slider-nav i {
    font-size: 16px;
}

.main-image {
    position: relative;
}

.main-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
    cursor: zoom-in;
}

.main-image:hover img {
    transform: scale(1.02);
}

/* Flipkart-style hover zoom lens */
.zoom-lens {
    position: absolute;
    border: 2px solid #ff6b35;
    background: rgba(255, 107, 53, 0.1);
    cursor: none;
    display: none;
    pointer-events: none;
    z-index: 100;
}

/* Flipkart-style zoom result panel */
.zoom-result {
    position: absolute;
    top: 0;
    left: 100%;
    width: 400px;
    height: 450px;
    margin-left: 20px;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    display: none;
    z-index: 200;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
}

.zoom-result img {
    position: absolute;
    max-width: none;
    max-height: none;
}

/* Zoom Modal Styles */
.zoom-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    backdrop-filter: blur(5px);
}

.zoom-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.zoom-container {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    overflow: hidden;
    border-radius: 8px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 600px;
    min-height: 400px;
}

.zoom-image {
    display: block;
    max-width: 100%;
    max-height: 100%;
    width: auto;
    height: auto;
    transition: transform 0.1s ease;
    cursor: grab;
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    object-fit: contain;
}

.zoom-image:active {
    cursor: grabbing;
}

.zoom-controls {
    position: absolute;
    top: 20px;
    right: 20px;
    display: flex;
    gap: 10px;
    z-index: 10001;
}

.zoom-btn {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 18px;
    color: #333;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.zoom-btn:hover {
    background: #ff6b35;
    color: white;
    transform: scale(1.1);
}

.zoom-close {
    position: absolute;
    top: 20px;
    left: 20px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 50%;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 20px;
    color: #333;
    z-index: 10001;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.zoom-close:hover {
    background: #dc3545;
    color: white;
    transform: scale(1.1);
}

.zoom-info {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    z-index: 10001;
}

/* Legacy thumbnail styles for backward compatibility */
.thumbnail-images {
    display: flex;
    gap: 0.5rem;
    overflow-x: auto;
}

.thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 4px;
    border: 2px solid #ddd;
    cursor: pointer;
    overflow: hidden;
    flex-shrink: 0;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
}

.thumbnail.active {
    border-color: #2874f0;
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 5px;
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

.original-price {
    font-size: 1.2rem;
    color: #999;
    text-decoration: line-through;
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
    flex-wrap: nowrap;
    align-items: center;
}

.variant-option {
    padding: 0.75rem 1.5rem;
    border: 2px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    background: #fff;
    flex: 1;
    text-align: center;
    min-width: 120px;
    font-weight: 500;
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

/* Wishlist Button Styles */
.wishlist-section {
    margin-top: 1rem;
    text-align: center;
}

.btn-wishlist {
    background: #dc3545;
    border: 2px solid #dc3545;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 160px;
    justify-content: center;
    white-space: nowrap;
}

.btn-wishlist:hover {
    background: #c82333;
    border-color: #c82333;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-wishlist.in-wishlist {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.btn-wishlist.in-wishlist:hover {
    background: #218838;
    border-color: #218838;
    color: white;
}

.btn-wishlist.in-wishlist i {
    color: white;
}

.btn-wishlist i {
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.btn-wishlist:hover i {
    transform: scale(1.1);
}

.btn-wishlist.in-wishlist i {
    animation: heartBeat 0.6s ease-in-out;
}

@keyframes heartBeat {
    0% {
        transform: scale(1);
    }

    25% {
        transform: scale(1.2);
    }

    50% {
        transform: scale(1);
    }

    75% {
        transform: scale(1.1);
    }

    100% {
        transform: scale(1);
    }
}

.product-description {
    margin-bottom: 2rem;
}

.description-tabs {
    border-bottom: 1px solid #ddd;
    margin-bottom: 1rem;
}

.tab-buttons {
    display: flex;
    gap: 2rem;
}

.tab-btn {
    padding: 1rem 0;
    border: none;
    background: none;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
}

.tab-btn.active {
    color: #2874f0;
    border-bottom-color: #2874f0;
}

.tab-content {
    display: none;
    line-height: 1.6;
}

.tab-content.active {
    display: block;
}

.nutrition-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.nutrition-table th,
.nutrition-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.nutrition-table th {
    background: #f8f9fa;
    font-weight: 600;
}

/* How to Use Styles */
.how-to-use-container {
    margin: 2rem 0;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
}

.usage-steps {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.usage-step {
    display: flex;
    align-items: center;
    gap: 2rem;
    background: #fff;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.usage-step:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.step-image {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 12px;
    overflow: hidden;
    background: linear-gradient(135deg, #ff6b35 0%, #f7931e 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.step-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 12px;
}

.step-number {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 30px;
    height: 30px;
    background: #fff;
    color: #ff6b35;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.step-content {
    flex: 1;
}

.step-content h4 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #333;
    margin: 0 0 0.5rem 0;
}

.step-content p {
    font-size: 1rem;
    color: #666;
    line-height: 1.5;
    margin: 0;
}

.text-instructions {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    border-left: 4px solid #2874f0;
}

.text-instructions h4 {
    color: #333;
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.text-instructions p {
    color: #666;
    line-height: 1.6;
    margin: 0;
}

/* Customer Reviews Section Styles */
.customer-reviews-section {
    transition: all 0.3s ease;
}

.customer-reviews-section:hover {
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
}

.main-reviews-container {
    max-width: 100%;
}

.main-reviews-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.main-reviews-pagination button {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    background: white;
    color: #374151;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}

.main-reviews-pagination button:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.main-reviews-pagination button.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.main-reviews-pagination button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Review Images Modal */
.image-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.image-modal.active {
    opacity: 1;
    visibility: visible;
}

.image-modal img {
    max-width: 90%;
    max-height: 90%;
    border-radius: 8px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}

.image-modal .close-modal {
    position: absolute;
    top: 20px;
    right: 20px;
    color: white;
    font-size: 2rem;
    cursor: pointer;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.image-modal .close-modal:hover {
    background: rgba(0, 0, 0, 0.8);
}

/* Responsive styles for reviews */
@media (max-width: 768px) {
    .customer-reviews-section {
        margin: 2rem 0;
        padding: 1.5rem;
    }

    .reviews-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch !important;
    }

    .review-stats-summary {
        padding: 1rem;
    }

    .review-stats-summary>div {
        flex-direction: column;
        gap: 1rem;
    }

    .rating-breakdown {
        min-width: auto !important;
    }
}

.related-products {
    margin-top: 3rem;
}

.related-products h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.related-product {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    transition: all 0.3s;
    text-decoration: none;
    color: inherit;
}

.related-product:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.related-product img {
    width: 100%;
    height: 150px;
    object-fit: contain;
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .product-detail-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .action-buttons {
        flex-direction: column;
    }

    .tab-buttons {
        flex-wrap: wrap;
        gap: 1rem;
    }

    .usage-step {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }

    .step-image {
        width: 100px;
        height: 100px;
    }

    .how-to-use-container {
        padding: 1rem;
    }

    .usage-steps {
        gap: 1.5rem;
    }

    /* Mobile layout for image gallery */
    .image-gallery-container {
        flex-direction: column;
        gap: 1rem;
    }

    .left-sidebar {
        flex-direction: row;
        gap: 1rem;
        width: 100%;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }

    .vertical-thumbnails {
        flex-direction: row;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        scrollbar-width: thin;
        scrollbar-color: #ff6b35 #f0f0f0;
        width: auto;
    }

    .usage-images-sidebar {
        flex-direction: row;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        scrollbar-width: thin;
        scrollbar-color: #28a745 #f0f0f0;
        width: auto;
    }

    .vertical-thumbnails::-webkit-scrollbar {
        height: 4px;
    }

    .vertical-thumbnails::-webkit-scrollbar-track {
        background: #f0f0f0;
        border-radius: 2px;
    }

    .vertical-thumbnails::-webkit-scrollbar-thumb {
        background: #ff6b35;
        border-radius: 2px;
    }

    .usage-images-sidebar::-webkit-scrollbar {
        height: 4px;
    }

    .usage-images-sidebar::-webkit-scrollbar-track {
        background: #f0f0f0;
        border-radius: 2px;
    }

    .usage-images-sidebar::-webkit-scrollbar-thumb {
        background: #28a745;
        border-radius: 2px;
    }

    .vertical-thumbnail,
    .usage-thumbnail {
        width: 70px;
        height: 70px;
        flex-shrink: 0;
    }

    .main-image {
        height: 300px;
    }

    /* Hide zoom result panel on tablets and mobile */
    .zoom-result {
        display: none !important;
    }

    /* Tablet Accordion Styles */
    .product-accordion-container {
        gap: 2rem;
        max-width: 1200px;
    }

    .accordion-image-space {
        max-width: 400px;
        min-height: 350px;
    }

    .slider-nav {
        width: 35px;
        height: 35px;
    }

    .slider-nav.prev {
        left: 10px;
    }

    .slider-nav.next {
        right: 10px;
    }

    .slider-nav i {
        font-size: 14px;
    }

    /* Enhanced Mobile Styles for Product Detail */
    .product-detail-section {
        padding: 1.5rem 0;
    }

    .product-detail-container {
        padding: 0 15px;
    }

    .product-detail-grid {
        gap: 2rem;
    }

    .product-info h1 {
        font-size: 1.5rem;
        line-height: 1.3;
        margin-bottom: 1rem;
    }

    .product-price {
        font-size: 1.8rem;
        margin-bottom: 1rem;
    }

    .product-description {
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }

    .variant-selector {
        margin-bottom: 1.5rem;
    }

    .variant-option {
        padding: 8px 12px;
        font-size: 0.85rem;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        min-width: 100px;
        flex: 1;
    }

    .variant-options {
        flex-wrap: wrap;
        gap: 0.3rem;
    }

    .variant-selection > div {
        flex-direction: column;
        align-items: stretch !important;
        gap: 1rem;
    }

    .btn-wishlist {
        min-width: auto;
        padding: 0.6rem 1rem;
        font-size: 0.85rem;
    }



    .quantity-selector {
        margin-bottom: 1.5rem;
    }

    .quantity-controls {
        gap: 0.5rem;
    }

    .quantity-btn {
        padding: 8px 12px;
        font-size: 0.9rem;
    }

    .quantity-input {
        padding: 8px 12px;
        font-size: 0.9rem;
        width: 60px;
    }

    .action-buttons {
        gap: 0.8rem;
        margin-bottom: 2rem;
    }

    .btn-primary,
    .btn-secondary {
        padding: 12px 20px;
        font-size: 0.9rem;
        border-radius: 6px;
        flex: 1;
        min-height: 44px;
    }

    .tab-buttons {
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        justify-content: center;
    }

    .tab-btn {
        padding: 8px 16px;
        font-size: 0.85rem;
        border-radius: 20px;
        white-space: nowrap;
    }

    .tab-content {
        padding: 1.5rem;
    }

    .tab-content h3 {
        font-size: 1.2rem;
        margin-bottom: 1rem;
    }

    .tab-content p {
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .usage-steps {
        gap: 1.5rem;
    }

    .usage-step {
        padding: 1rem;
        border-radius: 8px;
    }

    .step-image {
        width: 80px;
        height: 80px;
    }

    .step-content h4 {
        font-size: 1rem;
        margin-bottom: 0.5rem;
    }

    .step-content p {
        font-size: 0.85rem;
        line-height: 1.5;
    }
}

@media (max-width: 480px) {

    .vertical-thumbnail,
    .usage-thumbnail {
        width: 60px;
        height: 60px;
    }

    .main-image {
        height: 280px;
    }

    /* Mobile Accordion Styles */
    .product-accordion-container {
        margin: 2rem 0;
        padding: 0 0.5rem;
        flex-direction: column;
        gap: 2rem;
    }

    .accordion-wrapper {
        max-width: 100%;
    }

    .accordion-image-space {
        max-width: 100%;
        min-height: 250px;
        order: -1;
        /* Show image above accordion on mobile */
    }

    .accordion-header {
        padding: 1rem 1.5rem;
    }

    .accordion-header h3 {
        font-size: 1rem;
    }

    .accordion-body {
        padding: 1rem 1.5rem 1.5rem 1.5rem;
        font-size: 0.9rem;
    }

    .slider-nav {
        width: 30px;
        height: 30px;
    }

    .slider-nav.prev {
        left: 8px;
    }

    .slider-nav.next {
        right: 8px;
    }

    .slider-nav i {
        font-size: 12px;
    }

    .vertical-thumbnails,
    .usage-images-sidebar {
        gap: 0.4rem;
        width: auto;
    }

    .left-sidebar {
        gap: 0.8rem;
    }

    .image-gallery-container {
        gap: 0.8rem;
    }

    .usage-step-number {
        width: 16px;
        height: 16px;
        font-size: 10px;
        top: 2px;
        right: 2px;
    }
}







.content-card {
    background: #fff;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e9ecef;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.content-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.content-card h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.content-card h3 i {
    font-size: 1.2rem;
}

.content-text {
    font-size: 1rem;
    line-height: 1.7;
    color: #333;
}

/* Card-specific styling */
.highlight-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.highlight-card h3 {
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.highlight-card h3 i {
    color: #6b7280;
}

.highlight-card .content-text {
    color: #4b5563;
}

.description-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.description-card h3 {
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.description-card h3 i {
    color: #6b7280;
}

.description-card .content-text {
    color: #4b5563;
}

.benefits-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.benefits-card h3 {
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.benefits-card h3 i {
    color: #6b7280;
}

.benefits-card .content-text {
    color: #4b5563;
}

.usage-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.usage-card h3 {
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.usage-card h3 i {
    color: #6b7280;
}

.usage-card .content-text {
    color: #4b5563;
}

.images-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.images-card h3 {
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.images-card h3 i {
    color: #6b7280;
}

.images-card .content-text {
    color: #4b5563;
}

.ingredients-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.ingredients-card h3 {
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ingredients-card h3 i {
    color: #6b7280;
}

.ingredients-card .content-text {
    color: #4b5563;
}

/* Accordion Styles */
.product-accordion-container {
    margin: 3rem 0;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
    padding: 0 1rem;
    display: flex;
    gap: 3rem;
    align-items: flex-start;
}

.accordion-wrapper {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    flex: 1;
    max-width: 600px;
}

.accordion-image-space {
    flex: 1;
    max-width: 500px;
    min-height: 400px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #dee2e6;
    position: relative;
    overflow: hidden;
}

.accordion-image-space img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 10px;
}

.accordion-image-space .placeholder-text {
    color: #6c757d;
    font-size: 1.1rem;
    text-align: center;
    opacity: 0.7;
}

.accordion-item {
    border-bottom: 1px solid #e5e7eb;
}

.accordion-item:last-child {
    border-bottom: none;
}

.accordion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fff;
    border: none;
    width: 100%;
    text-align: left;
}

.accordion-header:hover {
    background: #f9fafb;
}

.accordion-header h3 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
    flex: 1;
}

.accordion-icon {
    font-size: 1rem;
    color: #6b7280;
    transition: transform 0.3s ease;
    margin-left: 1rem;
}

.accordion-header.active .accordion-icon {
    transform: rotate(180deg);
}

.accordion-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.accordion-content.active {
    max-height: 1000px;
}

.accordion-body {
    padding: 1.5rem 2rem 2rem 2rem;
    color: #4b5563;
    line-height: 1.6;
    background: #f9fafb;
}

.faq-item {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.faq-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.nutrition-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.nutrition-card h3 {
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nutrition-card h3 i {
    color: #6b7280;
}

.nutrition-card .content-text {
    color: #4b5563;
}

.warning-card {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    border-color: #f5c6cb;
}

.warning-card h3 {
    color: #721c24;
}

.warning-card .content-text {
    color: #721c24;
}

/* Usage Steps Styling */
.usage-instructions,
.usage-directions {
    margin-bottom: 1.5rem;
}

.usage-instructions h4,
.usage-directions h4,
.usage-steps h4 {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.8rem;
    color: inherit;
}

.step-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.8);
}

.step-number {
    background: #0c5460;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.step-content h5 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: inherit;
}

.step-content p {
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.6;
}

/* Usage Images Grid */
.usage-images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.usage-image-item {
    text-align: center;
}

.usage-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    border: 2px solid rgba(255, 255, 255, 0.8);
}

.usage-image-item h5 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: inherit;
}

.usage-image-item p {
    font-size: 0.9rem;
    margin: 0;
    color: inherit;
    opacity: 0.8;
}

/* Nutrition Table */
.nutrition-table {
    width: 100%;
    margin-top: 1rem;
}

.nutrition-table table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
    overflow: hidden;
}

.nutrition-table th,
.nutrition-table td {
    padding: 0.8rem 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.8);
}

.nutrition-table th {
    background: rgba(255, 255, 255, 0.9);
    font-weight: 600;
    color: inherit;
}

.nutrition-table td {
    color: inherit;
}

.nutrition-table tr:last-child td {
    border-bottom: none;
}

/* Responsive Design */
@media (max-width: 1024px) {

    @media (max-width: 768px) {


        .content-card {
            padding: 1.5rem;
        }

        .content-card h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
        }

        .content-text {
            font-size: 0.95rem;
        }

        .step-item {
            flex-direction: column;
            text-align: center;
            gap: 0.8rem;
        }

        .step-number {
            align-self: center;
        }

        .usage-images-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .usage-image {
            height: 150px;
        }
    }

    @media (max-width: 480px) {
        .content-card {
            padding: 1rem;
        }

        .content-card h3 {
            font-size: 1.2rem;
            flex-direction: column;
            gap: 0.3rem;
            text-align: center;
        }

        .content-text {
            font-size: 0.9rem;
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
                    <!-- Left Sidebar with Thumbnails and Usage Images -->
                    <div class="left-sidebar">
                        <!-- Product Thumbnail Gallery -->
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

                        <!-- Usage Images Sidebar -->
                        <div class="usage-images-sidebar">
                            <?php if (!empty($usageSteps)): ?>
                            <?php foreach ($usageSteps as $index => $step): ?>
                            <?php if ($step['step_image']): ?>
                            <div class="usage-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                onclick="changeMainImageToUsage('<?php echo htmlspecialchars($step['step_image']); ?>', this, <?php echo $step['step_number']; ?>, '<?php echo htmlspecialchars($step['step_title'] ?? ''); ?>', '<?php echo htmlspecialchars($step['step_description'] ?? ''); ?>')"
                                title="<?php echo htmlspecialchars($step['step_title'] ?? 'Step ' . $step['step_number']); ?>">
                                <img src="<?php echo htmlspecialchars($step['step_image']); ?>"
                                    alt="Step <?php echo $step['step_number']; ?><?php echo $step['step_title'] ? ': ' . htmlspecialchars($step['step_title']) : ''; ?>"
                                    onerror="this.style.display='none';">
                                <div class="usage-step-number"><?php echo $step['step_number']; ?></div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <!-- No usage images available for this product -->
                            <div style="text-align: center; padding: 2rem; color: #666;">
                                <p>No usage images available for this product.</p>
                                <p style="font-size: 0.9rem;">Images can be added through the admin panel.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

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
                            id="mainProductImage" onclick="openZoom(this.src)"
                            onerror="this.onerror=null;this.parentNode.innerHTML='<div style=\'color:#666;font-size:1rem;\'>No Image Available</div>';">

                        <!-- Flipkart-style zoom lens -->
                        <div class="zoom-lens" id="zoomLens"></div>

                        <!-- Flipkart-style zoom result panel -->
                        <div class="zoom-result" id="zoomResult">
                            <img id="zoomResultImage" src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Zoomed view">
                        </div>

                        <?php else: ?>
                        <div style="color: #666; font-size: 1rem;">No Image Available</div>
                        <?php endif; ?>

                        <!-- Slider Navigation Buttons -->
                        <?php if (count($images) > 1 || !empty($usageSteps)): ?>
                        <div class="slider-nav prev" onclick="navigateImage('prev')" title="Previous Image">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        <div class="slider-nav next" onclick="navigateImage('next')" title="Next Image">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Zoom Modal -->
            <div id="zoomModal" class="zoom-modal">
                <button class="zoom-close" onclick="closeZoom()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="zoom-container" id="zoomContainer">
                    <img id="zoomImage" class="zoom-image" src="" alt="Zoomed Product Image">
                    <div class="zoom-controls">
                        <button class="zoom-btn" onclick="zoomIn()" title="Zoom In">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="zoom-btn" onclick="zoomOut()" title="Zoom Out">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button class="zoom-btn" onclick="resetZoom()" title="Reset Zoom">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="zoom-info">
                    Drag to pan • Shift+Drag to zoom • Scroll to zoom • Pinch to zoom • ESC to close
                </div>
            </div>

            <!-- Product Information -->
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-category">
                    Category: <?php echo htmlspecialchars($product['category_name']); ?>
                </div>

                <?php
                // Get review statistics for this product
                $reviewStatsQuery = "
                    SELECT
                        COUNT(*) as total_reviews,
                        AVG(rating) as average_rating
                    FROM reviews
                    WHERE product_id = ? AND status = 'approved'
                ";
                $reviewStatsStmt = $pdo->prepare($reviewStatsQuery);
                $reviewStatsStmt->execute([$product_id]);
                $reviewStats = $reviewStatsStmt->fetch(PDO::FETCH_ASSOC);

                $avgRating = $reviewStats['average_rating'] ? round($reviewStats['average_rating'], 1) : 0;
                $totalReviews = $reviewStats['total_reviews'] ?? 0;
                ?>

                <div class="product-rating" id="productRating">
                    <?php if ($totalReviews > 0): ?>
                    <span style="font-weight:600; color:#222;"><?php echo $avgRating; ?></span>
                    <div class="star-display" style="display: inline-block; margin: 0 8px;">
                        <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avgRating) {
                                    echo '<i class="fas fa-star" style="color: #ffc107;"></i>';
                                } elseif ($i - 0.5 <= $avgRating) {
                                    echo '<i class="fas fa-star-half-alt" style="color: #ffc107;"></i>';
                                } else {
                                    echo '<i class="far fa-star" style="color: #ddd;"></i>';
                                }
                            }
                            ?>
                    </div>
                    <span style="color: #666; margin-left: 0.5rem;">
                        (<span id="reviewCount"><?php echo $totalReviews; ?></span>
                        review<?php echo $totalReviews != 1 ? 's' : ''; ?>)
                    </span>
                    <?php else: ?>
                    <span style="color: #666;">No reviews yet</span>
                    <div class="star-display" style="display: inline-block; margin: 0 8px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="far fa-star" style="color: #ddd;"></i>
                        <?php endfor; ?>
                    </div>
                    <span style="color: #666;">Be the first to review!</span>
                    <?php endif; ?>
                </div>



                <div class="product-price">
                    <span class="current-price"
                        id="currentPrice">₹<?php echo number_format($product['price'], 2); ?></span>
                    <span class="original-price">₹<?php echo number_format($product['price'] * 1.3, 2); ?></span>
                </div>

                <?php if (!empty($variants)): ?>
                <div class="variant-selection">
                    <label class="variant-label">Size:</label>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <button id="wishlistBtn" class="btn-wishlist" onclick="toggleWishlist()" style="margin: 0;">
                            <i class="far fa-heart"></i> <span id="wishlistText">Add to Wishlist</span>
                        </button>
                        <div class="variant-options">
                            <?php foreach ($variants as $variant): ?>
                            <div class="variant-option" data-variant-id="<?php echo $variant['variant_id']; ?>"
                                data-price="<?php echo $variant['price_modifier']; ?>"
                                data-stock="<?php echo $variant['stock']; ?>" onclick="selectVariant(this)">
                                <?php echo htmlspecialchars($variant['size']); ?>
                                <?php if ($variant['color']): ?>
                                - <?php echo htmlspecialchars($variant['color']); ?>
                                <?php endif; ?>
                                
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <!-- Wishlist button for products without variants -->
                <div style="margin-bottom: 1.5rem;">
                    <button id="wishlistBtn" class="btn-wishlist" onclick="toggleWishlist()">
                        <i class="far fa-heart"></i> <span id="wishlistText">Add to Wishlist</span>
                    </button>
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



                <!-- Quick Info -->
                <?php if ($product['weight_value'] || $product['servings_per_container'] || $product['serving_size'] || $product['stock_quantity'] || $product['calories'] || $product['protein']): ?>
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 0.5rem; color: #333;">Quick Info:</h4>

                    <div
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem;">
                        <?php if ($product['weight_value']): ?>
                        <p style="margin: 0.25rem 0; color: #666;">
                            <strong>Weight:</strong>
                            <?php echo $product['weight_value'] . ' ' . ($product['weight_unit'] ?: 'g'); ?>
                        </p>
                        <?php endif; ?>

                        <?php if ($product['servings_per_container']): ?>
                        <p style="margin: 0.25rem 0; color: #666;">
                            <strong>Servings:</strong> <?php echo $product['servings_per_container']; ?> per container
                        </p>
                        <?php endif; ?>

                        <?php if ($product['serving_size']): ?>
                        <p style="margin: 0.25rem 0; color: #666;">
                            <strong>Serving Size:</strong> <?php echo htmlspecialchars($product['serving_size']); ?>
                        </p>
                        <?php endif; ?>

                        <?php if ($product['calories']): ?>
                        <p style="margin: 0.25rem 0; color: #666;">
                            <strong>Calories:</strong> <?php echo $product['calories']; ?> per serving
                        </p>
                        <?php endif; ?>

                        <?php if ($product['protein']): ?>
                        <p style="margin: 0.25rem 0; color: #666;">
                            <strong>Protein:</strong> <?php echo $product['protein']; ?>g per serving
                        </p>
                        <?php endif; ?>

                        <?php if ($product['stock_quantity']): ?>
                        <p style="margin: 0.25rem 0; color: #666;">
                            <strong>Stock:</strong> <?php echo $product['stock_quantity']; ?> units available
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Short Description -->


                <!-- Key Benefits Preview Removed - Now only shown in zig-zag layout -->

                <!-- Warnings -->
                <?php if ($product['warnings']): ?>
                <div
                    style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 0.5rem; color: #721c24;"><i class="fas fa-exclamation-triangle"></i>
                        Important Warnings:</h4>
                    <p style="margin: 0; color: #721c24; font-size: 0.9rem;">
                        <?php echo nl2br(htmlspecialchars($product['warnings'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Directions Preview -->
                <?php if ($product['directions']): ?>
                <div
                    style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    <h4 style="margin-bottom: 0.5rem; color: #0c5460;"><i class="fas fa-info-circle"></i> Usage
                        Directions:</h4>
                    <div style="margin: 0; color: #0c5460; font-size: 0.95rem; line-height: 1.5;">
                        <?php
                        // Show first 150 characters of directions as preview
                        $directions = $product['directions'];
                        if (strlen($directions) > 150) {
                            echo nl2br(htmlspecialchars(substr($directions, 0, 150))) . '... <em>(See How to Use tab for complete instructions)</em>';
                        } else {
                            echo nl2br(htmlspecialchars($directions));
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>













        <!-- Collapsible Accordion Product Information -->
        <div class="product-accordion-container">
            <div class="accordion-wrapper">

                <!-- Description Accordion -->
                <?php if ($product['long_description']): ?>
                <div class="accordion-item">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <h3>Long Description</h3>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <?php echo nl2br(htmlspecialchars($product['long_description'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Key Benefits Accordion -->
                <?php if ($product['key_benefits']): ?>
                <div class="accordion-item">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <h3>Short Description</h3>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <?php echo nl2br(htmlspecialchars($product['key_benefits'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Why Choose Accordion (using short_description) -->
                <?php if ($product['short_description']): ?>
                <div class="accordion-item">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <h3>Key Benefits</h3>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <?php echo nl2br(htmlspecialchars($product['short_description'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- How to  Use Accordion -->
                <?php if ($product['how_to_use']): ?>
                <div class="accordion-item">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <h3>How to Use</h3>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <?php echo nl2br(htmlspecialchars($product['how_to_use'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Ingredients Accordion -->
                <?php if ($product['ingredients']): ?>
                <div class="accordion-item">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <h3>Ingredients</h3>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <?php echo nl2br(htmlspecialchars($product['ingredients'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Frequently Asked Questions Accordion -->
                <div class="accordion-item">
                    <div class="accordion-header" onclick="toggleAccordion(this)">
                        <h3>Frequently Asked Questions</h3>
                        <i class="fas fa-chevron-down accordion-icon"></i>
                    </div>
                    <div class="accordion-content">
                        <div class="accordion-body">
                            <div class="faq-item">
                                <strong>Q: How should I store this product?</strong><br>
                                A: Store in a cool, dry place away from direct sunlight. Keep the container tightly
                                closed.
                            </div>
                            <div class="faq-item">
                                <strong>Q: Is this product suitable for vegetarians?</strong><br>
                                A: Please check the ingredients list for specific dietary information.
                            </div>
                            <div class="faq-item">
                                <strong>Q: How long will one container last?</strong><br>
                                A: This depends on your usage frequency. Please refer to the suggested use instructions.
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Image Space for Product Information -->
            <div class="accordion-image-space">
                <div class="placeholder-text">
                    Product Information<br>
                    Image Space
                </div>
            </div>
        </div>

        <!-- Tabbed Layout Removed - Replaced with Zig-Zag Layout Above -->
        <div class="product-description" style="display: none;">
            <div class="description-tabs">
                <div class="tab-buttons">
                    <button class="tab-btn active" onclick="showTab('description')">Description</button>
                    <?php if ($product['ingredients'] || $product['key_benefits'] || $product['weight_value'] || $product['serving_size'] || $product['servings_per_container']): ?>
                    <button class="tab-btn" onclick="showTab('details')">Details & Benefits</button>
                    <?php endif; ?>
                    <?php if ($product['directions'] || $product['how_to_use'] || !empty($usageSteps) || !empty($usageInstructions)): ?>
                    <button class="tab-btn" onclick="showTab('usage')">How to Use</button>
                    <?php endif; ?>
                    <?php if ($product['protein'] || $product['calories'] || $product['carbs'] || $product['fats'] || $product['fiber'] || $product['sodium']): ?>
                    <button class="tab-btn" onclick="showTab('nutrition')">Nutrition Facts</button>
                    <?php endif; ?>
                    <?php
                    // Check if product has how-to-use images (JSON format)
                    $hasHowToUseImages = false;
                    if (!empty($product['how_to_use_images'])) {
                        $howToUseImagesArray = json_decode($product['how_to_use_images'], true);
                        $hasHowToUseImages = !empty($howToUseImagesArray);
                    }
                    ?>
                    <?php if ($hasHowToUseImages): ?>
                    <button class="tab-btn" onclick="showTab('images')">Usage Images</button>
                    <?php endif; ?>
                    <button class="tab-btn" onclick="showTab('reviews')">
                        Reviews
                        <?php if ($totalReviews > 0): ?>
                        <span
                            style="background: #007bff; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8rem; margin-left: 5px;">
                            <?php echo $totalReviews; ?>
                        </span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>

            <div class="tab-content active" id="description">
                <h3>Product Description</h3>

                <?php if ($product['short_description']): ?>
                <div
                    style="background: #e8f5e8; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #28a745;">
                    <h4 style="margin-top: 0; color: #28a745;">Quick Overview</h4>
                    <p style="margin-bottom: 0;"><?php echo nl2br(htmlspecialchars($product['short_description'])); ?>
                    </p>
                </div>
                <?php endif; ?>

                <?php if ($product['short_description']): ?>
                <div style="margin-bottom: 1.5rem;">
                    <h4>Description</h4>
                    <p><?php echo nl2br(htmlspecialchars($product['short_description'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($product['long_description']): ?>
                <div style="margin-bottom: 1.5rem;">
                    <h4>Detailed Information</h4>
                    <p><?php echo nl2br(htmlspecialchars($product['long_description'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if (empty($product['long_description']) && empty($product['short_description'])): ?>
                <p>Detailed product description coming soon.</p>
                <?php endif; ?>


            </div>

            <?php if ($product['ingredients'] || $product['key_benefits'] || $product['weight_value'] || $product['serving_size'] || $product['servings_per_container']): ?>
            <div class="tab-content" id="details">
                <?php if ($product['key_benefits']): ?>
                <div
                    style="background: #e8f5e8; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #28a745;">
                    <h3 style="margin-top: 0; color: #28a745;">Key Benefits</h3>
                    <div style="color: #333; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($product['key_benefits'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($product['ingredients']): ?>
                <div
                    style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #dee2e6; margin-bottom: 1.5rem;">
                    <h3 style="margin-top: 0; color: #333;">Ingredients</h3>
                    <div style="color: #666; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($product['ingredients'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Additional Product Details -->
                <div class="product-specifications"
                    style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border: 1px solid #dee2e6;">
                    <h3 style="margin-top: 0; color: #333;">Product Specifications</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">

                        <?php if ($product['weight_value'] || $product['weight_unit']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #007bff;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #007bff;">Net Weight</h5>
                            <p style="margin: 0; font-weight: 600;">
                                <?php if ($product['weight_value']): ?>
                                <?php echo $product['weight_value']; ?>
                                <?php endif; ?>
                                <?php if ($product['weight_unit']): ?>
                                <?php echo htmlspecialchars($product['weight_unit']); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['serving_size']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #28a745;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #28a745;">Serving Size</h5>
                            <p style="margin: 0; font-weight: 600;">
                                <?php echo htmlspecialchars($product['serving_size']); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['servings_per_container']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #6b7280; border: 1px solid #e5e7eb;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #374151;">Servings per Container</h5>
                            <p style="margin: 0; font-weight: 600; color: #4b5563;">
                                <?php echo $product['servings_per_container']; ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['calories']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #dc3545;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #dc3545;">Calories per Serving</h5>
                            <p style="margin: 0; font-weight: 600;"><?php echo $product['calories']; ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['protein']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #17a2b8;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #17a2b8;">Protein</h5>
                            <p style="margin: 0; font-weight: 600;"><?php echo $product['protein']; ?>g per serving</p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['carbs']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #fd7e14;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #fd7e14;">Carbohydrates</h5>
                            <p style="margin: 0; font-weight: 600;"><?php echo $product['carbs']; ?>g per serving</p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['fats']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #6610f2;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #6610f2;">Total Fat</h5>
                            <p style="margin: 0; font-weight: 600;"><?php echo $product['fats']; ?>g per serving</p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['fiber']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #20c997;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #20c997;">Dietary Fiber</h5>
                            <p style="margin: 0; font-weight: 600;"><?php echo $product['fiber']; ?>g per serving</p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['sodium']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #e83e8c;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #e83e8c;">Sodium</h5>
                            <p style="margin: 0; font-weight: 600;"><?php echo $product['sodium']; ?>mg per serving</p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['category_name']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #6c757d;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #6c757d;">Category</h5>
                            <p style="margin: 0; font-weight: 600;">
                                <?php echo htmlspecialchars($product['category_name']); ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['stock_quantity']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #28a745;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #28a745;">Stock Available</h5>
                            <p style="margin: 0; font-weight: 600;"><?php echo $product['stock_quantity']; ?> units</p>
                        </div>
                        <?php endif; ?>

                        <?php if ($product['price']): ?>
                        <div
                            style="background: #fff; padding: 1rem; border-radius: 6px; border-left: 3px solid #6b7280; border: 1px solid #e5e7eb;">
                            <h5 style="margin: 0 0 0.5rem 0; color: #374151;">Base Price</h5>
                            <p style="margin: 0; font-weight: 600; color: #4b5563;">
                                ₹<?php echo number_format($product['price'], 2); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($product['directions'] || $product['how_to_use'] || !empty($usageSteps) || !empty($usageInstructions)): ?>
            <div class="tab-content" id="usage">
                <h3>How to Use</h3>

                <!-- How to Use Visual Steps -->
                <div class="how-to-use-container">
                    <div class="usage-steps">
                        <?php if (!empty($usageSteps)): ?>
                        <?php foreach ($usageSteps as $step): ?>
                        <div class="usage-step">
                            <div class="step-image">
                                <?php if ($step['step_image']): ?>
                                <img src="<?php echo htmlspecialchars($step['step_image']); ?>"
                                    alt="Step <?php echo $step['step_number']; ?>" onerror="this.style.display='none';">
                                <?php endif; ?>
                                <div class="step-number"><?php echo $step['step_number']; ?></div>
                            </div>
                            <div class="step-content">
                                <h4><?php echo htmlspecialchars($step['step_title'] ?: 'Step ' . $step['step_number']); ?>
                                </h4>
                                <?php if (!empty($step['step_description'])): ?>
                                <p><?php echo htmlspecialchars($step['step_description']); ?></p>
                                <?php else: ?>
                                <p style="color: #666; font-style: italic;">Visual step - see image for details</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <!-- No usage steps available for this product -->
                        <div
                            style="text-align: center; padding: 2rem; color: #666; background: #f8f9fa; border-radius: 8px; border: 1px dashed #dee2e6;">
                            <h4 style="color: #6c757d; margin-bottom: 1rem;">No Usage Steps Available</h4>
                            <p>Step-by-step usage instructions have not been added for this product yet.</p>
                            <p style="font-size: 0.9rem; margin-bottom: 0;">You can add usage steps through the admin
                                panel to provide detailed instructions with images.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Detailed Usage Instructions from Database -->
                <?php if (!empty($usageInstructions)): ?>
                <div class="detailed-instructions" style="margin-top: 2rem;">
                    <h4 style="color: #333; margin-bottom: 1rem;">Detailed Instructions</h4>
                    <?php
                        $instructionTypes = [
                            'dosage' => ['title' => 'Recommended Dosage', 'color' => '#28a745', 'bg' => '#e8f5e8'],
                            'timing' => ['title' => 'Best Time to Take', 'color' => '#007bff', 'bg' => '#e3f2fd'],
                            'preparation' => ['title' => 'How to Prepare', 'color' => '#374151', 'bg' => '#f9fafb'],
                            'precautions' => ['title' => 'Important Notes', 'color' => '#dc3545', 'bg' => '#f8d7da'],
                            'storage' => ['title' => 'Storage Instructions', 'color' => '#6c757d', 'bg' => '#f8f9fa']
                        ];

                        foreach ($usageInstructions as $instruction):
                            $type = $instruction['instruction_type'];
                            $typeInfo = isset($instructionTypes[$type]) ? $instructionTypes[$type] : ['title' => ucfirst($type), 'color' => '#333', 'bg' => '#f8f9fa'];
                        ?>
                    <div
                        style="background: <?php echo $typeInfo['bg']; ?>; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid <?php echo $typeInfo['color']; ?>;">
                        <h5 style="margin-top: 0; color: <?php echo $typeInfo['color']; ?>; font-weight: 600;">
                            <?php echo htmlspecialchars($instruction['instruction_title']); ?>
                        </h5>
                        <p style="margin-bottom: 0; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($instruction['instruction_content'])); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Text Instructions from Product Table -->
                <?php if ($product['how_to_use']): ?>
                <div class="text-instructions"
                    style="background: #e8f5e8; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #28a745;">
                    <h4 style="margin-top: 0; color: #28a745;">Usage Instructions</h4>
                    <p style="margin-bottom: 0;"><?php echo nl2br(htmlspecialchars($product['how_to_use'])); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($product['directions']): ?>
                <div class="text-instructions"
                    style="background: #f9fafb; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #6b7280; border: 1px solid #e5e7eb;">
                    <h4 style="margin-top: 0; color: #374151;">Directions</h4>
                    <p style="margin-bottom: 0; color: #4b5563;">
                        <?php echo nl2br(htmlspecialchars($product['directions'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($product['warnings']): ?>
                <div
                    style="background: #f8d7da; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid #dc3545;">
                    <h4 style="margin-top: 0; color: #721c24;">Important Warnings</h4>
                    <p style="margin-bottom: 0; color: #721c24;">
                        <?php echo nl2br(htmlspecialchars($product['warnings'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($product['protein'] || $product['calories'] || $product['carbs'] || $product['fats'] || $product['fiber'] || $product['sodium']): ?>
            <div class="tab-content" id="nutrition">
                <h3>Nutrition Facts</h3>

                <div
                    style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border: 2px solid #dee2e6; margin-bottom: 1rem;">
                    <?php if ($product['serving_size']): ?>
                    <p style="margin: 0 0 1rem 0; font-size: 1.1rem;"><strong>Serving Size:</strong>
                        <?php echo htmlspecialchars($product['serving_size']); ?></p>
                    <?php endif; ?>
                    <?php if ($product['servings_per_container']): ?>
                    <p style="margin: 0 0 1rem 0; font-size: 1.1rem;"><strong>Servings per Container:</strong>
                        <?php echo $product['servings_per_container']; ?></p>
                    <?php endif; ?>
                </div>

                <table class="nutrition-table">
                    <thead>
                        <tr>
                            <th>Nutrient</th>
                            <th>Amount per Serving</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($product['calories']): ?>
                        <tr>
                            <td><strong>Calories</strong></td>
                            <td><strong><?php echo $product['calories']; ?></strong></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($product['protein']): ?>
                        <tr>
                            <td>Protein</td>
                            <td><?php echo $product['protein']; ?>g</td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($product['carbs']): ?>
                        <tr>
                            <td>Total Carbohydrates</td>
                            <td><?php echo $product['carbs']; ?>g</td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($product['fiber']): ?>
                        <tr>
                            <td>&nbsp;&nbsp;&nbsp;Dietary Fiber</td>
                            <td><?php echo $product['fiber']; ?>g</td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($product['fats']): ?>
                        <tr>
                            <td>Total Fat</td>
                            <td><?php echo $product['fats']; ?>g</td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($product['sodium']): ?>
                        <tr>
                            <td>Sodium</td>
                            <td><?php echo $product['sodium']; ?>mg</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Ingredients from supplement_details or products table -->
                <?php if ($product['ingredients'] || (isset($supplementDetails['ingredients']) && $supplementDetails['ingredients'])): ?>
                <div
                    style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #dee2e6; margin-top: 1rem;">
                    <h4 style="margin-top: 0; color: #333;">Ingredients</h4>
                    <p style="margin-bottom: 0; line-height: 1.6; color: #666;">
                        <?php
                            $ingredients = $product['ingredients'] ?: (isset($supplementDetails['ingredients']) ? $supplementDetails['ingredients'] : '');
                            echo nl2br(htmlspecialchars($ingredients));
                            ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Weight and Container Information -->
                <?php if ($product['weight_value'] || $product['weight_unit']): ?>
                <div
                    style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 1rem; border-left: 4px solid #007bff;">
                    <h5 style="margin-top: 0; color: #007bff;">Product Weight</h5>
                    <p style="margin-bottom: 0;">
                        <?php if ($product['weight_value']): ?>
                        <strong><?php echo $product['weight_value']; ?></strong>
                        <?php endif; ?>
                        <?php if ($product['weight_unit']): ?>
                        <?php echo htmlspecialchars($product['weight_unit']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Usage Images Tab -->
            <?php if ($product['how_to_use_images']): ?>
            <div class="tab-content" id="images">
                <h3>Usage Images</h3>
                <div class="usage-images-grid"
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
                    <?php
                    // Properly decode JSON format from database
                    $imageUrls = [];
                    if (!empty($product['how_to_use_images'])) {
                        $imageUrls = json_decode($product['how_to_use_images'], true) ?? [];
                    }

                    if (!empty($imageUrls)):
                        foreach ($imageUrls as $index => $imageUrl):
                            if (!empty($imageUrl)):
                    ?>
                    <div class="usage-image-item"
                        style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden; background: #f8f9fa;">
                        <img src="<?php echo htmlspecialchars($imageUrl); ?>"
                            alt="Usage Image <?php echo $index + 1; ?>"
                            style="width: 100%; height: 200px; object-fit: cover;"
                            onerror="this.parentNode.style.display='none';">
                        <div style="padding: 0.5rem; text-align: center; font-size: 0.9rem; color: #666;">
                            Step <?php echo $index + 1; ?>
                        </div>
                    </div>
                    <?php
                            endif;
                        endforeach;
                    else:
                    ?>
                    <div
                        style="text-align: center; padding: 2rem; color: #666; background: #f8f9fa; border-radius: 8px; border: 1px dashed #dee2e6; grid-column: 1 / -1;">
                        <h4 style="color: #6c757d; margin-bottom: 1rem;">No Usage Images Available</h4>
                        <p>Usage images have not been uploaded for this product yet.</p>
                        <p style="font-size: 0.9rem; margin-bottom: 0;">Images can be added through the admin panel.</p>
                    </div>
                    <?php
                    endif;
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reviews Tab -->
            <div class="tab-content" id="reviews">
                <div class="reviews-section">
                    <div class="reviews-header" style="margin-bottom: 2rem;">
                        <h3 style="margin: 0;">Customer Reviews</h3>
                    </div>

                    <!-- Review Statistics -->
                    <div id="reviewStats" class="review-stats" style="
                        background: #f8f9fa;
                        padding: 20px;
                        border-radius: 8px;
                        margin-bottom: 2rem;
                        border: 1px solid #dee2e6;
                    ">
                        <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                            <div class="overall-rating" style="text-align: center;">
                                <div style="font-size: 2.5rem; font-weight: bold; color: #333; margin-bottom: 5px;">
                                    <?php echo $avgRating > 0 ? $avgRating : '0.0'; ?>
                                </div>
                                <div class="star-display" style="margin-bottom: 5px;">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $avgRating) {
                                            echo '<i class="fas fa-star" style="color: #ffc107; font-size: 1.2rem;"></i>';
                                        } elseif ($i - 0.5 <= $avgRating) {
                                            echo '<i class="fas fa-star-half-alt" style="color: #ffc107; font-size: 1.2rem;"></i>';
                                        } else {
                                            echo '<i class="far fa-star" style="color: #ddd; font-size: 1.2rem;"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                                <div style="color: #666; font-size: 0.9rem;">
                                    Based on <?php echo $totalReviews; ?>
                                    review<?php echo $totalReviews != 1 ? 's' : ''; ?>
                                </div>
                            </div>

                            <?php if ($totalReviews > 0): ?>
                            <div class="rating-breakdown" style="flex: 1; min-width: 300px;">
                                <?php
                                // Get rating breakdown
                                $ratingBreakdownQuery = "
                                    SELECT
                                        rating,
                                        COUNT(*) as count,
                                        (COUNT(*) * 100.0 / ?) as percentage
                                    FROM reviews
                                    WHERE product_id = ? AND status = 'approved'
                                    GROUP BY rating
                                    ORDER BY rating DESC
                                ";
                                $ratingBreakdownStmt = $pdo->prepare($ratingBreakdownQuery);
                                $ratingBreakdownStmt->execute([$totalReviews, $product_id]);
                                $ratingBreakdown = $ratingBreakdownStmt->fetchAll(PDO::FETCH_ASSOC);

                                // Create array with all ratings (1-5) initialized to 0
                                $ratings = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                                foreach ($ratingBreakdown as $rating) {
                                    $ratings[$rating['rating']] = $rating;
                                }
                                ?>

                                <?php foreach ($ratings as $star => $data): ?>
                                <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                    <span style="width: 60px; font-size: 0.9rem;"><?php echo $star; ?> star</span>
                                    <div
                                        style="flex: 1; background: #e9ecef; height: 8px; border-radius: 4px; margin: 0 10px; overflow: hidden;">
                                        <div style="
                                            height: 100%;
                                            background: #ffc107;
                                            width: <?php echo is_array($data) ? $data['percentage'] : 0; ?>%;
                                            transition: width 0.3s;
                                        "></div>
                                    </div>
                                    <span style="width: 40px; font-size: 0.9rem; text-align: right;">
                                        <?php echo is_array($data) ? $data['count'] : 0; ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Reviews List -->
                    <div id="reviewsContainer" class="reviews-container">
                        <div style="text-align: center; padding: 2rem; color: #666;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p>Loading reviews...</p>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div id="reviewsPagination" class="reviews-pagination" style="margin-top: 2rem;"></div>
                </div>
            </div>
        </div>

        <!-- Customer Reviews Section -->
        <div class="customer-reviews-section"
            style="margin: 3rem 0; background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
            <div class="reviews-header" style="margin-bottom: 2rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem;">
                <h2 style="margin: 0; color: #1f2937; font-size: 1.75rem; font-weight: 700;">
                    <i class="fas fa-star" style="color: #fbbf24; margin-right: 0.5rem;"></i>
                    Customer Reviews
                </h2>
            </div>

            <!-- Review Statistics Summary -->
            <div class="review-stats-summary" style="
                background: linear-gradient(135deg, #f8fafc, #f1f5f9);
                padding: 1.5rem;
                border-radius: 8px;
                margin-bottom: 2rem;
                border: 1px solid #e2e8f0;
            ">
                <div style="display: flex; align-items: center; gap: 2rem; flex-wrap: wrap;">
                    <div class="overall-rating" style="text-align: center;">
                        <div style="font-size: 2.5rem; font-weight: 700; color: #1f2937; margin-bottom: 5px;">
                            <?php echo $avgRating > 0 ? $avgRating : '0.0'; ?>
                        </div>
                        <div class="star-display" style="margin-bottom: 5px;">
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avgRating) {
                                    echo '<i class="fas fa-star" style="color: #fbbf24; font-size: 1.2rem;"></i>';
                                } elseif ($i - 0.5 <= $avgRating) {
                                    echo '<i class="fas fa-star-half-alt" style="color: #fbbf24; font-size: 1.2rem;"></i>';
                                } else {
                                    echo '<i class="far fa-star" style="color: #e5e7eb; font-size: 1.2rem;"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div style="color: #6b7280; font-size: 0.9rem; font-weight: 500;">
                            Based on <?php echo $totalReviews; ?> review<?php echo $totalReviews != 1 ? 's' : ''; ?>
                        </div>
                    </div>

                    <?php if ($totalReviews > 0): ?>
                    <div class="rating-breakdown" style="flex: 1; min-width: 300px;">
                        <?php
                        // Get rating breakdown for the summary
                        $ratingBreakdownQuery = "
                            SELECT
                                rating,
                                COUNT(*) as count,
                                (COUNT(*) * 100.0 / ?) as percentage
                            FROM reviews
                            WHERE product_id = ? AND status = 'approved'
                            GROUP BY rating
                            ORDER BY rating DESC
                        ";
                        $ratingBreakdownStmt = $pdo->prepare($ratingBreakdownQuery);
                        $ratingBreakdownStmt->execute([$totalReviews, $product_id]);
                        $ratingBreakdown = $ratingBreakdownStmt->fetchAll(PDO::FETCH_ASSOC);

                        // Create array with all ratings (1-5) initialized to 0
                        $ratings = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                        foreach ($ratingBreakdown as $rating) {
                            $ratings[$rating['rating']] = $rating;
                        }
                        ?>

                        <?php foreach ($ratings as $star => $data): ?>
                        <div style="display: flex; align-items: center; margin-bottom: 8px;">
                            <span
                                style="width: 60px; font-size: 0.9rem; font-weight: 500; color: #374151;"><?php echo $star; ?>
                                star</span>
                            <div
                                style="flex: 1; background: #e5e7eb; height: 8px; border-radius: 4px; margin: 0 10px; overflow: hidden;">
                                <div style="
                                    height: 100%;
                                    background: linear-gradient(90deg, #fbbf24, #f59e0b);
                                    width: <?php echo is_array($data) ? $data['percentage'] : 0; ?>%;
                                    transition: width 0.3s ease;
                                    border-radius: 4px;
                                "></div>
                            </div>
                            <span
                                style="width: 40px; font-size: 0.9rem; text-align: right; font-weight: 500; color: #374151;">
                                <?php echo is_array($data) ? $data['count'] : 0; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reviews List Container -->
            <div id="mainReviewsContainer" class="main-reviews-container">
                <div style="text-align: center; padding: 2rem; color: #6b7280;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem; color: #3b82f6;"></i>
                    <p style="font-size: 1.1rem; font-weight: 500;">Loading reviews...</p>
                </div>
            </div>

            <!-- Pagination for main reviews -->
            <div id="mainReviewsPagination" class="main-reviews-pagination" style="margin-top: 2rem;"></div>
        </div>

        <!-- Related Products -->
        <?php if (!empty($relatedProducts)): ?>
        <div class="related-products">
            <h3>Related Products</h3>
            <div class="related-grid">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                <a href="product-detail.php?id=<?php echo $relatedProduct['product_id']; ?>" class="related-product">
                    <?php if ($relatedProduct['image_url']): ?>
                    <?php
                                $imgUrl = $relatedProduct['image_url'];
                                $imgFile = basename($imgUrl);
                                $imgUrl = 'assets/' . $imgFile;
                            ?>
                    <img src="<?php echo htmlspecialchars($imgUrl); ?>"
                        alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>"
                        onerror="this.style.display='none';">
                    <?php endif; ?>
                    <h4 style="font-size: 1rem; margin: 0.5rem 0; color: #333;">
                        <?php echo htmlspecialchars($relatedProduct['name']); ?>
                    </h4>
                    <p style="font-size: 1.1rem; font-weight: 600; color: #2874f0; margin: 0;">
                        ₹<?php echo number_format($relatedProduct['price'], 2); ?>
                    </p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
    const sliderNavs = mainImage.querySelectorAll('.slider-nav');

    mainImage.innerHTML = `
        <img src="${imageSrc}" alt="Product Image" id="mainProductImage" onclick="openZoom('${imageSrc}')" style="width: 100%; height: 100%; object-fit: cover; padding: 30px; cursor: zoom-in;">
        <div class="zoom-lens" id="zoomLens"></div>
        <div class="zoom-result" id="zoomResult">
            <img id="zoomResultImage" src="${imageSrc}" alt="Zoomed view">
        </div>
    `;

    // Re-add slider navigation buttons
    sliderNavs.forEach(nav => mainImage.appendChild(nav));

    // Update active thumbnail (both vertical and horizontal thumbnails)
    document.querySelectorAll('.thumbnail, .vertical-thumbnail, .usage-thumbnail').forEach(thumb => thumb.classList
        .remove('active'));
    thumbnail.classList.add('active');

    // Update current image index
    currentImageIndex = allImages.findIndex(img => img.element === thumbnail);
    if (currentImageIndex === -1) currentImageIndex = 0;

    // Reinitialize Flipkart zoom for new image
    setTimeout(initFlipkartZoom, 100);
}

// Usage image functionality
function changeMainImageToUsage(imageSrc, thumbnail, stepNumber, stepTitle = '', stepDescription = '') {
    const mainImage = document.getElementById('mainImage');
    const sliderNavs = mainImage.querySelectorAll('.slider-nav');

    // Create a more detailed display for usage images with step information
    mainImage.innerHTML = `
        <div style="position: relative; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px;">
            <div style="position: relative; flex: 1; width: 100%; display: flex; align-items: center; justify-content: center;">
                <img src="${imageSrc}" alt="Usage Step ${stepNumber}" id="mainProductImage" onclick="openZoom('${imageSrc}')" style="width: 100%; height: 100%; object-fit: cover; cursor: zoom-in;">
                <div class="zoom-lens" id="zoomLens"></div>
                <div class="zoom-result" id="zoomResult">
                    <img id="zoomResultImage" src="${imageSrc}" alt="Zoomed view">
                </div>

            </div>

        </div>
    `;

    // Re-add slider navigation buttons
    sliderNavs.forEach(nav => mainImage.appendChild(nav));

    // Update active thumbnail - remove active from all thumbnails and add to clicked one
    document.querySelectorAll('.thumbnail, .vertical-thumbnail, .usage-thumbnail').forEach(thumb => thumb.classList
        .remove('active'));
    thumbnail.classList.add('active');

    // Update current image index
    currentImageIndex = allImages.findIndex(img => img.element === thumbnail);
    if (currentImageIndex === -1) currentImageIndex = 0;

    // Reinitialize Flipkart zoom for new image
    setTimeout(initFlipkartZoom, 100);
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

// Tab functionality
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });

    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab content
    document.getElementById(tabName).classList.add('active');

    // Add active class to clicked button
    event.target.classList.add('active');
}

// Add to cart functionality
function addToCart() {
    const quantity = document.getElementById('quantity').value;
    const productId = '<?php echo $product_id; ?>';
    const selectedVariant = document.querySelector('.variant-option.selected');
    const variantId = selectedVariant ? selectedVariant.dataset.variantId : null;

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
                variant_id: variantId
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
    // You can modify this to match your checkout flow
    alert(`Proceeding to checkout with ${quantity} item(s)`);
    // window.location.href = 'checkout.php?product_id=' + productId + '&quantity=' + quantity + '&variant_id=' + selectedVariantId;
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

// Slider Navigation Functionality
let currentImageIndex = 0;
let allImages = [];

// Initialize image array on page load
document.addEventListener('DOMContentLoaded', function() {
    // Collect all product images
    const productImages = document.querySelectorAll('.vertical-thumbnail');
    const usageImages = document.querySelectorAll('.usage-thumbnail');

    allImages = [];

    // Add product images to array
    productImages.forEach((thumb, index) => {
        const img = thumb.querySelector('img');
        if (img) {
            allImages.push({
                type: 'product',
                element: thumb,
                src: img.src,
                alt: img.alt
            });
        }
    });

    // Add usage images to array
    usageImages.forEach((thumb, index) => {
        allImages.push({
            type: 'usage',
            element: thumb,
            src: thumb.querySelector('img').src,
            stepNumber: thumb.getAttribute('onclick').match(/\d+/)[0],
            stepTitle: thumb.title || '',
            stepDescription: ''
        });
    });

    // Set initial active image index
    const activeThumb = document.querySelector('.vertical-thumbnail.active, .usage-thumbnail.active');
    if (activeThumb) {
        currentImageIndex = allImages.findIndex(img => img.element === activeThumb);
        if (currentImageIndex === -1) currentImageIndex = 0;
    }
});

function navigateImage(direction) {
    if (allImages.length === 0) return;

    if (direction === 'next') {
        currentImageIndex = (currentImageIndex + 1) % allImages.length;
    } else if (direction === 'prev') {
        currentImageIndex = (currentImageIndex - 1 + allImages.length) % allImages.length;
    }

    const currentImage = allImages[currentImageIndex];

    if (currentImage.type === 'product') {
        // Trigger click on the corresponding thumbnail
        currentImage.element.click();
    } else if (currentImage.type === 'usage') {
        // Trigger click on the corresponding usage thumbnail
        currentImage.element.click();
    }
}

// Update slider navigation buttons visibility
function updateSliderNavigation() {
    const mainImage = document.getElementById('mainImage');
    const sliderNavs = mainImage.querySelectorAll('.slider-nav');

    if (allImages.length <= 1) {
        sliderNavs.forEach(nav => nav.style.display = 'none');
    } else {
        sliderNavs.forEach(nav => nav.style.display = 'flex');
    }
}

// Review system already initialized earlier

// Zoom functionality with dragging system
let currentZoom = 1;
let isDragging = false;
let isZooming = false;
let startX, startY, translateX = 0,
    translateY = 0;
let lastDistance = 0;

function openZoom(imageSrc) {
    const modal = document.getElementById('zoomModal');
    const zoomImage = document.getElementById('zoomImage');

    // Show modal first
    modal.style.display = 'flex';
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    // Set image source and wait for it to load
    zoomImage.onload = function() {
        // Reset zoom and position after image loads
        currentZoom = 1;
        translateX = 0;
        translateY = 0;
        updateImageTransform();
    };

    zoomImage.src = imageSrc;

    // Fallback in case onload doesn't fire
    setTimeout(() => {
        currentZoom = 1;
        translateX = 0;
        translateY = 0;
        updateImageTransform();
    }, 100);
}

function closeZoom() {
    const modal = document.getElementById('zoomModal');
    modal.classList.remove('active');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function zoomIn() {
    currentZoom = Math.min(currentZoom * 1.5, 5);
    updateImageTransform();
}

function zoomOut() {
    currentZoom = Math.max(currentZoom / 1.5, 0.5);
    updateImageTransform();
}

function resetZoom() {
    currentZoom = 1;
    translateX = 0;
    translateY = 0;
    updateImageTransform();
}

function updateImageTransform() {
    const zoomImage = document.getElementById('zoomImage');
    zoomImage.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
}

// Calculate distance between two touch points
function getDistance(touch1, touch2) {
    const dx = touch1.clientX - touch2.clientX;
    const dy = touch1.clientY - touch2.clientY;
    return Math.sqrt(dx * dx + dy * dy);
}

// Accordion toggle functionality
function toggleAccordion(header) {
    const content = header.nextElementSibling;
    const icon = header.querySelector('.accordion-icon');

    // Close all other accordions
    const allHeaders = document.querySelectorAll('.accordion-header');
    const allContents = document.querySelectorAll('.accordion-content');

    allHeaders.forEach(h => {
        if (h !== header) {
            h.classList.remove('active');
            h.querySelector('.accordion-icon').style.transform = 'rotate(0deg)';
        }
    });

    allContents.forEach(c => {
        if (c !== content) {
            c.classList.remove('active');
            c.style.maxHeight = '0';
        }
    });

    // Toggle current accordion
    if (content.classList.contains('active')) {
        // Close current
        header.classList.remove('active');
        content.classList.remove('active');
        content.style.maxHeight = '0';
        icon.style.transform = 'rotate(0deg)';
    } else {
        // Open current
        header.classList.add('active');
        content.classList.add('active');
        content.style.maxHeight = content.scrollHeight + 'px';
        icon.style.transform = 'rotate(180deg)';
    }
}

// Flipkart-style hover zoom functionality
function initFlipkartZoom() {
    const mainImage = document.getElementById('mainProductImage');
    const zoomLens = document.getElementById('zoomLens');
    const zoomResult = document.getElementById('zoomResult');
    const zoomResultImage = document.getElementById('zoomResultImage');

    if (!mainImage || !zoomLens || !zoomResult || !zoomResultImage) return;

    const lensSize = 100; // Size of the zoom lens
    const zoomFactor = 3; // Zoom magnification factor

    // Mouse enter - show zoom elements only when over the actual image
    mainImage.addEventListener('mouseenter', function(e) {
        if (window.innerWidth > 768) { // Only on desktop
            const img = mainImage.querySelector('img');
            if (img) {
                const imgRect = img.getBoundingClientRect();
                const x = e.clientX;
                const y = e.clientY;

                // Check if mouse is over the actual image (not padding)
                if (x >= imgRect.left && x <= imgRect.right &&
                    y >= imgRect.top && y <= imgRect.bottom) {
                    zoomLens.style.display = 'block';
                    zoomResult.style.display = 'block';
                }
            }
        }
    });

    // Mouse leave - hide zoom elements
    mainImage.addEventListener('mouseleave', function() {
        zoomLens.style.display = 'none';
        zoomResult.style.display = 'none';
    });

    // Mouse move - update lens position and zoom view
    mainImage.addEventListener('mousemove', function(e) {
        if (window.innerWidth <= 768) return; // Skip on mobile

        const rect = mainImage.getBoundingClientRect();
        const img = mainImage.querySelector('img');
        if (!img) return;

        const imgRect = img.getBoundingClientRect();

        // Check if mouse is over the actual image
        const mouseX = e.clientX;
        const mouseY = e.clientY;

        if (mouseX < imgRect.left || mouseX > imgRect.right ||
            mouseY < imgRect.top || mouseY > imgRect.bottom) {
            // Mouse is outside image area, hide zoom elements
            zoomLens.style.display = 'none';
            zoomResult.style.display = 'none';
            return;
        }

        // Mouse is over image, show zoom elements
        zoomLens.style.display = 'block';
        zoomResult.style.display = 'block';

        // Calculate mouse position relative to the actual image (not container)
        const x = e.clientX - imgRect.left;
        const y = e.clientY - imgRect.top;

        // Calculate lens position relative to image
        let lensX = x - lensSize / 2;
        let lensY = y - lensSize / 2;

        // Keep lens within image boundaries
        lensX = Math.max(0, Math.min(lensX, imgRect.width - lensSize));
        lensY = Math.max(0, Math.min(lensY, imgRect.height - lensSize));

        // Position the lens relative to the main image container
        const containerOffsetX = imgRect.left - rect.left;
        const containerOffsetY = imgRect.top - rect.top;

        zoomLens.style.left = (lensX + containerOffsetX) + 'px';
        zoomLens.style.top = (lensY + containerOffsetY) + 'px';
        zoomLens.style.width = lensSize + 'px';
        zoomLens.style.height = lensSize + 'px';

        // Calculate zoom result position - this shows the magnified area
        // We need to calculate what portion of the image the lens is covering
        const lensRatioX = lensX / imgRect.width;
        const lensRatioY = lensY / imgRect.height;

        // Calculate the size of the zoomed image in the result panel
        const zoomResultWidth = zoomResult.offsetWidth;
        const zoomResultHeight = zoomResult.offsetHeight;

        // Set the zoomed image size (larger than the result panel)
        const zoomedImageWidth = imgRect.width * zoomFactor;
        const zoomedImageHeight = imgRect.height * zoomFactor;

        // Calculate the position to show the correct portion
        const zoomX = -(lensRatioX * zoomedImageWidth - zoomResultWidth / 2);
        const zoomY = -(lensRatioY * zoomedImageHeight - zoomResultHeight / 2);

        // Update zoom result image
        zoomResultImage.style.width = zoomedImageWidth + 'px';
        zoomResultImage.style.height = zoomedImageHeight + 'px';
        zoomResultImage.style.left = zoomX + 'px';
        zoomResultImage.style.top = zoomY + 'px';
    });
}

// Event listeners for zoom modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('zoomModal');
    const zoomImage = document.getElementById('zoomImage');
    const zoomContainer = document.getElementById('zoomContainer');

    // Initialize Flipkart-style zoom
    initFlipkartZoom();

    // Close on background click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeZoom();
        }
    });

    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeZoom();
        }
    });

    // Mouse wheel zoom
    zoomContainer.addEventListener('wheel', function(e) {
        e.preventDefault();
        if (e.deltaY < 0) {
            zoomIn();
        } else {
            zoomOut();
        }
    });

    // Mouse drag functionality for panning and zooming
    zoomImage.addEventListener('mousedown', startDrag);
    zoomImage.addEventListener('touchstart', startTouch);

    function startDrag(e) {
        e.preventDefault();

        if (e.shiftKey) {
            // Shift + drag for zooming
            isZooming = true;
            startY = e.clientY;
            zoomImage.style.cursor = 'ns-resize';
        } else {
            // Regular drag for panning (only when zoomed in)
            if (currentZoom > 1) {
                isDragging = true;
                zoomImage.style.cursor = 'grabbing';
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
            }
        }
    }

    function startTouch(e) {
        e.preventDefault();

        if (e.touches.length === 1) {
            // Single touch for panning
            if (currentZoom > 1) {
                isDragging = true;
                startX = e.touches[0].clientX - translateX;
                startY = e.touches[0].clientY - translateY;
            }
        } else if (e.touches.length === 2) {
            // Two finger pinch for zooming
            isZooming = true;
            lastDistance = getDistance(e.touches[0], e.touches[1]);
        }
    }

    document.addEventListener('mousemove', handleMouseMove);
    document.addEventListener('touchmove', handleTouchMove);

    function handleMouseMove(e) {
        if (isZooming) {
            // Vertical drag to zoom
            const deltaY = startY - e.clientY;
            const zoomFactor = 1 + (deltaY * 0.01);
            currentZoom = Math.max(0.5, Math.min(5, currentZoom * zoomFactor));
            startY = e.clientY;
            updateImageTransform();
        } else if (isDragging && currentZoom > 1) {
            // Drag to pan
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            updateImageTransform();
        }
        e.preventDefault();
    }

    function handleTouchMove(e) {
        e.preventDefault();

        if (e.touches.length === 1 && isDragging && currentZoom > 1) {
            // Single touch pan
            translateX = e.touches[0].clientX - startX;
            translateY = e.touches[0].clientY - startY;
            updateImageTransform();
        } else if (e.touches.length === 2 && isZooming) {
            // Two finger pinch zoom
            const currentDistance = getDistance(e.touches[0], e.touches[1]);
            const zoomFactor = currentDistance / lastDistance;
            currentZoom = Math.max(0.5, Math.min(5, currentZoom * zoomFactor));
            lastDistance = currentDistance;
            updateImageTransform();
        }
    }

    document.addEventListener('mouseup', endInteraction);
    document.addEventListener('touchend', endInteraction);

    function endInteraction() {
        if (isDragging || isZooming) {
            isDragging = false;
            isZooming = false;
            zoomImage.style.cursor = currentZoom > 1 ? 'grab' : 'zoom-in';
        }
    }
});
</script>

<!-- Include Review System -->
<?php
// Include the review form component
include 'components/review-form.php';
?>

<!-- Review System JavaScript already loaded earlier -->

<!-- Review System Styles -->
<style>
/* Reviews Section Styles */
.reviews-container {
    max-width: 100%;
}

.review-item {
    background: white;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    transition: box-shadow 0.3s;
}

.review-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.reviewer-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.reviewer-name {
    font-weight: 600;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.verified-badge {
    background: #28a745;
    color: white;
    font-size: 0.75rem;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 500;
}

.review-rating {
    display: flex;
    align-items: center;
    gap: 2px;
}

.review-date {
    color: #666;
    font-size: 0.9rem;
}

.review-content {
    margin-bottom: 15px;
}

.review-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin: 0 0 10px 0;
}

.review-text {
    color: #555;
    line-height: 1.6;
    margin: 0;
}

.review-images {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.review-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #ddd;
    cursor: pointer;
    transition: transform 0.2s;
}

.review-image:hover {
    transform: scale(1.05);
}

.review-actions {
    display: flex;
    gap: 15px;
    padding-top: 15px;
    border-top: 1px solid #f0f0f0;
}

.helpful-btn,
.not-helpful-btn {
    background: none;
    border: 1px solid #ddd;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 5px;
}

.helpful-btn:hover {
    background: #28a745;
    color: white;
    border-color: #28a745;
}

.not-helpful-btn:hover {
    background: #dc3545;
    color: white;
    border-color: #dc3545;
}

.admin-response {
    background: #f8f9fa;
    border-left: 4px solid #007bff;
    padding: 15px;
    margin-top: 15px;
    border-radius: 0 6px 6px 0;
}

.admin-response-header {
    font-weight: 600;
    color: #007bff;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.no-reviews {
    text-align: center;
    padding: 3rem 2rem;
    color: #666;
}

.no-reviews i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ddd;
}

.no-reviews h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

/* Pagination Styles */
.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 2rem;
}

.page-btn {
    padding: 8px 12px;
    border: 1px solid #ddd;
    background: white;
    color: #333;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
}

.page-btn:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.page-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

/* Responsive */
@media (max-width: 768px) {
    .review-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .review-actions {
        flex-direction: column;
        gap: 10px;
    }

    .helpful-btn,
    .not-helpful-btn {
        justify-content: center;
    }

    .reviews-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start !important;
    }

    .write-review-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include 'includes/footer.php'; ?>