<?php
require_once __DIR__ . '/api/config.php';
$SITE_URL = SITE_URL;
$db = get_db();

$posts = $db->query("SELECT * FROM guide_posts WHERE published = 1 ORDER BY sort_order, created_at DESC")->fetchAll();

$pageTitle = 'Buying Guides | Bedrock Lapidary';
$pageDesc = 'Buying guides for lapidary and glass equipment: how to choose a tumbler, saw, grinder, or lap machine, from Bedrock Lapidary.';
$canonical = $SITE_URL . '/guides';
$ogImage = $SITE_URL . '/assets/img/hero/hero-stones.jpg';

$itemListSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'itemListElement' => array_map(function ($p, $i) use ($SITE_URL) {
        return [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'url' => $SITE_URL . '/guides/' . rawurlencode($p['slug']),
            'name' => $p['title'],
        ];
    }, $posts, array_keys($posts)),
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="Bedrock Lapidary">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($pageDesc) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
<?php if ($posts): ?>
<script type="application/ld+json"><?= json_encode($itemListSchema, JSON_UNESCAPED_SLASHES) ?></script>
<?php endif; ?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "<?= htmlspecialchars($SITE_URL) ?>/#organization",
  "name": "Bedrock Lapidary",
  "url": "<?= htmlspecialchars($SITE_URL) ?>/",
  "logo": "<?= htmlspecialchars($SITE_URL) ?>/assets/img/icons/android-chrome-512x512.png",
  "email": "contact@bedrocklapidary.com",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Bellaire",
    "addressRegion": "MI",
    "addressCountry": "US"
  }
}
</script>
<link rel="stylesheet" href="/assets/css/style.css?v=1790797840">
</head>
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

<nav class="breadcrumb" aria-label="Breadcrumb">
  <div class="container">
    <a href="/">Home</a><span class="sep">/</span>
    <span>Guides</span>
  </div>
</nav>

<div class="container">
  <div class="page-title-row" style="padding: 32px 0 8px;">
    <h1>Buying Guides</h1>
    <p>Straight answers to the questions that decide whether your first purchase is the right one, written by people who use this equipment.</p>
  </div>

  <?php if (!$posts): ?>
    <p style="padding: 40px 0; color: var(--ink-soft);">No guides published yet. Check back soon.</p>
  <?php else: ?>
  <div class="guide-grid" style="padding: 24px 0 64px;">
    <?php foreach ($posts as $p): ?>
      <?php $href = '/guides/' . rawurlencode($p['slug']); ?>
      <a class="guide-card" href="<?= htmlspecialchars($href) ?>">
        <div class="thumb">
          <img src="<?= htmlspecialchars('/' . ltrim($p['featured_image'] ?: 'assets/img/hero/hero-stones.jpg', '/')) ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy">
        </div>
        <div class="body">
          <div class="guide-hero-tag"><?= htmlspecialchars($p['hero_tag']) ?></div>
          <h3><?= htmlspecialchars($p['title']) ?></h3>
          <p><?= htmlspecialchars($p['excerpt']) ?></p>
          <span class="btn-plain">Read guide &rarr;</span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

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
