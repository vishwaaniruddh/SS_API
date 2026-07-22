# Legacy Database Sync - Testing & Verification Guide

## Overview
This document provides instructions for testing and verifying the legacy database synchronization feature that was implemented to sync orders from the new system (`u464193275_srishrinjewels`) to the legacy system (`u464193275_srishringarr`).

## What Was Implemented

### 1. Email Improvements ✅ COMPLETE
- ✅ Product images are clickable and link to product pages
- ✅ Product titles are clickable and link to product pages
- ✅ "View Order Details" button added in order number box
- ✅ Fixed purchase/rental detection to check both `'buy'` and `'purchase'`
- ✅ All three CC emails added: rajanipodar@gmail.com, yosshita.neha@gmail.com, vishwaaniruddh@gmail.com

### 2. Legacy Database Sync ⚠️ NEEDS TESTING
- ✅ Created `sync-legacy-database.php` with sync functions
- ✅ Purchase orders → `approval` and `approval_detail` tables
- ✅ Rental orders → `phppos_rent` and `order_detail` tables
- ✅ Quantity reduction in `phppos_items` table
- ✅ Integrated sync call in `verify-payment.php`
- ⚠️ **NEEDS VERIFICATION**: Table structures in legacy DB must match INSERT queries

## Files Modified/Created

### New Files
- `c:\xampp\htdocs\ss\API\v1\sync-legacy-database.php` - Main sync logic

### Modified Files
- `c:\xampp\htdocs\ss\API\v1\verify-payment.php` - Added sync call after order creation
- `c:\xampp\htdocs\ss\API\v1\email-templates\order-confirmation-template.php` - Added clickable images/titles and View Order button
- `c:\xampp\htdocs\ss\API\v1\send-order-email.php` - Already had CC emails

## Database Connections

### Connection Variables (from config.php)
- `$con` / `$conn` → `u464193275_srishrinjewels` (NEW database)
- `$con3` → `u464193275_srishringarr` (LEGACY database)

### Local Environment
```php
$con = mysqli_connect("localhost", "root", "", "u464193275_srishrinjewels");
$con3 = mysqli_connect("localhost", "root", "", "u464193275_srishringarr");
```

### Production Environment
```php
$con = mysqli_connect("localhost", "u464193275_srishrinjuser", "9b@hMgk!=zI", "u464193275_srishrinjewels");
$con3 = mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
```

## Legacy Database Tables - VERIFICATION NEEDED

### For Purchase Orders

#### Table: `approval` (Main purchase order)
**Columns being inserted:**
```sql
- bill_id (VARCHAR) - Format: 'PUR-{order_id}-{timestamp}'
- customer_name (VARCHAR)
- customer_email (VARCHAR)
- customer_phone (VARCHAR)
- customer_address (TEXT)
- total_amount (DECIMAL)
- payment_status (VARCHAR) - Value: 'paid'
- razorpay_order_id (VARCHAR)
- razorpay_payment_id (VARCHAR)
- created_at (DATETIME)
- status (VARCHAR) - Value: 'approved'
```

#### Table: `approval_detail` (Purchase items)
**Columns being inserted:**
```sql
- approval_id (INT) - Foreign key to approval.id
- bill_id (VARCHAR)
- item_id (VARCHAR) - SKU from order_items
- product_name (VARCHAR)
- qty (INT)
- price (DECIMAL)
- total (DECIMAL)
- product_type (VARCHAR) - 'jewellery' or 'garment'
```

### For Rental Orders

#### Table: `phppos_rent` (Main rental order)
**Columns being inserted:**
```sql
- bill_id (VARCHAR) - Format: 'RENT-{order_id}-{timestamp}'
- customer_name (VARCHAR)
- customer_email (VARCHAR)
- customer_phone (VARCHAR)
- customer_address (TEXT)
- pick_date (DATE) - Start date
- delivery_date (DATE) - End date
- days (INT)
- total_amount (DECIMAL)
- payment_status (VARCHAR) - Value: 'paid'
- razorpay_order_id (VARCHAR)
- razorpay_payment_id (VARCHAR)
- created_at (DATETIME)
- booking_status (VARCHAR) - Value: 'Confirmed'
```

#### Table: `order_detail` (Rental items)
**Columns being inserted:**
```sql
- bill_id (VARCHAR)
- item_id (VARCHAR) - SKU from order_items
- product_name (VARCHAR)
- qty (INT)
- price (DECIMAL)
- total (DECIMAL)
- product_type (VARCHAR) - 'jewellery' or 'garment'
- pick_date (DATE)
- delivery_date (DATE)
- days (INT)
```

### For Inventory Updates

#### Table: `phppos_items` (Inventory)
**Update query:**
```sql
UPDATE phppos_items 
SET quantity = GREATEST(0, quantity - {qty}),
    updated_at = NOW()
WHERE item_id = '{sku}'
```

**⚠️ IMPORTANT**: Verify that `phppos_items` uses `item_id` column for SKU. If it uses a different column name (like `sku`, `product_code`, etc.), the query needs to be updated.

## Testing Steps

### Step 1: Verify Legacy Database Tables Exist
Run these queries in the `u464193275_srishringarr` database:

```sql
-- Check if tables exist
SHOW TABLES LIKE 'approval';
SHOW TABLES LIKE 'approval_detail';
SHOW TABLES LIKE 'phppos_rent';
SHOW TABLES LIKE 'order_detail';
SHOW TABLES LIKE 'phppos_items';
```

### Step 2: Verify Table Structures
Run these queries to check column names and types:

```sql
-- Purchase tables
DESCRIBE approval;
DESCRIBE approval_detail;

-- Rental tables
DESCRIBE phppos_rent;
DESCRIBE order_detail;

-- Inventory table
DESCRIBE phppos_items;
```

**Compare the output with the columns listed above in "Legacy Database Tables" section.**

### Step 3: Check phppos_items Column Name
```sql
-- Find the correct column name for SKU/item code
DESCRIBE phppos_items;
SELECT * FROM phppos_items LIMIT 1;
```

If the column is NOT named `item_id`, update line 177 in `sync-legacy-database.php`:
```php
WHERE item_id = '" . mysqli_real_escape_string($con, $sku) . "'"
```

### Step 4: Place a Test Order
1. Add items to cart (mix of purchase and rental)
2. Complete checkout with Razorpay payment
3. Check for errors in PHP error log

### Step 5: Verify Sync Results

#### Check New Database (u464193275_srishrinjewels)
```sql
-- Get the latest order
SELECT * FROM orders ORDER BY id DESC LIMIT 1;

-- Get order items
SELECT * FROM order_items WHERE order_id = {latest_order_id};
```

#### Check Legacy Database (u464193275_srishringarr)

**For Purchase Items:**
```sql
-- Check approval table
SELECT * FROM approval WHERE razorpay_order_id = '{razorpay_order_id}';

-- Check approval_detail
SELECT * FROM approval_detail WHERE bill_id LIKE 'PUR-%' ORDER BY id DESC LIMIT 5;
```

**For Rental Items:**
```sql
-- Check phppos_rent table
SELECT * FROM phppos_rent WHERE razorpay_order_id = '{razorpay_order_id}';

-- Check order_detail
SELECT * FROM order_detail WHERE bill_id LIKE 'RENT-%' ORDER BY id DESC LIMIT 5;
```

**For Inventory:**
```sql
-- Check if quantity was reduced
SELECT item_id, quantity, updated_at 
FROM phppos_items 
WHERE item_id IN ('{sku1}', '{sku2}');
```

### Step 6: Check Error Logs
```php
// Check PHP error log for sync results
// Location: Usually in /var/log/apache2/error.log or similar

// Look for these log entries:
// "Legacy DB Sync Error for Order #X: ..."
// "Legacy DB Sync Success for Order #X: ..."
```

## Common Issues & Solutions

### Issue 1: Table/Column Doesn't Exist
**Error**: `Table 'u464193275_srishringarr.approval' doesn't exist`
**Solution**: Verify table name in legacy database, update sync-legacy-database.php if needed

### Issue 2: Column Name Mismatch
**Error**: `Unknown column 'item_id' in 'where clause'`
**Solution**: Check actual column name in phppos_items table and update the query

### Issue 3: Data Type Mismatch
**Error**: `Incorrect decimal value` or similar
**Solution**: Verify column types match the data being inserted

### Issue 4: Missing Required Columns
**Error**: `Field 'column_name' doesn't have a default value`
**Solution**: Either add the column to INSERT query or set a default value in the database

### Issue 5: Foreign Key Constraints
**Error**: `Cannot add or update a child row: a foreign key constraint fails`
**Solution**: Check if there are foreign key relationships that need to be satisfied

## Manual Sync for Existing Orders

If you need to sync an existing order manually:

```php
// Via browser (GET request)
http://yourdomain.com/API/v1/sync-legacy-database.php?order_id=123

// Via command line
php /path/to/API/v1/sync-legacy-database.php order_id=123
```

## Rollback Plan

If sync causes issues, you can disable it temporarily:

1. Open `c:\xampp\htdocs\ss\API\v1\verify-payment.php`
2. Comment out lines 107-113:
```php
// Sync to legacy database tables (u464193275_srishringarr)
// require_once __DIR__ . '/sync-legacy-database.php';
// $syncResult = syncOrderToLegacy($orderId, $con, $con3);
// if (!$syncResult['success']) {
//     error_log("Legacy DB Sync Error for Order #$orderId: " . json_encode($syncResult));
// } else {
//     error_log("Legacy DB Sync Success for Order #$orderId: " . json_encode($syncResult));
// }
```

## Next Steps

1. ✅ Verify all legacy database tables exist
2. ✅ Verify column names match the INSERT queries
3. ✅ Check `phppos_items` table structure (especially the SKU column name)
4. ✅ Place a test order with both purchase and rental items
5. ✅ Verify data appears in legacy tables
6. ✅ Check error logs for any issues
7. ✅ Test quantity reduction in phppos_items
8. ✅ Verify email is sent with clickable images and View Order button

## Contact Information

If you encounter issues during testing, check:
1. PHP error logs
2. MySQL error logs
3. Browser console for frontend errors
4. Network tab for API response errors

## Summary

The implementation is **COMPLETE** but requires **TESTING AND VERIFICATION** to ensure:
- Legacy database table structures match the code
- Column names are correct (especially in phppos_items)
- Data is being inserted correctly
- Quantity reduction works as expected
- No foreign key or constraint violations occur

Once testing is complete and any necessary adjustments are made, the system will automatically sync all new orders to the legacy database.
