<?php
namespace API\Controllers;

use API\Core\Controller;
use API\Services\ProductService;

class ProductController extends Controller {
    private $service;

    public function __construct() {
        $this->service = new ProductService();
    }

    public function products() {
        $params = [
            'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
            'search' => isset($_GET['search']) ? $_GET['search'] : '',
            'category' => isset($_GET['category']) ? $_GET['category'] : '',
            'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 20,
            'min_price' => isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0,
            'max_price' => isset($_GET['max_price']) ? (float)$_GET['max_price'] : 1000000,
            'featured' => isset($_GET['featured']) ? (bool)$_GET['featured'] : false,
            'sort' => isset($_GET['sort']) ? $_GET['sort'] : 'sku_desc',
            'type' => isset($_GET['type']) ? $_GET['type'] : ''
        ];

        try {
            $data = $this->service->fetchProducts($params);
            $this->json([
                'status' => 'success',
                'data' => $data['products'],
                'pagination' => $data['pagination']
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function featured() {
        $type = $_GET['type'] ?? 'all';
        $apiType = '';
        if ($type === 'Jewel') $apiType = 'jewellery';
        elseif ($type === 'Apparel') $apiType = 'garments';

        $params = [
            'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
            'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 20,
            'featured' => true,
            'type' => $apiType
        ];

        try {
            $data = $this->service->fetchProducts($params);
            $this->json([
                'status' => 'success',
                'data' => $data['products'],
                'pagination' => $data['pagination']
            ]);
        } catch (\Exception $e) {
            $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function productDetail() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $type = isset($_GET['type']) ? $_GET['type'] : '';

        if (!$id || !$type) {
            $this->json(['status' => 'error', 'message' => 'Invalid product parameters'], 400);
            return;
        }

        try {
            $data = $this->service->getProductDetail($id, $type);
            if ($data) {
                $this->json(['status' => 'success', 'data' => $data]);
            } else {
                $this->json(['status' => 'error', 'message' => 'Product not found'], 404);
            }
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function productDetailBySku() {
        $sku = isset($_GET['sku']) ? trim($_GET['sku']) : '';

        if (empty($sku)) {
            $this->json(['status' => 'error', 'message' => 'Invalid or missing SKU parameter'], 400);
            return;
        }

        try {
            $data = $this->service->getProductDetailBySku($sku);
            if ($data) {
                $this->json(['status' => 'success', 'data' => $data]);
            } else {
                $this->json(['status' => 'error', 'message' => "Product with SKU '$sku' not found"], 404);
            }
        } catch (\Exception $e) {
            $this->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
