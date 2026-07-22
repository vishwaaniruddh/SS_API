<?php
namespace API\Services;

use API\Models\DiscountModel;

class DiscountService {
    private $model;
    private $rules = null;

    public function __construct() {
        $this->model = new DiscountModel();
    }

    public function applyDiscounts(&$product) {
        if ($this->rules === null) {
            $this->rules = $this->model->getRules();
        }

        if (empty($this->rules)) {
            return $product;
        }

        $original_price = (float)$product['details']['rent_price'];
        if (!$original_price) return $product;

        $product_id = $product['id'];
        $product_type = $product['type'];
        $cat_id = $product['cat_id'] ?? null;

        foreach ($this->rules as $rule) {
            $applied = false;
            $scope = $rule['scope'];

            if ($scope === 'global') {
                $applied = true;
            } elseif ($scope === 'product') {
                $targets = explode(',', $rule['target']);
                $target_id = $product_id . ':' . $product_type;
                if (in_array($target_id, $targets)) {
                    $applied = true;
                }
            } elseif ($scope === 'category') {
                $targets = explode(',', $rule['target']);
                if ($cat_id) {
                    $target_cat = $cat_id . ':' . ($product_type === 'jewellery' ? 'jewel_child' : 'garment');
                    if (in_array($target_cat, $targets)) {
                        $applied = true;
                    }
                }
            } elseif ($scope === 'price_gt' && $original_price > (float)$rule['threshold']) {
                $applied = true;
            } elseif ($scope === 'price_lt' && $original_price < (float)$rule['threshold']) {
                $applied = true;
            } elseif ($scope === 'price_between' && $original_price >= (float)$rule['threshold'] && $original_price <= (float)$rule['threshold_max']) {
                $applied = true;
            } elseif (strpos($scope, 'cat_price') !== false) {
                $targets = explode(',', $rule['target']);
                $target_cat = $cat_id . ':' . ($product_type === 'jewellery' ? 'jewel_child' : 'garment');
                
                if (in_array($target_cat, $targets)) {
                    if ($scope === 'cat_price_gt' && $original_price > (float)$rule['threshold']) {
                        $applied = true;
                    } elseif ($scope === 'cat_price_lt' && $original_price < (float)$rule['threshold']) {
                        $applied = true;
                    } elseif ($scope === 'cat_price_between' && $original_price >= (float)$rule['threshold'] && $original_price <= (float)$rule['threshold_max']) {
                        $applied = true;
                    }
                }
            }

            if ($applied) {
                $discount_value = (float)$rule['value'];
                $discount_type = $rule['type'];

                if ($discount_type === 'percentage') {
                    $discount_amount = ($original_price * $discount_value) / 100;
                } else {
                    $discount_amount = $discount_value;
                }

                $product['details']['discounted_rent'] = max(0, $original_price - $discount_amount);
                $product['details']['discount_label'] = ($discount_type === 'percentage') ? ($discount_value . '% OFF') : ('₹' . $discount_value . ' OFF');
                $product['details']['has_discount'] = true;
                
                // For selling price too? 
                // Let's also apply to selling price if present
                if (isset($product['details']['sale_price'])) {
                    $original_sale = (float)$product['details']['sale_price'];
                    if ($discount_type === 'percentage') {
                        $sale_discount = ($original_sale * $discount_value) / 100;
                    } else {
                        $sale_discount = $discount_value;
                    }
                    $product['details']['discounted_sale'] = max(0, $original_sale - $sale_discount);
                }
                
                return $product;
            }
        }

        $product['details']['has_discount'] = false;
        return $product;
    }
}
