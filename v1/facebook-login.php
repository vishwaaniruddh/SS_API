<?php
// API/v1/facebook-login.php
require_once '../../facebook_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store the redirect URL (where to go after login)
$_SESSION['facebook_redirect_after'] = $_GET['redirect'] ?? '/account';

header("Location: " . $fb_oauth_url);
exit;
