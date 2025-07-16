<?php
// Create Blog Management Tables
include '../includes/db_connection.php';

echo "<h1>Creating Blog Management Tables</h1>";

try {
    // Blog Categories Table
    $sql_categories = "
    CREATE TABLE IF NOT EXISTS blog_categories (
        category_id CHAR(36) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        color VARCHAR(7) DEFAULT '#333333',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql_categories);
    echo "✅ Created blog_categories table<br>";

    // Blog Posts Table
    $sql_posts = "
    CREATE TABLE IF NOT EXISTS blog_posts (
        post_id CHAR(36) PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        excerpt TEXT,
        content LONGTEXT NOT NULL,
        featured_image VARCHAR(500),
        category_id CHAR(36),
        author_id CHAR(36),
        status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
        meta_title VARCHAR(255),
        meta_description TEXT,
        tags TEXT,
        view_count INT DEFAULT 0,
        is_featured TINYINT(1) DEFAULT 0,
        published_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES blog_categories(category_id) ON DELETE SET NULL,
        FOREIGN KEY (author_id) REFERENCES admin_users(admin_id) ON DELETE SET NULL,
        INDEX idx_status (status),
        INDEX idx_published_at (published_at),
        INDEX idx_category (category_id),
        INDEX idx_featured (is_featured)
    )";
    
    $pdo->exec($sql_posts);
    echo "✅ Created blog_posts table<br>";

    // Blog Comments Table (for future use)
    $sql_comments = "
    CREATE TABLE IF NOT EXISTS blog_comments (
        comment_id CHAR(36) PRIMARY KEY,
        post_id CHAR(36) NOT NULL,
        author_name VARCHAR(100) NOT NULL,
        author_email VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (post_id) REFERENCES blog_posts(post_id) ON DELETE CASCADE,
        INDEX idx_post_status (post_id, status)
    )";
    
    $pdo->exec($sql_comments);
    echo "✅ Created blog_comments table<br>";

    // Insert default blog categories
    $default_categories = [
        ['id' => 'cat-fitness', 'name' => 'Fitness', 'slug' => 'fitness', 'description' => 'Fitness tips and workout guides', 'color' => '#ff6b35'],
        ['id' => 'cat-nutrition', 'name' => 'Nutrition', 'slug' => 'nutrition', 'description' => 'Nutrition advice and supplement guides', 'color' => '#28a745'],
        ['id' => 'cat-lifestyle', 'name' => 'Healthy Lifestyle', 'slug' => 'healthy-lifestyle', 'description' => 'Tips for maintaining a healthy lifestyle', 'color' => '#007bff'],
        ['id' => 'cat-supplements', 'name' => 'Supplements', 'slug' => 'supplements', 'description' => 'Information about various supplements', 'color' => '#6f42c1']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO blog_categories (category_id, name, slug, description, color) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($default_categories as $category) {
        $stmt->execute([$category['id'], $category['name'], $category['slug'], $category['description'], $category['color']]);
    }
    
    echo "✅ Inserted default blog categories<br>";

    // Create sample blog posts from existing static content
    $sample_posts = [
        [
            'id' => 'post-taurine-benefits',
            'title' => 'Taurine Benefits And Side effects',
            'slug' => 'taurine-benefits-side-effects',
            'excerpt' => 'If you\'re an energy drink lover, chances are you\'ve encountered the word "taurine" for a line or two. Sometimes, the phrase "With taurine" is printed on the can or bottle...',
            'content' => '<p>If you\'re an energy drink lover, chances are you\'ve encountered the word "taurine" for a line or two. Sometimes, the phrase "With taurine" is printed on the can or bottle...</p><p>Taurine is a naturally occurring amino acid that plays crucial roles in various bodily functions. From supporting cardiovascular health to enhancing athletic performance, taurine offers numerous benefits when consumed appropriately.</p><h2>What is Taurine?</h2><p>Taurine is a semi-essential amino acid that your body produces naturally. It\'s found in high concentrations in the brain, heart, muscles, and other tissues.</p><h2>Benefits of Taurine</h2><ul><li>Supports heart health</li><li>May improve exercise performance</li><li>Supports brain function</li><li>May help with diabetes management</li></ul><h2>Potential Side Effects</h2><p>While taurine is generally safe for most people, some may experience mild side effects when consuming large amounts...</p>',
            'category_id' => 'cat-fitness',
            'featured_image' => 'assets/blog-fitness.jpg'
        ],
        [
            'id' => 'post-probiotic-guide',
            'title' => 'Probiotic Supplement: Everything You Need To Know',
            'slug' => 'probiotic-supplement-complete-guide',
            'excerpt' => 'Overview The human gut — a complex and fascinating ecosystem teeming with billions of microorganisms. These microscopic, friendly and not-so-friendly residents...',
            'content' => '<p>The human gut — a complex and fascinating ecosystem teeming with billions of microorganisms. These microscopic, friendly and not-so-friendly residents play a crucial role in our overall health and well-being.</p><h2>Understanding Probiotics</h2><p>Probiotics are live microorganisms that, when administered in adequate amounts, confer a health benefit on the host. They are often called "good" or "friendly" bacteria.</p><h2>Benefits of Probiotic Supplements</h2><ul><li>Improved digestive health</li><li>Enhanced immune function</li><li>Better nutrient absorption</li><li>Potential mood benefits</li></ul><h2>Choosing the Right Probiotic</h2><p>When selecting a probiotic supplement, consider factors such as strain diversity, CFU count, and storage requirements...</p>',
            'category_id' => 'cat-lifestyle',
            'featured_image' => 'assets/blog-lifestyle.jpg'
        ],
        [
            'id' => 'post-whey-protein-2024',
            'title' => 'Best Affordable Whey Protein Powders For 2024',
            'slug' => 'best-affordable-whey-protein-2024',
            'excerpt' => 'Whey protein, a supplement that has been the subject of extensive global research, is notable for its high nutritional value and the wide range of health benefits it provides...',
            'content' => '<p>Whey protein, a supplement that has been the subject of extensive global research, is notable for its high nutritional value and the wide range of health benefits it provides.</p><h2>What Makes Whey Protein Special?</h2><p>Whey protein is a complete protein containing all nine essential amino acids. It\'s rapidly absorbed by the body, making it ideal for post-workout recovery.</p><h2>Top Affordable Options for 2024</h2><ol><li>Alpha Nutrition Premium Whey</li><li>Budget-friendly alternatives</li><li>Value for money considerations</li></ol><h2>How to Choose the Right Whey Protein</h2><p>Consider factors such as protein content per serving, flavor options, additional ingredients, and third-party testing...</p>',
            'category_id' => 'cat-nutrition',
            'featured_image' => 'assets/blog-nutrition.jpg'
        ]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO blog_posts (post_id, title, slug, excerpt, content, category_id, featured_image, status, published_at, author_id) VALUES (?, ?, ?, ?, ?, ?, ?, 'published', NOW(), ?)");
    
    // Get first admin user ID for author
    $admin_stmt = $pdo->query("SELECT admin_id FROM admin_users LIMIT 1");
    $admin = $admin_stmt->fetch();
    $author_id = $admin ? $admin['admin_id'] : null;

    foreach ($sample_posts as $post) {
        $stmt->execute([
            $post['id'], 
            $post['title'], 
            $post['slug'], 
            $post['excerpt'], 
            $post['content'], 
            $post['category_id'], 
            $post['featured_image'],
            $author_id
        ]);
    }
    
    echo "✅ Inserted sample blog posts<br>";

    echo "<h2>✅ Blog Management System Setup Complete!</h2>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li>Manage blog categories</li>";
    echo "<li>Create and edit blog posts</li>";
    echo "<li>Publish/unpublish posts</li>";
    echo "<li>Manage comments (future feature)</li>";
    echo "</ul>";
    
    echo "<p><a href='blogs.php'>Go to Blog Management</a></p>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 40px; }
h1 { color: #333; }
h2 { color: #666; }
ul { margin: 20px 0; }
li { margin: 5px 0; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>
