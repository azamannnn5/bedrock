-- ============================================================================
-- Patch v8: Guides / buying-guide blog system
-- Replaces the single static guide.html with an admin-manageable set of
-- posts. Run this once in phpMyAdmin (or Hostinger's database manager)
-- before uploading the updated PHP files.
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

CREATE INDEX idx_guide_posts_published ON guide_posts(published);

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
