<?php
require_once __DIR__ . '/auth.php';
$activePage = 'content';

$db = get_db();
$aboutContent = get_setting('about_page_content', '');
$categories = $db->query("SELECT slug, label FROM categories ORDER BY label")->fetchAll();
$catContentRows = $db->query("SELECT category_slug, description FROM category_content")->fetchAll();
$catContent = [];
foreach ($catContentRows as $row) { $catContent[$row['category_slug']] = $row['description']; }

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Site Content - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790088838">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1>Site Content</h1>
  <p class="subtitle">Editable text blocks shown around the site. Plain text only, no bold/italic/image formatting here.</p>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <form method="post" action="content-save.php">
    <div class="panel">
      <h2>About Page</h2>
      <p class="panel-note">Shown as the main text on the About Us page.</p>
      <div class="field">
        <textarea name="about_page_content" rows="8"><?= htmlspecialchars($aboutContent) ?></textarea>
      </div>
    </div>

    <div class="panel">
      <h2>Category Page Intro Text</h2>
      <p class="panel-note">A short blurb shown at the top of each category page. Leave blank to show nothing.</p>
      <?php foreach ($categories as $c): ?>
        <div class="form-row full">
          <div class="field">
            <label><?= htmlspecialchars($c['label']) ?></label>
            <textarea name="cat_<?= htmlspecialchars($c['slug']) ?>" rows="2"><?= htmlspecialchars($catContent[$c['slug']] ?? '') ?></textarea>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <button type="submit" class="btn">Save Content</button>
  </form>
</div>
</body>
</html>
