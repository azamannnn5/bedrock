<?php
/**
 * GET /api/payment-methods.php
 * Returns the active payment methods for the checkout dropdown, in order.
 */

require_once __DIR__ . '/config.php';
api_headers();

$db = get_db();
$methods = $db->query("SELECT name FROM payment_methods WHERE active = 1 ORDER BY sort_order, name")->fetchAll();

json_response([
    'methods' => array_column($methods, 'name'),
]);
