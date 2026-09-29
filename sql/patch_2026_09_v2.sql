-- ============================================================================
-- Patch v2: adds everything from this build pass to a database that already
-- has the previous schema (products, orders, promos, site_settings, etc.
-- already exist). Safe to run once; skips anything already present.
-- ============================================================================

SET NAMES utf8mb4;

-- best_seller column on products, independent from the New/Sale tag
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'best_seller'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE products ADD COLUMN best_seller TINYINT(1) NOT NULL DEFAULT 0, ADD INDEX idx_products_best_seller (best_seller)',
  'SELECT "best_seller column already exists, skipped"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Banner messages (rotating top bar)
CREATE TABLE IF NOT EXISTS banner_messages (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  message         VARCHAR(255) NOT NULL,
  display_seconds INT NOT NULL DEFAULT 5,
  active          TINYINT(1) NOT NULL DEFAULT 1,
  sort_order      INT NOT NULL DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO banner_messages (message, display_seconds, active, sort_order)
SELECT 'Free Shipping on Select Products', 5, 1, 0
WHERE NOT EXISTS (SELECT 1 FROM banner_messages);

-- Payment methods
CREATE TABLE IF NOT EXISTS payment_methods (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  active      TINYINT(1) NOT NULL DEFAULT 1,
  sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO payment_methods (name, active, sort_order)
SELECT * FROM (
  SELECT 'Bitcoin' AS name, 1 AS active, 0 AS sort_order
  UNION ALL SELECT 'Apple Pay', 1, 1
  UNION ALL SELECT 'Cash App', 1, 2
  UNION ALL SELECT 'PayPal', 1, 3
  UNION ALL SELECT 'Chime', 1, 4
  UNION ALL SELECT 'Venmo', 1, 5
  UNION ALL SELECT 'Revolut', 1, 6
) seed
WHERE NOT EXISTS (SELECT 1 FROM payment_methods);

-- Vendors (seeded from whatever vendors already exist on your products)
CREATE TABLE IF NOT EXISTS vendors (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO vendors (name)
SELECT DISTINCT vendor FROM products;

-- Category content (intro blurbs)
CREATE TABLE IF NOT EXISTS category_content (
  category_slug VARCHAR(50) PRIMARY KEY,
  description   TEXT NULL,
  CONSTRAINT fk_category_content FOREIGN KEY (category_slug) REFERENCES categories(slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- New site_settings keys
INSERT INTO site_settings (setting_key, setting_value) VALUES
('about_page_content', ''),
('order_email_footer_note', ''),
('out_of_stock_behavior', 'show_grayed_out'),
('hero_photo_path', 'assets/img/hero/hero-stones.jpg')
ON DUPLICATE KEY UPDATE setting_key = setting_key;
