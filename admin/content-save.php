<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: content.php');
    exit;
}

$db = get_db();

try {
    $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute(['about_page_content', trim($_POST['about_page_content'] ?? '')]);

    $catStmt = $db->prepare("INSERT INTO category_content (category_slug, description) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE description = VALUES(description)");
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'cat_') === 0) {
            $slug = substr($key, 4);
            $catStmt->execute([$slug, trim($value)]);
        }
    }

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Content saved.'];
} catch (Exception $e) {
    error_log('Content save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save content.'];
}

header('Location: content.php');
exit;
