<?php
// callback.php

// Get DB connection from environment
$databaseUrl = getenv('DATABASE_URL');
if (!$databaseUrl) {
    die("DB connection not configured");
}

// Parse DATABASE_URL
$parsed = parse_url($databaseUrl);
$dbHost = $parsed['host'];
$dbPort = $parsed['port'] ?? 5432;
$dbName = ltrim($parsed['path'], '/');
$dbUser = $parsed['user'];
$dbPass = $parsed['pass'];

try {
    $pdo = new PDO("pgsql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("DB connection failed: " . $e->getMessage());
}

// App credentials (use environment variables if possible)
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$client_secret = 'de38Q~901_U0wQBpNYt5hhoNNJqBUs4CehuQFaiM';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';
$token_url = "https://login.microsoftonline.com/common/oauth2/v2.0/token";

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Prepare POST data for token exchange
    $postData = http_build_query([
        'client_id' => $client_id,
        'scope' => 'openid profile email User.Read',
        'code' => $code,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code',
        'client_secret' => $client_secret
    ]);

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $result = curl_exec($ch);
    curl_close($ch);

    if (!$result) {
        error_log("Token request failed");
        echo "Sorry, something went wrong. Please try again later.";
        exit;
    }

    $token_response = json_decode($result, true);

    if (!isset($token_response['access_token'])) {
        echo "Token request failed. Please try again.";
        exit;
    }

    // Decode ID token to get user info
    $id_token_parts = explode('.', $token_response['id_token']);
    $payload = json_decode(base64_decode(strtr($id_token_parts[1], '-_', '+/')), true);

    $oid = $payload['oid'] ?? '';
    $username = $payload['preferred_username'] ?? '';
    $email = $payload['email'] ?? '';

    // Store token in Postgres
    $stmt = $pdo->prepare("INSERT INTO oauth_users (oid, username, email, access_token, id_token, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$oid, $username, $email, $token_response['access_token'], $token_response['id_token']]);

    // Show only thank-you message
    echo "<h2>Thank you! You have successfully logged in.</h2>";

} else {
    echo "No authorization code received.";
}
