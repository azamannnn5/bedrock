<?php
/**
 * GET /api/reviews.php
 *
 * Two modes:
 *   ?product_id=X&limit=N   -> approved reviews for one product (for the
 *                              product page's Reviews tab). Reviews that
 *                              mention "Bedrock Lapidary" by name surface
 *                              first, then most recent.
 *   (no product_id)&limit=N -> a homepage-wide sample of approved reviews,
 *                              across the catalog. Every review that
 *                              mentions "Bedrock Lapidary" is always
 *                              included, the rest of the limit is filled
 *                              with other approved reviews.
 *
 * Only ever returns reviews with status = 'approved': a pending or
 * rejected customer submission never shows up here.
 */

require_once __DIR__ . '/config.php';
api_headers();

$db = get_db();

function review_row($r) {
    return [
        'id'        => (int)$r['id'],
        'productId' => $r['product_id'],
        'author'    => $r['author'],
        'rating'    => (float)$r['rating'],
        'body'      => $r['body'],
        'createdAt' => $r['created_at'],
    ];
}

if (isset($_GET['product_id']) && $_GET['product_id'] !== '') {
    $productId = $_GET['product_id'];
    $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 5;

    $stmt = $db->prepare("SELECT * FROM reviews
        WHERE product_id = ? AND status = 'approved'
        ORDER BY (body LIKE '%Bedrock Lapidary%') DESC, created_at DESC
        LIMIT " . (int)$limit);
    $stmt->execute([$productId]);
    $reviews = array_map('review_row', $stmt->fetchAll());

    json_response(['reviews' => $reviews]);
}

$limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 18;

// Every review mentioning "Bedrock Lapidary" by name, always included.
$featuredStmt = $db->query("SELECT * FROM reviews
    WHERE status = 'approved' AND body LIKE '%Bedrock Lapidary%'
    ORDER BY created_at DESC");
$featured = $featuredStmt->fetchAll();

$remaining = $limit - count($featured);
$rest = [];
if ($remaining > 0) {
    $featuredIds = array_column($featured, 'id');
    $placeholders = $featuredIds ? implode(',', array_fill(0, count($featuredIds), '?')) : null;
    $sql = "SELECT * FROM reviews WHERE status = 'approved'"
        . ($placeholders ? " AND id NOT IN ($placeholders)" : '')
        . " ORDER BY RAND() LIMIT " . (int)$remaining;
    $stmt = $db->prepare($sql);
    $stmt->execute($featuredIds);
    $rest = $stmt->fetchAll();
}

$reviews = array_map('review_row', array_merge($featured, $rest));

json_response(['reviews' => $reviews]);
