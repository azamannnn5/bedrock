<?php
/**
 * Bedrock Lapidary: Email templates
 *
 * Plain-table HTML matching the site's white/green look, kept deliberately
 * simple (inline styles, table layout) since that's what actually renders
 * consistently across Gmail, Outlook, and phone mail apps.
 */

function email_shell($title, $bodyHtml) {
    return '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title></head>
<body style="margin:0; padding:0; background:#EDF6EF; font-family: Arial, Helvetica, sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#EDF6EF; padding:32px 0;">
    <tr><td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#FFFFFF; border:1px solid #E2E5E2;">
        <tr>
          <td style="background:#2F5E41; padding:20px 32px;">
            <span style="color:#FFFFFF; font-size:20px; font-weight:bold;">Bedrock Lapidary</span>
          </td>
        </tr>
        <tr>
          <td style="padding:32px;">
            ' . $bodyHtml . '
          </td>
        </tr>
        <tr>
          <td style="background:#1E211F; padding:20px 32px; color:rgba(255,255,255,.7); font-size:12px;">
            Bedrock Lapidary &middot; Equipment and supplies for cutting, grinding, and polishing stone and glass.
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';
}

function email_button($url, $label) {
    return '<a href="' . htmlspecialchars($url) . '" style="display:inline-block; background:#3F7A55; color:#FFFFFF; text-decoration:none; padding:12px 24px; font-weight:bold; font-size:14px;">' . htmlspecialchars($label) . '</a>';
}

/**
 * Order confirmation. Sent to BOTH the customer and the store owner.
 * $order is an associative array matching the orders table columns.
 * $items is an array of order_items rows.
 */
function order_confirmation_email($order, $items, $isOwnerCopy = false) {
    $rows = '';
    foreach ($items as $item) {
        $lineTotal = $item['unit_price'] * $item['quantity'];
        $rows .= '<tr>
            <td style="padding:10px 0; border-bottom:1px solid #E2E5E2; font-size:14px;">' . htmlspecialchars($item['product_name']) . ' &times; ' . (int)$item['quantity'] . '</td>
            <td style="padding:10px 0; border-bottom:1px solid #E2E5E2; font-size:14px; text-align:right;">$' . number_format($lineTotal, 2) . '</td>
        </tr>';
    }

    $discountRow = '';
    if ($order['discount_amount'] > 0) {
        $discountRow = '<tr>
            <td style="padding:6px 0; font-size:14px; color:#3F7A55;">Discount' . ($order['promo_code'] ? ' (' . htmlspecialchars($order['promo_code']) . ')' : '') . '</td>
            <td style="padding:6px 0; font-size:14px; text-align:right; color:#3F7A55;">-$' . number_format($order['discount_amount'], 2) . '</td>
        </tr>';
    }

    $heading = $isOwnerCopy
        ? '<h2 style="margin:0 0 8px; font-size:20px; color:#1E211F;">New Order Request</h2>'
        : '<h2 style="margin:0 0 8px; font-size:20px; color:#1E211F;">Thanks for your order request, ' . htmlspecialchars(explode(' ', $order['customer_name'])[0]) . '!</h2>';

    $intro = $isOwnerCopy
        ? '<p style="font-size:14px; color:#5C625E;">A new order request just came in through the site.</p>'
        : '<p style="font-size:14px; color:#5C625E;">We\'ve received your order request. Nothing has been charged yet, our team will follow up shortly to confirm availability, shipping, and payment.</p>';

    $customerBlock = $isOwnerCopy ? '
        <h3 style="font-size:15px; margin:24px 0 8px;">Customer Details</h3>
        <p style="font-size:14px; color:#5C625E; margin:0 0 4px;">Name: ' . htmlspecialchars($order['customer_name']) . '</p>
        <p style="font-size:14px; color:#5C625E; margin:0 0 4px;">Phone: ' . htmlspecialchars($order['phone']) . '</p>
        <p style="font-size:14px; color:#5C625E; margin:0 0 4px;">Email: ' . htmlspecialchars($order['email']) . '</p>
        <p style="font-size:14px; color:#5C625E; margin:0 0 4px;">Address: ' . htmlspecialchars($order['address']) . ', ' . htmlspecialchars($order['city']) . ', ' . htmlspecialchars($order['state']) . ' ' . htmlspecialchars($order['zip']) . '</p>
        <p style="font-size:14px; color:#5C625E; margin:0 0 4px;">Payment method: ' . htmlspecialchars($order['payment_method'] ?: 'Not specified') . '</p>
        ' . ($order['notes'] ? '<p style="font-size:14px; color:#5C625E; margin:0 0 4px;">Notes: ' . nl2br(htmlspecialchars($order['notes'])) . '</p>' : '') . '
    ' : '';

    $body = $heading . $intro . '
        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:16px;">
            ' . $rows . '
            <tr>
                <td style="padding:10px 0; font-size:14px;">Subtotal</td>
                <td style="padding:10px 0; font-size:14px; text-align:right;">$' . number_format($order['subtotal'], 2) . '</td>
            </tr>
            ' . $discountRow . '
            <tr>
                <td style="padding:10px 0; font-size:16px; font-weight:bold; border-top:2px solid #1E211F;">Total</td>
                <td style="padding:10px 0; font-size:16px; font-weight:bold; text-align:right; border-top:2px solid #1E211F;">$' . number_format($order['total'], 2) . '</td>
            </tr>
        </table>
        ' . $customerBlock . '
        <p style="font-size:12px; color:#5C625E; margin-top:24px;">Order #' . (int)$order['id'] . ' &middot; ' . htmlspecialchars($order['created_at']) . '</p>
        ' . (!$isOwnerCopy && ($footerNote = trim(get_setting('order_email_footer_note', ''))) !== '' ? '<p style="font-size:13px; color:#5C625E; margin-top:12px; padding-top:12px; border-top:1px solid #E2E5E2;">' . nl2br(htmlspecialchars($footerNote)) . '</p>' : '') . '
    ';

    return email_shell('Order Confirmation', $body);
}

/**
 * Promo code delivery. Sent when someone submits the "10% off first
 * order" popup, or requests a code directly.
 */
function promo_code_email($firstName, $code, $discountPct, $expiresAt) {
    $expiresLabel = date('F j, Y \a\t g:i A', strtotime($expiresAt));
    $greeting = $firstName ? 'Hi ' . htmlspecialchars($firstName) . ',' : 'Hi there,';

    $body = '
        <h2 style="margin:0 0 8px; font-size:20px; color:#1E211F;">Here\'s your discount code</h2>
        <p style="font-size:14px; color:#5C625E;">' . $greeting . ' thanks for signing up. Use the code below to save on your first order:</p>
        <div style="background:#EDF6EF; border:1px dashed #3F7A55; padding:20px; text-align:center; margin:20px 0;">
            <span style="font-size:24px; font-weight:bold; letter-spacing:2px; color:#2F5E41;">' . htmlspecialchars($code) . '</span>
        </div>
        <p style="font-size:14px; color:#5C625E;">This code takes <strong>' . rtrim(rtrim(number_format($discountPct, 2), '0'), '.') . '% off</strong> your first order. Enter it at checkout, on most products.</p>
        <p style="font-size:13px; color:#5C625E;">Valid through ' . htmlspecialchars($expiresLabel) . '. One-time use per customer.</p>
    ';

    return email_shell('Your Discount Code', $body);
}
