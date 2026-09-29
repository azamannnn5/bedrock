-- ============================================================================
-- Patch v7: review moderation status + best sellers + drops the fake
-- homepage newsletter signup.
--
-- 1. Adds a `status` column to `reviews` so a customer-submitted review can
--    sit as 'pending' until approved in admin, instead of appearing (or
--    counting toward the product's rating) immediately. Existing rows
--    default to 'approved' so the 450 real reviews already pulled from
--    lapidarymart.com keep showing exactly as they do now.
-- 2. Seeds the initial "Best Sellers" homepage row with a spread of 10
--    products across categories (adjustable anytime from
--    admin/best-sellers.php).
--
-- Safe to run even if already applied.
-- ============================================================================

SET NAMES utf8mb4;

SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND COLUMN_NAME = 'status'
);
SET @sql = IF(@col_exists = 0,
  "ALTER TABLE reviews ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER source",
  'SELECT "status column already exists, skipped"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'reviews' AND INDEX_NAME = 'idx_reviews_status'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX idx_reviews_status ON reviews(product_id, status)',
  'SELECT "idx_reviews_status already exists, skipped"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Seed initial Best Sellers (spread across categories, all real products
-- with real reviews already). Adjust anytime from admin/best-sellers.php.
UPDATE products SET best_seller = 0;
UPDATE products SET best_seller = 1 WHERE id IN (
  'hi-tech-diamond-6-inch-trim-saw',
  'hi-tech-diamond-all-u-need-flat-lap-machine-glass-crystal-model',
  'lortone-33-b-rock-tumbler',
  'tumble-bee-4-lb-rock-tumbler-extra-barrel',
  'covington-large-sphere-machine',
  'hi-tech-diamond-electroplated-diamond-lap-discs',
  'lightning-lap-dlite-diamond-laps',
  'hi-tech-diamond-dopstation-wax-melting-pot',
  'covington-gem-marking-templates',
  'covington-black-sintered-diamond-saw-blades'
);
