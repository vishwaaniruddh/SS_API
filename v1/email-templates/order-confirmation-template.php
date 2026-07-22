<?php
function getOrderConfirmationEmail($order, $itemsArray, $itemsSubtotal, $shippingCharge, $discountAmount, $depositAmount, $couponCode, $orderId, $con) {
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;background:#0a0a0a;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#0a0a0a;">
<!-- Header -->
<tr><td style="background:linear-gradient(135deg,#1a1a1a 0%,#2d2d2d 100%);padding:50px 20px;text-align:center;border-bottom:3px solid #C9A96E;">
<img src="https://srishringarr.com/logo-transparent.png" alt="Sri Shringarr" style="height:60px;margin-bottom:25px;"/>
<h1 style="color:#C9A96E;margin:0;font-size:36px;font-weight:700;letter-spacing:1px;text-transform:uppercase;">ORDER CONFIRMED</h1>
<p style="color:#999;margin:15px 0 0 0;font-size:16px;letter-spacing:0.5px;">Thank you for choosing Sri Shringarr Fashion Studio</p>
</td></tr>

<!-- Main Content -->
<tr><td style="padding:50px 20px;background:#f5f5f5;">
<table width="100%" max-width="1200px" cellpadding="0" cellspacing="0" align="center" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,0.12);">

<!-- Welcome -->
<tr><td style="padding:40px 50px;background:#fafafa;border-bottom:1px solid #e5e5e5;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td style="width:65%;vertical-align:top;">
<p style="font-size:18px;color:#333;margin:0 0 10px 0;">Dear <strong style="color:#C9A96E;"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></strong>,</p>
<p style="font-size:15px;color:#666;line-height:1.8;margin:0;">We are thrilled to confirm that your order has been successfully placed! Your payment has been processed, and we are now preparing your exquisite pieces for delivery.</p>
</td>
<td style="width:35%;vertical-align:top;text-align:right;padding-left:30px;">
<div style="background:linear-gradient(135deg,#C9A96E 0%,#d4b87a 100%);border-radius:10px;padding:20px;text-align:center;">
<p style="margin:0;font-size:11px;color:rgba(0,0,0,0.6);text-transform:uppercase;letter-spacing:1.5px;font-weight:600;">Order Number</p>
<p style="margin:8px 0 0 0;font-size:28px;color:#1a1a1a;font-weight:800;letter-spacing:1px;">#SR-<?= ($orderId + 5000) ?></p>
<a href="https://srishringarr.com/account/orders/<?= $orderId ?>" style="display:inline-block;margin-top:15px;background:#1a1a1a;color:#C9A96E;padding:10px 20px;border-radius:6px;text-decoration:none;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:1px;transition:background 0.3s;" target="_blank">
View Order Details
</a>
</div>
<p style="margin:15px 0 0 0;font-size:12px;color:#999;text-align:center;">
Order Date: <?= date('d M Y', strtotime($order['created_at'])) ?><br><?= date('h:i A', strtotime($order['created_at'])) ?>
</p>
</td>
</tr>
</table>
</td></tr>

<!-- Order Items -->
<tr><td style="padding:40px 50px;">
<h2 style="font-size:22px;color:#1a1a1a;margin:0 0 25px 0;padding-bottom:15px;border-bottom:3px solid #C9A96E;font-weight:700;text-transform:uppercase;letter-spacing:1px;">📦 ORDER DETAILS</h2>
<table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e5e5;border-radius:8px;overflow:hidden;">
<?php
$itemCount = 0;
foreach ($itemsArray as $item) {
    $itemCount++;
    $pId = $item['product_id'];
    $pType = $item['product_type'];
    $imgField = ($pType == 'jewellery') ? "product_id" : "gproduct_id";
    $imgQ = mysqli_query($con, "SELECT img_name FROM product_images_new WHERE $imgField = '$pId' ORDER BY rank LIMIT 1");
    $imgR = mysqli_fetch_assoc($imgQ);
    $imgPath = !empty($imgR['img_name']) ? "https://srishringarr.com/yn/uploads" . $imgR['img_name'] : 'https://via.placeholder.com/120x150/f5f5f5/999?text=No+Image';
    
    // Generate product URL (slug-based)
    $productName = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $item['product_name'])));
    $productUrl = "https://srishringarr.com/product/{$productName}-{$pId}";
    
    $bgColor = ($itemCount % 2 == 0) ? '#fafafa' : '#ffffff';
?>
<tr style="background:<?= $bgColor ?>;">
<td style="padding:25px;width:140px;vertical-align:top;">
<a href="<?= $productUrl ?>" style="text-decoration:none;display:block;" target="_blank">
<img src="<?= $imgPath ?>" alt="Product" style="width:120px;height:150px;object-fit:cover;border-radius:8px;border:2px solid #e5e5e5;display:block;box-shadow:0 2px 8px rgba(0,0,0,0.1);transition:opacity 0.3s;"/>
</a>
</td>
<td style="padding:25px;vertical-align:top;">
<a href="<?= $productUrl ?>" style="text-decoration:none;color:#1a1a1a;display:block;margin-bottom:12px;" target="_blank">
<p style="margin:0;font-size:18px;font-weight:700;color:#1a1a1a;line-height:1.4;transition:color 0.3s;"><?= htmlspecialchars($item['product_name']) ?></p>
</a>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
<tr><td style="padding:6px 0;">
<span style="display:inline-block;background:#f0f0f0;padding:4px 10px;border-radius:4px;font-size:11px;color:#666;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">SKU: <?= htmlspecialchars($item['sku']) ?></span>
</td></tr>
<tr><td style="padding:6px 0;">
<span style="display:inline-block;background:linear-gradient(135deg,#C9A96E 0%,#d4b87a 100%);color:#1a1a1a;padding:5px 12px;border-radius:4px;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">
<?php 
if ($item['booking_type'] === 'buy' || $item['booking_type'] === 'purchase' || empty($item['booking_type'])) {
    echo '🛍️ Purchase';
} else {
    echo '📅 ' . $item['days'] . ' Days Rental';
}
?>
</span>
</td></tr>
<?php if ($item['booking_type'] !== 'buy' && $item['booking_type'] !== 'purchase' && !empty($item['booking_type']) && !empty($item['start_date'])) { ?>
<tr><td style="padding:6px 0;">
<p style="margin:0;font-size:13px;color:#666;line-height:1.6;">
<strong>Rental Period:</strong><br>
📅 Pickup: <?= date('d M Y', strtotime($item['start_date'])) ?><br>
📅 Return: <?= date('d M Y', strtotime($item['end_date'])) ?>
</p>
</td></tr>
<?php } ?>
<tr><td style="padding:6px 0;">
<p style="margin:0;font-size:13px;color:#666;"><strong>Quantity:</strong> <?= $item['qty'] ?> × ₹<?= number_format($item['price'], 2) ?></p>
</td></tr>
</table>
</td>
<td style="padding:25px;text-align:right;vertical-align:top;white-space:nowrap;width:150px;">
<p style="margin:0;font-size:11px;color:#999;text-transform:uppercase;letter-spacing:1px;">Item Total</p>
<p style="margin:5px 0 0 0;font-size:24px;font-weight:700;color:#C9A96E;">₹<?= number_format($item['total'], 2) ?></p>
</td>
</tr>
<?php } ?>
</table>
</td></tr>

<!-- Order Summary -->
<tr><td style="padding:0 50px 40px 50px;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td style="width:60%;vertical-align:top;padding-right:30px;">

<!-- Shipping Address -->
<div style="background:#fafafa;border-radius:10px;padding:25px;border:1px solid #e5e5e5;margin-bottom:25px;">
<h3 style="margin:0 0 15px 0;font-size:16px;color:#1a1a1a;text-transform:uppercase;letter-spacing:1px;font-weight:700;border-bottom:2px solid #C9A96E;padding-bottom:10px;">🚚 SHIPPING ADDRESS</h3>
<p style="margin:0;font-size:15px;line-height:1.8;color:#333;">
<strong style="font-size:16px;color:#1a1a1a;"><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></strong><br>
<?= htmlspecialchars($order['address']) ?><br>
<?php if (!empty($order['landmark'])) echo 'Landmark: ' . htmlspecialchars($order['landmark']) . '<br>'; ?>
<?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['state']) ?> - <?= htmlspecialchars($order['pincode']) ?><br>
<strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?><br>
<strong>Email:</strong> <?= htmlspecialchars($order['email']) ?>
</p>
</div>

<!-- Payment Info -->
<div style="background:#f0fdf4;border-radius:10px;padding:25px;border:1px solid #bbf7d0;">
<h3 style="margin:0 0 15px 0;font-size:16px;color:#166534;text-transform:uppercase;letter-spacing:1px;font-weight:700;border-bottom:2px solid #22c55e;padding-bottom:10px;">✅ PAYMENT CONFIRMED</h3>
<p style="margin:0;font-size:14px;line-height:1.8;color:#166534;">
<strong>Payment Method:</strong> Razorpay<br>
<strong>Payment ID:</strong> <?= htmlspecialchars($order['razorpay_payment_id']) ?><br>
<strong>Order ID:</strong> <?= htmlspecialchars($order['razorpay_order_id']) ?><br>
<strong>Status:</strong> <span style="background:#22c55e;color:white;padding:3px 10px;border-radius:4px;font-size:12px;font-weight:600;">PAID</span>
</p>
</div>

</td>
<td style="width:40%;vertical-align:top;">

<!-- Price Breakdown -->
<div style="background:linear-gradient(135deg,#1a1a1a 0%,#2d2d2d 100%);border-radius:10px;padding:30px;box-shadow:0 4px 12px rgba(0,0,0,0.15);">
<h3 style="margin:0 0 20px 0;font-size:18px;color:#C9A96E;text-transform:uppercase;letter-spacing:1px;font-weight:700;text-align:center;">💰 ORDER SUMMARY</h3>
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td style="padding:12px 0;font-size:15px;color:#ccc;border-bottom:1px solid rgba(255,255,255,0.1);">Items Subtotal</td>
<td style="padding:12px 0;text-align:right;font-size:15px;color:#fff;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.1);">₹<?= number_format($itemsSubtotal, 2) ?></td>
</tr>
<?php if ($depositAmount > 0) { ?>
<tr>
<td style="padding:12px 0;font-size:15px;color:#fbbf24;border-bottom:1px solid rgba(255,255,255,0.1);">💰 Refundable Deposit</td>
<td style="padding:12px 0;text-align:right;font-size:15px;color:#fbbf24;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.1);">₹<?= number_format($depositAmount, 2) ?></td>
</tr>
<?php } ?>
<?php if ($shippingCharge > 0) { ?>
<tr>
<td style="padding:12px 0;font-size:15px;color:#ccc;border-bottom:1px solid rgba(255,255,255,0.1);">Shipping Charge</td>
<td style="padding:12px 0;text-align:right;font-size:15px;color:#fff;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.1);">₹<?= number_format($shippingCharge, 2) ?></td>
</tr>
<?php } ?>
<?php if ($couponCode && $discountAmount > 0) { ?>
<tr>
<td style="padding:12px 0;font-size:15px;color:#22c55e;border-bottom:1px solid rgba(255,255,255,0.1);">🎟️ Discount (<?= htmlspecialchars($couponCode) ?>)</td>
<td style="padding:12px 0;text-align:right;font-size:15px;color:#22c55e;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.1);">− ₹<?= number_format($discountAmount, 2) ?></td>
</tr>
<?php } ?>
<tr>
<td style="padding:20px 0 0 0;font-size:18px;color:#C9A96E;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Total Paid</td>
<td style="padding:20px 0 0 0;text-align:right;font-size:32px;color:#C9A96E;font-weight:800;">₹<?= number_format($order['total_amount'], 2) ?></td>
</tr>
</table>
</div>

</td>
</tr>
</table>
</td></tr>

<!-- What Happens Next -->
<tr><td style="padding:0 50px 40px 50px;">
<div style="background:linear-gradient(135deg,#fef3c7 0%,#fde68a 100%);border-radius:10px;padding:30px;border:2px solid #fbbf24;">
<h3 style="margin:0 0 20px 0;font-size:20px;color:#92400e;text-transform:uppercase;letter-spacing:1px;font-weight:700;text-align:center;">⏱️ WHAT HAPPENS NEXT?</h3>
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td style="width:33.33%;padding:15px;vertical-align:top;text-align:center;">
<div style="background:white;border-radius:8px;padding:20px;height:100%;">
<p style="margin:0;font-size:36px;">📋</p>
<p style="margin:10px 0 5px 0;font-size:14px;font-weight:700;color:#92400e;">Step 1</p>
<p style="margin:0;font-size:13px;color:#78350f;line-height:1.6;">Order Processing<br><small>We prepare your items</small></p>
</div>
</td>
<td style="width:33.33%;padding:15px;vertical-align:top;text-align:center;">
<div style="background:white;border-radius:8px;padding:20px;height:100%;">
<p style="margin:0;font-size:36px;">📦</p>
<p style="margin:10px 0 5px 0;font-size:14px;font-weight:700;color:#92400e;">Step 2</p>
<p style="margin:0;font-size:13px;color:#78350f;line-height:1.6;">Quality Check & Pack<br><small>Ensuring perfection</small></p>
</div>
</td>
<td style="width:33.33%;padding:15px;vertical-align:top;text-align:center;">
<div style="background:white;border-radius:8px;padding:20px;height:100%;">
<p style="margin:0;font-size:36px;">🚚</p>
<p style="margin:10px 0 5px 0;font-size:14px;font-weight:700;color:#92400e;">Step 3</p>
<p style="margin:0;font-size:13px;color:#78350f;line-height:1.6;">Dispatch & Delivery<br><small>Track your order</small></p>
</div>
</td>
</tr>
</table>
</div>
</td></tr>

<!-- Customer Support -->
<tr><td style="padding:0 50px 40px 50px;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td style="width:50%;padding-right:15px;">
<div style="background:#eff6ff;border-radius:10px;padding:25px;border:1px solid #bfdbfe;text-align:center;">
<p style="margin:0;font-size:32px;">📞</p>
<p style="margin:10px 0 5px 0;font-size:16px;font-weight:700;color:#1e40af;">Need Help?</p>
<p style="margin:0;font-size:14px;color:#1e3a8a;line-height:1.6;">
Contact our support team<br>
<a href="mailto:support@srishringarr.com" style="color:#2563eb;text-decoration:none;font-weight:600;">support@srishringarr.com</a>
</p>
</div>
</td>
<td style="width:50%;padding-left:15px;">
<div style="background:#f0fdf4;border-radius:10px;padding:25px;border:1px solid #bbf7d0;text-align:center;">
<p style="margin:0;font-size:32px;">💬</p>
<p style="margin:10px 0 5px 0;font-size:16px;font-weight:700;color:#166534;">Track Your Order</p>
<p style="margin:0;font-size:14px;color:#14532d;line-height:1.6;">
Login to your account<br>
<a href="https://srishringarr.com/account" style="color:#16a34a;text-decoration:none;font-weight:600;">View Order Status</a>
</p>
</div>
</td>
</tr>
</table>
</td></tr>

</table>
</td></tr>

<!-- Footer -->
<tr><td style="background:#1a1a1a;padding:40px 20px;text-align:center;border-top:3px solid #C9A96E;">
<p style="margin:0 0 15px 0;font-size:14px;color:#C9A96E;font-weight:600;letter-spacing:1px;">FOLLOW US</p>
<p style="margin:0 0 20px 0;">
<a href="#" style="display:inline-block;margin:0 10px;color:#999;text-decoration:none;font-size:24px;">📘</a>
<a href="#" style="display:inline-block;margin:0 10px;color:#999;text-decoration:none;font-size:24px;">📷</a>
<a href="#" style="display:inline-block;margin:0 10px;color:#999;text-decoration:none;font-size:24px;">🐦</a>
</p>
<p style="margin:0;font-size:13px;color:#999;line-height:1.8;">
© <?= date('Y') ?> Sri Shringarr Fashion Studio. All Rights Reserved.<br>
This is an automated email. Please do not reply to this message.
</p>
</td></tr>

</table>
</body>
</html>
<?php
    return ob_get_clean();
}
?>
