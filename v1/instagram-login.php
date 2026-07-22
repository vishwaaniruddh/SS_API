<?php
// API/v1/instagram-login.php
require_once '../../instagram_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store the redirect URL (where to go after login)
$_SESSION['instagram_redirect_after'] = $_GET['redirect'] ?? '/account';

header("Location: " . $ig_oauth_url);
exit;
