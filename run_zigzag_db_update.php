<?php
// Database update script to add zig-zag section image fields
require_once 'includes/db_connection.php';

try {
    echo "<h2>Adding Zig-Zag Section Image Fields to Database</h2>";
    
    // Read the SQL file
    $sqlFile = 'zigzag_section_images_update.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        throw new Exception("Could not read SQL file: $sqlFile");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "<h3>Executing SQL Statements:</h3>";
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty lines and comments
        }
        
        echo "<p><strong>Executing:</strong> " . htmlspecialchars(substr($statement, 0, 100)) . "...</p>";
        
        try {
            $pdo->exec($statement);
            echo "<p style='color: green;'>✅ Success</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p style='color: orange;'>⚠️ Column already exists - skipping</p>";
            } else {
                echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    echo "<h3>Verifying Database Structure:</h3>";
    
    // Check if columns were added successfully
    $stmt = $pdo->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = [
        'short_description_image',
        'long_description_image', 
        'key_benefits_image',
        'ingredients_image'
    ];
    
    foreach ($requiredColumns as $column) {
        if (in_array($column, $columns)) {
            echo "<p style='color: green;'>✅ Column '$column' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Column '$column' missing</p>";
        }
    }
    
    echo "<h3>Database Update Complete!</h3>";
    echo "<p>You can now use the admin panel to upload section-specific images.</p>";
    echo "<p><a href='admin/product-edit.php'>Go to Product Edit</a></p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Error:</h3>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
