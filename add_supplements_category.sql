-- Add missing Supplements category to make the filter button work
INSERT INTO sub_category (category_id, name, description, parent_id) 
VALUES (
    CONCAT(
        LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 4, '0'), '-',
        LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 4, '0'), '-',
        LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 4, '0'), '-',
        LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 4, '0'), '-',
        LPAD(HEX(FLOOR(RAND() * 0xFFFFFFFF)), 8, '0'),
        LPAD(HEX(FLOOR(RAND() * 0xFFFF)), 4, '0')
    ),
    'Supplements', 
    'General nutritional supplements and health products', 
    NULL
);

-- Check if the category was added successfully
SELECT category_id, name, description FROM sub_category WHERE name = 'Supplements';
