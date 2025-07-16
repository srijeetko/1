-- Insert main categories
INSERT INTO sub_category (category_id, name, description, parent_id) VALUES
(UUID(), 'Gainer', 'Mass and weight gain supplements - Available in various sizes', NULL),
(UUID(), 'Pre-Workout', 'Pre-workout supplements and energizers - Available in various sizes', NULL),
(UUID(), 'Tablets', 'Medicine tablets and capsules - Available in different quantities', NULL);

-- Create product details table for supplement information
CREATE TABLE product_details (
    detail_id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) UNIQUE,
    weight_value DECIMAL(10,2),
    weight_unit ENUM('g', 'kg', 'lb', 'oz'),
    servings_per_container INT,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);
