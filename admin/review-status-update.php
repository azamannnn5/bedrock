<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reviews.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['approve', 'reject', 'delete'], true)) {
    header('Location: reviews.php');
    exit;
}

$db = get_db();

/**
 * Recomputes a product's review_count and rating from scratch off its
 * approved reviews, rather than incrementing/decrementing, so it's always
 * self-correcting no matter what moderation actions happened before it.
 */
function recompute_product_rating($db, $productId) {
    $stmt = $db->prepare("SELECT COUNT(*) AS n, AVG(rating) AS avg_rating FROM reviews WHERE product_id = ? AND status = 'approved'");
    $stmt->execute([$productId]);
    $row = $stmt->fetch();
    $count = (int)$row['n'];
    $avg = $count > 0 ? round((float)$row['avg_rating'], 1) : 0;
    $upd = $db->prepare("UPDATE products SET review_count = ?, rating = ? WHERE id = ?");
    $upd->execute([$count, $avg, $productId]);
}

$stmt = $db->prepare("SELECT * FROM reviews WHERE id = ?");
$stmt->execute([$id]);
$review = $stmt->fetch();

if (!$review) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Review not found.'];
    header('Location: reviews.php');
    exit;
}

try {
    if ($action === 'delete') {
        $db->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
        recompute_product_rating($db, $review['product_id']);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Review deleted.'];
    } else {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $db->prepare("UPDATE reviews SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        recompute_product_rating($db, $review['product_id']);
        $_SESSION['flash'] = ['type' => 'success', 'message' => $action === 'approve' ? 'Review approved and now live.' : 'Review rejected.'];
    }
} catch (Exception $e) {
    error_log('Review status update failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not update the review.'];
}

header('Location: reviews.php?status=' . urlencode($_POST['return_status'] ?? 'pending'));
exit;
