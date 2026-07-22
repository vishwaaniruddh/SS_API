<?php
namespace API\Models;

use API\Core\Model;

class DiscountModel extends Model {
    
    public function getRules() {
        $sql = "SELECT * FROM discount_rules ORDER BY weight DESC, id DESC";
        $result = $this->query($this->db, $sql);
        return $this->fetchAll($result);
    }

    public function getSettings() {
        $sql = "SELECT * FROM discount_settings";
        $result = $this->query($this->db, $sql);
        $settings = [];
        while ($row = $this->fetchOne($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}
