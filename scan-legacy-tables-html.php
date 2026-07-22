<?php
/**
 * Scan Legacy Database Tables - HTML Output
 * 
 * This script scans the actual table structures in the legacy database
 * and outputs the exact column names and types in a readable format.
 * 
 * Access via: http://localhost/ss/API/scan-legacy-tables-html.php
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legacy Database Table Scanner</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
            background: #1a1a1a;
            color: #00ff00;
        }
        h1 { color: #00ff00; border-bottom: 2px solid #00ff00; padding-bottom: 10px; }
        h2 { color: #ffff00; margin-top: 30px; }
        .table-container {
            background: #0a0a0a;
            border: 1px solid #00ff00;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #333;
        }
        th {
            background: #003300;
            color: #00ff00;
            font-weight: bold;
        }
        td {
            background: #0a0a0a;
            color: #00cc00;
        }
        .error {
            color: #ff0000;
            background: #330000;
            padding: 10px;
            border: 1px solid #ff0000;
            border-radius: 5px;
        }
        .success {
            color: #00ff00;
            background: #003300;
            padding: 10px;
            border: 1px solid #00ff00;
            border-radius: 5px;
        }
        .warning {
            color: #ffff00;
            background: #333300;
            padding: 10px;
            border: 1px solid #ffff00;
            border-radius: 5px;
        }
        .key { color: #ff00ff; font-weight: bold; }
        .type { color: #00ffff; }
        code {
            background: #003300;
            padding: 2px 6px;
            border-radius: 3px;
            color: #00ff00;
        }
        pre {
            background: #0a0a0a;
            border: 1px solid #00ff00;
            padding: 15px;
            overflow-x: auto;
            color: #00ff00;
        }
    </style>
</head>
<body>
    <h1>🔍 LEGACY DATABASE TABLE SCANNER</h1>
    <p>Database: <code>u464193275_srishringarr</code></p>
    <p>Environment: <code><?= $is_local ? 'LOCAL' : 'PRODUCTION' ?></code></p>

<?php

if (!$con3) {
    echo '<div class="error">❌ Cannot connect to legacy database: ' . mysqli_connect_error() . '</div>';
    exit;
}

echo '<div class="success">✅ Connected to legacy database successfully!</div>';

$tables = ['approval', 'approval_detail', 'phppos_rent', 'order_detail', 'phppos_items'];

foreach ($tables as $tableName) {
    echo "<div class='table-container'>";
    echo "<h2>📋 TABLE: $tableName</h2>";
    
    // Check if table exists
    $checkTable = mysqli_query($con3, "SHOW TABLES LIKE '$tableName'");
    
    if (mysqli_num_rows($checkTable) == 0) {
        echo "<div class='error'>❌ Table does not exist!</div>";
        echo "</div>";
        continue;
    }
    
    echo "<div class='success'>✅ Table exists</div>";
    
    // Get table structure
    $describeQuery = mysqli_query($con3, "DESCRIBE $tableName");
    
    echo "<h3>Column Structure:</h3>";
    echo "<table>";
    echo "<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
    echo "<tbody>";
    
    $columnNames = [];
    while ($col = mysqli_fetch_assoc($describeQuery)) {
        $columnNames[] = $col['Field'];
        echo "<tr>";
        echo "<td class='key'>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td class='type'>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    // Show column names as array for easy copy-paste
    echo "<h3>Column Names (for code):</h3>";
    echo "<pre>";
    echo "[\n";
    foreach ($columnNames as $colName) {
        echo "    '$colName',\n";
    }
    echo "]";
    echo "</pre>";
    
    // Get row count
    $countQuery = mysqli_query($con3, "SELECT COUNT(*) as cnt FROM $tableName");
    $countResult = mysqli_fetch_assoc($countQuery);
    echo "<p>📊 <strong>Total Records:</strong> " . number_format($countResult['cnt']) . "</p>";
    
    // Show sample data
    $sampleQuery = mysqli_query($con3, "SELECT * FROM $tableName LIMIT 1");
    if ($sampleQuery && mysqli_num_rows($sampleQuery) > 0) {
        echo "<h3>Sample Record:</h3>";
        echo "<table>";
        echo "<thead><tr><th>Column</th><th>Value</th></tr></thead>";
        echo "<tbody>";
        
        $sample = mysqli_fetch_assoc($sampleQuery);
        foreach ($sample as $key => $value) {
            echo "<tr>";
            echo "<td class='key'>" . htmlspecialchars($key) . "</td>";
            echo "<td>" . htmlspecialchars(substr($value ?? 'NULL', 0, 100)) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table>";
    } else {
        echo "<div class='warning'>⚠️ No sample data available (table is empty)</div>";
    }
    
    echo "</div>";
}

mysqli_close($con3);
?>

    <h2>📝 INSTRUCTIONS</h2>
    <div class="table-container">
        <p>Use the column names shown above to update the INSERT queries in <code>sync-legacy-database.php</code></p>
        <p>Make sure the column names in the code match exactly with the actual database columns.</p>
        <p>Pay special attention to:</p>
        <ul>
            <li>Customer name fields (might be split into first_name/last_name or combined)</li>
            <li>Date fields (pick_date, delivery_date, etc.)</li>
            <li>Item identifier in phppos_items (item_id, sku, product_code, etc.)</li>
        </ul>
    </div>

</body>
</html>
