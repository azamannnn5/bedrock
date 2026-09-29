<?php
require_once __DIR__ . '/auth.php';
$activePage = 'free-shipping';

$db = get_db();
$categories = $db->query("SELECT slug, label FROM categories ORDER BY label")->fetchAll();
$products = $db->query("SELECT id, name, vendor, category, price, free_shipping FROM products ORDER BY name")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$enabledCount = count(array_filter($products, fn($p) => $p['free_shipping']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Free Shipping - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790070146">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Free Shipping</h1>
  <p class="subtitle">
    <?= $enabledCount ?> of <?= count($products) ?> products currently show "Free shipping on this item"
    on their product page. The site's top banner reads "Free Shipping on Select Products", this is where you choose which ones.
  </p>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <form method="post" action="free-shipping-save.php" id="fs-form">
    <div class="panel">
      <div class="toolbar">
        <input type="search" id="search" placeholder="Search by name or vendor...">
        <select id="category-filter">
          <option value="">All categories</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= htmlspecialchars($c['slug']) ?>"><?= htmlspecialchars($c['label']) ?></option>
          <?php endforeach; ?>
        </select>
        <label style="display:flex; align-items:center; gap:6px; font-size:14px; color:var(--ink-soft);">
          <input type="checkbox" id="select-all"> Select all visible
        </label>
        <button type="button" class="btn btn-sm" id="btn-enable-selected">Turn ON for selected</button>
        <button type="button" class="btn btn-outline btn-sm" id="btn-disable-selected">Turn OFF for selected</button>
      </div>
    </div>

    <div class="panel" style="padding:0;">
      <table>
        <thead>
          <tr><th style="width:30px;"></th><th>Name</th><th>Category</th><th>Price</th><th>Free Shipping</th></tr>
        </thead>
        <tbody id="product-rows">
          <?php foreach ($products as $p): ?>
          <tr data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>" data-vendor="<?= htmlspecialchars(strtolower($p['vendor'])) ?>" data-category="<?= htmlspecialchars($p['category']) ?>">
            <td><input type="checkbox" class="row-select"></td>
            <td><?= htmlspecialchars($p['name']) ?><div style="color:var(--ink-soft); font-size:12px;"><?= htmlspecialchars($p['vendor']) ?></div></td>
            <td><?= htmlspecialchars($p['category']) ?></td>
            <td>$<?= number_format($p['price'], 2) ?></td>
            <td>
              <label class="switch">
                <input type="checkbox" name="free_shipping[]" value="<?= htmlspecialchars($p['id']) ?>" class="row-toggle" <?= $p['free_shipping'] ? 'checked' : '' ?>>
                <span class="slider"></span>
              </label>
              <span class="badge <?= $p['free_shipping'] ? 'badge-on' : 'badge-off' ?>" style="margin-left:8px;"><?= $p['free_shipping'] ? 'ON' : 'OFF' ?></span>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="margin-top:20px;">
      <button type="submit" class="btn">Save All Changes</button>
    </div>
  </form>
</div>

<script>
document.getElementById('search').addEventListener('input', filterRows);
document.getElementById('category-filter').addEventListener('change', filterRows);

function filterRows(){
  const q = document.getElementById('search').value.toLowerCase();
  const cat = document.getElementById('category-filter').value;
  document.querySelectorAll('#product-rows tr').forEach(row => {
    const matchesQ = !q || row.dataset.name.includes(q) || row.dataset.vendor.includes(q);
    const matchesCat = !cat || row.dataset.category === cat;
    row.style.display = (matchesQ && matchesCat) ? '' : 'none';
  });
}

document.getElementById('select-all').addEventListener('change', (e) => {
  document.querySelectorAll('#product-rows tr').forEach(row => {
    if (row.style.display !== 'none') {
      row.querySelector('.row-select').checked = e.target.checked;
    }
  });
});

function updateBadge(row){
  const toggle = row.querySelector('.row-toggle');
  const badge = row.querySelector('.badge');
  badge.textContent = toggle.checked ? 'ON' : 'OFF';
  badge.className = 'badge ' + (toggle.checked ? 'badge-on' : 'badge-off');
}

document.getElementById('btn-enable-selected').addEventListener('click', () => {
  document.querySelectorAll('#product-rows tr').forEach(row => {
    if (row.querySelector('.row-select').checked) {
      row.querySelector('.row-toggle').checked = true;
      updateBadge(row);
    }
  });
});
document.getElementById('btn-disable-selected').addEventListener('click', () => {
  document.querySelectorAll('#product-rows tr').forEach(row => {
    if (row.querySelector('.row-select').checked) {
      row.querySelector('.row-toggle').checked = false;
      updateBadge(row);
    }
  });
});
document.querySelectorAll('.row-toggle').forEach(t => {
  t.addEventListener('change', () => updateBadge(t.closest('tr')));
});
</script>
</body>
</html>
