<?php
namespace API\Core;

class Database {
    private static $instances = [];

    public static function getConnection($type = 'con') {
        global $con, $con3, $con_reporting;

        if (!isset(self::$instances[$type]) || !self::$instances[$type]) {
            // Adjust path to API config.php
            include_once(__DIR__ . '/../config.php');
            
            // Try to pull from GLOBALS or global keyword
            if (isset($GLOBALS['con'])) self::$instances['con'] = $GLOBALS['con'];
            else if (isset($con)) self::$instances['con'] = $con;

            if (isset($GLOBALS['con3'])) self::$instances['con3'] = $GLOBALS['con3'];
            else if (isset($con3)) self::$instances['con3'] = $con3;

            if (isset($GLOBALS['con_reporting'])) self::$instances['con_reporting'] = $GLOBALS['con_reporting'];
            else if (isset($con_reporting)) self::$instances['con_reporting'] = $con_reporting;
        }
        return self::$instances[$type] ?? null;
    }
}
