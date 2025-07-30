<?php
echo "<h1>Creating Return & Refund Tables</h1>";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=localhost;dbname=alphanutrition_db", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Step 1: Creating Return Reasons Table</h2>";
    
    $sql = "CREATE TABLE IF NOT EXISTS `return_reasons` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    $pdo->exec($sql);
    echo "✅ Return reasons table created<br>";
    
    echo "<h2>Step 2: Creating Return Policies Table</h2>";
    
    $sql = "CREATE TABLE IF NOT EXISTS `return_policies` (
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
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    $pdo->exec($sql);
    echo "✅ Return policies table created<br>";
    
    echo "<h2>Step 3: Creating Return Requests Table</h2>";
    
    $sql = "CREATE TABLE IF NOT EXISTS `return_requests` (
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
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    $pdo->exec($sql);
    echo "✅ Return requests table created<br>";
    
    echo "<h2>Step 4: Creating Return Items Table</h2>";
    
    $sql = "CREATE TABLE IF NOT EXISTS `return_items` (
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
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    $pdo->exec($sql);
    echo "✅ Return items table created<br>";
    
    echo "<h2>Step 5: Creating Return Status History Table</h2>";
    
    $sql = "CREATE TABLE IF NOT EXISTS `return_status_history` (
        `history_id` CHAR(36) NOT NULL PRIMARY KEY,
        `return_id` CHAR(36) NOT NULL,
        `previous_status` VARCHAR(50) NULL,
        `new_status` VARCHAR(50) NOT NULL,
        `changed_by` CHAR(36) NULL,
        `change_reason` TEXT,
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    $pdo->exec($sql);
    echo "✅ Return status history table created<br>";
    
    echo "<h2>Step 6: Creating Refund Transactions Table</h2>";
    
    $sql = "CREATE TABLE IF NOT EXISTS `refund_transactions` (
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
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    $pdo->exec($sql);
    echo "✅ Refund transactions table created<br>";
    
    echo "<h2>Step 7: Inserting Sample Data</h2>";
    
    // Insert return reasons
    $reasons = [
        ['DEFECTIVE', 'Defective Product', 'Product received was defective or damaged', 0, 1, 100.00],
        ['WRONG_ITEM', 'Wrong Item Received', 'Received different product than ordered', 0, 1, 100.00],
        ['NOT_AS_DESCRIBED', 'Not as Described', 'Product does not match description', 1, 0, 100.00],
        ['CHANGED_MIND', 'Changed Mind', 'Customer no longer wants the product', 1, 0, 90.00],
        ['SIZE_ISSUE', 'Size/Fit Issue', 'Product size or fit is not suitable', 1, 0, 95.00],
        ['QUALITY_ISSUE', 'Quality Issue', 'Product quality is below expectations', 1, 0, 100.00],
        ['EXPIRED', 'Expired Product', 'Product received was expired', 0, 1, 100.00],
        ['ALLERGIC_REACTION', 'Allergic Reaction', 'Product caused allergic reaction', 0, 1, 100.00]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO return_reasons (reason_id, reason_code, reason_name, description, requires_approval, auto_approve, refund_percentage) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($reasons as $reason) {
        $reasonId = str_replace('-', '', bin2hex(random_bytes(18)));
        $stmt->execute(array_merge([$reasonId], $reason));
    }
    
    echo "✅ Sample return reasons inserted<br>";
    
    // Insert default return policy
    $policyId = str_replace('-', '', bin2hex(random_bytes(18)));
    $stmt = $pdo->prepare("INSERT IGNORE INTO return_policies (policy_id, policy_name, return_window_days, requires_original_packaging, requires_receipt, shipping_cost_responsibility, restocking_fee_percentage, conditions) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$policyId, 'General Return Policy', 30, 1, 1, 'customer', 0.00, 'Products must be in original condition with all packaging and labels intact. Supplements must be unopened unless defective.']);
    
    echo "✅ Default return policy inserted<br>";
    
    echo "<h2>Verification</h2>";
    
    // Verify tables
    $tables = ['return_reasons', 'return_policies', 'return_requests', 'return_items', 'return_status_history', 'refund_transactions'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' not found<br>";
        }
    }
    
    // Check data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM return_reasons");
    $result = $stmt->fetch();
    echo "✅ Return reasons: {$result['count']} records<br>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM return_policies");
    $result = $stmt->fetch();
    echo "✅ Return policies: {$result['count']} records<br>";
    
    echo "<br><strong>✅ Return & Refund Management System created successfully!</strong>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
