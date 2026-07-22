<?php
namespace API\Models;

use API\Core\Model;

class SubcategoryModel extends Model {
    public function getSubcategories($catParam) {
        if (strpos($catParam, ':') === false) return [];

        list($type, $id) = explode(':', $catParam);
        $id = (int)$id;

        if ($type === 'jewel_parent') {
            $sql = "SELECT subcat_id as id, name FROM subcat1 WHERE maincat_id = $id AND status = 1 ORDER BY name ASC";
            $result = $this->query($this->db, $sql);
            return $this->fetchAll($result);
        } elseif ($type === 'garment') {
            $sql = "SELECT sub_id as id, sub_name as name FROM garment_subcat WHERE gmain_id = $id ORDER BY sub_name ASC";
            $result = $this->query($this->db, $sql);
            return $this->fetchAll($result);
        }

        return [];
    }
}
