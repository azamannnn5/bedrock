<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: index.php');
    exit;
}

$db = get_db();
$stmt = $db->prepare("SELECT name FROM products WHERE id = ?");
$stmt->execute([$_POST['id']]);
$product = $stmt->fetch();

if ($product) {
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => "Deleted \"{$product['name']}\"."];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Product not found.'];
}

header('Location: index.php');
exit;
