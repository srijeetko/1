<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=alphanutrition_db', 'root', '');

    // Check if products table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
    $tableExists = $stmt->fetch();

    if (!$tableExists) {
        echo '<h2>Products table does not exist</h2>';
        echo '<p>Please run the database setup scripts first.</p>';
        exit();
    }

    $stmt = $pdo->query('SELECT product_id, name, price, description FROM products WHERE is_active = 1 LIMIT 10');
    $products = $stmt->fetchAll();

    if (empty($products)) {
        echo '<h2>No products found in database</h2>';
        echo '<p>You may need to add some sample products first.</p>';
        echo '<p><a href="admin/products.php">Go to Admin Panel</a> to add products.</p>';

        // Check total products including inactive
        $stmt = $pdo->query('SELECT COUNT(*) as total FROM products');
        $total = $stmt->fetch();
        echo '<p>Total products in database (including inactive): ' . $total['total'] . '</p>';

    } else {
        echo '<h2>Found ' . count($products) . ' active products:</h2>';
        echo '<div style="margin: 20px 0;">';
        echo '<a href="products.php" style="background: #2874f0; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;">View Products Page</a>';
        echo '<a href="check-database-structure.php" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Check Database</a>';
        echo '</div>';

        echo '<div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #2874f0;">';
        echo '<h3 style="margin-top: 0;">Product Detail Page Options:</h3>';
        echo '<ul>';
        echo '<li><strong>Full Details:</strong> Complete product page with all features (requires supplement_details table)</li>';
        echo '<li><strong>Simple View:</strong> Basic product page that works with minimal database structure</li>';
        echo '</ul>';
        echo '</div>';

        echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
        echo '<tr><th>Product ID</th><th>Name</th><th>Price</th><th>Actions</th></tr>';
        foreach ($products as $product) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars(substr($product['product_id'], 0, 8)) . '...</td>';
            echo '<td>' . htmlspecialchars($product['name']) . '</td>';
            echo '<td>₹' . number_format($product['price'], 2) . '</td>';
            echo '<td>';
            echo '<a href="product-detail.php?id=' . htmlspecialchars($product['product_id']) . '" style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; margin-right: 5px;">Full Details</a>';
            echo '<a href="product-detail-simple.php?id=' . htmlspecialchars($product['product_id']) . '" style="background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">Simple View</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
} catch (Exception $e) {
    echo '<h2>Database Error:</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Make sure your database is set up correctly and the connection details are correct.</p>';
}
?>
