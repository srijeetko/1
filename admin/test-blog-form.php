<?php
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
        echo "<h3>Form Data Received:</h3>";
        echo "<pre>" . print_r($_POST, true) . "</pre>";
        
        $post_id = 'test-' . uniqid();
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        
        if (empty($title)) {
            throw new Exception('Title is required');
        }
        
        if (empty($content)) {
            throw new Exception('Content is required');
        }
        
        // Simple insert
        $sql = "INSERT INTO blog_posts (post_id, title, slug, content, status, author_id, created_at) 
                VALUES (?, ?, ?, ?, 'draft', ?, NOW())";
        
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($title)));
        $slug = trim($slug, '-');
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $post_id,
            $title,
            $slug,
            $content,
            $_SESSION['admin_id']
        ]);
        
        if ($result) {
            $message = "✅ Blog post created successfully! Post ID: $post_id";
        } else {
            $message = "❌ Failed to create blog post";
        }
        
    } catch (Exception $e) {
        $message = "❌ Error: " . $e->getMessage();
        echo "<h3>Database Error Info:</h3>";
        echo "<pre>" . print_r($pdo->errorInfo(), true) . "</pre>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Blog Form</title>
    <link href="admin-styles.css" rel="stylesheet">
</head>
<body class="admin-page">
    <div style="max-width: 800px; margin: 2rem auto; padding: 2rem;">
        <h1>Test Blog Creation Form</h1>
        
        <?php if ($message): ?>
            <div style="padding: 1rem; margin: 1rem 0; border-radius: 8px; <?php echo strpos($message, '✅') !== false ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="admin-form">
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" required placeholder="Enter blog title">
            </div>
            
            <div class="form-group">
                <label for="content">Content *</label>
                <textarea id="content" name="content" required rows="10" placeholder="Enter blog content"></textarea>
            </div>
            
            <button type="submit" class="button">
                <i class="fas fa-save"></i> Create Test Post
            </button>
        </form>
        
        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #ddd;">
            <h3>Recent Test Posts</h3>
            <?php
            try {
                $stmt = $pdo->query("SELECT * FROM blog_posts WHERE post_id LIKE 'test-%' ORDER BY created_at DESC LIMIT 5");
                $posts = $stmt->fetchAll();
                
                if ($posts) {
                    echo "<ul>";
                    foreach ($posts as $post) {
                        echo "<li>";
                        echo "<strong>" . htmlspecialchars($post['title']) . "</strong> ";
                        echo "<small>(" . $post['post_id'] . " - " . $post['created_at'] . ")</small>";
                        echo "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No test posts found.</p>";
                }
            } catch (Exception $e) {
                echo "<p>Error loading posts: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="blog-edit.php" class="button button-secondary">← Back to Blog Editor</a>
            <a href="test-blog-db.php" class="button button-secondary">Database Test</a>
        </div>
    </div>
</body>
</html>
