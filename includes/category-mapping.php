<?php
/**
 * Category Mapping System
 * 
 * This file provides functions to map category names to database IDs
 * and handle category-related operations consistently across the application.
 */

// Ensure database connection is available
if (!isset($pdo) || !$pdo) {
    include_once 'db_connection.php';
}

/**
 * Get category ID by name or partial name match
 * 
 * @param string $categoryName The category name to search for
 * @param array $alternativeNames Alternative names to search for
 * @return string|null The category ID if found, null otherwise
 */
function getCategoryId($categoryName, $alternativeNames = []) {
    global $pdo;
    
    if (!$pdo) {
        return null;
    }
    
    try {
        // First try exact match
        $stmt = $pdo->prepare("SELECT category_id FROM sub_category WHERE name = ? LIMIT 1");
        $stmt->execute([$categoryName]);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['category_id'];
        }
        
        // Try partial match with the main category name
        $stmt = $pdo->prepare("SELECT category_id FROM sub_category WHERE name LIKE ? LIMIT 1");
        $stmt->execute(['%' . $categoryName . '%']);
        $result = $stmt->fetch();
        
        if ($result) {
            return $result['category_id'];
        }
        
        // Try alternative names
        foreach ($alternativeNames as $altName) {
            $stmt = $pdo->prepare("SELECT category_id FROM sub_category WHERE name LIKE ? LIMIT 1");
            $stmt->execute(['%' . $altName . '%']);
            $result = $stmt->fetch();
            
            if ($result) {
                return $result['category_id'];
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("Category mapping error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all main categories (categories without parent)
 * 
 * @return array Array of categories with id, name, and description
 */
function getMainCategories() {
    global $pdo;
    
    if (!$pdo) {
        return [];
    }
    
    try {
        $stmt = $pdo->query("SELECT category_id, name, description FROM sub_category WHERE parent_id IS NULL ORDER BY name");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error fetching main categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Category mapping configuration
 * Maps common category names to their database search terms
 */
$categoryMappings = [
    'Gainer' => ['Mass Gainers', 'Mass', 'Bulk', 'Gain'],
    'Protein' => ['Whey Protein', 'Whey', 'Protein Powder'],
    'Pre-Workout' => ['Pre Workout', 'Preworkout', 'Energy', 'Performance'],
    'Supplements' => ['Supplement', 'Nutrition', 'Vitamin', 'Health'],
    'Weight Management' => ['Weight Loss', 'Fat Burner', 'Diet', 'Slim'],
    'Muscle Builder' => ['Creatine', 'Muscle', 'Builder', 'Strength'],
    'Health and Beauty' => ['Beauty', 'Health', 'Wellness', 'Vitamins'],
    'Tablets' => ['Tablet', 'Capsule', 'Pills', 'Medicine'],
    'BCAA' => ['Branch Chain', 'Amino Acids', 'Recovery'],
    'Amino Acids' => ['Amino', 'Recovery', 'Essential']
];

/**
 * Get category ID using the mapping system
 * 
 * @param string $categoryName The category name to find
 * @return string|null The category ID if found
 */
function getMappedCategoryId($categoryName) {
    global $categoryMappings;
    
    // Check if we have a mapping for this category
    if (isset($categoryMappings[$categoryName])) {
        return getCategoryId($categoryName, $categoryMappings[$categoryName]);
    }
    
    // Fallback to direct search
    return getCategoryId($categoryName);
}

/**
 * Initialize default categories if database is empty
 * This function can be called to set up basic categories
 */
function initializeDefaultCategories() {
    global $pdo;
    
    if (!$pdo) {
        return false;
    }
    
    try {
        // Check if categories already exist
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM sub_category");
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            return true; // Categories already exist
        }
        
        // Insert default categories
        $defaultCategories = [
            ['name' => 'Gainer', 'description' => 'Mass and weight gain supplements'],
            ['name' => 'Pre-Workout', 'description' => 'Pre-workout supplements and energizers'],
            ['name' => 'Supplements', 'description' => 'General nutritional supplements'],
            ['name' => 'Tablets', 'description' => 'Medicine tablets and capsules']
        ];
        
        $insertStmt = $pdo->prepare("INSERT INTO sub_category (category_id, name, description, parent_id) VALUES (?, ?, ?, NULL)");
        
        foreach ($defaultCategories as $category) {
            $categoryId = bin2hex(random_bytes(16)); // Generate UUID-like ID
            $insertStmt->execute([$categoryId, $category['name'], $category['description']]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Error initializing default categories: " . $e->getMessage());
        return false;
    }
}
?>
