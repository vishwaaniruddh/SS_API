<?php
namespace API\Services;

use API\Models\SubcategoryModel;

class SubcategoryService {
    private $model;

    public function __construct() {
        $this->model = new SubcategoryModel();
    }

    public function fetchSubcategories($cat) {
        return $this->model->getSubcategories($cat);
    }
}
