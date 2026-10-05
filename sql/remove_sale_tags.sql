-- Run this once on the LIVE database (phpMyAdmin > SQL).
-- Removes the "Sale" tag from every product. Nothing else is touched.
UPDATE products SET tag = NULL WHERE tag = 'Sale';
