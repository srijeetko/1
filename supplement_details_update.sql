-- Update supplement_details table to include all necessary fields
-- Run this if you already have the basic supplement_details table

-- Add new columns to supplement_details table
ALTER TABLE supplement_details 
ADD COLUMN serving_size VARCHAR(50) AFTER product_id,
ADD COLUMN calories INT AFTER servings_per_container,
ADD COLUMN protein DECIMAL(10,2) AFTER calories,
ADD COLUMN carbs DECIMAL(10,2) AFTER protein,
ADD COLUMN fats DECIMAL(10,2) AFTER carbs,
ADD COLUMN fiber DECIMAL(10,2) AFTER fats,
ADD COLUMN sodium DECIMAL(10,2) AFTER fiber,
ADD COLUMN ingredients TEXT AFTER sodium,
ADD COLUMN directions TEXT AFTER ingredients,
ADD COLUMN warnings TEXT AFTER directions,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER weight_unit,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Add foreign key constraint with cascade delete if not exists
ALTER TABLE supplement_details 
DROP FOREIGN KEY IF EXISTS supplement_details_ibfk_1;

ALTER TABLE supplement_details 
ADD CONSTRAINT supplement_details_ibfk_1 
FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE;
