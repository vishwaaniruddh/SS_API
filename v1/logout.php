<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

unset($_SESSION['userid']);
unset($_SESSION['fname']);
unset($_SESSION['lname']);
unset($_SESSION['email']);

echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);

