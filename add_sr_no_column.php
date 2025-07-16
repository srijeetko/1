<?php
// Add Sr No column to products table
require_once 'includes/db_connection.php';

try {
    echo "<h2>Adding Sr No Column to Products Table</h2>";
    
    // Step 1: Add the sr_no column
    $alterSql = "ALTER TABLE products ADD COLUMN sr_no INT AUTO_INCREMENT UNIQUE FIRST";
    
    echo "<p><strong>Step 1:</strong> Adding sr_no column...</p>";
    echo "<p><strong>SQL:</strong> " . htmlspecialchars($alterSql) . "</p>";
    
    try {
        $pdo->exec($alterSql);
        echo "<p style='color: green;'>✅ Sr No column added successfully</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p style='color: orange;'>⚠️ Sr No column already exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Error adding column: " . htmlspecialchars($e->getMessage()) . "</p>";
            
            // Try alternative approach without AUTO_INCREMENT
            echo "<p><strong>Trying alternative approach...</strong></p>";
            $alterSql2 = "ALTER TABLE products ADD COLUMN sr_no INT FIRST";
            try {
                $pdo->exec($alterSql2);
                echo "<p style='color: green;'>✅ Sr No column added (manual numbering)</p>";
            } catch (PDOException $e2) {
                echo "<p style='color: red;'>❌ Alternative failed: " . htmlspecialchars($e2->getMessage()) . "</p>";
                throw $e2;
            }
        }
    }
    
    // Step 2: Update existing records with sequential numbers
    echo "<p><strong>Step 2:</strong> Setting sequential numbers for existing products...</p>";
    
    // Get all products ordered by creation date or product_id
    $selectSql = "SELECT product_id FROM products ORDER BY created_at ASC, product_id ASC";
    $stmt = $pdo->query($selectSql);
    $products = $stmt->fetchAll();
    
    $srNo = 1;
    foreach ($products as $product) {
        $updateSql = "UPDATE products SET sr_no = ? WHERE product_id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([$srNo, $product['product_id']]);
        $srNo++;
    }
    
    echo "<p style='color: green;'>✅ Sequential numbers assigned to " . count($products) . " products</p>";
    
    // Step 3: Verify the results
    echo "<h3>Verification - First 10 Products with Sr No:</h3>";
    
    $verifySql = "SELECT sr_no, name, created_at FROM products ORDER BY sr_no ASC LIMIT 10";
    $verifyStmt = $pdo->query($verifySql);
    $verifyResults = $verifyStmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Sr No</th><th>Product Name</th><th>Created At</th></tr>";
    
    foreach ($verifyResults as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['sr_no']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
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
        $rowStyle = ($column['Field'] == 'sr_no') ? "style='background-color: #d4edda;'" : "";
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
    
    echo "<h3>✅ Sr No Column Successfully Added!</h3>";
    echo "<p>The sr_no column has been added as the first column in the products table.</p>";
    echo "<p>All existing products have been assigned sequential numbers starting from 1.</p>";
    echo "<p><a href='admin/products.php'>View Products in Admin Panel</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
