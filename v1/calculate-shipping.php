<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../Services/ShippingService.php';

try {
    $shippingService = new ShippingService();
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get cart total from query parameter
        $cartTotal = isset($_GET['cart_total']) ? floatval($_GET['cart_total']) : 0;
        
        if ($cartTotal <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid cart total'
            ]);
            exit();
        }
        
        $result = $shippingService->calculateShippingCharge($cartTotal);
        $freeShippingInfo = $shippingService->getFreeShippingInfo($cartTotal);
        
        echo json_encode([
            'success' => true,
            'data' => array_merge($result, [
                'free_shipping_info' => $freeShippingInfo
            ])
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get cart total from POST body
        $input = json_decode(file_get_contents('php://input'), true);
        $cartTotal = isset($input['cart_total']) ? floatval($input['cart_total']) : 0;
        
        if ($cartTotal <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid cart total'
            ]);
            exit();
        }
        
        $result = $shippingService->calculateShippingCharge($cartTotal);
        $freeShippingInfo = $shippingService->getFreeShippingInfo($cartTotal);
        
        echo json_encode([
            'success' => true,
            'data' => array_merge($result, [
                'free_shipping_info' => $freeShippingInfo
            ])
        ]);
        
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Method not allowed'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
