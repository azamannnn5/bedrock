<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: featured.php');
    exit;
}

$enabledIds = $_POST['featured'] ?? [];

$db = get_db();
$db->beginTransaction();
try {
    $db->exec("UPDATE products SET featured = 0");
    if (!empty($enabledIds)) {
        $placeholders = implode(',', array_fill(0, count($enabledIds), '?'));
        $db->prepare("UPDATE products SET featured = 1 WHERE id IN ($placeholders)")->execute($enabledIds);
    }
    $db->commit();
    $_SESSION['flash'] = ['type' => 'success', 'message' => count($enabledIds) . ' product(s) now featured.'];
} catch (Exception $e) {
    $db->rollBack();
    error_log('Featured save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save changes.'];
}

header('Location: featured.php');
exit;
