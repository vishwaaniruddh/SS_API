<?php
/**
 * Sync Order to Legacy Database Tables
 * 
 * This script syncs orders from the new system to legacy tables:
 * - Purchase orders → ONE approval + MULTIPLE approval_detail (bill_id is PK, FK)
 * - Rental orders → ONE phppos_rent + MULTIPLE order_detail (bill_id is PK, FK)
 * - Updates phppos_items quantity using 'name' column as SKU
 */

require_once __DIR__ . '/../config.php';

function generateInvoiceNumber($invoiceType, $con3) {
    // Generate financial year based invoice number
    $year = date("Y");
    $month = date("m");
    
    if ($month >= 4) {
        $financial_year = substr($year, 2);
    } else {
        $financial_year = substr($year - 1, 2);
    }
    
    $company_name = "Sri Shringarr";
    $company_prefix = "SSFS-"; // Sri Shringarr Fashion Studio
    $prefix = $company_prefix . $financial_year;
    $suffix = ($invoiceType === "rent") ? "/R-" : "/S-";
    
    // Get last invoice number for this company, type, and financial year
    $sql = "SELECT invoice_number FROM invoice_tracker 
            WHERE company = '" . mysqli_real_escape_string($con3, $company_name) . "' 
            AND invoice_type = '" . mysqli_real_escape_string($con3, $invoiceType) . "' 
            AND invoice LIKE '" . mysqli_real_escape_string($con3, $prefix) . "%' 
            ORDER BY id DESC LIMIT 1";
    
    $result = mysqli_query($con3, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $last_invoice_number = (int)$row["invoice_number"] + 1;
    } else {
        $last_invoice_number = 1;
    }
    
    $invoice_number = str_pad($last_invoice_number, 5, "0", STR_PAD_LEFT);
    $full_invoice = $prefix . $suffix . $invoice_number;
    
    // Insert into invoice_tracker
    $insert_sql = "INSERT INTO invoice_tracker (
        company, 
        invoice_type, 
        invoice_number, 
        invoice, 
        created_at, 
        created_by
    ) VALUES (
        '" . mysqli_real_escape_string($con3, $company_name) . "',
        '" . mysqli_real_escape_string($con3, $invoiceType) . "',
        $last_invoice_number,
        '" . mysqli_real_escape_string($con3, $full_invoice) . "',
        NOW(),
        'Online System'
    )";
    
    if (!mysqli_query($con3, $insert_sql)) {
        error_log("Failed to insert invoice tracker: " . mysqli_error($con3));
        return null;
    }
    
    return $full_invoice;
}

function getOrCreateCustomer($order, $con, $con3) {
    // Check if customer exists in phppos_people by email or phone
    $email = mysqli_real_escape_string($con3, $order['email']);
    $phone = mysqli_real_escape_string($con3, $order['phone']);
    
    // Try to find existing customer
    $findQuery = "SELECT person_id FROM phppos_people 
                  WHERE email = '$email' OR phone_number = '$phone' 
                  LIMIT 1";
    $result = mysqli_query($con3, $findQuery);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return ['success' => true, 'person_id' => $row['person_id'], 'created' => false];
    }
    
    // Customer doesn't exist, create new one
    // First, get the Registration data from new database
    $userId = $order['user_id'];
    $regQuery = mysqli_query($con, "SELECT * FROM Registration WHERE registration_id = $userId");
    
    if (!$regQuery || mysqli_num_rows($regQuery) == 0) {
        // No registration found, use order data
        $firstName = mysqli_real_escape_string($con3, $order['first_name']);
        $lastName = mysqli_real_escape_string($con3, $order['last_name']);
    } else {
        $regData = mysqli_fetch_assoc($regQuery);
        $firstName = mysqli_real_escape_string($con3, $regData['Firstname'] ?? $order['first_name']);
        $lastName = mysqli_real_escape_string($con3, $regData['Lastname'] ?? $order['last_name']);
    }
    
    $fullAddress = $order['address'] . ', ' . $order['city'] . ', ' . $order['state'] . ' - ' . $order['pincode'];
    $address = mysqli_real_escape_string($con3, $fullAddress);
    
    // Insert into phppos_people
    $insertQuery = "INSERT INTO phppos_people (
        first_name,
        last_name,
        email,
        phone_number,
        address_1,
        city,
        state,
        zip,
        comments
    ) VALUES (
        '$firstName',
        '$lastName',
        '$email',
        '$phone',
        '$address',
        '" . mysqli_real_escape_string($con3, $order['city']) . "',
        '" . mysqli_real_escape_string($con3, $order['state']) . "',
        '" . mysqli_real_escape_string($con3, $order['pincode']) . "',
        'Online Order - SR-" . ($order['id'] + 5000) . "'
    )";
    
    if (!mysqli_query($con3, $insertQuery)) {
        return ['success' => false, 'message' => 'Failed to create customer: ' . mysqli_error($con3)];
    }
    
    $personId = mysqli_insert_id($con3);
    return ['success' => true, 'person_id' => $personId, 'created' => true];
}

function syncOrderToLegacy($orderId, $con, $con3) {
    // Fetch order details from new database ($con)
    $orderQuery = mysqli_query($con, "SELECT * FROM orders WHERE id = $orderId");
    if (!$orderQuery || mysqli_num_rows($orderQuery) == 0) {
        return ['success' => false, 'message' => 'Order not found'];
    }
    $order = mysqli_fetch_assoc($orderQuery);
    
    // Get or create customer in phppos_people
    $customerResult = getOrCreateCustomer($order, $con, $con3);
    if (!$customerResult['success']) {
        return ['success' => false, 'message' => 'Failed to get/create customer: ' . $customerResult['message']];
    }
    $custId = $customerResult['person_id'];
    
    // Fetch order items from new database ($con)
    $itemsQuery = mysqli_query($con, "SELECT * FROM order_items WHERE order_id = $orderId");
    if (!$itemsQuery) {
        return ['success' => false, 'message' => 'Failed to fetch order items'];
    }
    
    $results = [];
    $results[] = ['type' => 'customer', 'success' => true, 'person_id' => $custId, 'created' => $customerResult['created']];
    
    $purchaseItems = [];
    $rentalItems = [];
    
    // Separate items by booking type
    while ($item = mysqli_fetch_assoc($itemsQuery)) {
        $bookingType = $item['booking_type'];
        
        // Empty booking_type or 'buy' or 'purchase' = Purchase
        // Anything else (like 'rent') = Rental
        if ($bookingType === 'buy' || $bookingType === 'purchase' || empty($bookingType)) {
            $purchaseItems[] = $item;
        } else {
            $rentalItems[] = $item;
        }
    }
    
    // Handle Purchase Orders - ONE approval entry with MULTIPLE approval_detail entries
    if (!empty($purchaseItems)) {
        $result = insertPurchaseOrder($order, $purchaseItems, $custId, $con3);
        $results[] = $result;
        
        // Reduce quantity for each purchase item
        foreach ($purchaseItems as $item) {
            $qtyResult = reduceItemQuantity($item['sku'], (int)$item['qty'], $item['product_type'], $con3);
            $results[] = $qtyResult;
        }
    }
    
    // Handle Rental Orders - ONE phppos_rent entry with MULTIPLE order_detail entries
    if (!empty($rentalItems)) {
        $result = insertRentalOrder($order, $rentalItems, $custId, $con3);
        $results[] = $result;
        
        // Reduce quantity for each rental item
        foreach ($rentalItems as $item) {
            $qtyResult = reduceItemQuantity($item['sku'], (int)$item['qty'], $item['product_type'], $con3);
            $results[] = $qtyResult;
        }
    }
    
    return ['success' => true, 'results' => $results];
}

function insertPurchaseOrder($order, $items, $custId, $con) {
    // Generate invoice number for purchase (sell type)
    $invoiceNumber = generateInvoiceNumber('sell', $con);
    if (!$invoiceNumber) {
        return ['type' => 'purchase', 'success' => false, 'message' => 'Failed to generate invoice number'];
    }
    
    // Insert ONE entry into approval table (main purchase order)
    // Calculate total from all purchase items
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += $item['total'];
    }
    
    // Store original order number in note column
    $originalOrderNumber = 'SR-' . ($order['id'] + 5000);
    
    $approvalQuery = "INSERT INTO approval (
        cust_id,
        bill_date,
        status,
        paid_amount,
        transaction_id,
        pay_by,
        amountTotal,
        new_bill_number,
        company_name,
        note
    ) VALUES (
        $custId,
        NOW(),
        'S',
        $totalAmount,
        '" . mysqli_real_escape_string($con, $order['razorpay_payment_id']) . "',
        'Online',
        '$totalAmount',
        '" . mysqli_real_escape_string($con, $invoiceNumber) . "',
        'Online Order',
        'Original Order: $originalOrderNumber'
    )";
    
    if (!mysqli_query($con, $approvalQuery)) {
        return ['type' => 'purchase', 'success' => false, 'message' => 'Failed to insert into approval: ' . mysqli_error($con)];
    }
    
    $approvalId = mysqli_insert_id($con); // This is the bill_id (PK)
    
    // Insert MULTIPLE entries into approval_detail table (one per item)
    // bill_id is the FK referencing approval.bill_id
    foreach ($items as $item) {
        $detailQuery = "INSERT INTO approval_detail (
            bill_id,
            item_id,
            qty,
            price,
            amount,
            final_amount,
            new_bill_number
        ) VALUES (
            $approvalId,
            '" . mysqli_real_escape_string($con, $item['sku']) . "',
            " . $item['qty'] . ",
            " . $item['price'] . ",
            '" . $item['total'] . "',
            " . $item['total'] . ",
            '" . mysqli_real_escape_string($con, $invoiceNumber) . "'
        )";
        
        if (!mysqli_query($con, $detailQuery)) {
            return ['type' => 'purchase', 'success' => false, 'message' => 'Failed to insert into approval_detail: ' . mysqli_error($con)];
        }
    }
    
    return [
        'type' => 'purchase', 
        'success' => true, 
        'bill_id' => $approvalId, 
        'cust_id' => $custId, 
        'invoice_number' => $invoiceNumber,
        'items_count' => count($items)
    ];
}

function insertRentalOrder($order, $items, $custId, $con) {
    // Generate invoice number for rental (rent type)
    $invoiceNumber = generateInvoiceNumber('rent', $con);
    if (!$invoiceNumber) {
        return ['type' => 'rental', 'success' => false, 'message' => 'Failed to generate invoice number'];
    }
    
    // Insert ONE entry into phppos_rent table (main rental order)
    // Calculate total from all rental items
    $totalAmount = 0;
    $totalRent = 0;
    foreach ($items as $item) {
        $totalAmount += $item['total'];
        $totalRent += $item['price'] * $item['qty'];
    }
    
    // Use dates from first item (or you can handle differently)
    $firstItem = $items[0];
    $pickDate = $firstItem['start_date'] ? "'" . $firstItem['start_date'] . "'" : "'0000-00-00'";
    $deliveryDate = $firstItem['end_date'] ? "'" . $firstItem['end_date'] . "'" : "'0000-00-00'";
    
    // Store original order number in note column
    $originalOrderNumber = 'SR-' . ($order['id'] + 5000);
    
    $rentQuery = "INSERT INTO phppos_rent (
        cust_id,
        cust_name,
        bill_date,
        rent_amount,
        amount,
        status,
        pstatus,
        pick_date,
        delivery_date,
        booking_status,
        transaction_id,
        payment_mode_name,
        new_bill_number,
        company_name,
        is_online,
        note
    ) VALUES (
        $custId,
        '" . mysqli_real_escape_string($con, $order['first_name'] . ' ' . $order['last_name']) . "',
        NOW(),
        $totalRent,
        '$totalAmount',
        'S',
        'Paid',
        $pickDate,
        $deliveryDate,
        'Confirmed',
        '" . mysqli_real_escape_string($con, $order['razorpay_payment_id']) . "',
        'Razorpay',
        '" . mysqli_real_escape_string($con, $invoiceNumber) . "',
        'Online Order',
        1,
        'Original Order: $originalOrderNumber'
    )";
    
    if (!mysqli_query($con, $rentQuery)) {
        return ['type' => 'rental', 'success' => false, 'message' => 'Failed to insert into phppos_rent: ' . mysqli_error($con)];
    }
    
    $rentId = mysqli_insert_id($con); // This is the bill_id (PK)
    
    // Insert MULTIPLE entries into order_detail table (one per item)
    // bill_id is the FK referencing phppos_rent.bill_id
    foreach ($items as $item) {
        $depositAmount = isset($item['deposit']) ? $item['deposit'] : 0;
        $pickupDate = $item['start_date'] ? "'" . $item['start_date'] . "'" : "'0000-00-00'";
        $returnDate = $item['end_date'] ? "'" . $item['end_date'] . "'" : "'0000-00-00'";
        
        $detailQuery = "INSERT INTO order_detail (
            bill_id,
            item_id,
            rent,
            deposit,
            qty,
            total_amount,
            item_detail,
            pickup_date,
            return_date,
            new_bill_number
        ) VALUES (
            $rentId,
            '" . mysqli_real_escape_string($con, $item['sku']) . "',
            " . $item['price'] . ",
            " . $depositAmount . ",
            " . $item['qty'] . ",
            " . $item['total'] . ",
            '" . mysqli_real_escape_string($con, $item['product_name']) . "',
            $pickupDate,
            $returnDate,
            '" . mysqli_real_escape_string($con, $invoiceNumber) . "'
        )";
        
        if (!mysqli_query($con, $detailQuery)) {
            return ['type' => 'rental', 'success' => false, 'message' => 'Failed to insert into order_detail: ' . mysqli_error($con)];
        }
    }
    
    return [
        'type' => 'rental', 
        'success' => true, 
        'bill_id' => $rentId, 
        'cust_id' => $custId, 
        'invoice_number' => $invoiceNumber,
        'items_count' => count($items)
    ];
}

function reduceItemQuantity($sku, $qty, $productType, $con) {
    // Update quantity in phppos_items table
    // Note: phppos_items uses 'name' column as the SKU identifier (not item_number or item_id)
    // item_id is the auto-increment primary key
    $updateQuery = "UPDATE phppos_items 
                    SET quantity = GREATEST(0, quantity - $qty),
                        updated_at = NOW()
                    WHERE name = '" . mysqli_real_escape_string($con, $sku) . "'";
    
    if (!mysqli_query($con, $updateQuery)) {
        return ['type' => 'quantity', 'success' => false, 'message' => 'Failed to update quantity: ' . mysqli_error($con)];
    }
    
    $affectedRows = mysqli_affected_rows($con);
    
    return [
        'type' => 'quantity', 
        'success' => true, 
        'sku' => $sku, 
        'reduced_by' => $qty,
        'affected_rows' => $affectedRows
    ];
}

// If called directly with order_id parameter
if (isset($_GET['order_id']) || isset($_POST['order_id'])) {
    header('Content-Type: application/json');
    $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : (int)$_POST['order_id'];
    
    if ($orderId > 0) {
        $result = syncOrderToLegacy($orderId, $con, $con3);
        echo json_encode($result);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    }
    exit;
}
?>
