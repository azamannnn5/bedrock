<?php
require_once __DIR__ . '/auth.php';
$activePage = 'notifications-orders';

$db = get_db();
$orders = $db->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Pull item counts per order in one query rather than N+1
$itemCounts = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $rows = $db->prepare("SELECT order_id, COUNT(*) AS n, SUM(quantity) AS qty FROM order_items WHERE order_id IN ($placeholders) GROUP BY order_id");
    $rows->execute($orderIds);
    foreach ($rows->fetchAll() as $r) {
        $itemCounts[$r['order_id']] = ['lines' => $r['n'], 'qty' => $r['qty']];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orders - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790088838">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Orders</h1>
  <p class="subtitle"><?= count($orders) ?> order request(s) received.</p>

  <div class="help-box">
    <strong>What this is</strong>
    Every order request submitted at checkout is saved here the moment it comes in, whether or not the confirmation email successfully sends. This is the reliable record of what's actually been ordered.
  </div>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <div class="toolbar">
    <input type="search" id="search" placeholder="Search by name, email, or order #...">
    <select id="status-filter">
      <option value="">All statuses</option>
      <option value="new">New</option>
      <option value="confirmed">Confirmed</option>
      <option value="cancelled">Cancelled</option>
    </select>
  </div>

  <div class="panel" style="padding:0;">
    <div class="table-scroll">
    <table>
      <thead><tr><th class="sortable" data-sort="date">Date <span class="sort-arrow">▾</span></th><th>Order #</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody id="order-rows">
        <?php foreach ($orders as $o):
          $counts = $itemCounts[$o['id']] ?? ['lines' => 0, 'qty' => 0];
        ?>
        <tr data-search="<?= htmlspecialchars(strtolower($o['customer_name'] . ' ' . $o['email'] . ' #' . $o['id'])) ?>" data-status="<?= htmlspecialchars($o['status']) ?>" data-date="<?= strtotime($o['created_at']) ?>">
          <td style="white-space:nowrap; font-size:12.5px; color:var(--ink-soft);"><?= date('M j, g:ia', strtotime($o['created_at'])) ?></td>
          <td>#<?= (int)$o['id'] ?></td>
          <td><?= htmlspecialchars($o['customer_name']) ?><div style="color:var(--ink-soft); font-size:12px;"><?= htmlspecialchars($o['email']) ?> &middot; <?= htmlspecialchars($o['phone']) ?></div></td>
          <td><?= (int)$counts['qty'] ?> item(s)</td>
          <td>$<?= number_format($o['total'], 2) ?><?= $o['discount_amount'] > 0 ? '<div style="font-size:11.5px; color:var(--green-d);">promo: ' . htmlspecialchars($o['promo_code']) . '</div>' : '' ?></td>
          <td>
            <form method="post" action="order-status-update.php" style="display:inline;">
              <input type="hidden" name="id" value="<?= $o['id'] ?>">
              <select name="status" onchange="this.form.submit()" style="font-size:12.5px; padding:4px 6px;">
                <option value="new" <?= $o['status'] === 'new' ? 'selected' : '' ?>>New</option>
                <option value="confirmed" <?= $o['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
              </select>
            </form>
          </td>
          <td><button class="btn btn-outline btn-sm btn-view" type="button" data-id="<?= $o['id'] ?>">View</button></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
          <tr><td colspan="7" style="text-align:center; color:var(--ink-soft); padding:24px;">No orders yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="modal-backdrop">
  <div class="modal" id="order-detail-modal">
  </div>
</div>

<script>
const ORDERS_DATA = <?= json_encode($orders) ?>;

document.getElementById('search').addEventListener('input', filterRows);
document.getElementById('status-filter').addEventListener('change', filterRows);

function filterRows(){
  const q = document.getElementById('search').value.toLowerCase();
  const status = document.getElementById('status-filter').value;
  document.querySelectorAll('#order-rows tr').forEach(row => {
    if (!row.dataset.search) return;
    const matchesQ = !q || row.dataset.search.includes(q);
    const matchesStatus = !status || row.dataset.status === status;
    row.style.display = (matchesQ && matchesStatus) ? '' : 'none';
  });
}

let sortAsc = false;
document.querySelectorAll('th.sortable').forEach(th => {
  th.addEventListener('click', () => {
    const rows = Array.from(document.querySelectorAll('#order-rows tr')).filter(r => r.dataset.date);
    rows.sort((a, b) => sortAsc ? a.dataset.date - b.dataset.date : b.dataset.date - a.dataset.date);
    sortAsc = !sortAsc;
    const tbody = document.getElementById('order-rows');
    rows.forEach(r => tbody.appendChild(r));
  });
});

document.querySelectorAll('.btn-view').forEach(btn => {
  btn.addEventListener('click', () => {
    const o = ORDERS_DATA.find(x => x.id == btn.dataset.id);
    if (!o) return;
    document.getElementById('order-detail-modal').innerHTML = `
      <h2 style="margin-top:0;">Order #${o.id}</h2>
      <p style="font-size:14px; color:var(--ink-soft);">${o.created_at}</p>
      <p style="font-size:14px;"><strong>${o.customer_name}</strong><br>${o.email} &middot; ${o.phone}<br>${o.address}, ${o.city}, ${o.state} ${o.zip}</p>
      <p style="font-size:14px;">Payment method: ${o.payment_method || 'Not specified'}</p>
      ${o.notes ? `<p style="font-size:14px;">Notes: ${o.notes}</p>` : ''}
      <p style="font-size:14px;">Subtotal: $${parseFloat(o.subtotal).toFixed(2)}<br>${o.discount_amount > 0 ? `Discount (${o.promo_code}): -$${parseFloat(o.discount_amount).toFixed(2)}<br>` : ''}<strong>Total: $${parseFloat(o.total).toFixed(2)}</strong></p>
      <div class="modal-actions"><button type="button" class="btn btn-outline" id="btn-close-detail">Close</button></div>
    `;
    document.getElementById('modal-backdrop').classList.add('open');
    document.getElementById('btn-close-detail').addEventListener('click', () => {
      document.getElementById('modal-backdrop').classList.remove('open');
    });
  });
});
document.getElementById('modal-backdrop').addEventListener('click', (e) => {
  if (e.target.id === 'modal-backdrop') e.currentTarget.classList.remove('open');
});
</script>
</body>
</html>
