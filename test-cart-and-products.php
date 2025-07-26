<?php
session_start();
require_once 'includes/db_connection.php';

echo "<h1>🧪 Test Cart and Products</h1>";

// Check products in database
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Products in Database</h3>";

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products");
    $stmt->execute();
    $productCount = $stmt->fetchColumn();
    
    echo "<p><strong>Total products:</strong> $productCount</p>";
    
    if ($productCount > 0) {
        $stmt = $pdo->prepare("SELECT product_id, name, price FROM products LIMIT 5");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>Product ID</th><th>Name</th><th>Price</th>";
        echo "</tr>";

        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>₹" . $product['price'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ <strong>No products found!</strong> This explains why orders have no items.";
        echo "<br>You need to add products to your database first.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}
echo "</div>";

// Check current cart session
echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Current Cart Session</h3>";

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    echo "<p>✅ Cart has " . count($_SESSION['cart']) . " items:</p>";
    echo "<pre style='background: #fff; padding: 15px; border-radius: 5px;'>";
    echo json_encode($_SESSION['cart'], JSON_PRETTY_PRINT);
    echo "</pre>";
} else {
    echo "<p>❌ Cart is empty or not set.</p>";
    
    // Create a test cart
    echo "<h4>Creating Test Cart:</h4>";
    
    if ($productCount > 0) {
        $stmt = $pdo->prepare("SELECT product_id FROM products LIMIT 1");
        $stmt->execute();
        $firstProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $_SESSION['cart'] = [
            $firstProduct['product_id'] . '_default' => [
                'product_id' => $firstProduct['product_id'],
                'variant_id' => null,
                'quantity' => 2,
                'added_at' => date('Y-m-d H:i:s')
            ]
        ];
        
        echo "<p>✅ Test cart created with product: " . $firstProduct['product_id'] . "</p>";
        echo "<pre style='background: #fff; padding: 15px; border-radius: 5px;'>";
        echo json_encode($_SESSION['cart'], JSON_PRETTY_PRINT);
        echo "</pre>";
    } else {
        echo "<p>❌ Cannot create test cart - no products available.</p>";
    }
}
echo "</div>";

// Test order processing logic
echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>Test Order Processing Logic</h3>";

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $orderItems = [];
    $totalAmount = 0;
    
    foreach ($_SESSION['cart'] as $cartKey => $cartItem) {
        $product_id = $cartItem['product_id'];
        $variant_id = $cartItem['variant_id'];
        $quantity = $cartItem['quantity'];
        
        // Get product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            $price = $product['price'];
            $itemTotal = $price * $quantity;
            $totalAmount += $itemTotal;
            
            $orderItems[] = [
                'product_id' => $product_id,
                'product_name' => $product['name'],
                'variant_id' => $variant_id,
                'variant_name' => null,
                'price' => $price,
                'quantity' => $quantity,
                'total' => $itemTotal
            ];
            
            echo "<p>✅ Processed: {$product['name']} - ₹{$price} x {$quantity} = ₹{$itemTotal}</p>";
        } else {
            echo "<p>❌ Product not found: {$product_id}</p>";
        }
    }
    
    echo "<h4>Final Order Items:</h4>";
    echo "<pre style='background: #fff; padding: 15px; border-radius: 5px;'>";
    echo json_encode($orderItems, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    echo "<p><strong>Total Amount: ₹{$totalAmount}</strong></p>";
    
    if (!empty($orderItems)) {
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Order processing would work!</strong><br>";
        echo "Found " . count($orderItems) . " valid items totaling ₹{$totalAmount}";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "❌ <strong>Order processing would fail!</strong><br>";
        echo "No valid items found in cart.";
        echo "</div>";
    }
    
} else {
    echo "<p>❌ Cannot test - no cart data available.</p>";
}
echo "</div>";

// Quick fix: Add sample products if none exist
if ($productCount == 0) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🚨 Critical Issue: No Products in Database</h3>";
    echo "<p>This is why orders have no items! You need products in your database.</p>";
    
    echo "<form method='post'>";
    echo "<button type='submit' name='add_sample_products' style='background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;'>Add Sample Products</button>";
    echo "</form>";
    echo "</div>";
}

// Handle adding sample products
if (isset($_POST['add_sample_products'])) {
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Adding Sample Products...</h3>";
    
    try {
        $sampleProducts = [
            [
                'product_id' => bin2hex(random_bytes(16)),
                'name' => 'Whey Protein Powder',
                'price' => 2500.00,
                'description' => 'Premium whey protein for muscle building'
            ],
            [
                'product_id' => bin2hex(random_bytes(16)),
                'name' => 'Creatine Monohydrate',
                'price' => 1200.00,
                'description' => 'Pure creatine for strength and power'
            ],
            [
                'product_id' => bin2hex(random_bytes(16)),
                'name' => 'BCAA Supplement',
                'price' => 1800.00,
                'description' => 'Branched-chain amino acids for recovery'
            ]
        ];
        
        foreach ($sampleProducts as $product) {
            $stmt = $pdo->prepare("
                INSERT INTO products (product_id, name, price, description, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $product['product_id'],
                $product['name'],
                $product['price'],
                $product['description']
            ]);
            
            echo "<p>✅ Added: {$product['name']} - ₹{$product['price']}</p>";
        }
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "✅ <strong>Sample products added successfully!</strong><br>";
        echo "You can now test the order flow properly.";
        echo "</div>";
        
        echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "❌ Error adding products: " . $e->getMessage();
        echo "</div>";
    }
    
    echo "</div>";
}

?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { border-collapse: collapse; width: 100%; margin: 15px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
pre { font-size: 12px; }
button:hover { opacity: 0.8; }
</style>
