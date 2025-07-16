<?php
// Check current database structure
require_once 'includes/db_connection.php';

try {
    echo "<h2>Current Products Table Structure:</h2>";
    
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Looking for Image Columns:</h3>";
    $imageColumns = ['short_description_image', 'long_description_image', 'key_benefits_image', 'ingredients_image'];
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($imageColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "<p style='color: green;'>✅ $col - EXISTS</p>";
        } else {
            echo "<p style='color: red;'>❌ $col - MISSING</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
