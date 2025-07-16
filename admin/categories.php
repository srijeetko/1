<?php
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle category deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $category_id = $_POST['delete_category'];
    try {
        // Check if category has products
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM products WHERE category_id = ?');
        $stmt->execute([$category_id]);
        $productCount = $stmt->fetch()['count'];

        if ($productCount > 0) {
            throw new Exception('Cannot delete category: It contains products');
        }

        // Delete category
        $stmt = $pdo->prepare('DELETE FROM sub_category WHERE category_id = ?');
        $stmt->execute([$category_id]);
        $success = 'Category deleted successfully';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle category creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    try {
        if (isset($_POST['category_id']) && $_POST['category_id']) {
            // Update
            $stmt = $pdo->prepare('UPDATE sub_category SET name = ?, parent_id = ? WHERE category_id = ?');
            $stmt->execute([
                $_POST['name'],
                !empty($_POST['parent_id']) ? $_POST['parent_id'] : null,
                $_POST['category_id']
            ]);
            $success = 'Category updated successfully';
        } else {
            // Create
            $stmt = $pdo->prepare('INSERT INTO sub_category (category_id, name, parent_id) VALUES (?, ?, ?)');
            $stmt->execute([
                bin2hex(random_bytes(16)),
                $_POST['name'],
                !empty($_POST['parent_id']) ? $_POST['parent_id'] : null
            ]);
            $success = 'Category created successfully';
        }
    } catch (Exception $e) {
        $error = 'Error saving category: ' . $e->getMessage();
    }
}

// Get all categories
$categories = $pdo->query('
    SELECT c.*, parent.name as parent_name, 
           (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) as product_count
    FROM sub_category c
    LEFT JOIN sub_category parent ON c.parent_id = parent.category_id
    ORDER BY c.name
')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Alpha Nutrition Admin</title>
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
                <h1>Manage Categories</h1>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>

            <!-- Add/Edit Category Form -->
            <div class="admin-form-section">
                <h2>Add New Category</h2>
                <form method="POST" class="admin-form" id="categoryForm">
                    <input type="hidden" name="category_id" id="category_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Category Name *</label>
                            <input type="text" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="parent_id">Parent Category</label>
                            <select name="parent_id" id="parent_id">
                                <option value="">None (Top Level)</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo htmlspecialchars($category['name'] ?? 'Unnamed Category'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>



                    <div class="form-actions">
                        <button type="submit" class="primary-btn">
                            <i class="fas fa-save"></i> Save Category
                        </button>
                        <button type="button" class="secondary-btn" id="resetForm">
                            <i class="fas fa-times"></i> Clear Form
                        </button>
                    </div>
                </form>
            </div>

            <!-- Categories Table -->
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Parent Category</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name'] ?? 'Unnamed Category'); ?></td>
                                <td><?php echo htmlspecialchars($category['parent_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($category['description'] ?? '-'); ?></td>
                                <td><?php echo $category['product_count']; ?></td>
                                <td class="actions">
                                    <button class="edit-btn" title="Edit" 
                                            onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($category['product_count'] == 0): ?>
                                        <form method="POST" class="delete-form" style="display: inline;"
                                              onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            <input type="hidden" name="delete_category" 
                                                   value="<?php echo $category['category_id']; ?>">
                                            <button type="submit" class="delete-btn" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
    function editCategory(category) {
        document.getElementById('category_id').value = category.category_id;
        document.getElementById('name').value = category.name;
        document.getElementById('description').value = category.description || '';
        document.getElementById('parent_id').value = category.parent_id || '';
        
        // Update form title
        document.querySelector('.admin-form-section h2').textContent = 'Edit Category';
        
        // Scroll to form
        document.querySelector('.admin-form-section').scrollIntoView({ behavior: 'smooth' });
    }

    document.getElementById('resetForm').addEventListener('click', function() {
        document.getElementById('categoryForm').reset();
        document.getElementById('category_id').value = '';
        document.querySelector('.admin-form-section h2').textContent = 'Add New Category';
    });
    </script>
</body>
</html>
