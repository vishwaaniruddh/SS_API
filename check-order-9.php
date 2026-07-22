<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

$orderId = 9;

// Get order items
$itemsQuery = mysqli_query($con, "SELECT * FROM order_items WHERE order_id = $orderId");

$items = [];
while ($item = mysqli_fetch_assoc($itemsQuery)) {
    $items[] = $item;
}

echo json_encode([
    'order_id' => $orderId,
    'items' => $items
], JSON_PRETTY_PRINT);
?>
