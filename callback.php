<?php
session_start();

/* ACTION 2.1 — Read the authorization code */
if (!isset($_GET['code'])) {
    echo "No authorization code received";
    exit;
}

$code = $_GET['code'];

/* ACTION 2.2 — Define Microsoft endpoints */
$tenant_id = "ecc697bd-ebb8-4055-8d5d-804f28f5cbe0";
$client_id = "6dff28e9-1e23-4b52-ad14-b1f2b4ed3525";
$client_secret = getenv("CLIENT_SECRET");
$redirect_uri = "https://render-php-oauth.render.com/callback.php";

$token_url = "https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/token";

/* ACTION 2.3 — Build POST request */
$data = [
    "client_id" => $client_id,
    "scope" => "openid profile offline_access",
    "code" => $code,
    "redirect_uri" => $redirect_uri,
    "grant_type" => "authorization_code",
    "client_secret" => $client_secret
];

$options = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/x-www-form-urlencoded",
        "content" => http_build_query($data)
    ]
];

/* ACTION 2.4 — Send request to Microsoft */
$response = file_get_contents($token_url, false, stream_context_create($options));

if ($response === false) {
    echo "Token request failed";
    exit;
}

/* ACTION 2.5 — Store token server-side */
$token = json_decode($response, true);
$_SESSION['access_token'] = $token['access_token'];

echo "Access granted. Backend token stored.";
