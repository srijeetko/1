<?php
// Database update script to add missing columns to supplement_details table
session_start();
include '../includes/db_connection.php';

echo "<h1>Database Update Script</h1>";
echo "<p>This script will add missing columns to the supplement_details table.</p>";

try {
    // Check current table structure
    echo "<h2>Current supplement_details table structure:</h2>";
    $stmt = $pdo->query("DESCRIBE supplement_details");
    $currentColumns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($currentColumns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // List of columns that should exist
    $requiredColumns = [
        'detail_id' => 'CHAR(36)',
        'product_id' => 'CHAR(36)',
        'serving_size' => 'VARCHAR(50)',
        'servings_per_container' => 'INT',
        'calories' => 'INT',
        'protein' => 'DECIMAL(10,2)',
        'carbs' => 'DECIMAL(10,2)',
        'fats' => 'DECIMAL(10,2)',
        'fiber' => 'DECIMAL(10,2)',
        'sodium' => 'DECIMAL(10,2)',
        'ingredients' => 'TEXT',
        'directions' => 'TEXT',
        'warnings' => 'TEXT',
        'weight_value' => 'DECIMAL(10,2)',
        'weight_unit' => 'ENUM(\'g\', \'kg\', \'lb\', \'oz\')',
        'created_at' => 'TIMESTAMP',
        'updated_at' => 'TIMESTAMP'
    ];
    
    $existingColumns = array_column($currentColumns, 'Field');
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
        
        // Add columns one by one to avoid issues
        $alterStatements = [
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
        
        foreach ($alterStatements as $sql) {
            try {
                // Extract column name for checking
                preg_match('/ADD COLUMN (\w+)/', $sql, $matches);
                $columnName = $matches[1] ?? 'unknown';
                
                // Check if column already exists before adding
                if (!in_array($columnName, $existingColumns)) {
                    $pdo->exec($sql);
                    echo "✅ Added column: $columnName<br>";
                } else {
                    echo "ℹ️ Column $columnName already exists, skipping<br>";
                }
            } catch (PDOException $e) {
                // If column already exists, that's fine
                if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                    echo "ℹ️ Column $columnName already exists<br>";
                } else {
                    echo "❌ Error adding column $columnName: " . $e->getMessage() . "<br>";
                }
            }
        }
        
        // Add timestamps if they don't exist
        try {
            if (!in_array('created_at', $existingColumns)) {
                $pdo->exec("ALTER TABLE supplement_details ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
                echo "✅ Added created_at column<br>";
            }
            if (!in_array('updated_at', $existingColumns)) {
                $pdo->exec("ALTER TABLE supplement_details ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                echo "✅ Added updated_at column<br>";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                echo "❌ Error adding timestamp columns: " . $e->getMessage() . "<br>";
            }
        }
        
        // Update foreign key constraint
        try {
            $pdo->exec("ALTER TABLE supplement_details DROP FOREIGN KEY IF EXISTS supplement_details_ibfk_1");
            $pdo->exec("ALTER TABLE supplement_details ADD CONSTRAINT supplement_details_ibfk_1 FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE");
            echo "✅ Updated foreign key constraint<br>";
        } catch (PDOException $e) {
            echo "ℹ️ Foreign key constraint update: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "<h2>✅ All required columns already exist!</h2>";
    }
    
    // Show updated table structure
    echo "<h2>Updated supplement_details table structure:</h2>";
    $stmt = $pdo->query("DESCRIBE supplement_details");
    $updatedColumns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($updatedColumns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>✅ Database Update Complete!</h2>";
    echo "<p><a href='test-product-management.php'>Run Product Management Test Again</a></p>";
    echo "<p><a href='products.php'>Go to Products Management</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error updating database:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
