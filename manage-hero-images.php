<?php
/**
 * Manage Hero Images
 */

include 'includes/db_connection.php';

echo "<h1>🖼️ Manage Hero Images</h1>";

// Handle actions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'deactivate_all':
                $stmt = $pdo->prepare("UPDATE banner_images SET status = 'inactive'");
                $stmt->execute();
                echo "<div style='background: #d4edda; padding: 1rem; border-radius: 8px; margin: 1rem 0; border: 1px solid #c3e6cb;'>";
                echo "<p style='color: #155724; margin: 0;'>✅ All hero images have been deactivated. Only the text slide will show.</p>";
                echo "</div>";
                break;
                
            case 'activate_all':
                $stmt = $pdo->prepare("UPDATE banner_images SET status = 'active'");
                $stmt->execute();
                echo "<div style='background: #d4edda; padding: 1rem; border-radius: 8px; margin: 1rem 0; border: 1px solid #c3e6cb;'>";
                echo "<p style='color: #155724; margin: 0;'>✅ All hero images have been activated.</p>";
                echo "</div>";
                break;
                
            case 'delete_all':
                // Get all image paths first
                $stmt = $pdo->query("SELECT image_path FROM banner_images");
                $images = $stmt->fetchAll();
                
                // Delete files
                foreach ($images as $img) {
                    $filePath = 'assets/' . $img['image_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
                
                // Delete from database
                $pdo->exec("DELETE FROM banner_images");
                echo "<div style='background: #d4edda; padding: 1rem; border-radius: 8px; margin: 1rem 0; border: 1px solid #c3e6cb;'>";
                echo "<p style='color: #155724; margin: 0;'>✅ All hero images have been deleted permanently.</p>";
                echo "</div>";
                break;
                
            case 'toggle_image':
                $imageId = $_POST['image_id'];
                $newStatus = $_POST['new_status'];
                $stmt = $pdo->prepare("UPDATE banner_images SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $imageId]);
                echo "<div style='background: #d4edda; padding: 1rem; border-radius: 8px; margin: 1rem 0; border: 1px solid #c3e6cb;'>";
                echo "<p style='color: #155724; margin: 0;'>✅ Image status updated.</p>";
                echo "</div>";
                break;
        }
    }
}

try {
    // Get current hero images
    $stmt = $pdo->query("SELECT * FROM banner_images ORDER BY display_order ASC");
    $heroImages = $stmt->fetchAll();
    
    echo "<h2>Current Hero Images:</h2>";
    
    if (empty($heroImages)) {
        echo "<div style='background: #fff3cd; padding: 1rem; border-radius: 8px; margin: 1rem 0; border: 1px solid #ffeaa7;'>";
        echo "<p style='color: #856404; margin: 0;'>ℹ️ No hero images found. Only the text slide will be displayed.</p>";
        echo "</div>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 2rem;'>";
        echo "<tr style='background: #f5f5f5;'>";
        echo "<th style='padding: 8px;'>Preview</th>";
        echo "<th style='padding: 8px;'>Image Path</th>";
        echo "<th style='padding: 8px;'>Title</th>";
        echo "<th style='padding: 8px;'>Status</th>";
        echo "<th style='padding: 8px;'>Actions</th>";
        echo "</tr>";
        
        foreach ($heroImages as $image) {
            $imagePath = 'assets/' . $image['image_path'];
            $imageExists = file_exists($imagePath);
            $statusColor = $image['status'] === 'active' ? '#28a745' : '#dc3545';
            $statusText = $image['status'] === 'active' ? 'Active' : 'Inactive';
            
            echo "<tr>";
            echo "<td style='padding: 8px; text-align: center;'>";
            if ($imageExists) {
                echo "<img src='" . htmlspecialchars($imagePath) . "' alt='Hero Image' style='width: 100px; height: 60px; object-fit: cover; border-radius: 4px;'>";
            } else {
                echo "<span style='color: #dc3545;'>❌ File not found</span>";
            }
            echo "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($image['image_path']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($image['title'] ?? 'No title') . "</td>";
            echo "<td style='padding: 8px; color: $statusColor; font-weight: bold;'>$statusText</td>";
            echo "<td style='padding: 8px;'>";
            
            // Toggle status button
            $newStatus = $image['status'] === 'active' ? 'inactive' : 'active';
            $buttonText = $image['status'] === 'active' ? 'Deactivate' : 'Activate';
            $buttonColor = $image['status'] === 'active' ? '#dc3545' : '#28a745';
            
            echo "<form method='POST' style='display: inline; margin-right: 5px;'>";
            echo "<input type='hidden' name='action' value='toggle_image'>";
            echo "<input type='hidden' name='image_id' value='" . $image['id'] . "'>";
            echo "<input type='hidden' name='new_status' value='$newStatus'>";
            echo "<button type='submit' style='background: $buttonColor; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer;'>$buttonText</button>";
            echo "</form>";
            
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Action buttons
    echo "<h3>Quick Actions:</h3>";
    echo "<div style='margin: 1rem 0;'>";
    
    // Deactivate all images
    echo "<form method='POST' style='display: inline; margin-right: 10px;'>";
    echo "<input type='hidden' name='action' value='deactivate_all'>";
    echo "<button type='submit' style='background: #ffc107; color: #212529; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;' onclick='return confirm(\"This will hide all hero images and show only the text slide. Continue?\");'>🚫 Hide All Images</button>";
    echo "</form>";
    
    // Activate all images
    echo "<form method='POST' style='display: inline; margin-right: 10px;'>";
    echo "<input type='hidden' name='action' value='activate_all'>";
    echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>✅ Show All Images</button>";
    echo "</form>";
    
    // Delete all images
    echo "<form method='POST' style='display: inline; margin-right: 10px;'>";
    echo "<input type='hidden' name='action' value='delete_all'>";
    echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;' onclick='return confirm(\"This will permanently delete all hero images and files. This cannot be undone. Continue?\");'>🗑️ Delete All Images</button>";
    echo "</form>";
    
    echo "</div>";
    
    // Preview section
    echo "<h3>Preview:</h3>";
    echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1rem 0;'>";
    echo "<p><strong>Current hero slider will show:</strong></p>";
    echo "<ol>";
    echo "<li>✅ <strong>Text slide:</strong> 'Welcome to Alpha Nutrition' (always visible)</li>";
    
    $activeImages = array_filter($heroImages, function($img) { return $img['status'] === 'active'; });
    if (!empty($activeImages)) {
        foreach ($activeImages as $index => $img) {
            echo "<li>✅ <strong>Image slide " . ($index + 2) . ":</strong> " . htmlspecialchars($img['image_path']) . "</li>";
        }
    } else {
        echo "<li style='color: #666;'>ℹ️ No image slides (only text slide will be visible)</li>";
    }
    echo "</ol>";
    echo "</div>";
    
    // Links
    echo "<h3>Manage Images:</h3>";
    echo "<p><a href='admin/banner-images.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>📁 Admin Panel - Banner Images</a>";
    echo "<a href='index.php' target='_blank' style='background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👁️ View Homepage</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
h1, h2, h3 { color: #333; }
table { font-size: 14px; }
button:hover { opacity: 0.9; }
</style>
