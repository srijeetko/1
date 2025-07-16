<?php
echo "<h1>Database Connection Test</h1>";

// Test 1: Check if MySQL is running
echo "<h2>1. MySQL Service Check</h2>";
$host = 'localhost';
$port = 3306;

$connection = @fsockopen($host, $port, $errno, $errstr, 5);
if ($connection) {
    echo "✅ MySQL service is running on $host:$port<br>";
    fclose($connection);
} else {
    echo "❌ MySQL service is not running on $host:$port<br>";
    echo "Error: $errstr ($errno)<br>";
}

// Test 2: Test basic MySQL connection without database
echo "<h2>2. Basic MySQL Connection Test</h2>";
try {
    $pdo_test = new PDO("mysql:host=localhost", 'root', '');
    $pdo_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Basic MySQL connection successful<br>";
    
    // List all databases
    echo "<h3>Available Databases:</h3>";
    $stmt = $pdo_test->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<ul>";
    foreach ($databases as $db) {
        echo "<li>" . htmlspecialchars($db);
        if ($db === 'alphanutrition_db') {
            echo " <strong style='color: green;'>← Target Database Found!</strong>";
        }
        echo "</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "❌ Basic MySQL connection failed: " . $e->getMessage() . "<br>";
}

// Test 3: Test connection to alphanutrition_db specifically
echo "<h2>3. Target Database Connection Test</h2>";
try {
    $pdo_target = new PDO("mysql:host=localhost;dbname=alphanutrition_db", 'root', '');
    $pdo_target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connection to 'alphanutrition_db' successful<br>";
    
    // Test a simple query
    $stmt = $pdo_target->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = 'alphanutrition_db'");
    $result = $stmt->fetch();
    echo "✅ Database has " . $result['table_count'] . " tables<br>";
    
    // List some key tables
    echo "<h3>Key Tables Check:</h3>";
    $key_tables = ['products', 'product_variants', 'sub_category', 'product_images'];
    
    foreach ($key_tables as $table) {
        try {
            $stmt = $pdo_target->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "✅ Table '$table': " . $result['count'] . " records<br>";
        } catch (PDOException $e) {
            echo "❌ Table '$table': " . $e->getMessage() . "<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Connection to 'alphanutrition_db' failed: " . $e->getMessage() . "<br>";
    
    // Suggest solutions
    echo "<h3>Possible Solutions:</h3>";
    echo "<ul>";
    echo "<li>Make sure the database 'alphanutrition_db' exists</li>";
    echo "<li>Check if MySQL service is running in Laragon</li>";
    echo "<li>Verify database credentials (username/password)</li>";
    echo "<li>Try creating the database if it doesn't exist</li>";
    echo "</ul>";
}

// Test 4: Test your actual db_connection.php file
echo "<h2>4. Your db_connection.php File Test</h2>";
try {
    // Capture any output from the include
    ob_start();
    include 'includes/db_connection.php';
    $output = ob_get_clean();
    
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "✅ Your db_connection.php works correctly<br>";
        
        // Test a query using your connection
        $stmt = $pdo->query("SELECT VERSION() as mysql_version");
        $result = $stmt->fetch();
        echo "✅ MySQL Version: " . $result['mysql_version'] . "<br>";
        
    } else {
        echo "❌ Your db_connection.php did not create a valid PDO connection<br>";
    }
    
    if (!empty($output)) {
        echo "Output from db_connection.php: " . htmlspecialchars($output) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing your db_connection.php: " . $e->getMessage() . "<br>";
}

// Test 5: Laragon specific checks
echo "<h2>5. Laragon Environment Check</h2>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? '✅ Yes' : '❌ No') . "<br>";

// Check if we're in Laragon environment
if (strpos($_SERVER['DOCUMENT_ROOT'], 'laragon') !== false) {
    echo "✅ Running in Laragon environment<br>";
    echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
} else {
    echo "⚠️ May not be running in Laragon environment<br>";
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #333; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>
