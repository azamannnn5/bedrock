<?php
require_once __DIR__ . '/auth.php';
$activePage = 'promos';

$db = get_db();
$promos = $db->query("SELECT * FROM promo_codes ORDER BY created_at DESC")->fetchAll();
$products = $db->query("SELECT id, name, vendor, category FROM products ORDER BY name")->fetchAll();

// Load which products each promo applies to
$promoProducts = [];
$rows = $db->query("SELECT promo_id, product_id FROM promo_code_products")->fetchAll();
foreach ($rows as $r) {
    $promoProducts[$r['promo_id']][] = $r['product_id'];
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function promo_status($promo) {
    if (!$promo['active']) return ['label' => 'Disabled', 'class' => 'badge-off'];
    $now = new DateTime();
    $starts = new DateTime($promo['starts_at']);
    $ends = new DateTime($promo['ends_at']);
    if ($now < $starts) return ['label' => 'Upcoming', 'class' => 'badge-upcoming'];
    if ($now > $ends) return ['label' => 'Expired', 'class' => 'badge-expired'];
    return ['label' => 'Active', 'class' => 'badge-active'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Promo Codes - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790088838">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Promo Codes</h1>
  <p class="subtitle">Powers both the "10% off your first order" popup and per-product promo banners. Each code has its own discount, time window, and scope.</p>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <div class="toolbar">
    <button class="btn" id="btn-add" type="button">+ New Promo Code</button>
  </div>

  <div class="panel" style="padding:0;">
    <table>
      <thead>
        <tr><th>Code</th><th>Discount</th><th>Scope</th><th>Window</th><th>Status</th><th>Used</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($promos as $promo):
          $status = promo_status($promo);
          $usedStmt = $db->prepare("SELECT COUNT(*) AS n FROM promo_redemptions WHERE promo_id = ?");
          $usedStmt->execute([$promo['id']]);
          $usedCount = $usedStmt->fetch()['n'];
          $scopedProducts = $promoProducts[$promo['id']] ?? [];
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($promo['code']) ?></strong><?php if ($promo['source'] === 'popup'): ?><div style="font-size:11px; color:var(--ink-soft);">popup code</div><?php endif; ?></td>
          <td><?= rtrim(rtrim(number_format($promo['discount_pct'], 2), '0'), '.') ?>%</td>
          <td><?= $promo['scope'] === 'sitewide' ? 'Sitewide' : count($scopedProducts) . ' product(s)' ?></td>
          <td style="font-size:12.5px;"><?= date('M j, g:ia', strtotime($promo['starts_at'])) ?> &rarr; <?= date('M j, g:ia', strtotime($promo['ends_at'])) ?></td>
          <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
          <td><?= (int)$usedCount ?><?= $promo['one_per_customer'] ? ' (1/customer)' : '' ?></td>
          <td>
            <button class="btn btn-outline btn-sm btn-edit-promo" type="button"
              data-promo='<?= htmlspecialchars(json_encode($promo)) ?>'
              data-products='<?= htmlspecialchars(json_encode($scopedProducts)) ?>'>Edit</button>
            <form method="post" action="promo-delete.php" style="display:inline;" onsubmit="return confirm('Delete this promo code?');">
              <input type="hidden" name="id" value="<?= $promo['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($promos)): ?>
          <tr><td colspan="7" style="text-align:center; color:var(--ink-soft); padding:24px;">No promo codes yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add/Edit modal -->
<div class="modal-backdrop" id="modal-backdrop">
  <div class="modal" style="max-width:720px;">
    <h2 id="modal-title" style="margin-top:0;">New Promo Code</h2>
    <form method="post" action="promo-save.php" id="promo-form">
      <input type="hidden" name="id" id="f-id">
      <div class="form-row">
        <div class="field"><label>Code</label><input type="text" name="code" id="f-code" placeholder="e.g. 2SAVE10" required style="text-transform:uppercase;"></div>
        <div class="field"><label>Discount percentage</label><input type="number" name="discount_pct" id="f-discount" min="1" max="100" step="0.01" required></div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Applies to</label>
          <select name="scope" id="f-scope">
            <option value="sitewide">Sitewide (any product)</option>
            <option value="product">Specific product(s)</option>
          </select>
        </div>
        <div class="field">
          <label>Where this code is used</label>
          <select name="source" id="f-source">
            <option value="manual">Manual / product banner</option>
            <option value="popup">First-order popup</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="field"><label>Starts at</label><input type="datetime-local" name="starts_at" id="f-starts" required></div>
        <div class="field"><label>Ends at</label><input type="datetime-local" name="ends_at" id="f-ends" required></div>
      </div>
      <div class="form-row">
        <div class="field"><label><input type="checkbox" name="one_per_customer" id="f-one-per" value="1" checked> Limit to one use per customer, per time period</label></div>
        <div class="field"><label><input type="checkbox" name="active" id="f-active" value="1" checked> Active</label></div>
      </div>

      <div id="product-picker-wrap" class="form-row full" style="display:none;">
        <div class="field">
          <label>Select product(s) this code applies to</label>
          <div class="toolbar" style="margin-bottom:10px;">
            <input type="search" id="product-search" placeholder="Search products...">
          </div>
          <div style="max-height:260px; overflow-y:auto; border:1px solid var(--line); padding:8px;">
            <?php foreach ($products as $p): ?>
              <label style="display:flex; align-items:center; gap:8px; padding:6px 4px; font-size:13.5px;" class="product-option" data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>">
                <input type="checkbox" name="product_ids[]" value="<?= htmlspecialchars($p['id']) ?>" class="product-checkbox">
                <?= htmlspecialchars($p['name']) ?> <span style="color:var(--ink-soft);">(<?= htmlspecialchars($p['vendor']) ?>)</span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-outline" id="btn-cancel">Cancel</button>
        <button type="submit" class="btn">Save Promo Code</button>
      </div>
    </form>
  </div>
</div>

<script>
function toggleScopeUI(){
  document.getElementById('product-picker-wrap').style.display =
    document.getElementById('f-scope').value === 'product' ? 'block' : 'none';
}
document.getElementById('f-scope').addEventListener('change', toggleScopeUI);

document.getElementById('product-search').addEventListener('input', (e) => {
  const q = e.target.value.toLowerCase();
  document.querySelectorAll('.product-option').forEach(opt => {
    opt.style.display = opt.dataset.name.includes(q) ? '' : 'none';
  });
});

function toLocalInputValue(mysqlDatetime){
  // mysqlDatetime: "YYYY-MM-DD HH:MM:SS" -> "YYYY-MM-DDTHH:MM" for datetime-local input
  return mysqlDatetime.replace(' ', 'T').slice(0, 16);
}

document.getElementById('btn-add').addEventListener('click', () => {
  document.getElementById('modal-title').textContent = 'New Promo Code';
  document.getElementById('promo-form').reset();
  document.getElementById('f-id').value = '';
  document.getElementById('f-code').disabled = false;
  document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = false);
  toggleScopeUI();
  document.getElementById('modal-backdrop').classList.add('open');
});

document.querySelectorAll('.btn-edit-promo').forEach(btn => {
  btn.addEventListener('click', () => {
    const promo = JSON.parse(btn.dataset.promo);
    const productIds = JSON.parse(btn.dataset.products);
    document.getElementById('modal-title').textContent = 'Edit Promo Code';
    document.getElementById('f-id').value = promo.id;
    document.getElementById('f-code').value = promo.code;
    document.getElementById('f-discount').value = promo.discount_pct;
    document.getElementById('f-scope').value = promo.scope;
    document.getElementById('f-source').value = promo.source;
    document.getElementById('f-starts').value = toLocalInputValue(promo.starts_at);
    document.getElementById('f-ends').value = toLocalInputValue(promo.ends_at);
    document.getElementById('f-one-per').checked = !!Number(promo.one_per_customer);
    document.getElementById('f-active').checked = !!Number(promo.active);
    document.querySelectorAll('.product-checkbox').forEach(cb => {
      cb.checked = productIds.includes(cb.value);
    });
    toggleScopeUI();
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
