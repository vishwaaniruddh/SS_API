<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../Services/ShippingService.php';

try {
    $shippingService = new ShippingService();
    $ranges = $shippingService->getShippingRanges();
    
    echo json_encode([
        'success' => true,
        'data' => $ranges
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
