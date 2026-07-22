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
$type = isset($_POST['type']) ? $_POST['type'] : 'success'; // 'success' or 'failure'

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
    // Basic PHPMailer Settings
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8'; // Fix Rupee symbol encoding
    $mail->Host = $smtpConfig['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtpConfig['smtp_user'];
    $mail->Password = $smtpConfig['smtp_pass'];
    $mail->SMTPSecure = ($smtpConfig['smtp_port'] == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpConfig['smtp_port'];

    $mail->setFrom($smtpConfig['from_email'], $smtpConfig['from_name']);
    $mail->addAddress($order['email'], $order['first_name'] . ' ' . $order['last_name']);
    $mail->addCC('vishwaaniruddh@gmail.com');
    $mail->isHTML(true);

    if ($type == 'success') {
        $mail->Subject = 'Order Confirmed - Order #SR-' . ($orderId + 5000);
        
        // Calculate subtotal from items
        $itemsSubtotal = 0;
        $itemsQuery = mysqli_query($con, "SELECT * FROM order_items WHERE order_id = '$orderId'");
        $itemsArray = [];
        while ($item = mysqli_fetch_assoc($itemsQuery)) {
            $itemsArray[] = $item;
            $itemsSubtotal += $item['total'];
        }
        
        $shippingCharge = isset($order['shipping_charge']) ? (float)$order['shipping_charge'] : 0;
        $discountAmount = isset($order['discount_amount']) ? (float)$order['discount_amount'] : 0;
        $couponCode = isset($order['coupon_code']) ? $order['coupon_code'] : null;
        
        $body = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); padding: 40px 30px; text-align: center;">
                            <img src="https://srishringarr.com/logo-transparent.png" alt="Sri Shringarr" style="height: 50px; display: block; margin: 0 auto 20px auto;" />
                            <h1 style="color: #C9A96E; margin: 0; font-size: 28px; font-weight: 600; letter-spacing: 0.5px;">Order Confirmed</h1>
                            <p style="color: #999; margin: 10px 0 0 0; font-size: 14px;">Thank you for your order!</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="font-size: 16px; color: #333; margin: 0 0 10px 0;">Dear <strong>' . htmlspecialchars($order['first_name']) . '</strong>,</p>
                            <p style="font-size: 14px; color: #666; line-height: 1.6; margin: 0 0 30px 0;">
                                Thank you for choosing Sri Shringarr. We\'re delighted to confirm that your payment was successful and your order has been placed.
                            </p>
                            
                            <!-- Order Number Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #fafafa; border-radius: 8px; margin-bottom: 30px; border: 1px solid #e5e5e5;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">Order Reference</p>
                                        <p style="margin: 8px 0 0 0; font-size: 24px; color: #C9A96E; font-weight: 700;">#SR-' . ($orderId + 5000) . '</p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Order Items -->
                            <h2 style="font-size: 18px; color: #333; margin: 0 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">Order Details</h2>
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 20px;">';

        foreach ($itemsArray as $item) {
            $pId = $item['product_id'];
            $pType = $item['product_type'];
            $imgField = ($pType == 'jewellery') ? "product_id" : "gproduct_id";
            $imgQ = mysqli_query($con, "SELECT img_name FROM product_images_new WHERE $imgField = '$pId' ORDER BY rank LIMIT 1");
            $imgR = mysqli_fetch_assoc($imgQ);
            $imgPath = !empty($imgR['img_name']) ? "https://srishringarr.com/yn/uploads" . $imgR['img_name'] : 'https://via.placeholder.com/80x100/f5f5f5/999?text=No+Image';

            $body .= '
                                <tr style="border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 15px 0; width: 90px; vertical-align: top;">
                                        <img src="' . $imgPath . '" alt="Product" style="width: 80px; height: 100px; object-fit: cover; border-radius: 6px; border: 1px solid #e5e5e5; display: block;" />
                                    </td>
                                    <td style="padding: 15px 10px; vertical-align: top;">
                                        <p style="margin: 0; font-size: 15px; font-weight: 600; color: #333;">' . htmlspecialchars($item['product_name']) . '</p>
                                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #999;">SKU: ' . htmlspecialchars($item['sku']) . '</p>
                                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #C9A96E; font-weight: 500;">' . ($item['booking_type'] === 'buy' ? 'Purchase' : $item['days'] . ' Days Rental') . '</p>
                                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">Qty: ' . $item['qty'] . '</p>
                                    </td>
                                    <td style="padding: 15px 0; text-align: right; vertical-align: top; white-space: nowrap;">
                                        <p style="margin: 0; font-size: 16px; font-weight: 600; color: #333;">₹' . number_format($item['total'], 2) . '</p>
                                    </td>
                                </tr>';
        }

        $body .= '
                            </table>

                            <!-- Order Summary -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px; border-top: 2px solid #f0f0f0; padding-top: 20px;">
                                <tr>
                                    <td style="padding: 8px 0; font-size: 14px; color: #666;">Items Subtotal</td>
                                    <td style="padding: 8px 0; text-align: right; font-size: 14px; color: #333; font-weight: 500;">₹' . number_format($itemsSubtotal, 2) . '</td>
                                </tr>';
        
        if ($shippingCharge > 0) {
            $body .= '
                                <tr>
                                    <td style="padding: 8px 0; font-size: 14px; color: #666;">Shipping Charge</td>
                                    <td style="padding: 8px 0; text-align: right; font-size: 14px; color: #333; font-weight: 500;">₹' . number_format($shippingCharge, 2) . '</td>
                                </tr>';
        }
        
        if ($couponCode && $discountAmount > 0) {
            $body .= '
                                <tr>
                                    <td style="padding: 8px 0; font-size: 14px; color: #22c55e;">Discount (' . htmlspecialchars($couponCode) . ')</td>
                                    <td style="padding: 8px 0; text-align: right; font-size: 14px; color: #22c55e; font-weight: 500;">− ₹' . number_format($discountAmount, 2) . '</td>
                                </tr>';
        }
        
        $body .= '
                                <tr style="border-top: 2px solid #C9A96E;">
                                    <td style="padding: 15px 0 0 0; font-size: 16px; color: #333; font-weight: 600;">Total Paid</td>
                                    <td style="padding: 15px 0 0 0; text-align: right; font-size: 24px; color: #C9A96E; font-weight: 700;">₹' . number_format($order['total_amount'], 2) . '</td>
                                </tr>
                            </table>

                            <!-- Shipping Address -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 40px; background-color: #fafafa; border-radius: 8px; border: 1px solid #e5e5e5;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <h3 style="margin: 0 0 12px 0; font-size: 14px; color: #333; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">Shipping Address</h3>
                                        <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #555;">
                                            <strong>' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '</strong><br>
                                            ' . htmlspecialchars($order['address']) . '<br>';
        if (!empty($order['landmark'])) $body .= htmlspecialchars($order['landmark']) . '<br>';
        $body .= htmlspecialchars($order['city']) . ', ' . htmlspecialchars($order['state']) . ' - ' . htmlspecialchars($order['pincode']) . '<br>
                                            Phone: ' . htmlspecialchars($order['phone']) . '
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 30px 0 0 0; font-size: 13px; color: #999; text-align: center; line-height: 1.6;">
                                We will notify you via email as soon as your order has been dispatched.<br>
                                For any queries, please contact us at <a href="mailto:support@srishringarr.com" style="color: #C9A96E; text-decoration: none;">support@srishringarr.com</a>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #1a1a1a; padding: 30px; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #999;">© ' . date('Y') . ' Sri Shringarr Fashion Studio. All Rights Reserved.</p>
                            <p style="margin: 10px 0 0 0; font-size: 11px; color: #666;">This is an automated email. Please do not reply.</p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    } else {
        $mail->Subject = 'Payment Failed - Order #' . ($orderId + 5000);
        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ffebee; border-radius: 12px; overflow: hidden;'>
            <div style='background-color: #fff1f0; padding: 30px; text-align: center;'>
                <h1 style='color: #d32f2f; margin: 0; font-size: 24px;'>Payment Failed</h1>
            </div>
            <div style='padding: 30px;'>
                <p>Hi " . htmlspecialchars($order['first_name']) . ",</p>
                <p>Unfortunately, the payment for your order #" . ($orderId + 5000) . " could not be processed.</p>
                <p>If any amount was debited from your account, it will be automatically refunded by your bank within 5-7 business days.</p>
                <p style='margin-top: 25px;'>Please try placing your order again using a different payment method.</p>
                <p style='margin-top: 30px;'>Best regards,<br><strong>Team Sri Shringarr</strong></p>
            </div>
        </div>";
    }

    $mail->Body = $body;
    $mail->send();
    error_log("Email sent successfully for Order #$orderId to {$order['email']}");
    echo json_encode(['status' => 'success', 'message' => 'Email sent successfully']);

} catch (\Exception $e) {
    error_log("Mailer Error for Order #$orderId: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
?>


