<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $db = get_db();
    $db->prepare("DELETE FROM banner_messages WHERE id = ?")->execute([$_POST['id']]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Banner message deleted.'];
}

header('Location: settings.php');
exit;
