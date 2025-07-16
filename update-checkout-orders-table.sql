-- Add missing columns to checkout_orders table for order processing
ALTER TABLE checkout_orders
ADD COLUMN order_number VARCHAR(50) UNIQUE AFTER order_id,
ADD COLUMN first_name VARCHAR(100) AFTER user_id,
ADD COLUMN last_name VARCHAR(100) AFTER first_name,
ADD COLUMN email VARCHAR(255) AFTER last_name,
ADD COLUMN phone VARCHAR(20) AFTER email,
ADD COLUMN address TEXT AFTER phone,
ADD COLUMN city VARCHAR(100) AFTER address,
ADD COLUMN state VARCHAR(100) AFTER city,
ADD COLUMN pincode VARCHAR(10) AFTER state,
ADD COLUMN payment_method ENUM('cod', 'razorpay', 'cashfree') DEFAULT 'cod' AFTER total_amount,
ADD COLUMN payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending' AFTER payment_method,
ADD COLUMN order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending' AFTER payment_status;

-- Show updated structure
DESCRIBE checkout_orders;

-- Success message
SELECT 'checkout_orders table updated successfully!' as message;