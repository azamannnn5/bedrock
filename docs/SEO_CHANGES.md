# Bedrock Lapidary: SEO round 2 (Screaming Frog, ChatGPT re-audit and keyword research)

## Deploy (in this order)
1. Upload the whole zip over the site (new: `includes/` changes, `assets/img/hero/*.webp` already live).
2. phpMyAdmin: run `sql/seo/guides_seo_batch_01.sql`. It now holds **17 guides** and is safe to re-run.
   *(Your Screaming Frog crawl found only 1 guide, so the first 10 were never imported. This step is required.)*
3. Open `/sitemap.xml`; it should list ~450 URLs including 30 type pages and 18 guides.
4. Check these three redirects return 301: `category.html?cat=tumblers&type=replacement-barrels`,
   `.../cat=supplies&type=grit-tumbling-media`, and `/index.html`.
5. Search Console > Sitemaps > submit `https://bedrocklapidary.com/sitemap.xml`.
6. URL Inspection > "Test live URL" on a product page, a type page and the homepage; confirm
   the H1, price and description appear in the rendered HTML.
7. Re-crawl with Screaming Frog after deploy and compare against this report.

## What the Screaming Frog crawl showed, and what was done
| Finding (crawl) | Fix |
|---|---|
| 6 image 404s (products with no photo, my WebP paths) | Products without a photo now render an inline placeholder; no dead URL is emitted |
| 77 product titles over 60 chars | Titles keep the brand suffix only when it fits in 60 characters |
| 60 meta descriptions over 155 | All capped at 155 (verified across all 417 products) |
| 6 duplicate meta descriptions | Every description now leads with the product name (0 duplicates across 417) |
| Type pages 44-130 words | 100-360 words: factual summary (brands, price range), "how to choose" notes, related guides |
| H2 missing / 229 duplicate H2s | Type pages have real H2s; product "Recommended with the <name>" is now unique |
| `/index.html` duplicate of `/` | 301 redirect; all internal Home links now point to `/` |
| No security headers on 99% of URLs | X-Content-Type-Options, X-Frame-Options, Referrer-Policy, HSTS added |
| 272 unsafe `target=_blank` links | `rel="noopener noreferrer"` added |
| `pumps-plumbing` had only 2 internal links | Listed on its category page; all type links audited |
| URL parameters (263) | Not changed; see below |

## Keyword research applied
- New pages: **Cabbing Machines**, **Rock Tumbler Grit & Polish**, **Tumbling Media**, **Tumbler Parts**,
  **Polishing Compounds**, **Gemini Ring Saw Parts**, **Flanges/Pulleys/Spindles**, **Diamond Burs**, **Core Drill Bits**.
- Renamed with 301s: grinders-polishers and combination-units -> cabbing-machines;
  replacement-barrels -> tumbler-parts; grit-tumbling-media -> silicon-carbide-grit.
- Combination units folded into Cabbing Machines to avoid two thin pages competing.
- H1s/titles reworded to the searched phrase ("Lapidary Diamond Saw Blades", "Rock Tumbler Grit & Polish").
- Homepage title is now "Lapidary Equipment & Supplies | Bedrock Lapidary"; H1 unchanged.
- **Thin-page control**: type pages with fewer than 3 products are `noindex, follow` and left out of the sitemap
  (Slant Cabbers, Glass Cutters, Glass Polishers).
- 7 new guides for the informational keywords: how to choose a lapidary saw, tile saw vs lapidary saw,
  how to use a rock tumbler, rotary vs vibratory tumblers, flat lap vs cabbing machine, what is a cabbing
  machine, how to polish rocks. Product pages and type pages now link to the relevant guides.
- `KEYWORD_MAP.md`: one primary page per keyword theme, with blank volume columns to fill from Keyword Planner.

## Deliberately not changed
- **Clean URLs**: all three assessments say not to rebuild them now, and I agree. The crawl shows the current URLs
  index fine, and rewriting every link and redirect without a staging server risks breaking a live site.
  Revisit after you see indexing data in Search Console.
- **Review/rating markup**: none. The reviews system (tables, API, admin page) was removed entirely, so no review or rating data is stored, shown or marked up.
- **Local SEO pages**: only if Bedrock has a real local presence.
- **Vibratory tumbler page**: no such inventory.

## Still to do (cannot be done from code)
- ~~Rewrite product descriptions~~ Done (2026-09-29): all product descriptions, features, included items and warranty text are original wording, in `sql/seed_products.sql` and `sql/content_updates_combined.sql`. The old vendor name was replaced with Bedrock Lapidary.
- Validate keyword volumes in Keyword Planner and fill in `KEYWORD_MAP.md`.
- Backlinks (none assessed by any of the audits): supplier/manufacturer listings, rockhounding clubs and forums, a Google Business Profile if eligible.
- Page speed: median server response in the crawl was ~1 s. Ask Hostinger about caching (LiteSpeed cache) and check PageSpeed Insights.
- Rotate the DB/SMTP passwords in `api/config.php` (they were in the zip you shared) and keep that file out of any public repo.
