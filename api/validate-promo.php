<?php
/**
 * POST /api/validate-promo.php
 * Body (JSON): { "code": "SAVE10", "email": "customer@example.com", "productIds": ["saw-x", ...] }
 *
 * Checks a promo code for:
 *   - exists and is active
 *   - current time is within its start/end window
 *   - if scope = 'product', at least one cart item is in its product list
 *   - if one_per_customer, this email hasn't redeemed THIS promo before
 *
 * Returns { valid: true, discountPct, code, scope } or { valid: false, reason }
 * Does NOT record a redemption, that happens at order submission, so
 * checking a code doesn't burn it before the order actually goes through.
 */

require_once __DIR__ . '/config.php';
api_headers();

$input = json_decode(file_get_contents('php://input'), true);
$code = trim($input['code'] ?? '');
$email = trim($input['email'] ?? '');
$productIds = $input['productIds'] ?? [];

if ($code === '') {
    json_response(['valid' => false, 'reason' => 'Enter a promo code.']);
}

$db = get_db();

$stmt = $db->prepare("SELECT * FROM promo_codes WHERE code = ? AND active = 1");
$stmt->execute([$code]);
$promo = $stmt->fetch();

if (!$promo) {
    json_response(['valid' => false, 'reason' => 'That code is not valid.']);
}

$now = new DateTime();
$starts = new DateTime($promo['starts_at']);
$ends = new DateTime($promo['ends_at']);

if ($now < $starts) {
    json_response(['valid' => false, 'reason' => 'This code is not active yet.']);
}
if ($now > $ends) {
    json_response(['valid' => false, 'reason' => 'This code has expired.']);
}

if ($promo['scope'] === 'product') {
    $stmt = $db->prepare("SELECT product_id FROM promo_code_products WHERE promo_id = ?");
    $stmt->execute([$promo['id']]);
    $eligibleIds = array_column($stmt->fetchAll(), 'product_id');
    $overlap = array_intersect($productIds, $eligibleIds);
    if (empty($overlap)) {
        json_response(['valid' => false, 'reason' => 'This code only applies to specific products not in your cart.']);
    }
}

if ($promo['one_per_customer'] && $email !== '') {
    $stmt = $db->prepare("SELECT COUNT(*) AS n FROM promo_redemptions WHERE promo_id = ? AND email = ?");
    $stmt->execute([$promo['id'], $email]);
    if ($stmt->fetch()['n'] > 0) {
        json_response(['valid' => false, 'reason' => 'This code has already been used on this email address.']);
    }
}

json_response([
    'valid'       => true,
    'code'        => $promo['code'],
    'discountPct' => (float)$promo['discount_pct'],
    'scope'       => $promo['scope'],
    'eligibleProductIds' => $promo['scope'] === 'product' ? $eligibleIds : null,
]);
