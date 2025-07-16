// Review System JavaScript
// Handles review form functionality, submission, and display

// Global variables
let selectedFiles = [];
const maxFiles = 5;
const maxFileSize = 5 * 1024 * 1024; // 5MB

// Initialize review system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeReviewForm();
    initializeCharacterCounters();
    initializeImageUpload();
});

// Open review modal
function openReviewModal() {
    const modal = document.getElementById('reviewFormModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Reset form
        document.getElementById('reviewForm').reset();
        selectedFiles = [];
        updateImagePreview();
        updateCharacterCounters();
    }
}

// Close review modal
function closeReviewModal() {
    const modal = document.getElementById('reviewFormModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Initialize review form
function initializeReviewForm() {
    const form = document.getElementById('reviewForm');
    if (!form) return;
    
    form.addEventListener('submit', handleReviewSubmission);
    
    // Close modal when clicking outside
    const modal = document.getElementById('reviewFormModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeReviewModal();
            }
        });
    }
    
    // Handle ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeReviewModal();
        }
    });
}

// Initialize character counters
function initializeCharacterCounters() {
    const titleInput = document.getElementById('review_title');
    const contentInput = document.getElementById('review_content');
    
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            updateCharacterCounter('title-counter', this.value.length, 100);
        });
    }
    
    if (contentInput) {
        contentInput.addEventListener('input', function() {
            updateCharacterCounter('content-counter', this.value.length, 2000);
        });
    }
}

// Update character counter
function updateCharacterCounter(counterId, current, max) {
    const counter = document.getElementById(counterId);
    if (counter) {
        counter.textContent = current;
        counter.style.color = current > max * 0.9 ? '#dc3545' : '#666';
    }
}

// Update character counters
function updateCharacterCounters() {
    const titleInput = document.getElementById('review_title');
    const contentInput = document.getElementById('review_content');
    
    if (titleInput) {
        updateCharacterCounter('title-counter', titleInput.value.length, 100);
    }
    
    if (contentInput) {
        updateCharacterCounter('content-counter', contentInput.value.length, 2000);
    }
}

// Initialize image upload
function initializeImageUpload() {
    const fileInput = document.getElementById('review_images');
    if (!fileInput) return;
    
    fileInput.addEventListener('change', handleFileSelection);
    
    // Drag and drop functionality
    const uploadArea = document.querySelector('.image-upload-area');
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#007bff';
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#ddd';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#ddd';
            
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });
    }
}

// Handle file selection
function handleFileSelection(e) {
    const files = Array.from(e.target.files);
    handleFiles(files);
}

// Handle files (from input or drag-drop)
function handleFiles(files) {
    const validFiles = [];
    
    for (let file of files) {
        if (selectedFiles.length + validFiles.length >= maxFiles) {
            showMessage(`Maximum ${maxFiles} images allowed`, 'error');
            break;
        }
        
        if (!file.type.startsWith('image/')) {
            showMessage(`${file.name} is not a valid image file`, 'error');
            continue;
        }
        
        if (file.size > maxFileSize) {
            showMessage(`${file.name} is too large. Maximum size is 5MB`, 'error');
            continue;
        }
        
        validFiles.push(file);
    }
    
    selectedFiles = selectedFiles.concat(validFiles);
    updateImagePreview();
}

// Update image preview
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
                <img src="${e.target.result}" alt="Preview ${index + 1}">
                <button type="button" class="remove-image" onclick="removeImage(${index})">×</button>
            `;
            previewContainer.appendChild(previewDiv);
        };
        reader.readAsDataURL(file);
    });
}

// Remove image from selection
function removeImage(index) {
    selectedFiles.splice(index, 1);
    updateImagePreview();
}

// Handle review submission
async function handleReviewSubmission(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitReviewBtn');
    const originalText = submitBtn.innerHTML;
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    try {
        // Validate form
        if (!validateReviewForm()) {
            return;
        }
        
        // Prepare form data
        const formData = new FormData();
        const form = document.getElementById('reviewForm');
        
        // Add form fields
        const formElements = form.elements;
        for (let element of formElements) {
            if (element.name && element.value && element.type !== 'file') {
                formData.append(element.name, element.value);
            }
        }
        
        // Add selected images
        selectedFiles.forEach((file, index) => {
            formData.append('review_images[]', file);
        });
        
        // Submit review
        const response = await fetch('api/submit-review.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage(result.message, 'success');
            closeReviewModal();
            
            // Optionally refresh reviews section
            if (typeof loadProductReviews === 'function') {
                setTimeout(() => loadProductReviews(), 1000);
            }
        } else {
            showMessage(result.message, 'error');
        }
        
    } catch (error) {
        console.error('Review submission error:', error);
        showMessage('An error occurred while submitting your review. Please try again.', 'error');
    } finally {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Validate review form
function validateReviewForm() {
    const rating = document.querySelector('input[name="rating"]:checked');
    const title = document.getElementById('review_title');
    const content = document.getElementById('review_content');
    const guestName = document.getElementById('guest_name');
    const guestEmail = document.getElementById('guest_email');
    
    if (!rating) {
        showMessage('Please select a rating', 'error');
        return false;
    }
    
    if (!title.value.trim()) {
        showMessage('Please enter a review title', 'error');
        title.focus();
        return false;
    }
    
    if (title.value.length > 100) {
        showMessage('Review title must be 100 characters or less', 'error');
        title.focus();
        return false;
    }
    
    if (!content.value.trim()) {
        showMessage('Please enter your review', 'error');
        content.focus();
        return false;
    }
    
    if (content.value.length > 2000) {
        showMessage('Review content must be 2000 characters or less', 'error');
        content.focus();
        return false;
    }
    
    // Validate guest information if not logged in
    if (guestName && !guestName.value.trim()) {
        showMessage('Please enter your name', 'error');
        guestName.focus();
        return false;
    }
    
    if (guestEmail && !guestEmail.value.trim()) {
        showMessage('Please enter your email', 'error');
        guestEmail.focus();
        return false;
    }
    
    if (guestEmail && !isValidEmail(guestEmail.value)) {
        showMessage('Please enter a valid email address', 'error');
        guestEmail.focus();
        return false;
    }
    
    return true;
}

// Validate email format
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Show message to user
function showMessage(message, type = 'info') {
    const messageDiv = document.getElementById('reviewMessage');
    if (!messageDiv) return;
    
    messageDiv.textContent = message;
    messageDiv.className = `review-message ${type}`;
    messageDiv.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

// Star rating display helper
function displayStarRating(rating, maxRating = 5) {
    let stars = '';
    for (let i = 1; i <= maxRating; i++) {
        if (i <= rating) {
            stars += '<i class="fas fa-star" style="color: #ffc107;"></i>';
        } else if (i - 0.5 <= rating) {
            stars += '<i class="fas fa-star-half-alt" style="color: #ffc107;"></i>';
        } else {
            stars += '<i class="far fa-star" style="color: #ddd;"></i>';
        }
    }
    return stars;
}

// Format date for display
function formatReviewDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return `${diffDays} days ago`;
    } else if (diffDays < 30) {
        const weeks = Math.floor(diffDays / 7);
        return `${weeks} week${weeks > 1 ? 's' : ''} ago`;
    } else {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

// Load product reviews
async function loadProductReviews(productId, page = 1, limit = 10) {
    try {
        const response = await fetch(`api/get-reviews.php?product_id=${productId}&page=${page}&limit=${limit}`);
        const result = await response.json();

        if (result.success) {
            displayReviews(result.data.reviews, result.data.stats);
            updatePagination(result.data.pagination);
        } else {
            console.error('Failed to load reviews:', result.message);
        }
    } catch (error) {
        console.error('Error loading reviews:', error);
    }
}

// Display reviews in the UI
function displayReviews(reviews, stats) {
    const reviewsContainer = document.getElementById('reviewsContainer');
    if (!reviewsContainer) return;

    let reviewsHTML = '';

    if (reviews.length === 0) {
        reviewsHTML = `
            <div class="no-reviews">
                <i class="fas fa-comment-alt"></i>
                <h3>No reviews yet</h3>
                <p>Be the first to review this product!</p>
            </div>
        `;
    } else {
        reviews.forEach(review => {
            const reviewImages = review.review_images ? JSON.parse(review.review_images) : null;
            const hasImages = reviewImages && reviewImages.images && reviewImages.images.length > 0;

            reviewsHTML += `
                <div class="review-item" data-review-id="${review.review_id}">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <div class="reviewer-name">
                                ${review.reviewer_name}
                                ${review.verified_purchase ? '<span class="verified-badge">Verified Purchase</span>' : ''}
                            </div>
                            <div class="review-rating">
                                ${displayStarRating(review.rating)}
                            </div>
                        </div>
                        <div class="review-date">${formatReviewDate(review.created_at)}</div>
                    </div>

                    <div class="review-content">
                        <h4 class="review-title">${review.title}</h4>
                        <p class="review-text">${review.content}</p>

                        ${hasImages ? `
                            <div class="review-images">
                                ${reviewImages.images.map(img => `
                                    <img src="${img}" alt="Review image" class="review-image" onclick="openImageModal('${img}')">
                                `).join('')}
                            </div>
                        ` : ''}
                    </div>

                    <div class="review-actions">
                        <button class="helpful-btn" onclick="markReviewHelpful('${review.review_id}', true)">
                            <i class="fas fa-thumbs-up"></i> Helpful (${review.helpful_yes_count || 0})
                        </button>
                        <button class="not-helpful-btn" onclick="markReviewHelpful('${review.review_id}', false)">
                            <i class="fas fa-thumbs-down"></i> Not Helpful (${review.helpful_no_count || 0})
                        </button>
                    </div>

                    ${review.admin_response ? `
                        <div class="admin-response">
                            <div class="admin-response-header">
                                <i class="fas fa-reply"></i> Response from Alpha Nutrition
                            </div>
                            <p>${review.admin_response}</p>
                        </div>
                    ` : ''}
                </div>
            `;
        });
    }

    reviewsContainer.innerHTML = reviewsHTML;
}

// Mark review as helpful or not helpful
async function markReviewHelpful(reviewId, isHelpful) {
    try {
        const response = await fetch('api/review-helpful.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                review_id: reviewId,
                is_helpful: isHelpful
            })
        });

        const result = await response.json();

        if (result.success) {
            // Update the button counts in the UI
            updateHelpfulCounts(reviewId, result.data.helpful_yes_count, result.data.helpful_no_count);
            showMessage(result.message, 'success');
        } else {
            showMessage(result.message, 'error');
        }
    } catch (error) {
        console.error('Error voting on review:', error);
        showMessage('An error occurred while voting. Please try again.', 'error');
    }
}

// Update helpful counts in the UI
function updateHelpfulCounts(reviewId, yesCount, noCount) {
    const reviewItem = document.querySelector(`[data-review-id="${reviewId}"]`);
    if (!reviewItem) return;

    const helpfulBtn = reviewItem.querySelector('.helpful-btn');
    const notHelpfulBtn = reviewItem.querySelector('.not-helpful-btn');

    if (helpfulBtn) {
        helpfulBtn.innerHTML = `<i class="fas fa-thumbs-up"></i> Helpful (${yesCount})`;
    }

    if (notHelpfulBtn) {
        notHelpfulBtn.innerHTML = `<i class="fas fa-thumbs-down"></i> Not Helpful (${noCount})`;
    }
}

// Update pagination display
function updatePagination(pagination) {
    const paginationContainer = document.getElementById('reviewsPagination');
    if (!paginationContainer) return;

    let paginationHTML = '';

    if (pagination.total_pages > 1) {
        paginationHTML = '<div class="pagination">';

        // Previous button
        if (pagination.has_prev) {
            paginationHTML += `<button class="page-btn" onclick="loadProductReviews(currentProductId, ${pagination.current_page - 1})">Previous</button>`;
        }

        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === pagination.current_page ? 'active' : '';
            paginationHTML += `<button class="page-btn ${isActive}" onclick="loadProductReviews(currentProductId, ${i})">${i}</button>`;
        }

        // Next button
        if (pagination.has_next) {
            paginationHTML += `<button class="page-btn" onclick="loadProductReviews(currentProductId, ${pagination.current_page + 1})">Next</button>`;
        }

        paginationHTML += '</div>';
    }

    paginationContainer.innerHTML = paginationHTML;
}

// Export functions for global use
window.openReviewModal = openReviewModal;
window.closeReviewModal = closeReviewModal;
window.removeImage = removeImage;
window.displayStarRating = displayStarRating;
window.formatReviewDate = formatReviewDate;
window.loadProductReviews = loadProductReviews;
window.markReviewHelpful = markReviewHelpful;
