<?php
/**
 * Website Access Tracking Utility
 * Logs user visits and increments product visit counts.
 */

function logSiteAccess($con) {
    // 1. Skip admin pages
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, '/admin/') !== false) {
        return;
    }

    // 2. Capture basic info
    $userId = $_SESSION['gid'] ?? null;
    $sessionId = session_id();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $pageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');

    // 3. Parse page type and IDs
    $pageType = 'other';
    $productId = null;
    $productType = null;
    $categoryId = null;

    $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');

    if ($scriptName == 'index.php' || $scriptName == '') {
        $pageType = 'home';
    } elseif ($scriptName == 'product.php') {
        $pageType = 'product';
        $productId = $_GET['id'] ?? null;
        $productType = $_GET['type'] ?? 'jewellery'; // Default to jewellery if not specified
    } elseif ($scriptName == 'category.php' || $scriptName == 'sub_category.php') {
        $pageType = 'category';
        $categoryId = $_GET['type'] ?? null;
    }

    // 4. Log to site_access_logs
    $stmt = $con->prepare("INSERT INTO site_access_logs (user_id, session_id, ip_address, page_url, page_type, product_id, product_type, category_id, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issssisss", $userId, $sessionId, $ipAddress, $pageUrl, $pageType, $productId, $productType, $categoryId, $userAgent);
        $stmt->execute();
        $stmt->close();
    }

    // 5. Increment visitcount for products
    if ($pageType == 'product' && !empty($productId)) {
        if ($productType == 'garment') {
            $table = 'garment_product';
            $pk = 'gproduct_id';
        } else {
            $table = 'product';
            $pk = 'product_id';
        }
        
        $updateStmt = $con->prepare("UPDATE $table SET visitcount = visitcount + 1 WHERE $pk = ?");
        if ($updateStmt) {
            $updateStmt->bind_param("i", $productId);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
}
?>
