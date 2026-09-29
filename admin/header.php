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
      <button class="admin-menu-toggle" id="admin-menu-toggle" type="button">Menu ▾</button>
    </div>
  </div>
  <nav class="admin-nav" id="admin-nav">
    <?php foreach ($navGroups as $groupLabel => $items): ?>
      <span class="admin-nav-group-label"><?= htmlspecialchars($groupLabel) ?></span>
      <?php foreach ($items as $key => [$href, $label]): ?>
        <a href="<?= $href ?>" class="<?= $activePage === $key ? 'active' : '' ?>"><?= htmlspecialchars($label) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
</div>
<script>
  document.getElementById('admin-menu-toggle')?.addEventListener('click', () => {
    document.getElementById('admin-nav').classList.toggle('open');
  });
</script>
