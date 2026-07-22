<?php
require_once __DIR__ . '/../autoload.php';

use API\Controllers\ProductController;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$controller = new ProductController();
$controller->products();
