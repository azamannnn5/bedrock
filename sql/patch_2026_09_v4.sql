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
