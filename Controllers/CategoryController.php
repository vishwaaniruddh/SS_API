<?php
namespace API\Controllers;

use API\Core\Controller;
use API\Services\CategoryService;

class CategoryController extends Controller {
    private $service;

    public function __construct() {
        $this->service = new CategoryService();
    }

    public function list() {
        try {
            $categories = $this->service->fetchCategories();
            $this->json([
                'status' => 'success',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function subcategories($type) {
        try {
            $data = $this->service->fetchSubcategories($type);
            $this->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
