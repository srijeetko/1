<?php
include 'includes/db_connection.php';

echo "<h1>Check Image Paths</h1>";

try {
    // Get banner images
    $stmt = $pdo->query("SELECT banner_id, image_path, title, is_active FROM banner_images ORDER BY display_order");
    $banners = $stmt->fetchAll();
    
    echo "<h2>Current Banner Images in Database</h2>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Banner ID</th><th>Image Path</th><th>Title</th><th>Status</th><th>File Exists?</th><th>Preview</th></tr>";
    
    foreach ($banners as $banner) {
        $imagePath = $banner['image_path'];
        $fullPath = 'assets/' . $imagePath;
        $fileExists = file_exists($fullPath);
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($banner['banner_id']) . "</td>";
        echo "<td>" . htmlspecialchars($imagePath) . "</td>";
        echo "<td>" . htmlspecialchars($banner['title']) . "</td>";
        echo "<td>" . ($banner['is_active'] ? 'Active' : 'Inactive') . "</td>";
        echo "<td style='color: " . ($fileExists ? 'green' : 'red') . ";'>" . ($fileExists ? 'YES' : 'NO') . "</td>";
        echo "<td>";
        if ($fileExists) {
            echo "<img src='$fullPath' style='max-width: 100px; max-height: 60px;'>";
        } else {
            echo "File not found";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check assets directory
    echo "<h2>Assets Directory Contents</h2>";
    $assetsDir = 'assets/';
    if (is_dir($assetsDir)) {
        $files = scandir($assetsDir);
        $imageFiles = array_filter($files, function($file) {
            return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
        });
        
        if (!empty($imageFiles)) {
            echo "<p>Found " . count($imageFiles) . " image files:</p>";
            echo "<ul>";
            foreach ($imageFiles as $file) {
                echo "<li>$file</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No image files found in assets directory</p>";
        }
    } else {
        echo "<p style='color: red;'>Assets directory does not exist!</p>";
    }
    
    // Test image URLs
    echo "<h2>Test Image URLs</h2>";
    if (!empty($banners)) {
        $testImage = $banners[0]['image_path'];
        $testPaths = [
            "assets/$testImage",
            "../assets/$testImage",
            "admin/assets/$testImage",
            $testImage
        ];
        
        echo "<p>Testing different paths for: <strong>$testImage</strong></p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Path</th><th>File Exists?</th><th>Preview</th></tr>";
        
        foreach ($testPaths as $path) {
            $exists = file_exists($path);
            echo "<tr>";
            echo "<td>$path</td>";
            echo "<td style='color: " . ($exists ? 'green' : 'red') . ";'>" . ($exists ? 'YES' : 'NO') . "</td>";
            echo "<td>";
            if ($exists) {
                echo "<img src='$path' style='max-width: 100px; max-height: 60px;'>";
            } else {
                echo "Not found";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<style>
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
th { background: #f5f5f5; }
</style>
