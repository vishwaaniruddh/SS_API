<?php
namespace API\Services;

use API\Models\CategoryModel;

class CategoryService {
    private $model;

    public function __construct() {
        $this->model = new CategoryModel();
    }

    public function fetchCategories() {
        return $this->model->getAllCategories();
    }

    public function fetchSubcategories($typeParam) {
        $prefix = '';
        $actualId = '';

        if (strpos($typeParam, ':') !== false) {
            list($prefix, $actualId) = explode(':', $typeParam);
        } else {
            $prefix = $typeParam;
        }

        // Standardize legacy types
        if ($prefix == '1') $prefix = 'jewel_main_list';
        if ($prefix == '2' || $prefix == 'bridal') $prefix = 'garment_list';

        if ($prefix == 'garment_list') {
            $rawItems = $this->model->getGarmentList();
            $items = [];
            foreach ($rawItems as $item) {
                $image = $this->getGarmentImage($item['name'], $item['image']);
                $items[] = [
                    'name' => $item['name'],
                    'image' => $this->resolveImagePath($image),
                    'url' => "category.php?type=garment:" . $item['id']
                ];
            }
            return ['title' => 'Bridal & Occasion Wear', 'items' => $items, 'context' => 'Apparel'];
        } else if ($prefix == 'jewel_main_list') {
            $rawItems = $this->model->getJewelMainList();
            $items = [];
            foreach ($rawItems as $item) {
                $hasSubs = $this->model->hasDistinctSubCategories($item['subcat_id']);
                $image = $item['image'];
                if (empty($image)) {
                    $subs = $this->model->getSubCategories($item['subcat_id']);
                    if (!empty($subs)) $image = $subs[0]['image'];
                }
                
                $items[] = [
                    'name' => $item['name'],
                    'image' => $this->resolveImagePath($image),
                    'url' => ($hasSubs ? "sub_category.php?type=jewel_main:" : "category.php?type=jewel_main:") . $item['subcat_id']
                ];
            }
            return ['title' => 'Jewellery Collections', 'items' => $items, 'context' => 'Jewellery'];
        } else if ($prefix == 'jewel_main') {
            $title = $this->model->getCategoryName($prefix . ":" . $actualId);
            $rawSubs = $this->model->getSubCategories($actualId);
            $items = [];
            foreach ($rawSubs as $sub) {
                $items[] = [
                    'name' => $sub['name'],
                    'image' => $this->resolveImagePath($sub['image']),
                    'url' => "category.php?type=jewel_sub:" . $sub['id']
                ];
            }
            return ['title' => $title, 'items' => $items, 'context' => 'Jewellery'];
        } else if ($prefix == 'garment') {
            $title = $this->model->getCategoryName($prefix . ":" . $actualId);
            $items = [[
                'name' => $title,
                'image' => '',
                'url' => "category.php?type=garment:" . $actualId
            ]];
            return ['title' => $title, 'items' => $items, 'context' => 'Apparel'];
        }

        return ['title' => 'Our Collections', 'items' => [], 'context' => 'Collections'];
    }

    private function getGarmentImage($name, $defaultImage) {
        $name = ucwords(strtolower(trim($name)));
        $specialImages = [
            'Lehenga Choli' => 'https://srishringarr.com/yn/uploads/2021/12/16398326820.jpg',
            'Indo Western Outfits' => 'https://srishringarr.com/yn/uploads/2021/12/16402678470.jpg',
            'Evening Gowns' => 'https://srishringarr.com/yn/uploads/2021/12/16393124560.jpg',
            'Trail Gowns / Infinity Gowns' => 'https://srishringarr.com/yn/uploads/2020/07/15954172634.jpg'
        ];

        return $specialImages[$name] ?? $defaultImage;
    }

    private function resolveImagePath($image) {
        if (empty($image)) return '';
        if (strpos($image, 'http') === 0) return $image;

        $image = ltrim($image, '/');
        if (strpos($image, 'yn/') === 0) {
            $image = substr($image, 3);
        }
        return 'https://srishringarr.com/yn/' . $image;
    }
}
