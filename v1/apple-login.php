<?php
// API/v1/apple-login.php
require_once '../../apple_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store the redirect URL (where to go after login)
$_SESSION['apple_redirect_after'] = $_GET['redirect'] ?? '/account';

header("Location: " . $apple_oauth_url);
exit;
