<?php
require_once __DIR__ . '/../autoload.php';

use API\Controllers\ProductController;

$controller = new ProductController();
$controller->featured();
