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
-- ============================================================================
-- Patch v3: covers everything new in this build pass. Safe to run on a
-- database that already has the earlier patches
-- applied. Skips or safely updates anything already present.
-- ============================================================================

SET NAMES utf8mb4;

-- Contact messages (Contact Us form submissions)
CREATE TABLE IF NOT EXISTS contact_messages (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(255) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  order_number  VARCHAR(100) NULL,
  message       TEXT NOT NULL,
  is_read       TINYINT(1) NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_messages' AND INDEX_NAME = 'idx_contact_messages_read'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX idx_contact_messages_read ON contact_messages(is_read)',
  'SELECT "index already exists, skipped"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Free shipping threshold (replaces the old per-product free shipping system)
INSERT INTO site_settings (setting_key, setting_value) VALUES
('free_shipping_threshold', '175')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Real business info now that the domain and mailbox exist
UPDATE site_settings SET setting_value = 'Bedrock Lapidary'
  WHERE setting_key = 'business_name' AND setting_value = '[Your Business Name]';
UPDATE site_settings SET setting_value = 'contact@bedrocklapidary.com'
  WHERE setting_key = 'contact_email' AND setting_value = '[your-email@placeholder.com]';
UPDATE site_settings SET setting_value = 'contact@bedrocklapidary.com'
  WHERE setting_key = 'order_notification_email' AND setting_value = '[orders-email@placeholder.com]';
UPDATE site_settings SET setting_value = ''
  WHERE setting_key = 'phone_number' AND setting_value = '(307) 555-0142';
UPDATE site_settings SET setting_value = ''
  WHERE setting_key = 'business_hours' AND setting_value = 'Monday-Friday, 9am-5pm';

-- Payment methods: replace the old placeholder list with the real one.
-- Only runs if the table still has the original seeded list untouched, so
-- it won't clobber payment methods you've already customized in admin.
SET @payment_methods_untouched = (
  SELECT COUNT(*) FROM payment_methods WHERE name IN ('Bitcoin','Venmo','Revolut')
);
DELETE FROM payment_methods WHERE @payment_methods_untouched = 3;
INSERT INTO payment_methods (name, active, sort_order)
SELECT * FROM (
  SELECT 'Bank' AS name, 1 AS active, 0 AS sort_order
  UNION ALL SELECT 'Credit Card', 1, 1
  UNION ALL SELECT 'PayPal', 1, 2
  UNION ALL SELECT 'Cash App', 1, 3
  UNION ALL SELECT 'Chime', 1, 4
  UNION ALL SELECT 'Apple Pay', 1, 5
) seed
WHERE @payment_methods_untouched = 3;

-- Banner message: update the old default text to match the new shipping policy
UPDATE banner_messages SET message = 'Free Shipping on Orders $175+'
  WHERE message = 'Free Shipping on Select Products';
-- ============================================================================
-- Patch v4: widen products.id (and the two columns that reference it) from
-- VARCHAR(64) to VARCHAR(120).
--
-- Why: the new 1,770-product catalog generates longer id slugs than the old
-- 132-product one (e.g. "covington-4-inch-c-brand-sintered-diamond-carving-
-- wheels-radius-round-200-230" is 77 characters). With the column capped at
-- 64 characters, several different product variants (different grit/size
-- options of the same base product) were getting silently cut off at the
-- same 64-character point and colliding on the same primary key, which is
-- what caused:
--   #1062 - Duplicate entry '...radius-' for key 'PRIMARY'
--
-- Run this ONCE on the live database, then re-run sql/seed_products.sql.
-- Safe to run even if already applied (MODIFY COLUMN to the same size is a
-- no-op).
-- ============================================================================

SET NAMES utf8mb4;

ALTER TABLE products             MODIFY COLUMN id         VARCHAR(120) NOT NULL;
ALTER TABLE promo_code_products  MODIFY COLUMN product_id VARCHAR(120) NOT NULL;
ALTER TABLE order_items          MODIFY COLUMN product_id VARCHAR(120) NOT NULL;
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
-- ============================================================================
-- Patch v8: Guides / buying-guide blog system
-- ============================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS guide_posts (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  slug              VARCHAR(160)  NOT NULL UNIQUE,
  title             VARCHAR(255)  NOT NULL,
  hero_tag          VARCHAR(50)   NOT NULL DEFAULT 'Buying Guide',
  excerpt           TEXT          NOT NULL,
  meta_description  VARCHAR(300)  NULL,
  body              LONGTEXT      NOT NULL,
  category_link     VARCHAR(50)   NULL,
  featured_image    VARCHAR(255)  NULL,
  published         TINYINT(1)    NOT NULL DEFAULT 1,
  sort_order        INT           NOT NULL DEFAULT 0,
  created_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_guide_posts_category FOREIGN KEY (category_link) REFERENCES categories(slug) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @idx_exists = (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'guide_posts' AND INDEX_NAME = 'idx_guide_posts_published'
);
SET @sql = IF(@idx_exists = 0,
  'CREATE INDEX idx_guide_posts_published ON guide_posts(published)',
  'SELECT "idx_guide_posts_published already exists, skipped"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Migrate the one existing static guide (guide.html) into the new table so
-- nothing is lost and its URL (now guide-post.html?slug=...) keeps working.
INSERT INTO guide_posts
  (slug, title, hero_tag, excerpt, meta_description, body, category_link, published, sort_order)
VALUES (
  'choosing-your-first-rotary-tumbler',
  'Choosing Your First Rotary Tumbler',
  'Buying Guide',
  'Barrel size, grit stages, and how long to run each load: the questions that actually decide whether your first batch comes out polished or just scratched.',
  'A buying guide to choosing your first rotary rock tumbler, from Bedrock Lapidary.',
  '<h2 id="size">Start with barrel size, not price</h2>
<p>A single 3 lb barrel is plenty for testing grit stages on a handful of stones, but most people outgrow it within a season. If you''re planning to keep tumbling past the first batch, a twin 6 lb or single 12 lb setup gets you a usable amount of finished stone per run without a huge jump in coolant, grit, or electricity cost.</p>
<p>Twin-barrel machines let you run two different grit stages at once (coarse in one, pre-polish in the other), which is the main reason experienced tumblers tend to prefer them over a single large barrel.</p>

<div class="callout">
<strong>Rule of thumb</strong>
Fill barrels half to two-thirds full of rock. Too empty and the stones fall instead of tumbling against each other; too full and there''s no room for the grinding action that actually shapes them.
</div>

<h2 id="stages">The four grit stages</h2>
<p>Standard rock tumbling runs through four stages, each finer than the last:</p>
<ul>
<li><strong>Coarse grind (60/90):</strong> shapes rough rock and knocks off sharp edges, typically 5-7 days.</li>
<li><strong>Fine grind (150/220):</strong> removes scratches from the coarse stage, 5-7 days.</li>
<li><strong>Pre-polish (500):</strong> smooths the surface further, 5-7 days.</li>
<li><strong>Polish (cerium oxide or aluminum oxide):</strong> brings out the final shine, 4-7 days.</li>
</ul>
<p>Each stage needs a full clean of the barrel and stones before the next grit goes in. Carried-over grit from a coarser stage is the most common reason a batch comes out cloudy instead of glossy.</p>

<h2 id="motor">Motor noise and run time</h2>
<p>Tumblers run continuously for a week or more per stage, so where you can put the machine matters as much as the machine itself. Rubber-barrel rotary tumblers are noticeably quieter than older metal-barrel designs, but even a quiet unit is easier to live with in a garage or basement than next to a bedroom.</p>
<p>Motor housings do get warm during normal operation, which is expected on a machine running 24 hours a day, but the motor should never be hot to the touch or cycle on and off on its own.</p>

<h2 id="media">Filler media and rock mix</h2>
<p>Mixing plastic or ceramic filler media in with your rocks, especially in the earlier stages, cushions the tumbling action and helps prevent chipping, particularly useful when tumbling a mixed batch of different hardness stones together.</p>

<div class="callout">
<strong>Before you buy</strong>
Decide whether you''ll tumble mixed rock (different hardness levels together) or sorted batches. Mixed batches are more forgiving to start with; sorted batches by Mohs hardness give more consistent results once you know what you''re doing.
</div>

<h2 id="next">What to pair with your first tumbler</h2>
<p>A basic starter kit (coarse grit, fine grit, pre-polish, and polish, plus a bag of ceramic filler media) covers a first full batch. Past that, a rock hardness reference and a spare barrel (so one stage can run while you sort the next batch) are the two upgrades most people reach for first.</p>',
  'tumblers',
  1,
  0
)
ON DUPLICATE KEY UPDATE slug = slug;
