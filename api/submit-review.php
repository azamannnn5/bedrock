<?php
/**
 * POST /api/submit-review.php
 * Body (JSON): { "productId": "...", "author": "...", "rating": 1-5, "body": "..." }
 *
 * Saves a customer review as 'pending'. It does NOT show on the site and
 * does NOT count toward the product's review count or star rating until
 * an admin approves it from admin/reviews.php.
 */

require_once __DIR__ . '/config.php';
api_headers();

$input = json_decode(file_get_contents('php://input'), true);
$productId = trim($input['productId'] ?? '');
$author = trim($input['author'] ?? '');
$rating = isset($input['rating']) ? (int)$input['rating'] : 0;
$body = trim($input['body'] ?? '');

if ($productId === '' || $author === '' || $body === '' || $rating < 1 || $rating > 5) {
    json_response(['success' => false, 'error' => 'Please add your name, a star rating, and a review before submitting.'], 400);
}

if (mb_strlen($author) > 150) {
    json_response(['success' => false, 'error' => 'Name is too long.'], 400);
}

$db = get_db();

$stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$productId]);
if (!$stmt->fetch()) {
    json_response(['success' => false, 'error' => 'Product not found.'], 404);
}

$stmt = $db->prepare("INSERT INTO reviews (product_id, author, rating, body, source, status) VALUES (?, ?, ?, ?, 'site', 'pending')");
$stmt->execute([$productId, $author, $rating, $body]);

json_response([
    'success' => true,
    'message' => "Thanks! Your review has been submitted and will show up here once it's approved.",
]);
