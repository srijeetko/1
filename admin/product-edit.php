<?php
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$product = [
    'product_id' => '',
    'name' => '',
    'description' => '',
    'price' => '',
    'category_id' => '',
    'stock_quantity' => '',
    'is_active' => 1
];

$variants = [];
$images = [];
$supplementDetails = [];

$error = '';
$success = '';

// Get main categories (ones without parent)
$mainCategories = $pdo->query('SELECT * FROM sub_category WHERE parent_id IS NULL ORDER BY name')->fetchAll();

// If editing existing product
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch();

    if ($product) {
        // Get variants
        $stmt = $pdo->prepare('SELECT * FROM product_variants WHERE product_id = ?');
        $stmt->execute([$_GET['id']]);
        $variants = $stmt->fetchAll();

        // Get images
        $stmt = $pdo->prepare('SELECT * FROM product_images WHERE product_id = ?');
        $stmt->execute([$_GET['id']]);
        $images = $stmt->fetchAll();

        // Get supplement details
        try {
            $stmt = $pdo->prepare('SELECT * FROM supplement_details WHERE product_id = ?');
            $stmt->execute([$_GET['id']]);
            $supplementDetails = $stmt->fetch() ?: [];
        } catch (PDOException $e) {
            // supplement_details table might not exist
            $supplementDetails = [];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Basic product information
        $isNewProduct = empty($_POST['product_id']);
        $productId = $isNewProduct ? bin2hex(random_bytes(16)) : $_POST['product_id'];

        // Product data
        $productData = [
            'product_id' => $productId,
            'name' => $_POST['name'] ?? '',
            'short_description' => $_POST['short_description'] ?? '',
            'short_description_image' => $product['short_description_image'] ?? '', // Keep existing or set after upload
            'long_description' => $_POST['long_description'] ?? '',
            'long_description_image' => $product['long_description_image'] ?? '', // Keep existing or set after upload
            'key_benefits' => $_POST['key_benefits'] ?? '',
            'key_benefits_image' => $product['key_benefits_image'] ?? '', // Keep existing or set after upload
            'how_to_use' => $_POST['how_to_use'] ?? '',
            'how_to_use_images' => '', // Will be set after upload
            'ingredients' => $_POST['ingredients'] ?? '',
            'ingredients_image' => $product['ingredients_image'] ?? '', // Keep existing or set after upload
            'price' => $_POST['price'] ?? 0,
            'category_id' => $_POST['category_id'] ?? '',
            'stock_quantity' => $_POST['stock_quantity'] ?? 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        // Handle How To Use Images upload (if any)
        $howToUseImages = [];
        if (!empty($product['how_to_use_images'])) {
            $howToUseImages = json_decode($product['how_to_use_images'], true) ?? [];
        }
        if (!empty($_FILES['how_to_use_images']['name'][0])) {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            foreach ($_FILES['how_to_use_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['how_to_use_images']['error'][$key] === 0) {
                    $ext = strtolower(pathinfo($_FILES['how_to_use_images']['name'][$key], PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedTypes)) {
                        $filename = bin2hex(random_bytes(8)) . '_' . $_FILES['how_to_use_images']['name'][$key];
                        $upload_path = '../assets/how-to-use/' . $filename;
                        if (!file_exists('../assets/how-to-use/')) {
                            mkdir('../assets/how-to-use/', 0777, true);
                        }
                        if (move_uploaded_file($_FILES['how_to_use_images']['tmp_name'][$key], $upload_path)) {
                            $howToUseImages[] = 'assets/how-to-use/' . $filename;
                        }
                    }
                }
            }
        }
        $productData['how_to_use_images'] = json_encode($howToUseImages);

        if ($isNewProduct) {
            $sql = "INSERT INTO products (product_id, name, short_description, short_description_image, long_description, long_description_image, key_benefits, key_benefits_image, how_to_use, how_to_use_images, ingredients, ingredients_image, price, category_id, stock_quantity, is_active)
                    VALUES (:product_id, :name, :short_description, :short_description_image, :long_description, :long_description_image, :key_benefits, :key_benefits_image, :how_to_use, :how_to_use_images, :ingredients, :ingredients_image, :price, :category_id, :stock_quantity, :is_active)";
        } else {
            $sql = "UPDATE products SET
                    name = :name,
                    short_description = :short_description,
                    short_description_image = :short_description_image,
                    long_description = :long_description,
                    long_description_image = :long_description_image,
                    key_benefits = :key_benefits,
                    key_benefits_image = :key_benefits_image,
                    how_to_use = :how_to_use,
                    how_to_use_images = :how_to_use_images,
                    ingredients = :ingredients,
                    ingredients_image = :ingredients_image,
                    price = :price,
                    category_id = :category_id,
                    stock_quantity = :stock_quantity,
                    is_active = :is_active
                    WHERE product_id = :product_id";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($productData);

        // Remove redundant updates for price and ingredients
        // $stmt = $pdo->prepare('UPDATE products SET ingredients = ? WHERE product_id = ?');
        // $stmt->execute([$_POST['ingredients'] ?? '', $productId]);
        // $stmt = $pdo->prepare('UPDATE products SET price = ? WHERE product_id = ?');
        // $stmt->execute([$_POST['price'] ?? 0, $productId]);

        // Handle variants
        $totalStock = 0;
        if (isset($_POST['variants'])) {
            // Delete existing variants
            $stmt = $pdo->prepare('DELETE FROM product_variants WHERE product_id = ?');
            $stmt->execute([$productId]);

            // Add new variants
            foreach ($_POST['variants'] as $variant) {
                if (!empty($variant['size'])) {
                    $stmt = $pdo->prepare('INSERT INTO product_variants (variant_id, product_id, size, price_modifier, stock) 
                                         VALUES (?, ?, ?, ?, ?)');
                    $stmt->execute([
                        bin2hex(random_bytes(16)),
                        $productId,
                        $variant['size'],
                        $variant['price_modifier'],
                        $variant['stock']
                    ]);
                    $totalStock += (int)$variant['stock'];
                }
            }
        }
        // Update stock_quantity in products table
        $stmt = $pdo->prepare('UPDATE products SET stock_quantity = ? WHERE product_id = ?');
        $stmt->execute([$totalStock, $productId]);

        // Handle image uploads
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = '../assets/';
            $primarySet = false;
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === 0) {
                    $originalName = basename($_FILES['images']['name'][$key]);
                    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedTypes)) {
                        // Create unique filename to avoid conflicts
                        $fileName = 'product_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                        $targetPath = $uploadDir . $fileName;
                        if (move_uploaded_file($tmp_name, $targetPath)) {
                            // Determine if this should be primary
                            $isPrimary = 0;
                            if (isset($_POST['primary_image']) && $_POST['primary_image'] == $key) {
                                $isPrimary = 1;
                                $primarySet = true;
                            } elseif (!$primarySet && $key == 0 && !isset($_POST['primary_image'])) {
                                // If no primary selected, set first image as primary
                                $isPrimary = 1;
                                $primarySet = true;
                            }
                            $stmt = $pdo->prepare('INSERT INTO product_images (image_id, product_id, image_url, is_primary)
                                                 VALUES (?, ?, ?, ?)');
                            $stmt->execute([
                                bin2hex(random_bytes(16)),
                                $productId,
                                'assets/' . $fileName,
                                $isPrimary
                            ]);
                        }
                    }
                }
            }
        }

        // Handle section image uploads
        $uploadDir = '../assets/';
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        // Short Description Image
        if (!empty($_FILES['short_description_image']['name'])) {
            $file = $_FILES['short_description_image'];
            if ($file['error'] === 0) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowedTypes)) {
                    $fileName = 'section_short_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $productData['short_description_image'] = 'assets/' . $fileName;
                    }
                }
            }
        }

        // Long Description Image
        if (!empty($_FILES['long_description_image']['name'])) {
            $file = $_FILES['long_description_image'];
            if ($file['error'] === 0) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowedTypes)) {
                    $fileName = 'section_long_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $productData['long_description_image'] = 'assets/' . $fileName;
                    }
                }
            }
        }

        // Key Benefits Image
        if (!empty($_FILES['key_benefits_image']['name'])) {
            $file = $_FILES['key_benefits_image'];
            if ($file['error'] === 0) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowedTypes)) {
                    $fileName = 'section_benefits_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $productData['key_benefits_image'] = 'assets/' . $fileName;
                    }
                }
            }
        }

        // Ingredients Image
        if (!empty($_FILES['ingredients_image']['name'])) {
            $file = $_FILES['ingredients_image'];
            if ($file['error'] === 0) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowedTypes)) {
                    $fileName = 'section_ingredients_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $productData['ingredients_image'] = 'assets/' . $fileName;
                    }
                }
            }
        }

        // Handle supplement details
        if (isset($_POST['supplement_details']) && $_POST['supplement_details'] == '1') {
            // Check if supplement details already exist
            $checkStmt = $pdo->prepare('SELECT detail_id FROM supplement_details WHERE product_id = ?');
            $checkStmt->execute([$productId]);
            $existingDetail = $checkStmt->fetch();

            $supplementData = [
                'product_id' => $productId,
                'serving_size' => $_POST['serving_size'] ?? null,
                'servings_per_container' => $_POST['servings_per_container'] ?? null,
                'calories' => $_POST['calories'] ?? null,
                'protein' => $_POST['protein'] ?? null,
                'carbs' => $_POST['carbs'] ?? null,
                'fats' => $_POST['fats'] ?? null,
                'fiber' => $_POST['fiber'] ?? null,
                'sodium' => $_POST['sodium'] ?? null,
                'ingredients' => $_POST['supplement_ingredients'] ?? null,
                'directions' => $_POST['directions'] ?? null,
                'warnings' => $_POST['warnings'] ?? null,
                'weight_value' => $_POST['weight_value'] ?? null,
                'weight_unit' => $_POST['weight_unit'] ?? null
            ];

            if ($existingDetail) {
                // Update existing supplement details
                $supplementSql = "UPDATE supplement_details SET
                    serving_size = :serving_size,
                    servings_per_container = :servings_per_container,
                    calories = :calories,
                    protein = :protein,
                    carbs = :carbs,
                    fats = :fats,
                    fiber = :fiber,
                    sodium = :sodium,
                    ingredients = :ingredients,
                    directions = :directions,
                    warnings = :warnings,
                    weight_value = :weight_value,
                    weight_unit = :weight_unit
                    WHERE product_id = :product_id";
            } else {
                // Insert new supplement details
                $supplementData['detail_id'] = bin2hex(random_bytes(16));
                $supplementSql = "INSERT INTO supplement_details
                    (detail_id, product_id, serving_size, servings_per_container, calories, protein, carbs, fats, fiber, sodium, ingredients, directions, warnings, weight_value, weight_unit)
                    VALUES (:detail_id, :product_id, :serving_size, :servings_per_container, :calories, :protein, :carbs, :fats, :fiber, :sodium, :ingredients, :directions, :warnings, :weight_value, :weight_unit)";
            }

            try {
                $supplementStmt = $pdo->prepare($supplementSql);
                $supplementStmt->execute($supplementData);
            } catch (PDOException $e) {
                // If supplement_details table doesn't exist, continue without error
                error_log("Supplement details save failed (table might not exist): " . $e->getMessage());
            }
        }

        $pdo->commit();
        $success = 'Product saved successfully!';

        // Debug: Log successful save
        error_log("Product saved successfully: ID = $productId, Name = " . ($productData['name'] ?? 'Unknown'));

        if ($isNewProduct) {
            header('Location: product-edit.php?id=' . $productId . '&success=1');
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error saving product: ' . $e->getMessage();

        // Debug: Log the error
        error_log("Product save error: " . $e->getMessage());
        error_log("POST data: " . print_r($_POST, true));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_GET['id']) ? 'Edit' : 'Add'; ?> Product - Alpha Nutrition Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            <div class="admin-content-header">
                <h1><?php echo isset($_GET['id']) ? 'Edit' : 'Add New'; ?> Product</h1>
                <a href="products.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success || isset($_GET['success'])): ?>
                <div class="success-message"><?php echo $success ?: 'Product saved successfully!'; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id'] ?? ''); ?>">

                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($mainCategories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category_id'] ?? ''); ?>"
                                    <?php echo ($product['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name'] ?? 'Unnamed Category'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="name">Product Name *</label>
                    <input type="text" id="name" name="name" required
                           value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                </div>



                <div class="form-group">
                    <label for="price">Base Price (₹)</label>
                    <input type="number" id="price" name="price" step="0.01" required value="<?php echo htmlspecialchars($product['price'] ?? 0); ?>">
                    <small class="form-text text-muted">This is the base price. Variant prices are calculated as base price + price modifier.</small>
                </div>

                <div class="form-group">
                    <label>Short Description</label>
                    <textarea name="short_description" class="form-control" rows="2"><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
                    <small class="form-text text-muted">Brief overview of the product (1-2 sentences)</small>
                </div>

                <div class="form-group">
                    <label>Short Description Image</label>
                    <input type="file" name="short_description_image" class="form-control-file" accept="image/*">
                    <small class="form-text text-muted">Image to display with product highlights section</small>
                    <?php if(!empty($product['short_description_image'])): ?>
                    <div class="mt-2">
                        <p>Current Image:</p>
                        <img src="../<?= htmlspecialchars($product['short_description_image']) ?>" alt="Short description" class="img-thumbnail" style="max-width: 150px;">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Long Description</label>
                    <textarea name="long_description" class="form-control rich-editor" rows="5"><?= htmlspecialchars($product['long_description'] ?? '') ?></textarea>
                    <small class="form-text text-muted">Detailed product description with formatting</small>
                </div>

                <div class="form-group">
                    <label>Long Description Image</label>
                    <input type="file" name="long_description_image" class="form-control-file" accept="image/*">
                    <small class="form-text text-muted">Image to display with detailed description section</small>
                    <?php if(!empty($product['long_description_image'])): ?>
                    <div class="mt-2">
                        <p>Current Image:</p>
                        <img src="../<?= htmlspecialchars($product['long_description_image']) ?>" alt="Long description" class="img-thumbnail" style="max-width: 150px;">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Key Benefits</label>
                    <textarea name="key_benefits" class="form-control rich-editor" rows="4"><?= htmlspecialchars($product['key_benefits'] ?? '') ?></textarea>
                    <small class="form-text text-muted">List the main benefits of the product</small>
                </div>

                <div class="form-group">
                    <label>Key Benefits Image</label>
                    <input type="file" name="key_benefits_image" class="form-control-file" accept="image/*">
                    <small class="form-text text-muted">Image to display with key benefits section</small>
                    <?php if(!empty($product['key_benefits_image'])): ?>
                    <div class="mt-2">
                        <p>Current Image:</p>
                        <img src="../<?= htmlspecialchars($product['key_benefits_image']) ?>" alt="Key benefits" class="img-thumbnail" style="max-width: 150px;">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>How To Use</label>
                    <textarea name="how_to_use" class="form-control rich-editor" rows="4"><?= htmlspecialchars($product['how_to_use'] ?? '') ?></textarea>
                    <small class="form-text text-muted">Detailed usage instructions</small>
                </div>

                <div class="form-group">
                    <label>How To Use Images</label>
                    <input type="file" name="how_to_use_images[]" class="form-control-file" multiple accept="image/*">
                    <small class="form-text text-muted">Upload images showing how to use the product</small>
                    <?php if(!empty($product['how_to_use_images'])): ?>
                    <div class="mt-2">
                        <p>Current Images:</p>
                        <?php foreach(json_decode($product['how_to_use_images'], true) as $image): ?>
                        <img src="<?= htmlspecialchars($image) ?>" alt="How to use" class="img-thumbnail" style="max-width: 100px;">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label>Ingredients</label>
                    <textarea name="ingredients" class="form-control rich-editor" rows="4"><?= htmlspecialchars($product['ingredients'] ?? '') ?></textarea>
                    <small class="form-text text-muted">List all ingredients in the product</small>
                </div>

                <div class="form-group">
                    <label>Ingredients Image</label>
                    <input type="file" name="ingredients_image" class="form-control-file" accept="image/*">
                    <small class="form-text text-muted">Image to display with ingredients section</small>
                    <?php if(!empty($product['ingredients_image'])): ?>
                    <div class="mt-2">
                        <p>Current Image:</p>
                        <img src="../<?= htmlspecialchars($product['ingredients_image']) ?>" alt="Ingredients" class="img-thumbnail" style="max-width: 150px;">
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Supplement Details Section -->
                <div class="form-section">
                    <h3>Supplement Details (Optional)</h3>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="supplement_details" value="1"
                                   <?php echo (!empty($supplementDetails) && array_filter($supplementDetails)) ? 'checked' : ''; ?>>
                            Enable supplement details for this product
                        </label>
                        <small class="form-text text-muted">Check this to add nutritional information and supplement-specific details</small>
                    </div>

                    <div id="supplementDetailsSection" style="<?php echo (!empty($supplementDetails) && array_filter($supplementDetails)) ? '' : 'display: none;'; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Serving Size</label>
                                <input type="text" name="serving_size" placeholder="e.g., 30g, 1 scoop"
                                       value="<?php echo htmlspecialchars($supplementDetails['serving_size'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Servings per Container</label>
                                <input type="number" name="servings_per_container"
                                       value="<?php echo htmlspecialchars($supplementDetails['servings_per_container'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Calories per Serving</label>
                                <input type="number" name="calories"
                                       value="<?php echo htmlspecialchars($supplementDetails['calories'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Protein (g)</label>
                                <input type="number" name="protein" step="0.1"
                                       value="<?php echo htmlspecialchars($supplementDetails['protein'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Carbohydrates (g)</label>
                                <input type="number" name="carbs" step="0.1"
                                       value="<?php echo htmlspecialchars($supplementDetails['carbs'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Total Fat (g)</label>
                                <input type="number" name="fats" step="0.1"
                                       value="<?php echo htmlspecialchars($supplementDetails['fats'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Dietary Fiber (g)</label>
                                <input type="number" name="fiber" step="0.1"
                                       value="<?php echo htmlspecialchars($supplementDetails['fiber'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Sodium (mg)</label>
                                <input type="number" name="sodium" step="0.1"
                                       value="<?php echo htmlspecialchars($supplementDetails['sodium'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Weight Value</label>
                                <input type="number" name="weight_value" step="0.01"
                                       value="<?php echo htmlspecialchars($supplementDetails['weight_value'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Weight Unit</label>
                                <select name="weight_unit">
                                    <option value="">Select Unit</option>
                                    <option value="g" <?php echo ($supplementDetails['weight_unit'] ?? '') === 'g' ? 'selected' : ''; ?>>Grams (g)</option>
                                    <option value="kg" <?php echo ($supplementDetails['weight_unit'] ?? '') === 'kg' ? 'selected' : ''; ?>>Kilograms (kg)</option>
                                    <option value="lb" <?php echo ($supplementDetails['weight_unit'] ?? '') === 'lb' ? 'selected' : ''; ?>>Pounds (lb)</option>
                                    <option value="oz" <?php echo ($supplementDetails['weight_unit'] ?? '') === 'oz' ? 'selected' : ''; ?>>Ounces (oz)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Supplement Ingredients</label>
                            <textarea name="supplement_ingredients" rows="3" placeholder="List all supplement-specific ingredients"><?php echo htmlspecialchars($supplementDetails['ingredients'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">This is separate from the main ingredients field above</small>
                        </div>

                        <div class="form-group">
                            <label>Directions for Use</label>
                            <textarea name="directions" rows="3" placeholder="Detailed usage directions"><?php echo htmlspecialchars($supplementDetails['directions'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Warnings</label>
                            <textarea name="warnings" rows="2" placeholder="Important warnings and precautions"><?php echo htmlspecialchars($supplementDetails['warnings'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Original fields continue below -->
                <div class="form-section">
                    <h3>Product Variants</h3>
                    <div id="variantsContainer">
                        <?php 
                        if (empty($variants)) {
                            $variants = [['variant_id' => '', 'size' => '', 'price_modifier' => '0', 'stock' => '0']];
                        }
                        foreach ($variants as $index => $variant): 
                        ?>
                        <div class="variant-row">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Size/Quantity</label>
                                    <input type="text" 
                                           name="variants[<?php echo $index; ?>][size]" 
                                           placeholder="<?php echo $category['name'] === 'Tablets' ? 'e.g., 60 tablets' : 'e.g., 1kg'; ?>"
                                           value="<?php echo htmlspecialchars($variant['size'] ?? ''); ?>"
                                           class="size-input">
                                </div>
                                <div class="form-group">
                                    <label>Variant Price (₹)</label>
                                    <input type="number" 
                                           name="variants[<?php echo $index; ?>][price_modifier]" 
                                           step="0.01"
                                           value="<?php echo htmlspecialchars($variant['price_modifier'] ?? '0'); ?>"
                                           required>
                                </div>
                                <div class="form-group">
                                    <label>Stock</label>
                                    <input type="number" 
                                           name="variants[<?php echo $index; ?>][stock]" 
                                           value="<?php echo htmlspecialchars($variant['stock'] ?? '0'); ?>">
                                </div>
                                <?php if ($index > 0): ?>
                                <button type="button" class="remove-variant-btn" onclick="removeVariant(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-variant-btn" onclick="addVariant()">
                        Add Another Variant
                    </button>
                </div>

                <script>
                function addVariant() {
                    const container = document.getElementById('variantsContainer');
                    const index = container.children.length;
                    const category = document.getElementById('category').selectedOptions[0].text;
                    const placeholder = category === 'Tablets' ? 'e.g., 60 tablets' : 'e.g., 1kg';
                    
                    const newVariant = document.createElement('div');
                    newVariant.className = 'variant-row';
                    newVariant.innerHTML = `
                        <div class="form-row">
                            <div class="form-group">
                                <label>Size/Quantity</label>
                                <input type="text" name="variants[${index}][size]" placeholder="${placeholder}" class="size-input">
                            </div>
                            <div class="form-group">
                                <label>Variant Price (₹)</label>
                                <input type="number" name="variants[${index}][price_modifier]" step="0.01" value="0" required>
                            </div>
                            <div class="form-group">
                                <label>Stock</label>
                                <input type="number" name="variants[${index}][stock]" value="0">
                            </div>
                            <button type="button" class="remove-variant-btn" onclick="removeVariant(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                    container.appendChild(newVariant);
                }

                function removeVariant(button) {
                    button.closest('.variant-row').remove();
                }

                // Update placeholders when category changes
                document.getElementById('category').addEventListener('change', function() {
                    const category = this.selectedOptions[0].text;
                    const placeholder = category === 'Tablets' ? 'e.g., 60 tablets' : 'e.g., 1kg';
                    document.querySelectorAll('.size-input').forEach(input => {
                        input.placeholder = placeholder;
                    });
                });

                // Toggle supplement details section
                document.querySelector('input[name="supplement_details"]').addEventListener('change', function() {
                    const section = document.getElementById('supplementDetailsSection');
                    if (this.checked) {
                        section.style.display = 'block';
                    } else {
                        section.style.display = 'none';
                    }
                });
                </script>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1"
                               <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                        Active (visible on site)
                    </label>
                </div>



                <!-- Images -->
                <div class="images-section">
                    <h3>Product Images</h3>
                    <?php if (!empty($images)): ?>
                        <div class="current-images">
                            <?php foreach ($images as $image): ?>
                                <div class="image-preview">
                                    <img src="../<?php echo htmlspecialchars($image['image_url'] ?? ''); ?>"
                                         alt="Product image">
                                    <label class="radio-label">
                                        <input type="radio" name="primary_image" value="<?php echo $image['image_id']; ?>"
                                               <?php echo $image['is_primary'] ? 'checked' : ''; ?>>
                                        Primary Image
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label>Add New Images</label>
                        <input type="file" name="images[]" multiple accept="image/*">
                    </div>
                </div>





                <div class="form-actions">
                    <button type="submit" class="primary-btn">
                        <i class="fas fa-save"></i> Save Product
                    </button>
                </div>
            </form>
        </main>
    </div>




    </script>
</body>
</html>
