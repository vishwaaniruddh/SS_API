<?php
require_once __DIR__ . '/config.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check phppos_people Table</title>
    <style>
        body { font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #00ff00; padding: 8px; text-align: left; }
        th { background: #003300; }
        h2 { color: #ffff00; }
    </style>
</head>
<body>
    <h1>phppos_people Table Structure</h1>
    
    <?php
    if (!$con3) {
        echo "<p style='color:red;'>Cannot connect to legacy database</p>";
        exit;
    }
    
    echo "<h2>Table Structure:</h2>";
    $describe = mysqli_query($con3, "DESCRIBE phppos_people");
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($describe)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Sample Records (first 3):</h2>";
    $sample = mysqli_query($con3, "SELECT * FROM phppos_people LIMIT 3");
    if ($sample && mysqli_num_rows($sample) > 0) {
        echo "<table>";
        $first = true;
        while ($row = mysqli_fetch_assoc($sample)) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $col) {
                    echo "<th>$col</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $val) {
                echo "<td>" . htmlspecialchars(substr($val ?? '', 0, 50)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>Registration Table (New DB):</h2>";
    $regDescribe = mysqli_query($con, "DESCRIBE Registration");
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($regDescribe)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Sample Registration Records (first 3):</h2>";
    $regSample = mysqli_query($con, "SELECT * FROM Registration LIMIT 3");
    if ($regSample && mysqli_num_rows($regSample) > 0) {
        echo "<table>";
        $first = true;
        while ($row = mysqli_fetch_assoc($regSample)) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $col) {
                    echo "<th>$col</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $val) {
                echo "<td>" . htmlspecialchars(substr($val ?? '', 0, 50)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    mysqli_close($con);
    mysqli_close($con3);
    ?>
</body>
</html>
