<?php
namespace API\Models;

use API\Core\Model;

class CategoryModel extends Model {
    
    public function getAllCategories() {
        $data = [];

        // 1. Apparel Categories
        $apparel_items = [];
        $apparel_qry = "SELECT garment_id, name FROM garments WHERE Main_id IN (1, 2, 3) ORDER BY name";
        $apparel_res = $this->query($this->db, $apparel_qry);
        
        while ($row = $this->fetchOne($apparel_res)) {
            $apparel_items[] = [
                'id' => "garment:" . $row['garment_id'],
                'name' => ucwords(strtolower($row['name']))
            ];
        }
        if (!empty($apparel_items)) {
            $data[] = [
                'category' => 'Apparel',
                'items' => $apparel_items
            ];
        }

        // 2. Jewellery Categories
        $jewel_items = [];
        $jewel_qry = "SELECT subcat_id, categories_name FROM jewel_subcat WHERE mcat_id=1 OR mcat_id=3 ORDER BY categories_name";
        $jewel_res = $this->query($this->db, $jewel_qry);

        while ($row = $this->fetchOne($jewel_res)) {
            $parent_id = $row['subcat_id'];
            $parent_name = ucwords(strtolower($row['categories_name']));

            $children = [];
            $sub_qry = "SELECT subcat_id, name FROM subcat1 WHERE maincat_id = $parent_id AND status=1 ORDER BY name";
            $sub_res = $this->query($this->db, $sub_qry);
            
            while ($sub_row = $this->fetchOne($sub_res)) {
                $sub_name = ucwords(strtolower($sub_row['name']));
                if ($sub_name !== $parent_name) {
                    $children[] = [
                        'id' => "jewel_sub:" . $sub_row['subcat_id'],
                        'name' => $sub_name
                    ];
                }
            }

            $jewel_items[] = [
                'id' => "jewel_main:" . $parent_id,
                'name' => $parent_name,
                'children' => $children
            ];
        }
        
        if (!empty($jewel_items)) {
            $data[] = [
                'category' => 'Jewellery',
                'items' => $jewel_items
            ];
        }

        return $data;
    }

    public function getCategoryName($catParam) {
        if (strpos($catParam, ':') === false) return 'Collection';

        list($type, $id) = explode(':', $catParam);
        $id = (int)$id;

        if ($type === 'jewel_main') {
            $sql = "SELECT categories_name FROM jewel_subcat WHERE subcat_id = $id";
            $result = $this->query($this->db, $sql);
            $row = $this->fetchOne($result);
            return $row['categories_name'] ?? 'Jewellery';
        } elseif ($type === 'jewel_sub') {
            $sql = "SELECT name FROM subcat1 WHERE subcat_id = $id";
            $result = $this->query($this->db, $sql);
            $row = $this->fetchOne($result);
            return $row['name'] ?? 'Jewellery Item';
        } elseif ($type === 'garment') {
            $sql = "SELECT name FROM garments WHERE garment_id = $id";
            $result = $this->query($this->db, $sql);
            $row = $this->fetchOne($result);
            return $row['name'] ?? 'Apparel';
        }

        return 'Collection';
    }

    public function getCategoryImage($catParam) {
        if (strpos($catParam, ':') === false) return '';

        list($type, $id) = explode(':', $catParam);
        $id = (int)$id;

        if ($type === 'jewel_main') {
            $sql = "SELECT image FROM jewel_subcat WHERE subcat_id = $id";
            $result = $this->query($this->db, $sql);
            $row = $this->fetchOne($result);
            return $row['image'] ?? '';
        } elseif ($type === 'jewel_sub') {
            $sql = "SELECT image FROM subcat1 WHERE subcat_id = $id";
            $result = $this->query($this->db, $sql);
            $row = $this->fetchOne($result);
            return $row['image'] ?? '';
        } elseif ($type === 'garment') {
            $sql = "SELECT garments_image as image FROM garments WHERE garment_id = $id";
            $result = $this->query($this->db, $sql);
            $row = $this->fetchOne($result);
            return $row['image'] ?? '';
        }
        return '';
    }

    public function getSubCategories($parentId) {
        $parentName = $this->getCategoryName("jewel_main:" . $parentId);
        $parentId = (int)$parentId;
        
        $sql = "SELECT subcat_id, name, image FROM subcat1 WHERE maincat_id = $parentId AND status=1 ORDER BY name";
        $result = $this->query($this->db, $sql);
        $data = [];
        
        while ($row = $this->fetchOne($result)) {
            $parentNameNormalized = trim(ucwords(strtolower($parentName)));
            $subNameNormalized = trim(ucwords(strtolower($row['name'])));
            
            // Filter out same-name subcategories
            if ($subNameNormalized !== $parentNameNormalized) {
                $data[] = [
                    'id' => $row['subcat_id'],
                    'name' => $subNameNormalized,
                    'image' => $row['image']
                ];
            }
        }
        return $data;
    }

    public function getJewelMainList() {
        $sql = "SELECT subcat_id, categories_name as name, image FROM jewel_subcat WHERE mcat_id IN (1, 3) ORDER BY categories_name";
        $result = $this->query($this->db, $sql);
        return $this->fetchAll($result);
    }

    public function getGarmentList() {
        $sql = "SELECT garment_id as id, name, garments_image as image FROM garments WHERE Main_id IN (1, 3) ORDER BY name";
        $result = $this->query($this->db, $sql);
        return $this->fetchAll($result);
    }

    public function hasDistinctSubCategories($parentId) {
        $subs = $this->getSubCategories($parentId);
        return count($subs) > 0;
    }
}
