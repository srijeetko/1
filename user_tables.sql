-- Alpha Nutrition User Management Database Schema
-- Compatible with existing alphanutrition_db structure
-- Run this script to create all user-related tables

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing user tables if they exist (to avoid conflicts)
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS user_addresses;
DROP TABLE IF EXISTS users;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create users table (compatible with existing structure)
CREATE TABLE `users` (
    `user_id` CHAR(36) NOT NULL PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `phone` VARCHAR(20),
    `password_hash` VARCHAR(255) NOT NULL,
    `date_of_birth` DATE,
    `gender` ENUM('male', 'female', 'other'),
    `profile_image` VARCHAR(255),
    `email_verified` TINYINT(1) DEFAULT 0,
    `email_verification_token` VARCHAR(100),
    `password_reset_token` VARCHAR(100),
    `password_reset_expires` DATETIME,
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create user addresses table (compatible with existing addresses table structure)
CREATE TABLE `user_addresses` (
    `address_id` CHAR(36) NOT NULL PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `address_type` ENUM('home', 'work', 'other') DEFAULT 'home',
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `company` VARCHAR(100),
    `address_line_1` VARCHAR(255) NOT NULL,
    `address_line_2` VARCHAR(255),
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100) NOT NULL,
    `postal_code` VARCHAR(20) NOT NULL,
    `country` VARCHAR(100) DEFAULT 'India',
    `phone` VARCHAR(20),
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create user sessions table
CREATE TABLE `user_sessions` (
    `session_id` VARCHAR(128) PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create user preferences table
CREATE TABLE `user_preferences` (
    `preference_id` CHAR(36) NOT NULL PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `newsletter_subscription` TINYINT(1) DEFAULT 1,
    `sms_notifications` TINYINT(1) DEFAULT 0,
    `order_updates` TINYINT(1) DEFAULT 1,
    `promotional_emails` TINYINT(1) DEFAULT 1,
    `preferred_language` VARCHAR(10) DEFAULT 'en',
    `preferred_currency` VARCHAR(10) DEFAULT 'INR',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Update existing checkout_orders table to link with users
ALTER TABLE `checkout_orders`
ADD COLUMN `guest_email` VARCHAR(255) AFTER `user_id`,
ADD COLUMN `first_name` VARCHAR(100) AFTER `guest_email`,
ADD COLUMN `last_name` VARCHAR(100) AFTER `first_name`,
ADD COLUMN `email` VARCHAR(255) AFTER `last_name`,
ADD COLUMN `phone` VARCHAR(20) AFTER `email`,
ADD COLUMN `order_number` VARCHAR(50) UNIQUE AFTER `phone`,
ADD COLUMN `subtotal` DECIMAL(10,2) DEFAULT 0.00 AFTER `order_number`,
ADD COLUMN `shipping_cost` DECIMAL(10,2) DEFAULT 0.00 AFTER `subtotal`,
ADD COLUMN `tax_amount` DECIMAL(10,2) DEFAULT 0.00 AFTER `shipping_cost`,
ADD COLUMN `payment_method` ENUM('cod', 'online', 'wallet') DEFAULT 'cod' AFTER `tax_amount`,
ADD COLUMN `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER `payment_method`,
ADD COLUMN `order_status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending' AFTER `payment_status`,
ADD COLUMN `notes` TEXT AFTER `order_status`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Add foreign key constraint to link checkout_orders with users
ALTER TABLE `checkout_orders`
ADD CONSTRAINT `fk_checkout_orders_user`
FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL;

-- Update existing order_items table to be compatible
ALTER TABLE `order_items`
ADD COLUMN `product_name` VARCHAR(255) AFTER `variant_id`,
ADD COLUMN `variant_name` VARCHAR(100) AFTER `product_name`,
ADD COLUMN `unit_price` DECIMAL(10,2) DEFAULT 0.00 AFTER `variant_name`,
ADD COLUMN `total_price` DECIMAL(10,2) DEFAULT 0.00 AFTER `unit_price`,
ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `total_price`;

-- Update existing cart_items table to link with users
ALTER TABLE `cart_items`
ADD CONSTRAINT `fk_cart_items_user`
FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE;

-- Create indexes for better performance
CREATE INDEX `idx_users_email` ON `users`(`email`);
CREATE INDEX `idx_users_active` ON `users`(`is_active`);
CREATE INDEX `idx_user_addresses_user_id` ON `user_addresses`(`user_id`);
CREATE INDEX `idx_user_addresses_default` ON `user_addresses`(`is_default`);
CREATE INDEX `idx_user_sessions_user_id` ON `user_sessions`(`user_id`);
CREATE INDEX `idx_user_sessions_expires` ON `user_sessions`(`expires_at`);
CREATE INDEX `idx_checkout_orders_user_id` ON `checkout_orders`(`user_id`);
CREATE INDEX `idx_checkout_orders_status` ON `checkout_orders`(`order_status`);
CREATE INDEX `idx_checkout_orders_created` ON `checkout_orders`(`created_at`);
CREATE INDEX `idx_order_items_order_id` ON `order_items`(`order_id`);

-- Function to generate UUID (for sample data)
DELIMITER $$
CREATE FUNCTION IF NOT EXISTS GENERATE_UUID() RETURNS CHAR(36)
READS SQL DATA
DETERMINISTIC
BEGIN
    RETURN LOWER(CONCAT(
        LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFF)), 8, '0'), '-',
        LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 4, '0'), '-',
        '4', LPAD(HEX(FLOOR(RAND() * 0x0FFF)), 3, '0'), '-',
        HEX(FLOOR(RAND() * 4 + 8)), LPAD(HEX(FLOOR(RAND() * 0x0FFF)), 3, '0'), '-',
        LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFFFFFF)), 12, '0')
    ));
END$$
DELIMITER ;

-- Insert sample data for testing (using UUIDs)
SET @user1_id = GENERATE_UUID();
SET @user2_id = GENERATE_UUID();
SET @user3_id = GENERATE_UUID();

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `is_active`, `email_verified`) VALUES
(@user1_id, 'John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
(@user2_id, 'Jane', 'Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
(@user3_id, 'Test', 'User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 0);

-- Insert default preferences for sample users
INSERT INTO `user_preferences` (`preference_id`, `user_id`) VALUES
(GENERATE_UUID(), @user1_id),
(GENERATE_UUID(), @user2_id),
(GENERATE_UUID(), @user3_id);

-- Insert sample addresses
INSERT INTO `user_addresses` (`address_id`, `user_id`, `address_type`, `first_name`, `last_name`, `address_line_1`, `city`, `state`, `postal_code`, `is_default`) VALUES
(GENERATE_UUID(), @user1_id, 'home', 'John', 'Doe', '123 Main Street', 'Mumbai', 'Maharashtra', '400001', 1),
(GENERATE_UUID(), @user2_id, 'home', 'Jane', 'Smith', '456 Oak Avenue', 'Delhi', 'Delhi', '110001', 1);

COMMIT;

-- Success messages
SELECT 'User management tables created successfully!' as message;
SELECT 'Sample users created with password: password' as note;
SELECT 'Compatible with existing alphanutrition_db structure' as compatibility;
SELECT 'You can now test the authentication system' as instruction;
