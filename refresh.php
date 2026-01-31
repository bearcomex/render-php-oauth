<?php
session_start();

if (!isset($_SESSION['refresh_token'])) {
    echo "No refresh token found. User must authorize again.";
    exit;
}

$tenant_id = "ecc697bd-ebb8-4055-8d5d-804f28f5cbe0";
$client_id = "6dff28e9-1e23-4b52-ad14-b1f2b4ed3525";
$client_secret = getenv("CLIENT_SECRET");
$redirect_uri = "https://render-php-oauth.render.com/callback.php";

$token_url = "https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/token";

/* Build POST request */
$data = [
    "client_id" => $client_id,
    "scope" => "openid profile offline_access",
    "refresh_token" => $_SESSION['refresh_token'],
    "redirect_uri" => $redirect_uri,
    "grant_type" => "refresh_token",
    "client_secret" => $client_secret
];

$options = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/x-www-form-urlencoded",
        "content" => http_build_query($data)
    ]
];

$response = file_get_contents($token_url, false, stream_context_create($options));

if ($response === false) {
    echo "Token refresh failed.";
    exit;
}

$token = json_decode($response, true);

/* Update session with new tokens */
$_SESSION['access_token'] = $token['access_token'];
$_SESSION['refresh_token'] = $token['refresh_token'];
$_SESSION['expires_at'] = time() + $token['expires_in'];

echo "Access token refreshed successfully.";
