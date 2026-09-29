<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: promos.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare("DELETE FROM promo_codes WHERE id = ?");
$stmt->execute([$_POST['id']]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Promo code deleted.'];
header('Location: promos.php');
exit;
