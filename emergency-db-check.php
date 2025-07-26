<?php
// Emergency Database Structure Check
require_once 'includes/db_connection.php';

echo "<h1>🚨 Emergency Database Structure Check</h1>";

try {
    // Check if tables exist and their structure
    $tables = ['checkout_orders', 'payment_transactions'];
    
    foreach ($tables as $table) {
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>📋 Table: {$table}</h3>";
        
        try {
            // Check if table exists
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() == 0) {
                echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
                echo "❌ Table '{$table}' does not exist!";
                echo "</div>";
                continue;
            }
            
            // Get table structure
            $stmt = $pdo->query("DESCRIBE {$table}");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
            echo "<tr style='background: #e9ecef;'><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Get recent records count
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p><strong>Total Records:</strong> {$count['count']}</p>";
            
            // Get recent records
            if ($count['count'] > 0) {
                $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 3");
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($records)) {
                    echo "<h4>Recent Records:</h4>";
                    echo "<pre style='background: #fff; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
                    foreach ($records as $record) {
                        echo json_encode($record, JSON_PRETTY_PRINT) . "\n\n";
                    }
                    echo "</pre>";
                }
            }
            
        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
            echo "❌ Error checking table: " . $e->getMessage();
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    // Check for any orders in the last 24 hours with flexible column names
    echo "<div style='background: #cce5ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>🔍 Emergency Order Search (Last 24 Hours)</h3>";
    
    try {
        // Try different possible column combinations
        $possibleQueries = [
            "SELECT * FROM checkout_orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 5",
            "SELECT * FROM checkout_orders WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 5",
            "SELECT * FROM checkout_orders ORDER BY id DESC LIMIT 5",
            "SELECT * FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 5",
            "SELECT * FROM orders ORDER BY id DESC LIMIT 5"
        ];
        
        $found = false;
        foreach ($possibleQueries as $query) {
            try {
                $stmt = $pdo->query($query);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($orders)) {
                    echo "<h4>✅ Found Recent Orders:</h4>";
                    echo "<pre style='background: #fff; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
                    foreach ($orders as $order) {
                        echo json_encode($order, JSON_PRETTY_PRINT) . "\n\n";
                    }
                    echo "</pre>";
                    $found = true;
                    break;
                }
            } catch (Exception $e) {
                // Continue to next query
                continue;
            }
        }
        
        if (!$found) {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>";
            echo "⚠️ No recent orders found with any query variation.";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "❌ Error searching for orders: " . $e->getMessage();
        echo "</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>❌ Critical Database Error</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div style='background: #e2e3e5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3>🛠️ Next Steps Based on Findings:</h3>";
echo "<ol>";
echo "<li><strong>If tables exist but columns are different:</strong> We'll create a manual order entry</li>";
echo "<li><strong>If no recent orders found:</strong> Order creation completely failed</li>";
echo "<li><strong>If payment successful in Cashfree:</strong> We'll manually create the order or process refund</li>";
echo "</ol>";
echo "</div>";

?>

<style>
body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
table { border-collapse: collapse; width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
th { background-color: #f8f9fa; }
pre { font-size: 12px; }
</style>
