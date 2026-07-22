<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if (isset($_SESSION['userid']) && $_SESSION['userid']) {
    $userId = (int) $_SESSION['userid'];
    $query = mysqli_query($con, "SELECT * FROM Registration WHERE registration_id = $userId");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        echo json_encode([
            'status' => 'success',
            'loggedIn' => true,
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
        echo json_encode(['status' => 'success', 'loggedIn' => false]);
    }
} else {
    echo json_encode(['status' => 'success', 'loggedIn' => false]);
}

