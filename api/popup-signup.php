<?php
/**
 * POST /api/popup-signup.php
 * Body (JSON): { "firstName": "...", "email": "..." }
 *
 * Looks up the currently active sitewide "popup" promo code (set up by the
 * admin in the Promo Codes section), saves the lead, and emails them the
 * code. If no popup promo is currently active, saves the lead anyway and
 * says so, rather than failing outright, the admin can always send a
 * follow-up once a promo exists.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/email_templates.php';
api_headers();

$input = json_decode(file_get_contents('php://input'), true);
$firstName = trim($input['firstName'] ?? '');
$email = trim($input['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'error' => 'Please enter a valid email address.'], 400);
}

$db = get_db();

$stmt = $db->prepare("SELECT * FROM promo_codes
    WHERE scope = 'sitewide' AND source = 'popup' AND active = 1
    AND NOW() BETWEEN starts_at AND ends_at
    ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$promo = $stmt->fetch();

$stmt = $db->prepare("INSERT INTO popup_leads (first_name, email, promo_id) VALUES (?, ?, ?)");
$stmt->execute([$firstName ?: null, $email, $promo ? $promo['id'] : null]);

if (!$promo) {
    // Lead is saved either way; nothing to email since no promo is configured yet.
    json_response(['success' => true, 'codeSent' => false, 'message' => 'Thanks, you are on the list!']);
}

$sent = send_email(
    $email,
    'Your Bedrock Lapidary Discount Code',
    promo_code_email($firstName, $promo['code'], $promo['discount_pct'], $promo['ends_at'])
);

json_response([
    'success' => true,
    'codeSent' => $sent,
    'message' => $sent ? 'Check your inbox for your code!' : 'You are signed up, but the email could not be sent. Please contact us for your code.',
]);
