<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit;
}

$fields = [
    'business_name', 'phone_number', 'contact_email', 'business_hours',
    'order_notification_email', 'order_email_footer_note', 'out_of_stock_behavior',
    'free_shipping_threshold',
    'popup_delay_seconds', 'popup_frequency',
    'social_facebook', 'social_instagram', 'social_youtube',
];

$db = get_db();
$stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

try {
    foreach ($fields as $field) {
        $value = trim($_POST[$field] ?? '');
        $stmt->execute([$field, $value]);
    }
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Settings saved.'];
} catch (Exception $e) {
    error_log('Settings save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save settings.'];
}

header('Location: settings.php');
exit;
