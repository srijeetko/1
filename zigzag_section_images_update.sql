-- Add image fields for each zig-zag section to the products table
-- This allows admins to upload specific images for each content section

ALTER TABLE products 
ADD COLUMN short_description_image VARCHAR(255) AFTER short_description,
ADD COLUMN long_description_image VARCHAR(255) AFTER long_description,
ADD COLUMN key_benefits_image VARCHAR(255) AFTER key_benefits,
ADD COLUMN ingredients_image VARCHAR(255) AFTER ingredients;

-- Note: how_to_use_images already exists as a JSON field for multiple images
-- The new fields are for single representative images for each section

-- Update existing products to use primary product image as fallback for all sections
UPDATE products p
SET 
    short_description_image = (
        SELECT image_url 
        FROM product_images pi 
        WHERE pi.product_id = p.product_id 
        AND pi.is_primary = 1 
        LIMIT 1
    ),
    long_description_image = (
        SELECT image_url 
        FROM product_images pi 
        WHERE pi.product_id = p.product_id 
        AND pi.is_primary = 1 
        LIMIT 1
    ),
    key_benefits_image = (
        SELECT image_url 
        FROM product_images pi 
        WHERE pi.product_id = p.product_id 
        AND pi.is_primary = 1 
        LIMIT 1
    ),
    ingredients_image = (
        SELECT image_url 
        FROM product_images pi 
        WHERE pi.product_id = p.product_id 
        AND pi.is_primary = 1 
        LIMIT 1
    )
WHERE p.product_id IS NOT NULL;
