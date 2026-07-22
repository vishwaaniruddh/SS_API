<?php
require_once __DIR__ . '/../autoload.php';

use API\Controllers\CategoryController;

$type = $_GET['type'] ?? '';

$controller = new CategoryController();
$controller->subcategories($type);
