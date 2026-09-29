<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? '';
$enabled = !empty($input['enabled']) ? 1 : 0;

if ($id === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing product id']);
    exit;
}

$db = get_db();
$stmt = $db->prepare("UPDATE products SET free_shipping = ? WHERE id = ?");
$stmt->execute([$enabled, $id]);

echo json_encode(['success' => true]);
