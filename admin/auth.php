<?php
/**
 * Include this at the very top of every admin page (except login.php).
 * Redirects to login.php if there's no valid admin session.
 */

require_once __DIR__ . '/../api/config.php';

session_name(ADMIN_SESSION_NAME);
session_start();

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
