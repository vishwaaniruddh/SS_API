<?php
session_start();

// --- Razorpay Configuration initialized dynamically after DB connection ---


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Custom error and exception handlers for API debugging
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return;
    
    // Suppress harmless session_start() notices when session is already active
    if (stripos($errstr, 'session_start()') !== false && stripos($errstr, 'already active') !== false) {
        return;
    }
    
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

set_exception_handler(function($exception) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    
    $errorResponse = [
        'status' => 'error',
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => explode("\n", $exception->getTraceAsString())
    ];
    
    // Log to a file on the server
    $logMsg = date("Y-m-d H:i:s") . " [ERROR] " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n" . $exception->getTraceAsString() . "\n\n";
    file_put_contents(__DIR__ . "/api_error_log.txt", $logMsg, FILE_APPEND);
    
    echo json_encode($errorResponse, JSON_PRETTY_PRINT);
    exit;
});

if (!headers_sent()) {
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
}

date_default_timezone_set('Asia/Kolkata');
global $con, $conn, $con3, $con_reporting;

$is_local = isset($_SERVER['HTTP_HOST']) && (in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '::1']) || strpos($_SERVER['HTTP_HOST'], 'localhost:') === 0);

if ($is_local) {
    $con = mysqli_connect("localhost", "root", "", "u464193275_srishrinjewels");
    $conn = mysqli_connect("localhost", "root", "", "u464193275_srishrinjewels");
    $con3 = mysqli_connect("localhost", "root", "", "u464193275_srishringarr");
    $con_reporting = mysqli_connect("localhost", "root", "", "u464193275_reporting");
} else {
    $con = mysqli_connect("localhost", "u464193275_srishrinjuser", "9b@hMgk!=zI", "u464193275_srishrinjewels");
    $conn = mysqli_connect("localhost", "u464193275_srishrinjuser", "9b@hMgk!=zI", "u464193275_srishrinjewels");
    $con3 = mysqli_connect("localhost", "u464193275_sarmicropos", "Mypos1234", "u464193275_srishringarr");
    $con_reporting = mysqli_connect("localhost", "u464193275_reporting", "AVav@@2026", "u464193275_reporting");
}
$pathmain = "";

// --- Dynamic Razorpay Configuration from Database ---
$rzp_defaults = [
    'razorpay_mode' => 'test',
    'razorpay_live_key_id' => 'rzp_live_DW1px0XkHJ4tAv',
    'razorpay_live_key_secret' => 'A52buJeuJW1E8hsEg6ssfm70',
    'razorpay_test_key_id' => 'rzp_test_4gwWqpQ2mlWxfH',
    'razorpay_test_key_secret' => 'e5DXo5IJdIkBO3apRU5zhCVd',
];

if ($con) {
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS site_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    
    $rzp_res = mysqli_query($con, "SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'razorpay_%'");
    if ($rzp_res) {
        while ($r = mysqli_fetch_assoc($rzp_res)) {
            $rzp_defaults[$r['setting_key']] = $r['setting_value'];
        }
    }

    mysqli_query($con, "CREATE TABLE IF NOT EXISTS ai_analytics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NULL,
        product_type VARCHAR(50) NULL,
        prompt_text TEXT NOT NULL,
        num_images INT DEFAULT 1,
        prompt_tokens INT DEFAULT 0,
        candidate_tokens INT DEFAULT 0,
        total_tokens INT DEFAULT 0,
        cost_estimate DECIMAL(10, 6) DEFAULT 0.000000,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
}

$razorpay_mode = strtolower($rzp_defaults['razorpay_mode'] ?? 'test');

if ($razorpay_mode === 'live') {
    define('RAZORPAY_KEY_ID', $rzp_defaults['razorpay_live_key_id']);
    define('RAZORPAY_KEY_SECRET', $rzp_defaults['razorpay_live_key_secret']);
} else {
    define('RAZORPAY_KEY_ID', $rzp_defaults['razorpay_test_key_id']);
    define('RAZORPAY_KEY_SECRET', $rzp_defaults['razorpay_test_key_secret']);
}
define('RAZORPAY_MODE', $razorpay_mode);
// ----------------------------------------


$currency = $_SESSION['cur'] ?? ($_SESSION['cur'] = 'INR');


// Check if gid is set
if (!isset($_SESSION['gid'])) {
    $query = "INSERT INTO `Registration`(`Firstname`) VALUES ('')";
    if (mysqli_query($con, $query)) {
        $_SESSION['gid'] = mysqli_insert_id($con);
    } else {
        // Silently fail for API requests — gid is only needed for cart
        $_SESSION['gid'] = 0;
    }
}

$userid = $_SESSION['gid'] ?? 'Not Set';

$logfile = __DIR__ . "/connection_log.txt";
file_put_contents($logfile, date("Y-m-d H:i:s") . " - " . $_SERVER['PHP_SELF'] . "\n", FILE_APPEND);

// Site Access Tracking
require_once 'tracking.php';
logSiteAccess($con);

?>