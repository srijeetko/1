<?php
// Manually add the missing image columns
require_once 'includes/db_connection.php';

try {
    echo "<h2>Adding Missing Image Columns to Products Table</h2>";
    
    $alterStatements = [
        "ALTER TABLE products ADD COLUMN short_description_image VARCHAR(255) AFTER short_description",
        "ALTER TABLE products ADD COLUMN long_description_image VARCHAR(255) AFTER long_description", 
        "ALTER TABLE products ADD COLUMN key_benefits_image VARCHAR(255) AFTER key_benefits",
        "ALTER TABLE products ADD COLUMN ingredients_image VARCHAR(255) AFTER ingredients"
    ];
    
    foreach ($alterStatements as $sql) {
        echo "<p><strong>Executing:</strong> " . htmlspecialchars($sql) . "</p>";
        
        try {
            $pdo->exec($sql);
            echo "<p style='color: green;'>✅ Success</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p style='color: orange;'>⚠️ Column already exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        echo "<hr>";
    }
    
    echo "<h3>Verifying Columns Were Added:</h3>";
    
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $imageColumns = ['short_description_image', 'long_description_image', 'key_benefits_image', 'ingredients_image'];
    
    foreach ($imageColumns as $col) {
        if (in_array($col, $columns)) {
            echo "<p style='color: green;'>✅ $col - Successfully added</p>";
        } else {
            echo "<p style='color: red;'>❌ $col - Still missing</p>";
        }
    }
    
    echo "<h3>Setting Default Values for Existing Products:</h3>";
    
    // Set primary image as default for existing products
    $updateSql = "
        UPDATE products p
        SET 
            short_description_image = COALESCE(
                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1),
                ''
            ),
            long_description_image = COALESCE(
                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1),
                ''
            ),
            key_benefits_image = COALESCE(
                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1),
                ''
            ),
            ingredients_image = COALESCE(
                (SELECT image_url FROM product_images pi WHERE pi.product_id = p.product_id AND pi.is_primary = 1 LIMIT 1),
                ''
            )
        WHERE p.product_id IS NOT NULL
    ";
    
    try {
        $pdo->exec($updateSql);
        echo "<p style='color: green;'>✅ Default values set for existing products</p>";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error setting defaults: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h3>✅ Database Update Complete!</h3>";
    echo "<p><a href='admin/product-edit.php'>Test Product Edit Form</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
