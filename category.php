<?php
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/includes/seo.php';
$SITE_URL = SITE_URL;
$db = get_db();
$catSlug = isset($_GET['cat']) ? (string)$_GET['cat'] : '';
$typeKey = isset($_GET['type']) ? (string)$_GET['type'] : '';
$searchQ = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$categoryRow = null;
if ($catSlug !== '') {
    $stmt = $db->prepare("SELECT slug, label FROM categories WHERE slug = ?");
    $stmt->execute([$catSlug]);
    $categoryRow = $stmt->fetch();
}

// Renamed type keys: permanent redirect to the new URL.
if ($catSlug !== '' && $typeKey !== '' && ($newKey = bl_type_legacy($catSlug, $typeKey))) {
    header('Location: ' . $SITE_URL . '/category/' . rawurlencode($catSlug) . '/' . rawurlencode($newKey), true, 301);
    exit;
}

$mode = 'notfound';   // category | type | search | notfound
$items = [];
$typeDef = null;
$typeIds = null;
$siblingTypes = [];
$guides = [];
$collectionSchema = null;
$breadcrumbSchema = null;
$robotsMeta = '';
$h1 = 'Category';
$intro = '';
$about = '';
$tips = '';
$typeGuideSlugs = [];
$summary = '';

if ($searchQ !== '' ) {
    $mode = 'search';
    $pageTitle = 'Search results | Bedrock Lapidary';
    $pageDesc = 'Search results at Bedrock Lapidary.';
    $canonical = $SITE_URL . '/category';
    $robotsMeta = '<meta name="robots" content="noindex, follow">';
    $h1 = 'Search results';
} elseif ($categoryRow) {
    $catLabel = $categoryRow['label'];
    $seo = bl_category_seo($catSlug, $catLabel);
    $hideOut = get_setting('out_of_stock_behavior', 'show_grayed_out') === 'hide_completely';
    $types = bl_types($catSlug);

    if ($typeKey !== '') {
        if (isset($types[$typeKey])) {
            $items = bl_type_products($db, $catSlug, $typeKey);
            if ($hideOut) { $items = array_values(array_filter($items, function ($p) { return $p['stock'] !== 'out'; })); }
        }
        if ($items) {
            $mode = 'type';
            $typeDef = $types[$typeKey];
            $h1 = $typeDef[1];
            $pageTitle = bl_e(bl_title($typeDef[9] !== '' ? $typeDef[9] : $typeDef[1]));
            $summary = bl_collection_summary($items);
            $pageDescRaw = $typeDef[4] . (mb_strlen($typeDef[4]) < 115 ? ' Browse ' . $summary : '');
            $intro = $typeDef[5];
            $tips = $typeDef[7];
            $typeGuideSlugs = $typeDef[8];
            if (!bl_type_indexable(count($items))) { $robotsMeta = '<meta name="robots" content="noindex, follow">'; }
            $canonical = $SITE_URL . '/category/' . rawurlencode($catSlug) . '/' . rawurlencode($typeKey);
            $typeIds = array_map(function ($p) { return $p['id']; }, $items);
            foreach ($types as $k => $d) { if ($k !== $typeKey) { $siblingTypes[$k] = $d; } }
        }
    } else {
        $sql = "SELECT * FROM products WHERE category = ?" . ($hideOut ? " AND stock != 'out'" : "") . " ORDER BY name";
        $ps = $db->prepare($sql);
        $ps->execute([$catSlug]);
        $items = $ps->fetchAll();
        $mode = 'category';
        $h1 = $seo['h1'];
        $pageTitle = bl_e(bl_title($seo['title']));
        $summary = bl_collection_summary($items);
        $pageDescRaw = $seo['desc'];
        $canonical = $SITE_URL . '/category/' . rawurlencode($catSlug);
        $intro = $seo['intro'];
        try {
            $cs = $db->prepare("SELECT description FROM category_content WHERE category_slug = ?");
            $cs->execute([$catSlug]);
            $row = $cs->fetch();
            if ($row && trim($row['description']) !== '') { $intro = trim($row['description']); }
        } catch (Exception $e) { /* table optional */ }
        foreach ($types as $k => $d) { $siblingTypes[$k] = $d; }
    }

    if ($mode === 'category' || $mode === 'type') {
        $pageDesc = bl_e(bl_clean_text($pageDescRaw, 155));
        try {
            if ($typeGuideSlugs) {
                $ph = implode(',', array_fill(0, count($typeGuideSlugs), '?'));
                $gs = $db->prepare("SELECT slug, title FROM guide_posts WHERE published = 1 AND slug IN ($ph)");
                $gs->execute($typeGuideSlugs);
                $bySlug = [];
                foreach ($gs->fetchAll() as $g) { $bySlug[$g['slug']] = $g; }
                foreach ($typeGuideSlugs as $sl) { if (isset($bySlug[$sl])) { $guides[] = $bySlug[$sl]; } }
            }
            if (count($guides) < 2) {
                $gs = $db->prepare("SELECT slug, title FROM guide_posts WHERE published = 1 AND category_link = ? ORDER BY sort_order, id LIMIT 5");
                $gs->execute([$catSlug]);
                $have = array_column($guides, 'slug');
                foreach ($gs->fetchAll() as $g) { if (!in_array($g['slug'], $have, true)) { $guides[] = $g; } }
            }
        } catch (Exception $e) { /* guides optional */ }

        $listEls = [];
        foreach (array_slice($items, 0, 60) as $i => $p) {
            $listEls[] = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $SITE_URL . '/product/' . rawurlencode($p['id']), 'name' => $p['name']];
        }
        $collectionSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $h1,
            'description' => bl_clean_text($pageDescRaw),
            'url' => $canonical,
            'mainEntity' => ['@type' => 'ItemList', 'numberOfItems' => count($items), 'itemListElement' => $listEls],
        ];
        $crumbs = [['Home', $SITE_URL . '/'], [$catLabel, $SITE_URL . '/category/' . rawurlencode($catSlug)]];
        if ($mode === 'type') { $crumbs[] = [$typeDef[0], $canonical]; }
        $bl = [];
        foreach ($crumbs as $i => $c) { $bl[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c[0], 'item' => $c[1]]; }
        $breadcrumbSchema = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $bl];
    }
}

if ($mode === 'notfound') {
    bl_404();
    $pageTitle = 'Category Not Found | Bedrock Lapidary';
    $pageDesc = 'This category could not be found. Shop lapidary saws, grinders, tumblers and supplies at Bedrock Lapidary.';
    $canonical = $SITE_URL . '/category';
    $robotsMeta = '<meta name="robots" content="noindex, follow">';
}
$ogImage = $SITE_URL . '/assets/img/hero/hero-stones.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<meta name="description" content="<?= $pageDesc ?>">
<?= $robotsMeta ?>
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Bedrock Lapidary">
<meta property="og:title" content="<?= $pageTitle ?>">
<meta property="og:description" content="<?= $pageDesc ?>">
<meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $pageTitle ?>">
<meta name="twitter:description" content="<?= $pageDesc ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
<?php if ($collectionSchema): ?>
<script type="application/ld+json"><?= json_encode($collectionSchema, JSON_UNESCAPED_SLASHES) ?></script>
<script type="application/ld+json"><?= json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<script type="application/ld+json"><?= bl_org_schema_json($SITE_URL) ?></script>
<link rel="stylesheet" href="/assets/css/style.css?v=1790797840">
<?= bl_img_fallback_script() ?></head>
<body>

<div class="utility-bar">
  <div class="container">
    <span class="ship-msg">Free Shipping on Select Products</span>
  </div>
</div>

<header class="site-header">
  <div class="container">
    <button class="hamburger-toggle" id="hamburger-toggle" aria-label="Menu" type="button">
      <span></span><span></span><span></span>
    </button>
    <a href="/" class="logo-wrap">
      <span class="logo"><span class="mark">Bedrock</span>&nbsp;Lapidary</span>
    </a>
    <nav class="main-nav" aria-label="Primary">
      <div class="mobile-nav-header">
        <button class="mobile-nav-close" id="mobile-nav-close" aria-label="Close menu" type="button">&times;</button>
        <span class="logo"><span class="mark">Bedrock</span>&nbsp;Lapidary</span>
      </div>
      <ul>
        <li class="has-drop">
          <a href="/category/grinding-polishing">Grinding &amp; Polishing</a>
          <ul class="drop">
            <li><a href="/category/grinding-polishing/cabbing-machines">Cabbing Machines</a></li>
            <li><a href="/category/grinding-polishing/arbors">Arbors</a></li>
            <li><a href="/category/grinding-polishing/polishing-compounds">Polishing Compounds</a></li>
            <li><a href="/category/grinding-polishing/grinding-wheels">Grinding Wheels</a></li>
            <li><a href="/category/grinding-polishing/polishing-wheels">Polishing Wheels</a></li>
            <li><a href="/category/grinding-polishing/sanders">Sanders</a></li>
          </ul>
        </li>
        <li class="has-drop">
          <a href="/category/glass">Glass</a>
          <ul class="drop">
            <li><a href="/category/glass">Bevelers</a></li>
            <li><a href="/category/glass/glass-cutters">Cutters</a></li>
            <li><a href="/category/glass/glass-grinders">Grinders</a></li>
            <li><a href="/category/glass/glass-lathes">Lathes</a></li>
            <li><a href="/category/glass/glass-polishers">Polishers</a></li>
          </ul>
        </li>
        <li class="has-drop">
          <a href="/category/lap-machines">Lap Machines</a>
          <ul class="drop">
            <li><a href="/category/lap-machines/flat-lap-machines">Flat Lap Machines</a></li>
            <li><a href="/category/lap-machines/vibrating-lap-machines">Vibrating Lap Machines</a></li>
            <li><a href="/category/lap-machines/lap-disks">Lap Disks</a></li>
            <li><a href="/category/lap-machines/slant-cabbers">Slant Cabbers</a></li>
          </ul>
        </li>
        <li class="has-drop">
          <a href="/category/saws">Saws</a>
          <ul class="drop">
            <li><a href="/category/saws/slab-saws">Slab Saws</a></li>
            <li><a href="/category/saws/trim-saws">Trim Saws</a></li>
            <li><a href="/category/saws/band-saws">Band Saws</a></li>
            <li><a href="/category/saws/ring-saws">Ring Saws</a></li>
            <li><a href="/category/saws/saw-blades">Saw Blades</a></li>
          </ul>
        </li>
        <li class="has-drop">
          <a href="/category/tumblers">Tumblers</a>
          <ul class="drop">
            <li><a href="/category/tumblers/rotary-tumblers">Rotary Tumblers</a></li>
            <li><a href="/category/tumblers/tumbling-grit-polish">Grit &amp; Polish Kits</a></li>
            <li><a href="/category/tumblers/tumbling-media">Tumbling Media</a></li>
            <li><a href="/category/tumblers/tumbler-parts">Tumbler Parts</a></li>
            <li><a href="/category/accessories/tumbler-motors">Replacement Motors</a></li>
          </ul>
        </li>
        <li class="has-drop">
          <a href="/category/supplies">Supplies</a>
          <ul class="drop">
            <li><a href="/category/supplies/silicon-carbide-grit">Silicon Carbide Grit</a></li>
            <li><a href="/category/supplies/diamond-compounds">Diamond Compounds</a></li>
            <li><a href="/category/supplies/dop-wax">Dop Wax &amp; Sticks</a></li>
            <li><a href="/category/supplies/sanding-belts-discs">Sanding Belts &amp; Discs</a></li>
          </ul>
        </li>
        <li class="has-drop">
          <a href="/category/tools">Tools</a>
          <ul class="drop">
            <li><a href="/category/tools">All Tools</a></li>
            <li><a href="/category/accessories">Accessories</a></li>
          </ul>
        </li>
        <li><a href="/category/books">Books</a></li>
        <li><a href="/guides">Guides</a></li>
      </ul>
    </nav>
        <div class="header-actions">
      <button aria-label="Search" id="search-toggle" type="button">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.5" y2="16.5"/></svg>
        <span class="label">Search</span>
      </button>
      <form class="search-flyout" id="search-flyout">
        <input type="text" placeholder="Search products..." aria-label="Search products" autocomplete="off">
        <button type="submit" id="search-form-submit">Go</button>
        <div class="search-live-results" id="search-live-results"></div>
      </form>
      <button aria-label="Cart" id="cart-button" type="button" style="position:relative;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 6h2l2.2 11.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.6L21 8H7"/><circle cx="10" cy="21" r="1"/><circle cx="17" cy="21" r="1"/></svg>
        <span class="label">Cart</span><span class="cart-count" id="cart-count-badge" style="display:none;"></span>
      </button>
    </div>
  </div>
</header>
<div class="nav-overlay" id="nav-overlay"></div>

<?php if ($mode === 'notfound'): ?>
<div class="container" style="padding:60px 0;">
  <h1>Category not found</h1>
  <p style="max-width:60ch;">We couldn't find that category. Try one of these:</p>
  <ul>
    <li><a href="/category/saws">Lapidary saws</a></li>
    <li><a href="/category/grinding-polishing">Grinding &amp; polishing</a></li>
    <li><a href="/category/lap-machines">Flat lap machines</a></li>
    <li><a href="/category/tumblers">Rock tumblers</a></li>
    <li><a href="/category/supplies">Supplies</a></li>
    <li><a href="/">Back to the homepage</a></li>
  </ul>
</div>
<?php else: ?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <div class="container">
    <a href="/">Home</a><span class="sep">/</span>
<?php if ($mode === 'type'): ?>
    <a href="/category/<?= bl_e(urlencode($catSlug)) ?>"><?= bl_e($catLabel) ?></a><span class="sep">/</span>
    <span id="cat-breadcrumb"><?= bl_e($typeDef[0]) ?></span>
<?php elseif ($mode === 'category'): ?>
    <span id="cat-breadcrumb"><?= bl_e($catLabel) ?></span>
<?php else: ?>
    <span id="cat-breadcrumb">Search results</span>
<?php endif; ?>
  </div>
</nav>

<div class="container">
  <div class="listing-shell">

    <div class="page-title-row">
      <h1 id="cat-title"><?= bl_e($h1) ?></h1>
      <p id="cat-intro-text" data-ssr="<?= $intro !== '' ? '1' : '0' ?>" style="<?= $intro !== '' ? '' : 'display:none; ' ?>color:var(--ink-soft); max-width:70ch;"><?= bl_e($intro) ?></p>
      <p id="cat-count"><?= $mode === 'search' ? '' : count($items) . ' product' . (count($items) === 1 ? '' : 's') ?></p>
<?php if ($summary !== ''): ?>
      <p class="cat-summary"><?= bl_e($summary) ?></p>
<?php endif; ?>
<?php if ($siblingTypes): ?>
      <h2 class="subcat-title"><?= $mode === 'type' ? 'More ' . bl_e(strtolower($catLabel)) : 'Shop ' . bl_e(strtolower($catLabel)) . ' by type' ?></h2>
      <nav class="subcat-links" aria-label="<?= $mode === 'type' ? 'Related types' : 'Shop by type' ?>">
<?php if ($mode === 'type'): ?>
        <a href="/category/<?= bl_e(urlencode($catSlug)) ?>">All <?= bl_e($catLabel) ?></a>
<?php endif; ?>
<?php foreach ($siblingTypes as $k => $d): ?>
        <a href="/category/<?= bl_e(urlencode($catSlug)) ?>&amp;type=<?= bl_e(urlencode($k)) ?>"><?= bl_e($d[0]) ?></a>
<?php endforeach; ?>
      </nav>
<?php endif; ?>
    </div>

    <aside class="filters">
      <div class="f-group">
        <h4>Brand</h4>
        <div id="cat-filter-vendors"></div>
      </div>
      <div class="f-group" style="border-bottom:none;">
        <h4>Availability</h4>
        <label><input type="checkbox" checked disabled> In stock</label>
      </div>
    </aside>

    <div>
      <div class="listing-toolbar">
        <span></span>
        <select id="cat-sort" aria-label="Sort by">
          <option value="featured">Sort: Featured</option>
          <option value="price-asc">Price: Low to High</option>
          <option value="price-desc">Price: High to Low</option>
        </select>
      </div>

      <div class="product-grid" id="cat-grid"><?php foreach ($items as $i => $p) { echo bl_card($p, $i < 4); } ?></div>

<?php if ($tips !== ''): ?>
      <section class="cat-notes">
        <h2>How to choose <?= bl_e(strtolower($typeDef[0])) ?></h2>
<?php foreach (preg_split('/\n\s*\n/', $tips) as $para): ?>
        <p><?= bl_e($para) ?></p>
<?php endforeach; ?>
      </section>
<?php endif; ?>

<?php if ($guides): ?>
      <section class="cat-guides">
        <h2>Related buying guides</h2>
        <ul>
<?php foreach ($guides as $g): ?>
          <li><a href="/guides/<?= bl_e(urlencode($g['slug'])) ?>"><?= bl_e($g['title']) ?></a></li>
<?php endforeach; ?>
        </ul>
      </section>
<?php endif; ?>
    </div>

  </div>
</div>
<?php if ($typeIds !== null): ?>
<script>window.BL_TYPE_IDS = <?= json_encode($typeIds) ?>;</script>
<?php endif; ?>
<?php endif; ?>

<footer class="site-footer" style="margin-top:0;">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="foot-logo"><span class="mark">Bedrock</span> Lapidary</div>
        <p style="color:rgba(255,255,255,.7); font-size:18.2px; max-width:34ch;">Equipment and supplies for cutting, grinding, and polishing stone and glass, chosen by people who use it.</p><p style="color:rgba(255,255,255,.7); font-size:15px; margin-top:8px;">Bellaire, MI</p>
      </div>
      <div>
        <h4>Shop</h4>
        <ul>
          <li><a href="/category/saws">Saws</a></li>
          <li><a href="/category/grinding-polishing">Grinding &amp; Polishing</a></li>
          <li><a href="/category/lap-machines">Lap Machines</a></li>
          <li><a href="/category/tumblers">Tumblers</a></li>
          <li><a href="/category/glass">Glass Equipment</a></li>
          <li><a href="/category/supplies">Supplies</a></li>
        </ul>
      </div>
      <div>
        <h4>Support</h4>
        <ul>
          <li><a href="/about">About Us</a></li>
          <li><a href="/contact">Contact Us</a></li>
          <li><a href="/returns">Shipping &amp; Returns</a></li>
          <li><a href="/privacy">Privacy Policy</a></li>
          <li><a href="/terms">Terms of Service</a></li>
          <li><a href="/guides">Buying Guides</a></li>
        </ul>
      </div>
      <div>
        <h4>Follow Us</h4>
        <div id="footer-social-icons" style="display:flex; gap:14px; margin-top:4px;"></div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 Bedrock Lapidary. All rights reserved.</span>
    </div>
  </div>
</footer>

<script src="/assets/js/catalog.js?v=1790797840"></script>
<script src="/assets/js/cart.js?v=1790797840"></script>
<script src="/assets/js/main.js?v=1790797840"></script>
<?php if ($mode !== 'notfound'): ?><script>loadCatalog().then(renderCategoryPage);</script><?php endif; ?>
<!-- Smartsupp Live Chat script -->
<script type="text/javascript">
var _smartsupp = _smartsupp || {};
_smartsupp.key = 'cb58c34eb2f63b4ce30fe36cb6c27974cd722f67';
window.smartsupp||(function(d) {
  var s,c,o=smartsupp=function(){ o._.push(arguments)};o._=[];
  s=d.getElementsByTagName('script')[0];c=d.createElement('script');
  c.type='text/javascript';c.charset='utf-8';c.async=true;
  c.src='https://www.smartsuppchat.com/loader.js?';s.parentNode.insertBefore(c,s);
})(document);
</script>
<noscript>Powered by <a href="https://www.smartsupp.com" target="_blank" rel="noopener noreferrer">Smartsupp</a></noscript>

</body>
</html>
