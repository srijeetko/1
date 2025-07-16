<?php
include 'includes/header.php';
include 'includes/db_connection.php';

// Get post slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: blog.php');
    exit();
}

// Fetch blog post with category and author info
$stmt = $pdo->prepare('
    SELECT bp.*, bc.name as category_name, bc.color as category_color, au.name as author_name
    FROM blog_posts bp
    LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id
    LEFT JOIN admin_users au ON bp.author_id = au.admin_id
    WHERE bp.slug = ? AND bp.status = "published"
');
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: blog.php');
    exit();
}

// Increment view count
$stmt = $pdo->prepare('UPDATE blog_posts SET view_count = view_count + 1 WHERE post_id = ?');
$stmt->execute([$post['post_id']]);

// Get related posts from same category
$stmt = $pdo->prepare('
    SELECT bp.*, bc.name as category_name, bc.color as category_color
    FROM blog_posts bp
    LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id
    WHERE bp.category_id = ? AND bp.post_id != ? AND bp.status = "published"
    ORDER BY bp.published_at DESC
    LIMIT 3
');
$stmt->execute([$post['category_id'], $post['post_id']]);
$related_posts = $stmt->fetchAll();

// Set page title and meta description
$page_title = !empty($post['meta_title']) ? $post['meta_title'] : $post['title'];
$meta_description = !empty($post['meta_description']) ? $post['meta_description'] : $post['excerpt'];
?>

<style>
/* Blog Post Styles */
.blog-post-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 4rem 0 2rem;
    color: white;
    text-align: center;
}

.blog-post-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
}

.blog-post-meta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    opacity: 0.9;
}

.blog-post-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    line-height: 1.2;
}

.blog-post-excerpt {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.blog-post-content {
    background: white;
    padding: 3rem 0;
}

.blog-post-body {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
    font-size: 1.1rem;
    line-height: 1.8;
    color: #333;
}

/* WYSIWYG Content Styling - Perfect Match with TinyMCE Editor */
.blog-post-body .content {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    font-size: 16px;
    line-height: 1.6;
    color: #333;

    /* Reset any inherited styles */
    text-align: left;
    direction: ltr;

    /* Ensure consistent box model */
    box-sizing: border-box;

    /* Prevent text overflow issues */
    word-wrap: break-word;
    overflow-wrap: break-word;
    hyphens: auto;
}

.blog-post-body .content h1 {
    font-size: 2.5rem;
    font-weight: 600;
    margin: 2.5rem 0 1rem;
    color: #2c3e50;
    line-height: 1.2;
}

.blog-post-body .content h2 {
    font-size: 2rem;
    font-weight: 600;
    margin: 2rem 0 1rem;
    color: #2c3e50;
    line-height: 1.3;
}

.blog-post-body .content h3 {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 1.5rem 0 0.75rem;
    color: #2c3e50;
    line-height: 1.4;
}

.blog-post-body .content h4 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 1.25rem 0 0.5rem;
    color: #2c3e50;
}

.blog-post-body .content h5 {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 1rem 0 0.5rem;
    color: #2c3e50;
}

.blog-post-body .content h6 {
    font-size: 1rem;
    font-weight: 600;
    margin: 1rem 0 0.5rem;
    color: #2c3e50;
}

.blog-post-body .content p {
    margin-bottom: 1rem;
    line-height: 1.6;
}

.blog-post-body .content ul,
.blog-post-body .content ol {
    margin: 1rem 0;
    padding-left: 2rem;
}

.blog-post-body .content li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.blog-post-body .content ul li {
    list-style-type: disc;
}

.blog-post-body .content ol li {
    list-style-type: decimal;
}

.blog-post-body .content img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 2rem 0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.blog-post-body .content blockquote {
    border-left: 4px solid #3b82f6;
    margin: 1.5rem 0;
    padding-left: 1rem;
    font-style: italic;
    color: #666;
    background: #f8fafc;
    padding: 1rem 1rem 1rem 2rem;
    border-radius: 0 8px 8px 0;
}

.blog-post-body .content code {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
    font-size: 0.9rem;
    color: #e11d48;
}

.blog-post-body .content pre {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 1rem;
    overflow-x: auto;
    margin: 1.5rem 0;
}

.blog-post-body .content pre code {
    background: none;
    padding: 0;
    color: #333;
    font-size: 0.9rem;
}

.blog-post-body .content table {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.blog-post-body .content table th,
.blog-post-body .content table td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.blog-post-body .content table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
}

.blog-post-body .content table tr:hover {
    background: #f9fafb;
}

.blog-post-body .content a {
    color: #3b82f6;
    text-decoration: underline;
    transition: color 0.2s ease;
}

.blog-post-body .content a:hover {
    color: #1d4ed8;
}

.blog-post-body .content strong {
    font-weight: 600;
    color: #1f2937;
}

.blog-post-body .content em {
    font-style: italic;
    color: #4b5563;
}

.blog-post-body .content hr {
    border: none;
    height: 2px;
    background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
    margin: 2rem 0;
}

/* Text alignment classes - TinyMCE compatibility */
.blog-post-body .content .text-left,
.blog-post-body .content [style*="text-align: left"] { text-align: left; }

.blog-post-body .content .text-center,
.blog-post-body .content [style*="text-align: center"] { text-align: center; }

.blog-post-body .content .text-right,
.blog-post-body .content [style*="text-align: right"] { text-align: right; }

.blog-post-body .content .text-justify,
.blog-post-body .content [style*="text-align: justify"] { text-align: justify; }

/* Font size classes - TinyMCE compatibility */
.blog-post-body .content .mce-content-body { font-size: inherit; }

/* Color preservation */
.blog-post-body .content [style*="color:"] { /* Preserve inline colors */ }
.blog-post-body .content [style*="background-color:"] { /* Preserve inline backgrounds */ }

/* Font family preservation */
.blog-post-body .content [style*="font-family:"] { /* Preserve inline fonts */ }

/* TinyMCE specific classes */
.blog-post-body .content .mce-item-table { border-collapse: collapse; }
.blog-post-body .content .mce-item-anchor { display: inline-block; }

/* Preserve any custom styling from TinyMCE */
.blog-post-body .content [class*="mce-"] {
    /* Maintain TinyMCE specific styling */
}

/* Additional WYSIWYG Content Enhancements */
.wysiwyg-content {
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Ensure proper spacing for nested elements */
.blog-post-body .content ul ul,
.blog-post-body .content ol ol,
.blog-post-body .content ul ol,
.blog-post-body .content ol ul {
    margin: 0.5rem 0;
}

/* Style for inline elements */
.blog-post-body .content sup {
    font-size: 0.75rem;
    vertical-align: super;
}

.blog-post-body .content sub {
    font-size: 0.75rem;
    vertical-align: sub;
}

.blog-post-body .content mark {
    background: #fef08a;
    padding: 0.1rem 0.2rem;
    border-radius: 2px;
}

/* Responsive images */
.blog-post-body .content figure {
    margin: 2rem 0;
    text-align: center;
}

.blog-post-body .content figure img {
    margin: 0;
}

.blog-post-body .content figcaption {
    font-size: 0.9rem;
    color: #6b7280;
    font-style: italic;
    margin-top: 0.5rem;
}

/* Definition lists */
.blog-post-body .content dl {
    margin: 1rem 0;
}

.blog-post-body .content dt {
    font-weight: 600;
    color: #374151;
    margin-top: 1rem;
}

.blog-post-body .content dd {
    margin-left: 1rem;
    margin-bottom: 0.5rem;
    color: #6b7280;
}

/* Address and contact info */
.blog-post-body .content address {
    font-style: italic;
    margin: 1rem 0;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 4px solid #d1d5db;
}

/* Keyboard shortcuts */
.blog-post-body .content kbd {
    background: #374151;
    color: white;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.85rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

/* Sample output */
.blog-post-body .content samp {
    background: #1f2937;
    color: #10b981;
    padding: 0.5rem;
    border-radius: 4px;
    font-family: monospace;
    display: block;
    margin: 1rem 0;
    overflow-x: auto;
}

/* Variable text */
.blog-post-body .content var {
    font-style: italic;
    color: #7c3aed;
    font-weight: 500;
}

/* Deleted and inserted text */
.blog-post-body .content del {
    text-decoration: line-through;
    color: #ef4444;
    background: #fef2f2;
    padding: 0.1rem 0.2rem;
    border-radius: 2px;
}

.blog-post-body .content ins {
    text-decoration: underline;
    color: #10b981;
    background: #f0fdf4;
    padding: 0.1rem 0.2rem;
    border-radius: 2px;
}

/* Abbreviations */
.blog-post-body .content abbr {
    text-decoration: underline dotted;
    cursor: help;
}

/* Small text */
.blog-post-body .content small {
    font-size: 0.875rem;
    color: #6b7280;
}

/* Ensure proper line height for all text elements */
.blog-post-body .content * {
    line-height: inherit;
}

/* Print styles for blog content */
@media print {
    .blog-post-body .content {
        font-size: 12pt;
        line-height: 1.5;
        color: black;
    }

    .blog-post-body .content a {
        color: black;
        text-decoration: underline;
    }

    .blog-post-body .content a[href]:after {
        content: " (" attr(href) ")";
        font-size: 0.8em;
        color: #666;
    }
}

.blog-post-featured-image {
    width: 100%;
    max-height: 400px;
    object-fit: cover;
    border-radius: 12px;
    margin: 2rem 0;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.blog-post-tags {
    margin: 2rem 0;
    padding: 2rem 0;
    border-top: 1px solid #e5e5e5;
    border-bottom: 1px solid #e5e5e5;
}

.blog-post-tags h4 {
    margin-bottom: 1rem;
    color: #666;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tag {
    display: inline-block;
    background: #f8f9fa;
    color: #666;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.85rem;
    margin: 0.25rem 0.5rem 0.25rem 0;
    text-decoration: none;
    transition: all 0.3s ease;
}

.tag:hover {
    background: #ff6b35;
    color: white;
}

.related-posts {
    background: #f8f9fa;
    padding: 3rem 0;
}

.related-posts-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.related-posts h3 {
    text-align: center;
    font-size: 2rem;
    font-weight: 600;
    margin-bottom: 2rem;
    color: #333;
}

.related-posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.related-post-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    text-decoration: none;
    color: inherit;
}

.related-post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    text-decoration: none;
    color: inherit;
}

.related-post-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.related-post-content {
    padding: 1.5rem;
}

.related-post-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #333;
    line-height: 1.3;
}

.related-post-excerpt {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.related-post-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: #999;
}

/* Responsive Design for WYSIWYG Content */
@media (max-width: 768px) {
    .blog-post-title {
        font-size: 2rem;
    }

    .blog-post-excerpt {
        font-size: 1.1rem;
    }

    .blog-post-body {
        font-size: 1rem;
        padding: 0 15px;
    }

    .blog-post-hero {
        padding: 2rem 0 1rem;
    }

    .blog-post-content {
        padding: 2rem 0;
    }

    /* Mobile-specific WYSIWYG content adjustments */
    .blog-post-body .content h1 {
        font-size: 2rem;
        margin: 2rem 0 1rem;
    }

    .blog-post-body .content h2 {
        font-size: 1.75rem;
        margin: 1.5rem 0 0.75rem;
    }

    .blog-post-body .content h3 {
        font-size: 1.5rem;
        margin: 1.25rem 0 0.5rem;
    }

    .blog-post-body .content h4 {
        font-size: 1.25rem;
    }

    .blog-post-body .content table {
        font-size: 0.9rem;
        overflow-x: auto;
        display: block;
        white-space: nowrap;
    }

    .blog-post-body .content pre {
        font-size: 0.85rem;
        overflow-x: auto;
    }

    .blog-post-body .content blockquote {
        margin: 1rem 0;
        padding: 0.75rem 0.75rem 0.75rem 1.5rem;
    }

    .blog-post-body .content ul,
    .blog-post-body .content ol {
        padding-left: 1.5rem;
    }
}

@media (max-width: 480px) {
    .blog-post-body .content {
        font-size: 15px;
    }

    .blog-post-body .content h1 {
        font-size: 1.75rem;
    }

    .blog-post-body .content h2 {
        font-size: 1.5rem;
    }

    .blog-post-body .content h3 {
        font-size: 1.25rem;
    }

    .blog-post-body .content table th,
    .blog-post-body .content table td {
        padding: 0.5rem;
    }
}
</style>

<!-- Blog Post Hero -->
<section class="blog-post-hero">
    <div class="blog-post-container">
        <div class="blog-post-meta">
            <?php if ($post['category_name']): ?>
                <span class="category-badge" style="background: <?php echo htmlspecialchars($post['category_color']); ?>; color: white; padding: 4px 12px; border-radius: 15px; font-size: 0.8rem;">
                    <?php echo htmlspecialchars($post['category_name']); ?>
                </span>
            <?php endif; ?>
            <span><?php echo date('F j, Y', strtotime($post['published_at'])); ?></span>
            <?php if ($post['author_name']): ?>
                <span>by <?php echo htmlspecialchars($post['author_name']); ?></span>
            <?php endif; ?>
            <span><?php echo number_format($post['view_count']); ?> views</span>
        </div>
        
        <h1 class="blog-post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
        
        <?php if ($post['excerpt']): ?>
            <p class="blog-post-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- Blog Post Content -->
<section class="blog-post-content">
    <div class="blog-post-body">
        <?php if ($post['featured_image']): ?>
            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                 alt="<?php echo htmlspecialchars($post['title']); ?>" 
                 class="blog-post-featured-image"
                 onerror="this.style.display='none'">
        <?php endif; ?>
        
        <div class="content wysiwyg-content">
            <?php
            // Output the content exactly as stored (true WYSIWYG)
            // Content is already sanitized by TinyMCE and stored safely
            echo $post['content'];
            ?>
        </div>
        
        <?php if ($post['tags']): ?>
            <div class="blog-post-tags">
                <h4>Tags</h4>
                <?php 
                $tags = explode(',', $post['tags']);
                foreach ($tags as $tag): 
                    $tag = trim($tag);
                    if ($tag):
                ?>
                    <a href="blog.php?tag=<?php echo urlencode($tag); ?>" class="tag">
                        <?php echo htmlspecialchars($tag); ?>
                    </a>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Related Posts -->
<?php if (!empty($related_posts)): ?>
<section class="related-posts">
    <div class="related-posts-container">
        <h3>Related Articles</h3>
        <div class="related-posts-grid">
            <?php foreach ($related_posts as $related): ?>
                <a href="blog-post.php?slug=<?php echo urlencode($related['slug']); ?>" class="related-post-card">
                    <?php if ($related['featured_image']): ?>
                        <img src="<?php echo htmlspecialchars($related['featured_image']); ?>" 
                             alt="<?php echo htmlspecialchars($related['title']); ?>" 
                             class="related-post-image"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                    <div class="related-post-content">
                        <h4 class="related-post-title"><?php echo htmlspecialchars($related['title']); ?></h4>
                        <?php if ($related['excerpt']): ?>
                            <p class="related-post-excerpt"><?php echo htmlspecialchars(substr($related['excerpt'], 0, 120)) . '...'; ?></p>
                        <?php endif; ?>
                        <div class="related-post-meta">
                            <?php if ($related['category_name']): ?>
                                <span class="category-badge" style="background: <?php echo htmlspecialchars($related['category_color']); ?>; color: white; padding: 2px 6px; border-radius: 8px; font-size: 0.7rem;">
                                    <?php echo htmlspecialchars($related['category_name']); ?>
                                </span>
                            <?php endif; ?>
                            <span><?php echo date('M j, Y', strtotime($related['published_at'])); ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

<!-- Update page title and meta description -->
<script>
document.title = "<?php echo htmlspecialchars($page_title); ?> - Alpha Nutrition";

// Update meta description
let metaDescription = document.querySelector('meta[name="description"]');
if (metaDescription) {
    metaDescription.setAttribute('content', "<?php echo htmlspecialchars($meta_description); ?>");
} else {
    metaDescription = document.createElement('meta');
    metaDescription.name = 'description';
    metaDescription.content = "<?php echo htmlspecialchars($meta_description); ?>";
    document.head.appendChild(metaDescription);
}
</script>
