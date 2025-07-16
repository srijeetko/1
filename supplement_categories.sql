-- Supplement Categories Table
CREATE TABLE supplement_categories (
    category_id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert the 8 types of supplements
INSERT INTO supplement_categories (category_id, name, description) VALUES
(UUID(), 'Mass Gainers', 'Supplements designed for muscle mass gain'),
(UUID(), 'Whey Protein', 'Pure protein supplements for muscle recovery'),
(UUID(), 'Pre-Workout', 'Energy and focus boosting supplements'),
(UUID(), 'BCAA', 'Branch Chain Amino Acids for muscle recovery'),
(UUID(), 'Creatine', 'Strength and performance enhancement supplements'),
(UUID(), 'Weight Loss', 'Fat burning and weight management supplements'),
(UUID(), 'Amino Acids', 'Essential amino acids for muscle growth'),
(UUID(), 'Protein Bars', 'Protein-rich snack bars');

-- Supplement Details Table
CREATE TABLE supplement_details (
    detail_id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) UNIQUE,
    serving_size VARCHAR(50),
    servings_per_container INT,
    calories INT,
    protein DECIMAL(10,2),
    carbs DECIMAL(10,2),
    fats DECIMAL(10,2),
    fiber DECIMAL(10,2),
    sodium DECIMAL(10,2),
    ingredients TEXT,
    directions TEXT,
    warnings TEXT,
    weight_value DECIMAL(10,2),
    weight_unit ENUM('g', 'kg', 'lb', 'oz'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Add indexes for better performance
CREATE INDEX idx_supplement_category ON supplement_categories(name);
CREATE INDEX idx_supplement_weight ON supplement_details(weight_value, weight_unit);
