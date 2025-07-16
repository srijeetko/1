<?php
include 'includes/header.php';
include 'includes/db_connection.php';

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$tag_filter = $_GET['tag'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query conditions
$where_conditions = ["bp.status = 'published'"];
$params = [];

if ($category_filter) {
    $where_conditions[] = 'bp.category_id = ?';
    $params[] = $category_filter;
}

if ($tag_filter) {
    $where_conditions[] = 'bp.tags LIKE ?';
    $params[] = '%' . $tag_filter . '%';
}

if ($search_query) {
    $where_conditions[] = '(bp.title LIKE ? OR bp.excerpt LIKE ? OR bp.content LIKE ?)';
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
    $params[] = '%' . $search_query . '%';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get blog posts
$query = "
    SELECT bp.*, bc.name as category_name, bc.color as category_color, au.name as author_name
    FROM blog_posts bp
    LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id
    LEFT JOIN admin_users au ON bp.author_id = au.admin_id
    $where_clause
    ORDER BY bp.is_featured DESC, bp.published_at DESC
    LIMIT 20
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query('SELECT * FROM blog_categories ORDER BY name')->fetchAll();

// Get featured post
$featured_post = null;
foreach ($posts as $post) {
    if ($post['is_featured']) {
        $featured_post = $post;
        break;
    }
}
?>

<style>
/* Blog Page Styles */
.blog-section {
    padding: 4rem 0;
    background: #f8f9fa;
}

.blog-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.blog-title {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 3rem;
    position: relative;
}

.blog-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: #ff6b35;
    border-radius: 2px;
}

.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.blog-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    position: relative;
}

.blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.blog-image {
    width: 100%;
    height: 220px;
    object-fit: cover;
    position: relative;
}

.blog-category {
    position: absolute;
    top: 15px;
    left: 15px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: white;
}

.category-fitness {
    background: #6c5ce7;
}

.category-lifestyle {
    background: #fd79a8;
}

.category-nutrition {
    background: #e17055;
}

.blog-content {
    padding: 1.5rem;
}

.blog-card-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.8rem;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.blog-excerpt {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.blog-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.8rem;
    color: #888;
    border-top: 1px solid #eee;
    padding-top: 1rem;
}

.blog-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.blog-author {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.read-more-btn {
    background: #ff6b35;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.read-more-btn:hover {
    background: #e55a2b;
    transform: translateY(-1px);
    color: white;
    text-decoration: none;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .blog-section {
        padding: 3rem 0;
    }
    
    .blog-container {
        padding: 0 15px;
    }
    
    .blog-title {
        font-size: 2rem;
        margin-bottom: 2rem;
    }
    
    .blog-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .blog-card {
        border-radius: 8px;
    }
    
    .blog-image {
        height: 200px;
    }
    
    .blog-content {
        padding: 1.2rem;
    }
    
    .blog-card-title {
        font-size: 1.1rem;
    }
    
    .blog-excerpt {
        font-size: 0.85rem;
    }
    
    .blog-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}

@media (max-width: 480px) {
    .blog-title {
        font-size: 1.8rem;
    }
    
    .blog-grid {
        gap: 1rem;
    }
    
    .blog-content {
        padding: 1rem;
    }
    
    .blog-category {
        top: 10px;
        left: 10px;
        padding: 4px 8px;
        font-size: 0.7rem;
    }
}
</style>

<section class="blog-section">
    <div class="blog-container">
        <h1 class="blog-title">Health & Wellness Blog</h1>
        <p style="text-align: center; font-size: 1.1rem; color: #666; max-width: 600px; margin: 0 auto 2rem;">
            Discover expert insights on nutrition, fitness, and healthy living to help you achieve your wellness goals.
        </p>

        <!-- Search and Filter -->
        <div class="blog-filters" style="background: white; padding: 2rem; border-radius: 12px; margin-bottom: 3rem; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
            <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
                <div style="flex: 1; min-width: 200px;">
                    <label for="search" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333;">Search</label>
                    <input type="text" id="search" name="search"
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="Search articles..."
                           style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                </div>
                <div style="min-width: 150px;">
                    <label for="category" style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333;">Category</label>
                    <select id="category" name="category"
                            style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category_id']); ?>"
                                    <?php echo $category_filter === $cat['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="submit" style="padding: 0.75rem 1.5rem; background: #ff6b35; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: background 0.3s ease;">
                        Search
                    </button>
                    <a href="blog.php" style="display: inline-block; padding: 0.75rem 1.5rem; background: #f8f9fa; color: #666; text-decoration: none; border-radius: 8px; margin-left: 0.5rem; transition: background 0.3s ease;">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <?php if ($featured_post): ?>
        <!-- Featured Post -->
        <div class="featured-post" style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); margin-bottom: 3rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0; min-height: 300px;">
                <div style="position: relative;">
                    <?php if ($featured_post['featured_image']): ?>
                        <img src="<?php echo htmlspecialchars($featured_post['featured_image']); ?>"
                             alt="<?php echo htmlspecialchars($featured_post['title']); ?>"
                             style="width: 100%; height: 100%; object-fit: cover;"
                             onerror="this.src='https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
                    <?php endif; ?>
                    <span class="blog-category" style="position: absolute; top: 1rem; left: 1rem; background: <?php echo htmlspecialchars($featured_post['category_color'] ?? '#ff6b35'); ?>; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: 500;">
                        FEATURED
                    </span>
                </div>
                <div style="padding: 2rem; display: flex; flex-direction: column; justify-content: center;">
                    <h2 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 1rem; color: #333; line-height: 1.3;">
                        <a href="blog-post.php?slug=<?php echo urlencode($featured_post['slug']); ?>" style="color: inherit; text-decoration: none;">
                            <?php echo htmlspecialchars($featured_post['title']); ?>
                        </a>
                    </h2>
                    <p style="color: #666; margin-bottom: 1.5rem; line-height: 1.6;">
                        <?php echo htmlspecialchars(substr($featured_post['excerpt'], 0, 150)) . '...'; ?>
                    </p>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; font-size: 0.9rem; color: #999;">
                        <?php if ($featured_post['category_name']): ?>
                            <span class="blog-category" style="background: <?php echo htmlspecialchars($featured_post['category_color']); ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem;">
                                <?php echo htmlspecialchars($featured_post['category_name']); ?>
                            </span>
                        <?php endif; ?>
                        <span><?php echo date('M j, Y', strtotime($featured_post['published_at'])); ?></span>
                        <span><?php echo number_format($featured_post['view_count']); ?> views</span>
                    </div>
                    <a href="blog-post.php?slug=<?php echo urlencode($featured_post['slug']); ?>"
                       style="display: inline-block; background: #ff6b35; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 500; transition: background 0.3s ease; align-self: flex-start;">
                        Read More
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="blog-grid">
            <?php if (empty($posts)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);">
                    <i class="fas fa-search" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <h3 style="color: #666; margin-bottom: 0.5rem;">No articles found</h3>
                    <p style="color: #999;">Try adjusting your search criteria or browse all articles.</p>
                    <a href="blog.php" style="display: inline-block; margin-top: 1rem; background: #ff6b35; color: white; padding: 0.75rem 1.5rem; border-radius: 8px; text-decoration: none; font-weight: 500;">
                        View All Articles
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <?php if ($post['is_featured'] && $featured_post) continue; // Skip featured post in grid ?>
                    <article class="blog-card">
                        <div style="position: relative;">
                            <?php if ($post['featured_image']): ?>
                                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                                     class="blog-image"
                                     onerror="this.src='https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
                            <?php else: ?>
                                <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                                     alt="<?php echo htmlspecialchars($post['title']); ?>"
                                     class="blog-image">
                            <?php endif; ?>
                            <?php if ($post['category_name']): ?>
                                <span class="blog-category" style="background: <?php echo htmlspecialchars($post['category_color']); ?>;">
                                    <?php echo htmlspecialchars($post['category_name']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="blog-content">
                            <h2 class="blog-card-title">
                                <a href="blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo htmlspecialchars($post['title']); ?>
                                </a>
                            </h2>
                            <p class="blog-excerpt">
                                <?php echo htmlspecialchars(substr($post['excerpt'], 0, 150)) . '...'; ?>
                            </p>
                            <div class="blog-meta">
                                <div class="blog-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
                                </div>
                                <div class="blog-author">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($post['author_name'] ?? 'Alpha Nutrition'); ?></span>
                                </div>
                                <div class="blog-views">
                                    <i class="fas fa-eye"></i>
                                    <span><?php echo number_format($post['view_count']); ?></span>
                                </div>
                            </div>
                            <a href="blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" class="read-more-btn">Read More</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>


        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
