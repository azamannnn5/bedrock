<?php
/**
 * POST /api/submit-order.php
 * Body (JSON): {
 *   customerName, phone, email, address, city, state, zip,
 *   paymentMethod, notes, promoCode,
 *   items: [{ id, name, unitPrice, quantity }, ...]
 * }
 *
 * Recalculates the subtotal/discount/total server-side (never trusts the
 * client's math), re-validates the promo code, saves the order, records the
 * promo redemption if one was used, and emails both the customer and the
 * store owner. Returns { success: true, orderId, total } or an error.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/email_templates.php';
api_headers();

$input = json_decode(file_get_contents('php://input'), true);

$required = ['customerName', 'phone', 'email', 'address', 'city', 'state', 'zip', 'items'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        json_response(['success' => false, 'error' => "Missing required field: $field"], 400);
    }
}

$items = $input['items'];
if (!is_array($items) || count($items) === 0) {
    json_response(['success' => false, 'error' => 'Your cart is empty.'], 400);
}

$db = get_db();

// Recompute subtotal from the database's own prices, not whatever the client sent.
// Cart ids are variant ids (for plain products the variant id equals the product id).
$subtotal = 0;
$cartIds = [];
foreach ($items as $item) {
    if (isset($item['id'])) $cartIds[] = (string)$item['id'];
}
$cartIds = array_values(array_unique($cartIds));
if (!$cartIds) {
    json_response(['success' => false, 'error' => 'Your cart is empty.'], 400);
}
$placeholders = implode(',', array_fill(0, count($cartIds), '?'));

$dbItems = [];   // cart id => ['product_id','name','price','sale_price']
$stmt = $db->prepare("SELECT v.id, v.product_id, v.price, v.sale_price, v.option_label, p.name
                      FROM product_variants v JOIN products p ON p.id = v.product_id
                      WHERE v.id IN ($placeholders)");
$stmt->execute($cartIds);
foreach ($stmt->fetchAll() as $row) {
    $label = trim((string)$row['option_label']);
    $dbItems[$row['id']] = [
        'product_id' => $row['product_id'],
        'name'       => $row['name'] . ($label !== '' ? ' - ' . $label : ''),
        'price'      => $row['price'],
        'sale_price' => $row['sale_price'],
    ];
}
// Fallback for older carts / products without a variant row: match the product itself.
$stmt = $db->prepare("SELECT id, name, price, sale_price FROM products WHERE id IN ($placeholders)");
$stmt->execute($cartIds);
foreach ($stmt->fetchAll() as $row) {
    if (!isset($dbItems[$row['id']])) {
        $dbItems[$row['id']] = [
            'product_id' => $row['id'], 'name' => $row['name'],
            'price' => $row['price'], 'sale_price' => $row['sale_price'],
        ];
    }
}

$verifiedItems = [];
foreach ($items as $item) {
    $cid = isset($item['id']) ? (string)$item['id'] : '';
    if (!isset($dbItems[$cid])) continue;
    $p = $dbItems[$cid];
    $unitPrice = $p['sale_price'] !== null ? (float)$p['sale_price'] : (float)$p['price'];
    $qty = max(1, (int)($item['quantity'] ?? 1));
    $subtotal += $unitPrice * $qty;
    $verifiedItems[] = [
        'product_id'   => $p['product_id'],
        'product_name' => $p['name'],
        'unit_price'   => $unitPrice,
        'quantity'     => $qty,
    ];
}

if (empty($verifiedItems)) {
    json_response(['success' => false, 'error' => 'None of the items in your cart could be found.'], 400);
}

// Re-validate and apply the promo code server-side
$discountAmount = 0;
$promoCode = null;
$promoRow = null;
if (!empty($input['promoCode'])) {
    $stmt = $db->prepare("SELECT * FROM promo_codes WHERE code = ? AND active = 1");
    $stmt->execute([trim($input['promoCode'])]);
    $promo = $stmt->fetch();

    if ($promo) {
        $now = new DateTime();
        $starts = new DateTime($promo['starts_at']);
        $ends = new DateTime($promo['ends_at']);
        $withinWindow = ($now >= $starts && $now <= $ends);

        $alreadyUsed = false;
        if ($promo['one_per_customer']) {
            $stmt2 = $db->prepare("SELECT COUNT(*) AS n FROM promo_redemptions WHERE promo_id = ? AND email = ?");
            $stmt2->execute([$promo['id'], $input['email']]);
            $alreadyUsed = $stmt2->fetch()['n'] > 0;
        }

        $scopeOk = true;
        if ($promo['scope'] === 'product') {
            $stmt3 = $db->prepare("SELECT product_id FROM promo_code_products WHERE promo_id = ?");
            $stmt3->execute([$promo['id']]);
            $eligibleIds = array_column($stmt3->fetchAll(), 'product_id');
            $cartIds = array_column($verifiedItems, 'product_id');
            $scopeOk = count(array_intersect($cartIds, $eligibleIds)) > 0;
        }

        if ($withinWindow && !$alreadyUsed && $scopeOk) {
            $discountAmount = round($subtotal * ((float)$promo['discount_pct'] / 100), 2);
            $promoCode = $promo['code'];
            $promoRow = $promo;
        }
    }
}

$total = max(0, $subtotal - $discountAmount);

// Save the order
$db->beginTransaction();
try {
    $stmt = $db->prepare("INSERT INTO orders
        (customer_name, phone, email, address, city, state, zip, payment_method, notes, promo_code, subtotal, discount_amount, total)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $input['customerName'], $input['phone'], $input['email'], $input['address'],
        $input['city'], $input['state'], $input['zip'], $input['paymentMethod'] ?? null,
        $input['notes'] ?? null, $promoCode, $subtotal, $discountAmount, $total,
    ]);
    $orderId = $db->lastInsertId();

    $itemStmt = $db->prepare("INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity) VALUES (?, ?, ?, ?, ?)");
    foreach ($verifiedItems as $vi) {
        $itemStmt->execute([$orderId, $vi['product_id'], $vi['product_name'], $vi['unit_price'], $vi['quantity']]);
    }

    if ($promoRow) {
        $redeemStmt = $db->prepare("INSERT INTO promo_redemptions (promo_id, email, order_id) VALUES (?, ?, ?)");
        $redeemStmt->execute([$promoRow['id'], $input['email'], $orderId]);
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log("Order submission failed: " . $e->getMessage());
    json_response(['success' => false, 'error' => 'Something went wrong saving your order. Please try again.'], 500);
}

// Send confirmation emails (order is already saved either way, so a mail
// failure here doesn't lose the order)
$orderData = [
    'id' => $orderId,
    'customer_name' => $input['customerName'],
    'phone' => $input['phone'],
    'email' => $input['email'],
    'address' => $input['address'],
    'city' => $input['city'],
    'state' => $input['state'],
    'zip' => $input['zip'],
    'payment_method' => $input['paymentMethod'] ?? null,
    'notes' => $input['notes'] ?? null,
    'promo_code' => $promoCode,
    'subtotal' => $subtotal,
    'discount_amount' => $discountAmount,
    'total' => $total,
    'created_at' => date('Y-m-d H:i:s'),
];

$customerEmailSent = send_email(
    $input['email'],
    "Your Order Request, #$orderId",
    order_confirmation_email($orderData, $verifiedItems, false)
);

$ownerEmailSent = send_email(
    get_setting('order_notification_email', 'orders@REPLACE_ME.com'),
    "New Order Request, #$orderId",
    order_confirmation_email($orderData, $verifiedItems, true),
    $input['email']
);

json_response([
    'success' => true,
    'orderId' => (int)$orderId,
    'subtotal' => $subtotal,
    'discountAmount' => $discountAmount,
    'total' => $total,
    'emailSent' => $customerEmailSent,
]);
