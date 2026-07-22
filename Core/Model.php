<?php
namespace API\Core;

class Model {
    protected $db;
    protected $db3;
    protected $db_reporting;

    public function __construct() {
        $this->db = Database::getConnection('con');
        $this->db3 = Database::getConnection('con3');
        $this->db_reporting = Database::getConnection('con_reporting');
    }

    public function query($db, $sql) {
        return mysqli_query($db, $sql);
    }

    public function fetchAll($result) {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function fetchOne($result) {
        return mysqli_fetch_assoc($result);
    }
}
