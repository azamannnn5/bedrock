<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $db = get_db();
    $active = isset($_POST['active']) && $_POST['active'] == '1' ? 1 : 0;
    $db->prepare("UPDATE payment_methods SET active = ? WHERE id = ?")->execute([$active, $_POST['id']]);
}

header('Location: payment-methods.php');
exit;
