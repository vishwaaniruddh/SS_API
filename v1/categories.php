<?php
require_once __DIR__ . '/../autoload.php';

use API\Controllers\CategoryController;

$controller = new CategoryController();
$controller->list();
