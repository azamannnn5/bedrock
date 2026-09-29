<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $validStatuses = ['new', 'confirmed', 'cancelled'];
    $status = in_array($_POST['status'] ?? '', $validStatuses) ? $_POST['status'] : 'new';
    $db = get_db();
    $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $_POST['id']]);
}

header('Location: notifications-orders.php');
exit;
