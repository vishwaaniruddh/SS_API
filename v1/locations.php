<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


global $con;

if ($action === 'get_states') {
    $query = "SELECT id, name FROM states WHERE status = 'active' ORDER BY name ASC";
    $result = mysqli_query($con, $query);
    $states = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $states[] = $row;
    }
    echo json_encode($states);
} else if ($action === 'get_cities') {
    $stateId = (int)($_GET['state_id'] ?? 0);
    if (!$stateId) {
        echo json_encode([]);
        exit;
    }
    $query = "SELECT id, name FROM cities WHERE state_id = $stateId AND status = 'active' ORDER BY name ASC";
    $result = mysqli_query($con, $query);
    $cities = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cities[] = $row;
    }
    echo json_encode($cities);
} else {
    echo json_encode(['error' => 'Invalid action']);
}

