<?php
// Fix Supplements Category Issue
include_once 'includes/db_connection.php';

echo "<h2>Checking and Fixing Supplements Category</h2>";

try {
    // First, let's see what categories exist
    echo "<h3>Current Categories:</h3>";
    $stmt = $pdo->query("SELECT category_id, name, description FROM sub_category ORDER BY name");
    $categories = $stmt->fetchAll();

    echo "<ul>";
    foreach ($categories as $category) {
        echo "<li><strong>" . htmlspecialchars($category['name']) . "</strong> (ID: " . htmlspecialchars($category['category_id']) . ")</li>";
    }
    echo "</ul>";

    // Check if Supplements category exists
    $stmt = $pdo->prepare("SELECT category_id FROM sub_category WHERE name = 'Supplements'");
    $stmt->execute();
    $supplementsCategory = $stmt->fetch();

    if (!$supplementsCategory) {
        echo "<h3>Adding Missing 'Supplements' Category:</h3>";

        // Generate a new category ID (using a simpler format)
        $categoryId = 'supp-' . uniqid() . '-' . bin2hex(random_bytes(8));

        // Insert the Supplements category
        $stmt = $pdo->prepare("INSERT INTO sub_category (category_id, name, description, parent_id) VALUES (?, ?, ?, NULL)");
        $stmt->execute([
            $categoryId,
            'Supplements',
            'General nutritional supplements and health products'
        ]);

        echo "✅ Successfully added 'Supplements' category with ID: " . $categoryId . "<br>";

        // Now let's create some sample products for this category
        echo "<h3>Adding Sample Products to Supplements Category:</h3>";

        $sampleProducts = [
            [
                'name' => 'Multivitamin Complex',
                'description' => 'Complete daily vitamin and mineral supplement',
                'price' => 899.00
            ],
            [
                'name' => 'Omega-3 Fish Oil',
                'description' => 'High-quality omega-3 fatty acids for heart health',
                'price' => 1299.00
            ],
            [
                'name' => 'Vitamin D3',
                'description' => 'Essential vitamin D3 for bone health and immunity',
                'price' => 599.00
            ]
        ];

        foreach ($sampleProducts as $product) {
            $productId = 'prod-' . uniqid() . '-' . bin2hex(random_bytes(8));

            $stmt = $pdo->prepare("
                INSERT INTO products (product_id, name, description, short_description, price, category_id, stock_quantity, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");

            $stmt->execute([
                $productId,
                $product['name'],
                $product['description'],
                substr($product['description'], 0, 100),
                $product['price'],
                $categoryId,
                50
            ]);

            echo "✅ Added product: " . $product['name'] . "<br>";
        }

    } else {
        echo "<h3>✅ 'Supplements' category already exists with ID: " . $supplementsCategory['category_id'] . "</h3>";
    }

    // Now let's test the category mapping function
    echo "<h3>Testing Category Mapping:</h3>";
    include_once 'includes/category-mapping.php';

    $testCategories = ['Gainer', 'Pre-Workout', 'Supplements', 'Tablets'];

    foreach ($testCategories as $categoryName) {
        $categoryId = getMappedCategoryId($categoryName);
        if ($categoryId) {
            echo "✅ " . $categoryName . " → " . $categoryId . "<br>";
        } else {
            echo "❌ " . $categoryName . " → NOT FOUND<br>";
        }
    }

    // Final check - count products in each category
    echo "<h3>Product Count by Category:</h3>";
    $stmt = $pdo->query("
        SELECT sc.name, COUNT(p.product_id) as product_count
        FROM sub_category sc
        LEFT JOIN products p ON sc.category_id = p.category_id
        GROUP BY sc.category_id, sc.name
        ORDER BY sc.name
    ");
    $categoryCounts = $stmt->fetchAll();

    echo "<ul>";
    foreach ($categoryCounts as $count) {
        echo "<li><strong>" . htmlspecialchars($count['name']) . ":</strong> " . $count['product_count'] . " products</li>";
    }
    echo "</ul>";

    echo "<h3>✅ Fix Complete!</h3>";
    echo "<p><a href='products.php'>Test the Products Page</a> - The Supplements button should now work!</p>";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
