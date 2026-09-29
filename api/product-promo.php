<?php
/**
 * GET /api/product-promo.php?id=product-id
 * Returns the currently active product-scoped promo for this product, if
 * one exists, for the on-page promo banner. Returns { active: false } when
 * there isn't one, rather than a 404, since "no promo" is a normal state.
 */

require_once __DIR__ . '/config.php';
api_headers();

$productId = $_GET['id'] ?? '';
if ($productId === '') {
    json_response(['active' => false]);
}

$db = get_db();

$stmt = $db->prepare("SELECT pc.* FROM promo_codes pc
    JOIN promo_code_products pcp ON pcp.promo_id = pc.id
    WHERE pcp.product_id = ? AND pc.scope = 'product' AND pc.active = 1
    AND NOW() BETWEEN pc.starts_at AND pc.ends_at
    ORDER BY pc.created_at DESC LIMIT 1");
$stmt->execute([$productId]);
$promo = $stmt->fetch();

if (!$promo) {
    json_response(['active' => false]);
}

json_response([
    'active'      => true,
    'code'        => $promo['code'],
    'discountPct' => (float)$promo['discount_pct'],
    'endsAt'      => $promo['ends_at'],
]);
