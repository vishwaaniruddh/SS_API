-- ============================================
-- Legacy Database Structure Verification
-- Database: u464193275_srishringarr
-- ============================================

-- Run this script in the u464193275_srishringarr database
-- to verify table structures match the sync code

USE u464193275_srishringarr;

-- ============================================
-- 1. CHECK IF TABLES EXIST
-- ============================================

SELECT 'Checking if tables exist...' AS status;

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✓ EXISTS' ELSE '✗ MISSING' END AS approval_table
FROM information_schema.tables 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'approval';

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✓ EXISTS' ELSE '✗ MISSING' END AS approval_detail_table
FROM information_schema.tables 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'approval_detail';

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✓ EXISTS' ELSE '✗ MISSING' END AS phppos_rent_table
FROM information_schema.tables 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'phppos_rent';

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✓ EXISTS' ELSE '✗ MISSING' END AS order_detail_table
FROM information_schema.tables 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'order_detail';

SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✓ EXISTS' ELSE '✗ MISSING' END AS phppos_items_table
FROM information_schema.tables 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'phppos_items';

-- ============================================
-- 2. VERIFY APPROVAL TABLE STRUCTURE
-- ============================================

SELECT 'Checking approval table structure...' AS status;

DESCRIBE approval;

-- Check for required columns
SELECT 
    CASE WHEN COUNT(*) = 11 THEN '✓ ALL COLUMNS PRESENT' ELSE '✗ MISSING COLUMNS' END AS approval_columns
FROM information_schema.columns 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'approval'
AND column_name IN (
    'bill_id', 'customer_name', 'customer_email', 'customer_phone', 
    'customer_address', 'total_amount', 'payment_status', 
    'razorpay_order_id', 'razorpay_payment_id', 'created_at', 'status'
);

-- ============================================
-- 3. VERIFY APPROVAL_DETAIL TABLE STRUCTURE
-- ============================================

SELECT 'Checking approval_detail table structure...' AS status;

DESCRIBE approval_detail;

-- Check for required columns
SELECT 
    CASE WHEN COUNT(*) = 8 THEN '✓ ALL COLUMNS PRESENT' ELSE '✗ MISSING COLUMNS' END AS approval_detail_columns
FROM information_schema.columns 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'approval_detail'
AND column_name IN (
    'approval_id', 'bill_id', 'item_id', 'product_name', 
    'qty', 'price', 'total', 'product_type'
);

-- ============================================
-- 4. VERIFY PHPPOS_RENT TABLE STRUCTURE
-- ============================================

SELECT 'Checking phppos_rent table structure...' AS status;

DESCRIBE phppos_rent;

-- Check for required columns
SELECT 
    CASE WHEN COUNT(*) = 14 THEN '✓ ALL COLUMNS PRESENT' ELSE '✗ MISSING COLUMNS' END AS phppos_rent_columns
FROM information_schema.columns 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'phppos_rent'
AND column_name IN (
    'bill_id', 'customer_name', 'customer_email', 'customer_phone', 
    'customer_address', 'pick_date', 'delivery_date', 'days',
    'total_amount', 'payment_status', 'razorpay_order_id', 
    'razorpay_payment_id', 'created_at', 'booking_status'
);

-- ============================================
-- 5. VERIFY ORDER_DETAIL TABLE STRUCTURE
-- ============================================

SELECT 'Checking order_detail table structure...' AS status;

DESCRIBE order_detail;

-- Check for required columns
SELECT 
    CASE WHEN COUNT(*) = 10 THEN '✓ ALL COLUMNS PRESENT' ELSE '✗ MISSING COLUMNS' END AS order_detail_columns
FROM information_schema.columns 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'order_detail'
AND column_name IN (
    'bill_id', 'item_id', 'product_name', 'qty', 'price', 
    'total', 'product_type', 'pick_date', 'delivery_date', 'days'
);

-- ============================================
-- 6. VERIFY PHPPOS_ITEMS TABLE STRUCTURE
-- ============================================

SELECT 'Checking phppos_items table structure...' AS status;

DESCRIBE phppos_items;

-- Check if item_id column exists (critical for quantity update)
SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✓ item_id COLUMN EXISTS' ELSE '✗ item_id COLUMN MISSING' END AS item_id_check
FROM information_schema.columns 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'phppos_items'
AND column_name = 'item_id';

-- Check if quantity column exists
SELECT 
    CASE WHEN COUNT(*) > 0 THEN '✓ quantity COLUMN EXISTS' ELSE '✗ quantity COLUMN MISSING' END AS quantity_check
FROM information_schema.columns 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'phppos_items'
AND column_name = 'quantity';

-- List all columns in phppos_items (to identify correct SKU column if item_id doesn't exist)
SELECT column_name, data_type, column_key
FROM information_schema.columns 
WHERE table_schema = 'u464193275_srishringarr' 
AND table_name = 'phppos_items'
ORDER BY ordinal_position;

-- ============================================
-- 7. SAMPLE DATA CHECK
-- ============================================

SELECT 'Checking for sample data...' AS status;

-- Check if there's any existing data
SELECT COUNT(*) AS approval_count FROM approval;
SELECT COUNT(*) AS approval_detail_count FROM approval_detail;
SELECT COUNT(*) AS phppos_rent_count FROM phppos_rent;
SELECT COUNT(*) AS order_detail_count FROM order_detail;
SELECT COUNT(*) AS phppos_items_count FROM phppos_items;

-- Show sample records if they exist
SELECT 'Sample approval records:' AS info;
SELECT * FROM approval ORDER BY id DESC LIMIT 3;

SELECT 'Sample phppos_rent records:' AS info;
SELECT * FROM phppos_rent ORDER BY id DESC LIMIT 3;

SELECT 'Sample phppos_items records:' AS info;
SELECT * FROM phppos_items LIMIT 3;

-- ============================================
-- 8. FOREIGN KEY CONSTRAINTS CHECK
-- ============================================

SELECT 'Checking foreign key constraints...' AS status;

SELECT 
    constraint_name,
    table_name,
    column_name,
    referenced_table_name,
    referenced_column_name
FROM information_schema.key_column_usage
WHERE table_schema = 'u464193275_srishringarr'
AND table_name IN ('approval', 'approval_detail', 'phppos_rent', 'order_detail')
AND referenced_table_name IS NOT NULL;

-- ============================================
-- SUMMARY
-- ============================================

SELECT '============================================' AS summary;
SELECT 'VERIFICATION COMPLETE' AS summary;
SELECT '============================================' AS summary;
SELECT 'Review the results above to ensure:' AS summary;
SELECT '1. All tables exist' AS summary;
SELECT '2. All required columns are present' AS summary;
SELECT '3. phppos_items has item_id and quantity columns' AS summary;
SELECT '4. Data types are compatible' AS summary;
SELECT '============================================' AS summary;
