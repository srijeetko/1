<?php
echo "<h1>Setting up Return & Refund Management System</h1>";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=localhost;dbname=alphanutrition_db", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Step 1: Reading SQL file</h2>";
    
    // Read the SQL file
    $sql = file_get_contents('setup_returns_refunds_system.sql');
    
    if (!$sql) {
        throw new Exception("Could not read SQL file");
    }
    
    echo "✅ SQL file read successfully<br>";
    
    echo "<h2>Step 2: Executing SQL statements</h2>";
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $successCount++;
            
            // Show progress for major operations
            if (strpos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE `?(\w+)`?/', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✅ Created table: {$matches[1]}<br>";
                }
            } elseif (strpos($statement, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO `?(\w+)`?/', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✅ Inserted data into: {$matches[1]}<br>";
                }
            } elseif (strpos($statement, 'CREATE VIEW') !== false) {
                preg_match('/CREATE VIEW `?(\w+)`?/', $statement, $matches);
                if (isset($matches[1])) {
                    echo "✅ Created view: {$matches[1]}<br>";
                }
            }
            
        } catch (Exception $e) {
            $errorCount++;
            echo "❌ Error executing statement: " . $e->getMessage() . "<br>";
            echo "Statement: " . substr($statement, 0, 100) . "...<br><br>";
        }
    }
    
    echo "<h2>Step 3: Verification</h2>";
    
    // Verify tables were created
    $tables = [
        'return_reasons',
        'return_policies', 
        'return_requests',
        'return_items',
        'return_status_history',
        'refund_transactions'
    ];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table '$table' exists<br>";
            } else {
                echo "❌ Table '$table' not found<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error checking table '$table': " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>Step 4: Sample Data Check</h2>";
    
    // Check if sample data was inserted
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM return_reasons");
        $result = $stmt->fetch();
        echo "✅ Return reasons inserted: {$result['count']} records<br>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM return_policies");
        $result = $stmt->fetch();
        echo "✅ Return policies inserted: {$result['count']} records<br>";
        
    } catch (Exception $e) {
        echo "❌ Error checking sample data: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>Summary</h2>";
    echo "✅ Successfully executed: $successCount statements<br>";
    if ($errorCount > 0) {
        echo "❌ Errors encountered: $errorCount statements<br>";
    }
    echo "<br><strong>Return & Refund Management System setup completed!</strong><br>";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "<br>";
}
?>
