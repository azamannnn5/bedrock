<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: free-shipping.php');
    exit;
}

$enabledIds = $_POST['free_shipping'] ?? [];

$db = get_db();
$db->beginTransaction();
try {
    $db->exec("UPDATE products SET free_shipping = 0");
    if (!empty($enabledIds)) {
        $placeholders = implode(',', array_fill(0, count($enabledIds), '?'));
        $stmt = $db->prepare("UPDATE products SET free_shipping = 1 WHERE id IN ($placeholders)");
        $stmt->execute($enabledIds);
    }
    $db->commit();
    $_SESSION['flash'] = ['type' => 'success', 'message' => count($enabledIds) . ' product(s) now show free shipping.'];
} catch (Exception $e) {
    $db->rollBack();
    error_log('Free shipping save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save changes.'];
}

header('Location: free-shipping.php');
exit;
