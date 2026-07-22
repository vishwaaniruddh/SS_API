<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['code'])) {
    echo json_encode(['status' => 'error', 'message' => 'Coupon code is required']);
    exit;
}

global $con;

$code = mysqli_real_escape_string($con, trim($input['code']));
$cartTotal = (float) ($input['cartTotal'] ?? 0);
$userEmail = mysqli_real_escape_string($con, $input['email'] ?? '');

// Fetch coupon
$query = mysqli_query($con, "SELECT * FROM coupons WHERE code = '$code' LIMIT 1");
if (!$query || mysqli_num_rows($query) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid coupon code']);
    exit;
}

$coupon = mysqli_fetch_assoc($query);

// Check status
if ($coupon['status'] !== 'active') {
    echo json_encode(['status' => 'error', 'message' => 'This coupon is no longer active']);
    exit;
}

// Check expiry
if (!empty($coupon['expiry_date']) && strtotime($coupon['expiry_date']) < strtotime(date('Y-m-d'))) {
    echo json_encode(['status' => 'error', 'message' => 'This coupon has expired']);
    exit;
}

// Check usage limit
if (!empty($coupon['usage_limit']) && $coupon['usage_count'] >= $coupon['usage_limit']) {
    echo json_encode(['status' => 'error', 'message' => 'This coupon has reached its usage limit']);
    exit;
}

// Check minimum amount
if (!empty($coupon['minimum_amount']) && $cartTotal < (float)$coupon['minimum_amount']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Minimum order amount is ₹' . number_format($coupon['minimum_amount'], 2)
    ]);
    exit;
}

// Check maximum amount
if (!empty($coupon['maximum_amount']) && $cartTotal > (float)$coupon['maximum_amount']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Maximum order amount is ₹' . number_format($coupon['maximum_amount'], 2)
    ]);
    exit;
}

// Check email whitelist
if (!empty($coupon['customer_email_white_list'])) {
    $allowedEmails = array_map('trim', explode(',', $coupon['customer_email_white_list']));
    $allowedEmails = array_filter($allowedEmails);
    if (!empty($allowedEmails) && !in_array($userEmail, $allowedEmails)) {
        echo json_encode(['status' => 'error', 'message' => 'This coupon is not valid for your account']);
        exit;
    }
}

// Calculate discount
$discount = 0;
$discountType = $coupon['discount_type'];
$couponAmount = (float)$coupon['coupon_amount'];

if ($discountType === 'percent') {
    $discount = ($cartTotal * $couponAmount) / 100;
} elseif ($discountType === 'fixed_cart' || $discountType === 'fixed_product') {
    $discount = $couponAmount;
}

// Discount can't be more than cart total
if ($discount > $cartTotal) $discount = $cartTotal;

echo json_encode([
    'status' => 'success',
    'coupon' => [
        'id' => (int)$coupon['id'],
        'code' => $coupon['code'],
        'description' => $coupon['description'],
        'discountType' => $discountType,
        'couponAmount' => $couponAmount,
        'discount' => round($discount, 2),
    ]
]);
