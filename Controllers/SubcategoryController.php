<?php
namespace API\Controllers;

use API\Core\Controller;
use API\Services\SubcategoryService;

class SubcategoryController extends Controller {
    private $service;

    public function __construct() {
        $this->service = new SubcategoryService();
    }

    public function list() {
        $cat = $_GET['cat'] ?? '';
        if (empty($cat)) {
            $this->json(['status' => 'error', 'message' => 'Missing category parameter'], 400);
        }

        try {
            $data = $this->service->fetchSubcategories($cat);
            $this->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
