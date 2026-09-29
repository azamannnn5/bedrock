<?php
/**
 * GET /api/products.php
 * Returns the full product catalog as JSON, in the same shape the frontend
 * already expects (matches the old static products-data.js structure) so
 * catalog.js only needs to change *how* it loads data, not how it reads it.
 *
 * Optional: ?id=product-id to fetch a single product.
 */

require_once __DIR__ . '/config.php';
api_headers();

$db = get_db();

function row_to_product($row) {
    return [
        'id'           => $row['id'],
        'name'         => $row['name'],
        'vendor'       => $row['vendor'],
        'category'     => $row['category'],
        'price'        => (float)$row['price'],
        'sale'         => $row['sale_price'] !== null ? (float)$row['sale_price'] : null,
        'sku'          => $row['sku'],
        'blurb'        => $row['blurb'],
        'features'     => json_decode($row['features'], true) ?: [],
        'included'     => json_decode($row['included'], true) ?: [],
        'specs'        => json_decode($row['specs'], true) ?: new stdClass(),
        'warranty'     => $row['warranty'],
        'tag'          => $row['tag'],
        'stock'        => $row['stock'],
        'shipping'     => $row['shipping_text'],
        'featured'     => (bool)$row['featured'],
        'bestSeller'   => (bool)$row['best_seller'],
        'recommends'   => json_decode($row['recommends'], true) ?: [],
    ];
}

if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(['error' => 'Product not found'], 404);
    }
    json_response(row_to_product($row));
}

$categoryRows = $db->query("SELECT slug, label FROM categories")->fetchAll();
$categories = [];
foreach ($categoryRows as $c) {
    $categories[$c['slug']] = $c['label'];
}

$hideOutOfStock = get_setting('out_of_stock_behavior', 'show_grayed_out') === 'hide_completely';
$productSql = "SELECT * FROM products" . ($hideOutOfStock ? " WHERE stock != 'out'" : "") . " ORDER BY name";
$productRows = $db->query($productSql)->fetchAll();
$products = array_map('row_to_product', $productRows);

json_response([
    'products'      => $products,
    'categories'    => $categories,
]);
