<?php
/**
 * Bedrock Lapidary: Minimal SMTP mailer
 *
 * Plain-PHP SMTP client with no external dependencies, so it works on basic
 * Hostinger shared hosting plans where Composer/PHPMailer aren't available.
 * If your Hostinger plan does have Composer access, swapping this for
 * PHPMailer or Symfony Mailer is a straightforward upgrade, this exists so
 * the site works correctly either way, on the very first upload.
 *
 * Usage:
 *   $ok = send_email('customer@example.com', 'Your order', $htmlBody);
 *
 * Returns true/false. Failures are logged with error_log() rather than
 * thrown, so a broken mail config never crashes an order submission, the
 * order is still saved to the database even if the email can't go out.
 */

require_once __DIR__ . '/config.php';

function send_email($toEmail, $subject, $htmlBody, $replyTo = null) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $user = SMTP_USER;
    $pass = SMTP_PASS;
    $fromName = get_setting('business_name', 'Bedrock Lapidary');

    $transport = ($port == 465) ? "ssl://$host" : $host;

    $smtp = @fsockopen($transport, $port, $errno, $errstr, 15);
    if (!$smtp) {
        error_log("Mailer: could not connect to $host:$port ($errno $errstr)");
        return false;
    }

    stream_set_timeout($smtp, 15);

    $read = function() use ($smtp) {
        $data = '';
        while ($line = fgets($smtp, 515)) {
            $data .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $data;
    };

    $write = function($cmd) use ($smtp) {
        fwrite($smtp, $cmd . "\r\n");
    };

    $read(); // greeting

    $write("EHLO " . parse_url(SITE_URL, PHP_URL_HOST));
    $read();

    if ($port == 587) {
        $write("STARTTLS");
        $read();
        stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $write("EHLO " . parse_url(SITE_URL, PHP_URL_HOST));
        $read();
    }

    $write("AUTH LOGIN");
    $read();
    $write(base64_encode($user));
    $read();
    $write(base64_encode($pass));
    $authResp = $read();

    if (strpos($authResp, '235') === false) {
        error_log("Mailer: SMTP auth failed: $authResp");
        fclose($smtp);
        return false;
    }

    $write("MAIL FROM:<$user>");
    $read();
    $write("RCPT TO:<$toEmail>");
    $read();
    $write("DATA");
    $read();

    $boundary = md5(uniqid());
    $headers = [];
    $headers[] = "From: $fromName <$user>";
    $headers[] = "To: <$toEmail>";
    if ($replyTo) $headers[] = "Reply-To: <$replyTo>";
    $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/html; charset=UTF-8";
    $headers[] = "Content-Transfer-Encoding: 8bit";

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
    $write($message);
    $sendResp = $read();

    $write("QUIT");
    fclose($smtp);

    if (strpos($sendResp, '250') === false) {
        error_log("Mailer: send failed: $sendResp");
        return false;
    }
    return true;
}
