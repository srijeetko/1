<?php
include 'includes/db_connection.php';

echo "<h1>Debug Banner Table</h1>";

try {
    // Show table structure
    echo "<h2>Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE banner_images");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show sample data
    echo "<h2>Sample Data</h2>";
    $stmt = $pdo->query("SELECT * FROM banner_images LIMIT 3");
    $banners = $stmt->fetchAll();
    
    if (!empty($banners)) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (array_keys($banners[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        
        foreach ($banners as $banner) {
            echo "<tr>";
            foreach ($banner as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<style>
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; }
th { background: #f5f5f5; }
</style>
