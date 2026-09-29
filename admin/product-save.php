<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = get_db();

function slugify_id($s) {
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

$id = trim($_POST['id']);
$id = slugify_id($id);
$originalId = trim($_POST['original_id'] ?? '');
$isEdit = $originalId !== '';

$name = trim($_POST['name']);
$vendor = trim($_POST['vendor']);
$category = trim($_POST['category']);
$blurb = trim($_POST['blurb']);
$warranty = trim($_POST['warranty'] ?? '');
$tag = $_POST['tag'] !== '' ? $_POST['tag'] : null;
$featured = isset($_POST['featured']) ? 1 : 0;
$bestSeller = isset($_POST['best_seller']) ? 1 : 0;

// Variants: a JSON array built client-side, e.g.
// [{"id":"existing-variant-id-or-empty","option":"8 x 2","sku":"RB0810","price":59.99,"sale_price":null,"stock":"in"}, ...]
// Every product has at least one variant row, even ones with no real
// options (single row, option = null).
$variantsRaw = json_decode($_POST['variants'] ?? '[]', true);
if (!is_array($variantsRaw) || count($variantsRaw) === 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'A product needs at least one price/SKU row. Not saved.'];
    header('Location: index.php');
    exit;
}

$variants = [];
foreach ($variantsRaw as $i => $v) {
    $price = isset($v['price']) && $v['price'] !== '' ? (float)$v['price'] : null;
    $sale = isset($v['sale_price']) && $v['sale_price'] !== '' ? (float)$v['sale_price'] : null;
    $sku = trim($v['sku'] ?? '');
    if ($price === null || $sku === '') {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Every variant needs a SKU and a price. Not saved.'];
        header('Location: index.php');
        exit;
    }
    if ($sale !== null && $sale >= $price) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => "Sale price ($sale) must be lower than the regular price ($price) for SKU $sku. Not saved."];
        header('Location: index.php');
        exit;
    }
    $option = trim($v['option'] ?? '');
    $existingVariantId = trim($v['id'] ?? '');
    $variantId = $existingVariantId !== '' ? $existingVariantId : $id . '-' . ($option !== '' ? slugify_id($option) : slugify_id($sku));
    $variants[] = [
        'id' => $variantId,
        'sku' => $sku,
        'price' => $price,
        'sale_price' => $sale,
        'stock' => in_array($v['stock'] ?? 'in', ['in','low','out']) ? $v['stock'] : 'in',
        'option_label' => $option !== '' ? $option : null,
        'sort_order' => $i,
    ];
}

// Mirror the cheapest (in-stock first) variant onto the products row itself,
// same rule api/products.php uses, so anything still reading products.price
// directly (admin list, order lookups) stays accurate.
$inStock = array_values(array_filter($variants, fn($v) => $v['stock'] !== 'out'));
$pool = count($inStock) ? $inStock : $variants;
$default = $pool[0];
foreach ($pool as $v) {
    if (($v['sale_price'] ?? $v['price']) < ($default['sale_price'] ?? $default['price'])) $default = $v;
}
$hasVariants = count($variants) > 1 ? 1 : 0;

try {
    $db->beginTransaction();

    if ($isEdit) {
        $stmt = $db->prepare("UPDATE products SET
            id = ?, name = ?, vendor = ?, category = ?, price = ?, sale_price = ?, sku = ?,
            blurb = ?, warranty = ?, tag = ?, stock = ?, featured = ?, best_seller = ?, has_variants = ?
            WHERE id = ?");
        $stmt->execute([$id, $name, $vendor, $category, $default['price'], $default['sale_price'], $default['sku'],
            $blurb, $warranty, $tag, $default['stock'], $featured, $bestSeller, $hasVariants, $originalId]);
        // product_variants.product_id has ON UPDATE CASCADE, so an id rename above
        // already carried every existing variant row over to the new id. We now
        // replace them wholesale with whatever the form submitted.
        $db->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$id]);
    } else {
        $stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            $db->rollBack();
            $_SESSION['flash'] = ['type' => 'error', 'message' => "A product with ID \"$id\" already exists. Choose a different ID."];
            header('Location: index.php');
            exit;
        }

        $stmt = $db->prepare("INSERT INTO products
            (id, name, vendor, category, price, sale_price, sku, blurb, features, included, specs, warranty, tag, stock, shipping_text, featured, best_seller, recommends, review_count, rating, has_variants)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, '[]', '[]', '{}', ?, ?, ?, ?, ?, ?, '[]', 0, 0, ?)");
        $defaultShipping = 'Ships in 1-2 business days. Tracking sent within 24 hours of dispatch.';
        $stmt->execute([$id, $name, $vendor, $category, $default['price'], $default['sale_price'], $default['sku'],
            $blurb, $warranty, $tag, $default['stock'], $defaultShipping, $featured, $bestSeller, $hasVariants]);
    }

    $vStmt = $db->prepare("INSERT INTO product_variants (id, product_id, sku, price, sale_price, stock, option_label, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($variants as $v) {
        $vStmt->execute([$v['id'], $id, $v['sku'], $v['price'], $v['sale_price'], $v['stock'], $v['option_label'], $v['sort_order']]);
    }

    $db->commit();
    $_SESSION['flash'] = ['type' => 'success', 'message' => ($isEdit ? "Updated \"$name\"." : "Added \"$name\".")];
} catch (Exception $e) {
    $db->rollBack();
    error_log('Product save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save the product. Check that all required fields are filled in correctly, and that SKUs are unique.'];
}

header('Location: index.php');
exit;
