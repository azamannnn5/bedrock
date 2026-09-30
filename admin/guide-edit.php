<?php
require_once __DIR__ . '/auth.php';
$activePage = 'guides';

$db = get_db();
$categories = $db->query("SELECT slug, label FROM categories ORDER BY label")->fetchAll();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$guide = null;
if ($id) {
    $stmt = $db->prepare("SELECT * FROM guide_posts WHERE id = ?");
    $stmt->execute([$id]);
    $guide = $stmt->fetch();
    if (!$guide) {
        header('Location: guides.php');
        exit;
    }
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$g = $guide ?: [
    'id' => '', 'slug' => '', 'title' => '', 'hero_tag' => 'Buying Guide', 'excerpt' => '',
    'meta_description' => '', 'body' => '', 'category_link' => '', 'featured_image' => '', 'published' => 1,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $guide ? 'Edit' : 'Add' ?> Guide - Bedrock Lapidary Admin</title>
<link rel="stylesheet" href="admin-style.css?v=1790797840">
</head>
<body>
<?php include 'header.php'; ?>

<div class="admin-wrap">
  <h1><?= $guide ? 'Edit Guide' : 'Add Guide' ?></h1>
  <p class="subtitle"><a href="guides.php">&larr; Back to all guides</a></p>

  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <form method="post" action="guide-save.php">
    <input type="hidden" name="id" value="<?= htmlspecialchars((string)$g['id']) ?>">

    <div class="panel">
      <h2>Basics</h2>
      <div class="form-row full">
        <div class="field"><label>Title</label><input type="text" name="title" id="f-title" value="<?= htmlspecialchars($g['title']) ?>" required></div>
      </div>
      <div class="form-row">
        <div class="field">
          <label>URL slug</label>
          <input type="text" name="slug" id="f-slug" value="<?= htmlspecialchars($g['slug']) ?>" pattern="[a-z0-9-]+" required>
          <div class="field-help">Lowercase letters, numbers, hyphens only. The guide's URL will be /guides/&lt;this&gt;. Changing it breaks any links already pointing to the old one.</div>
        </div>
        <div class="field">
          <label>Badge / hero tag</label>
          <input type="text" name="hero_tag" value="<?= htmlspecialchars($g['hero_tag']) ?>" placeholder="Buying Guide">
        </div>
      </div>
      <div class="form-row full">
        <div class="field">
          <label><input type="checkbox" name="published" value="1" <?= $g['published'] ? 'checked' : '' ?>> Published (visible on the live site)</label>
        </div>
      </div>
    </div>

    <div class="panel">
      <h2>Summary</h2>
      <div class="form-row full">
        <div class="field">
          <label>Excerpt</label>
          <textarea name="excerpt" rows="2" required><?= htmlspecialchars($g['excerpt']) ?></textarea>
          <div class="field-help">Shown on the guide card in the guides list and under the title on the guide page. 1-2 sentences.</div>
        </div>
      </div>
      <div class="form-row full">
        <div class="field">
          <label>Meta description (optional)</label>
          <input type="text" name="meta_description" value="<?= htmlspecialchars($g['meta_description'] ?? '') ?>" maxlength="300">
          <div class="field-help">Used for search-engine results. Leave blank to reuse the excerpt.</div>
        </div>
      </div>
    </div>

    <div class="panel">
      <h2>Content</h2>
      <div class="form-row full">
        <div class="field">
          <label>Body (HTML)</label>
          <textarea name="body" rows="20" style="font-family: monospace; font-size: 14px;" required><?= htmlspecialchars($g['body']) ?></textarea>
          <div class="field-help">
            Written as HTML, same as the rest of the site. Use <code>&lt;h2 id="some-id"&gt;Heading&lt;/h2&gt;</code> for section headings — each one automatically becomes an entry in the "On this page" sidebar. Wrap paragraphs in <code>&lt;p&gt;</code>, lists in <code>&lt;ul&gt;&lt;li&gt;</code>, and use <code>&lt;div class="callout"&gt;&lt;strong&gt;Label&lt;/strong&gt;Text&lt;/div&gt;</code> for a highlighted tip box.
          </div>
        </div>
      </div>
    </div>

    <div class="panel">
      <h2>Extras</h2>
      <div class="form-row">
        <div class="field">
          <label>Link to category (optional)</label>
          <select name="category_link">
            <option value="">None</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= htmlspecialchars($c['slug']) ?>" <?= $g['category_link'] === $c['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($c['label']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="field-help">Adds a "Shop &lt;category&gt;" button to the sidebar.</div>
        </div>
        <div class="field">
          <label>Featured image path (optional)</label>
          <input type="text" name="featured_image" value="<?= htmlspecialchars($g['featured_image'] ?? '') ?>" placeholder="assets/img/hero/hero-stones.jpg">
          <div class="field-help">Relative path from the site root. Leave blank to use the default hero photo.</div>
        </div>
      </div>
    </div>

    <button type="submit" class="btn">Save Guide</button>
  </form>
</div>

<script>
// Auto-fill the slug from the title, but only while the user hasn't
// touched the slug field by hand (and only for a brand-new guide).
const isNew = <?= $guide ? 'false' : 'true' ?>;
let slugTouched = !isNew;
document.getElementById('f-slug').addEventListener('input', () => { slugTouched = true; });
document.getElementById('f-title').addEventListener('input', (e) => {
  if (slugTouched) return;
  document.getElementById('f-slug').value = e.target.value
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
});
</script>
</body>
</html>
