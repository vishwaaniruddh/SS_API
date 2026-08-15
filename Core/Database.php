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
            if ($type === 'con' || $type === 'conn') {
                if (isset($GLOBALS['con'])) self::$instances['con'] = $GLOBALS['con'];
                else if (isset($con)) self::$instances['con'] = $con;
            } else if ($type === 'con3') {
                if (!isset($GLOBALS['con3']) || !$GLOBALS['con3']) {
                    $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
                    $GLOBALS['con3'] = $is_local ? @mysqli_connect("localhost", "root", "", "u464193275_srishringarr") : @mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
                }
                self::$instances['con3'] = $GLOBALS['con3'] ?? null;
            } else if ($type === 'con_reporting') {
                if (!isset($GLOBALS['con_reporting']) || !$GLOBALS['con_reporting']) {
                    $is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);
                    $GLOBALS['con_reporting'] = $is_local ? @mysqli_connect("localhost", "root", "", "u464193275_reporting") : @mysqli_connect("localhost", "u464193275_reporting", "AVav@@2026", "u464193275_reporting");
                }
                self::$instances['con_reporting'] = $GLOBALS['con_reporting'] ?? null;
            }
        }
        return self::$instances[$type] ?? null;
    }
}
