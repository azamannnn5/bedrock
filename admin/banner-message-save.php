<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

$id = $_POST['id'] ?: null;
$message = trim($_POST['message'] ?? '');
$seconds = max(2, (int)($_POST['display_seconds'] ?? 5));

if ($message === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Banner message cannot be empty.'];
    header('Location: settings.php');
    exit;
}

$db = get_db();
try {
    if ($id) {
        $db->prepare("UPDATE banner_messages SET message = ?, display_seconds = ? WHERE id = ?")->execute([$message, $seconds, $id]);
    } else {
        $maxOrder = $db->query("SELECT COALESCE(MAX(sort_order), -1) AS m FROM banner_messages")->fetch()['m'];
        $db->prepare("INSERT INTO banner_messages (message, display_seconds, active, sort_order) VALUES (?, ?, 1, ?)")
           ->execute([$message, $seconds, $maxOrder + 1]);
    }
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Banner message saved.'];
} catch (Exception $e) {
    error_log('Banner message save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save.'];
}

header('Location: settings.php');
exit;
