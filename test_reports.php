<?php
require_once 'includes/db_connection.php';

echo "<h2>🔍 Testing OMS Reports System</h2>";

try {
    // Test date range
    $date_from = date('Y-m-01'); // First day of current month
    $date_to = date('Y-m-d'); // Today
    
    echo "<p><strong>Testing date range:</strong> $date_from to $date_to</p>";
    
    // Test 1: Sales Overview Report
    echo "<h3>📊 Sales Overview Report</h3>";
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_order_value,
            SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
            SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
            SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
        FROM checkout_orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$date_from, $date_to]);
    $sales_overview = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sales_overview) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Metric</th><th>Value</th></tr>";
        echo "<tr><td>Total Orders</td><td>" . ($sales_overview['total_orders'] ?? 0) . "</td></tr>";
        echo "<tr><td>Total Revenue</td><td>₹" . number_format($sales_overview['total_revenue'] ?? 0, 2) . "</td></tr>";
        echo "<tr><td>Avg Order Value</td><td>₹" . number_format($sales_overview['avg_order_value'] ?? 0, 2) . "</td></tr>";
        echo "<tr><td>Paid Orders</td><td>" . ($sales_overview['paid_orders'] ?? 0) . "</td></tr>";
        echo "<tr><td>Delivered Orders</td><td>" . ($sales_overview['delivered_orders'] ?? 0) . "</td></tr>";
        echo "<tr><td>Cancelled Orders</td><td>" . ($sales_overview['cancelled_orders'] ?? 0) . "</td></tr>";
        echo "</table>";
        echo "<p style='color: green;'>✅ Sales Overview Report working</p>";
    } else {
        echo "<p style='color: red;'>❌ Sales Overview Report failed</p>";
    }
    
    // Test 2: Daily Sales Trend
    echo "<h3>📈 Daily Sales Trend</h3>";
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as order_date,
            COUNT(*) as orders_count,
            SUM(total_amount) as daily_revenue
        FROM checkout_orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY order_date DESC
        LIMIT 10
    ");
    $stmt->execute([$date_from, $date_to]);
    $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($daily_sales)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Date</th><th>Orders</th><th>Revenue</th></tr>";
        foreach ($daily_sales as $day) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($day['order_date']) . "</td>";
            echo "<td>" . htmlspecialchars($day['orders_count']) . "</td>";
            echo "<td>₹" . number_format($day['daily_revenue'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✅ Daily Sales Trend working</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No daily sales data found</p>";
    }
    
    // Test 3: Payment Methods Report
    echo "<h3>💳 Payment Methods Report</h3>";
    $stmt = $pdo->prepare("
        SELECT 
            payment_method,
            COUNT(*) as order_count,
            SUM(total_amount) as total_amount
        FROM checkout_orders 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY payment_method
    ");
    $stmt->execute([$date_from, $date_to]);
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($payment_methods)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Payment Method</th><th>Orders</th><th>Total Amount</th></tr>";
        foreach ($payment_methods as $method) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($method['payment_method'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($method['order_count']) . "</td>";
            echo "<td>₹" . number_format($method['total_amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✅ Payment Methods Report working</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No payment method data found</p>";
    }
    
    // Test 4: Delivery Performance Report
    echo "<h3>🚚 Delivery Performance Report</h3>";
    $stmt = $pdo->prepare("
        SELECT 
            dp.partner_name,
            COUNT(da.assignment_id) as total_assignments,
            SUM(CASE WHEN da.delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
            SUM(CASE WHEN da.delivery_status = 'failed' THEN 1 ELSE 0 END) as failed_count,
            AVG(da.delivery_charges) as avg_delivery_charge
        FROM delivery_assignments da
        JOIN delivery_partners dp ON da.partner_id = dp.partner_id
        WHERE DATE(da.created_at) BETWEEN ? AND ?
        GROUP BY dp.partner_id, dp.partner_name
    ");
    $stmt->execute([$date_from, $date_to]);
    $delivery_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($delivery_performance)) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Partner</th><th>Assignments</th><th>Delivered</th><th>Failed</th><th>Avg Charge</th></tr>";
        foreach ($delivery_performance as $partner) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($partner['partner_name']) . "</td>";
            echo "<td>" . htmlspecialchars($partner['total_assignments']) . "</td>";
            echo "<td>" . htmlspecialchars($partner['delivered_count']) . "</td>";
            echo "<td>" . htmlspecialchars($partner['failed_count']) . "</td>";
            echo "<td>₹" . number_format($partner['avg_delivery_charge'] ?? 0, 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p style='color: green;'>✅ Delivery Performance Report working</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No delivery performance data found</p>";
    }
    
    // Test 5: Check required tables exist
    echo "<h3>🗄️ Database Tables Check</h3>";
    $required_tables = ['checkout_orders', 'payment_transactions', 'delivery_assignments', 'delivery_partners'];
    
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
