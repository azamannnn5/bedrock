<?php
/**
 * POST /api/submit-contact.php
 * Body (JSON): { name, email, orderNumber, message }
 *
 * Saves the message to the database first, then tries to email it to the
 * store owner. Saving always happens even if the email fails, so nothing
 * submitted through the Contact page is ever lost, it just also shows up
 * in the admin Notifications area either way.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/email_templates.php';
api_headers();

$input = json_decode(file_get_contents('php://input'), true);

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$orderNumber = trim($input['orderNumber'] ?? '');
$message = trim($input['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    json_response(['success' => false, 'error' => 'Please fill in your name, email, and message.'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'error' => 'Please enter a valid email address.'], 400);
}

$db = get_db();

try {
    $stmt = $db->prepare("INSERT INTO contact_messages (name, email, order_number, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $orderNumber ?: null, $message]);
    $messageId = $db->lastInsertId();
} catch (Exception $e) {
    error_log('Contact message save failed: ' . $e->getMessage());
    json_response(['success' => false, 'error' => 'Something went wrong. Please try again.'], 500);
}

$bodyHtml = '
    <h2 style="margin:0 0 8px; font-size:20px; color:#1E211F;">New Contact Message</h2>
    <p style="font-size:14px; color:#5C625E; margin:0 0 4px;">From: ' . htmlspecialchars($name) . ' (' . htmlspecialchars($email) . ')</p>
    ' . ($orderNumber !== '' ? '<p style="font-size:14px; color:#5C625E; margin:0 0 4px;">Order #: ' . htmlspecialchars($orderNumber) . '</p>' : '') . '
    <p style="font-size:14px; color:#1E211F; margin-top:16px; white-space:pre-wrap;">' . htmlspecialchars($message) . '</p>
';
$emailSent = send_email(
    get_setting('order_notification_email', 'contact@bedrocklapidary.com'),
    "New Contact Message from $name",
    email_shell('New Contact Message', $bodyHtml),
    $email
);

json_response(['success' => true, 'id' => (int)$messageId, 'emailSent' => $emailSent]);
