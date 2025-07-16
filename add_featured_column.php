<?php
// Add Featured column to products table
require_once 'includes/db_connection.php';

try {
    echo "<h2>Adding Featured Column to Products Table</h2>";
    
    // Step 1: Add the featured column
    $alterSql = "ALTER TABLE products ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER is_active";
    
    echo "<p><strong>Step 1:</strong> Adding is_featured column...</p>";
    echo "<p><strong>SQL:</strong> " . htmlspecialchars($alterSql) . "</p>";
    
    try {
        $pdo->exec($alterSql);
        echo "<p style='color: green;'>✅ Featured column added successfully</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠️ Featured column already exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding column: " . htmlspecialchars($e->getMessage()) . "</p>";
            throw $e;
        }
    }
    
    // Step 2: Set some products as featured (first 3 products as example)
    echo "<p><strong>Step 2:</strong> Setting first 3 products as featured...</p>";
    
    $updateSql = "UPDATE products SET is_featured = 1 WHERE sr_no IN (1, 2, 3)";
    try {
        $pdo->exec($updateSql);
        echo "<p style='color: green;'>✅ First 3 products marked as featured</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error setting featured products: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Step 3: Verify the results
    echo "<h3>Verification - Products with Featured Status:</h3>";
    
    $verifySql = "SELECT sr_no, name, is_featured, 
                         CASE WHEN is_featured = 1 THEN 'Yes' ELSE 'No' END as featured_status 
                  FROM products 
                  ORDER BY sr_no ASC 
                  LIMIT 10";
    $verifyStmt = $pdo->query($verifySql);
    $verifyResults = $verifyStmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Sr No</th><th>Product Name</th><th>Featured Status</th></tr>";
    
    foreach ($verifyResults as $row) {
        $rowStyle = ($row['is_featured'] == 1) ? "style='background-color: #fff3cd;'" : "";
        echo "<tr $rowStyle>";
        echo "<td>" . htmlspecialchars($row['sr_no']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['featured_status']) . "</strong></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Step 4: Update table structure display
    echo "<h3>Updated Products Table Structure:</h3>";
    
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        $rowStyle = ($column['Field'] == 'is_featured') ? "style='background-color: #d4edda;'" : "";
        echo "<tr $rowStyle>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Featured Column Successfully Added!</h3>";
    echo "<p>The is_featured column has been added to the products table.</p>";
    echo "<p>Featured products can now be managed through the admin panel.</p>";
    echo "<p><a href='admin/products.php'>View Products in Admin Panel</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
