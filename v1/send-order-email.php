<?php
// API/v1/send-order-email.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$orderId = isset($_POST['order_id']) ? mysqli_real_escape_string($con, $_POST['order_id']) : '';
$type = isset($_POST['type']) ? $_POST['type'] : 'success';

if (empty($orderId)) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit;
}

// Fetch Order
$orderQuery = mysqli_query($con, "SELECT * FROM orders WHERE id = '$orderId'");
if (!$orderQuery || mysqli_num_rows($orderQuery) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
    exit;
}
$order = mysqli_fetch_assoc($orderQuery);

// Fetch SMTP Config
$smtpResult = mysqli_query($con, "SELECT * FROM smtp_configs WHERE is_active=1 LIMIT 1");
if (!$smtpResult || mysqli_num_rows($smtpResult) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'No active SMTP configuration found']);
    exit;
}
$smtpConfig = mysqli_fetch_assoc($smtpResult);

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host = $smtpConfig['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtpConfig['smtp_user'];
    $mail->Password = $smtpConfig['smtp_pass'];
    $mail->SMTPSecure = ($smtpConfig['smtp_port'] == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpConfig['smtp_port'];
    $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
    $mail->addAddress($order['email'], $order['first_name'] . ' ' . $order['last_name']);
    
    // Always CC these emails for all orders
    $mail->addCC('rajanipodar@gmail.com', 'Rajani Podar');
    $mail->addCC('yosshita.neha@gmail.com', 'Yosshita Neha');
    $mail->addCC('vishwaaniruddh@gmail.com', 'Vishwa Aniruddh');
    
    $mail->isHTML(true);

    if ($type == 'success') {
        $mail->Subject = 'Order Confirmed #SR-' . ($orderId + 5000) . ' - Sri Shringarr';
        
        // Calculate subtotal
        $itemsSubtotal = 0;
        $itemsQuery = mysqli_query($con, "SELECT * FROM order_items WHERE order_id = '$orderId'");
        $itemsArray = [];
        while ($item = mysqli_fetch_assoc($itemsQuery)) {
            $itemsArray[] = $item;
            $itemsSubtotal += $item['total'];
        }
        
        $shippingCharge = isset($order['shipping_charge']) ? (float)$order['shipping_charge'] : 0;
        $discountAmount = isset($order['discount_amount']) ? (float)$order['discount_amount'] : 0;
        $depositAmount = isset($order['deposit_amount']) ? (float)$order['deposit_amount'] : 0;
        $couponCode = isset($order['coupon_code']) ? $order['coupon_code'] : null;
        
        // Start building email HTML
        require __DIR__ . '/email-templates/order-confirmation-template.php';
        $body = getOrderConfirmationEmail($order, $itemsArray, $itemsSubtotal, $shippingCharge, $discountAmount, $depositAmount, $couponCode, $orderId, $con);
        
    } else {
        $mail->Subject = 'Payment Failed - Order #SR-' . ($orderId + 5000);
        $body = getPaymentFailedEmail($order, $orderId);
    }

    $mail->Body = $body;
    $mail->send();
    error_log("Email sent successfully for Order #$orderId to {$order['email']}");
    echo json_encode(['status' => 'success', 'message' => 'Email sent successfully']);

} catch (\Exception $e) {
    error_log("Mailer Error for Order #$orderId: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}

function getPaymentFailedEmail($order, $orderId) {
    return '<!DOCTYPE html>
<html><body style="margin:0;padding:0;font-family:Arial,sans-serif;background:#ffebee;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;">
<tr><td style="background:#fff1f0;padding:30px;text-align:center;">
<h1 style="color:#d32f2f;margin:0;font-size:24px;">Payment Failed</h1>
</td></tr>
<tr><td style="padding:30px;">
<p>Hi ' . htmlspecialchars($order['first_name']) . ',</p>
<p>Unfortunately, the payment for your order #SR-' . ($orderId + 5000) . ' could not be processed.</p>
<p>If any amount was debited, it will be refunded within 5-7 business days.</p>
<p style="margin-top:25px;">Please try placing your order again.</p>
<p style="margin-top:30px;">Best regards,<br><strong>Team Sri Shringarr</strong></p>
</td></tr>
</table>
</td></tr>
</table>
</body></html>';
}
?>
