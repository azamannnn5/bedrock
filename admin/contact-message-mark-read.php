<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $db = get_db();
    $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$_POST['id']]);
}

header('Location: notifications-contact.php');
exit;
