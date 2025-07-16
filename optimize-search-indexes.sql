-- Search Performance Optimization Indexes
-- Run these SQL commands to optimize search performance

-- ============================================
-- PRODUCTS TABLE INDEXES
-- ============================================

-- Index for product name searches (most common)
CREATE INDEX idx_products_name ON products(name);

-- Index for active products filter
CREATE INDEX idx_products_active ON products(is_active);

-- Index for category searches
CREATE INDEX idx_products_category ON products(category_id);

-- Composite index for active products by category
CREATE INDEX idx_products_active_category ON products(is_active, category_id);

-- Index for product creation date (for newest sorting)
CREATE INDEX idx_products_created ON products(created_at);

-- Full-text search indexes for better text search performance
-- Note: These require MySQL 5.6+ and InnoDB engine
ALTER TABLE products ADD FULLTEXT(name);
ALTER TABLE products ADD FULLTEXT(short_description);
ALTER TABLE products ADD FULLTEXT(long_description);
ALTER TABLE products ADD FULLTEXT(key_benefits);
ALTER TABLE products ADD FULLTEXT(ingredients);
ALTER TABLE products ADD FULLTEXT(how_to_use);

-- Composite full-text index for multi-field searches
ALTER TABLE products ADD FULLTEXT(name, short_description, long_description, key_benefits, ingredients);

-- ============================================
-- SUB_CATEGORY TABLE INDEXES
-- ============================================

-- Index for category name searches
CREATE INDEX idx_subcategory_name ON sub_category(name);

-- ============================================
-- PRODUCT_VARIANTS TABLE INDEXES
-- ============================================

-- Index for variant searches by product
CREATE INDEX idx_variants_product ON product_variants(product_id);

-- Index for size searches
CREATE INDEX idx_variants_size ON product_variants(size);

-- Index for color searches
CREATE INDEX idx_variants_color ON product_variants(color);

-- Index for stock queries
CREATE INDEX idx_variants_stock ON product_variants(stock);

-- Composite index for product variants with stock
CREATE INDEX idx_variants_product_stock ON product_variants(product_id, stock);

-- ============================================
-- PRODUCT_IMAGES TABLE INDEXES
-- ============================================

-- Index for primary image lookups
CREATE INDEX idx_images_primary ON product_images(product_id, is_primary);

-- Index for product image searches
CREATE INDEX idx_images_product ON product_images(product_id);

-- ============================================
-- BEST_SELLERS TABLE INDEXES
-- ============================================

-- Index for best seller lookups
CREATE INDEX idx_bestsellers_product ON best_sellers(product_id);

-- Index for sales count sorting
CREATE INDEX idx_bestsellers_sales ON best_sellers(sales_count);

-- ============================================
-- PERFORMANCE OPTIMIZATION QUERIES
-- ============================================

-- Analyze table statistics for query optimization
ANALYZE TABLE products;
ANALYZE TABLE sub_category;
ANALYZE TABLE product_variants;
ANALYZE TABLE product_images;
ANALYZE TABLE best_sellers;

-- ============================================
-- ALTERNATIVE FULL-TEXT SEARCH IMPLEMENTATION
-- ============================================

-- If you want to use MySQL's MATCH() AGAINST() for better search performance,
-- you can replace the LIKE queries with these optimized versions:

/*
Example optimized search query using FULLTEXT:

SELECT p.*, 
       sc.name as category_name,
       MATCH(p.name, p.short_description, p.long_description, p.key_benefits, p.ingredients) 
       AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score
FROM products p
LEFT JOIN sub_category sc ON p.category_id = sc.category_id
WHERE p.is_active = 1 
  AND MATCH(p.name, p.short_description, p.long_description, p.key_benefits, p.ingredients) 
      AGAINST(? IN NATURAL LANGUAGE MODE)
ORDER BY relevance_score DESC, p.name ASC;

For boolean mode searches (with +, -, *, etc.):
MATCH(...) AGAINST(? IN BOOLEAN MODE)

For phrase searches:
MATCH(...) AGAINST('"exact phrase"' IN BOOLEAN MODE)
*/

-- ============================================
-- SEARCH ANALYTICS TABLE (OPTIONAL)
-- ============================================

-- Create table to track search analytics for further optimization
CREATE TABLE search_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_query VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    search_time_ms DECIMAL(10,2) DEFAULT 0,
    user_ip VARCHAR(45),
    user_agent TEXT,
    page_type ENUM('frontend', 'admin') DEFAULT 'frontend',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_search_query (search_query),
    INDEX idx_search_date (created_at),
    INDEX idx_search_type (page_type)
);

-- ============================================
-- SEARCH CACHE TABLE (OPTIONAL)
-- ============================================

-- Create table for caching popular search results
CREATE TABLE search_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_query VARCHAR(255) NOT NULL,
    search_hash VARCHAR(64) NOT NULL,
    results_json LONGTEXT,
    results_count INT DEFAULT 0,
    cache_expires TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_search_hash (search_hash),
    INDEX idx_cache_query (search_query),
    INDEX idx_cache_expires (cache_expires)
);

-- ============================================
-- MAINTENANCE QUERIES
-- ============================================

-- Query to check index usage
-- Run this periodically to see which indexes are being used
/*
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    CARDINALITY,
    SUB_PART,
    PACKED,
    NULLABLE,
    INDEX_TYPE
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN ('products', 'sub_category', 'product_variants', 'product_images', 'best_sellers')
ORDER BY TABLE_NAME, INDEX_NAME;
*/

-- Query to check table sizes and optimization needs
/*
SELECT 
    TABLE_NAME,
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size (MB)',
    TABLE_ROWS,
    ROUND((INDEX_LENGTH / 1024 / 1024), 2) AS 'Index Size (MB)'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN ('products', 'sub_category', 'product_variants', 'product_images', 'best_sellers')
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;
*/

-- ============================================
-- NOTES AND RECOMMENDATIONS
-- ============================================

/*
PERFORMANCE TIPS:

1. FULLTEXT vs LIKE:
   - FULLTEXT is much faster for text searches but requires specific syntax
   - LIKE with wildcards can be slow on large datasets
   - Consider hybrid approach: FULLTEXT for main search, LIKE for exact matches

2. Index Maintenance:
   - Monitor index usage with EXPLAIN queries
   - Remove unused indexes to improve INSERT/UPDATE performance
   - Rebuild indexes periodically: OPTIMIZE TABLE products;

3. Query Optimization:
   - Use LIMIT to restrict result sets
   - Avoid SELECT * when possible
   - Use prepared statements to prevent SQL injection and improve performance

4. Caching Strategy:
   - Cache popular search results in Redis/Memcached
   - Implement search result pagination
   - Cache category and product counts

5. Search Analytics:
   - Track search queries to identify optimization opportunities
   - Monitor slow queries with MySQL slow query log
   - Use search analytics to improve relevance scoring

6. Hardware Considerations:
   - Ensure adequate RAM for index caching
   - Use SSD storage for better I/O performance
   - Consider read replicas for high-traffic sites

7. Alternative Solutions:
   - For very large datasets (>1M products), consider Elasticsearch
   - Implement search microservice for better scalability
   - Use CDN for static search suggestions
*/
