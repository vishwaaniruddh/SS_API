<?php
namespace API\Services;

use API\Models\ProductModel;

class ProductService {
    private $model;

    public function __construct() {
        $this->model = new ProductModel();
    }

    public function fetchProducts($params) {
        $products = $this->model->getProducts($params);
        $total = $this->model->getTotalCount($params);
        
        return [
            'products' => $products,
            'pagination' => [
                'total' => $total,
                'page' => (int)($params['page'] ?? 1),
                'limit' => (int)($params['limit'] ?? 20),
                'total_pages' => ceil($total / ($params['limit'] ?? 20))
            ]
        ];
    }
    public function getProductDetail($id, $type) {
        return $this->model->getProductById($id, $type);
    }
    public function getProductDetailBySku($sku) {
        return $this->model->getProductBySku($sku);
    }
}
