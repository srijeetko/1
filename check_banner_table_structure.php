<?php
echo "<h1>Check Banner Table Structure</h1>";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=alphanutrition_db", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database<br>";
    
    // Check banner_images table structure
    echo "<h2>Banner Images Table Structure</h2>";
    $stmt = $pdo->query("DESCRIBE banner_images");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
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
    
    // Check sample data
    echo "<h2>Sample Banner Data</h2>";
    $stmt = $pdo->query("SELECT * FROM banner_images LIMIT 5");
    $banners = $stmt->fetchAll();
    
    if (!empty($banners)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach (array_keys($banners[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
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
    } else {
        echo "<p>No banner data found</p>";
    }
    
    // Test different queries
    echo "<h2>Query Tests</h2>";
    
    $queries = [
        "status='active'" => "SELECT COUNT(*) as count FROM banner_images WHERE status='active'",
        "is_active=1" => "SELECT COUNT(*) as count FROM banner_images WHERE is_active=1"
    ];
    
    foreach ($queries as $description => $query) {
        try {
            $stmt = $pdo->query($query);
            $result = $stmt->fetch();
            echo "✅ Query with $description: " . $result['count'] . " records<br>";
        } catch (PDOException $e) {
            echo "❌ Query with $description failed: " . $e->getMessage() . "<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . htmlspecialchars($e->getMessage()) . "<br>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f2f2f2; }
</style>
