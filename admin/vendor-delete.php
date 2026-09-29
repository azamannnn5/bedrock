<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: vendors.php');
    exit;
}

$db = get_db();
$db->prepare("DELETE FROM vendors WHERE id = ?")->execute([$_POST['id']]);
$_SESSION['flash'] = ['type' => 'success', 'message' => 'Vendor removed from the list.'];
header('Location: vendors.php');
exit;
