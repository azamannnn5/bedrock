<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: guides.php');
    exit;
}

$db = get_db();
$db->prepare("DELETE FROM guide_posts WHERE id = ?")->execute([$_POST['id']]);
$_SESSION['flash'] = ['type' => 'success', 'message' => 'Guide deleted.'];
header('Location: guides.php');
exit;
