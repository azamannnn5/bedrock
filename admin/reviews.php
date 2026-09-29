<?php
require_once __DIR__ . '/auth.php';
$activePage = 'reviews';

$db = get_db();

$statusFilter = $_GET['status'] ?? 'pending';
if (!in_array($statusFilter, ['pending', 'approved', 'rejected'], true)) {
    $statusFilter = 'pending';
}

$counts = [];
foreach (['pending', 'approved', 'rejected'] as $s) {
    $stmt = $db->prepare("SELECT COUNT(*) AS n FROM reviews WHERE status = ?");
    $stmt->execute([$s]);
    $counts[$s] = (int)$stmt->fetch()['n'];
}

$stmt = $db->prepare("SELECT r.*, p.name AS product_name FROM reviews r
    LEFT JOIN products p ON p.id = r.product_id
    WHERE r.status = ?
    ORDER BY r.created_at DESC");
$stmt->execute([$statusFilter]);
$reviews = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reviews - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790088838">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Reviews</h1>
  <p class="subtitle">
    Customer-submitted reviews from the product page land here as Pending.
    Approving one makes it visible on the site and folds it into that
    product's star rating and review count; the 450 reviews pulled from
    lapidarymart.com were imported as already-approved and don't need any
    action.
  </p>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <div class="toolbar">
    <a href="reviews.php?status=pending" class="btn <?= $statusFilter === 'pending' ? '' : 'btn-outline' ?> btn-sm">Pending (<?= $counts['pending'] ?>)</a>
    <a href="reviews.php?status=approved" class="btn <?= $statusFilter === 'approved' ? '' : 'btn-outline' ?> btn-sm">Approved (<?= $counts['approved'] ?>)</a>
    <a href="reviews.php?status=rejected" class="btn <?= $statusFilter === 'rejected' ? '' : 'btn-outline' ?> btn-sm">Rejected (<?= $counts['rejected'] ?>)</a>
  </div>

  <div class="panel" style="padding:0;">
    <div class="table-scroll">
    <table>
      <thead>
        <tr>
          <th>Product</th><th>Author</th><th>Rating</th><th>Review</th><th>Submitted</th><th>Source</th><th style="width:220px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reviews as $r): ?>
        <tr>
          <td>
            <?php if ($r['product_name']): ?>
              <a href="../product.html?id=<?= urlencode($r['product_id']) ?>" target="_blank"><?= htmlspecialchars($r['product_name']) ?></a>
            <?php else: ?>
              <span style="color:var(--ink-soft);"><?= htmlspecialchars($r['product_id']) ?> (deleted)</span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($r['author']) ?></td>
          <td><?= number_format((float)$r['rating'], 1) ?> ★</td>
          <td style="max-width:360px; font-size:13.5px;"><?= nl2br(htmlspecialchars($r['body'])) ?></td>
          <td style="font-size:12.5px; white-space:nowrap;"><?= date('M j, Y g:ia', strtotime($r['created_at'])) ?></td>
          <td><?= htmlspecialchars($r['source']) ?></td>
          <td style="white-space:nowrap;">
            <?php if ($statusFilter !== 'approved'): ?>
              <form method="post" action="review-status-update.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars($statusFilter) ?>">
                <button type="submit" class="btn btn-sm">Approve</button>
              </form>
            <?php endif; ?>
            <?php if ($statusFilter !== 'rejected'): ?>
              <form method="post" action="review-status-update.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="return_status" value="<?= htmlspecialchars($statusFilter) ?>">
                <button type="submit" class="btn btn-outline btn-sm">Reject</button>
              </form>
            <?php endif; ?>
            <form method="post" action="review-status-update.php" style="display:inline;" onsubmit="return confirm('Permanently delete this review? This can\'t be undone.');">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="return_status" value="<?= htmlspecialchars($statusFilter) ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($reviews)): ?>
          <tr><td colspan="7" style="text-align:center; color:var(--ink-soft); padding:24px;">No <?= htmlspecialchars($statusFilter) ?> reviews.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>
</body>
</html>
