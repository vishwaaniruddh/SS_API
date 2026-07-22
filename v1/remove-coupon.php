<?php
require_once __DIR__ . '/../config.php';
require_once '../autoload.php';

header('Content-Type: application/json');

global $con_reporting;
$controller = new \API\Controllers\CartController($con_reporting);
$response = $controller->removeCoupon();

echo json_encode($response);

