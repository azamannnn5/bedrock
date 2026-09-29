<?php
/**
 * Dynamic XML sitemap. Always reflects the live catalog, category list, and
 * published guides, so it never goes stale the way a generated-once static
 * file would. Served at /sitemap.xml via the rewrite rule in .htaccess.
 */
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/includes/seo.php';

header('Content-Type: application/xml; charset=utf-8');

$SITE_URL = SITE_URL;
$db = get_db();

$staticPages = [
    ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
    ['loc' => '/about.html', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ['loc' => '/contact.html', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ['loc' => '/guide.html', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => '/returns.html', 'priority' => '0.3', 'changefreq' => 'yearly'],
    ['loc' => '/privacy.html', 'priority' => '0.2', 'changefreq' => 'yearly'],
    ['loc' => '/terms.html', 'priority' => '0.2', 'changefreq' => 'yearly'],
    ['loc' => '/warranties.html', 'priority' => '0.3', 'changefreq' => 'yearly'],
];

$categories = $db->query("SELECT slug FROM categories")->fetchAll();
$products = $db->query("SELECT id, updated_at FROM products WHERE stock != 'out'")->fetchAll();
$typeUrls = [];
foreach (bl_all_types() as $t) {
    if (bl_type_indexable(count(bl_type_products($db, $t[0], $t[1])))) {
        $typeUrls[] = '/category.html?cat=' . urlencode($t[0]) . '&type=' . urlencode($t[1]);
    }
}
$guides = $db->query("SELECT slug, updated_at FROM guide_posts WHERE published = 1")->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

foreach ($staticPages as $p) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($SITE_URL . $p['loc']) . "</loc>\n";
    echo "    <changefreq>{$p['changefreq']}</changefreq>\n";
    echo "    <priority>{$p['priority']}</priority>\n";
    echo "  </url>\n";
}

foreach ($categories as $c) {
    $loc = $SITE_URL . '/category.html?cat=' . urlencode($c['slug']);
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

foreach ($typeUrls as $tu) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($SITE_URL . $tu) . "</loc>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

foreach ($products as $p) {
    $loc = $SITE_URL . '/product.html?id=' . urlencode($p['id']);
    $lastmod = $p['updated_at'] ? date('c', strtotime($p['updated_at'])) : null;
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    if ($lastmod) {
        echo "    <lastmod>{$lastmod}</lastmod>\n";
    }
    if (file_exists(__DIR__ . '/assets/img/products/' . $p['id'] . '.jpg')) {
        echo "    <image:image><image:loc>" . htmlspecialchars($SITE_URL . '/assets/img/products/' . rawurlencode($p['id']) . '.jpg') . "</image:loc></image:image>\n";
    }
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    echo "  </url>\n";
}

foreach ($guides as $g) {
    $loc = $SITE_URL . '/guide-post.html?slug=' . urlencode($g['slug']);
    $lastmod = $g['updated_at'] ? date('c', strtotime($g['updated_at'])) : null;
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    if ($lastmod) {
        echo "    <lastmod>{$lastmod}</lastmod>\n";
    }
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.6</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>' . "\n";
