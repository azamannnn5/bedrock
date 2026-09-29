<?php
/**
 * GET /api/settings.php
 * Returns the public-facing site settings (business name, contact email,
 * phone, hours, etc.) as JSON, so the frontend can display them without
 * having them hardcoded into HTML. Admin-editable via admin/settings.php.
 *
 * Deliberately excludes anything sensitive (SMTP credentials, DB info stay
 * in config.php and are never exposed here).
 */

require_once __DIR__ . '/config.php';
api_headers();

$settings = get_all_settings();
$db = get_db();
$banners = $db->query("SELECT message, display_seconds FROM banner_messages WHERE active = 1 ORDER BY sort_order, id")->fetchAll();

json_response([
    'businessName'            => $settings['business_name'] ?? '',
    'contactEmail'            => $settings['contact_email'] ?? '',
    'phoneNumber'             => $settings['phone_number'] ?? '',
    'businessHours'           => $settings['business_hours'] ?? '',
    'bannerMessages'          => array_map(fn($b) => ['message' => $b['message'], 'seconds' => (int)$b['display_seconds']], $banners),
    'popupDelaySeconds'       => (int)($settings['popup_delay_seconds'] ?? 4),
    'popupFrequency'          => $settings['popup_frequency'] ?? 'once_per_session',
    'heroPhotoPath'           => $settings['hero_photo_path'] ?? 'assets/img/hero/hero-stones.jpg',
    'freeShippingThreshold'   => (float)($settings['free_shipping_threshold'] ?? 175),
    'social' => [
        'facebook'  => $settings['social_facebook'] ?? '',
        'instagram' => $settings['social_instagram'] ?? '',
        'youtube'   => $settings['social_youtube'] ?? '',
    ],
]);
