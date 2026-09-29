<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['hero_photo'])) {
    header('Location: settings.php');
    exit;
}

$file = $_FILES['hero_photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Upload failed. Please try again.'];
    header('Location: settings.php');
    exit;
}

$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
$mime = mime_content_type($file['tmp_name']);

if (!isset($allowed[$mime])) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Please upload a JPG or PNG image.'];
    header('Location: settings.php');
    exit;
}

if ($file['size'] > 8 * 1024 * 1024) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Image is too large. Please keep it under 8 MB.'];
    header('Location: settings.php');
    exit;
}

$ext = $allowed[$mime];
$filename = 'hero-' . time() . '.' . $ext;
$destDir = __DIR__ . '/../assets/img/hero/';
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}
$destPath = $destDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save the uploaded file. Check folder permissions.'];
    header('Location: settings.php');
    exit;
}

$db = get_db();
$relativePath = 'assets/img/hero/' . $filename;
$stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('hero_photo_path', ?)
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
$stmt->execute([$relativePath]);

$_SESSION['flash'] = ['type' => 'success', 'message' => 'Hero photo updated.'];
header('Location: settings.php');
exit;
