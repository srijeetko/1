<?php
require_once 'includes/db_connection.php';

echo "<h2>Product Price Checker & Fixer</h2>";

// Check all products and their prices
try {
    $stmt = $pdo->query("
        SELECT product_id, name, price, 
               CASE 
                   WHEN price IS NULL THEN 'NULL'
                   WHEN price = 0 THEN 'ZERO'
                   WHEN price = '' THEN 'EMPTY'
                   WHEN price < 0 THEN 'NEGATIVE'
                   ELSE 'VALID'
               END as price_status
        FROM products 
        ORDER BY name
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Products and Their Prices:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Product ID</th><th>Product Name</th><th>Current Price</th><th>Status</th><th>Action</th>";
    echo "</tr>";
    
    $invalid_products = [];
    
    foreach ($products as $product) {
        $status_color = '';
        switch ($product['price_status']) {
            case 'VALID':
                $status_color = 'color: green;';
                break;
            case 'NULL':
            case 'ZERO':
            case 'EMPTY':
            case 'NEGATIVE':
                $status_color = 'color: red; font-weight: bold;';
                $invalid_products[] = $product;
                break;
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($product['product_id']) . "</td>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td>₹" . ($product['price'] ?? 'NULL') . "</td>";
        echo "<td style='$status_color'>" . $product['price_status'] . "</td>";
        
        if ($product['price_status'] !== 'VALID') {
            echo "<td><a href='?fix_product=" . urlencode($product['product_id']) . "' style='color: blue;'>Fix Price</a></td>";
        } else {
            echo "<td>✅ OK</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Show summary
    echo "<h3>Summary:</h3>";
    echo "<p><strong>Total Products:</strong> " . count($products) . "</p>";
    echo "<p><strong>Invalid Prices:</strong> " . count($invalid_products) . "</p>";
    
    if (!empty($invalid_products)) {
        echo "<h3>❌ Products with Invalid Prices:</h3>";
        echo "<ul>";
        foreach ($invalid_products as $product) {
            echo "<li><strong>" . htmlspecialchars($product['name']) . "</strong> - " . $product['price_status'] . "</li>";
        }
        echo "</ul>";
        
        echo "<h3>🔧 Quick Fix Options:</h3>";
        echo "<p><a href='?fix_all=1' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Fix All Invalid Prices</a></p>";
        echo "<p><em>This will set default prices for all products with invalid prices.</em></p>";
    } else {
        echo "<h3>✅ All product prices are valid!</h3>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking products: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Handle individual product fix
if (isset($_GET['fix_product'])) {
    $product_id = $_GET['fix_product'];
    
    // Get product details
    $stmt = $pdo->prepare("SELECT name FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        // Set a default price based on product name
        $default_price = 999.00; // Default price
        
        // Try to guess price based on product name
        $name = strtolower($product['name']);
        if (strpos($name, 'mass') !== false || strpos($name, 'gainer') !== false) {
            $default_price = 1299.00;
        } elseif (strpos($name, 'protein') !== false || strpos($name, 'whey') !== false) {
            $default_price = 1599.00;
        } elseif (strpos($name, 'creatine') !== false) {
            $default_price = 899.00;
        } elseif (strpos($name, 'vitamin') !== false || strpos($name, 'supplement') !== false) {
            $default_price = 699.00;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE products SET price = ? WHERE product_id = ?");
            $stmt->execute([$default_price, $product_id]);
            
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>✅ Price Fixed!</h4>";
            echo "<p><strong>" . htmlspecialchars($product['name']) . "</strong> price updated to ₹" . number_format($default_price, 2) . "</p>";
            echo "<p><a href='fix-product-prices.php'>Refresh Page</a></p>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>❌ Error fixing price:</h4>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    }
}

// Handle fix all
if (isset($_GET['fix_all'])) {
    try {
        // Get all products with invalid prices
        $stmt = $pdo->query("
            SELECT product_id, name 
            FROM products 
            WHERE price IS NULL OR price = 0 OR price = '' OR price < 0
        ");
        $invalid_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $fixed_count = 0;
        
        foreach ($invalid_products as $product) {
            // Set default price based on product name
            $default_price = 999.00;
            
            $name = strtolower($product['name']);
            if (strpos($name, 'mass') !== false || strpos($name, 'gainer') !== false) {
                $default_price = 1299.00;
            } elseif (strpos($name, 'protein') !== false || strpos($name, 'whey') !== false) {
                $default_price = 1599.00;
            } elseif (strpos($name, 'creatine') !== false) {
                $default_price = 899.00;
            } elseif (strpos($name, 'vitamin') !== false || strpos($name, 'supplement') !== false) {
                $default_price = 699.00;
            }
            
            $stmt = $pdo->prepare("UPDATE products SET price = ? WHERE product_id = ?");
            $stmt->execute([$default_price, $product['product_id']]);
            $fixed_count++;
        }
        
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ All Prices Fixed!</h4>";
        echo "<p>Updated prices for <strong>$fixed_count</strong> products.</p>";
        echo "<p><a href='fix-product-prices.php'>Refresh Page</a></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>❌ Error fixing prices:</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
}

echo "<hr>";
echo "<h3>🔗 Quick Links:</h3>";
echo "<p>";
echo "<a href='products.php'>View Products</a> | ";
echo "<a href='cart.php'>View Cart</a> | ";
echo "<a href='checkout.php'>Try Checkout</a> | ";
echo "<a href='debug-order-total.php'>Debug Order Total</a>";
echo "</p>";
?>
