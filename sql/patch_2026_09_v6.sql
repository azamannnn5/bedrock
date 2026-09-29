-- ============================================================================
-- Patch v6: real reviews table. Reviews were previously synthesized on the
-- fly in catalog.js from a pool of ~7 canned lines, not tied to any real
-- product. This adds a table to hold real review text pulled from the
-- source catalog (lapidarymart.com), one row per review.
--
-- Run this ONCE on the live database. Product-by-product review rows are
-- added separately as they're pulled (see sql/seed_reviews_batch_*.sql).
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  product_id    VARCHAR(120)  NOT NULL,
  author        VARCHAR(150)  NOT NULL,
  rating        DECIMAL(2,1)  NOT NULL,
  body          TEXT          NOT NULL,
  source        VARCHAR(50)   NOT NULL DEFAULT 'lapidarymart.com',
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND INDEX_NAME = 'idx_reviews_product'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX idx_reviews_product ON reviews(product_id)',
  'SELECT "idx_reviews_product already exists, skipped"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
