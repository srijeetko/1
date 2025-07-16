<?php
include 'includes/db_connection.php';

echo "<h2>Database Structure Check</h2>";

try {
    // Check if supplement_details table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'supplement_details'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "<h3>supplement_details table exists</h3>";
        
        // Get column information
        $stmt = $pdo->query("DESCRIBE supplement_details");
        $columns = $stmt->fetchAll();
        
        echo "<h4>Columns in supplement_details:</h4>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>" . htmlspecialchars($column['Field']) . " (" . htmlspecialchars($column['Type']) . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<h3>supplement_details table does NOT exist</h3>";
    }
    
    // Check products table structure
    echo "<h3>Products table structure:</h3>";
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . htmlspecialchars($column['Field']) . " (" . htmlspecialchars($column['Type']) . ")</li>";
    }
    echo "</ul>";
    
    // Check if there are any products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "<h3>Total products: " . $result['count'] . "</h3>";
    
    // Show sample product
    $stmt = $pdo->query("SELECT * FROM products LIMIT 1");
    $product = $stmt->fetch();
    if ($product) {
        echo "<h3>Sample product data:</h3>";
        echo "<pre>";
        print_r($product);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
