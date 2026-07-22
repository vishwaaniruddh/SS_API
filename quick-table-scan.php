<?php
// Quick table structure scan - outputs raw SQL results
require_once __DIR__ . '/config.php';

if (!$con3) {
    die("Cannot connect: " . mysqli_connect_error());
}

echo "=== LEGACY DATABASE TABLE STRUCTURES ===\n\n";

$tables = ['approval', 'approval_detail', 'phppos_rent', 'order_detail', 'phppos_items'];

foreach ($tables as $table) {
    echo "\n========================================\n";
    echo "TABLE: $table\n";
    echo "========================================\n";
    
    $result = mysqli_query($con3, "DESCRIBE $table");
    
    if (!$result) {
        echo "ERROR: " . mysqli_error($con3) . "\n";
        continue;
    }
    
    echo sprintf("%-30s %-20s %-8s %-8s\n", "COLUMN", "TYPE", "NULL", "KEY");
    echo str_repeat("-", 70) . "\n";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo sprintf("%-30s %-20s %-8s %-8s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key']
        );
    }
    
    // Get count
    $countResult = mysqli_query($con3, "SELECT COUNT(*) as cnt FROM $table");
    $count = mysqli_fetch_assoc($countResult);
    echo "\nTotal records: " . $count['cnt'] . "\n";
}

mysqli_close($con3);
?>
