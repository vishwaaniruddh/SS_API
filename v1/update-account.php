<?php
require_once __DIR__ . '/../config.php';
require_once '../autoload.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$userId = $_SESSION['userid'];
$action = $_POST['action'] ?? '';

global $con;

if ($action === 'update_profile') {
    $fname = mysqli_real_escape_string($con, $_POST['fname'] ?? '');
    $lname = mysqli_real_escape_string($con, $_POST['lname'] ?? '');
    $mobile = mysqli_real_escape_string($con, $_POST['mobile'] ?? '');
    $gender = mysqli_real_escape_string($con, $_POST['gender'] ?? '');

    $query = "UPDATE Registration SET Firstname = '$fname', Lastname = '$lname', Mobile = '$mobile', Gender = '$gender' WHERE registration_id = $userId";
    if (mysqli_query($con, $query)) {
        $_SESSION['fname'] = $fname;
        $_SESSION['lname'] = $lname;
        echo json_encode(['status' => 'success', 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
} else if ($action === 'update_address') {
    $type = $_POST['type'] ?? ''; // 'billing' or 'shipping'
    $fname = mysqli_real_escape_string($con, $_POST['first_name'] ?? '');
    $lname = mysqli_real_escape_string($con, $_POST['last_name'] ?? '');
    $phone = mysqli_real_escape_string($con, $_POST['phone'] ?? '');
    $address = mysqli_real_escape_string($con, $_POST['address'] ?? '');
    $city = mysqli_real_escape_string($con, $_POST['city'] ?? '');
    $state = mysqli_real_escape_string($con, $_POST['state'] ?? '');
    $pincode = mysqli_real_escape_string($con, $_POST['pincode'] ?? '');

    $prefix = ($type === 'billing') ? 'billing_' : 'shipping_';
    $query = "UPDATE Registration SET 
                {$prefix}first_name = '$fname',
                {$prefix}last_name = '$lname',
                {$prefix}phone = '$phone',
                {$prefix}address = '$address', 
                {$prefix}city = '$city', 
                {$prefix}state = '$state', 
                {$prefix}pincode = '$pincode' 
              WHERE registration_id = $userId";

    if (mysqli_query($con, $query)) {
        echo json_encode(['status' => 'success', 'message' => ucfirst($type) . ' address updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
} else if ($action === 'add_alternate_address') {
    $fname = mysqli_real_escape_string($con, $_POST['first_name'] ?? '');
    $lname = mysqli_real_escape_string($con, $_POST['last_name'] ?? '');
    $type = mysqli_real_escape_string($con, $_POST['address_type'] ?? 'Other');
    $address = mysqli_real_escape_string($con, $_POST['address'] ?? '');
    $city = mysqli_real_escape_string($con, $_POST['city'] ?? '');
    $state = mysqli_real_escape_string($con, $_POST['state'] ?? '');
    $pincode = mysqli_real_escape_string($con, $_POST['pincode'] ?? '');
    $phone = mysqli_real_escape_string($con, $_POST['phone'] ?? '');

    $query = "INSERT INTO user_addresses (user_id, first_name, last_name, address_type, address, city, state, pincode, phone) 
              VALUES ('$userId', '$fname', '$lname', '$type', '$address', '$city', '$state', '$pincode', '$phone')";

    if (mysqli_query($con, $query)) {
        echo json_encode(['status' => 'success', 'message' => 'New address added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
} else if ($action === 'delete_address') {
    $addressId = (int) ($_POST['address_id'] ?? 0);
    $query = "DELETE FROM user_addresses WHERE id = $addressId AND user_id = $userId";

    if (mysqli_query($con, $query)) {
        echo json_encode(['status' => 'success', 'message' => 'Address deleted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}

