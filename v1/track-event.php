<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

// Block known bots/crawlers — don't waste DB space
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$botPatterns = ['bot', 'crawl', 'spider', 'slurp', 'lighthouse', 'headless', 'phantom', 'wget', 'curl', 'python', 'scrapy', 'semrush', 'ahref', 'bytespider', 'yandex', 'baidu', 'sogou', 'petalbot'];
foreach ($botPatterns as $bp) {
    if (strpos($ua, $bp) !== false) {
        echo json_encode(['status' => 'ignored', 'message' => 'Bot traffic filtered']);
        exit;
    }
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['eventType']) || empty($input['pagePath'])) {
    echo json_encode(['status' => 'error', 'message' => 'Event type and page path are required']);
    exit;
}

// Filter out garbage/bot paths — paths with random alphanumeric segments like /shop/H887578912/
$rawPath = $input['pagePath'];
if (preg_match('#/[A-Z][0-9]{6,}#', $rawPath)) {
    echo json_encode(['status' => 'ignored', 'message' => 'Suspicious path filtered']);
    exit;
}

global $con;

$eventType = mysqli_real_escape_string($con, $input['eventType']);
$pagePath = mysqli_real_escape_string($con, $input['pagePath']);
$targetId = !empty($input['targetId']) ? (int)$input['targetId'] : 'NULL';
$targetType = !empty($input['targetType']) ? "'" . mysqli_real_escape_string($con, $input['targetType']) . "'" : 'NULL';
$sessionId = mysqli_real_escape_string($con, session_id());

// Process metadata if present
$metadata = null;
if (isset($input['metadata'])) {
    $metadata = mysqli_real_escape_string($con, json_encode($input['metadata']));
}
$metadataVal = $metadata !== null ? "'$metadata'" : 'NULL';

// Deduplicate: skip if same session + event_type + page_path was logged within 10 seconds
$dedupeCheck = mysqli_query($con, 
    "SELECT id FROM analytics_events 
     WHERE session_id = '$sessionId' 
     AND event_type = '$eventType' 
     AND page_path = '$pagePath' 
     AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND) 
     LIMIT 1"
);

if ($dedupeCheck && mysqli_num_rows($dedupeCheck) > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Already tracked']);
    exit;
}

// 1. Log to analytics_events table
$query = "INSERT INTO analytics_events 
    (session_id, event_type, page_path, target_id, target_type, metadata, created_at) 
    VALUES 
    ('$sessionId', '$eventType', '$pagePath', $targetId, $targetType, $metadataVal, NOW())";

$eventSuccess = mysqli_query($con, $query);

// 2. If it is a search event, also log to analytics_searches table
if ($eventType === 'search' && isset($input['metadata']['query'])) {
    $searchQuery = mysqli_real_escape_string($con, $input['metadata']['query']);
    $resultsCount = isset($input['metadata']['resultsCount']) ? (int)$input['metadata']['resultsCount'] : 0;
    
    $searchQuery = trim($searchQuery);
    if (!empty($searchQuery)) {
        $searchSql = "INSERT INTO analytics_searches (query, results_count, created_at) 
                      VALUES ('$searchQuery', $resultsCount, NOW())";
        mysqli_query($con, $searchSql);
    }
}

if ($eventSuccess) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . mysqli_error($con)]);
}
