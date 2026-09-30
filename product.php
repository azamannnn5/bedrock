<?php
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/includes/seo.php';
$SITE_URL = SITE_URL;
$productId = isset($_GET['id']) ? (string)$_GET['id'] : '';
$product = null;
$db = get_db();
if ($productId !== '') {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
}

$productSchema = null;
$breadcrumbSchema = null;
$related = [];
if ($product) {
    $catStmt = $db->prepare("SELECT slug, label FROM categories WHERE slug = ?");
    $catStmt->execute([$product['category']]);
    $categoryRow = $catStmt->fetch();
    $catLabel = $categoryRow ? $categoryRow['label'] : ucfirst($product['category']);
    $catUrl = $SITE_URL . '/category/' . rawurlencode($product['category']);

    $pageTitle = bl_e(bl_title($product['name']));
    $pageDescRaw = bl_product_meta_desc($product, $catLabel);
    $pageDesc = bl_e($pageDescRaw);
    $canonical = $SITE_URL . '/product/' . rawurlencode($product['id']);
    $robotsMeta = '';

    $imgFile = __DIR__ . '/assets/img/products/' . $product['id'] . '.jpg';
    $ogImage = file_exists($imgFile)
        ? $SITE_URL . '/assets/img/products/' . rawurlencode($product['id']) . '.jpg'
        : $SITE_URL . '/assets/img/hero/hero-stones.jpg';

    $features = bl_json_list($product['features']);
    $included = bl_json_list($product['included']);
    $specs = json_decode((string)$product['specs'], true);
    if (!is_array($specs)) { $specs = []; }
    $hasSale = ($product['sale_price'] !== null && $product['sale_price'] !== '');
    $curPrice = $hasSale ? $product['sale_price'] : $product['price'];

    $offers = [
        '@type' => 'Offer',
        'url' => $canonical,
        'priceCurrency' => 'USD',
        'price' => number_format((float)$curPrice, 2, '.', ''),
        'itemCondition' => 'https://schema.org/NewCondition',
        'availability' => $product['stock'] === 'out' ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
        'seller' => ['@id' => $SITE_URL . '/#organization'],
        'hasMerchantReturnPolicy' => bl_return_policy_schema($SITE_URL),
    ];
    $ship = bl_shipping_details_schema($product);
    if ($ship) { $offers['shippingDetails'] = $ship; }

    $productSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['name'],
        'description' => bl_clean_text($product['blurb']),
        'sku' => $product['sku'],
        'mpn' => $product['sku'],
        'image' => [$ogImage],
        'brand' => ['@type' => 'Brand', 'name' => $product['vendor']],
        'category' => $catLabel,
        'offers' => $offers,
    ];
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $SITE_URL . '/'],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $catLabel, 'item' => $catUrl],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $product['name'], 'item' => $canonical],
        ],
    ];

    // Related products: the admin-chosen list, else other items from the same category.
    $recIds = array_values(array_filter(bl_json_list($product['recommends']), 'is_string'));
    if ($recIds) {
        $ph = implode(',', array_fill(0, count($recIds), '?'));
        $rs = $db->prepare("SELECT * FROM products WHERE id IN ($ph)");
        $rs->execute($recIds);
        $byId = [];
        foreach ($rs->fetchAll() as $r) { $byId[$r['id']] = $r; }
        foreach ($recIds as $rid) { if (isset($byId[$rid])) { $related[] = $byId[$rid]; } }
    }
    if (!$related) {
        $rs = $db->prepare("SELECT * FROM products WHERE category = ? AND id != ? ORDER BY best_seller DESC, featured DESC, name LIMIT 4");
        $rs->execute([$product['category'], $product['id']]);
        $related = $rs->fetchAll();
    }

    // "Browse more" links to the type pages this product belongs to, plus guides for those types.
    $productTypes = bl_product_types($product);
    $guideSlugs = [];
    foreach ($productTypes as $pt) { foreach ($pt[2][8] as $gs) { $guideSlugs[$gs] = true; } }
    $productGuides = [];
    if ($guideSlugs) {
        $slugs = array_slice(array_keys($guideSlugs), 0, 4);
        try {
            $ph = implode(',', array_fill(0, count($slugs), '?'));
            $gq = $db->prepare("SELECT slug, title FROM guide_posts WHERE published = 1 AND slug IN ($ph)");
            $gq->execute($slugs);
            $productGuides = $gq->fetchAll();
        } catch (Exception $e) { /* guides optional */ }
    }
} else {
    bl_404();
    $pageTitle = 'Product Not Found | Bedrock Lapidary';
    $pageDesc = 'This product could not be found. Browse lapidary saws, grinders, tumblers and supplies at Bedrock Lapidary.';
    $canonical = $SITE_URL . '/product' . ($productId !== '' ? '/' . rawurlencode($productId) : '');
    $robotsMeta = '<meta name="robots" content="noindex, follow">';
    $ogImage = $SITE_URL . '/assets/img/hero/hero-stones.jpg';
}
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
<meta property="og:type" content="product">
<meta property="og:site_name" content="Bedrock Lapidary">
<meta property="og:title" content="<?= $pageTitle ?>">
<meta property="og:description" content="<?= $pageDesc ?>">
<meta property="og:url" content="<?= htmlspecialchars($canonical) ?>">
<meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $pageTitle ?>">
<meta name="twitter:description" content="<?= $pageDesc ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
<?php if ($productSchema): ?>
<script type="application/ld+json"><?= json_encode($productSchema, JSON_UNESCAPED_SLASHES) ?></script>
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

<?php if (!$product): ?>
<div class="container" style="padding:60px 0;">
  <h1>Product not found</h1>
  <p style="max-width:60ch;">We couldn't find that product. It may have been renamed or removed. Browse our categories to find what you need:</p>
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
    <a href="/category/<?= bl_e(urlencode($product['category'])) ?>" id="pdp-breadcrumb-cat"><?= bl_e($catLabel) ?></a><span class="sep">/</span>
    <span id="pdp-breadcrumb-name"><?= bl_e($product['name']) ?></span>
  </div>
</nav>

<div class="container">
  <div class="pdp">

    <div class="pdp-gallery">
      <div class="pdp-gallery-main" id="pdp-gallery-main"><?= bl_img($product['id'], $product['name'], 'pdp-photo', true, '(max-width: 900px) 92vw, 560px') ?></div>
    </div>

    <div class="pdp-info">
      <div class="vendor" id="pdp-vendor"><?= bl_e($product['vendor']) ?></div>
      <span id="pdp-best-seller-badge" style="<?= !empty($product['best_seller']) ? 'display:inline-block;' : 'display:none;' ?> background:var(--gold); color:var(--ink); font-size:13px; font-weight:700; padding:3px 10px; border-radius:3px; margin-bottom:8px; width:fit-content;">Best Seller</span>
      <h1 id="pdp-title"><?= bl_e($product['name']) ?></h1>

      <div class="pdp-sku" id="pdp-sku">SKU: <span><?= bl_e($product['sku']) ?></span></div>

      <div class="pdp-price-block" id="pdp-price-block">
<?php if ($hasSale): ?>
        <span class="price"><?= bl_money($product['sale_price']) ?></span><span class="was"><?= bl_money($product['price']) ?></span><span class="save">Save <?= (int)round((1 - $product['sale_price'] / $product['price']) * 100) ?>%</span>
<?php else: ?>
        <span class="price"><?= bl_money($product['price']) ?></span>
<?php endif; ?>
      </div>

      <div class="pdp-variant-picker" id="pdp-variant-picker" style="display:none;"></div>

      <div class="pdp-promo-banner" id="pdp-promo-banner" style="display:none;"></div>

      <div class="qty-row">
        <div class="qty-stepper">
          <button class="minus" aria-label="Decrease quantity" type="button">–</button>
          <input type="text" value="1" inputmode="numeric" aria-label="Quantity">
          <button class="plus" aria-label="Increase quantity" type="button">+</button>
        </div>
        <?php
          $stockLabels = ['in' => 'In stock, ships in 1–2 days', 'low' => 'Low stock, order soon', 'out' => 'Currently out of stock'];
          $stockColor = $product['stock'] === 'out' ? 'var(--oxblood)' : 'var(--green-d)';
        ?>
        <span id="pdp-stock" style="font-size:17.6px; font-weight:600; color:<?= $stockColor ?>;"><?= bl_e(isset($stockLabels[$product['stock']]) ? $stockLabels[$product['stock']] : '') ?></span>
      </div>

      <div class="pdp-actions">
        <button class="btn btn-block" id="add-to-cart-btn"<?= $product['stock'] === 'out' ? ' disabled style="opacity:.5;cursor:not-allowed;"' : '' ?>><?= $product['stock'] === 'out' ? 'Out of Stock' : 'Add to Cart' ?></button>
        <a href="/contact" class="btn btn-outline">Ask a Question</a>
      </div>

      <a href="/returns" class="pdp-policy-link">Shipping &amp; Returns Policy →</a>

      <ul class="pdp-meta-list" id="pdp-meta-list">
<?php foreach (array_slice($specs, 0, 5, true) as $k => $v): ?>
        <li><span><?= bl_e($k) ?></span><span><?= bl_e($v) ?></span></li>
<?php endforeach; ?>
        <li><span>SKU</span><span><?= bl_e($product['sku']) ?></span></li>
      </ul>
    </div>
  </div>

  <div class="pdp-tabs">
    <div class="tab-heads">
      <button class="active" data-tab="desc">Description</button>
      <button data-tab="specs">Specifications</button>
    </div>
    <div class="tab-panel active" data-tab="desc" id="tab-desc">
<?php foreach (preg_split('/\n\s*\n/', (string)$product['blurb']) as $para): if (trim($para) === '') continue; ?>
      <p><?= bl_e(trim($para)) ?></p>
<?php endforeach; ?>
<?php if ($features): ?>
      <p><strong>Key features:</strong></p><ul><?php foreach ($features as $f): ?><li><?= bl_e($f) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
<?php if ($included): ?>
      <p><strong>What's included:</strong></p><ul><?php foreach ($included as $f): ?><li><?= bl_e($f) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
    </div>
    <div class="tab-panel" data-tab="specs" id="tab-specs">
      <table class="spec-table">
<?php foreach ($specs as $k => $v): ?>
        <tr><td><?= bl_e($k) ?></td><td><?= bl_e($v) ?></td></tr>
<?php endforeach; ?>
        <tr><td>Warranty</td><td><?= bl_e($product['warranty']) ?></td></tr>
      </table>
      <p style="margin-top:18px; font-size:17.6px;"><a href="/returns">See our shipping &amp; returns policy →</a></p>
    </div>
  </div>

  <nav class="pdp-browse" aria-label="Browse related categories">
    <span>More in:</span>
    <a href="/category/<?= bl_e(urlencode($product['category'])) ?>"><?= bl_e($catLabel) ?></a>
<?php foreach ($productTypes as $pt): ?>
    <a href="/category/<?= bl_e(urlencode($pt[0])) ?>&amp;type=<?= bl_e(urlencode($pt[1])) ?>"><?= bl_e($pt[2][0]) ?></a>
<?php endforeach; ?>
  </nav>
<?php if ($productGuides): ?>
  <section class="cat-guides">
    <h2>Helpful buying guides</h2>
    <ul>
<?php foreach ($productGuides as $g): ?>
      <li><a href="/guides/<?= bl_e(urlencode($g['slug'])) ?>"><?= bl_e($g['title']) ?></a></li>
<?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<?php if ($related): ?>
  <section class="section" style="padding-top:0;">
    <div class="section-head">
      <h2>Recommended with the <?= bl_e($product['name']) ?></h2>
    </div>
    <div class="product-grid" id="pdp-recommends"><?php foreach ($related as $r) { echo bl_card($r); } ?></div>
  </section>
<?php endif; ?>
</div>
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
<script>loadCatalog().then(renderProductDetail);</script>
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
