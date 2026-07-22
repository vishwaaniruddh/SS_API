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

$fname = mysqli_real_escape_string($con, $input['firstName'] ?? '');
$lname = mysqli_real_escape_string($con, $input['lastName'] ?? '');
$email = mysqli_real_escape_string($con, $input['email'] ?? '');
$phone = mysqli_real_escape_string($con, $input['phone'] ?? '');
$password = $input['password'] ?? '';

if (empty($fname) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'First name, email and password are required']);
    exit;
}

// Check if email already exists
$check = mysqli_query($con, "SELECT registration_id FROM Registration WHERE Email = '$email' AND Password != ''");
if ($check && mysqli_num_rows($check) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'An account with this email already exists']);
    exit;
}

$escapedPassword = mysqli_real_escape_string($con, $password);

// Check if guest record exists with this email, update it; otherwise insert new
$guestCheck = mysqli_query($con, "SELECT registration_id FROM Registration WHERE Email = '$email' AND (Password = '' OR Password IS NULL)");
if ($guestCheck && mysqli_num_rows($guestCheck) > 0) {
    $guest = mysqli_fetch_assoc($guestCheck);
    $userId = $guest['registration_id'];
    $query = "UPDATE Registration SET Firstname='$fname', Lastname='$lname', Mobile='$phone', Password='$escapedPassword' WHERE registration_id=$userId";
    mysqli_query($con, $query);
} else {
    $query = "INSERT INTO Registration (Firstname, Lastname, Email, Mobile, Password) VALUES ('$fname', '$lname', '$email', '$phone', '$escapedPassword')";
    mysqli_query($con, $query);
    $userId = mysqli_insert_id($con);
}

$_SESSION['userid'] = $userId;
$_SESSION['fname'] = $fname;
$_SESSION['lname'] = $lname;
$_SESSION['email'] = $email;

echo json_encode([
    'status' => 'success',
    'user' => [
        'id' => $userId,
        'firstName' => $fname,
        'lastName' => $lname,
        'email' => $email,
        'phone' => $phone,
        'gender' => '',
    ]
]);

