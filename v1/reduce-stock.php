<?php
// API/v1/reduce-stock.php

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$orderId = isset($_POST['order_id']) ? mysqli_real_escape_string($con, $_POST['order_id']) : '';

if (empty($orderId)) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit;
}

// 1. Fetch Order Items
$itemsQuery = mysqli_query($con, "SELECT sku, qty, product_name FROM order_items WHERE order_id = '$orderId'");
if (!$itemsQuery || mysqli_num_rows($itemsQuery) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'No items found for this order']);
    exit;
}

$results = [];
$successCount = 0;

// 2. Connect to POS Database (con3)
// Note: $con3 is already established in config.php

while ($item = mysqli_fetch_assoc($itemsQuery)) {
    $sku = mysqli_real_escape_string($con3, $item['sku']);
    $qty = (float)$item['qty'];
    
    // 3. Update Quantity in phppos_items
    // We match by 'name' as per ProductModel.php logic where name = SKU
    $updateQuery = "UPDATE phppos_items SET quantity = quantity - $qty WHERE name = '$sku'";
    
    if (mysqli_query($con3, $updateQuery)) {
        if (mysqli_affected_rows($con3) > 0) {
            $successCount++;
            $results[] = "Reduced stock for SKU $sku by $qty units.";
        } else {
            $results[] = "SKU $sku not found in POS database or quantity unchanged.";
        }
    } else {
        $results[] = "Error updating SKU $sku: " . mysqli_error($con3);
    }
}

// Log the activity
error_log("Stock Reduction for Order #$orderId: " . implode(" | ", $results));

echo json_encode([
    'status' => 'success',
    'message' => "Stock reduction processed for $successCount items",
    'details' => $results
]);
?>

