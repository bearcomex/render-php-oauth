<?php
session_start();
require_once 'db.php'; // Must be the Render-ready db.php

// Azure App credentials
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$client_secret = 'de38Q~901_U0wQBpNYt5hhoNNJqBUs4CehuQFaiM';
$tenant_id = 'ecc697bd-ebb8-4055-8d5d-804f28f5cbe0';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';

// Token endpoint
$token_url = "https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/token";

// Check if auth code is present
if (!isset($_GET['code'])) {
    die("No authorization code received.");
}

$code = $_GET['code'];

// Prepare POST data for token request
$postData = http_build_query([
    'client_id' => $client_id,
    'scope' => 'openid profile email User.Read',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
    'client_secret' => $client_secret
]);

// cURL request to get tokens
$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/x-www-form-urlencoded"
]);
$result = curl_exec($ch);

if (curl_errno($ch)) {
    die("cURL error: " . curl_error($ch));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    die("Token request failed with HTTP code $httpCode. Check client ID, secret, and redirect URI.");
}

// Decode token response
$token_response = json_decode($result, true);

// Decode ID token to get user info
$id_token_parts = explode('.', $token_response['id_token']);
$payload = json_decode(base64_decode(strtr($id_token_parts[1], '-_', '+/')), true);

$oid = $payload['oid'] ?? '';
$username = $payload['preferred_username'] ?? '';
$email = $payload['email'] ?? '';

// Store tokens in Render Postgres
try {
    $stmt = $pdo->prepare("INSERT INTO oauth_users (oid, username, email, access_token, id_token) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$oid, $username, $email, $token_response['access_token'], $token_response['id_token']]);
} catch (Exception $e) {
    die("Failed to store tokens: " . $e->getMessage());
}

// Success message
echo "<h2>Thank you! You have successfully logged in.</h2>";
echo "<p>User: $username ($email)</p>";
