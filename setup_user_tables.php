<?php
// Create user management tables
require_once 'includes/db_connection.php';

try {
    echo "<h2>Setting Up User Management Tables</h2>";
    
    // Drop existing tables if they exist to avoid conflicts
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS user_sessions");
    $pdo->exec("DROP TABLE IF EXISTS user_preferences");
    $pdo->exec("DROP TABLE IF EXISTS user_addresses");
    $pdo->exec("DROP TABLE IF EXISTS order_items");
    $pdo->exec("DROP TABLE IF EXISTS orders");
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Users table
    $usersTable = "
        CREATE TABLE users (
            user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(20),
            password_hash VARCHAR(255) NOT NULL,
            date_of_birth DATE,
            gender ENUM('male', 'female', 'other'),
            profile_image VARCHAR(255),
            email_verified BOOLEAN DEFAULT FALSE,
            email_verification_token VARCHAR(100),
            password_reset_token VARCHAR(100),
            password_reset_expires DATETIME,
            is_active BOOLEAN DEFAULT TRUE,
            last_login DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ";
    
    $pdo->exec($usersTable);
    echo "<p style='color: green;'>✅ Users table created successfully</p>";
    
    // User addresses table
    $addressesTable = "
        CREATE TABLE user_addresses (
            address_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            address_type ENUM('home', 'work', 'other') DEFAULT 'home',
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            company VARCHAR(100),
            address_line_1 VARCHAR(255) NOT NULL,
            address_line_2 VARCHAR(255),
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NOT NULL,
            postal_code VARCHAR(20) NOT NULL,
            country VARCHAR(100) DEFAULT 'India',
            phone VARCHAR(20),
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ";
    
    $pdo->exec($addressesTable);
    echo "<p style='color: green;'>✅ User addresses table created successfully</p>";
    
    // User sessions table for better session management
    $sessionsTable = "
        CREATE TABLE user_sessions (
            session_id VARCHAR(128) PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ";
    
    $pdo->exec($sessionsTable);
    echo "<p style='color: green;'>✅ User sessions table created successfully</p>";
    
    // User preferences table
    $preferencesTable = "
        CREATE TABLE user_preferences (
            preference_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            newsletter_subscription BOOLEAN DEFAULT TRUE,
            sms_notifications BOOLEAN DEFAULT FALSE,
            order_updates BOOLEAN DEFAULT TRUE,
            promotional_emails BOOLEAN DEFAULT TRUE,
            preferred_language VARCHAR(10) DEFAULT 'en',
            preferred_currency VARCHAR(10) DEFAULT 'INR',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ";
    
    $pdo->exec($preferencesTable);
    echo "<p style='color: green;'>✅ User preferences table created successfully</p>";
    
    // Orders table (if not exists)
    $ordersTable = "
        CREATE TABLE IF NOT EXISTS orders (
            order_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            guest_email VARCHAR(255),
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            address_line_1 VARCHAR(255) NOT NULL,
            address_line_2 VARCHAR(255),
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NOT NULL,
            postal_code VARCHAR(20) NOT NULL,
            country VARCHAR(100) DEFAULT 'India',
            subtotal DECIMAL(10,2) NOT NULL,
            shipping_cost DECIMAL(10,2) DEFAULT 0.00,
            tax_amount DECIMAL(10,2) DEFAULT 0.00,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_method ENUM('cod', 'online', 'wallet') NOT NULL,
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
        )
    ";
    
    $pdo->exec($ordersTable);
    echo "<p style='color: green;'>✅ Orders table created successfully</p>";
    
    // Order items table (if not exists)
    $orderItemsTable = "
        CREATE TABLE IF NOT EXISTS order_items (
            order_item_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            variant_id INT,
            product_name VARCHAR(255) NOT NULL,
            variant_name VARCHAR(100),
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
            FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE SET NULL
        )
    ";
    
    $pdo->exec($orderItemsTable);
    echo "<p style='color: green;'>✅ Order items table created successfully</p>";
    
    echo "<h3>✅ All User Management Tables Created Successfully!</h3>";
    echo "<p><a href='register.php' style='color: blue;'>Test Registration Page</a></p>";
    echo "<p><a href='login.php' style='color: blue;'>Test Login Page</a></p>";
    echo "<p><a href='admin/user-management.php' style='color: blue;'>Admin User Management</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
