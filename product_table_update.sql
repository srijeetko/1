-- Add new columns to the products table
ALTER TABLE products 
ADD COLUMN short_description TEXT AFTER description,
ADD COLUMN long_description TEXT AFTER short_description,
ADD COLUMN key_benefits TEXT AFTER long_description,
ADD COLUMN how_to_use TEXT AFTER key_benefits,
ADD COLUMN how_to_use_images TEXT AFTER how_to_use,
ADD COLUMN ingredients TEXT AFTER how_to_use_images;
