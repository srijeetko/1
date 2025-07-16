<?php
require_once 'includes/db_connection.php';

echo "<h2>Database Table Structure Check</h2>";

// Check checkout_orders table structure
try {
    $stmt = $pdo->query("DESCRIBE checkout_orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>✅ checkout_orders table structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $has_address_fields = false;
    $has_address_id = false;
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
        
        // Check for address-related fields
        if (in_array($column['Field'], ['address', 'first_name', 'last_name', 'email', 'phone', 'city', 'state', 'pincode'])) {
            $has_address_fields = true;
        }
        if ($column['Field'] === 'address_id') {
            $has_address_id = true;
        }
    }
    
    echo "</table>";
    
    echo "<h3>Analysis:</h3>";
    if ($has_address_fields) {
        echo "<p>✅ <strong>Extended structure detected</strong> - Table has individual address fields (address, first_name, etc.)</p>";
        echo "<p>✅ The current process-order.php should work with this structure.</p>";
    } elseif ($has_address_id) {
        echo "<p>⚠️ <strong>Original structure detected</strong> - Table uses address_id reference</p>";
        echo "<p>❌ The current process-order.php won't work with this structure.</p>";
        echo "<p><strong>Solution:</strong> Need to run the table update script.</p>";
    } else {
        echo "<p>❓ <strong>Unknown structure</strong> - Please check the table manually.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking checkout_orders table: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>The table might not exist. You need to run the setup script.</p>";
}

// Check if addresses table exists
try {
    $stmt = $pdo->query("DESCRIBE addresses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>✅ addresses table exists with " . count($columns) . " columns</h3>";
} catch (Exception $e) {
    echo "<h3>❌ addresses table doesn't exist</h3>";
}

// Check if user_addresses table exists
try {
    $stmt = $pdo->query("DESCRIBE user_addresses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>✅ user_addresses table exists with " . count($columns) . " columns</h3>";
} catch (Exception $e) {
    echo "<h3>❌ user_addresses table doesn't exist</h3>";
}

echo "<hr>";
echo "<h3>🔧 Solutions:</h3>";

if (!$has_address_fields && $has_address_id) {
    echo "<h4>Option 1: Update Table Structure (Recommended)</h4>";
    echo "<p>Run this SQL to add the missing columns:</p>";
    echo "<textarea style='width: 100%; height: 200px; font-family: monospace;'>";
    echo "-- Add missing columns to checkout_orders table
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
ADD COLUMN subtotal DECIMAL(10,2) DEFAULT 0.00 AFTER pincode,
ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0.00 AFTER subtotal,
ADD COLUMN tax_amount DECIMAL(10,2) DEFAULT 0.00 AFTER shipping_cost,
ADD COLUMN payment_method ENUM('cod', 'razorpay', 'cashfree', 'online') DEFAULT 'cod' AFTER tax_amount,
ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' AFTER payment_method,
ADD COLUMN order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending' AFTER payment_status,
ADD COLUMN notes TEXT AFTER order_status,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;";
    echo "</textarea>";
    
    echo "<h4>Option 2: Recreate Table</h4>";
    echo "<p>Drop and recreate the table with the correct structure:</p>";
    echo "<p><a href='?recreate_table=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>⚠️ Recreate Table (Will lose existing orders!)</a></p>";
}

// Handle table recreation
if (isset($_GET['recreate_table'])) {
    try {
        // Drop existing table
        $pdo->exec("DROP TABLE IF EXISTS checkout_orders");
        
        // Create new table with correct structure
        $pdo->exec("
            CREATE TABLE checkout_orders (
                order_id CHAR(36) NOT NULL PRIMARY KEY,
                order_number VARCHAR(50) UNIQUE NOT NULL,
                user_id CHAR(36) NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                address TEXT NOT NULL,
                city VARCHAR(100) NOT NULL,
                state VARCHAR(100) NOT NULL,
                pincode VARCHAR(10) NOT NULL,
                subtotal DECIMAL(10,2) DEFAULT 0.00,
                shipping_cost DECIMAL(10,2) DEFAULT 0.00,
                tax_amount DECIMAL(10,2) DEFAULT 0.00,
                total_amount DECIMAL(10,2) NOT NULL,
                payment_method ENUM('cod', 'razorpay', 'cashfree', 'online') DEFAULT 'cod',
                payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
                order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci
        ");
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ Table Recreated Successfully!</h4>";
        echo "<p>The checkout_orders table has been recreated with the correct structure.</p>";
        echo "<p><a href='check-table-structure.php'>Refresh Page</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>❌ Error recreating table:</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='checkout.php'>Try Checkout</a> | <a href='fix-product-prices.php'>Fix Product Prices</a></p>";
?>
