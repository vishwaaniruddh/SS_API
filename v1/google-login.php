<?php
/**
 * Google Login Initiator
 * 
 * Redirects the user to Google's OAuth consent screen.
 * After auth, Google redirects to google-callback.php which sets the session
 * and redirects back to the React app.
 */
require_once '../../google_config.php';

// Store the redirect URL (where to go after login)
$_SESSION['google_redirect_after'] = $_GET['redirect'] ?? '/account';

$authUrl = $client->createAuthUrl();
header("Location: " . $authUrl);
exit;
