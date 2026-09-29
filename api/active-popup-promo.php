<?php
/**
 * GET /api/active-popup-promo.php
 * Returns just the discount percentage of the currently active sitewide
 * "popup" promo, so the popup headline can say the real number instead of
 * a hardcoded one. Deliberately does NOT return the actual code, that's
 * only ever delivered by email after someone signs up.
 */

require_once __DIR__ . '/config.php';
api_headers();

$db = get_db();
$stmt = $db->prepare("SELECT discount_pct FROM promo_codes
    WHERE scope = 'sitewide' AND source = 'popup' AND active = 1
    AND NOW() BETWEEN starts_at AND ends_at
    ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$promo = $stmt->fetch();

json_response([
    'active'      => (bool)$promo,
    'discountPct' => $promo ? (float)$promo['discount_pct'] : null,
]);
