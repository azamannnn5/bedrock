<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: payment-methods.php');
    exit;
}

$id = $_POST['id'] ?: null;
$name = trim($_POST['name'] ?? '');
$sortOrder = (int)($_POST['sort_order'] ?? 0);

if ($name === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Name cannot be empty.'];
    header('Location: payment-methods.php');
    exit;
}

$db = get_db();
try {
    if ($id) {
        $db->prepare("UPDATE payment_methods SET name = ?, sort_order = ? WHERE id = ?")->execute([$name, $sortOrder, $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Updated \"$name\"."];
    } else {
        $db->prepare("INSERT INTO payment_methods (name, sort_order, active) VALUES (?, ?, 1)")->execute([$name, $sortOrder]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Added \"$name\"."];
    }
} catch (Exception $e) {
    error_log('Payment method save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save.'];
}

header('Location: payment-methods.php');
exit;
