<?php
session_start();
include '../includes/db_connection.php';

// Check if logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    // First get the image path
    $stmt = $pdo->prepare('SELECT image_path FROM banner_images WHERE id = ?');
    $stmt->execute([$id]);
    $image = $stmt->fetch();

    if ($image) {
        // Delete from database
        $stmt = $pdo->prepare('DELETE FROM banner_images WHERE id = ?');
        if ($stmt->execute([$id])) {
            // Delete file from server
            $file_path = '../assets/' . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            $msg = 'Banner deleted successfully!';
        } else {
            $msg = 'Failed to delete banner.';
        }
    }
}

// Handle order update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    $orders = json_decode($_POST['order_data'], true);
    if ($orders) {
        foreach ($orders as $order) {
            $stmt = $pdo->prepare('UPDATE banner_images SET display_order = ? WHERE id = ?');
            $stmt->execute([$order['order'], $order['id']]);
        }
        echo json_encode(['success' => true]);
        exit;
    }
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['banner_image'])) {
    $title = $_POST['title'] ?? '';
    $status = $_POST['status'] ?? 'active';
    $file = $_FILES['banner_image'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeType = mime_content_type($file['tmp_name']);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (in_array($ext, $allowedTypes) && in_array($mimeType, $allowedMimes)) {
            $newName = 'banner_' . bin2hex(random_bytes(16)) . '.' . $ext;
            $target = '../assets/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $target)) {
            // Get max display order
            $maxOrder = $pdo->query('SELECT MAX(display_order) as max_order FROM banner_images')->fetch();
            $newOrder = ($maxOrder['max_order'] ?? 0) + 1;
            
            $stmt = $pdo->prepare('INSERT INTO banner_images (image_path, title, status, display_order) VALUES (?, ?, ?, ?)');
                $stmt->execute([$newName, $title, $status, $newOrder]);
                $msg = 'Banner image uploaded successfully!';
            } else {
                $msg = 'Failed to upload image.';
            }
        } else {
            $msg = 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.';
        }
    } else {
        $msg = 'No file uploaded or upload error.';
    }
}

// Fetch all banner images
$images = $pdo->query('SELECT * FROM banner_images ORDER BY display_order ASC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Banner Images</title>
    <link href="../styles.css" rel="stylesheet">
    <link href="admin-styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .swiper {
            width: 100%;
            height: 300px;
            margin-bottom: 2rem;
        }
        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .banner-preview {
            margin: 2rem 0;
            padding: 1rem;
            background: #f5f5f5;
            border-radius: 8px;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .admin-table th, .admin-table td {
            padding: 1rem;
            border: 1px solid #ddd;
        }
        .admin-table tr {
            cursor: move;
        }
        .banner-actions {
            display: flex;
            gap: 1rem;
        }
        .delete-btn {
            color: #dc3545;
            cursor: pointer;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .status-active {
            background: #28a745;
            color: white;
        }
        .status-inactive {
            background: #dc3545;
            color: white;
        }
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 4px;
            color: white;
            z-index: 1000;
            display: none;
        }
        .notification.success {
            background: #28a745;
        }
        .notification.error {
            background: #dc3545;
        }
    </style>
</head>
<body class="admin-page">
    <?php include 'includes/admin-header.php'; ?>
    <div class="admin-container">
        <div class="admin-sidebar">
            <?php include 'includes/admin-sidebar.php'; ?>
        </div>
        <main class="admin-main">
            <h1>Banner Images</h1>
            <?php if (!empty($msg)) echo '<div class="admin-msg">' . htmlspecialchars($msg) . '</div>'; ?>
            
            <div class="banner-preview">
                <h2>Banner Preview</h2>
                <div class="swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($images as $img): ?>
                            <?php if ($img['status'] === 'active'): ?>
                            <div class="swiper-slide">
                                <img src="../assets/<?= htmlspecialchars($img['image_path'] ?? '') ?>" alt="<?= htmlspecialchars($img['title'] ?? 'Banner Image') ?>">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <label>Title: <input type="text" name="title" required></label><br>
                <label>Image: <input type="file" name="banner_image" accept="image/*" required></label><br>
                <label>Status: 
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label><br>
                <button type="submit">Add Banner Image</button>
            </form>

            <h2>Manage Banner Images</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sortable-banners">
                    <?php foreach ($images as $img): ?>
                    <tr data-id="<?= $img['id'] ?? '' ?>">
                        <td><i class="fas fa-grip-vertical"></i></td>
                        <td><img src="../assets/<?= htmlspecialchars($img['image_path'] ?? '') ?>" style="max-width:120px;"></td>
                        <td><?= htmlspecialchars($img['title'] ?? 'Untitled') ?></td>
                        <td><span class="status-badge status-<?= $img['status'] ?? 'inactive' ?>"><?= htmlspecialchars($img['status'] ?? 'inactive') ?></span></td>
                        <td><?= htmlspecialchars($img['created_at'] ?? '') ?></td>
                        <td class="banner-actions">
                            <i class="fas fa-trash delete-btn" onclick="deleteBanner('<?= $img['id'] ?? '' ?>')"></i>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>

    <div id="notification" class="notification"></div>

    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script>
        // Initialize Swiper
        const swiper = new Swiper('.swiper', {
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            }
        });

        // Initialize Sortable
        new Sortable(document.getElementById('sortable-banners'), {
            animation: 150,
            handle: '.fa-grip-vertical',
            onEnd: function() {
                updateOrder();
            }
        });

        // Update order function
        function updateOrder() {
            const rows = document.querySelectorAll('#sortable-banners tr');
            const orderData = Array.from(rows).map((row, index) => ({
                id: row.dataset.id,
                order: index + 1
            }));

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'update_order=1&order_data=' + encodeURIComponent(JSON.stringify(orderData))
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Order updated successfully', 'success');
                }
            });
        }

        // Delete banner function
        function deleteBanner(id) {
            if (confirm('Are you sure you want to delete this banner?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Notification function
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Show notification for PHP messages
        <?php if (!empty($msg)): ?>
        showNotification('<?= addslashes($msg) ?>', '<?= strpos($msg, 'success') !== false ? 'success' : 'error' ?>');
        <?php endif; ?>
    </script>
</body>
</html>
