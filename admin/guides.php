<?php
require_once __DIR__ . '/auth.php';
$activePage = 'guides';

$db = get_db();
$guides = $db->query("SELECT * FROM guide_posts ORDER BY sort_order, created_at DESC")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Guides - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790797840">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Guides</h1>
  <p class="subtitle"><?= count($guides) ?> guide<?= count($guides) === 1 ? '' : 's' ?>. Published guides appear on the live site at /guides; unpublished ones are saved but hidden from visitors.</p>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <div class="toolbar">
    <input type="search" id="search" placeholder="Search by title...">
    <a class="btn" href="guide-edit.php">+ Add Guide</a>
  </div>

  <div class="panel" style="padding:0;">
    <div class="table-scroll">
    <table>
      <thead>
        <tr><th>Title</th><th>Category link</th><th>Status</th><th>Updated</th><th></th></tr>
      </thead>
      <tbody id="guide-rows">
        <?php foreach ($guides as $g): ?>
        <tr data-title="<?= htmlspecialchars(strtolower($g['title'])) ?>">
          <td><?= htmlspecialchars($g['title']) ?><div style="color:var(--ink-soft); font-size:12px;">/guides/<?= htmlspecialchars($g['slug']) ?></div></td>
          <td><?= htmlspecialchars($g['category_link'] ?: '—') ?></td>
          <td><?= $g['published'] ? '<span style="color:var(--green-d); font-weight:600;">Published</span>' : '<span style="color:var(--ink-soft);">Draft</span>' ?></td>
          <td><?= htmlspecialchars(date('M j, Y', strtotime($g['updated_at']))) ?></td>
          <td>
            <a class="btn btn-outline btn-sm" href="guide-edit.php?id=<?= (int)$g['id'] ?>">Edit</a>
            <form method="post" action="guide-delete.php" style="display:inline;" onsubmit="return confirm('Delete ' + <?= json_encode($g['title']) ?> + '? This can\'t be undone.');">
              <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<script>
document.getElementById('search').addEventListener('input', (e) => {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('#guide-rows tr').forEach(row => {
    row.style.display = row.dataset.title.includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>
