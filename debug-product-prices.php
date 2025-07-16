<?php
require_once 'includes/db_connection.php';

echo "<h2>Product Price Debug</h2>";

// Get all products with their variants
$sql = "
    SELECT p.product_id, p.name, p.price as base_price,
           pv.variant_id, pv.size, pv.color, pv.price_modifier,
           (p.price + COALESCE(pv.price_modifier, 0)) as final_price
    FROM products p
    LEFT JOIN product_variants pv ON p.product_id = pv.product_id
    ORDER BY p.name, pv.size
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Product Name</th><th>Base Price</th><th>Variant</th><th>Price Modifier</th><th>Final Price</th></tr>";

foreach ($results as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>₹" . number_format($row['base_price'], 2) . "</td>";
    echo "<td>" . htmlspecialchars($row['size'] ?? $row['color'] ?? 'No variant') . "</td>";
    echo "<td>₹" . number_format($row['price_modifier'] ?? 0, 2) . "</td>";
    echo "<td>₹" . number_format($row['final_price'], 2) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Check for products with high prices
echo "<h3>Products with Price > ₹5000:</h3>";
$highPriceProducts = array_filter($results, function($row) {
    return $row['final_price'] > 5000;
});

if (empty($highPriceProducts)) {
    echo "<p>No products found with price > ₹5000</p>";
} else {
    foreach ($highPriceProducts as $product) {
        echo "<p><strong>" . htmlspecialchars($product['name']) . "</strong> - ₹" . number_format($product['final_price'], 2) . "</p>";
    }
}

// Check for products around ₹1200
echo "<h3>Products with Price around ₹1200 (₹1000-₹1500):</h3>";
$midPriceProducts = array_filter($results, function($row) {
    return $row['final_price'] >= 1000 && $row['final_price'] <= 1500;
});

if (empty($midPriceProducts)) {
    echo "<p>No products found in ₹1000-₹1500 range</p>";
} else {
    foreach ($midPriceProducts as $product) {
        echo "<p><strong>" . htmlspecialchars($product['name']) . "</strong> - ₹" . number_format($product['final_price'], 2) . "</p>";
    }
}
?>
