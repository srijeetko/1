<?php
// Simple test form to verify product creation
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $productId = bin2hex(random_bytes(16));
        
        // Simple product data
        $productData = [
            'product_id' => $productId,
            'name' => $_POST['name'] ?? 'Test Product',
            'description' => $_POST['description'] ?? 'Test Description',
            'short_description' => $_POST['short_description'] ?? 'Short desc',
            'long_description' => $_POST['long_description'] ?? 'Long desc',
            'key_benefits' => $_POST['key_benefits'] ?? 'Benefits',
            'how_to_use' => $_POST['how_to_use'] ?? 'Usage',
            'how_to_use_images' => '',
            'ingredients' => $_POST['ingredients'] ?? 'Ingredients',
            'price' => $_POST['price'] ?? 29.99,
            'category_id' => $_POST['category_id'] ?? null,
            'stock_quantity' => $_POST['stock_quantity'] ?? 100,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $sql = "INSERT INTO products (product_id, name, description, short_description, long_description, key_benefits, how_to_use, how_to_use_images, ingredients, price, category_id, stock_quantity, is_active) 
                VALUES (:product_id, :name, :description, :short_description, :long_description, :key_benefits, :how_to_use, :how_to_use_images, :ingredients, :price, :category_id, :stock_quantity, :is_active)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($productData);
        
        if ($result) {
            // Add a simple variant
            if (!empty($_POST['variant_size']) && !empty($_POST['variant_price'])) {
                $variantSql = "INSERT INTO product_variants (variant_id, product_id, size, price_modifier, stock) VALUES (?, ?, ?, ?, ?)";
                $variantStmt = $pdo->prepare($variantSql);
                $variantStmt->execute([
                    bin2hex(random_bytes(16)),
                    $productId,
                    $_POST['variant_size'],
                    $_POST['variant_price'],
                    $_POST['variant_stock'] ?? 50
                ]);
            }
            
            // Add supplement details if provided
            if (!empty($_POST['serving_size'])) {
                $supplementSql = "INSERT INTO supplement_details (detail_id, product_id, serving_size, servings_per_container, calories, protein, carbs, fats, fiber, sodium, ingredients, directions, warnings) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $supplementStmt = $pdo->prepare($supplementSql);
                $supplementStmt->execute([
                    bin2hex(random_bytes(16)),
                    $productId,
                    $_POST['serving_size'] ?? '',
                    $_POST['servings_per_container'] ?? null,
                    $_POST['calories'] ?? null,
                    $_POST['protein'] ?? null,
                    $_POST['carbs'] ?? null,
                    $_POST['fats'] ?? null,
                    $_POST['fiber'] ?? null,
                    $_POST['sodium'] ?? null,
                    $_POST['supplement_ingredients'] ?? '',
                    $_POST['directions'] ?? '',
                    $_POST['warnings'] ?? ''
                ]);
            }
            
            $pdo->commit();
            $message = "✅ Product created successfully! ID: $productId";
        } else {
            $message = "❌ Failed to create product";
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "❌ Error: " . $e->getMessage();
    }
}

// Get categories for dropdown
$categories = $pdo->query('SELECT * FROM sub_category ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Product Form</title>
    <link href="../styles.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
</head>
<body class="admin-page">
    <?php include 'includes/admin-header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-sidebar">
            <?php include 'includes/admin-sidebar.php'; ?>
        </div>
        
        <main class="admin-main">
            <h1>Test Product Creation Form</h1>
            
            <?php if ($message): ?>
                <div class="<?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="admin-form">
                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required value="Test Product <?php echo time(); ?>">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description">Test product description</textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">Price *</label>
                    <input type="number" id="price" name="price" step="0.01" required value="29.99">
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category_id'] ?? ''); ?>">
                                <?php echo htmlspecialchars($cat['name'] ?? 'Unnamed Category'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" value="100">
                </div>
                
                <h3>Variant (Optional)</h3>
                <div class="form-group">
                    <label for="variant_size">Variant Size</label>
                    <input type="text" id="variant_size" name="variant_size" placeholder="e.g., 1kg">
                </div>
                
                <div class="form-group">
                    <label for="variant_price">Variant Price</label>
                    <input type="number" id="variant_price" name="variant_price" step="0.01" placeholder="29.99">
                </div>
                
                <h3>Supplement Details (Optional)</h3>
                <div class="form-group">
                    <label for="serving_size">Serving Size</label>
                    <input type="text" id="serving_size" name="serving_size" placeholder="30g">
                </div>
                
                <div class="form-group">
                    <label for="calories">Calories</label>
                    <input type="number" id="calories" name="calories" placeholder="120">
                </div>
                
                <div class="form-group">
                    <label for="protein">Protein (g)</label>
                    <input type="number" id="protein" name="protein" step="0.1" placeholder="25.5">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" checked> Active
                    </label>
                </div>
                
                <button type="submit" class="primary-btn">Create Test Product</button>
            </form>
            
            <p><a href="debug-product-issues.php">Run Debug Script</a> | <a href="products.php">View Products</a></p>
        </main>
    </div>
</body>
</html>
