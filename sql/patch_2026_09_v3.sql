-- ============================================================================
-- Patch v3: covers everything new in this build pass. Safe to run on a
-- database that already has patch_2026_09.sql and patch_2026_09_v2.sql
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
