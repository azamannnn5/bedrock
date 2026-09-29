<?php
require_once __DIR__ . '/auth.php';
$activePage = 'payment-methods';

$db = get_db();
$methods = $db->query("SELECT * FROM payment_methods ORDER BY sort_order, name")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Payment Methods - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790088838">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Payment Methods</h1>
  <p class="subtitle">The options a customer can choose from at checkout.</p>

  <div class="help-box">
    <strong>What this controls</strong>
    Add a new payment method (like Zelle) any time, no code changes needed. Turning one off hides it from checkout immediately without deleting it, in case you want it back later.
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <div class="toolbar">
    <button class="btn" id="btn-add" type="button">+ Add Payment Method</button>
  </div>

  <div class="panel" style="padding:0;">
    <div class="table-scroll">
    <table>
      <thead><tr><th>Name</th><th>Active</th><th>Order</th><th></th></tr></thead>
      <tbody id="method-rows">
        <?php foreach ($methods as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['name']) ?></td>
          <td>
            <form method="post" action="payment-method-toggle.php" style="display:inline;">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <input type="hidden" name="active" value="<?= $m['active'] ? 0 : 1 ?>">
              <button type="submit" class="btn btn-sm <?= $m['active'] ? 'btn-outline' : '' ?>"><?= $m['active'] ? 'On, click to turn off' : 'Off, click to turn on' ?></button>
            </form>
          </td>
          <td><?= (int)$m['sort_order'] ?></td>
          <td>
            <button class="btn btn-outline btn-sm btn-edit" type="button" data-id="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['name']) ?>" data-sort="<?= $m['sort_order'] ?>">Edit</button>
            <form method="post" action="payment-method-delete.php" style="display:inline;" onsubmit="return confirm('Delete this payment method?');">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
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

<div class="modal-backdrop" id="modal-backdrop">
  <div class="modal">
    <h2 id="modal-title" style="margin-top:0;">Add Payment Method</h2>
    <form method="post" action="payment-method-save.php">
      <input type="hidden" name="id" id="f-id">
      <div class="form-row">
        <div class="field"><label>Name</label><input type="text" name="name" id="f-name" required></div>
        <div class="field"><label>Sort order (lower shows first)</label><input type="number" name="sort_order" id="f-sort" value="0"></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" id="btn-cancel">Cancel</button>
        <button type="submit" class="btn">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('btn-add').addEventListener('click', () => {
  document.getElementById('modal-title').textContent = 'Add Payment Method';
  document.getElementById('f-id').value = '';
  document.getElementById('f-name').value = '';
  document.getElementById('f-sort').value = '0';
  document.getElementById('modal-backdrop').classList.add('open');
});
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('modal-title').textContent = 'Edit Payment Method';
    document.getElementById('f-id').value = btn.dataset.id;
    document.getElementById('f-name').value = btn.dataset.name;
    document.getElementById('f-sort').value = btn.dataset.sort;
    document.getElementById('modal-backdrop').classList.add('open');
  });
});
document.getElementById('btn-cancel').addEventListener('click', () => {
  document.getElementById('modal-backdrop').classList.remove('open');
});
document.getElementById('modal-backdrop').addEventListener('click', (e) => {
  if (e.target.id === 'modal-backdrop') e.currentTarget.classList.remove('open');
});
</script>
</body>
</html>
