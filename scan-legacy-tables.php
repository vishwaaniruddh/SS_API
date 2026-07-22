<?php
/**
 * Scan Legacy Database Tables
 * 
 * This script scans the actual table structures in the legacy database
 * and outputs the exact column names and types.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

if (!$con3) {
    echo json_encode(['error' => 'Cannot connect to legacy database: ' . mysqli_connect_error()]);
    exit;
}

$tables = ['approval', 'approval_detail', 'phppos_rent', 'order_detail', 'phppos_items'];
$result = [];

foreach ($tables as $tableName) {
    // Check if table exists
    $checkTable = mysqli_query($con3, "SHOW TABLES LIKE '$tableName'");
    
    if (mysqli_num_rows($checkTable) == 0) {
        $result[$tableName] = ['exists' => false, 'message' => 'Table does not exist'];
        continue;
    }
    
    // Get table structure
    $describeQuery = mysqli_query($con3, "DESCRIBE $tableName");
    $columns = [];
    
    while ($col = mysqli_fetch_assoc($describeQuery)) {
        $columns[] = [
            'Field' => $col['Field'],
            'Type' => $col['Type'],
            'Null' => $col['Null'],
            'Key' => $col['Key'],
            'Default' => $col['Default'],
            'Extra' => $col['Extra']
        ];
    }
    
    // Get sample data
    $sampleQuery = mysqli_query($con3, "SELECT * FROM $tableName LIMIT 1");
    $sample = mysqli_fetch_assoc($sampleQuery);
    
    $result[$tableName] = [
        'exists' => true,
        'columns' => $columns,
        'sample_data' => $sample ? array_keys($sample) : []
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT);

mysqli_close($con3);
?>
