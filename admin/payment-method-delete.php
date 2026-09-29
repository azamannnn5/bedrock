<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $db = get_db();
    $db->prepare("DELETE FROM payment_methods WHERE id = ?")->execute([$_POST['id']]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Payment method deleted.'];
}

header('Location: payment-methods.php');
exit;
