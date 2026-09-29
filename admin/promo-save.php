<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: promos.php');
    exit;
}

$id = $_POST['id'] ?: null;
$code = strtoupper(trim($_POST['code']));
$discountPct = (float)$_POST['discount_pct'];
$scope = $_POST['scope'] === 'product' ? 'product' : 'sitewide';
$source = $_POST['source'] === 'popup' ? 'popup' : 'manual';
$startsAt = str_replace('T', ' ', $_POST['starts_at']) . ':00';
$endsAt = str_replace('T', ' ', $_POST['ends_at']) . ':00';
$onePerCustomer = isset($_POST['one_per_customer']) ? 1 : 0;
$active = isset($_POST['active']) ? 1 : 0;
$productIds = $_POST['product_ids'] ?? [];

if ($code === '' || $discountPct <= 0) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Code and a valid discount percentage are required.'];
    header('Location: promos.php');
    exit;
}

if ($scope === 'product' && empty($productIds)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Select at least one product for a product-scoped promo.'];
    header('Location: promos.php');
    exit;
}

$db = get_db();

try {
    // Check for duplicate code (excluding the one we're editing)
    $dupStmt = $db->prepare("SELECT id FROM promo_codes WHERE code = ? AND id != ?");
    $dupStmt->execute([$code, $id ?: 0]);
    if ($dupStmt->fetch()) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => "The code \"$code\" is already in use by another promo."];
        header('Location: promos.php');
        exit;
    }

    $db->beginTransaction();

    if ($id) {
        $stmt = $db->prepare("UPDATE promo_codes SET
            code = ?, discount_pct = ?, scope = ?, source = ?, starts_at = ?, ends_at = ?,
            one_per_customer = ?, active = ? WHERE id = ?");
        $stmt->execute([$code, $discountPct, $scope, $source, $startsAt, $endsAt, $onePerCustomer, $active, $id]);
        $promoId = $id;
    } else {
        $stmt = $db->prepare("INSERT INTO promo_codes
            (code, discount_pct, scope, source, starts_at, ends_at, one_per_customer, active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $discountPct, $scope, $source, $startsAt, $endsAt, $onePerCustomer, $active]);
        $promoId = $db->lastInsertId();
    }

    // Reset product scope links
    $db->prepare("DELETE FROM promo_code_products WHERE promo_id = ?")->execute([$promoId]);
    if ($scope === 'product') {
        $linkStmt = $db->prepare("INSERT INTO promo_code_products (promo_id, product_id) VALUES (?, ?)");
        foreach ($productIds as $pid) {
            $linkStmt->execute([$promoId, $pid]);
        }
    }

    $db->commit();
    $_SESSION['flash'] = ['type' => 'success', 'message' => "Saved promo code \"$code\"."];
} catch (Exception $e) {
    $db->rollBack();
    error_log('Promo save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save the promo code.'];
}

header('Location: promos.php');
exit;
