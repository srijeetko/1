-- Setup Order Processing Tables for Alpha Nutrition
-- Run this script to ensure all necessary tables exist for order processing

-- Enable UUID generation function
SET sql_mode = '';

-- Create checkout_orders table with all necessary fields
CREATE TABLE IF NOT EXISTS `checkout_orders` (
    `order_id` CHAR(36) NOT NULL PRIMARY KEY,
    `order_number` VARCHAR(50) UNIQUE NOT NULL,
    `user_id` CHAR(36) NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `pincode` VARCHAR(10) NOT NULL,
    `subtotal` DECIMAL(10,2) DEFAULT 0.00,
    `shipping_cost` DECIMAL(10,2) DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `payment_method` ENUM('cod', 'razorpay', 'cashfree', 'online') DEFAULT 'cod',
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    `order_status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create order_items table
CREATE TABLE IF NOT EXISTS `order_items` (
    `order_item_id` CHAR(36) NOT NULL PRIMARY KEY,
    `order_id` CHAR(36) NOT NULL,
    `product_id` CHAR(36) NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `variant_id` CHAR(36) NULL,
    `variant_name` VARCHAR(100) NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `total` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `checkout_orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create user_addresses table for saving customer addresses
CREATE TABLE IF NOT EXISTS `user_addresses` (
    `address_id` CHAR(36) NOT NULL PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `address_type` ENUM('home', 'work', 'other') DEFAULT 'home',
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `company` VARCHAR(100) NULL,
    `phone` VARCHAR(20) NOT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `pincode` VARCHAR(10) NOT NULL,
    `country` VARCHAR(100) DEFAULT 'India',
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create payment_transactions table (if not exists from OMS setup)
CREATE TABLE IF NOT EXISTS `payment_transactions` (
    `transaction_id` CHAR(36) NOT NULL PRIMARY KEY,
    `order_id` CHAR(36) NOT NULL,
    `payment_gateway` VARCHAR(50) NULL,
    `gateway_transaction_id` VARCHAR(100) NULL,
    `payment_method` ENUM('card', 'upi', 'netbanking', 'wallet', 'cod') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'INR',
    `transaction_status` ENUM('pending', 'processing', 'success', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    `gateway_response` JSON NULL,
    `failure_reason` TEXT NULL,
    `processed_at` DATETIME NULL,
    `refund_amount` DECIMAL(10,2) DEFAULT 0.00,
    `refund_date` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `checkout_orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create order_status_history table for tracking order changes
CREATE TABLE IF NOT EXISTS `order_status_history` (
    `history_id` CHAR(36) NOT NULL PRIMARY KEY,
    `order_id` CHAR(36) NOT NULL,
    `previous_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `changed_by` CHAR(36) NULL,
    `change_reason` TEXT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `checkout_orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS `idx_checkout_orders_user` ON `checkout_orders`(`user_id`);
CREATE INDEX IF NOT EXISTS `idx_checkout_orders_status` ON `checkout_orders`(`order_status`);
CREATE INDEX IF NOT EXISTS `idx_checkout_orders_payment` ON `checkout_orders`(`payment_status`);
CREATE INDEX IF NOT EXISTS `idx_checkout_orders_created` ON `checkout_orders`(`created_at`);
CREATE INDEX IF NOT EXISTS `idx_order_items_order` ON `order_items`(`order_id`);
CREATE INDEX IF NOT EXISTS `idx_order_items_product` ON `order_items`(`product_id`);
CREATE INDEX IF NOT EXISTS `idx_user_addresses_user` ON `user_addresses`(`user_id`);
CREATE INDEX IF NOT EXISTS `idx_payment_transactions_order` ON `payment_transactions`(`order_id`);
CREATE INDEX IF NOT EXISTS `idx_payment_transactions_status` ON `payment_transactions`(`transaction_status`);
CREATE INDEX IF NOT EXISTS `idx_order_status_history_order` ON `order_status_history`(`order_id`);

-- Insert sample data for testing (optional)
-- You can uncomment these lines to add test data

/*
-- Sample order
SET @sample_order_id = REPLACE(UUID(), '-', '');
INSERT INTO `checkout_orders` (
    `order_id`, `order_number`, `first_name`, `last_name`, `email`, `phone`,
    `address`, `city`, `state`, `pincode`, `total_amount`, `payment_method`
) VALUES (
    @sample_order_id, 'ORD-20241212-ABC123', 'John', 'Doe', 'john@example.com', '9876543210',
    '123 Main Street', 'Mumbai', 'Maharashtra', '400001', 1500.00, 'cod'
);

-- Sample order item
INSERT INTO `order_items` (
    `order_item_id`, `order_id`, `product_id`, `product_name`, `quantity`, `price`, `total`
) VALUES (
    REPLACE(UUID(), '-', ''), @sample_order_id, 'sample-product-id', 'Sample Protein Powder', 2, 750.00, 1500.00
);

-- Sample transaction
INSERT INTO `payment_transactions` (
    `transaction_id`, `order_id`, `payment_method`, `amount`, `transaction_status`
) VALUES (
    REPLACE(UUID(), '-', ''), @sample_order_id, 'cod', 1500.00, 'pending'
);
*/

-- Success message
SELECT 'Order processing tables created successfully!' as message;
SELECT 'You can now process orders through the checkout system' as instruction;
SELECT 'Tables created: checkout_orders, order_items, user_addresses, payment_transactions, order_status_history' as tables_created;
