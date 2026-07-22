<?php
require_once __DIR__ . '/../config.php';
require_once '../autoload.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$keyId = RAZORPAY_KEY_ID;
$keySecret = RAZORPAY_KEY_SECRET;

// Use amount from frontend (in paise) — the React app sends the correct total
$amount = isset($input['amount']) ? (int) $input['amount'] : 0;

if ($amount <= 0) {
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

$orderData = [
    'receipt' => 'rcpt_' . time(),
    'amount' => $amount,
    'currency' => 'INR',
    'payment_capture' => 1
];

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $keyId . ":" . $keySecret);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['error' => 'Curl Error: ' . $err]);
} else {
    echo $response;
}

