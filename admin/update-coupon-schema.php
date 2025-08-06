<?php
// Update coupon table schema with additional fields
session_start();
include '../includes/db_connection.php';

echo "<h1>Coupon Schema Update</h1>";

try {
    // Check current coupon table structure
    echo "<h2>Current Coupon Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE coupons");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if additional columns exist
    $existingColumns = array_column($columns, 'Field');
    $requiredColumns = [
        'minimum_order_amount',
        'usage_count',
        'created_at',
        'updated_at',
        'description'
    ];
    
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (!empty($missingColumns)) {
        echo "<h2>Adding Missing Columns:</h2>";
        
        // Add minimum_order_amount column
        if (in_array('minimum_order_amount', $missingColumns)) {
            $pdo->exec("ALTER TABLE coupons ADD COLUMN minimum_order_amount DECIMAL(10,2) DEFAULT 0.00 AFTER discount_value");
            echo "✅ Added minimum_order_amount column<br>";
        }
        
        // Add usage_count column
        if (in_array('usage_count', $missingColumns)) {
            $pdo->exec("ALTER TABLE coupons ADD COLUMN usage_count INT DEFAULT 0 AFTER usage_limit");
            echo "✅ Added usage_count column<br>";
        }
        
        // Add created_at column
        if (in_array('created_at', $missingColumns)) {
            $pdo->exec("ALTER TABLE coupons ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_active");
            echo "✅ Added created_at column<br>";
        }
        
        // Add updated_at column
        if (in_array('updated_at', $missingColumns)) {
            $pdo->exec("ALTER TABLE coupons ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
            echo "✅ Added updated_at column<br>";
        }
        
        // Add description column
        if (in_array('description', $missingColumns)) {
            $pdo->exec("ALTER TABLE coupons ADD COLUMN description TEXT AFTER code");
            echo "✅ Added description column<br>";
        }
        
    } else {
        echo "<h2>✅ All required columns already exist!</h2>";
    }
    
    // Show updated table structure
    echo "<h2>Updated Coupon Table Structure:</h2>";
    $stmt = $pdo->query("DESCRIBE coupons");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Create some sample coupons for testing
    echo "<h2>Creating Sample Coupons:</h2>";
    
    $sampleCoupons = [
        [
            'coupon_id' => bin2hex(random_bytes(16)),
            'code' => 'WELCOME10',
            'description' => 'Welcome discount for new customers',
            'discount_type' => 'percentage',
            'discount_value' => 10.00,
            'minimum_order_amount' => 500.00,
            'usage_limit' => 100,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'is_active' => 1
        ],
        [
            'coupon_id' => bin2hex(random_bytes(16)),
            'code' => 'SAVE50',
            'description' => 'Flat ₹50 off on orders above ₹1000',
            'discount_type' => 'fixed',
            'discount_value' => 50.00,
            'minimum_order_amount' => 1000.00,
            'usage_limit' => 50,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+15 days')),
            'is_active' => 1
        ],
        [
            'coupon_id' => bin2hex(random_bytes(16)),
            'code' => 'MEGA20',
            'description' => '20% off on all products - Limited time',
            'discount_type' => 'percentage',
            'discount_value' => 20.00,
            'minimum_order_amount' => 0.00,
            'usage_limit' => 200,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
            'is_active' => 1
        ]
    ];
    
    foreach ($sampleCoupons as $coupon) {
        // Check if coupon already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM coupons WHERE code = ?");
        $stmt->execute([$coupon['code']]);
        
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("
                INSERT INTO coupons (coupon_id, code, description, discount_type, discount_value, minimum_order_amount, usage_limit, usage_count, expires_at, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
            ");
            
            $result = $stmt->execute([
                $coupon['coupon_id'],
                $coupon['code'],
                $coupon['description'],
                $coupon['discount_type'],
                $coupon['discount_value'],
                $coupon['minimum_order_amount'],
                $coupon['usage_limit'],
                $coupon['expires_at'],
                $coupon['is_active']
            ]);
            
            if ($result) {
                echo "✅ Created sample coupon: " . $coupon['code'] . "<br>";
            } else {
                echo "❌ Failed to create coupon: " . $coupon['code'] . "<br>";
            }
        } else {
            echo "ℹ️ Coupon already exists: " . $coupon['code'] . "<br>";
        }
    }
    
    echo "<h2>✅ Coupon schema update completed successfully!</h2>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error updating coupon schema:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
