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
        // Check if category has posts
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM blog_posts WHERE category_id = ?');
        $stmt->execute([$category_id]);
        $postCount = $stmt->fetch()['count'];

        if ($postCount > 0) {
            throw new Exception('Cannot delete category: It contains blog posts');
        }

        // Delete category
        $stmt = $pdo->prepare('DELETE FROM blog_categories WHERE category_id = ?');
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
            $stmt = $pdo->prepare('UPDATE blog_categories SET name = ?, slug = ?, description = ?, color = ? WHERE category_id = ?');
            $stmt->execute([
                $_POST['name'],
                $_POST['slug'],
                $_POST['description'],
                $_POST['color'],
                $_POST['category_id']
            ]);
            $success = 'Category updated successfully';
        } else {
            // Create
            $category_id = 'cat-' . uniqid();
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($_POST['name'])));
            $slug = trim($slug, '-');
            
            $stmt = $pdo->prepare('INSERT INTO blog_categories (category_id, name, slug, description, color) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $category_id,
                $_POST['name'],
                $slug,
                $_POST['description'],
                $_POST['color']
            ]);
            $success = 'Category created successfully';
        }
    } catch (Exception $e) {
        $error = 'Error saving category: ' . $e->getMessage();
    }
}

// Get all categories with post counts
$stmt = $pdo->query('
    SELECT bc.*, COUNT(bp.post_id) as post_count 
    FROM blog_categories bc 
    LEFT JOIN blog_posts bp ON bc.category_id = bp.category_id 
    GROUP BY bc.category_id 
    ORDER BY bc.name
');
$categories = $stmt->fetchAll();

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM blog_categories WHERE category_id = ?');
    $stmt->execute([$_GET['edit']]);
    $edit_category = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Categories - Alpha Nutrition Admin</title>
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
                <h1><i class="fas fa-tags"></i> Blog Categories</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="blogs.php" class="button button-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Posts
                    </a>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Add/Edit Category Form -->
                <div class="admin-form">
                    <h2><?php echo $edit_category ? 'Edit' : 'Add New'; ?> Category</h2>
                    
                    <form method="POST">
                        <?php if ($edit_category): ?>
                            <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($edit_category['category_id']); ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="name">Category Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>"
                                   placeholder="e.g., Fitness">
                        </div>
                        
                        <div class="form-group">
                            <label for="slug">URL Slug</label>
                            <input type="text" id="slug" name="slug" 
                                   value="<?php echo htmlspecialchars($edit_category['slug'] ?? ''); ?>"
                                   placeholder="auto-generated-from-name">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"
                                      placeholder="Brief description of this category"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="color">Category Color</label>
                            <input type="color" id="color" name="color" 
                                   value="<?php echo htmlspecialchars($edit_category['color'] ?? '#333333'); ?>">
                            <small style="color: #666; font-size: 0.9rem;">Used for category badges</small>
                        </div>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="button">
                                <i class="fas fa-save"></i> 
                                <?php echo $edit_category ? 'Update' : 'Create'; ?> Category
                            </button>
                            <?php if ($edit_category): ?>
                                <a href="blog-categories.php" class="button button-secondary">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Categories List -->
                <div>
                    <h2>Existing Categories</h2>
                    
                    <?php if (empty($categories)): ?>
                        <div class="admin-form" style="text-align: center; padding: 2rem;">
                            <p style="color: #666;">No categories found. Create your first category!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Posts</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <span class="category-badge" 
                                                          style="background: <?php echo htmlspecialchars($category['color']); ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </span>
                                                </div>
                                                <?php if ($category['description']): ?>
                                                    <small style="color: #666; display: block; margin-top: 4px;">
                                                        <?php echo htmlspecialchars($category['description']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span style="font-weight: 500;"><?php echo $category['post_count']; ?></span>
                                                <?php echo $category['post_count'] == 1 ? 'post' : 'posts'; ?>
                                            </td>
                                            <td class="actions">
                                                <a href="blog-categories.php?edit=<?php echo $category['category_id']; ?>" 
                                                   class="edit-btn" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($category['post_count'] == 0): ?>
                                                    <form method="POST" class="delete-form" 
                                                          onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                        <input type="hidden" name="delete_category" 
                                                               value="<?php echo $category['category_id']; ?>">
                                                        <button type="submit" class="delete-btn" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="delete-btn" disabled title="Cannot delete: Category has posts">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value;
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            
            if (!document.getElementById('slug').value || document.getElementById('slug').dataset.auto !== 'false') {
                document.getElementById('slug').value = slug;
            }
        });

        // Mark slug as manually edited
        document.getElementById('slug').addEventListener('input', function() {
            this.dataset.auto = 'false';
        });
    </script>

    <style>
    .delete-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .delete-btn:disabled:hover {
        transform: none;
        background: #fff0f0;
    }
    </style>
</body>
</html>
