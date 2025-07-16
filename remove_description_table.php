<?php
include 'includes/db_connection.php';

echo "<h1>Remove Description Table</h1>";
echo "<p>This script will safely remove the description table from the database.</p>";

try {
    // Check if description table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'description'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px;'>";
        echo "<h2>⚠️ Table Not Found</h2>";
        echo "<p>The 'description' table does not exist in the database.</p>";
        echo "</div>";
        exit;
    }
    
    // Show table info before deletion
    echo "<h2>Table Information</h2>";
    
    // Show table structure
    echo "<h3>Table Structure</h3>";
    $stmt = $pdo->query("DESCRIBE description");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
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
    
    // Show record count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM description");
    $count = $stmt->fetch()['count'];
    echo "<p><strong>Records in table:</strong> $count</p>";
    
    // Show sample data if any exists
    if ($count > 0) {
        echo "<h3>Sample Data (First 5 Records)</h3>";
        $stmt = $pdo->query("SELECT * FROM description LIMIT 5");
        $records = $stmt->fetchAll();
        
        if (!empty($records)) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr>";
            foreach (array_keys($records[0]) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            
            foreach ($records as $record) {
                echo "<tr>";
                foreach ($record as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Check for foreign key constraints
    echo "<h3>Foreign Key Check</h3>";
    $stmt = $pdo->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            REFERENCED_TABLE_SCHEMA = DATABASE()
            AND (REFERENCED_TABLE_NAME = 'description' OR TABLE_NAME = 'description')
    ");
    $constraints = $stmt->fetchAll();
    
    if (!empty($constraints)) {
        echo "<p style='color: orange;'><strong>⚠️ Warning: Foreign key constraints found:</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Table</th><th>Column</th><th>Constraint</th><th>References</th></tr>";
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>{$constraint['TABLE_NAME']}</td>";
            echo "<td>{$constraint['COLUMN_NAME']}</td>";
            echo "<td>{$constraint['CONSTRAINT_NAME']}</td>";
            echo "<td>{$constraint['REFERENCED_TABLE_NAME']}.{$constraint['REFERENCED_COLUMN_NAME']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>These constraints will need to be handled before dropping the table.</p>";
    } else {
        echo "<p style='color: green;'>✅ No foreign key constraints found. Safe to drop table.</p>";
    }
    
    // Confirmation form
    if (!isset($_POST['confirm_delete'])) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin-top: 20px;'>";
        echo "<h2>⚠️ Confirm Table Deletion</h2>";
        echo "<p><strong>This action cannot be undone!</strong></p>";
        echo "<p>Are you sure you want to delete the 'description' table and all its data?</p>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='confirm_delete' value='yes'>";
        echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Yes, Delete Table</button>";
        echo " ";
        echo "<a href='admin/' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Cancel</a>";
        echo "</form>";
        echo "</div>";
    } else {
        // Perform the deletion
        echo "<h2>Deleting Table...</h2>";
        
        try {
            // Drop foreign key constraints first if any exist
            foreach ($constraints as $constraint) {
                if ($constraint['TABLE_NAME'] !== 'description') {
                    echo "<p>🔄 Dropping foreign key constraint: {$constraint['CONSTRAINT_NAME']}</p>";
                    $pdo->exec("ALTER TABLE {$constraint['TABLE_NAME']} DROP FOREIGN KEY {$constraint['CONSTRAINT_NAME']}");
                }
            }
            
            // Drop the table
            $pdo->exec("DROP TABLE description");
            
            echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin-top: 20px;'>";
            echo "<h2>✅ Table Deleted Successfully!</h2>";
            echo "<p>The 'description' table has been removed from the database.</p>";
            echo "<p><a href='admin/'>Return to Admin Panel</a></p>";
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
            echo "<h2>❌ Error Deleting Table</h2>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
h1, h2, h3 { color: #333; }
</style>
