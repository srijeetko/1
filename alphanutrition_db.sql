-- MySQL-Compatible Schema for Real-Life eCommerce Application
-- Tested for MySQL 8.0+

-- Admins Table
CREATE TABLE admin_users (
    admin_id CHAR(36) PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role VARCHAR(50) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users Table
CREATE TABLE users (
    user_id CHAR(36) PRIMARY KEY,
    full_name VARCHAR(100),
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    phone_number VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1
);

-- Addresses Table
CREATE TABLE addresses (
    address_id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    type ENUM('shipping', 'billing'),
    line1 TEXT NOT NULL,
    line2 TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100),
    is_default TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Sub Categories
CREATE TABLE sub_category (
    category_id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    parent_id CHAR(36),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES sub_category(category_id) ON DELETE SET NULL
);

-- Products Table
CREATE TABLE products (
    product_id CHAR(36) PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category_id CHAR(36),
    stock_quantity INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES sub_category(category_id)
);

-- Product Images
CREATE TABLE product_images (
    image_id CHAR(36) PRIMARY KEY,
    product_id CHAR(36),
    image_url TEXT NOT NULL,
    alt_text TEXT,
    is_primary TINYINT(1) DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Product Variants
CREATE TABLE product_variants (
    variant_id CHAR(36) PRIMARY KEY,
    product_id CHAR(36),
    size VARCHAR(20),
    color VARCHAR(30),
    price_modifier DECIMAL(10,2) DEFAULT 0.0,
    stock INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Tablets Table
CREATE TABLE tablets (
    tablet_id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) UNIQUE,
    os VARCHAR(50),
    ram VARCHAR(50),
    storage VARCHAR(50),
    battery VARCHAR(50),
    screen_size VARCHAR(50),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Cart Items
CREATE TABLE cart_items (
    cart_item_id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    product_id CHAR(36),
    variant_id CHAR(36),
    quantity INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
);

-- Coupons
CREATE TABLE coupons (
    coupon_id CHAR(36) PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed'),
    discount_value DECIMAL(10,2),
    usage_limit INT,
    expires_at TIMESTAMP,
    is_active TINYINT(1) DEFAULT 1
);

-- Shipping Methods
CREATE TABLE shipping_methods (
    method_id CHAR(36) PRIMARY KEY,
    name VARCHAR(100),
    description TEXT,
    base_price DECIMAL(10,2),
    estimated_days INT
);

-- Checkout Orders
CREATE TABLE checkout_orders (
    order_id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    address_id CHAR(36),
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    coupon_id CHAR(36),
    shipping_method_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (address_id) REFERENCES addresses(address_id),
    FOREIGN KEY (coupon_id) REFERENCES coupons(coupon_id),
    FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(method_id)
);

-- Order Items
CREATE TABLE order_items (
    order_item_id CHAR(36) PRIMARY KEY,
    order_id CHAR(36),
    product_id CHAR(36),
    variant_id CHAR(36),
    quantity INT,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES checkout_orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
);

-- Payment Gateway Logs
CREATE TABLE payment_gateway_logs (
    payment_id CHAR(36) PRIMARY KEY,
    order_id CHAR(36),
    payment_status VARCHAR(50),
    transaction_id TEXT,
    gateway_name VARCHAR(100),
    paid_amount DECIMAL(10,2),
    paid_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES checkout_orders(order_id)
);

-- Contact Messages
CREATE TABLE contact_messages (
    message_id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    name VARCHAR(100),
    email VARCHAR(100),
    subject VARCHAR(255),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Quotation Requests
CREATE TABLE quotation_requests (
    quote_id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    product_id CHAR(36),
    quantity INT,
    message TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Best Sellers
CREATE TABLE best_sellers (
    product_id CHAR(36) PRIMARY KEY,
    sales_count INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Featured Collections
CREATE TABLE featured_collections (
    collection_id CHAR(36) PRIMARY KEY,
    title VARCHAR(100),
    description TEXT
);

-- Collection Products
CREATE TABLE collection_products (
    collection_id CHAR(36),
    product_id CHAR(36),
    PRIMARY KEY (collection_id, product_id),
    FOREIGN KEY (collection_id) REFERENCES featured_collections(collection_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Blogs
CREATE TABLE blogs (
    blog_id CHAR(36) PRIMARY KEY,
    author_id CHAR(36),
    title VARCHAR(200),
    content TEXT,
    image_url TEXT,
    published_at TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES admin_users(admin_id)
);

-- Returned Products
CREATE TABLE returned_products (
    return_id CHAR(36) PRIMARY KEY,
    order_item_id CHAR(36),
    user_id CHAR(36),
    reason TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Wishlists
CREATE TABLE wishlists (
    wishlist_id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    product_id CHAR(36),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Reviews
CREATE TABLE reviews (
    review_id CHAR(36) PRIMARY KEY,
    user_id CHAR(36),
    product_id CHAR(36),
    rating INT CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(100),
    content TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- Audit Logs
CREATE TABLE audit_logs (
    log_id CHAR(36) PRIMARY KEY,
    actor_type VARCHAR(50),
    actor_id CHAR(36),
    action VARCHAR(100),
    target_table VARCHAR(100),
    target_id CHAR(36),
    metadata JSON,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
