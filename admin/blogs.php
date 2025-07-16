<?php
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle post deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $post_id = $_POST['delete_post'];
    try {
        $stmt = $pdo->prepare('DELETE FROM blog_posts WHERE post_id = ?');
        $stmt->execute([$post_id]);
        $_SESSION['success_message'] = 'Blog post deleted successfully';
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error deleting blog post';
    }
    header('Location: blogs.php');
    exit();
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $post_id = $_POST['toggle_status'];
    $current_status = $_POST['current_status'];
    
    $new_status = ($current_status === 'published') ? 'draft' : 'published';
    $published_at = ($new_status === 'published') ? 'NOW()' : 'NULL';
    
    try {
        $stmt = $pdo->prepare("UPDATE blog_posts SET status = ?, published_at = $published_at WHERE post_id = ?");
        $stmt->execute([$new_status, $post_id]);
        $_SESSION['success_message'] = "Post status updated to $new_status";
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error updating post status';
    }
    header('Location: blogs.php');
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = 'bp.status = ?';
    $params[] = $status_filter;
}

if ($category_filter) {
    $where_conditions[] = 'bp.category_id = ?';
    $params[] = $category_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get blog posts with category and author info
$query = "
    SELECT bp.*, bc.name as category_name, bc.color as category_color, au.name as author_name
    FROM blog_posts bp
    LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id
    LEFT JOIN admin_users au ON bp.author_id = au.admin_id
    $where_clause
    ORDER BY bp.created_at DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query('SELECT * FROM blog_categories ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Management - Alpha Nutrition Admin</title>
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
                <h1><i class="fas fa-blog"></i> Blog Management</h1>
                <div style="display: flex; gap: 10px;">
                    <a href="blog-categories.php" class="button button-secondary">
                        <i class="fas fa-tags"></i> Manage Categories
                    </a>
                    <a href="blog-edit.php" class="button">
                        <i class="fas fa-plus"></i> New Blog Post
                    </a>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="">All Status</option>
                                <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="category">Category</label>
                            <select name="category" id="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category_id']); ?>"
                                            <?php echo $category_filter === $cat['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="button">Apply Filters</button>
                        <a href="blogs.php" class="button button-secondary">Clear Filters</a>
                    </div>
                </form>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <!-- Blog Posts Table -->
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Featured Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Published</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">
                                    <p>No blog posts found. <a href="blog-edit.php">Create your first blog post</a></p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($post['featured_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($post['featured_image']); ?>"
                                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                                 class="product-thumbnail"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="no-image-placeholder" style="display: none;">
                                                <i class="fas fa-image"></i>
                                                <span>No Image</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-image-placeholder">
                                                <i class="fas fa-image"></i>
                                                <span>No Image</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($post['title']); ?></strong>
                                        <?php if ($post['is_featured']): ?>
                                            <span class="featured-badge" style="background: #ff6b35; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.7rem; margin-left: 5px;">FEATURED</span>
                                        <?php endif; ?>
                                        <br>
                                        <small style="color: #666;"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 100)) . '...'; ?></small>
                                    </td>
                                    <td>
                                        <?php if ($post['category_name']): ?>
                                            <span class="category-badge" style="background: <?php echo htmlspecialchars($post['category_color']); ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem;">
                                                <?php echo htmlspecialchars($post['category_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">No Category</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="toggle_status" value="<?php echo $post['post_id']; ?>">
                                            <input type="hidden" name="current_status" value="<?php echo $post['status']; ?>">
                                            <button type="submit" class="status-btn status-<?php echo $post['status']; ?>" 
                                                    title="Click to toggle status">
                                                <?php echo ucfirst($post['status']); ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?php echo number_format($post['view_count']); ?></td>
                                    <td>
                                        <?php if ($post['published_at']): ?>
                                            <?php echo date('M j, Y', strtotime($post['published_at'])); ?>
                                        <?php else: ?>
                                            <span style="color: #999;">Not published</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <a href="../blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" 
                                           class="edit-btn" title="View Post" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="blog-edit.php?id=<?php echo $post['post_id']; ?>" 
                                           class="edit-btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" class="delete-form" 
                                              onsubmit="return confirm('Are you sure you want to delete this blog post?');">
                                            <input type="hidden" name="delete_post" value="<?php echo $post['post_id']; ?>">
                                            <button type="submit" class="delete-btn" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <style>
    .status-btn {
        padding: 4px 12px;
        border: none;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .status-draft {
        background: #f8f9fa;
        color: #6c757d;
        border: 1px solid #dee2e6;
    }
    
    .status-published {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .status-archived {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .status-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    </style>
</body>
</html>
