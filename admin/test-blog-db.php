<?php
include '../includes/db_connection.php';

echo "<h2>Blog Database Test</h2>";

try {
    // Check if blog tables exist
    $tables = ['blog_categories', 'blog_posts', 'blog_comments'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists<br>";
            
            // Show table structure
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            echo "<details><summary>View $table structure</summary>";
            echo "<table border='1' style='margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";
            }
            echo "</table></details>";
        } else {
            echo "❌ Table '$table' does NOT exist<br>";
        }
    }
    
    // Test inserting a sample post
    echo "<h3>Testing Blog Post Creation</h3>";
    
    $test_post_id = 'test-post-' . time();
    $test_data = [
        'post_id' => $test_post_id,
        'title' => 'Test Blog Post',
        'slug' => 'test-blog-post-' . time(),
        'excerpt' => 'This is a test excerpt',
        'content' => '<p>This is test content for the blog post.</p>',
        'featured_image' => '',
        'category_id' => null,
        'status' => 'draft',
        'meta_title' => '',
        'meta_description' => '',
        'tags' => 'test',
        'is_featured' => 0,
        'author_id' => null
    ];
    
    $sql = "INSERT INTO blog_posts (post_id, title, slug, excerpt, content, featured_image, 
            category_id, status, meta_title, meta_description, tags, is_featured, author_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute(array_values($test_data));
    
    if ($result) {
        echo "✅ Test blog post created successfully<br>";
        
        // Clean up test post
        $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE post_id = ?");
        $stmt->execute([$test_post_id]);
        echo "✅ Test post cleaned up<br>";
    } else {
        echo "❌ Failed to create test blog post<br>";
        echo "Error: " . print_r($pdo->errorInfo(), true) . "<br>";
    }
    
    // Check admin users table
    echo "<h3>Admin Users Check</h3>";
    $stmt = $pdo->query("SELECT admin_id, name FROM admin_users LIMIT 1");
    $admin = $stmt->fetch();
    if ($admin) {
        echo "✅ Admin user found: {$admin['name']} (ID: {$admin['admin_id']})<br>";
    } else {
        echo "❌ No admin users found<br>";
    }
    
    // Check categories
    echo "<h3>Blog Categories Check</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM blog_categories");
    $count = $stmt->fetch()['count'];
    echo "📊 Blog categories count: $count<br>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT * FROM blog_categories LIMIT 5");
        $categories = $stmt->fetchAll();
        echo "<ul>";
        foreach ($categories as $cat) {
            echo "<li>{$cat['name']} (ID: {$cat['category_id']})</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br><a href='blog-edit.php'>← Back to Blog Editor</a>";
echo "<br><a href='create-blog-tables.php'>Run Table Creation Script</a>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; }
th, td { padding: 8px; text-align: left; }
details { margin: 10px 0; }
summary { cursor: pointer; font-weight: bold; }
</style>
