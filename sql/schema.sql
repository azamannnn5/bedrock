-- ============================================================================
-- Bedrock Lapidary: Database Schema
-- Target: MySQL 5.7+ / MariaDB 10.3+ (Hostinger shared hosting default)
-- Run this once in phpMyAdmin (or Hostinger's database manager) before
-- uploading the PHP files. See SETUP_HOSTINGER.md for the full walkthrough.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- Categories: matches the fixed set of shop categories on the site
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  slug  VARCHAR(50)  PRIMARY KEY,
  label VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Products: the full catalog. This replaces the static products-data.js
-- file; the frontend now fetches this from api/products.php instead.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
  id              VARCHAR(120)  PRIMARY KEY,
  name            VARCHAR(255)  NOT NULL,
  vendor          VARCHAR(150)  NOT NULL,
  category        VARCHAR(50)   NOT NULL,
  price           DECIMAL(10,2) NOT NULL,
  sale_price      DECIMAL(10,2) NULL,
  sku             VARCHAR(20)   NOT NULL,
  blurb           TEXT          NOT NULL,
  features        JSON          NOT NULL,
  included        JSON          NOT NULL,
  specs           JSON          NOT NULL,
  warranty        VARCHAR(255)  NULL,
  tag             VARCHAR(20)   NULL,
  stock           ENUM('in','low','out') NOT NULL DEFAULT 'in',
  shipping_text   TEXT          NULL,
  free_shipping   TINYINT(1)    NOT NULL DEFAULT 0,
  featured        TINYINT(1)    NOT NULL DEFAULT 0,
  recommends      JSON          NULL,
  review_count    INT           NOT NULL DEFAULT 0,
  rating          DECIMAL(2,1)  NOT NULL DEFAULT 0,
  has_variants    TINYINT(1)    NOT NULL DEFAULT 0,
  created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category) REFERENCES categories(slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_products_free_shipping ON products(free_shipping);

-- ----------------------------------------------------------------------------
-- Product variants: one row per purchasable option (grit, size, motor, etc.)
-- of a product. A product with has_variants = 0 still gets exactly one row
-- here, so every price/stock/sku lookup goes through this table uniformly.
-- id here is the variant-level id (what actually goes in the cart / order
-- line items); product_id is the parent product shown in the catalog grid.
-- ----------------------------------------------------------------------------
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

CREATE INDEX idx_variants_product ON product_variants(product_id);

-- ----------------------------------------------------------------------------
-- Reviews: real customer review text pulled from the source catalog, one row
-- per review. Replaces the old client-side synthetic review generator in
-- catalog.js (REVIEW_LINES), which reused ~7 canned lines across every
-- product and wasn't tied to anything real.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  product_id    VARCHAR(120)  NOT NULL,
  author        VARCHAR(150)  NOT NULL,
  rating        DECIMAL(2,1)  NOT NULL,
  body          TEXT          NOT NULL,
  source        VARCHAR(50)   NOT NULL DEFAULT 'lapidarymart.com',
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_reviews_product ON reviews(product_id);
CREATE INDEX idx_reviews_status ON reviews(product_id, status);
CREATE INDEX idx_products_featured ON products(featured);

-- ----------------------------------------------------------------------------
-- Promo codes: one system backs both the "10% off first order" popup code
-- and per-product promo banners. `scope` decides which one it is.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS promo_codes (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  code             VARCHAR(50)  NOT NULL UNIQUE,
  discount_pct     DECIMAL(5,2) NOT NULL,
  scope            ENUM('sitewide','product') NOT NULL DEFAULT 'sitewide',
  starts_at        DATETIME NOT NULL,
  ends_at          DATETIME NOT NULL,
  one_per_customer TINYINT(1) NOT NULL DEFAULT 1,
  active           TINYINT(1) NOT NULL DEFAULT 1,
  source           ENUM('popup','manual') NOT NULL DEFAULT 'manual',
  label            VARCHAR(255) NULL COMMENT 'Internal note, e.g. "First-order popup code"',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Which products a 'product'-scoped promo applies to (many-to-many)
CREATE TABLE IF NOT EXISTS promo_code_products (
  promo_id   INT NOT NULL,
  product_id VARCHAR(120) NOT NULL,
  PRIMARY KEY (promo_id, product_id),
  CONSTRAINT fk_pcp_promo   FOREIGN KEY (promo_id)   REFERENCES promo_codes(id) ON DELETE CASCADE,
  CONSTRAINT fk_pcp_product FOREIGN KEY (product_id) REFERENCES products(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Redemption log: this is what makes "once per customer, per time window"
-- actually enforceable instead of just a stated policy.
CREATE TABLE IF NOT EXISTS promo_redemptions (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  promo_id    INT NOT NULL,
  email       VARCHAR(255) NOT NULL,
  order_id    INT NULL,
  redeemed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_redemption_promo FOREIGN KEY (promo_id) REFERENCES promo_codes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_redemptions_lookup ON promo_redemptions(promo_id, email);

-- ----------------------------------------------------------------------------
-- Orders: replaces the mailto-based checkout with real stored orders
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  customer_name    VARCHAR(255) NOT NULL,
  phone            VARCHAR(50)  NOT NULL,
  email            VARCHAR(255) NOT NULL,
  address          VARCHAR(255) NOT NULL,
  city             VARCHAR(100) NOT NULL,
  state            VARCHAR(50)  NOT NULL,
  zip              VARCHAR(20)  NOT NULL,
  payment_method   VARCHAR(50)  NULL,
  notes            TEXT         NULL,
  promo_code       VARCHAR(50)  NULL,
  subtotal         DECIMAL(10,2) NOT NULL,
  discount_amount  DECIMAL(10,2) NOT NULL DEFAULT 0,
  total            DECIMAL(10,2) NOT NULL,
  status           ENUM('new','confirmed','cancelled') NOT NULL DEFAULT 'new',
  created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  order_id      INT NOT NULL,
  product_id    VARCHAR(120) NOT NULL,
  product_name  VARCHAR(255) NOT NULL,
  unit_price    DECIMAL(10,2) NOT NULL,
  quantity      INT NOT NULL,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Popup leads: captured from the "Save 10% on Your First Order" popup
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS popup_leads (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  first_name  VARCHAR(100) NULL,
  email       VARCHAR(255) NOT NULL,
  promo_id    INT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_popup_promo FOREIGN KEY (promo_id) REFERENCES promo_codes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Site settings: sitewide values editable from the admin panel instead of
-- being hardcoded into HTML files. Simple key/value store.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS site_settings (
  setting_key   VARCHAR(100) PRIMARY KEY,
  setting_value TEXT NULL,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO site_settings (setting_key, setting_value) VALUES
('business_name', 'Bedrock Lapidary'),
('contact_email', 'contact@bedrocklapidary.com'),
('order_notification_email', 'contact@bedrocklapidary.com'),
('phone_number', ''),
('business_hours', ''),
('free_shipping_banner_text', 'Free Shipping on Select Products'),
('popup_delay_seconds', '4'),
('popup_frequency', 'once_per_session'),
('social_facebook', ''),
('social_instagram', ''),
('social_youtube', ''),
('about_page_content', ''),
('order_email_footer_note', ''),
('out_of_stock_behavior', 'show_grayed_out'),
('hero_photo_path', 'assets/img/hero/hero-stones.jpg'),
('free_shipping_threshold', '175')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- ----------------------------------------------------------------------------
-- Best seller flag: independent from the New/Sale tag, so a product can be
-- both a Best Seller and on Sale at the same time.
-- ----------------------------------------------------------------------------
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

-- ----------------------------------------------------------------------------
-- Banner messages: the rotating top-bar text. Multiple messages can be
-- active at once and rotate; each has its own display duration.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS banner_messages (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  message         VARCHAR(255) NOT NULL,
  display_seconds INT NOT NULL DEFAULT 5,
  active          TINYINT(1) NOT NULL DEFAULT 1,
  sort_order      INT NOT NULL DEFAULT 0,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO banner_messages (message, display_seconds, active, sort_order) VALUES
('Free Shipping on Orders $175+', 5, 1, 0);

-- ----------------------------------------------------------------------------
-- Payment methods: shown as options at checkout. Admin-managed instead of
-- hardcoded, so a new payment option can be added without a code change.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS payment_methods (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  active      TINYINT(1) NOT NULL DEFAULT 1,
  sort_order  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO payment_methods (name, active, sort_order) VALUES
('Bank', 1, 0), ('Credit Card', 1, 1), ('PayPal', 1, 2), ('Cash App', 1, 3),
('Chime', 1, 4), ('Apple Pay', 1, 5);

-- ----------------------------------------------------------------------------
-- Vendors: the brand-name list used when adding/editing a product, so a new
-- brand can be added without typing it fresh (and risking a typo mismatch).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS vendors (
  id    INT AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO vendors (name)
SELECT DISTINCT vendor FROM products;

-- ----------------------------------------------------------------------------
-- Category content: an editable intro blurb shown at the top of each
-- category page.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS category_content (
  category_slug VARCHAR(50) PRIMARY KEY,
  description   TEXT NULL,
  CONSTRAINT fk_category_content FOREIGN KEY (category_slug) REFERENCES categories(slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Contact messages: submissions from the Contact Us page, saved here even
-- if the notification email fails to send, so nothing gets lost.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(255) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  order_number  VARCHAR(100) NULL,
  message       TEXT NOT NULL,
  is_read       TINYINT(1) NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_contact_messages_read ON contact_messages(is_read);

-- ----------------------------------------------------------------------------
-- Admin users: single admin account for the admin panel
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin account: username "admin", password "Admin@1234"
-- The hash below is a real bcrypt hash (verified to match the password above).
-- CHANGE THIS PASSWORD after first login, see SETUP_HOSTINGER.md.
INSERT INTO admin_users (username, password_hash) VALUES
('admin', '$2b$10$mGR21rbZexrB5S669gG7r.hnNZIk8BVl84lXANh2khMsXMpv1xusS')
ON DUPLICATE KEY UPDATE username = username;

-- ----------------------------------------------------------------------------
-- Seed categories (matches assets/js/catalog.js CATEGORIES list)
-- ----------------------------------------------------------------------------
INSERT INTO categories (slug, label) VALUES
('saws', 'Saws'),
('grinding-polishing', 'Grinding & Polishing'),
('lap-machines', 'Lap Machines'),
('tumblers', 'Tumblers'),
('glass', 'Glass Equipment'),
('tools', 'Tools'),
('accessories', 'Accessories'),
('supplies', 'Supplies'),
('books', 'Books')
ON DUPLICATE KEY UPDATE label = VALUES(label);
