-- ============================================
-- Export Table Structures from Legacy Database
-- ============================================
-- Run this in phpMyAdmin SQL tab after selecting u464193275_srishringarr database
-- Copy the results and share them
-- ============================================

USE u464193275_srishringarr;

-- Show all tables
SELECT '=== ALL TABLES IN DATABASE ===' as info;
SHOW TABLES;

-- ============================================
-- APPROVAL TABLE
-- ============================================
SELECT '' as space;
SELECT '========================================' as info;
SELECT 'TABLE: approval' as info;
SELECT '========================================' as info;
DESCRIBE approval;

SELECT '' as space;
SELECT 'Sample data from approval:' as info;
SELECT * FROM approval LIMIT 1;

-- ============================================
-- APPROVAL_DETAIL TABLE
-- ============================================
SELECT '' as space;
SELECT '========================================' as info;
SELECT 'TABLE: approval_detail' as info;
SELECT '========================================' as info;
DESCRIBE approval_detail;

SELECT '' as space;
SELECT 'Sample data from approval_detail:' as info;
SELECT * FROM approval_detail LIMIT 1;

-- ============================================
-- PHPPOS_RENT TABLE
-- ============================================
SELECT '' as space;
SELECT '========================================' as info;
SELECT 'TABLE: phppos_rent' as info;
SELECT '========================================' as info;
DESCRIBE phppos_rent;

SELECT '' as space;
SELECT 'Sample data from phppos_rent:' as info;
SELECT * FROM phppos_rent LIMIT 1;

-- ============================================
-- ORDER_DETAIL TABLE
-- ============================================
SELECT '' as space;
SELECT '========================================' as info;
SELECT 'TABLE: order_detail' as info;
SELECT '========================================' as info;
DESCRIBE order_detail;

SELECT '' as space;
SELECT 'Sample data from order_detail:' as info;
SELECT * FROM order_detail LIMIT 1;

-- ============================================
-- PHPPOS_ITEMS TABLE
-- ============================================
SELECT '' as space;
SELECT '========================================' as info;
SELECT 'TABLE: phppos_items' as info;
SELECT '========================================' as info;
DESCRIBE phppos_items;

SELECT '' as space;
SELECT 'Sample data from phppos_items:' as info;
SELECT * FROM phppos_items LIMIT 1;

-- ============================================
-- SUMMARY
-- ============================================
SELECT '' as space;
SELECT '========================================' as info;
SELECT 'SUMMARY - Record Counts' as info;
SELECT '========================================' as info;

SELECT 'approval' as table_name, COUNT(*) as record_count FROM approval
UNION ALL
SELECT 'approval_detail', COUNT(*) FROM approval_detail
UNION ALL
SELECT 'phppos_rent', COUNT(*) FROM phppos_rent
UNION ALL
SELECT 'order_detail', COUNT(*) FROM order_detail
UNION ALL
SELECT 'phppos_items', COUNT(*) FROM phppos_items;

SELECT '' as space;
SELECT '========================================' as info;
SELECT 'INSTRUCTIONS' as info;
SELECT '========================================' as info;
SELECT 'Copy all the DESCRIBE results above and share them.' as instruction;
SELECT 'Focus on the Field column - those are the actual column names.' as instruction;
