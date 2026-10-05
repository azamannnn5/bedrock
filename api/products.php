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

/**
 * Variants (grit / size / motor options) for every product, grouped by
 * product id. Every product has at least one variant row; for a plain
 * product the variant id equals the product id.
 */
function load_variants($db, $ids = null) {
    $sql = "SELECT id, product_id, sku, price, sale_price, stock, option_label FROM product_variants";
    $params = [];
    if ($ids !== null) {
        if (!$ids) return [];
        $sql .= " WHERE product_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")";
        $params = $ids;
    }
    $sql .= " ORDER BY product_id, sort_order, id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $out = [];
    foreach ($stmt->fetchAll() as $v) {
        $out[$v['product_id']][] = [
            'id'    => $v['id'],
            'sku'   => $v['sku'],
            'price' => (float)$v['price'],
            'sale'  => $v['sale_price'] !== null ? (float)$v['sale_price'] : null,
            'stock' => $v['stock'],
            'label' => $v['option_label'] !== null ? $v['option_label'] : '',
        ];
    }
    return $out;
}

function row_to_product($row, $variants = []) {
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
        'variants'     => $variants,
    ];
}

if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(['error' => 'Product not found'], 404);
    }
    $vmap = load_variants($db, [$row['id']]);
    json_response(row_to_product($row, $vmap[$row['id']] ?? []));
}

$categoryRows = $db->query("SELECT slug, label FROM categories")->fetchAll();
$categories = [];
foreach ($categoryRows as $c) {
    $categories[$c['slug']] = $c['label'];
}

$hideOutOfStock = get_setting('out_of_stock_behavior', 'show_grayed_out') === 'hide_completely';
$productSql = "SELECT * FROM products" . ($hideOutOfStock ? " WHERE stock != 'out'" : "") . " ORDER BY name";
$productRows = $db->query($productSql)->fetchAll();
$vmap = load_variants($db);
$products = array_map(function ($r) use ($vmap) { return row_to_product($r, $vmap[$r['id']] ?? []); }, $productRows);

json_response([
    'products'      => $products,
    'categories'    => $categories,
]);
