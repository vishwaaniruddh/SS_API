<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
    exit;
}

$email = mysqli_real_escape_string($con, $input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
    exit;
}

// Use lowercase 'email' to match actual DB column name
$query = mysqli_query($con, "SELECT * FROM Registration WHERE email = '$email' AND Password = '$password'");

if ($query && mysqli_num_rows($query) > 0) {
    $user = mysqli_fetch_assoc($query);
    $_SESSION['userid'] = $user['registration_id'];
    $_SESSION['fname'] = $user['Firstname'];
    $_SESSION['lname'] = $user['Lastname'];
    $_SESSION['email'] = $user['email'];

    echo json_encode([
        'status' => 'success',
        'user' => [
            'id' => $user['registration_id'],
            'firstName' => $user['Firstname'] ?? '',
            'lastName' => $user['Lastname'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['Mobile'] ?? '',
            'gender' => $user['Gender'] ?? '',
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
}

