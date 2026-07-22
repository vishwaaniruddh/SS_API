<?php
/**
 * Instagram Feed Proxy
 * 
 * Returns cached Instagram posts. Since Instagram's API requires
 * authentication tokens, this returns an empty feed when not configured.
 * 
 * To enable: Add your Instagram Basic Display API token to config.php
 * and implement the fetch logic below.
 */
header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600');

// Return empty feed for now — no Instagram API configured
echo json_encode([
    'status' => 'success',
    'posts' => [],
    'profile_url' => 'https://www.instagram.com/flyrobe_srishringarr/',
    'message' => 'Instagram feed not configured'
]);
