<?php
session_start();
require_once 'includes/db_connection.php';

echo "<h2>Order Total Debug</h2>";

// Check if cart exists
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "<p>❌ Cart is empty</p>";
    echo "<p><a href='products.php'>Add products to cart</a></p>";
    exit();
}

$cartItems = $_SESSION['cart'];

echo "<h3>Cart Contents:</h3>";
echo "<pre>";
print_r($cartItems);
echo "</pre>";

// Extract product IDs
$productIds = [];
foreach ($cartItems as $cartKey => $item) {
    $productId = $item['product_id'];
    if (!in_array($productId, $productIds)) {
        $productIds[] = $productId;
    }
}

echo "<h3>Product IDs to lookup:</h3>";
echo "<ul>";
foreach ($productIds as $id) {
    echo "<li>" . htmlspecialchars($id) . "</li>";
}
echo "</ul>";

// Get products from database
if (!empty($productIds)) {
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    $sql = "
        SELECT p.product_id, p.name, p.price, 
               COALESCE(pi.image_url, '') as image_url
        FROM products p
        LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
        WHERE p.product_id IN ($placeholders)
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($productIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Products found in database:</h3>";
    if (empty($products)) {
        echo "<p>❌ No products found!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Product ID</th><th>Name</th><th>Price</th><th>Price Type</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . htmlspecialchars($product['price']) . "</td>";
            echo "<td>" . gettype($product['price']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Calculate total like the order processing does
    echo "<h3>Order Total Calculation:</h3>";
    
    $productLookup = [];
    foreach ($products as $product) {
        $productLookup[$product['product_id']] = $product;
    }
    
    $totalAmount = 0;
    $orderItems = [];
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Cart Key</th><th>Product ID</th><th>Variant ID</th><th>Quantity</th><th>Price</th><th>Item Total</th><th>Status</th></tr>";
    
    foreach ($cartItems as $cartKey => $cartItem) {
        $product_id = $cartItem['product_id'];
        $variant_id = $cartItem['variant_id'] ?? 'default';
        $quantity = $cartItem['quantity'];
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($cartKey) . "</td>";
        echo "<td>" . htmlspecialchars($product_id) . "</td>";
        echo "<td>" . htmlspecialchars($variant_id) . "</td>";
        echo "<td>" . htmlspecialchars($quantity) . "</td>";
        
        if (isset($productLookup[$product_id])) {
            $product = $productLookup[$product_id];
            $price = floatval($product['price']);
            
            // Handle variant pricing
            if ($variant_id && $variant_id !== 'default') {
                try {
                    $stmt = $pdo->prepare("SELECT price FROM product_variants WHERE variant_id = ?");
                    $stmt->execute([$variant_id]);
                    $variant = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($variant && isset($variant['price'])) {
                        $price = floatval($variant['price']);
                        echo "<td>" . $price . " (variant)</td>";
                    } else {
                        echo "<td>" . $price . " (base - variant not found)</td>";
                    }
                } catch (Exception $e) {
                    echo "<td>" . $price . " (base - variant error)</td>";
                }
            } else {
                echo "<td>" . $price . " (base)</td>";
            }
            
            $quantity = intval($quantity);
            $itemTotal = $price * $quantity;
            $totalAmount += $itemTotal;
            
            echo "<td>" . $itemTotal . "</td>";
            echo "<td>✅ Added</td>";
            
            $orderItems[] = [
                'product_id' => $product_id,
                'product_name' => $product['name'],
                'variant_id' => $variant_id,
                'price' => $price,
                'quantity' => $quantity,
                'total' => $itemTotal
            ];
        } else {
            echo "<td>-</td>";
            echo "<td>-</td>";
            echo "<td>❌ Product not found</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>Final Results:</h3>";
    echo "<p><strong>Total Amount:</strong> ₹" . number_format($totalAmount, 2) . "</p>";
    echo "<p><strong>Order Items Count:</strong> " . count($orderItems) . "</p>";
    echo "<p><strong>Valid for Order:</strong> " . ($totalAmount > 0 ? "✅ YES" : "❌ NO") . "</p>";
    
    if ($totalAmount <= 0) {
        echo "<h3>❌ Problem Analysis:</h3>";
        echo "<ul>";
        echo "<li>Check if product prices are set correctly in database</li>";
        echo "<li>Check if product IDs in cart match database</li>";
        echo "<li>Check if quantities are valid numbers</li>";
        echo "<li>Check if variant prices exist (if using variants)</li>";
        echo "</ul>";
    }
}

echo "<hr>";
echo "<p><a href='cart.php'>View Cart</a> | <a href='checkout.php'>Try Checkout</a> | <a href='products.php'>Products</a></p>";
?>
