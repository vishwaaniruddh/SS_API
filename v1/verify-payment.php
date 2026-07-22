<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$payment = $input['payment'];
$orderData = $input['order'];
$shipping = $input['shipping'];
$cartItems = $input['cartItems'] ?? []; // Cart items from React frontend
$couponCode = $input['couponCode'] ?? null;
$shippingCharge = isset($input['shippingCharge']) ? (float)$input['shippingCharge'] : 0;
$discountAmount = isset($input['discountAmount']) ? (float)$input['discountAmount'] : 0;

// Calculate deposit amount from cart items
$depositAmount = 0;
foreach ($cartItems as $item) {
    if (isset($item['rental']['deposit'])) {
        $depositAmount += (float)$item['rental']['deposit'] * (int)($item['quantity'] ?? 1);
    }
}

$keySecret = RAZORPAY_KEY_SECRET;

// Verify Signature
$generated_signature = hash_hmac('sha256', $payment['razorpay_order_id'] . "|" . $payment['razorpay_payment_id'], $keySecret);

if ($generated_signature !== $payment['razorpay_signature']) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
    exit;
}

global $con;
$userId = $_SESSION['userid'] ?? $_SESSION['gid'] ?? 0;

// Calculate total from cart items
$total = $orderData['amount'] / 100; // Amount from Razorpay order (in rupees)

$fname = mysqli_real_escape_string($con, $shipping['firstName'] ?? '');
$lname = mysqli_real_escape_string($con, $shipping['lastName'] ?? '');
$email = mysqli_real_escape_string($con, $shipping['email'] ?? '');
$phone = mysqli_real_escape_string($con, $shipping['phone'] ?? '');
$address = mysqli_real_escape_string($con, $shipping['address'] ?? '');
$landmark = mysqli_real_escape_string($con, $shipping['landmark'] ?? '');
$city = mysqli_real_escape_string($con, $shipping['city'] ?? '');
$state = mysqli_real_escape_string($con, $shipping['state'] ?? '');
$pincode = mysqli_real_escape_string($con, $shipping['pincode'] ?? '');
$rzp_order_id = mysqli_real_escape_string($con, $payment['razorpay_order_id']);
$rzp_payment_id = mysqli_real_escape_string($con, $payment['razorpay_payment_id']);
$safeCouponCode = $couponCode ? "'" . mysqli_real_escape_string($con, $couponCode) . "'" : 'NULL';

$query = "INSERT INTO orders (user_id, razorpay_order_id, razorpay_payment_id, total_amount, shipping_charge, coupon_code, discount_amount, deposit_amount, status, first_name, last_name, email, phone, address, landmark, city, state, pincode, created_at) 
          VALUES ('$userId', '$rzp_order_id', '$rzp_payment_id', '$total', '$shippingCharge', $safeCouponCode, '$discountAmount', '$depositAmount', 'paid', '$fname', '$lname', '$email', '$phone', '$address', '$landmark', '$city', '$state', '$pincode', NOW())";

if (mysqli_query($con, $query)) {
    $orderId = mysqli_insert_id($con);

    // Save Order Items from the React frontend cart
    foreach ($cartItems as $item) {
        $pId = (int)($item['id'] ?? 0);
        $pName = mysqli_real_escape_string($con, $item['name'] ?? '');
        $sku = mysqli_real_escape_string($con, $item['code'] ?? '');
        $qty = (int)($item['quantity'] ?? 1);
        $price = (float)($item['price'] ?? 0);
        $itemTotal = $price * $qty;
        $bType = mysqli_real_escape_string($con, $item['orderType'] ?? 'rent');
        $pType = mysqli_real_escape_string($con, $item['type'] ?? 'jewellery');
        
        // Rental details
        $rental = $item['rental'] ?? null;
        $days = $rental ? (int)($rental['days'] ?? 0) : 0;
        $sDate = 'NULL';
        $eDate = 'NULL';
        if ($rental && !empty($rental['startDate'])) {
            $startTs = strtotime($rental['startDate']);
            if ($startTs) $sDate = "'" . date('Y-m-d', $startTs) . "'";
        }
        if ($rental && !empty($rental['endDate'])) {
            $endTs = strtotime($rental['endDate']);
            if ($endTs) $eDate = "'" . date('Y-m-d', $endTs) . "'";
        }

        $detailQuery = "INSERT INTO order_items (order_id, product_id, product_name, sku, qty, price, total, booking_type, start_date, end_date, days, product_type) 
                        VALUES ('$orderId', '$pId', '$pName', '$sku', '$qty', '$price', '$itemTotal', '$bType', $sDate, $eDate, '$days', '$pType')";
        mysqli_query($con, $detailQuery);
    }

    // Increment coupon usage count if a coupon was used
    if (!empty($couponCode)) {
        $safeCode = mysqli_real_escape_string($con, $couponCode);
        mysqli_query($con, "UPDATE coupons SET usage_count = usage_count + 1 WHERE code = '$safeCode'");
    }

    // Send Order Confirmation Email
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    $apiUrl = $protocol . $host . $uri . '/send-order-email.php';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['order_id' => $orderId, 'type' => 'success']));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    // Pass session cookie so email script can access DB
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    $emailResponse = curl_exec($ch);
    $emailError = curl_error($ch);
    curl_close($ch);

    if ($emailError) {
        error_log("Email cURL Error: " . $emailError);
    } else {
        error_log("Email Response: " . $emailResponse);
    }
    
    // Sync to legacy database tables (u464193275_srishringarr)
    require_once __DIR__ . '/sync-legacy-database.php';
    $syncResult = syncOrderToLegacy($orderId, $con, $con3);
    if (!$syncResult['success']) {
        error_log("Legacy DB Sync Error for Order #$orderId: " . json_encode($syncResult));
    } else {
        error_log("Legacy DB Sync Success for Order #$orderId: " . json_encode($syncResult));
    }

    echo json_encode(['status' => 'success', 'order_id' => $orderId]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Order insertion error: ' . mysqli_error($con)]);
}

