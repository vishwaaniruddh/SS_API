<?php
namespace API\Models;

use API\Core\Model;

class ProductModel extends Model {
    
    private $discountService;

    public function __construct() {
        parent::__construct();
        $this->discountService = new \API\Services\DiscountService();
    }

    public function getProducts($params = []) {
        $records_per_page = $params['limit'] ?? 20;
        $page = $params['page'] ?? 1;
        $offset = ($page - 1) * $records_per_page;
        $search = $params['search'] ?? '';
        $category_param = $params['category'] ?? '';
        $minPrice = $params['min_price'] ?? 0;
        $maxPrice = $params['max_price'] ?? 1000000;
        $type_filter = $params['type'] ?? '';

        $jewellery_search = '';
        $garments_search = '';
        
        if ($type_filter === 'jewellery') {
            $garments_search .= " AND 1=0";
        } elseif ($type_filter === 'garments') {
            $jewellery_search .= " AND 1=0";
        }
        
        $featured = $params['featured'] ?? false;
        if ($featured) {
            $jewellery_search .= " AND p.featured = 1";
            $garments_search .= " AND gp.featured = 1";
        }
        
        if (!empty($search)) {
            $search = mysqli_real_escape_string($this->db, $search);
            $jewellery_search = " AND (p.product_name LIKE '%$search%' OR p.product_code LIKE '%$search%')";
            $garments_search = " AND (gp.gproduct_name LIKE '%$search%' OR gp.gproduct_code LIKE '%$search%')";
        }

        if (!empty($category_param)) {
            if (strpos($category_param, ':') !== false) {
                list($type, $id) = explode(':', $category_param);
                $id = (int)$id;
                if ($type === 'garment') {
                    $garments_search .= " AND (gp.garment_id = $id OR gp.product_for = $id OR EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = gp.gproduct_id AND pc.product_type = 'garments' AND (pc.legacy_category_id = $id OR pc.legacy_subcategory_id = $id)))";
                    $jewellery_search .= " AND 1=0";
                } elseif ($type === 'jewel_main' || $type === 'jewel_parent') {
                    $jewellery_search .= " AND (p.categories_id = $id OR p.subcat_id IN (SELECT subcat_id FROM subcat1 WHERE maincat_id = $id) OR EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.product_id AND pc.product_type = 'jewellery' AND (pc.legacy_category_id = $id OR pc.legacy_subcategory_id IN (SELECT subcat_id FROM subcat1 WHERE maincat_id = $id))))";
                    $garments_search .= " AND 1=0";
                } elseif ($type === 'jewel_sub' || $type === 'jewel_child') {
                    $jewellery_search .= " AND (p.subcat_id = $id OR EXISTS (SELECT 1 FROM product_categories pc WHERE pc.product_id = p.product_id AND pc.product_type = 'jewellery' AND pc.legacy_subcategory_id = $id))";
                    $garments_search .= " AND 1=0";
                }
            }
        }

        $color_param = $params['color'] ?? $params['colors'] ?? '';
        if (!empty($color_param)) {
            $colorEsc = mysqli_real_escape_string($this->db, trim($color_param));
            $jewellery_search .= " AND p.brand_color LIKE '%$colorEsc%'";
            $garments_search .= " AND gp.brand_color LIKE '%$colorEsc%'";
        }

        $sort = $params['sort'] ?? 'sku_desc';
        $orderBy = "CAST(REGEXP_REPLACE(code, '[^0-9]', '') AS UNSIGNED) DESC";
        
        if ($sort === 'sku_asc') {
            $orderBy = "CAST(REGEXP_REPLACE(code, '[^0-9]', '') AS UNSIGNED) ASC";
        }

        $query = "
            (SELECT p.product_id as id, p.product_name as name, p.product_code as code, 'jewellery' as type, p.sales_price as original_sales_price, p.rent_price as db_rent_price, p.deposit as db_deposit, p.price_source, p.availability, p.brand_name, p.size_avail, p.brand_color FROM product p WHERE 1=1 AND EXISTS (SELECT 1 FROM product_images_new pin WHERE pin.pro_code = p.product_code AND pin.product_id = p.product_id) $jewellery_search)
            UNION ALL
            (SELECT gp.gproduct_id as id, gp.gproduct_name as name, gp.gproduct_code as code, 'garments' as type, gp.sales_price as original_sales_price, gp.rent_price as db_rent_price, gp.deposit as db_deposit, gp.price_source, gp.availability, gp.brand_name, gp.size_avail, gp.brand_color FROM garment_product gp WHERE 1=1 AND EXISTS (SELECT 1 FROM product_images_new pin WHERE pin.pro_code = gp.gproduct_code AND pin.gproduct_id = gp.gproduct_id) $garments_search)
            ORDER BY $orderBy";

        $result = $this->query($this->db, $query);
        $allProducts = $this->fetchAll($result);
        
        if (empty($allProducts)) {
            $this->lastFilteredCount = 0;
            return [];
        }

        // --- BATCH OPTIMIZATION START ---
        $skus = array_unique(array_column($allProducts, 'code'));
        $skuString = "'" . implode("','", array_map(function($s) { return mysqli_real_escape_string($this->db3, $s); }, $skus)) . "'";
        
        // Batch POS Data
        $posItems = [];
        $pos_query = "SELECT name as sku, category, category_type, unit_price, quantity, cost_price FROM phppos_items WHERE name IN ($skuString)";
        $pos_result = $this->query($this->db3, $pos_query);
        while ($row = $this->fetchOne($pos_result)) {
            $posItems[strtolower($row['sku'])] = $row;
        }

        // Batch Commissions
        $commissions = [];
        $comm_query = "SELECT item_id as sku, SUM(CAST(REPLACE(commission_amt, ',', '') AS DECIMAL(10,2))) as total_comm
                       FROM order_detail 
                       WHERE item_id IN ($skuString)
                       AND bill_id IN (SELECT bill_id FROM phppos_rent WHERE booking_status != 'Booked')
                       GROUP BY item_id";
        $comm_result = $this->query($this->db3, $comm_query);
        while ($row = $this->fetchOne($comm_result)) {
            $commissions[strtolower($row['sku'])] = (float)$row['total_comm'];
        }

        // Batch Images
        $productImages = [];
        $productAllImages = [];
        $clauses = [];
        foreach ($allProducts as $p) {
            $sku = mysqli_real_escape_string($this->db, $p['code']);
            $id = (int)$p['id'];
            $img_field = ($p['type'] === 'jewellery') ? 'product_id' : 'gproduct_id';
            $clauses[] = "(pro_code = '$sku' AND $img_field = $id)";
        }
        
        if (!empty($clauses)) {
            $img_query = "SELECT pro_code, img_name FROM product_images_new WHERE " . implode(' OR ', $clauses) . " ORDER BY rank ASC";
            $res = $this->query($this->db, $img_query);
            while ($row = $this->fetchOne($res)) { 
                $sku = $row['pro_code'];
                $lowerSku = strtolower($sku);
                $fullImg = "https://srishringarr.com/yn/uploads/" . ltrim($row['img_name'], '/');
                if (!isset($productImages[$lowerSku])) {
                    $productImages[$lowerSku] = $row['img_name']; 
                }
                if (!isset($productAllImages[$lowerSku])) {
                    $productAllImages[$lowerSku] = [];
                }
                if (!in_array($fullImg, $productAllImages[$lowerSku])) {
                    $productAllImages[$lowerSku][] = $fullImg;
                }
            }
        }
        // --- BATCH OPTIMIZATION END ---

        $filteredProducts = [];
        foreach ($allProducts as $product) {
            $sku = $product['code'];
            $lowerSku = strtolower($sku);
            $pos_item = $posItems[$lowerSku] ?? null;
            $comm_amt = $commissions[$lowerSku] ?? 0;
            $img_name = $productImages[$lowerSku] ?? null;
            
            $product['images'] = $productAllImages[$lowerSku] ?? [];
            if (empty($product['images']) && $img_name) {
                $product['images'][] = "https://srishringarr.com/yn/uploads/" . ltrim($img_name, '/');
            }
            if (empty($product['images'])) {
                $product['images'][] = 'https://srishringarr.com/static/images/default.jpg';
            }
            
            $product['colors'] = $this->parseColors($product['brand_color'] ?? '');
            $product['details'] = $this->calculateDetailsWithBatchData($product, $pos_item, $comm_amt, $img_name);
            
            // Apply Discounts
            $this->discountService->applyDiscounts($product);
            
            $price = $product['details']['rent_price'];
            
            $priceSource = $product['price_source'] ?? 'pos';
            $quantity = (int)($pos_item['quantity'] ?? 0);
            
            // Manual-priced products always show (bypass POS inventory check)
            // POS-priced products require quantity > 0
            if ($priceSource === 'manual') {
                if ($price >= $minPrice && $price <= $maxPrice) {
                    $filteredProducts[] = $product;
                }
            } else {
                if ($quantity > 0 && $price >= $minPrice && $price <= $maxPrice) {
                    $filteredProducts[] = $product;
                }
            }
        }

        // Apply PHP Sorting for Price
        if ($sort === 'price_low' || $sort === 'price_asc') {
            usort($filteredProducts, function($a, $b) {
                return $a['details']['rent_price'] <=> $b['details']['rent_price'];
            });
        } elseif ($sort === 'price_high' || $sort === 'price_desc') {
            usort($filteredProducts, function($a, $b) {
                return $b['details']['rent_price'] <=> $a['details']['rent_price'];
            });
        }

        $this->lastFilteredCount = count($filteredProducts);
        return array_slice($filteredProducts, $offset, $records_per_page);
    }

    private function calculateDetailsWithBatchData($product, $pos_item, $commissionAmount, $imageName = null) {
        $sku = $product['code'];
        $type = $product['type'];
        $priceSource = $product['price_source'] ?? 'pos';
        
        $mrp = $pos_item['unit_price'] ?? 0;
        $product_type_id = ($type == 'jewellery') ? 1 : 2;
        if ($pos_item) {
            $product_type_id = $pos_item['category_type'] ?? $product_type_id;
        }

        $image_path = !empty($imageName) ? "https://srishringarr.com/yn/uploads/" . ltrim($imageName, '/') : 'https://srishringarr.com/static/images/default.jpg';

        // --- Manual price source: use DB values directly ---
        if ($priceSource === 'manual') {
            return [
                'sale_price' => (float)($product['original_sales_price'] ?? 0),
                'rent_price' => (float)($product['db_rent_price'] ?? 0),
                'deposit' => (float)($product['db_deposit'] ?? 0),
                'image_path' => $image_path,
                'mrp' => $mrp,
                'inventory' => (int)($pos_item['quantity'] ?? 0),
                'price_source' => 'manual',
                'availability' => $product['availability'] ?? 'both'
            ];
        }

        // --- POS price source: existing formula logic ---
        $currentsp = $mrp - $commissionAmount;
        $lastSellingPrice = 0;
        $addedRentPrice = 0;
        $deposit = 0;

        if ($product_type_id == 1) { // Jewellery
            $courier = ($mrp <= 2000) ? 100 : (($mrp <= 5000) ? 250 : (($mrp <= 10000) ? 500 : 1000));
            $sellingCalc = ($mrp - $commissionAmount) * 0.6;
            $lastSellingPrice = ($mrp >= 10000) ? max(5000, $sellingCalc) : ($mrp * 0.5);

            if ($currentsp > 0) {
                if ($mrp <= 10000) {
                    $addedRentPrice = $courier + ($mrp * 0.20);
                    $deposit = $mrp * 0.35;
                } else {
                    $rentprice = ($currentsp <= 40000) ? ($currentsp * 0.20) : (($currentsp <= 60000) ? ($currentsp * 0.17) : ($currentsp * 0.15));
                    $addedRentPrice = max(3000, $courier + $rentprice);
                    $deposit = max(3000, $currentsp * 0.35);
                }
            } else {
                $addedRentPrice = ($mrp <= 10000) ? ($courier + $mrp * 0.20) : 3000;
                $deposit = ($mrp <= 10000) ? ($mrp * 0.35) : 3000;
            }
        } else { // Garments
            $sellingCalc = ($mrp - $commissionAmount) * 0.6;
            $lastSellingPrice = ($mrp >= 10000) ? max(5000, $sellingCalc) : ($mrp * 0.5);

            if ($currentsp > 0) {
                $courier = ($mrp <= 10000) ? 1000 : 2000;
                if ($mrp <= 10000) {
                    $addedRentPrice = $courier + ($mrp * 0.20);
                    $deposit = $mrp * 0.35;
                } else {
                    $rentprice = ($currentsp <= 40000) ? ($currentsp * 0.20) : (($currentsp <= 60000) ? ($currentsp * 0.17) : ($currentsp * 0.15));
                    $addedRentPrice = max(3000, $courier + $rentprice);
                    $deposit = max(3000, $currentsp * 0.35);
                }
            } else {
                $addedRentPrice = ($mrp <= 10000) ? (1000 + $mrp * 0.20) : 3000;
                $deposit = ($mrp <= 10000) ? ($mrp * 0.35) : 3000;
            }
        }

        if (isset($product['original_sales_price']) && $product['original_sales_price'] > 0) {
            $lastSellingPrice = $product['original_sales_price'];
        }

        return [
            'sale_price' => $lastSellingPrice,
            'rent_price' => ceil($addedRentPrice / 100) * 100,
            'deposit' => ceil($deposit / 100) * 100,
            'image_path' => $image_path,
            'mrp' => $mrp,
            'inventory' => (int)($pos_item['quantity'] ?? 0),
            'price_source' => 'pos',
            'availability' => $product['availability'] ?? 'both'
        ];
    }

    private $lastFilteredCount = 0;

    public function getTotalCount($params = []) {
        // If getProducts was called, use the saved count
        if ($this->lastFilteredCount > 0) return $this->lastFilteredCount;
        
        // Otherwise, fetch enough to count (this is a fallback)
        $this->getProducts($params);
        return $this->lastFilteredCount;
    }

    private function getProductDetails($product) {
        $sku = $product['code'];
        $type = $product['type'];
        $priceSource = $product['price_source'] ?? 'pos';
        
        // POS Data
        $pos_query = "SELECT category, category_type, unit_price, quantity, cost_price FROM phppos_items WHERE name LIKE '$sku'";
        $pos_result = $this->query($this->db3, $pos_query);
        $pos_item = $this->fetchOne($pos_result);

        $mrp = $pos_item['unit_price'] ?? 0;
        $cost_price = $pos_item['cost_price'] ?? 0;
        $product_type_id = ($type == 'jewellery') ? 1 : 2;

        if ($pos_item) {
            $product_type_id = $pos_item['category_type'] ?? $product_type_id;
        }

        // Image
        $img_field = ($type === 'jewellery') ? 'product_id' : 'gproduct_id';
        $pid = (int)$product['id'];
        $img_query = "SELECT img_name FROM product_images_new WHERE pro_code = '$sku' AND $img_field = $pid ORDER BY rank LIMIT 1";
        $img_result = $this->query($this->db, $img_query);
        $img_row = $this->fetchOne($img_result);
        $image_path = !empty($img_row['img_name']) ? "https://srishringarr.com/yn/uploads/" . ltrim($img_row['img_name'], '/') : 'https://srishringarr.com/static/images/default.jpg';

        // --- Manual price source: use DB values directly ---
        if ($priceSource === 'manual') {
            return [
                'sale_price' => (float)($product['original_sales_price'] ?? 0),
                'rent_price' => (float)($product['db_rent_price'] ?? $product['original_rent_price'] ?? 0),
                'deposit' => (float)($product['db_deposit'] ?? $product['original_deposit'] ?? 0),
                'image_path' => $image_path,
                'mrp' => $mrp,
                'inventory' => (int)($pos_item['quantity'] ?? 0),
                'price_source' => 'manual',
                'availability' => $product['availability'] ?? 'both'
            ];
        }

        // --- POS price source: existing formula logic ---
        // Commission
        $comm_query = "SELECT SUM(CAST(REPLACE(commission_amt, ',', '') AS DECIMAL(10,2))) 
                       FROM order_detail 
                       WHERE item_id='$sku' 
                       AND bill_id IN (SELECT bill_id FROM phppos_rent WHERE booking_status != 'Booked')";
        $comm_result = $this->query($this->db3, $comm_query);
        $comm_row = mysqli_fetch_row($comm_result);
        $commissionAmount = (float)($comm_row[0] ?? 0);

        $currentsp = $mrp - $commissionAmount;

        // Price Calculations
        $lastSellingPrice = 0;
        $addedRentPrice = 0;
        $deposit = 0;

        if ($product_type_id == 1) { // Jewellery
            $courier = ($mrp <= 2000) ? 100 : (($mrp <= 5000) ? 250 : (($mrp <= 10000) ? 500 : 1000));
            
            $sellingCalc = $mrp - $commissionAmount;
            $sellingCalc = $sellingCalc - ($sellingCalc * 0.4);

            if ($mrp >= 10000) {
                $lastSellingPrice = ($sellingCalc < 5000) ? 5000 : $sellingCalc;
            } else {
                $lastSellingPrice = $mrp - ($mrp * 0.5);
            }

            if ($currentsp > 0) {
                if ($mrp <= 10000) {
                    $rentprice = $mrp * 0.20;
                    $addedRentPrice = $courier + $rentprice;
                    $deposit = $mrp * 0.35;
                } else {
                    $rentprice = ($currentsp <= 40000) ? ($currentsp * 0.20) : (($currentsp <= 60000) ? ($currentsp * 0.17) : ($currentsp * 0.15));
                    $addedRentPrice = max(3000, $courier + $rentprice);
                    $deposit = max(3000, $currentsp * 0.35);
                }
            } else {
                if ($mrp <= 10000) {
                    $addedRentPrice = $courier + ($mrp * 0.20);
                    $deposit = $mrp * 0.35;
                } else {
                    $deposit = 3000;
                    $addedRentPrice = 3000;
                }
            }
        } else { // Garments
            $sellingCalc = $mrp - $commissionAmount;
            $sellingCalc = $sellingCalc - ($sellingCalc * 0.4);

            if ($mrp >= 10000) {
                $lastSellingPrice = ($sellingCalc < 5000) ? 5000 : $sellingCalc;
            } else {
                $lastSellingPrice = $mrp - ($mrp * 0.5);
            }

            if ($currentsp > 0) {
                if ($mrp <= 10000) {
                    $courier = 1000;
                    $addedRentPrice = $courier + ($mrp * 0.20);
                    $deposit = $mrp * 0.35;
                } else {
                    $courier = 2000;
                    $rentprice = ($currentsp <= 40000) ? ($currentsp * 0.20) : (($currentsp <= 60000) ? ($currentsp * 0.17) : ($currentsp * 0.15));
                    $addedRentPrice = max(3000, $courier + $rentprice);
                    $deposit = max(3000, $currentsp * 0.35);
                }
            } else {
                if ($mrp <= 10000) {
                    $courier = 1000;
                    $addedRentPrice = $courier + ($mrp * 0.20);
                    $deposit = $mrp * 0.35;
                } else {
                    $deposit = 3000;
                    $addedRentPrice = 3000;
                }
            }
        }

        if (isset($product['original_sales_price']) && $product['original_sales_price'] > 0) {
            $lastSellingPrice = $product['original_sales_price'];
        }

        return [
            'sale_price' => $lastSellingPrice,
            'rent_price' => ceil($addedRentPrice / 100) * 100,
            'deposit' => ceil($deposit / 100) * 100,
            'image_path' => $image_path,
            'mrp' => $mrp,
            'inventory' => (int)($pos_item['quantity'] ?? 0),
            'price_source' => 'pos',
            'availability' => $product['availability'] ?? 'both'
        ];
    }
    public function getProductById($id, $type) {
        $id = (int)$id;
        $table = ($type === 'jewellery') ? 'product' : 'garment_product';
        $id_field = ($type === 'jewellery') ? 'product_id' : 'gproduct_id';
        $name_field = ($type === 'jewellery') ? 'product_name' : 'gproduct_name';
        $code_field = ($type === 'jewellery') ? 'product_code' : 'gproduct_code';
        $product_desc = ($type === 'jewellery') ? 'product_desc' : 'gproduct_desc';
        
        $cat_field = ($type === 'jewellery') ? 'subcat_id as cat_id' : 'product_for as cat_id';

        $query = "SELECT $id_field as id, $product_desc as product_desc, $name_field as name, $code_field as code, sales_price as original_sales_price, rent_price as db_rent_price, deposit as db_deposit, price_source, availability, brand_name, size_avail, brand_color, $cat_field 
                  FROM $table WHERE $id_field = $id";
        $result = $this->query($this->db, $query);
        $product = $this->fetchOne($result);

        if ($product) {
            $product['type'] = $type;
            $product['colors'] = $this->parseColors($product['brand_color'] ?? '');
            $product['details'] = $this->getProductDetails($product);
            
            // Get all images
            $sku = $product['code'];
            $img_query = "SELECT img_name FROM product_images_new WHERE pro_code = '" . mysqli_real_escape_string($this->db, $sku) . "' AND $id_field = $id ORDER BY rank";
            $img_result = $this->query($this->db, $img_query);
            $product['images'] = [];
            while ($img_row = $this->fetchOne($img_result)) {
                $product['images'][] = "https://srishringarr.com/yn/uploads/" . ltrim($img_row['img_name'], '/');
            }
            if (empty($product['images'])) {
                $product['images'][] = 'https://srishringarr.com/static/images/default.jpg';
            }

            // Apply Discounts
            $this->discountService->applyDiscounts($product);
        }

        return $product;
    }

    public function getProductBySku($sku) {
        $escapedSku = mysqli_real_escape_string($this->db, $sku);
        
        // Check jewellery
        $jQ = "SELECT product_id FROM product WHERE product_code = '$escapedSku' LIMIT 1";
        $jRes = $this->query($this->db, $jQ);
        $jRow = $this->fetchOne($jRes);
        if ($jRow) {
            return $this->getProductById((int)$jRow['product_id'], 'jewellery');
        }

        // Check garments
        $gQ = "SELECT gproduct_id FROM garment_product WHERE gproduct_code = '$escapedSku' LIMIT 1";
        $gRes = $this->query($this->db, $gQ);
        $gRow = $this->fetchOne($gRes);
        if ($gRow) {
            return $this->getProductById((int)$gRow['gproduct_id'], 'garments');
        }

        return null;
    }

    public function parseColors($rawColor) {
        $colors = [];
        if (!empty($rawColor)) {
            $rawColor = trim($rawColor);
            $decoded = json_decode($rawColor, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $colors = $decoded;
            } else if (str_starts_with($rawColor, '[') && str_ends_with($rawColor, ']')) {
                $decoded = json_decode(stripslashes($rawColor), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $colors = $decoded;
                }
            } else {
                $colors = array_filter(array_map('trim', explode(',', $rawColor)));
            }
        }
        return array_values(array_unique(array_filter(array_map(function($c) {
            return trim(strip_tags((string)$c));
        }, $colors))));
    }
}
