<?php
require_once __DIR__ . '/auth.php';
$activePage = 'settings';

$db = get_db();
$rows = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
$s = [];
foreach ($rows as $r) { $s[$r['setting_key']] = $r['setting_value']; }
$banners = $db->query("SELECT * FROM banner_messages ORDER BY sort_order, id")->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Site Settings - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790088838">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Site Settings</h1>
  <p class="subtitle">Sitewide info shown across the site, phone number, contact email, hours, and the rest. Changes here go live immediately, no redeploy needed.</p>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <form method="post" action="settings-save.php">
    <div class="panel">
      <h2>Business Info</h2>
      <div class="form-row">
        <div class="field">
          <label>Business name</label>
          <input type="text" name="business_name" value="<?= htmlspecialchars($s['business_name'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Phone number</label>
          <input type="text" name="phone_number" value="<?= htmlspecialchars($s['phone_number'] ?? '') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>Contact email (shown on site)</label>
          <input type="email" name="contact_email" value="<?= htmlspecialchars($s['contact_email'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Business hours</label>
          <input type="text" name="business_hours" value="<?= htmlspecialchars($s['business_hours'] ?? '') ?>">
        </div>
      </div>
      <p class="form-note" style="font-size:12.5px; color:var(--ink-soft); margin-top:-8px;">
        Note: there is intentionally no business address field. None has ever been provided, so none is shown, rather than a placeholder.
      </p>
    </div>

    <div class="panel">
      <h2>Orders</h2>
      <div class="form-row full">
        <div class="field">
          <label>Order notification email (where new orders get sent)</label>
          <input type="email" name="order_notification_email" value="<?= htmlspecialchars($s['order_notification_email'] ?? '') ?>">
        </div>
      </div>
    </div>

    <div class="panel">
      <h2>Top Banner Messages</h2>
      <p class="panel-note">The rotating message(s) shown at the very top of every page. Add more than one and they rotate automatically.</p>
      <div class="table-scroll">
      <table style="margin-bottom:16px;">
        <thead><tr><th>Message</th><th>Seconds shown</th><th>Active</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($banners as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['message']) ?></td>
            <td><?= (int)$b['display_seconds'] ?>s</td>
            <td>
              <form method="post" action="banner-message-toggle.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <input type="hidden" name="active" value="<?= $b['active'] ? 0 : 1 ?>">
                <button type="submit" class="btn btn-sm <?= $b['active'] ? 'btn-outline' : '' ?>"><?= $b['active'] ? 'On' : 'Off' ?></button>
              </form>
            </td>
            <td>
              <button class="btn btn-outline btn-sm btn-edit-banner" type="button"
                data-id="<?= $b['id'] ?>" data-message="<?= htmlspecialchars($b['message']) ?>" data-seconds="<?= $b['display_seconds'] ?>">Edit</button>
              <form method="post" action="banner-message-delete.php" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($banners)): ?>
            <tr><td colspan="4" style="text-align:center; color:var(--ink-soft);">No banner messages yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
      </div>
      <button type="button" class="btn btn-sm" id="btn-add-banner">+ Add Message</button>
    </div>

    <div class="panel">
      <h2>Shipping</h2>
      <div class="form-row full">
        <div class="field">
          <label>Free shipping on orders at or above this amount ($)</label>
          <input type="number" step="0.01" min="0" name="free_shipping_threshold" value="<?= htmlspecialchars($s['free_shipping_threshold'] ?? '299') ?>">
          <div class="field-help">Shown to customers in the cart and at checkout. Below this amount, shipping is calculated after the order request comes in.</div>
        </div>
      </div>
    </div>

    <div class="panel">
      <h2>Order Emails</h2>
      <div class="form-row full">
        <div class="field">
          <label>Footer note added to every order confirmation email</label>
          <textarea name="order_email_footer_note" rows="2"><?= htmlspecialchars($s['order_email_footer_note'] ?? '') ?></textarea>
          <div class="field-help">Optional. Example: "Thanks for supporting a small shop!"</div>
        </div>
      </div>
    </div>

    <div class="panel">
      <h2>Out-of-Stock Products</h2>
      <div class="form-row full">
        <div class="field">
          <label>When a product is out of stock</label>
          <select name="out_of_stock_behavior">
            <option value="show_grayed_out" <?= ($s['out_of_stock_behavior'] ?? '') === 'show_grayed_out' ? 'selected' : '' ?>>Show it, grayed out</option>
            <option value="hide_completely" <?= ($s['out_of_stock_behavior'] ?? '') === 'hide_completely' ? 'selected' : '' ?>>Hide it from browsing entirely</option>
          </select>
        </div>
      </div>
    </div>

    <div class="panel">
      <h2>First-Order Discount Popup</h2>
      <div class="form-row">
        <div class="field">
          <label>Delay before popup appears (seconds)</label>
          <input type="number" name="popup_delay_seconds" min="0" max="60" value="<?= htmlspecialchars($s['popup_delay_seconds'] ?? '4') ?>">
        </div>
        <div class="field">
          <label>Frequency</label>
          <select name="popup_frequency">
            <option value="once_per_session" <?= ($s['popup_frequency'] ?? '') === 'once_per_session' ? 'selected' : '' ?>>Once per browser session</option>
            <option value="every_visit" <?= ($s['popup_frequency'] ?? '') === 'every_visit' ? 'selected' : '' ?>>Every page load</option>
            <option value="once_ever" <?= ($s['popup_frequency'] ?? '') === 'once_ever' ? 'selected' : '' ?>>Once ever (per browser)</option>
          </select>
        </div>
      </div>
      <p class="form-note" style="font-size:12.5px; color:var(--ink-soft);">
        The popup's headline percentage always matches whichever promo code is set as the active "First-order popup" promo in Promo Codes, it is not set here.
      </p>
    </div>

    <div class="panel">
      <h2>Social Links</h2>
      <p class="form-note" style="font-size:12.5px; color:var(--ink-soft); margin-top:-4px;">Leave blank to hide. None are shown until filled in.</p>
      <div class="form-row">
        <div class="field"><label>Facebook URL</label><input type="url" name="social_facebook" value="<?= htmlspecialchars($s['social_facebook'] ?? '') ?>"></div>
        <div class="field"><label>Instagram URL</label><input type="url" name="social_instagram" value="<?= htmlspecialchars($s['social_instagram'] ?? '') ?>"></div>
      </div>
      <div class="form-row">
        <div class="field"><label>YouTube URL</label><input type="url" name="social_youtube" value="<?= htmlspecialchars($s['social_youtube'] ?? '') ?>"></div>
      </div>
    </div>

    <button type="submit" class="btn">Save Settings</button>
  </form>

  <div class="panel">
    <h2>Homepage Hero Photo</h2>
    <p class="panel-note">Current photo:</p>
    <img src="../<?= htmlspecialchars($s['hero_photo_path'] ?? 'assets/img/hero/hero-stones.jpg') ?>" alt="Current hero photo" style="max-width:280px; display:block; margin-bottom:16px; border:1px solid var(--line); border-radius:6px;">
    <form method="post" action="hero-photo-save.php" enctype="multipart/form-data">
      <div class="field" style="margin-bottom:14px;">
        <label>Replace with a new photo (JPG or PNG)</label>
        <input type="file" name="hero_photo" accept="image/jpeg,image/png" required>
      </div>
      <button type="submit" class="btn">Upload New Photo</button>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="banner-modal-backdrop">
  <div class="modal">
    <h2 id="banner-modal-title" style="margin-top:0;">Add Banner Message</h2>
    <form method="post" action="banner-message-save.php">
      <input type="hidden" name="id" id="bf-id">
      <div class="form-row full">
        <div class="field"><label>Message</label><input type="text" name="message" id="bf-message" required></div>
      </div>
      <div class="form-row full">
        <div class="field"><label>Seconds shown before rotating to the next</label><input type="number" name="display_seconds" id="bf-seconds" min="2" max="30" value="5"></div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" id="banner-btn-cancel">Cancel</button>
        <button type="submit" class="btn">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('btn-add-banner').addEventListener('click', () => {
  document.getElementById('banner-modal-title').textContent = 'Add Banner Message';
  document.getElementById('bf-id').value = '';
  document.getElementById('bf-message').value = '';
  document.getElementById('bf-seconds').value = '5';
  document.getElementById('banner-modal-backdrop').classList.add('open');
});
document.querySelectorAll('.btn-edit-banner').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('banner-modal-title').textContent = 'Edit Banner Message';
    document.getElementById('bf-id').value = btn.dataset.id;
    document.getElementById('bf-message').value = btn.dataset.message;
    document.getElementById('bf-seconds').value = btn.dataset.seconds;
    document.getElementById('banner-modal-backdrop').classList.add('open');
  });
});
document.getElementById('banner-btn-cancel').addEventListener('click', () => {
  document.getElementById('banner-modal-backdrop').classList.remove('open');
});
document.getElementById('banner-modal-backdrop').addEventListener('click', (e) => {
  if (e.target.id === 'banner-modal-backdrop') e.currentTarget.classList.remove('open');
});
</script>
</body>
</html>
