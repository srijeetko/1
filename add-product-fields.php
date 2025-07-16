<?php
include 'includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

echo "<h2>Adding Missing Product Fields</h2>";

// Check current table structure
$stmt = $pdo->query("DESCRIBE products");
$existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h3>Current Products Table Columns:</h3>";
echo "<ul>";
foreach ($existingColumns as $column) {
    echo "<li>" . htmlspecialchars($column) . "</li>";
}
echo "</ul>";

// Define the fields we need to add
$fieldsToAdd = [
    'short_description' => 'TEXT',
    'long_description' => 'TEXT',
    'key_benefits' => 'TEXT',
    'how_to_use' => 'TEXT',
    'how_to_use_images' => 'TEXT',
    'ingredients' => 'TEXT'
];

echo "<h3>Adding Missing Fields:</h3>";

$addedFields = [];
$skippedFields = [];

foreach ($fieldsToAdd as $fieldName => $fieldType) {
    if (in_array($fieldName, $existingColumns)) {
        echo "<p>⏭️ Field '$fieldName' already exists - skipping</p>";
        $skippedFields[] = $fieldName;
    } else {
        try {
            // Add the field
            $alterSQL = "ALTER TABLE products ADD COLUMN $fieldName $fieldType";
            $pdo->exec($alterSQL);
            echo "<p>✅ Successfully added field '$fieldName' ($fieldType)</p>";
            $addedFields[] = $fieldName;
        } catch (PDOException $e) {
            echo "<p>❌ Error adding field '$fieldName': " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h3>Summary:</h3>";
echo "<p><strong>Added fields:</strong> " . (empty($addedFields) ? "None" : implode(", ", $addedFields)) . "</p>";
echo "<p><strong>Skipped fields:</strong> " . (empty($skippedFields) ? "None" : implode(", ", $skippedFields)) . "</p>";

// Verify the final structure
echo "<h3>Final Products Table Structure:</h3>";
$stmt = $pdo->query("DESCRIBE products");
$finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 1rem;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
foreach ($finalColumns as $column) {
    $isNew = in_array($column['Field'], $addedFields);
    $rowStyle = $isNew ? "background-color: #d4edda;" : "";
    echo "<tr style='$rowStyle'>";
    echo "<td>" . htmlspecialchars($column['Field']) . ($isNew ? " <strong>(NEW)</strong>" : "") . "</td>";
    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
    echo "</tr>";
}
echo "</table>";

if (!empty($addedFields)) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 1rem; border-radius: 8px; margin-top: 1rem;'>";
    echo "<h4 style='color: #155724; margin-top: 0;'>✅ Success!</h4>";
    echo "<p style='color: #155724; margin-bottom: 0;'>The products table has been updated with the missing fields. Now all product details entered through the admin panel will be properly stored and displayed on the product detail page.</p>";
    echo "</div>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Go to your admin panel and edit a product</li>";
    echo "<li>Fill in the new fields (short description, long description, key benefits, how to use, ingredients)</li>";
    echo "<li>Save the product</li>";
    echo "<li>View the product detail page to see all the information displayed</li>";
    echo "</ol>";
} else {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 1rem; border-radius: 8px; margin-top: 1rem;'>";
    echo "<h4 style='color: #856404; margin-top: 0;'>ℹ️ Information</h4>";
    echo "<p style='color: #856404; margin-bottom: 0;'>All required fields already exist in the products table. Your product detail page should already be displaying all the information from the admin panel.</p>";
    echo "</div>";
}

echo "<p style='margin-top: 2rem;'><a href='check-product-fields.php'>← Back to Field Check</a> | <a href='product-detail.php?id=" . (isset($_GET['test_id']) ? $_GET['test_id'] : 'test') . "'>Test Product Detail Page →</a></p>";
?>
