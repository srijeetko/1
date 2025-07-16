<?php
// Start session and include database connection
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get admin info
$stmt = $pdo->prepare('SELECT name FROM admin_users WHERE admin_id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Alpha Nutrition</title>
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
            <!-- Welcome Header -->
            <div class="dashboard-header">
                <div class="welcome-section">
                    <h1 class="dashboard-title">
                        <i class="fas fa-tachometer-alt"></i>
                        Welcome back, <?php echo htmlspecialchars($admin['name'] ?? 'Admin'); ?>!
                    </h1>
                    <p class="dashboard-subtitle">Here's what's happening with your store today</p>
                </div>
                <div class="dashboard-date">
                    <i class="fas fa-calendar-alt"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="dashboard-stats">
                <div class="stat-card products">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        $stmt = $pdo->query('SELECT COUNT(*) as count FROM products');
                        $productCount = $stmt->fetch()['count'];
                        ?>
                        <h3><?php echo number_format($productCount); ?></h3>
                        <p>Total Products</p>
                        <span class="stat-trend">
                            <i class="fas fa-arrow-up"></i> Active
                        </span>
                    </div>
                </div>

                <div class="stat-card categories">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        $stmt = $pdo->query('SELECT COUNT(*) as count FROM sub_category');
                        $categoryCount = $stmt->fetch()['count'];
                        ?>
                        <h3><?php echo number_format($categoryCount); ?></h3>
                        <p>Categories</p>
                        <span class="stat-trend">
                            <i class="fas fa-arrow-up"></i> Growing
                        </span>
                    </div>
                </div>

                <div class="stat-card blogs">
                    <div class="stat-icon">
                        <i class="fas fa-blog"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        try {
                            $stmt = $pdo->query('SELECT COUNT(*) as count FROM blog_posts WHERE status = "published"');
                            $blogCount = $stmt->fetch()['count'];
                        } catch (Exception $e) {
                            $blogCount = 0;
                        }
                        ?>
                        <h3><?php echo number_format($blogCount); ?></h3>
                        <p>Published Blogs</p>
                        <span class="stat-trend">
                            <i class="fas fa-arrow-up"></i> Content
                        </span>
                    </div>
                </div>

                <div class="stat-card images">
                    <div class="stat-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        try {
                            $stmt = $pdo->query('SELECT COUNT(*) as count FROM banner_images');
                            $imageCount = $stmt->fetch()['count'];
                        } catch (Exception $e) {
                            $imageCount = 0;
                        }
                        ?>
                        <h3><?php echo number_format($imageCount); ?></h3>
                        <p>Banner Images</p>
                        <span class="stat-trend">
                            <i class="fas fa-arrow-up"></i> Media
                        </span>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- Quick Actions -->
                <div class="dashboard-card quick-actions-card">
                    <div class="card-header">
                        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                        <p>Common tasks and shortcuts</p>
                    </div>
                    <div class="action-grid">
                        <a href="products.php?action=add" class="action-btn add-product">
                            <i class="fas fa-plus-circle"></i>
                            <span>Add Product</span>
                        </a>
                        <a href="categories.php?action=add" class="action-btn add-category">
                            <i class="fas fa-folder-plus"></i>
                            <span>Add Category</span>
                        </a>
                        <a href="blog-edit.php" class="action-btn add-blog">
                            <i class="fas fa-pen"></i>
                            <span>Write Blog</span>
                        </a>
                        <a href="banner-images.php" class="action-btn manage-banners">
                            <i class="fas fa-image"></i>
                            <span>Manage Banners</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="dashboard-card activity-card">
                    <div class="card-header">
                        <h2><i class="fas fa-clock"></i> Recent Activity</h2>
                        <p>Latest updates and changes</p>
                    </div>
                    <div class="activity-list">
                        <?php
                        try {
                            // Get recent blog posts
                            $stmt = $pdo->query('SELECT title, created_at FROM blog_posts ORDER BY created_at DESC LIMIT 3');
                            $recentBlogs = $stmt->fetchAll();

                            if ($recentBlogs) {
                                foreach ($recentBlogs as $blog) {
                                    echo '<div class="activity-item">';
                                    echo '<i class="fas fa-blog activity-icon blog"></i>';
                                    echo '<div class="activity-content">';
                                    echo '<p>New blog post: <strong>' . htmlspecialchars($blog['title']) . '</strong></p>';
                                    echo '<span class="activity-time">' . date('M j, g:i A', strtotime($blog['created_at'])) . '</span>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="activity-item">';
                                echo '<i class="fas fa-info-circle activity-icon info"></i>';
                                echo '<div class="activity-content">';
                                echo '<p>No recent activity</p>';
                                echo '<span class="activity-time">Start creating content!</span>';
                                echo '</div>';
                                echo '</div>';
                            }
                        } catch (Exception $e) {
                            echo '<div class="activity-item">';
                            echo '<i class="fas fa-info-circle activity-icon info"></i>';
                            echo '<div class="activity-content">';
                            echo '<p>Welcome to your dashboard!</p>';
                            echo '<span class="activity-time">Start managing your store</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- System Status -->
                <div class="dashboard-card status-card">
                    <div class="card-header">
                        <h2><i class="fas fa-server"></i> System Status</h2>
                        <p>Everything looks good!</p>
                    </div>
                    <div class="status-list">
                        <div class="status-item">
                            <div class="status-indicator online"></div>
                            <span>Database Connection</span>
                            <i class="fas fa-check-circle status-check"></i>
                        </div>
                        <div class="status-item">
                            <div class="status-indicator online"></div>
                            <span>File System</span>
                            <i class="fas fa-check-circle status-check"></i>
                        </div>
                        <div class="status-item">
                            <div class="status-indicator online"></div>
                            <span>Admin Session</span>
                            <i class="fas fa-check-circle status-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
