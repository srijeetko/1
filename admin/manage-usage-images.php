<?php
session_start();
include '../includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

$message = '';
$error = '';

// Handle delete action
if (isset($_GET['delete_step'])) {
    try {
        $step_id = $_GET['delete_step'];
        
        // Get the image path before deleting
        $stmt = $pdo->prepare("SELECT step_image FROM product_usage_steps WHERE step_id = ?");
        $stmt->execute([$step_id]);
        $step = $stmt->fetch();
        
        // Delete the database record
        $deleteStmt = $pdo->prepare("DELETE FROM product_usage_steps WHERE step_id = ?");
        $deleteStmt->execute([$step_id]);
        
        // Delete the image file if it exists
        if ($step && $step['step_image'] && file_exists('../' . $step['step_image'])) {
            unlink('../' . $step['step_image']);
        }
        
        $message = 'Usage step deleted successfully!';
    } catch (Exception $e) {
        $error = 'Error deleting step: ' . $e->getMessage();
    }
}

// Get all products with their usage steps
$sql = "
    SELECT p.product_id, p.name as product_name,
           COUNT(pus.step_id) as step_count,
           GROUP_CONCAT(
               CONCAT(pus.step_number, ':', pus.step_title, ':', IFNULL(pus.step_image, 'no-image'))
               ORDER BY pus.step_number SEPARATOR '|'
           ) as steps_info
    FROM products p
    LEFT JOIN product_usage_steps pus ON p.product_id = pus.product_id AND pus.is_active = 1
    WHERE p.is_active = 1
    GROUP BY p.product_id, p.name
    ORDER BY p.name
";

$products = $pdo->query($sql)->fetchAll();

// Get detailed steps for selected product
$selectedProduct = null;
$detailedSteps = [];
if (isset($_GET['product_id'])) {
    $selectedProductId = $_GET['product_id'];
    
    // Get product info
    $stmt = $pdo->prepare("SELECT product_id, name FROM products WHERE product_id = ?");
    $stmt->execute([$selectedProductId]);
    $selectedProduct = $stmt->fetch();
    
    // Get detailed steps
    $stmt = $pdo->prepare("
        SELECT step_id, step_number, step_title, step_description, step_image, created_at
        FROM product_usage_steps 
        WHERE product_id = ? AND is_active = 1
        ORDER BY step_number
    ");
    $stmt->execute([$selectedProductId]);
    $detailedSteps = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Usage Images - Alpha Nutrition Admin</title>
    <style>
        :root {
            --primary: #4285f4;
            --primary-dark: #3367d6;
            --success: #34a853;
            --success-dark: #2e7d32;
            --danger: #ea4335;
            --danger-dark: #c62828;
            --accent: #fbbc05;
            --accent-light: #fff8e1;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-600: #6c757d;
            --gray-800: #343a40;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --radius: 8px;
            --gradient: linear-gradient(135deg, #4285f4, #34a853);
        }
        
        html, body { 
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body { 
            font-family: 'Segoe UI', Roboto, -apple-system, sans-serif; 
            background: linear-gradient(135deg, #f5f7fa, #e4e8f0); 
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container { 
            max-width: 1400px; 
            min-height: calc(100vh - 60px);
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 0;
            box-shadow: var(--shadow);
        }
        
        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px;
            border-bottom: 3px solid var(--primary);
            padding-bottom: 15px;
            background: var(--gray-100);
            margin: -30px -30px 30px -30px;
            padding: 20px 30px;
            border-radius: 0;
        }
        
        h1 {
            color: var(--primary);
            margin-top: 0;
            position: relative;
            padding-left: 15px;
        }
        
        h1::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
            background: var(--primary);
            border-radius: 3px;
        }
        
        h2 {
            color: var(--gray-800);
            margin-top: 0;
            border-bottom: 2px solid var(--accent);
            padding-bottom: 8px;
            display: inline-block;
        }
        
        .btn { 
            padding: 10px 20px; 
            background: var(--gradient);
            color: white; 
            border: none; 
            border-radius: var(--radius); 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-block;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .btn:hover { 
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.2);
            filter: brightness(1.1);
        }
        
        .btn-success { 
            background: linear-gradient(135deg, #34a853, #2e7d32); 
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, #ea4335, #c62828); 
        }
        
        .btn-small { 
            padding: 8px 15px; 
            font-size: 0.9rem; 
        }
        
        .products-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
            gap: 25px; 
            margin-bottom: 30px; 
        }
        
        .product-card { 
            border: none;
            border-radius: var(--radius); 
            padding: 25px; 
            background: white;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            border-top: 4px solid var(--primary);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.1);
            border-top-color: var(--accent);
        }
        
        .product-name { 
            font-weight: 600; 
            font-size: 1.3rem;
            margin-bottom: 15px; 
            color: var(--primary);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gray-200);
        }
        
        .step-count { 
            color: var(--gray-600); 
            margin-bottom: 15px;
            font-weight: 500;
            background: var(--gray-100);
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
        }
        
        .steps-preview { 
            font-size: 0.95rem; 
            color: var(--gray-600);
            margin-bottom: 20px;
            background: var(--accent-light);
            padding: 12px;
            border-radius: var(--radius);
        }
        
        .steps-preview > * {
            padding: 3px 0;
        }
        
        .step-detail { 
            border: none;
            border-radius: var(--radius); 
            padding: 25px; 
            margin-bottom: 25px; 
            background: white;
            box-shadow: var(--shadow);
            border-left: 5px solid var(--primary);
            transition: transform 0.2s;
        }
        
        .step-detail:hover {
            transform: translateX(5px);
        }
        
        .step-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px;
            background: var(--gray-100);
            padding: 10px 15px;
            border-radius: var(--radius);
        }
        
        .step-number { 
            background: var(--gradient);
            color: white; 
            border-radius: 50%; 
            width: 40px; 
            height: 40px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: bold;
            box-shadow: 0 3px 6px rgba(0,0,0,0.2);
        }
        
        .step-image { 
            max-width: 250px; 
            max-height: 250px; 
            border-radius: var(--radius); 
            margin-top: 15px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
            transition: all 0.3s;
            border: 3px solid white;
        }
        
        .step-image:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border-color: var(--accent);
        }
        
        .message { 
            padding: 15px 20px; 
            margin-bottom: 25px; 
            border-radius: var(--radius); 
            border-left: 5px solid;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success { 
            background: #d4edda; 
            color: #155724; 
            border-color: var(--success);
        }
        
        .error { 
            background: #f8d7da; 
            color: #721c24; 
            border-color: var(--danger);
        }
        
        .back-link { 
            color: var(--primary); 
            text-decoration: none; 
            margin-bottom: 20px; 
            display: inline-block;
            font-weight: 500;
            transition: all 0.2s;
            padding: 5px 10px;
            border-radius: var(--radius);
        }
        
        .back-link:hover { 
            color: var(--primary-dark);
            background: var(--gray-100);
        }
        
        .no-steps { 
            text-align: center; 
            padding: 60px 30px; 
            color: var(--gray-600); 
            background: var(--gray-100); 
            border-radius: var(--radius); 
            border: 3px dashed var(--gray-300);
            margin: 40px 0;
        }
        
        .no-steps h3 {
            margin-top: 0;
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
                min-height: calc(100vh - 30px);
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                margin: -15px -15px 20px -15px;
                padding: 15px;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <a href="products.php" class="back-link">← Back to Products</a>
                <h1>Manage Usage Images</h1>
            </div>
            <a href="add-usage-steps.php" class="btn btn-success">Add New Usage Steps</a>
        </div>
        
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!$selectedProduct): ?>
            <h2>Products Overview</h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                        <div class="step-count">
                            <?php echo $product['step_count']; ?> usage step(s)
                        </div>
                        <?php if ($product['step_count'] > 0): ?>
                            <div class="steps-preview">
                                <?php
                                $steps = explode('|', $product['steps_info']);
                                foreach (array_slice($steps, 0, 3) as $step) {
                                    $parts = explode(':', $step);
                                    if (count($parts) >= 2) {
                                        echo "• " . htmlspecialchars($parts[1]) . "<br>";
                                    }
                                }
                                if (count($steps) > 3) {
                                    echo "• ... and " . (count($steps) - 3) . " more";
                                }
                                ?>
                            </div>
                            <a href="?product_id=<?php echo urlencode($product['product_id']); ?>" class="btn btn-small">View Details</a>
                        <?php else: ?>
                            <div style="color: #666; font-style: italic;">No usage steps added yet</div>
                            <a href="add-usage-steps.php" class="btn btn-small">Add Steps</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="header">
                <div>
                    <a href="manage-usage-images.php" class="back-link">← Back to Overview</a>
                    <h2>Usage Steps for: <?php echo htmlspecialchars($selectedProduct['name']); ?></h2>
                </div>
                <a href="add-usage-steps.php" class="btn btn-success">Edit Steps</a>
            </div>
            
            <?php if (empty($detailedSteps)): ?>
                <div class="no-steps">
                    <h3>No Usage Steps Found</h3>
                    <p>This product doesn't have any usage steps with images yet.</p>
                    <a href="add-usage-steps.php" class="btn">Add Usage Steps</a>
                </div>
            <?php else: ?>
                <?php foreach ($detailedSteps as $step): ?>
                    <div class="step-detail">
                        <div class="step-header">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="step-number"><?php echo $step['step_number']; ?></div>
                                <div>
                                    <h4 style="margin: 0;"><?php echo htmlspecialchars($step['step_title']); ?></h4>
                                    <small style="color: #666;">Added: <?php echo date('M j, Y', strtotime($step['created_at'])); ?></small>
                                </div>
                            </div>
                            <a href="?product_id=<?php echo urlencode($selectedProduct['product_id']); ?>&delete_step=<?php echo urlencode($step['step_id']); ?>" 
                               class="btn btn-danger btn-small"
                               onclick="return confirm('Are you sure you want to delete this step?')">Delete</a>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($step['step_description'])); ?></p>
                        <?php if ($step['step_image']): ?>
                            <div>
                                <strong>Image:</strong><br>
                                <img src="../<?php echo htmlspecialchars($step['step_image']); ?>" 
                                     alt="Step <?php echo $step['step_number']; ?>" 
                                     class="step-image"
                                     onerror="this.style.display='none';">
                                <br><small style="color: #666;"><?php echo htmlspecialchars($step['step_image']); ?></small>
                            </div>
                        <?php else: ?>
                            <div style="color: #666; font-style: italic;">No image uploaded for this step</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
