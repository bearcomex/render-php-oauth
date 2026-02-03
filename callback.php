<?php
session_start();
require_once 'db.php'; // your Render DB connection

// Azure App credentials
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';
$client_secret = getenv('CLIENT_SECRET'); // Set this in Render environment variables

$token_url = "https://login.microsoftonline.com/common/oauth2/v2.0/token";

if (!isset($_GET['code'])) {
    die("No authorization code received.");
}

$code = $_GET['code'];

// Prepare POST data with new granted scopes
$postData = http_build_query([
    'client_id' => $client_id,
    'scope' => 'openid profile email User.Read',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
    'client_secret' => $client_secret
]);

// Use cURL to request token
$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/x-www-form-urlencoded"
]);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    die("Token request failed with HTTP code $httpCode. Response: $result");
}

$token_response = json_decode($result, true);

// Decode ID token to get user info
$id_token_parts = explode('.', $token_response['id_token']);
$payload = json_decode(base64_decode(strtr($id_token_parts[1], '-_', '+/')), true);

$oid = $payload['oid'] ?? '';
$username = $payload['preferred_username'] ?? '';
$email = $payload['email'] ?? '';

// Store tokens in Render Postgres DB
try {
    $stmt = $pdo->prepare("
        INSERT INTO oauth_users (oid, username, email, access_token, id_token)
        VALUES (?, ?, ?, ?, ?)
        ON CONFLICT (oid) DO UPDATE
        SET username = EXCLUDED.username,
            email = EXCLUDED.email,
            access_token = EXCLUDED.access_token,
            id_token = EXCLUDED.id_token
    ");
    $stmt->execute([
        $oid,
        $username,
        $email,
        $token_response['access_token'],
        $token_response['id_token']
    ]);
} catch (Exception $e) {
    die("Failed to store tokens: " . $e->getMessage());
}

// Success message
echo "<h2>Thank you! You have successfully logged in.</h2>";
echo "<p>User: $username ($email)</p>";
echo "<h3>Token Response:</h3><pre>";
print_r($token_response);
echo "</pre>";
