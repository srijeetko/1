-- Create table for storing "How to Use" steps
CREATE TABLE IF NOT EXISTS product_usage_steps (
    step_id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) NOT NULL,
    step_number INT NOT NULL,
    step_title VARCHAR(100) NOT NULL,
    step_description TEXT NOT NULL,
    step_image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_step (product_id, step_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample "How to Use" steps for demonstration
-- Note: Replace the UUIDs with actual product IDs from your products table

-- Sample steps for a supplement product (you'll need to replace with actual product IDs)
INSERT INTO product_usage_steps (step_id, product_id, step_number, step_title, step_description, step_image, is_active) VALUES
-- Replace 'SAMPLE-PRODUCT-ID-1' with actual product ID
('STEP-1-UUID-1', 'SAMPLE-PRODUCT-ID-1', 1, 'Mix with Water', 'Add 1 scoop (30g) to 200-250ml of cold water in a shaker bottle or glass.', 'assets/how-to-use/686238c5559f2_B - Complex 1.jpg', 1),
('STEP-2-UUID-1', 'SAMPLE-PRODUCT-ID-1', 2, 'Shake Well', 'Shake vigorously for 30 seconds until the powder is completely dissolved and mixed.', 'assets/how-to-use/686238c555f2c_B - Complex 2.jpg', 1),
('STEP-3-UUID-1', 'SAMPLE-PRODUCT-ID-1', 3, 'Consume Immediately', 'Drink the mixture immediately after preparation for optimal absorption and effectiveness.', 'assets/how-to-use/686238c556100_B - Complex 3.jpg', 1),
('STEP-4-UUID-1', 'SAMPLE-PRODUCT-ID-1', 4, 'Best Time to Take', 'Take 30 minutes before workout or as directed by your healthcare professional.', 'assets/how-to-use/686238c5562b2_B - Complex 4.jpg', 1);

-- Alternative table for storing general usage instructions (if you prefer text-based approach)
CREATE TABLE IF NOT EXISTS product_usage_instructions (
    instruction_id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) NOT NULL,
    instruction_type ENUM('dosage', 'timing', 'preparation', 'precautions', 'storage') NOT NULL,
    instruction_title VARCHAR(100) NOT NULL,
    instruction_content TEXT NOT NULL,
    display_order INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample usage instructions
INSERT INTO product_usage_instructions (instruction_id, product_id, instruction_type, instruction_title, instruction_content, display_order, is_active) VALUES
('INST-1-UUID-1', 'SAMPLE-PRODUCT-ID-1', 'dosage', 'Recommended Dosage', 'Take 1 scoop (30g) daily or as recommended by your healthcare provider.', 1, 1),
('INST-2-UUID-1', 'SAMPLE-PRODUCT-ID-1', 'timing', 'Best Time to Take', 'For best results, consume 30 minutes before your workout session.', 2, 1),
('INST-3-UUID-1', 'SAMPLE-PRODUCT-ID-1', 'preparation', 'How to Prepare', 'Mix with 200-250ml of cold water, shake well, and consume immediately.', 3, 1),
('INST-4-UUID-1', 'SAMPLE-PRODUCT-ID-1', 'precautions', 'Important Notes', 'Do not exceed recommended dosage. Consult healthcare provider if pregnant or nursing.', 4, 1),
('INST-5-UUID-1', 'SAMPLE-PRODUCT-ID-1', 'storage', 'Storage Instructions', 'Store in a cool, dry place away from direct sunlight. Keep container tightly closed.', 5, 1);

-- Create indexes for better performance
CREATE INDEX idx_product_usage_steps_product_id ON product_usage_steps(product_id);
CREATE INDEX idx_product_usage_steps_active ON product_usage_steps(is_active);
CREATE INDEX idx_product_usage_instructions_product_id ON product_usage_instructions(product_id);
CREATE INDEX idx_product_usage_instructions_type ON product_usage_instructions(instruction_type);
