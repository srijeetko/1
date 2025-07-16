<?php
include '../includes/db_connection.php';

if (!isset($pdo) || !$pdo) {
    die('Database connection not established. Please check your configuration.');
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_step':
                try {
                    $stepId = 'step-' . uniqid();
                    $sql = "INSERT INTO product_usage_steps (step_id, product_id, step_number, step_title, step_description, step_image, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $stepId,
                        $_POST['product_id'],
                        $_POST['step_number'],
                        $_POST['step_title'],
                        $_POST['step_description'],
                        $_POST['step_image']
                    ]);
                    $message = "Usage step added successfully!";
                } catch (Exception $e) {
                    $error = "Error adding step: " . $e->getMessage();
                }
                break;
                
            case 'update_step':
                try {
                    $sql = "UPDATE product_usage_steps SET step_title = ?, step_description = ?, step_image = ? WHERE step_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_POST['step_title'],
                        $_POST['step_description'],
                        $_POST['step_image'],
                        $_POST['step_id']
                    ]);
                    $message = "Usage step updated successfully!";
                } catch (Exception $e) {
                    $error = "Error updating step: " . $e->getMessage();
                }
                break;
                
            case 'delete_step':
                try {
                    $sql = "DELETE FROM product_usage_steps WHERE step_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$_POST['step_id']]);
                    $message = "Usage step deleted successfully!";
                } catch (Exception $e) {
                    $error = "Error deleting step: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get products for dropdown
$productsStmt = $pdo->query("SELECT product_id, name FROM products WHERE is_active = 1 ORDER BY name");
$products = $productsStmt->fetchAll();

// Get selected product's usage steps
$selectedProductId = $_GET['product_id'] ?? ($_POST['product_id'] ?? '');
$usageSteps = [];
if ($selectedProductId) {
    $stepsStmt = $pdo->prepare("SELECT * FROM product_usage_steps WHERE product_id = ? ORDER BY step_number");
    $stepsStmt->execute([$selectedProductId]);
    $usageSteps = $stepsStmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Usage Steps - Alpha Nutrition Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; }
        .header { background: #2874f0; color: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        textarea { height: 80px; resize: vertical; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-right: 10px; }
        .btn-primary { background: #2874f0; color: #fff; }
        .btn-success { background: #28a745; color: #fff; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .steps-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .steps-table th, .steps-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .steps-table th { background: #f8f9fa; }
        .step-image { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Product Usage Steps</h1>
            <p>Add, edit, and manage "How to Use" steps for products</p>
        </div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="grid">
            <!-- Product Selection -->
            <div>
                <h3>Select Product</h3>
                <form method="GET">
                    <div class="form-group">
                        <label for="product_id">Choose Product:</label>
                        <select name="product_id" id="product_id" onchange="this.form.submit()">
                            <option value="">-- Select a Product --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['product_id']; ?>" 
                                        <?php echo $selectedProductId === $product['product_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <?php if ($selectedProductId): ?>
                    <p><a href="../product-detail.php?id=<?php echo $selectedProductId; ?>" target="_blank" class="btn btn-secondary">Preview Product Page</a></p>
                <?php endif; ?>
            </div>

            <!-- Add New Step -->
            <?php if ($selectedProductId): ?>
            <div>
                <h3>Add New Usage Step</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_step">
                    <input type="hidden" name="product_id" value="<?php echo $selectedProductId; ?>">
                    
                    <div class="form-group">
                        <label for="step_number">Step Number:</label>
                        <input type="number" name="step_number" id="step_number" min="1" max="10" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="step_title">Step Title:</label>
                        <input type="text" name="step_title" id="step_title" required placeholder="e.g., Mix with Water">
                    </div>
                    
                    <div class="form-group">
                        <label for="step_description">Step Description:</label>
                        <textarea name="step_description" id="step_description" required placeholder="Detailed description of this step..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="step_image">Step Image Path:</label>
                        <input type="text" name="step_image" id="step_image" placeholder="assets/how-to-use/image.jpg">
                    </div>
                    
                    <button type="submit" class="btn btn-success">Add Step</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Existing Steps -->
        <?php if ($selectedProductId && !empty($usageSteps)): ?>
        <h3>Existing Usage Steps</h3>
        <table class="steps-table">
            <thead>
                <tr>
                    <th>Step #</th>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usageSteps as $step): ?>
                <tr>
                    <td><?php echo $step['step_number']; ?></td>
                    <td>
                        <?php if ($step['step_image']): ?>
                            <img src="../<?php echo htmlspecialchars($step['step_image']); ?>" 
                                 alt="Step <?php echo $step['step_number']; ?>" 
                                 class="step-image"
                                 onerror="this.style.display='none';">
                        <?php else: ?>
                            <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #666;">No Image</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($step['step_title']); ?></td>
                    <td><?php echo htmlspecialchars(substr($step['step_description'], 0, 100)) . (strlen($step['step_description']) > 100 ? '...' : ''); ?></td>
                    <td>
                        <button onclick="editStep('<?php echo $step['step_id']; ?>', '<?php echo htmlspecialchars($step['step_title']); ?>', '<?php echo htmlspecialchars($step['step_description']); ?>', '<?php echo htmlspecialchars($step['step_image']); ?>')" class="btn btn-primary">Edit</button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this step?')">
                            <input type="hidden" name="action" value="delete_step">
                            <input type="hidden" name="step_id" value="<?php echo $step['step_id']; ?>">
                            <input type="hidden" name="product_id" value="<?php echo $selectedProductId; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php elseif ($selectedProductId): ?>
        <div class="alert alert-info">
            <p>No usage steps found for this product. Add some steps using the form above.</p>
        </div>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p><a href="../test-products.php" class="btn btn-secondary">← Back to Products</a></p>
        </div>
    </div>

    <!-- Edit Modal (Simple JavaScript) -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px;">
            <h3>Edit Usage Step</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="update_step">
                <input type="hidden" name="step_id" id="edit_step_id">
                <input type="hidden" name="product_id" value="<?php echo $selectedProductId; ?>">
                
                <div class="form-group">
                    <label for="edit_step_title">Step Title:</label>
                    <input type="text" name="step_title" id="edit_step_title" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_step_description">Step Description:</label>
                    <textarea name="step_description" id="edit_step_description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit_step_image">Step Image Path:</label>
                    <input type="text" name="step_image" id="edit_step_image">
                </div>
                
                <button type="submit" class="btn btn-success">Update Step</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function editStep(stepId, title, description, image) {
            document.getElementById('edit_step_id').value = stepId;
            document.getElementById('edit_step_title').value = title;
            document.getElementById('edit_step_description').value = description;
            document.getElementById('edit_step_image').value = image;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>
