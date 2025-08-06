<?php
session_start();
include 'includes/db_connection.php';

echo "<h1>Coupon System Test</h1>";

try {
    // Test 1: Check if coupon table exists and has data
    echo "<h2>1. Coupon Table Test</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM coupons");
    $count = $stmt->fetch()['count'];
    echo "✅ Coupon table exists with $count coupons<br>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT * FROM coupons LIMIT 3");
        $coupons = $stmt->fetchAll();
        
        echo "<h3>Sample Coupons:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Code</th><th>Type</th><th>Value</th><th>Min Order</th><th>Status</th><th>Expires</th></tr>";
        foreach ($coupons as $coupon) {
            $status = $coupon['is_active'] ? 'Active' : 'Inactive';
            if (strtotime($coupon['expires_at']) < time()) {
                $status = 'Expired';
            }
            echo "<tr>";
            echo "<td>" . htmlspecialchars($coupon['code']) . "</td>";
            echo "<td>" . ucfirst($coupon['discount_type']) . "</td>";
            echo "<td>" . $coupon['discount_value'] . ($coupon['discount_type'] === 'percentage' ? '%' : ' ₹') . "</td>";
            echo "<td>₹" . number_format($coupon['minimum_order_amount'], 2) . "</td>";
            echo "<td>$status</td>";
            echo "<td>" . date('M j, Y', strtotime($coupon['expires_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 2: API Endpoint Test
    echo "<h2>2. API Endpoint Test</h2>";
    
    // Test coupon validation
    if ($count > 0) {
        $stmt = $pdo->query("SELECT code FROM coupons WHERE is_active = 1 AND expires_at > NOW() LIMIT 1");
        $testCoupon = $stmt->fetch();
        
        if ($testCoupon) {
            $testCode = $testCoupon['code'];
            $testAmount = 1000; // Test with ₹1000 order
            
            echo "<h3>Testing coupon validation for code: $testCode</h3>";
            
            // Simulate API call
            $apiUrl = "admin/api/coupon-api.php?action=validate&code=" . urlencode($testCode) . "&amount=$testAmount";
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'Content-Type: application/json'
                ]
            ]);
            
            $response = @file_get_contents($apiUrl, false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                if ($data && $data['success']) {
                    echo "✅ API validation successful<br>";
                    echo "- Discount Amount: ₹" . number_format($data['discount_amount'], 2) . "<br>";
                    echo "- Final Amount: ₹" . number_format($data['final_amount'], 2) . "<br>";
                } else {
                    echo "❌ API validation failed: " . ($data['message'] ?? 'Unknown error') . "<br>";
                }
            } else {
                echo "❌ Could not connect to API endpoint<br>";
            }
        } else {
            echo "ℹ️ No active coupons found for testing<br>";
        }
    }
    
    // Test 3: Database Schema Test
    echo "<h2>3. Database Schema Test</h2>";
    $stmt = $pdo->query("DESCRIBE coupons");
    $columns = $stmt->fetchAll();
    
    $requiredColumns = [
        'coupon_id', 'code', 'description', 'discount_type', 'discount_value',
        'minimum_order_amount', 'usage_limit', 'usage_count', 'expires_at', 'is_active'
    ];
    
    $existingColumns = array_column($columns, 'Field');
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (empty($missingColumns)) {
        echo "✅ All required columns exist<br>";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missingColumns) . "<br>";
    }
    
    // Test 4: Checkout Integration Test
    echo "<h2>4. Checkout Integration Test</h2>";
    
    // Check if coupon handler JS exists
    if (file_exists('js/coupon-handler.js')) {
        echo "✅ Coupon handler JavaScript file exists<br>";
    } else {
        echo "❌ Coupon handler JavaScript file missing<br>";
    }
    
    // Check if checkout.php has been updated
    $checkoutContent = file_get_contents('checkout.php');
    if (strpos($checkoutContent, 'coupon-section') !== false) {
        echo "✅ Checkout page has coupon section<br>";
    } else {
        echo "❌ Checkout page missing coupon section<br>";
    }
    
    if (strpos($checkoutContent, 'coupon-handler.js') !== false) {
        echo "✅ Checkout page includes coupon handler script<br>";
    } else {
        echo "❌ Checkout page missing coupon handler script<br>";
    }
    
    // Test 5: Admin Panel Test
    echo "<h2>5. Admin Panel Test</h2>";
    
    if (file_exists('admin/coupons.php')) {
        echo "✅ Admin coupon management page exists<br>";
    } else {
        echo "❌ Admin coupon management page missing<br>";
    }
    
    // Check if sidebar has been updated
    $sidebarContent = file_get_contents('admin/includes/admin-sidebar.php');
    if (strpos($sidebarContent, 'coupons.php') !== false) {
        echo "✅ Admin sidebar includes coupon management link<br>";
    } else {
        echo "❌ Admin sidebar missing coupon management link<br>";
    }
    
    // Test 6: Order Processing Test
    echo "<h2>6. Order Processing Test</h2>";
    
    $processOrderContent = file_get_contents('process-order.php');
    if (strpos($processOrderContent, 'coupon_id') !== false) {
        echo "✅ Order processing includes coupon handling<br>";
    } else {
        echo "❌ Order processing missing coupon handling<br>";
    }
    
    // Check if checkout_orders table has coupon_id column
    $stmt = $pdo->query("DESCRIBE checkout_orders");
    $orderColumns = $stmt->fetchAll();
    $orderColumnNames = array_column($orderColumns, 'Field');
    
    if (in_array('coupon_id', $orderColumnNames)) {
        echo "✅ Orders table has coupon_id column<br>";
    } else {
        echo "❌ Orders table missing coupon_id column<br>";
        echo "<p><strong>Fix:</strong> Run this SQL to add the column:</p>";
        echo "<code>ALTER TABLE checkout_orders ADD COLUMN coupon_id CHAR(36) NULL AFTER shipping_method_id;</code>";
    }
    
    // Test 7: Sample Coupon Creation Test
    echo "<h2>7. Sample Coupon Creation Test</h2>";
    
    $testCouponCode = 'TEST' . date('His');
    $testCouponId = bin2hex(random_bytes(16));
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO coupons (coupon_id, code, description, discount_type, discount_value, minimum_order_amount, usage_limit, usage_count, expires_at, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)
        ");
        
        $result = $stmt->execute([
            $testCouponId,
            $testCouponCode,
            'Test coupon created by system test',
            'percentage',
            15.00,
            500.00,
            10,
            date('Y-m-d H:i:s', strtotime('+1 day')),
            1
        ]);
        
        if ($result) {
            echo "✅ Successfully created test coupon: $testCouponCode<br>";
            
            // Clean up test coupon
            $stmt = $pdo->prepare("DELETE FROM coupons WHERE coupon_id = ?");
            $stmt->execute([$testCouponId]);
            echo "✅ Test coupon cleaned up<br>";
        } else {
            echo "❌ Failed to create test coupon<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error creating test coupon: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>✅ Coupon System Test Complete!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Visit <a href='admin/coupons.php'>Admin Coupon Management</a> to create and manage coupons</li>";
    echo "<li>Add some products to cart and test coupon application on <a href='checkout.php'>Checkout Page</a></li>";
    echo "<li>Test different coupon types (percentage vs fixed amount)</li>";
    echo "<li>Test minimum order amount restrictions</li>";
    echo "<li>Test usage limits and expiry dates</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error during testing:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        line-height: 1.6;
    }
    
    h1 {
        color: #2c3e50;
        border-bottom: 3px solid #3498db;
        padding-bottom: 10px;
    }
    
    h2 {
        color: #34495e;
        margin-top: 30px;
        padding: 10px;
        background: #ecf0f1;
        border-left: 4px solid #3498db;
    }
    
    h3 {
        color: #2c3e50;
        margin-top: 20px;
    }
    
    table {
        width: 100%;
        margin: 15px 0;
        border-collapse: collapse;
    }
    
    th, td {
        padding: 10px;
        text-align: left;
        border: 1px solid #bdc3c7;
    }
    
    th {
        background: #3498db;
        color: white;
        font-weight: bold;
    }
    
    tr:nth-child(even) {
        background: #f8f9fa;
    }
    
    code {
        background: #2c3e50;
        color: #ecf0f1;
        padding: 10px;
        display: block;
        border-radius: 4px;
        margin: 10px 0;
        font-family: 'Courier New', monospace;
    }
    
    ul {
        background: #e8f5e8;
        padding: 15px 30px;
        border-radius: 5px;
        border-left: 4px solid #27ae60;
    }
    
    a {
        color: #3498db;
        text-decoration: none;
        font-weight: bold;
    }
    
    a:hover {
        text-decoration: underline;
    }
</style>
