<?php
include 'includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

echo "<h2>Database Structure Analysis</h2>";

// Check products table structure
echo "<h3>1. Products Table Structure</h3>";
try {
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
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for specific fields we need
    $requiredFields = ['short_description', 'long_description', 'key_benefits', 'how_to_use', 'ingredients', 'how_to_use_images'];
    echo "<h4>Required Fields Check:</h4>";
    $columnNames = array_column($columns, 'Field');
    foreach ($requiredFields as $field) {
        $exists = in_array($field, $columnNames);
        echo "<p>" . ($exists ? "✅" : "❌") . " $field</p>";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking products table: " . $e->getMessage();
}

// Check supplement_details table structure
echo "<h3>2. Supplement Details Table Structure</h3>";
try {
    $stmt = $pdo->query("DESCRIBE supplement_details");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Error checking supplement_details table: " . $e->getMessage();
}

// Check sample product data
echo "<h3>3. Sample Product Data</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM products LIMIT 1");
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "<h4>Sample Product Fields and Values:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Value</th><th>Has Data</th></tr>";
        foreach ($product as $field => $value) {
            $hasData = !empty($value) ? "✅" : "❌";
            $displayValue = !empty($value) ? htmlspecialchars(substr($value, 0, 100)) . (strlen($value) > 100 ? '...' : '') : '(empty)';
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($field) . "</strong></td>";
            echo "<td>" . $displayValue . "</td>";
            echo "<td>" . $hasData . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No products found in database</p>";
    }
    
} catch (Exception $e) {
    echo "❌ Error fetching sample product: " . $e->getMessage();
}

// Check if we need to add missing columns
echo "<h3>4. Recommendations</h3>";
$stmt = $pdo->query("DESCRIBE products");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$missingFields = [];

$expectedFields = [
    'short_description' => 'TEXT',
    'long_description' => 'TEXT', 
    'key_benefits' => 'TEXT',
    'how_to_use' => 'TEXT',
    'ingredients' => 'TEXT',
    'how_to_use_images' => 'TEXT'
];

foreach ($expectedFields as $field => $type) {
    if (!in_array($field, $columns)) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    echo "<p><strong>❌ Missing fields in products table:</strong></p>";
    echo "<ul>";
    foreach ($missingFields as $field) {
        echo "<li>$field</li>";
    }
    echo "</ul>";
    
    echo "<p><strong>SQL to add missing fields:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 1rem; border-radius: 4px;'>";
    echo "ALTER TABLE products\n";
    $alterStatements = [];
    foreach ($missingFields as $field) {
        $alterStatements[] = "ADD COLUMN $field " . $expectedFields[$field];
    }
    echo implode(",\n", $alterStatements) . ";";
    echo "</pre>";
} else {
    echo "<p>✅ All required fields exist in products table</p>";
}
?>
