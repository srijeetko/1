-- Fix the specific product price that's causing the checkout error
-- Run this SQL script in phpMyAdmin or MySQL command line

-- Check current price of the problematic product
SELECT product_id, name, price, 
       CASE 
           WHEN price IS NULL THEN 'NULL'
           WHEN price = 0 THEN 'ZERO'
           WHEN price = '' THEN 'EMPTY'
           WHEN price < 0 THEN 'NEGATIVE'
           ELSE 'VALID'
       END as price_status
FROM products 
WHERE name LIKE '%Hardcore%Mass%Gainer%' OR product_id LIKE '%Hardcore-Mass-Gainer%';

-- Fix the price for Hardcore Mass Gainer
UPDATE products 
SET price = 1299.00 
WHERE name LIKE '%Hardcore%Mass%Gainer%' OR product_id LIKE '%Hardcore-Mass-Gainer%';

-- Verify the fix
SELECT product_id, name, price 
FROM products 
WHERE name LIKE '%Hardcore%Mass%Gainer%' OR product_id LIKE '%Hardcore-Mass-Gainer%';

-- Fix any other products with invalid prices
UPDATE products SET price = 1599.00 WHERE (price IS NULL OR price = 0 OR price = '' OR price < 0) AND (name LIKE '%protein%' OR name LIKE '%whey%');
UPDATE products SET price = 1299.00 WHERE (price IS NULL OR price = 0 OR price = '' OR price < 0) AND (name LIKE '%mass%' OR name LIKE '%gainer%');
UPDATE products SET price = 899.00 WHERE (price IS NULL OR price = 0 OR price = '' OR price < 0) AND name LIKE '%creatine%';
UPDATE products SET price = 699.00 WHERE (price IS NULL OR price = 0 OR price = '' OR price < 0) AND (name LIKE '%vitamin%' OR name LIKE '%supplement%');
UPDATE products SET price = 999.00 WHERE price IS NULL OR price = 0 OR price = '' OR price < 0;

-- Show all products with their updated prices
SELECT product_id, name, price FROM products ORDER BY name;

-- Success message
SELECT 'Product prices have been fixed!' as message;
