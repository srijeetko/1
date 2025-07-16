<?php
// Quick database structure fix for supplement_details table
session_start();
include '../includes/db_connection.php';

echo "<h1>Database Structure Fix</h1>";

try {
    // Check current supplement_details table structure
    echo "<h2>Current supplement_details table structure:</h2>";
    $stmt = $pdo->query("DESCRIBE supplement_details");
    $currentColumns = $stmt->fetchAll();
    
    $existingColumns = array_column($currentColumns, 'Field');
    echo "Existing columns: " . implode(', ', $existingColumns) . "<br><br>";
    
    // Required columns
    $requiredColumns = [
        'serving_size' => 'VARCHAR(50)',
        'calories' => 'INT',
        'protein' => 'DECIMAL(10,2)',
        'carbs' => 'DECIMAL(10,2)',
        'fats' => 'DECIMAL(10,2)',
        'fiber' => 'DECIMAL(10,2)',
        'sodium' => 'DECIMAL(10,2)',
        'ingredients' => 'TEXT',
        'directions' => 'TEXT',
        'warnings' => 'TEXT'
    ];
    
    $missingColumns = [];
    echo "<h2>Column Status:</h2>";
    foreach ($requiredColumns as $column => $type) {
        if (in_array($column, $existingColumns)) {
            echo "✅ $column exists<br>";
        } else {
            echo "❌ $column missing<br>";
            $missingColumns[] = $column;
        }
    }
    
    if (!empty($missingColumns)) {
        echo "<h2>Adding Missing Columns:</h2>";
        
        // Add columns one by one
        $alterCommands = [
            "ALTER TABLE supplement_details ADD COLUMN serving_size VARCHAR(50) AFTER product_id",
            "ALTER TABLE supplement_details ADD COLUMN calories INT AFTER servings_per_container", 
            "ALTER TABLE supplement_details ADD COLUMN protein DECIMAL(10,2) AFTER calories",
            "ALTER TABLE supplement_details ADD COLUMN carbs DECIMAL(10,2) AFTER protein",
            "ALTER TABLE supplement_details ADD COLUMN fats DECIMAL(10,2) AFTER carbs",
            "ALTER TABLE supplement_details ADD COLUMN fiber DECIMAL(10,2) AFTER fats",
            "ALTER TABLE supplement_details ADD COLUMN sodium DECIMAL(10,2) AFTER fiber",
            "ALTER TABLE supplement_details ADD COLUMN ingredients TEXT AFTER sodium",
            "ALTER TABLE supplement_details ADD COLUMN directions TEXT AFTER ingredients",
            "ALTER TABLE supplement_details ADD COLUMN warnings TEXT AFTER directions"
        ];
        
        foreach ($alterCommands as $sql) {
            try {
                // Extract column name for checking
                preg_match('/ADD COLUMN (\w+)/', $sql, $matches);
                $columnName = $matches[1] ?? 'unknown';
                
                if (!in_array($columnName, $existingColumns)) {
                    $pdo->exec($sql);
                    echo "✅ Added column: $columnName<br>";
                } else {
                    echo "ℹ️ Column $columnName already exists<br>";
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                    echo "ℹ️ Column already exists<br>";
                } else {
                    echo "❌ Error adding column: " . $e->getMessage() . "<br>";
                }
            }
        }
        
        echo "<h2>✅ Database structure updated!</h2>";
    } else {
        echo "<h2>✅ All required columns exist!</h2>";
    }
    
    // Show final structure
    echo "<h2>Final supplement_details table structure:</h2>";
    $stmt = $pdo->query("DESCRIBE supplement_details");
    $finalColumns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($finalColumns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test product creation after fix
echo "<h2>Test Product Creation</h2>";
try {
    $testProductId = 'test-fix-' . time();
    
    // Test basic product insertion
    $sql = "INSERT INTO products (product_id, name, description, short_description, long_description, key_benefits, how_to_use, ingredients, price, category_id, stock_quantity, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $testProductId,
        'Test Product After Fix',
        'Test Description',
        'Short description',
        'Long description',
        'Key benefits',
        'How to use',
        'Test ingredients',
        39.99,
        null,
        100,
        1
    ]);
    
    if ($result) {
        echo "✅ Basic product creation successful<br>";
        
        // Test supplement details insertion
        $checkColumns = $pdo->query("SHOW COLUMNS FROM supplement_details LIKE 'serving_size'");
        if ($checkColumns->rowCount() > 0) {
            $supplementSql = "INSERT INTO supplement_details (detail_id, product_id, serving_size, servings_per_container, calories, protein, carbs, fats, fiber, sodium, ingredients, directions, warnings) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($supplementSql);
            $supplementResult = $stmt->execute([
                'test-supplement-' . time(),
                $testProductId,
                '30g',
                30,
                120,
                25.5,
                5.2,
                1.8,
                2.1,
                150.0,
                'Test supplement ingredients',
                'Test directions',
                'Test warnings'
            ]);
            
            if ($supplementResult) {
                echo "✅ Supplement details insertion successful<br>";
            } else {
                echo "❌ Supplement details insertion failed<br>";
            }
        } else {
            echo "ℹ️ Supplement details columns still missing<br>";
        }
        
        // Clean up test data
        $pdo->prepare("DELETE FROM supplement_details WHERE product_id = ?")->execute([$testProductId]);
        $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$testProductId]);
        echo "✅ Test data cleaned up<br>";
        
    } else {
        echo "❌ Product creation failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Test error: " . $e->getMessage() . "<br>";
}
?>

<h2>Next Steps</h2>
<p>
    <a href="test-product-form.php">Test Product Creation</a> | 
    <a href="products.php">View Products</a> | 
    <a href="product-edit.php">Add New Product</a>
</p>
