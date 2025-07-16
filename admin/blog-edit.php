<?php
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$post_id = $_GET['id'] ?? null;
$is_edit = !empty($post_id);

// Initialize post data
$post = [
    'post_id' => '',
    'title' => '',
    'slug' => '',
    'excerpt' => '',
    'content' => '',
    'featured_image' => '',
    'category_id' => '',
    'status' => 'draft',
    'meta_title' => '',
    'meta_description' => '',
    'tags' => '',
    'is_featured' => 0
];

// If editing, fetch existing post
if ($is_edit) {
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE post_id = ?');
    $stmt->execute([$post_id]);
    $existing_post = $stmt->fetch();
    
    if (!$existing_post) {
        $_SESSION['error_message'] = 'Blog post not found';
        header('Location: blogs.php');
        exit();
    }
    
    $post = array_merge($post, $existing_post);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Check if form data is being received
    error_log('Form submitted with data: ' . print_r($_POST, true));

    // Also display debug info on screen for testing
    if (isset($_GET['debug'])) {
        echo "<div style='background: #f0f0f0; padding: 1rem; margin: 1rem; border-radius: 8px;'>";
        echo "<h3>Debug Info:</h3>";
        echo "<pre>" . print_r($_POST, true) . "</pre>";
        echo "</div>";
    }

    // Validate required fields
    if (empty($_POST['title'])) {
        $_SESSION['error_message'] = 'Title is required';
    } elseif (empty($_POST['content'])) {
        $_SESSION['error_message'] = 'Content is required';
    } else {
        try {
        // Generate UUID for new posts
        if (!$is_edit) {
            $post_id = 'post-' . uniqid();
        }
        
        // Generate slug from title if not provided
        $slug = !empty($_POST['slug']) ? $_POST['slug'] : strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($_POST['title'])));
        $slug = trim($slug, '-');
        
        // Handle file upload
        $featured_image = $post['featured_image']; // Keep existing image
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/blog/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $filename = $slug . '-' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                $featured_image = 'assets/blog/' . $filename;
            }
        }
        
        $post_data = [
            'title' => $_POST['title'],
            'slug' => $slug,
            'excerpt' => $_POST['excerpt'],
            'content' => $_POST['content'],
            'featured_image' => $featured_image,
            'category_id' => !empty($_POST['category_id']) ? $_POST['category_id'] : null,
            'status' => $_POST['status'],
            'meta_title' => $_POST['meta_title'],
            'meta_description' => $_POST['meta_description'],
            'tags' => $_POST['tags'],
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'author_id' => $_SESSION['admin_id']
        ];
        
        if ($is_edit) {
            // Update existing post
            $sql = "UPDATE blog_posts SET 
                    title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?, 
                    category_id = ?, status = ?, meta_title = ?, meta_description = ?, 
                    tags = ?, is_featured = ?, updated_at = NOW()";
            
            if ($_POST['status'] === 'published' && $post['status'] !== 'published') {
                $sql .= ", published_at = NOW()";
            }
            
            $sql .= " WHERE post_id = ?";
            
            $params = array_values($post_data);
            $params[] = $post_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $_SESSION['success_message'] = 'Blog post updated successfully';
        } else {
            // Create new post
            $sql = "INSERT INTO blog_posts (post_id, title, slug, excerpt, content, featured_image, 
                    category_id, status, meta_title, meta_description, tags, is_featured, author_id, published_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, " . 
                    ($_POST['status'] === 'published' ? 'NOW()' : 'NULL') . ")";
            
            $params = [$post_id];
            $params = array_merge($params, array_values($post_data));
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $_SESSION['success_message'] = 'Blog post created successfully';
        }
        
        header('Location: blogs.php');
        exit();

        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error saving blog post: ' . $e->getMessage();
            error_log('Blog creation error: ' . $e->getMessage());
            error_log('SQL Error: ' . print_r($pdo->errorInfo(), true));
        }
    }
}

// Get categories for dropdown
$categories = $pdo->query('SELECT * FROM blog_categories ORDER BY name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit' : 'Create'; ?> Blog Post - Alpha Nutrition Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../styles.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
    
    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdn.tiny.cloud/1/mhl9tlcgdi6wgcx33b8baq459q3jesxpvywdmak9fmlwz4xv/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body class="admin-page">
    <?php include 'includes/admin-header.php'; ?>

    <div class="admin-container">
        <div class="admin-sidebar">
            <?php include 'includes/admin-sidebar.php'; ?>
        </div>
        
        <main class="admin-main">
            <div class="admin-content-header">
                <h1>
                    <i class="fas fa-<?php echo $is_edit ? 'edit' : 'plus'; ?>"></i>
                    <?php echo $is_edit ? 'Edit' : 'Create'; ?> Blog Post
                </h1>
                <div style="display: flex; gap: 10px;">
                    <a href="blogs.php" class="button button-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Posts
                    </a>
                </div>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="admin-form" action="<?php echo $_SERVER['PHP_SELF'] . (isset($_GET['id']) ? '?id=' . urlencode($_GET['id']) : ''); ?>">
                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label for="title">Post Title *</label>
                        <input type="text" id="title" name="title" required 
                               value="<?php echo htmlspecialchars($post['title']); ?>"
                               placeholder="Enter blog post title">
                    </div>
                    <div class="form-group">
                        <label for="slug">URL Slug</label>
                        <input type="text" id="slug" name="slug" 
                               value="<?php echo htmlspecialchars($post['slug']); ?>"
                               placeholder="auto-generated-from-title">
                    </div>
                </div>

                <div class="form-group">
                    <label for="excerpt">Excerpt</label>
                    <textarea id="excerpt" name="excerpt" rows="3" 
                              placeholder="Brief description of the blog post (used in previews)"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="content">Content *</label>
                    <textarea id="content" name="content"><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="featured_image">Featured Image</label>
                        <input type="file" id="featured_image" name="featured_image" accept="image/*">
                        <?php if (!empty($post['featured_image'])): ?>
                            <div class="current-image-preview">
                                <img src="../<?php echo htmlspecialchars($post['featured_image']); ?>"
                                     alt="Current featured image" style="max-width: 200px; height: auto;">
                                <p style="font-size: 0.9rem; color: #6b7280; margin: 8px 0 0 0; font-weight: 500;">Current featured image</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['category_id']); ?>"
                                        <?php echo $post['category_id'] === $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo $post['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tags">Tags</label>
                        <input type="text" id="tags" name="tags" 
                               value="<?php echo htmlspecialchars($post['tags']); ?>"
                               placeholder="fitness, nutrition, supplements (comma-separated)">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_featured" value="1" 
                               <?php echo $post['is_featured'] ? 'checked' : ''; ?>>
                        Featured Post (will be highlighted on the blog page)
                    </label>
                </div>

                <!-- SEO Section -->
                <!-- SEO Section -->
                <div class="form-section" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-top: 2rem;">
                    <h3 style="margin-top: 0; color: #333;">SEO Settings</h3>
                    <div class="form-group">
                        <label for="meta_title">Meta Title</label>
                        <input type="text" id="meta_title" name="meta_title"
                               value="<?php echo htmlspecialchars($post['meta_title']); ?>"
                               placeholder="SEO title (leave empty to use post title)">
                    </div>
                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea id="meta_description" name="meta_description" rows="3"
                                  placeholder="SEO description for search engines"><?php echo htmlspecialchars($post['meta_description']); ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <div class="button-group">
                        <button type="submit" class="button" id="submit-btn">
                            <i class="fas fa-save"></i>
                            <?php echo $is_edit ? 'Update' : 'Create'; ?> Post
                        </button>
                        <button type="button" class="button button-secondary" onclick="testSubmit()" style="background: #ff6b35;">
                            <i class="fas fa-bug"></i> Test Submit
                        </button>
                        <a href="blogs.php" class="button button-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                    </div>
                    <?php if ($is_edit && $post['status'] === 'published'): ?>
                        <a href="../blog-post.php?slug=<?php echo urlencode($post['slug']); ?>"
                           class="button button-secondary" target="_blank">
                            <i class="fas fa-eye"></i> Preview Post
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </main>
    </div>

    <script>
        // Initialize TinyMCE with Enhanced Configuration
        console.log('Initializing TinyMCE...');
        tinymce.init({
            selector: '#content',
            height: 500,
            menubar: 'edit view insert format tools table help',
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | bold italic underline | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | code preview fullscreen | help',

            // Content styling - EXACT match with frontend blog-post.php
            content_style: `
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    font-size: 16px;
                    line-height: 1.6;
                    color: #333;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                }

                h1 {
                    font-size: 2.5rem;
                    font-weight: 600;
                    margin: 2.5rem 0 1rem;
                    color: #2c3e50;
                    line-height: 1.2;
                }

                h2 {
                    font-size: 2rem;
                    font-weight: 600;
                    margin: 2rem 0 1rem;
                    color: #2c3e50;
                    line-height: 1.3;
                }

                h3 {
                    font-size: 1.5rem;
                    font-weight: 600;
                    margin: 1.5rem 0 0.75rem;
                    color: #2c3e50;
                    line-height: 1.4;
                }

                h4 {
                    font-size: 1.25rem;
                    font-weight: 600;
                    margin: 1.25rem 0 0.5rem;
                    color: #2c3e50;
                }

                h5 {
                    font-size: 1.1rem;
                    font-weight: 600;
                    margin: 1rem 0 0.5rem;
                    color: #2c3e50;
                }

                h6 {
                    font-size: 1rem;
                    font-weight: 600;
                    margin: 1rem 0 0.5rem;
                    color: #2c3e50;
                }

                p {
                    margin-bottom: 1rem;
                    line-height: 1.6;
                }

                ul, ol {
                    margin: 1rem 0;
                    padding-left: 2rem;
                }

                li {
                    margin-bottom: 0.5rem;
                    line-height: 1.6;
                }

                img {
                    max-width: 100%;
                    height: auto;
                    border-radius: 8px;
                    margin: 2rem 0;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }

                blockquote {
                    border-left: 4px solid #3b82f6;
                    margin: 1.5rem 0;
                    padding: 1rem 1rem 1rem 2rem;
                    font-style: italic;
                    color: #666;
                    background: #f8fafc;
                    border-radius: 0 8px 8px 0;
                }

                code {
                    background: #f1f5f9;
                    padding: 2px 6px;
                    border-radius: 4px;
                    font-family: 'Monaco', 'Consolas', 'Courier New', monospace;
                    font-size: 0.9rem;
                    color: #e11d48;
                }

                pre {
                    background: #f8fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 1rem;
                    overflow-x: auto;
                    margin: 1.5rem 0;
                }

                pre code {
                    background: none;
                    padding: 0;
                    color: #333;
                    font-size: 0.9rem;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 1.5rem 0;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                }

                table th, table td {
                    padding: 0.75rem 1rem;
                    text-align: left;
                    border-bottom: 1px solid #e2e8f0;
                }

                table th {
                    background: #f8fafc;
                    font-weight: 600;
                    color: #374151;
                }

                a {
                    color: #3b82f6;
                    text-decoration: underline;
                }

                a:hover {
                    color: #1d4ed8;
                }

                strong {
                    font-weight: 600;
                    color: #1f2937;
                }

                em {
                    font-style: italic;
                    color: #4b5563;
                }

                hr {
                    border: none;
                    height: 2px;
                    background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
                    margin: 2rem 0;
                }
            `,

            // Image handling
            images_upload_url: 'upload-image.php',
            automatic_uploads: true,
            images_reuse_filename: true,

            // Advanced features
            branding: false,
            resize: true,
            elementpath: false,
            statusbar: true,

            // Setup callback
            setup: function(editor) {
                editor.on('init', function() {
                    console.log('TinyMCE editor initialized successfully');
                });
            }
        });

        // Auto-generate slug from title
        document.getElementById('title').addEventListener('input', function() {
            const title = this.value;
            const slug = title.toLowerCase()
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

        // Form submission handler
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log('Form submit event triggered');

            try {
                // First, save TinyMCE content to textarea
                if (tinymce.get('content')) {
                    tinymce.triggerSave();
                    console.log('TinyMCE content saved');

                    // Wait a moment for the content to be saved
                    setTimeout(() => {
                        validateAndSubmitForm(e);
                    }, 100);

                    // Prevent immediate submission
                    e.preventDefault();
                    return false;
                } else {
                    // No TinyMCE, validate immediately
                    return validateAndSubmitForm(e);
                }

            } catch (error) {
                console.error('Form submission error:', error);
                // Allow submission even if there's an error
                return true;
            }
        });

        function validateAndSubmitForm(e) {
            // Get form values after TinyMCE save
            const title = document.getElementById('title').value.trim();
            const content = document.getElementById('content').value.trim();

            console.log('Title:', title);
            console.log('Content length:', content.length);
            console.log('Content preview:', content.substring(0, 100));

            // Validate required fields
            if (!title) {
                alert('Please enter a post title.');
                document.getElementById('title').focus();
                if (e) e.preventDefault();
                return false;
            }

            if (!content || content === '<p></p>' || content === '<p><br></p>' || content === '' || content.length < 10) {
                alert('Please enter post content.');
                if (tinymce.get('content')) {
                    tinymce.get('content').focus();
                }
                if (e) e.preventDefault();
                return false;
            }

            // Show loading state
            const submitBtn = document.getElementById('submit-btn');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
            }

            console.log('Form validation passed, submitting...');

            // Submit the form
            if (e) {
                // Re-submit the form
                e.target.submit();
            }

            return true;
        }

        // Test submit function
        function testSubmit() {
            console.log('Test submit clicked');

            // Fill in test data
            const timestamp = new Date().getTime();
            document.getElementById('title').value = 'Test Blog Post ' + timestamp;
            document.getElementById('excerpt').value = 'This is a test excerpt for the blog post created at ' + new Date().toLocaleString();

            // Add content to TinyMCE
            const testContent = `
                <h2>Test Blog Post</h2>
                <p>This is test content for the blog post created at <strong>${new Date().toLocaleString()}</strong></p>
                <h3>Key Points</h3>
                <ul>
                    <li>This is a test blog post</li>
                    <li>Created automatically for testing</li>
                    <li>Contains sample content</li>
                </ul>
                <p>This post was created to test the blog creation functionality.</p>
            `;

            if (tinymce.get('content')) {
                tinymce.get('content').setContent(testContent);
                console.log('Test content added to TinyMCE');
            } else {
                document.getElementById('content').value = testContent;
                console.log('Test content added to textarea');
            }

            // Auto-submit after a short delay
            setTimeout(() => {
                console.log('Auto-submitting test form...');
                document.querySelector('form').submit();
            }, 1000);

            alert('Test data filled in. Form will auto-submit in 1 second...');
        }
    </script>

    <style>
    /* Enhanced Blog Editor Styling */
    .admin-form {
        max-width: none;
    }

    .form-group textarea#content {
        border: none !important;
        padding: 0 !important;
    }

    /* TinyMCE Container Styling */
    .tox-tinymce {
        border: 2px solid #d1d5db !important;
        border-radius: 12px !important;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
    }

    .tox-tinymce:focus-within {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 4px 20px rgba(0, 0, 0, 0.08) !important;
    }

    /* TinyMCE Toolbar Styling */
    .tox .tox-toolbar,
    .tox .tox-toolbar__primary {
        background: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
    }

    .tox .tox-toolbar__group {
        border-color: #e2e8f0 !important;
    }

    /* TinyMCE Editor Area */
    .tox .tox-edit-area {
        border: none !important;
    }

    .tox .tox-edit-area__iframe {
        background: white !important;
    }

    /* TinyMCE Status Bar */
    .tox .tox-statusbar {
        background: #f8fafc !important;
        border-top: 1px solid #e2e8f0 !important;
        color: #6b7280 !important;
    }

    /* Form Section for SEO */
    .form-section {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border: 1px solid #e2e8f0;
        position: relative;
    }

    .form-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        border-radius: 12px 12px 0 0;
    }

    /* Enhanced File Input */
    input[type="file"] {
        position: relative;
        overflow: hidden;
    }

    input[type="file"]:hover {
        border-color: #3b82f6 !important;
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
    }

    /* Current Image Preview */
    .current-image-preview {
        margin-top: 1rem;
        padding: 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }

    .current-image-preview img {
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Enhanced Checkbox Styling */
    .form-group label:has(input[type="checkbox"]) {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    .form-group label:has(input[type="checkbox"]):hover {
        background: #eff6ff;
        border-color: #3b82f6;
    }

    .form-group input[type="checkbox"] {
        width: 20px !important;
        height: 20px;
        margin: 0 !important;
    }

    /* Action Buttons Container */
    .form-actions {
        background: white;
        padding: 2rem;
        border-top: 1px solid #e2e8f0;
        border-radius: 0 0 12px 12px;
        margin: 2rem -2.5rem -2.5rem;
        display: flex;
        gap: 1rem;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .form-actions .button-group {
        display: flex;
        gap: 1rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .form-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .form-actions .button-group {
            flex-direction: column;
        }

        .tox-tinymce {
            border-radius: 8px !important;
        }
    }
    </style>
</body>
</html>
