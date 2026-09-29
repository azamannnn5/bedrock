<?php
require_once __DIR__ . '/auth.php';
$activePage = 'products';

$db = get_db();
$categories = $db->query("SELECT slug, label FROM categories ORDER BY label")->fetchAll();
$products = $db->query("SELECT * FROM products ORDER BY name")->fetchAll();
$vendors = $db->query("SELECT name FROM vendors ORDER BY name")->fetchAll();

$variantRows = $db->query("SELECT * FROM product_variants ORDER BY product_id, sort_order")->fetchAll();
$variantsByProduct = [];
foreach ($variantRows as $v) {
    $variantsByProduct[$v['product_id']][] = $v;
}
foreach ($products as &$p) {
    $p['variants'] = $variantsByProduct[$p['id']] ?? [];
}
unset($p);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Products - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790088838">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Products</h1>
  <p class="subtitle"><?= count($products) ?> products in the catalog. Editing here updates the live site for every visitor immediately.</p>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <div class="toolbar">
    <input type="search" id="search" placeholder="Search by name or vendor...">
    <select id="category-filter">
      <option value="">All categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= htmlspecialchars($c['slug']) ?>"><?= htmlspecialchars($c['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" id="btn-add" type="button">+ Add Product</button>
  </div>

  <div class="panel" style="padding:0;">
    <table>
      <thead>
        <tr>
          <th></th><th>Name</th><th>Category</th><th>Price</th><th>Variants</th><th>Stock</th>
          <th></th>
        </tr>
      </thead>
      <tbody id="product-rows">
        <?php foreach ($products as $p): $vCount = count($p['variants']); ?>
        <tr data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>" data-vendor="<?= htmlspecialchars(strtolower($p['vendor'])) ?>" data-category="<?= htmlspecialchars($p['category']) ?>">
          <td><img class="thumb" src="../assets/img/products/<?= htmlspecialchars($p['id']) ?>.jpg" onerror="this.style.visibility='hidden'"></td>
          <td><?= htmlspecialchars($p['name']) ?><div style="color:var(--ink-soft); font-size:12px;"><?= htmlspecialchars($p['vendor']) ?></div></td>
          <td><?= htmlspecialchars($p['category']) ?></td>
          <td>$<?= number_format($p['price'], 2) ?><?= $p['sale_price'] !== null ? ' <span style="color:var(--red);">sale $' . number_format($p['sale_price'], 2) . '</span>' : '' ?><?= $vCount > 1 ? ' <span style="color:var(--ink-soft);">from</span>' : '' ?></td>
          <td><?= $vCount > 1 ? "$vCount options" : '—' ?></td>
          <td><?= htmlspecialchars($p['stock']) ?></td>
          <td>
            <button class="btn btn-outline btn-sm btn-edit" type="button" data-id="<?= htmlspecialchars($p['id']) ?>">Edit</button>
            <button class="btn btn-danger btn-sm btn-delete" type="button" data-id="<?= htmlspecialchars($p['id']) ?>">Delete</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit modal -->
<div class="modal-backdrop" id="modal-backdrop">
  <div class="modal">
    <h2 id="modal-title" style="margin-top:0;">Edit Product</h2>
    <form id="product-form" method="post" action="product-save.php">
      <input type="hidden" name="original_id" id="f-original-id">
      <input type="hidden" name="variants" id="f-variants">
      <div class="modal-body">
        <div class="form-row">
          <div class="field"><label>Product ID</label><input type="text" name="id" id="f-id" required></div>
        </div>
        <div class="form-row full">
          <div class="field"><label>Name</label><input type="text" name="name" id="f-name" required></div>
        </div>
        <div class="form-row">
          <div class="field">
            <label>Vendor</label>
            <select name="vendor" id="f-vendor" required>
              <?php foreach ($vendors as $v): ?>
                <option value="<?= htmlspecialchars($v['name']) ?>"><?= htmlspecialchars($v['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="field-help">Need a new brand? Add it on the <a href="vendors.php">Vendors</a> page first.</div>
          </div>
          <div class="field">
            <label>Category</label>
            <select name="category" id="f-category" required>
              <?php foreach ($categories as $c): ?>
                <option value="<?= htmlspecialchars($c['slug']) ?>"><?= htmlspecialchars($c['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label>Badge</label>
            <select name="tag" id="f-tag">
              <option value="">None</option>
              <option value="New">New</option>
              <option value="Sale">Sale</option>
            </select>
          </div>
        </div>
        <div class="form-row full">
          <div class="field"><label>Description</label><textarea name="blurb" id="f-blurb" rows="4" required></textarea></div>
        </div>
        <div class="form-row full">
          <div class="field"><label>Warranty text</label><input type="text" name="warranty" id="f-warranty"></div>
        </div>
        <div class="form-row full">
          <div class="field">
            <label><input type="checkbox" name="featured" id="f-featured" value="1"> Featured (shows in the homepage "Featured Machine Collections" carousel)</label>
          </div>
        </div>
        <div class="form-row full">
          <div class="field">
            <label><input type="checkbox" name="best_seller" id="f-best-seller" value="1"> Best Seller (shows in the homepage "Best Sellers" carousel, independent from the New/Sale badge below)</label>
          </div>
        </div>
        <div class="form-row full">
          <div class="field">
            <label>Options / variants</label>
            <div class="field-help" style="margin-bottom:8px;">One row per purchasable SKU. Leave "Option" blank on a single-row product with no real variants (grit, size, motor, etc).</div>
            <div class="variant-editor">
              <div class="variant-editor-head"><span>Option</span><span>SKU</span><span>Price</span><span>Sale</span><span>Stock</span><span></span></div>
              <div id="variant-rows"></div>
              <button type="button" class="btn btn-outline btn-sm" id="btn-add-variant">+ Add option</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" id="btn-cancel">Cancel</button>
        <button type="submit" class="btn">Save Product</button>
      </div>
    </form>
  </div>
</div>

<script>
const PRODUCTS_DATA = <?= json_encode($products) ?>;

function findProduct(id){ return PRODUCTS_DATA.find(p => p.id === id); }

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


// ---------------------------------------------------------------------------
// Variant rows editor
// ---------------------------------------------------------------------------
function variantRowHTML(v){
  v = v || {};
  return `
    <div class="variant-editor-row" data-variant-id="${v.id || ''}">
      <input type="text" class="v-option" placeholder="e.g. 8&quot; / 220 / No Hole" value="${(v.option_label || '').replace(/"/g,'&quot;')}">
      <input type="text" class="v-sku" placeholder="SKU" value="${(v.sku || '').replace(/"/g,'&quot;')}" required>
      <input type="number" step="0.01" class="v-price" placeholder="Price" value="${v.price ?? ''}" required>
      <input type="number" step="0.01" class="v-sale" placeholder="Sale" value="${v.sale_price ?? ''}">
      <select class="v-stock">
        <option value="in" ${v.stock === 'in' || !v.stock ? 'selected' : ''}>In stock</option>
        <option value="low" ${v.stock === 'low' ? 'selected' : ''}>Low</option>
        <option value="out" ${v.stock === 'out' ? 'selected' : ''}>Out</option>
      </select>
      <button type="button" class="variant-remove-btn" title="Remove this option">&times;</button>
    </div>`;
}

function addVariantRow(v){
  const wrap = document.getElementById('variant-rows');
  wrap.insertAdjacentHTML('beforeend', variantRowHTML(v));
  const row = wrap.lastElementChild;
  row.querySelector('.variant-remove-btn').addEventListener('click', () => {
    if (document.querySelectorAll('.variant-editor-row').length > 1) row.remove();
  });
}

document.getElementById('btn-add-variant').addEventListener('click', () => addVariantRow());

document.getElementById('product-form').addEventListener('submit', () => {
  const rows = [...document.querySelectorAll('.variant-editor-row')].map(row => ({
    id: row.dataset.variantId || '',
    option: row.querySelector('.v-option').value.trim(),
    sku: row.querySelector('.v-sku').value.trim(),
    price: row.querySelector('.v-price').value,
    sale_price: row.querySelector('.v-sale').value,
    stock: row.querySelector('.v-stock').value,
  }));
  document.getElementById('f-variants').value = JSON.stringify(rows);
});

document.getElementById('btn-add').addEventListener('click', () => {
  document.getElementById('modal-title').textContent = 'Add Product';
  document.getElementById('product-form').reset();
  document.getElementById('f-original-id').value = '';
  document.getElementById('f-id').disabled = false;
  document.getElementById('variant-rows').innerHTML = '';
  addVariantRow();
  document.getElementById('modal-backdrop').classList.add('open');
});

document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    const p = findProduct(btn.dataset.id);
    if (!p) return;
    document.getElementById('modal-title').textContent = 'Edit Product';
    document.getElementById('f-original-id').value = p.id;
    document.getElementById('f-id').value = p.id;
    document.getElementById('f-id').disabled = false;
    document.getElementById('f-name').value = p.name;
    document.getElementById('f-vendor').value = p.vendor;
    document.getElementById('f-category').value = p.category;
    document.getElementById('f-tag').value = p.tag || '';
    document.getElementById('f-blurb').value = p.blurb;
    document.getElementById('f-warranty').value = p.warranty || '';
    document.getElementById('f-featured').checked = !!Number(p.featured);
    document.getElementById('f-best-seller').checked = !!Number(p.best_seller);
    document.getElementById('variant-rows').innerHTML = '';
    (p.variants && p.variants.length ? p.variants : [{sku: p.sku, price: p.price, sale_price: p.sale_price, stock: p.stock}]).forEach(addVariantRow);
    document.getElementById('modal-backdrop').classList.add('open');
  });
});

document.querySelectorAll('.btn-delete').forEach(btn => {
  btn.addEventListener('click', () => {
    const p = findProduct(btn.dataset.id);
    if (!p) return;
    if (confirm(`Delete "${p.name}"? This can't be undone.`)) {
      const form = document.createElement('form');
      form.method = 'post';
      form.action = 'product-delete.php';
      form.innerHTML = `<input type="hidden" name="id" value="${btn.dataset.id}">`;
      document.body.appendChild(form);
      form.submit();
    }
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
