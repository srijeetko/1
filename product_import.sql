-- First, let's create a category for supplements
INSERT INTO sub_category (category_id, name, description) 
VALUES (UUID(), 'Supplements', 'Nutritional and workout supplements');

-- Store the category_id in a variable for use in product insertions
SET @supplement_category = (SELECT category_id FROM sub_category WHERE name = 'Supplements' LIMIT 1);

-- Insert products
INSERT INTO products (product_id, name, description, price, category_id, stock_quantity, is_active) VALUES
(UUID(), 'Black Powder Pre-Workout', 'High-performance pre-workout supplement for enhanced energy and focus', 49.99, @supplement_category, 100, 1),
(UUID(), 'Cratein 100g', 'Pure creatine supplement for muscle strength and power', 19.99, @supplement_category, 150, 1),
(UUID(), 'G-One Gainer 1Kg', 'Premium mass gainer for muscle growth - 1kg', 39.99, @supplement_category, 100, 1),
(UUID(), 'G-One Gainer 3Kg', 'Premium mass gainer for muscle growth - 3kg', 89.99, @supplement_category, 75, 1),
(UUID(), 'Hardcore Mass Gainer 1Kg', 'Advanced formula mass gainer - 1kg', 44.99, @supplement_category, 100, 1),
(UUID(), 'Hardcore Mass Gainer 3Kg', 'Advanced formula mass gainer - 3kg', 99.99, @supplement_category, 50, 1),
(UUID(), 'Intense Pre-workout', 'Powerful pre-workout formula for maximum performance', 54.99, @supplement_category, 100, 1),
(UUID(), 'Lean Mass Gainer 3Kg', 'Lean muscle mass gainer with low fat content - 3kg', 94.99, @supplement_category, 80, 1),
(UUID(), 'Massive Gain 1Kg', 'Ultimate mass gainer for rapid muscle growth - 1kg', 49.99, @supplement_category, 100, 1),
(UUID(), 'Massive Gain 3Kg', 'Ultimate mass gainer for rapid muscle growth - 3kg', 109.99, @supplement_category, 60, 1),
(UUID(), 'Real Bulk Gain 1Kg', 'Professional bulk gainer formula - 1kg', 45.99, @supplement_category, 100, 1),
(UUID(), 'Real Bulk Gain 3Kg', 'Professional bulk gainer formula - 3kg', 99.99, @supplement_category, 70, 1),
(UUID(), 'Shootup Pre-Workout', 'Advanced pre-workout formula for explosive energy', 52.99, @supplement_category, 100, 1),
(UUID(), 'Whey Gold 1kg', 'Premium whey protein isolate - 1kg', 59.99, @supplement_category, 120, 1);

-- Insert product images
INSERT INTO product_images (image_id, product_id, image_url, is_primary)
SELECT 
    UUID(),
    p.product_id,
    CASE p.name
        WHEN 'Black Powder Pre-Workout' THEN 'assets/Black-Powder.jpg'
        WHEN 'Cratein 100g' THEN 'assets/Cratein-100g.jpg'
        WHEN 'G-One Gainer 1Kg' THEN 'assets/G-One-Gainer-1-Kg.jpg'
        WHEN 'G-One Gainer 3Kg' THEN 'assets/G-One-Gainer-3-Kg.jpg'
        WHEN 'Hardcore Mass Gainer 1Kg' THEN 'assets/Hardcore-Mass-Gainer-1-Kg.jpg'
        WHEN 'Hardcore Mass Gainer 3Kg' THEN 'assets/Hardcore-Mass-Gainer-3-Kg.jpg'
        WHEN 'Intense Pre-workout' THEN 'assets/Intense-Pre-workout.jpg'
        WHEN 'Lean Mass Gainer 3Kg' THEN 'assets/Lean-Mass-Gainer.jpg-3-kg.jpg'
        WHEN 'Massive Gain 1Kg' THEN 'assets/Massive-Gain--1Kg.jpg'
        WHEN 'Massive Gain 3Kg' THEN 'assets/Massive-Gain--3Kg.jpg'
        WHEN 'Real Bulk Gain 1Kg' THEN 'assets/Real-Bulk-Gain-1-Kg.jpg'
        WHEN 'Real Bulk Gain 3Kg' THEN 'assets/Real-Bulk-Gain-3-Kg.jpg'
        WHEN 'Shootup Pre-Workout' THEN 'assets/Shootup-pre-Workout.jpg'
        WHEN 'Whey Gold 1kg' THEN 'assets/Whey-gold-1kg.jpg'
    END,
    1
FROM products p
WHERE p.category_id = @supplement_category;
