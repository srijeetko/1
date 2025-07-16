<?php
session_start();
require_once 'includes/db_connection.php';

echo "<h2>Cart Price Debug</h2>";

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "<p>Cart is empty</p>";
    exit;
}

echo "<h3>Cart Contents:</h3>";
echo "<pre>" . print_r($_SESSION['cart'], true) . "</pre>";

$cartItems = $_SESSION['cart'];
$totalAmount = 0;

echo "<h3>Price Calculations:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Product</th><th>Base Price</th><th>Variant</th><th>Price Modifier</th><th>Final Price</th><th>Quantity</th><th>Total</th></tr>";

foreach ($cartItems as $cartKey => $cartItem) {
    $product_id = $cartItem['product_id'];
    $variant_id = $cartItem['variant_id'];
    $quantity = $cartItem['quantity'];

    // Get product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $basePrice = floatval($product['price']);
        $price = $basePrice;
        $variantInfo = 'None';
        $priceModifier = 0;

        // Get variant details if exists
        if ($variant_id && $variant_id !== 'default') {
            $variantStmt = $pdo->prepare("SELECT * FROM product_variants WHERE variant_id = ? AND product_id = ?");
            $variantStmt->execute([$variant_id, $product_id]);
            $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);

            if ($variant) {
                $priceModifier = floatval($variant['price_modifier'] ?? 0);
                $price = $basePrice + $priceModifier;
                $variantInfo = $variant['size'] ?? $variant['color'] ?? 'Variant';
            }
        }

        $itemTotal = $price * $quantity;
        $totalAmount += $itemTotal;

        echo "<tr>";
        echo "<td>" . htmlspecialchars($product['name']) . "</td>";
        echo "<td>₹" . number_format($basePrice, 2) . "</td>";
        echo "<td>" . htmlspecialchars($variantInfo) . "</td>";
        echo "<td>₹" . number_format($priceModifier, 2) . "</td>";
        echo "<td>₹" . number_format($price, 2) . "</td>";
        echo "<td>" . $quantity . "</td>";
        echo "<td>₹" . number_format($itemTotal, 2) . "</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<h3>Summary:</h3>";
echo "<p><strong>Total Amount: ₹" . number_format($totalAmount, 2) . "</strong></p>";

// Compare with checkout.php calculation
echo "<h3>Checkout.php Calculation:</h3>";
$checkoutTotal = 0;
foreach ($_SESSION['cart'] as $cartKey => $cartData) {
    $productId = $cartData['product_id'];
    $variantId = $cartData['variant_id'] ?? null;
    $quantity = $cartData['quantity'];
    
    // Get product details (same as checkout.php)
    $stmt = $pdo->prepare("SELECT p.*,
        (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1) as image_url
        FROM products p WHERE p.product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        $price = $product['price'];
        
        // Get variant details if exists (same as checkout.php)
        if ($variantId) {
            $variantStmt = $pdo->prepare("SELECT * FROM product_variants WHERE variant_id = ? AND product_id = ?");
            $variantStmt->execute([$variantId, $productId]);
            $variant = $variantStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($variant) {
                $price = $product['price'] + ($variant['price_modifier'] ?? 0);
            }
        }
        
        $itemTotal = $price * $quantity;
        $checkoutTotal += $itemTotal;
        
        echo "<p>" . $product['name'] . ": ₹" . number_format($price, 2) . " x " . $quantity . " = ₹" . number_format($itemTotal, 2) . "</p>";
    }
}

echo "<p><strong>Checkout Total: ₹" . number_format($checkoutTotal, 2) . "</strong></p>";

if (abs($totalAmount - $checkoutTotal) > 0.01) {
    echo "<p style='color: red;'><strong>⚠️ Price mismatch detected!</strong></p>";
} else {
    echo "<p style='color: green;'><strong>✅ Prices match!</strong></p>";
}
?>
