<?php
// $activePage should be set by the including page.
$activePage = $activePage ?? '';

$navGroups = [
    'Catalog' => [
        'products'      => ['index.php', 'Products'],
        'vendors'       => ['vendors.php', 'Vendors'],
    ],
    'Merchandising' => [
        'featured'      => ['featured.php', 'Featured Products'],
        'best-sellers'  => ['best-sellers.php', 'Best Sellers'],
        'promos'        => ['promos.php', 'Promo Codes'],
    ],
    'Checkout' => [
        'payment-methods' => ['payment-methods.php', 'Payment Methods'],
        'free-shipping'   => ['free-shipping.php', 'Free Shipping'],
    ],
    'Notifications' => [
        'notifications-orders'  => ['notifications-orders.php', 'Orders'],
        'notifications-contact' => ['notifications-contact.php', 'Contact Messages'],
    ],
    'Content & Settings' => [
        'content'  => ['content.php', 'Site Content'],
        'settings' => ['settings.php', 'Site Settings'],
    ],
];
?>
<div class="admin-header">
  <div class="admin-header-top">
    <div class="brand">Bedrock <span>Lapidary</span> Admin</div>
    <div style="display:flex; align-items:center; gap:14px;">
      <a href="logout.php" class="logout">Log out (<?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?>)</a>
      <button class="admin-menu-toggle" id="admin-menu-toggle" type="button" aria-expanded="false" aria-controls="admin-nav"><span class="bars" id="admin-menu-icon">&#9776;</span> Menu</button>
    </div>
  </div>
  <nav class="admin-nav" id="admin-nav">
    <?php foreach ($navGroups as $groupLabel => $items): ?>
      <span class="admin-nav-group-label"><?= htmlspecialchars($groupLabel) ?></span>
      <?php foreach ($items as $key => [$href, $label]): ?>
        <a href="<?= $href ?>" class="<?= $activePage === $key ? 'active' : '' ?>"><?= htmlspecialchars($label) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <a href="logout.php" class="logout-link">Log out (<?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?>)</a>
  </nav>
</div>
<script>
  (function(){
    var btn = document.getElementById('admin-menu-toggle');
    var nav = document.getElementById('admin-nav');
    var icon = document.getElementById('admin-menu-icon');
    if (!btn || !nav) return;
    btn.addEventListener('click', function(){
      var open = nav.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      icon.innerHTML = open ? '&#10005;' : '&#9776;';
    });
  })();
</script>
