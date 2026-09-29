<?php
require_once __DIR__ . '/auth.php';
$activePage = 'vendors';

$db = get_db();
$vendors = $db->query("SELECT v.*, (SELECT COUNT(*) FROM products p WHERE p.vendor = v.name) AS product_count
    FROM vendors v ORDER BY v.name")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vendors - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790088838">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Vendors</h1>
  <p class="subtitle">The brand names available when adding or editing a product. Renaming a vendor here updates it everywhere it's used.</p>

  <div class="help-box">
    <strong>What this controls</strong>
    Add a new brand name here before it's needed, so it's ready in the dropdown when you're adding a product. Deleting a vendor here does not delete products, it just removes that name from the list, existing products keep whatever name they already have.
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <div class="toolbar">
    <input type="search" id="search" placeholder="Search vendors...">
    <button class="btn" id="btn-add" type="button">+ Add Vendor</button>
  </div>

  <div class="panel" style="padding:0;">
    <div class="table-scroll">
    <table>
      <thead><tr><th class="sortable" data-sort="name">Name <span class="sort-arrow">▾</span></th><th class="sortable" data-sort="count">Products Using It</th><th></th></tr></thead>
      <tbody id="vendor-rows">
        <?php foreach ($vendors as $v): ?>
        <tr data-name="<?= htmlspecialchars(strtolower($v['name'])) ?>" data-count="<?= (int)$v['product_count'] ?>">
          <td><?= htmlspecialchars($v['name']) ?></td>
          <td><?= (int)$v['product_count'] ?></td>
          <td>
            <button class="btn btn-outline btn-sm btn-edit" type="button" data-id="<?= $v['id'] ?>" data-name="<?= htmlspecialchars($v['name']) ?>">Rename</button>
            <form method="post" action="vendor-delete.php" style="display:inline;" onsubmit="return confirm('Remove this vendor from the list? Products keep their current vendor name.');">
              <input type="hidden" name="id" value="<?= $v['id'] ?>">
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
    <h2 id="modal-title" style="margin-top:0;">Add Vendor</h2>
    <form method="post" action="vendor-save.php">
      <input type="hidden" name="id" id="f-id">
      <div class="form-row full">
        <div class="field"><label>Vendor / brand name</label><input type="text" name="name" id="f-name" required></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" id="btn-cancel">Cancel</button>
        <button type="submit" class="btn">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('search').addEventListener('input', (e) => {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('#vendor-rows tr').forEach(row => {
    row.style.display = row.dataset.name.includes(q) ? '' : 'none';
  });
});

let sortAsc = true;
document.querySelectorAll('th.sortable').forEach(th => {
  th.addEventListener('click', () => {
    const key = th.dataset.sort;
    const rows = Array.from(document.querySelectorAll('#vendor-rows tr'));
    rows.sort((a, b) => {
      const av = key === 'count' ? parseInt(a.dataset.count) : a.dataset.name;
      const bv = key === 'count' ? parseInt(b.dataset.count) : b.dataset.name;
      return sortAsc ? (av > bv ? 1 : -1) : (av < bv ? 1 : -1);
    });
    sortAsc = !sortAsc;
    const tbody = document.getElementById('vendor-rows');
    rows.forEach(r => tbody.appendChild(r));
  });
});

document.getElementById('btn-add').addEventListener('click', () => {
  document.getElementById('modal-title').textContent = 'Add Vendor';
  document.getElementById('f-id').value = '';
  document.getElementById('f-name').value = '';
  document.getElementById('modal-backdrop').classList.add('open');
});
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('modal-title').textContent = 'Rename Vendor';
    document.getElementById('f-id').value = btn.dataset.id;
    document.getElementById('f-name').value = btn.dataset.name;
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
