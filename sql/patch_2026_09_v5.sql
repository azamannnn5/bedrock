-- ============================================================================
-- Patch v5: variant merge. The 1,770-row product catalog had one row per
-- variant (grit, size, motor, etc.), all sharing the same name, so the
-- category grid showed the same product repeated 2-65 times. This patch
-- adds a product_variants table so a product has ONE row in `products` and
-- N rows in `product_variants` for its purchasable options.
--
-- Run this ONCE on the live database, then re-run sql/seed_products.sql
-- (now 417 rows instead of 1770) and sql/seed_product_variants.sql (new,
-- 1770 rows). Safe to run even if already applied.
-- ============================================================================

SET NAMES utf8mb4;

-- has_variants flag on products
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'has_variants'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE products ADD COLUMN has_variants TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT "has_variants column already exists, skipped"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- product_variants table
CREATE TABLE IF NOT EXISTS product_variants (
  id            VARCHAR(160)  PRIMARY KEY,
  product_id    VARCHAR(120)  NOT NULL,
  sku           VARCHAR(20)   NOT NULL,
  price         DECIMAL(10,2) NOT NULL,
  sale_price    DECIMAL(10,2) NULL,
  stock         ENUM('in','low','out') NOT NULL DEFAULT 'in',
  option_label  VARCHAR(255)  NULL,
  sort_order    INT           NOT NULL DEFAULT 0,
  CONSTRAINT fk_variants_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND INDEX_NAME = 'idx_variants_product'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX idx_variants_product ON product_variants(product_id)',
  'SELECT "idx_variants_product already exists, skipped"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
