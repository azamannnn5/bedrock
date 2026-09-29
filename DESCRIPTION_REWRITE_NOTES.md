# Product description rewrite (2026-09-29)

**Done:** all 416 product `blurb` descriptions (the Description tab and the source of each
product's meta description) were rewritten from each product's own facts. The old copied text
is gone from `sql/content_updates/batch_*.sql` and `sql/content_updates_combined.sql`.
Each new blurb opens with the product name, so `bl_product_meta_desc()` still produces unique metas.

**Deploy:** `sql/seed_products.sql` now contains the rewritten blurb, features, included, specs and warranty
for all 417 products, so it is authoritative on its own. `sql/content_updates_combined.sql` (416 statements
for the earlier rewrite plus 1 for `covington-bonded-diamond-drill-bits`, which was missing) applies the same
text and is safe to re-run in any order. Old copied text no longer exists anywhere in `sql/`.
Running `seed_products.sql` clears `product_variants`, so re-run `seed_product_variants.sql` after it.

**Not changed (still worth a look):**
1. `features`, `included`, `specs`, `warranty` are still the short factual lists from the original scrape.
   Facts are not copyrightable, but the wording is identical to the source. Ask if you want these reworded too.
2. 29 products in `seed_products.sql` have the vendor/brand set to "Lapidary Mart" (mostly books).
   The vendor shows on the product page and in the Product JSON-LD `brand`. Suggest replacing with the
   real publisher/manufacturer.
4. Product photos in `assets/img/products/` may have come from the same source. Check their origin.
5. Books: descriptions are deliberately short and built only from title, author and the listed facts,
   so nothing about a book's contents is invented.
