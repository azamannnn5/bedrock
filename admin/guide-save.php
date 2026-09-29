<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: guides.php');
    exit;
}

$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$title = trim($_POST['title'] ?? '');
$slug = trim(strtolower($_POST['slug'] ?? ''));
$heroTag = trim($_POST['hero_tag'] ?? '') ?: 'Buying Guide';
$excerpt = trim($_POST['excerpt'] ?? '');
$metaDesc = trim($_POST['meta_description'] ?? '');
$body = $_POST['body'] ?? '';
$categoryLink = trim($_POST['category_link'] ?? '') ?: null;
$featuredImage = trim($_POST['featured_image'] ?? '') ?: null;
$published = !empty($_POST['published']) ? 1 : 0;

$redirectBack = 'guide-edit.php' . ($id ? '?id=' . $id : '');

if ($title === '' || $excerpt === '' || trim($body) === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Title, excerpt, and body are required.'];
    header('Location: ' . $redirectBack);
    exit;
}

if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Slug can only contain lowercase letters, numbers, and hyphens.'];
    header('Location: ' . $redirectBack);
    exit;
}

$db = get_db();

try {
    if ($id) {
        $stmt = $db->prepare("UPDATE guide_posts SET slug=?, title=?, hero_tag=?, excerpt=?, meta_description=?, body=?, category_link=?, featured_image=?, published=? WHERE id=?");
        $stmt->execute([$slug, $title, $heroTag, $excerpt, $metaDesc ?: null, $body, $categoryLink, $featuredImage, $published, $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Guide updated.'];
    } else {
        $stmt = $db->prepare("INSERT INTO guide_posts (slug, title, hero_tag, excerpt, meta_description, body, category_link, featured_image, published) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$slug, $title, $heroTag, $excerpt, $metaDesc ?: null, $body, $categoryLink, $featuredImage, $published]);
        $id = $db->lastInsertId();
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Guide created.'];
    }
} catch (Exception $e) {
    error_log('Guide save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save, that slug may already be in use.'];
    header('Location: ' . $redirectBack);
    exit;
}

header('Location: guide-edit.php?id=' . $id);
exit;
