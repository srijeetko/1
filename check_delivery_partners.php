<?php
require_once 'includes/db_connection.php';

echo "<h2>🔍 Checking Delivery Partners Database</h2>";

// Handle fix request
if (isset($_POST['fix_partners'])) {
    try {
        echo "<h3>🔧 Fixing Delivery Partners...</h3>";

        // Clear existing delivery partners
        $pdo->exec("DELETE FROM delivery_partners");
        echo "<p>✅ Cleared existing delivery partners</p>";

        // Insert the correct 3 delivery partners
        $partners = [
            [
                'name' => 'Delhivery',
                'charges' => json_encode(['surface' => 40, 'express' => 80, 'same_day' => 150, 'cod' => 25])
            ],
            [
                'name' => 'Shiprocket',
                'charges' => json_encode(['surface' => 35, 'express' => 75, 'same_day' => 140, 'cod' => 20])
            ],
            [
                'name' => 'RapidShyp',
                'charges' => json_encode(['surface' => 38, 'express' => 78, 'same_day' => 145, 'cod' => 22])
            ]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO delivery_partners (partner_id, partner_name, delivery_charges, is_active, created_at)
            VALUES (?, ?, ?, 1, NOW())
        ");

        foreach ($partners as $partner) {
            $partner_id = bin2hex(random_bytes(16));
            $stmt->execute([$partner_id, $partner['name'], $partner['charges']]);
            echo "<p>✅ Added " . htmlspecialchars($partner['name']) . "</p>";
        }

        echo "<p style='color: green; font-weight: bold;'>🎉 Delivery partners fixed successfully!</p>";

    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Fix Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

try {
    // Check if delivery_partners table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'delivery_partners'");
    $table_exists = $stmt->rowCount() > 0;

    if (!$table_exists) {
        echo "<p style='color: red;'>❌ delivery_partners table does not exist!</p>";
        exit;
    }

    echo "<p style='color: green;'>✅ delivery_partners table exists</p>";

    // Get table structure
    echo "<h3>📋 Table Structure:</h3>";
    $stmt = $pdo->query("DESCRIBE delivery_partners");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Get current delivery partners
    echo "<h3>📦 Current Delivery Partners:</h3>";
    $stmt = $pdo->query("SELECT * FROM delivery_partners ORDER BY partner_name");
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($partners)) {
        echo "<p style='color: orange;'>⚠️ No delivery partners found in database!</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Active</th><th>Charges</th><th>Created</th></tr>";
        foreach ($partners as $partner) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars(substr($partner['partner_id'], 0, 8)) . "...</td>";
            echo "<td>" . htmlspecialchars($partner['partner_name']) . "</td>";
            echo "<td>" . ($partner['is_active'] ? '✅' : '❌') . "</td>";
            echo "<td>" . htmlspecialchars($partner['delivery_charges'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($partner['created_at'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // Count total partners
    $total = count($partners);
    echo "<p><strong>Total Partners: $total</strong></p>";

    if ($total > 3) {
        echo "<p style='color: red;'>❌ Issue Found: More than 3 delivery partners exist!</p>";
        echo "<p>Expected: Delhivery, Shiprocket, RapidShyp</p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='fix_partners' style='background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🔧 Fix Delivery Partners</button>";
        echo "</form>";
    } elseif ($total < 3) {
        echo "<p style='color: orange;'>⚠️ Issue Found: Less than 3 delivery partners exist!</p>";
        echo "<p>Expected: Delhivery, Shiprocket, RapidShyp</p>";
        echo "<form method='POST'>";
        echo "<button type='submit' name='fix_partners' style='background: #f39c12; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🔧 Fix Delivery Partners</button>";
        echo "</form>";
    } else {
        echo "<p style='color: green;'>✅ Correct number of delivery partners (3)</p>";

        // Check if names are correct
        $expected_names = ['Delhivery', 'Shiprocket', 'RapidShyp'];
        $actual_names = array_column($partners, 'partner_name');

        $missing_names = array_diff($expected_names, $actual_names);
        $extra_names = array_diff($actual_names, $expected_names);

        if (!empty($missing_names) || !empty($extra_names)) {
            echo "<p style='color: red;'>❌ Issue Found: Incorrect partner names!</p>";
            if (!empty($missing_names)) {
                echo "<p>Missing: " . implode(', ', $missing_names) . "</p>";
            }
            if (!empty($extra_names)) {
                echo "<p>Extra: " . implode(', ', $extra_names) . "</p>";
            }
            echo "<form method='POST'>";
            echo "<button type='submit' name='fix_partners' style='background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🔧 Fix Delivery Partners</button>";
            echo "</form>";
        } else {
            echo "<p style='color: green;'>✅ All delivery partner names are correct</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
