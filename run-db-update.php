<?php
/**
 * Database Update Script
 * Run this once to add shipping_charge, coupon_code, and discount_amount columns to orders table
 * 
 * Access: http://localhost/ss/API/run-db-update.php
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Database Update Script</h2>";
echo "<p>Adding shipping and coupon columns to orders table...</p>";

// Check if columns already exist
$checkQuery = "SHOW COLUMNS FROM orders LIKE 'shipping_charge'";
$result = mysqli_query($con, $checkQuery);

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: orange;'>⚠️ Columns already exist. No update needed.</p>";
} else {
    // Add the columns
    $alterQuery = "ALTER TABLE orders 
                   ADD COLUMN shipping_charge DECIMAL(10,2) DEFAULT 0 AFTER total_amount,
                   ADD COLUMN coupon_code VARCHAR(50) DEFAULT NULL AFTER shipping_charge,
                   ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0 AFTER coupon_code";
    
    if (mysqli_query($con, $alterQuery)) {
        echo "<p style='color: green;'>✅ Successfully added columns:</p>";
        echo "<ul>";
        echo "<li>shipping_charge (DECIMAL)</li>";
        echo "<li>coupon_code (VARCHAR)</li>";
        echo "<li>discount_amount (DECIMAL)</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . mysqli_error($con) . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Delete this file (run-db-update.php) for security</li>";
echo "<li>Test the checkout process to ensure shipping and coupons are saved</li>";
echo "<li>Check order history to see shipping and coupon details</li>";
echo "</ol>";

mysqli_close($con);
?>
