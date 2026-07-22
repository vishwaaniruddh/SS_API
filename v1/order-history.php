<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$userId = (int) $_SESSION['userid'];

$ordersQuery = mysqli_query($con, "SELECT * FROM orders WHERE user_id = $userId ORDER BY id DESC");

$orders = [];
if ($ordersQuery) {
    while ($order = mysqli_fetch_assoc($ordersQuery)) {
        $orderId = $order['id'];
        $itemsQuery = mysqli_query($con, "SELECT * FROM order_items WHERE order_id = $orderId");
        $items = [];
        if ($itemsQuery) {
            while ($item = mysqli_fetch_assoc($itemsQuery)) {
                $pId = $item['product_id'];
                $pType = $item['product_type'];
                
                // Fetch product image
                $imgField = ($pType == 'jewellery') ? "product_id" : "gproduct_id";
                $imgQ = mysqli_query($con, "SELECT img_name FROM product_images_new WHERE $imgField = $pId ORDER BY rank LIMIT 1");
                $imgR = mysqli_fetch_assoc($imgQ);
                $imgPath = !empty($imgR['img_name']) ? "https://srishringarr.com/yn/uploads" . $imgR['img_name'] : null;
                
                $items[] = [
                    'id' => $item['id'],
                    'productId' => $item['product_id'],
                    'name' => $item['product_name'],
                    'sku' => $item['sku'],
                    'qty' => (int) $item['qty'],
                    'price' => (float) $item['price'],
                    'total' => (float) $item['total'],
                    'bookingType' => $item['booking_type'],
                    'startDate' => $item['start_date'],
                    'endDate' => $item['end_date'],
                    'days' => (int) $item['days'],
                    'productType' => $item['product_type'],
                    'image' => $imgPath,
                ];
            }
        }

        $orders[] = [
            'id' => (int) $order['id'],  // Convert to integer
            'orderNumber' => 'SR-' . ($order['id'] + 5000),
            'razorpayOrderId' => $order['razorpay_order_id'],
            'razorpayPaymentId' => $order['razorpay_payment_id'],
            'total' => (float) $order['total_amount'],
            'shippingCharge' => isset($order['shipping_charge']) ? (float) $order['shipping_charge'] : 0,
            'couponCode' => $order['coupon_code'] ?? null,
            'discountAmount' => isset($order['discount_amount']) ? (float) $order['discount_amount'] : 0,
            'depositAmount' => isset($order['deposit_amount']) ? (float) $order['deposit_amount'] : 0,
            'status' => $order['status'],
            'firstName' => $order['first_name'],
            'lastName' => $order['last_name'],
            'email' => $order['email'],
            'phone' => $order['phone'],
            'address' => $order['address'],
            'city' => $order['city'],
            'state' => $order['state'],
            'pincode' => $order['pincode'],
            'createdAt' => $order['created_at'] ?? null,
            'items' => $items,
        ];
    }
}

echo json_encode(['status' => 'success', 'orders' => $orders]);

