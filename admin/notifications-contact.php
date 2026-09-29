<?php
require_once __DIR__ . '/auth.php';
$activePage = 'notifications-contact';

$db = get_db();
$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
$unreadCount = count(array_filter($messages, fn($m) => !$m['is_read']));

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contact Messages - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790088838">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Contact Messages</h1>
  <p class="subtitle"><?= $unreadCount ?> unread of <?= count($messages) ?> total.</p>

  <div class="help-box">
    <strong>What this is</strong>
    Every submission from the Contact Us page lands here, saved to the database the moment it's sent, whether or not the notification email successfully goes out. This is the reliable record, check here if you're ever unsure whether an email reached you.
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <div class="toolbar">
    <input type="search" id="search" placeholder="Search by name, email, or message...">
  </div>

  <div class="panel" style="padding:0;">
    <div class="table-scroll">
    <table>
      <thead><tr><th class="sortable" data-sort="date">Received <span class="sort-arrow">▾</span></th><th>From</th><th>Order #</th><th>Message</th><th></th></tr></thead>
      <tbody id="msg-rows">
        <?php foreach ($messages as $m): ?>
        <tr data-search="<?= htmlspecialchars(strtolower($m['name'] . ' ' . $m['email'] . ' ' . $m['message'])) ?>" data-date="<?= strtotime($m['created_at']) ?>" style="<?= $m['is_read'] ? '' : 'background:#FFF9EC;' ?>">
          <td style="white-space:nowrap; font-size:12.5px; color:var(--ink-soft);"><?= date('M j, g:ia', strtotime($m['created_at'])) ?></td>
          <td><?= htmlspecialchars($m['name']) ?><div style="color:var(--ink-soft); font-size:12px;"><?= htmlspecialchars($m['email']) ?></div></td>
          <td><?= htmlspecialchars($m['order_number'] ?: 'None') ?></td>
          <td style="max-width:340px; white-space:pre-wrap;"><?= htmlspecialchars($m['message']) ?></td>
          <td>
            <?php if (!$m['is_read']): ?>
            <form method="post" action="contact-message-mark-read.php" style="display:inline;">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn btn-outline btn-sm">Mark read</button>
            </form>
            <?php else: ?>
              <span class="badge badge-off">Read</span>
            <?php endif; ?>
            <form method="post" action="contact-message-delete.php" style="display:inline;" onsubmit="return confirm('Delete this message?');">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($messages)): ?>
          <tr><td colspan="5" style="text-align:center; color:var(--ink-soft); padding:24px;">No contact messages yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<script>
document.getElementById('search').addEventListener('input', (e) => {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('#msg-rows tr').forEach(row => {
    if (!row.dataset.search) return;
    row.style.display = row.dataset.search.includes(q) ? '' : 'none';
  });
});

let sortAsc = false;
document.querySelectorAll('th.sortable').forEach(th => {
  th.addEventListener('click', () => {
    const rows = Array.from(document.querySelectorAll('#msg-rows tr')).filter(r => r.dataset.date);
    rows.sort((a, b) => sortAsc ? a.dataset.date - b.dataset.date : b.dataset.date - a.dataset.date);
    sortAsc = !sortAsc;
    const tbody = document.getElementById('msg-rows');
    rows.forEach(r => tbody.appendChild(r));
  });
});
</script>
</body>
</html>
