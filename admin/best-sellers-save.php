<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: best-sellers.php');
    exit;
}

$enabledIds = $_POST['best_seller'] ?? [];

$db = get_db();
$db->beginTransaction();
try {
    $db->exec("UPDATE products SET best_seller = 0");
    if (!empty($enabledIds)) {
        $placeholders = implode(',', array_fill(0, count($enabledIds), '?'));
        $db->prepare("UPDATE products SET best_seller = 1 WHERE id IN ($placeholders)")->execute($enabledIds);
    }
    $db->commit();
    $_SESSION['flash'] = ['type' => 'success', 'message' => count($enabledIds) . ' product(s) now marked as best sellers.'];
} catch (Exception $e) {
    $db->rollBack();
    error_log('Best sellers save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save changes.'];
}

header('Location: best-sellers.php');
exit;
