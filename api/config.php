<?php
/**
 * Bedrock Lapidary: Configuration
 *
 * PLACEHOLDER VALUES: everything in this file needs to be replaced with the
 * real values from your Hostinger hPanel once the domain and database exist.
 * See SETUP_HOSTINGER.md for exactly where each of these comes from.
 *
 * This file is included by every PHP script that needs the database or
 * email sending. Nothing here is executed on its own.
 */

// ---------------------------------------------------------------------------
// Database (Hostinger hPanel > Databases > MySQL Databases)
// ---------------------------------------------------------------------------
define('DB_HOST', 'localhost');              // Hostinger MySQL is almost always 'localhost'
define('DB_NAME', 'u861850543_lapidary');
define('DB_USER', 'u861850543_lapidary');
define('DB_PASS', 'Database.7088');

// ---------------------------------------------------------------------------
// Outgoing email (Hostinger hPanel > Emails > Email Accounts)
// Create a real mailbox on your domain (e.g. orders@yourdomain.com) once the
// domain is live, then fill these in. Until then, emails will fail silently
// and every order/promo action is still saved to the database either way.
// ---------------------------------------------------------------------------
define('SMTP_HOST', 'smtp.hostinger.com');    // Hostinger's standard SMTP host
define('SMTP_PORT', 465);                     // 465 = SSL, 587 = TLS
define('SMTP_USER', 'contact@bedrocklapidary.com');
define('SMTP_PASS', 'Mailbox.7088');
// MAIL_FROM_NAME and where order notifications land now come from
// site_settings (business_name, order_notification_email), see get_setting()

// ---------------------------------------------------------------------------
// Site
// Business name, contact email, and order-notification email now live in
// the site_settings table (editable from admin/settings.php) instead of
// being hardcoded here. SITE_URL stays a code-level setting since it's about
// how the server/mailer function, not business info.
// ---------------------------------------------------------------------------
define('SITE_URL', 'https://bedrocklapidary.com'); // no trailing slash

// ---------------------------------------------------------------------------
// Admin session
// ---------------------------------------------------------------------------
define('ADMIN_SESSION_NAME', 'bedrock_admin_session');

// ---------------------------------------------------------------------------
// PDO connection helper, used by every API and admin script
// ---------------------------------------------------------------------------
function get_db() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Database connection failed. Check config.php credentials.']);
            exit;
        }
    }
    return $pdo;
}

// ---------------------------------------------------------------------------
// Site settings helper: reads the site_settings table (business name,
// email, phone, etc). Cached per-request so repeated calls don't re-query.
// ---------------------------------------------------------------------------
function get_all_settings() {
    static $settings = null;
    if ($settings === null) {
        $db = get_db();
        $rows = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

function get_setting($key, $default = '') {
    $settings = get_all_settings();
    return $settings[$key] ?? $default;
}

// ---------------------------------------------------------------------------
// JSON response helper
// ---------------------------------------------------------------------------
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ---------------------------------------------------------------------------
// Simple header setup for API endpoints called via fetch()
// ---------------------------------------------------------------------------
function api_headers() {
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
}
