<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['productId'])) {
    echo json_encode(['status' => 'error', 'message' => 'Product ID required']);
    exit;
}

global $con;

$productId = (int) $input['productId'];
$productName = mysqli_real_escape_string($con, $input['productName'] ?? '');
$productType = mysqli_real_escape_string($con, $input['productType'] ?? '');
$userId = (int) ($_SESSION['userid'] ?? $_SESSION['gid'] ?? 0);
$sessionId = mysqli_real_escape_string($con, session_id());
$ip = mysqli_real_escape_string($con, $_SERVER['REMOTE_ADDR'] ?? '');
$userAgent = mysqli_real_escape_string($con, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500));
$referrer = mysqli_real_escape_string($con, substr($input['referrer'] ?? ($_SERVER['HTTP_REFERER'] ?? ''), 0, 500));
$deviceType = 'desktop';
$utmSource = mysqli_real_escape_string($con, $input['utmSource'] ?? '');
$utmMedium = mysqli_real_escape_string($con, $input['utmMedium'] ?? '');
$utmCampaign = mysqli_real_escape_string($con, $input['utmCampaign'] ?? '');

// Detect device type from user agent
$ua = strtolower($userAgent);
if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/i', $ua)) {
    $deviceType = 'mobile';
} elseif (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
    $deviceType = 'tablet';
}

// Prevent duplicate views within 5 minutes from same session for same product
$dedupeCheck = mysqli_query($con, 
    "SELECT id FROM product_views 
     WHERE product_id = $productId 
     AND session_id = '$sessionId' 
     AND viewed_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) 
     LIMIT 1"
);

if ($dedupeCheck && mysqli_num_rows($dedupeCheck) > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Already tracked']);
    exit;
}

$query = "INSERT INTO product_views 
    (product_id, product_name, product_type, user_id, session_id, ip_address, user_agent, referrer, device_type, utm_source, utm_medium, utm_campaign, viewed_at) 
    VALUES 
    ($productId, '$productName', '$productType', $userId, '$sessionId', '$ip', '$userAgent', '$referrer', '$deviceType', '$utmSource', '$utmMedium', '$utmCampaign', NOW())";

if (mysqli_query($con, $query)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . mysqli_error($con)]);
}
