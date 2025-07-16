<?php
// Review Form Component
// This component can be included in product detail pages

// Include Auth class if not already included
if (!class_exists('UserAuth')) {
    require_once __DIR__ . '/../includes/auth.php';
}

// Get current user info if logged in
// The $auth instance is already created in auth.php
$is_logged_in = $auth->isLoggedIn();
$current_user = $is_logged_in ? $auth->getCurrentUser() : null;
?>

<div id="reviewFormModal" class="review-modal" style="display: none;">
    <div class="review-modal-content">
        <div class="review-modal-header">
            <h3>Write a Review</h3>
            <span class="review-modal-close" onclick="closeReviewModal()">&times;</span>
        </div>
        
        <form id="reviewForm" enctype="multipart/form-data" onsubmit="return false;">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
            <?php if ($is_logged_in): ?>
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($current_user['user_id']); ?>">
            <?php endif; ?>
            
            <!-- Rating Section -->
            <div class="review-form-group">
                <label class="review-form-label">Rating *</label>
                <div class="star-rating-input">
                    <input type="radio" name="rating" value="5" id="star5" required>
                    <label for="star5" class="star">★</label>
                    <input type="radio" name="rating" value="4" id="star4">
                    <label for="star4" class="star">★</label>
                    <input type="radio" name="rating" value="3" id="star3">
                    <label for="star3" class="star">★</label>
                    <input type="radio" name="rating" value="2" id="star2">
                    <label for="star2" class="star">★</label>
                    <input type="radio" name="rating" value="1" id="star1">
                    <label for="star1" class="star">★</label>
                </div>
                <div class="rating-text">Click to rate</div>
            </div>
            
            <!-- Guest Information (if not logged in) -->
            <?php if (!$is_logged_in): ?>
                <div class="review-form-group">
                    <label for="guest_name" class="review-form-label">Your Name *</label>
                    <input type="text" id="guest_name" name="guest_name" required 
                           placeholder="Enter your full name" maxlength="100">
                </div>
                
                <div class="review-form-group">
                    <label for="guest_email" class="review-form-label">Your Email *</label>
                    <input type="email" id="guest_email" name="guest_email" required 
                           placeholder="Enter your email address">
                    <small class="form-help">Your email will not be displayed publicly</small>
                </div>
            <?php else: ?>
                <div class="review-form-group">
                    <div class="logged-in-info">
                        <i class="fas fa-user"></i>
                        Reviewing as: <strong><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></strong>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Review Title -->
            <div class="review-form-group">
                <label for="review_title" class="review-form-label">Review Title *</label>
                <input type="text" id="review_title" name="title" required 
                       placeholder="Summarize your review in a few words" maxlength="100">
                <div class="char-counter">
                    <span id="title-counter">0</span>/100 characters
                </div>
            </div>
            
            <!-- Review Content -->
            <div class="review-form-group">
                <label for="review_content" class="review-form-label">Your Review *</label>
                <textarea id="review_content" name="content" required rows="6" 
                          placeholder="Share your experience with this product. What did you like or dislike? How did it work for you?" 
                          maxlength="2000"></textarea>
                <div class="char-counter">
                    <span id="content-counter">0</span>/2000 characters
                </div>
            </div>
            
            <!-- Image Upload -->
            <div class="review-form-group">
                <label for="review_images" class="review-form-label">Add Photos (Optional)</label>
                <div class="image-upload-area">
                    <input type="file" id="review_images" name="review_images[]" 
                           accept="image/jpeg,image/jpg,image/png,image/gif" multiple>
                    <div class="upload-placeholder">
                        <i class="fas fa-camera"></i>
                        <p>Click to add photos or drag and drop</p>
                        <small>JPEG, PNG, GIF up to 5MB each (max 5 photos)</small>
                    </div>
                </div>
                <div id="image-preview" class="image-preview-container"></div>
            </div>
            
            <!-- Guidelines -->
            <div class="review-guidelines">
                <h4>Review Guidelines:</h4>
                <ul>
                    <li>Be honest and helpful to other customers</li>
                    <li>Focus on the product's features and your experience</li>
                    <li>Avoid inappropriate language or personal information</li>
                    <li>Reviews are moderated and may take 24-48 hours to appear</li>
                </ul>
            </div>
            
            <!-- Submit Button -->
            <div class="review-form-actions">
                <button type="button" class="btn-secondary" onclick="closeReviewModal()">Cancel</button>
                <button type="submit" class="btn-primary" id="submitReviewBtn">
                    <i class="fas fa-paper-plane"></i> Submit Review
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Success/Error Messages -->
<div id="reviewMessage" class="review-message" style="display: none;"></div>

<style>
/* Review Modal Styles */
.review-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.review-modal-content {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.review-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
    border-radius: 12px 12px 0 0;
}

.review-modal-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.4rem;
}

.review-modal-close {
    font-size: 28px;
    cursor: pointer;
    color: #666;
    line-height: 1;
    padding: 0 5px;
}

.review-modal-close:hover {
    color: #333;
}

#reviewForm {
    padding: 24px;
}

.review-form-group {
    margin-bottom: 20px;
}

.review-form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 0.95rem;
}

/* Star Rating Input */
.star-rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    margin-bottom: 5px;
}

.star-rating-input input[type="radio"] {
    display: none;
}

.star-rating-input .star {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
    margin-right: 5px;
}

.star-rating-input .star:hover,
.star-rating-input .star:hover ~ .star,
.star-rating-input input[type="radio"]:checked ~ .star {
    color: #ffc107;
}

.rating-text {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 10px;
}

/* Form Inputs */
.review-form-group input[type="text"],
.review-form-group input[type="email"],
.review-form-group textarea {
    width: 100%;
    padding: 12px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: border-color 0.3s;
    font-family: inherit;
}

.review-form-group input:focus,
.review-form-group textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.char-counter {
    text-align: right;
    font-size: 0.85rem;
    color: #666;
    margin-top: 5px;
}

.form-help {
    display: block;
    font-size: 0.85rem;
    color: #666;
    margin-top: 5px;
}

.logged-in-info {
    background: #e8f5e8;
    padding: 12px;
    border-radius: 8px;
    color: #2d5a2d;
    border-left: 4px solid #28a745;
}

.logged-in-info i {
    margin-right: 8px;
}

/* Image Upload */
.image-upload-area {
    position: relative;
    border: 2px dashed #ddd;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    transition: border-color 0.3s;
    cursor: pointer;
}

.image-upload-area:hover {
    border-color: #007bff;
}

.image-upload-area input[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.upload-placeholder i {
    font-size: 2rem;
    color: #666;
    margin-bottom: 10px;
}

.upload-placeholder p {
    margin: 0 0 5px 0;
    color: #333;
    font-weight: 500;
}

.upload-placeholder small {
    color: #666;
}

.image-preview-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
    min-height: 40px;
    padding: 10px;
    border: 1px dashed #ddd;
    border-radius: 8px;
    background: #fafafa;
}

.image-preview-container:empty::before {
    content: "Selected images will appear here";
    color: #999;
    font-style: italic;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 40px;
}

.image-preview {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    border: 2px solid #eee;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.image-preview:hover {
    transform: scale(1.05);
}

.image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-preview .remove-image {
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
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: background 0.2s;
}

.image-preview .remove-image:hover {
    background: #c82333;
}

/* Guidelines */
.review-guidelines {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.review-guidelines h4 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 1rem;
}

.review-guidelines ul {
    margin: 0;
    padding-left: 20px;
}

.review-guidelines li {
    margin-bottom: 5px;
    font-size: 0.9rem;
    color: #555;
}

/* Form Actions */
.review-form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.btn-primary, .btn-secondary {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
    transform: translateY(-1px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-primary:disabled {
    background: #ccc;
    cursor: not-allowed;
    transform: none;
}

/* Review Message */
.review-message {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 10001;
    max-width: 400px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.review-message.success {
    background: #28a745;
}

.review-message.error {
    background: #dc3545;
}

/* Responsive */
@media (max-width: 768px) {
    .review-modal {
        padding: 10px;
    }
    
    .review-modal-content {
        max-height: 95vh;
    }
    
    #reviewForm {
        padding: 20px;
    }
    
    .review-form-actions {
        flex-direction: column;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        justify-content: center;
    }
}
</style>
