-- Enhance Reviews Table for Comprehensive Review System
-- This script adds additional fields to make the review system more robust

-- First, check if reviews table exists and add missing columns
-- Add columns one by one to handle MySQL compatibility
ALTER TABLE reviews ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE reviews ADD COLUMN helpful_count INT DEFAULT 0;
ALTER TABLE reviews ADD COLUMN verified_purchase TINYINT(1) DEFAULT 0;
ALTER TABLE reviews ADD COLUMN admin_response TEXT;
ALTER TABLE reviews ADD COLUMN admin_response_date TIMESTAMP NULL;
ALTER TABLE reviews ADD COLUMN status ENUM('pending', 'approved', 'rejected', 'spam') DEFAULT 'pending';
ALTER TABLE reviews ADD COLUMN review_images TEXT COMMENT 'JSON array of image URLs uploaded with review';

-- Add rating constraint if not exists
ALTER TABLE reviews 
ADD CONSTRAINT chk_rating CHECK (rating >= 1 AND rating <= 5);

-- Create review_helpful table for tracking helpful votes
CREATE TABLE IF NOT EXISTS review_helpful (
    helpful_id CHAR(36) PRIMARY KEY,
    review_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,
    is_helpful TINYINT(1) NOT NULL COMMENT '1 for helpful, 0 for not helpful',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_review (user_id, review_id)
);

-- Create review_reports table for reporting inappropriate reviews
CREATE TABLE IF NOT EXISTS review_reports (
    report_id CHAR(36) PRIMARY KEY,
    review_id CHAR(36) NOT NULL,
    reporter_user_id CHAR(36) NOT NULL,
    reason ENUM('spam', 'inappropriate', 'fake', 'offensive', 'other') NOT NULL,
    description TEXT,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Add indexes for better performance (MySQL compatible syntax)
CREATE INDEX idx_reviews_product_status ON reviews(product_id, status);
CREATE INDEX idx_reviews_user ON reviews(user_id);
CREATE INDEX idx_reviews_rating ON reviews(rating);
CREATE INDEX idx_reviews_created ON reviews(created_at);
CREATE INDEX idx_reviews_verified ON reviews(verified_purchase);

-- Create a view for approved reviews with helpful counts
CREATE OR REPLACE VIEW approved_reviews AS
SELECT 
    r.*,
    COALESCE(u.first_name, 'Anonymous') as reviewer_name,
    COALESCE(u.last_name, '') as reviewer_last_name,
    u.email as reviewer_email,
    p.name as product_name,
    (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.review_id AND rh.is_helpful = 1) as helpful_yes_count,
    (SELECT COUNT(*) FROM review_helpful rh WHERE rh.review_id = r.review_id AND rh.is_helpful = 0) as helpful_no_count
FROM reviews r
LEFT JOIN users u ON r.user_id = u.user_id
LEFT JOIN products p ON r.product_id = p.product_id
WHERE r.status = 'approved';

-- Create a view for review statistics per product
CREATE OR REPLACE VIEW product_review_stats AS
SELECT 
    p.product_id,
    p.name as product_name,
    COUNT(r.review_id) as total_reviews,
    AVG(r.rating) as average_rating,
    COUNT(CASE WHEN r.rating = 5 THEN 1 END) as five_star_count,
    COUNT(CASE WHEN r.rating = 4 THEN 1 END) as four_star_count,
    COUNT(CASE WHEN r.rating = 3 THEN 1 END) as three_star_count,
    COUNT(CASE WHEN r.rating = 2 THEN 1 END) as two_star_count,
    COUNT(CASE WHEN r.rating = 1 THEN 1 END) as one_star_count,
    COUNT(CASE WHEN r.verified_purchase = 1 THEN 1 END) as verified_reviews_count
FROM products p
LEFT JOIN reviews r ON p.product_id = r.product_id AND r.status = 'approved'
GROUP BY p.product_id, p.name;

-- Insert some sample review data for testing (optional)
-- You can remove this section if you don't want sample data

-- First, let's get some existing product and user IDs for sample data
-- Note: This will only work if you have existing products and users

-- Sample reviews (uncomment and modify IDs as needed)
/*
INSERT INTO reviews (review_id, user_id, product_id, rating, title, content, status, verified_purchase, created_at) VALUES
(REPLACE(UUID(), '-', ''), 'your-user-id-here', 'your-product-id-here', 5, 'Excellent Product!', 'This supplement has really helped me with my fitness goals. Highly recommended!', 'approved', 1, NOW() - INTERVAL 30 DAY),
(REPLACE(UUID(), '-', ''), 'your-user-id-here', 'your-product-id-here', 4, 'Good Quality', 'Good product overall, taste could be better but effectiveness is great.', 'approved', 1, NOW() - INTERVAL 15 DAY),
(REPLACE(UUID(), '-', ''), 'your-user-id-here', 'your-product-id-here', 5, 'Amazing Results', 'Saw results within 2 weeks of using this product. Will definitely buy again!', 'approved', 1, NOW() - INTERVAL 7 DAY);
*/

-- Note: Triggers will be created separately due to DELIMITER syntax complexity in PHP execution

-- Success message
SELECT 'Reviews table enhancement completed successfully!' as message;

