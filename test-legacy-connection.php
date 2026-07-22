<?php
/**
 * Legacy Database Connection & Structure Test
 * 
 * This script tests the connection to the legacy database and verifies
 * that all required tables and columns exist for the sync functionality.
 * 
 * Run this script in your browser: http://localhost/ss/API/test-legacy-connection.php
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legacy Database Connection Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a1a1a;
            border-bottom: 3px solid #C9A96E;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
            border-left: 4px solid #C9A96E;
            padding-left: 15px;
        }
        .success {
            color: #16a34a;
            background: #f0fdf4;
            padding: 10px 15px;
            border-radius: 6px;
            border-left: 4px solid #16a34a;
            margin: 10px 0;
        }
        .error {
            color: #dc2626;
            background: #fef2f2;
            padding: 10px 15px;
            border-radius: 6px;
            border-left: 4px solid #dc2626;
            margin: 10px 0;
        }
        .warning {
            color: #d97706;
            background: #fffbeb;
            padding: 10px 15px;
            border-radius: 6px;
            border-left: 4px solid #d97706;
            margin: 10px 0;
        }
        .info {
            color: #2563eb;
            background: #eff6ff;
            padding: 10px 15px;
            border-radius: 6px;
            border-left: 4px solid #2563eb;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e5e5;
        }
        th {
            background: #fafafa;
            font-weight: 600;
            color: #1a1a1a;
        }
        tr:hover {
            background: #fafafa;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        .badge-error {
            background: #fee2e2;
            color: #991b1b;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Legacy Database Connection & Structure Test</h1>
        <p>Testing connection to <code>u464193275_srishringarr</code> database and verifying table structures for order sync.</p>

<?php

// Test connection
echo "<h2>1. Database Connection Test</h2>";

if (!$con3) {
    echo '<div class="error">❌ <strong>Connection Failed:</strong> ' . mysqli_connect_error() . '</div>';
    echo '<p>Please check your database credentials in <code>config.php</code></p>';
    exit;
}

echo '<div class="success">✅ <strong>Connection Successful!</strong> Connected to legacy database.</div>';

// Get database name
$dbNameQuery = mysqli_query($con3, "SELECT DATABASE() as db_name");
$dbNameResult = mysqli_fetch_assoc($dbNameQuery);
echo '<div class="info">📊 <strong>Database:</strong> ' . htmlspecialchars($dbNameResult['db_name']) . '</div>';

// Define required tables and their columns
$requiredTables = [
    'approval' => [
        'bill_id', 'customer_name', 'customer_email', 'customer_phone',
        'customer_address', 'total_amount', 'payment_status',
        'razorpay_order_id', 'razorpay_payment_id', 'created_at', 'status'
    ],
    'approval_detail' => [
        'approval_id', 'bill_id', 'item_id', 'product_name',
        'qty', 'price', 'total', 'product_type'
    ],
    'phppos_rent' => [
        'bill_id', 'customer_name', 'customer_email', 'customer_phone',
        'customer_address', 'pick_date', 'delivery_date', 'days',
        'total_amount', 'payment_status', 'razorpay_order_id',
        'razorpay_payment_id', 'created_at', 'booking_status'
    ],
    'order_detail' => [
        'bill_id', 'item_id', 'product_name', 'qty', 'price',
        'total', 'product_type', 'pick_date', 'delivery_date', 'days'
    ],
    'phppos_items' => [
        'item_id', 'quantity'
    ]
];

// Check each table
echo "<h2>2. Table Structure Verification</h2>";

$allTablesOk = true;

foreach ($requiredTables as $tableName => $requiredColumns) {
    echo "<h3>📋 Table: <code>$tableName</code></h3>";
    
    // Check if table exists
    $tableCheck = mysqli_query($con3, "SHOW TABLES LIKE '$tableName'");
    
    if (mysqli_num_rows($tableCheck) == 0) {
        echo '<div class="error">❌ <strong>Table does not exist!</strong></div>';
        $allTablesOk = false;
        continue;
    }
    
    echo '<div class="success">✅ Table exists</div>';
    
    // Get table structure
    $columnsQuery = mysqli_query($con3, "DESCRIBE $tableName");
    $existingColumns = [];
    
    echo '<table>';
    echo '<thead><tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    
    while ($col = mysqli_fetch_assoc($columnsQuery)) {
        $existingColumns[] = $col['Field'];
        $isRequired = in_array($col['Field'], $requiredColumns);
        $badge = $isRequired ? '<span class="badge badge-success">Required</span>' : '';
        
        echo '<tr>';
        echo '<td><code>' . htmlspecialchars($col['Field']) . '</code></td>';
        echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
        echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
        echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
        echo '<td>' . $badge . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    
    // Check for missing required columns
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (!empty($missingColumns)) {
        echo '<div class="error">❌ <strong>Missing required columns:</strong> ' . implode(', ', array_map(function($col) {
            return '<code>' . htmlspecialchars($col) . '</code>';
        }, $missingColumns)) . '</div>';
        $allTablesOk = false;
    } else {
        echo '<div class="success">✅ All required columns present</div>';
    }
    
    // Get row count
    $countQuery = mysqli_query($con3, "SELECT COUNT(*) as cnt FROM $tableName");
    $countResult = mysqli_fetch_assoc($countQuery);
    echo '<div class="info">📊 <strong>Current records:</strong> ' . number_format($countResult['cnt']) . '</div>';
}

// Special check for phppos_items
echo "<h2>3. Inventory Table (phppos_items) Details</h2>";

$itemsColumnsQuery = mysqli_query($con3, "DESCRIBE phppos_items");
$hasItemId = false;
$hasQuantity = false;
$skuColumnName = null;

echo '<div class="info">🔍 Checking for SKU/item identifier column...</div>';

while ($col = mysqli_fetch_assoc($itemsColumnsQuery)) {
    if ($col['Field'] == 'item_id') {
        $hasItemId = true;
        $skuColumnName = 'item_id';
    }
    if ($col['Field'] == 'quantity') {
        $hasQuantity = true;
    }
    // Check for alternative SKU column names
    if (in_array(strtolower($col['Field']), ['sku', 'product_code', 'item_code', 'code'])) {
        $skuColumnName = $col['Field'];
    }
}

if ($hasItemId) {
    echo '<div class="success">✅ <strong>item_id</strong> column exists (used for SKU matching)</div>';
} else {
    if ($skuColumnName) {
        echo '<div class="warning">⚠️ <strong>item_id</strong> column not found, but found <code>' . htmlspecialchars($skuColumnName) . '</code> column. You may need to update the sync code.</div>';
    } else {
        echo '<div class="error">❌ No SKU/item identifier column found! Please identify the correct column name.</div>';
        $allTablesOk = false;
    }
}

if ($hasQuantity) {
    echo '<div class="success">✅ <strong>quantity</strong> column exists</div>';
} else {
    echo '<div class="error">❌ <strong>quantity</strong> column not found!</div>';
    $allTablesOk = false;
}

// Show sample items
echo '<h3>Sample Items (first 5 records)</h3>';
$sampleQuery = mysqli_query($con3, "SELECT * FROM phppos_items LIMIT 5");

if ($sampleQuery && mysqli_num_rows($sampleQuery) > 0) {
    echo '<table>';
    
    // Get column names
    $firstRow = mysqli_fetch_assoc($sampleQuery);
    echo '<thead><tr>';
    foreach (array_keys($firstRow) as $colName) {
        echo '<th>' . htmlspecialchars($colName) . '</th>';
    }
    echo '</tr></thead><tbody>';
    
    // Show first row
    echo '<tr>';
    foreach ($firstRow as $value) {
        echo '<td>' . htmlspecialchars(substr($value, 0, 50)) . '</td>';
    }
    echo '</tr>';
    
    // Show remaining rows
    while ($row = mysqli_fetch_assoc($sampleQuery)) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>' . htmlspecialchars(substr($value, 0, 50)) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</tbody></table>';
} else {
    echo '<div class="warning">⚠️ No items found in phppos_items table</div>';
}

// Final summary
echo "<h2>4. Summary & Recommendations</h2>";

if ($allTablesOk) {
    echo '<div class="success">';
    echo '<h3 style="margin-top:0;">✅ All Checks Passed!</h3>';
    echo '<p>The legacy database structure is compatible with the sync code. You can proceed with testing the order sync functionality.</p>';
    echo '<p><strong>Next Steps:</strong></p>';
    echo '<ol>';
    echo '<li>Place a test order with both purchase and rental items</li>';
    echo '<li>Check PHP error logs for sync results</li>';
    echo '<li>Verify data appears in the legacy tables</li>';
    echo '<li>Confirm quantity reduction in phppos_items</li>';
    echo '</ol>';
    echo '</div>';
} else {
    echo '<div class="error">';
    echo '<h3 style="margin-top:0;">❌ Issues Found</h3>';
    echo '<p>Some required tables or columns are missing. Please review the errors above and:</p>';
    echo '<ol>';
    echo '<li>Create missing tables using the appropriate SQL schema</li>';
    echo '<li>Add missing columns to existing tables</li>';
    echo '<li>Update the sync code if column names differ</li>';
    echo '</ol>';
    echo '<p>Refer to <code>LEGACY_DB_SYNC_TESTING.md</code> for detailed information about required table structures.</p>';
    echo '</div>';
}

// Connection info
echo "<h2>5. Connection Information</h2>";
echo '<div class="info">';
echo '<p><strong>Environment:</strong> ' . ($is_local ? 'Local Development' : 'Production') . '</p>';
echo '<p><strong>New Database ($con):</strong> u464193275_srishrinjewels</p>';
echo '<p><strong>Legacy Database ($con3):</strong> u464193275_srishringarr</p>';
echo '</div>';

mysqli_close($con3);
?>

    </div>
</body>
</html>
