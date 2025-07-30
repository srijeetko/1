-- =====================================================
-- COMPREHENSIVE RETURN & REFUND MANAGEMENT SYSTEM
-- =====================================================
-- This script creates a complete return and refund management system
-- for the Alpha Nutrition e-commerce platform

-- Drop existing tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS `refund_transactions`;
DROP TABLE IF EXISTS `return_status_history`;
DROP TABLE IF EXISTS `return_items`;
DROP TABLE IF EXISTS `return_requests`;
DROP TABLE IF EXISTS `return_policies`;
DROP TABLE IF EXISTS `return_reasons`;

-- =====================================================
-- 1. RETURN REASONS TABLE
-- =====================================================
CREATE TABLE `return_reasons` (
    `reason_id` CHAR(36) NOT NULL PRIMARY KEY,
    `reason_code` VARCHAR(50) NOT NULL UNIQUE,
    `reason_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `requires_approval` TINYINT(1) DEFAULT 0,
    `auto_approve` TINYINT(1) DEFAULT 0,
    `refund_percentage` DECIMAL(5,2) DEFAULT 100.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- 2. RETURN POLICIES TABLE
-- =====================================================
CREATE TABLE `return_policies` (
    `policy_id` CHAR(36) NOT NULL PRIMARY KEY,
    `policy_name` VARCHAR(100) NOT NULL,
    `category_id` CHAR(36) NULL,
    `product_id` CHAR(36) NULL,
    `return_window_days` INT NOT NULL DEFAULT 30,
    `requires_original_packaging` TINYINT(1) DEFAULT 1,
    `requires_receipt` TINYINT(1) DEFAULT 1,
    `shipping_cost_responsibility` ENUM('customer', 'company', 'shared') DEFAULT 'customer',
    `restocking_fee_percentage` DECIMAL(5,2) DEFAULT 0.00,
    `conditions` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_category_id` (`category_id`),
    INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- 3. RETURN REQUESTS TABLE
-- =====================================================
CREATE TABLE `return_requests` (
    `return_id` CHAR(36) NOT NULL PRIMARY KEY,
    `return_number` VARCHAR(50) NOT NULL UNIQUE,
    `order_id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NULL,
    `customer_email` VARCHAR(255) NOT NULL,
    `customer_phone` VARCHAR(20),
    `return_reason_id` CHAR(36) NOT NULL,
    `custom_reason` TEXT,
    `return_status` ENUM('pending', 'approved', 'rejected', 'items_received', 'inspecting', 'processed', 'refunded', 'cancelled') DEFAULT 'pending',
    `total_return_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `restocking_fee` DECIMAL(10,2) DEFAULT 0.00,
    `shipping_cost` DECIMAL(10,2) DEFAULT 0.00,
    `pickup_address` TEXT,
    `pickup_scheduled_date` DATETIME NULL,
    `pickup_actual_date` DATETIME NULL,
    `items_received_date` DATETIME NULL,
    `inspection_notes` TEXT,
    `admin_notes` TEXT,
    `customer_notes` TEXT,
    `processed_by` CHAR(36) NULL,
    `processed_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `checkout_orders`(`order_id`) ON DELETE CASCADE,
    FOREIGN KEY (`return_reason_id`) REFERENCES `return_reasons`(`reason_id`) ON DELETE RESTRICT,
    INDEX `idx_return_status` (`return_status`),
    INDEX `idx_customer_email` (`customer_email`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- 4. RETURN ITEMS TABLE
-- =====================================================
CREATE TABLE `return_items` (
    `return_item_id` CHAR(36) NOT NULL PRIMARY KEY,
    `return_id` CHAR(36) NOT NULL,
    `order_item_id` CHAR(36) NOT NULL,
    `product_id` CHAR(36) NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `variant_id` CHAR(36) NULL,
    `variant_name` VARCHAR(100) NULL,
    `quantity_ordered` INT NOT NULL,
    `quantity_returned` INT NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `condition_received` ENUM('new', 'good', 'fair', 'poor', 'damaged') NULL,
    `inspection_notes` TEXT,
    `refund_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `is_approved` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`return_id`) REFERENCES `return_requests`(`return_id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`order_item_id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE RESTRICT,
    INDEX `idx_return_id` (`return_id`),
    INDEX `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- 5. RETURN STATUS HISTORY TABLE
-- =====================================================
CREATE TABLE `return_status_history` (
    `history_id` CHAR(36) NOT NULL PRIMARY KEY,
    `return_id` CHAR(36) NOT NULL,
    `previous_status` VARCHAR(50) NULL,
    `new_status` VARCHAR(50) NOT NULL,
    `changed_by` CHAR(36) NULL,
    `change_reason` TEXT,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`return_id`) REFERENCES `return_requests`(`return_id`) ON DELETE CASCADE,
    INDEX `idx_return_id` (`return_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- 6. REFUND TRANSACTIONS TABLE
-- =====================================================
CREATE TABLE `refund_transactions` (
    `refund_id` CHAR(36) NOT NULL PRIMARY KEY,
    `return_id` CHAR(36) NOT NULL,
    `original_transaction_id` CHAR(36) NULL,
    `refund_method` ENUM('original_payment', 'bank_transfer', 'store_credit', 'cash') DEFAULT 'original_payment',
    `refund_amount` DECIMAL(10,2) NOT NULL,
    `processing_fee` DECIMAL(10,2) DEFAULT 0.00,
    `net_refund_amount` DECIMAL(10,2) NOT NULL,
    `refund_status` ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    `gateway_refund_id` VARCHAR(100) NULL,
    `gateway_response` JSON NULL,
    `bank_details` JSON NULL,
    `processed_by` CHAR(36) NULL,
    `processed_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `failure_reason` TEXT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`return_id`) REFERENCES `return_requests`(`return_id`) ON DELETE CASCADE,
    FOREIGN KEY (`original_transaction_id`) REFERENCES `payment_transactions`(`transaction_id`) ON DELETE SET NULL,
    INDEX `idx_return_id` (`return_id`),
    INDEX `idx_refund_status` (`refund_status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Default Return Reasons
INSERT INTO `return_reasons` (`reason_id`, `reason_code`, `reason_name`, `description`, `requires_approval`, `auto_approve`, `refund_percentage`) VALUES
(REPLACE(UUID(), '-', ''), 'DEFECTIVE', 'Defective Product', 'Product received was defective or damaged', 0, 1, 100.00),
(REPLACE(UUID(), '-', ''), 'WRONG_ITEM', 'Wrong Item Received', 'Received different product than ordered', 0, 1, 100.00),
(REPLACE(UUID(), '-', ''), 'NOT_AS_DESCRIBED', 'Not as Described', 'Product does not match description', 1, 0, 100.00),
(REPLACE(UUID(), '-', ''), 'CHANGED_MIND', 'Changed Mind', 'Customer no longer wants the product', 1, 0, 90.00),
(REPLACE(UUID(), '-', ''), 'SIZE_ISSUE', 'Size/Fit Issue', 'Product size or fit is not suitable', 1, 0, 95.00),
(REPLACE(UUID(), '-', ''), 'QUALITY_ISSUE', 'Quality Issue', 'Product quality is below expectations', 1, 0, 100.00),
(REPLACE(UUID(), '-', ''), 'EXPIRED', 'Expired Product', 'Product received was expired', 0, 1, 100.00),
(REPLACE(UUID(), '-', ''), 'ALLERGIC_REACTION', 'Allergic Reaction', 'Product caused allergic reaction', 0, 1, 100.00);

-- Default Return Policy (General)
INSERT INTO `return_policies` (`policy_id`, `policy_name`, `return_window_days`, `requires_original_packaging`, `requires_receipt`, `shipping_cost_responsibility`, `restocking_fee_percentage`, `conditions`) VALUES
(REPLACE(UUID(), '-', ''), 'General Return Policy', 30, 1, 1, 'customer', 0.00, 'Products must be in original condition with all packaging and labels intact. Supplements must be unopened unless defective.');

-- =====================================================
-- CREATE INDEXES FOR PERFORMANCE
-- =====================================================
CREATE INDEX `idx_return_requests_status_date` ON `return_requests`(`return_status`, `created_at`);
CREATE INDEX `idx_return_items_product_date` ON `return_items`(`product_id`, `created_at`);
CREATE INDEX `idx_refund_transactions_status_date` ON `refund_transactions`(`refund_status`, `created_at`);

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- Return Summary View
CREATE VIEW `return_summary_view` AS
SELECT 
    rr.return_id,
    rr.return_number,
    rr.order_id,
    co.order_number,
    rr.customer_email,
    rr.return_status,
    rr.total_return_amount,
    rr.refund_amount,
    rr.created_at,
    rr.processed_at,
    rrs.reason_name,
    COUNT(ri.return_item_id) as item_count
FROM return_requests rr
LEFT JOIN checkout_orders co ON rr.order_id = co.order_id
LEFT JOIN return_reasons rrs ON rr.return_reason_id = rrs.reason_id
LEFT JOIN return_items ri ON rr.return_id = ri.return_id
GROUP BY rr.return_id;

-- =====================================================
-- SUCCESS MESSAGE
-- =====================================================
SELECT 'Return & Refund Management System created successfully!' as message;
