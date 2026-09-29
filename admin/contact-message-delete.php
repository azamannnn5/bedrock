<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $db = get_db();
    $db->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$_POST['id']]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Message deleted.'];
}

header('Location: notifications-contact.php');
exit;
