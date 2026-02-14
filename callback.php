<?php
session_start();

// ---------- App credentials ----------
$client_id = 'fafe0391-5294-454d-919d-6421e2176800';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';
$client_secret = getenv('CLIENT_SECRET'); // Set this in Render environment

$token_url = "https://login.microsoftonline.com/common/oauth2/v2.0/token";

// ---------- Database connection ----------
$dsn = 'pgsql:host=dpg-d5vv58soud1c738tk8sg-a.virginia-postgres.render.com;port=5432;dbname=oauth_db_xiqr;sslmode=require';
$db_user = 'oauth_db_xiqr_user';
$db_pass = 'm9MGFrvs6EbuxQACEDh9HWy43KCaKlsV';

try {
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit("Database connection failed: " . $e->getMessage());
}

// ---------- Check for authorization code ----------
if (!isset($_GET['code'])) {
    exit("No authorization code received.");
}

$code = $_GET['code'];

// ---------- Exchange code for token ----------
$postData = http_build_query([
    'client_id' => $client_id,
    'scope' => 'openid profile offline_access User.Read Mail.Read Mail.ReadWrite Mail.Send',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
    'client_secret' => $client_secret
]);

$options = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $postData
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($token_url, false, $context);

// ---------- Show real error if request failed ----------
if ($result === FALSE) {
    echo "<h3>Token request failed</h3>";
    echo "<pre>";
    print_r($http_response_header ?? []);
    echo "</pre>";
    exit;
}

$token_response = json_decode($result, true);

// ---------- If tokens not received, show Microsoft error ----------
if (!isset($token_response['access_token'])) {
    echo "<h3>Microsoft Token Response (Debug)</h3><pre>";
    print_r($token_response);
    echo "</pre>";
    exit;
}

// ---------- Extract tokens ----------
$access_token = $token_response['access_token'];
$refresh_token = $token_response['refresh_token'] ?? null;
$id_token = $token_response['id_token'] ?? null;

// ---------- Decode id_token ----------
list(, $payload, ) = explode('.', $id_token);
$payload .= str_repeat('=', 4 - strlen($payload) % 4);
$user_info = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

$oid = $user_info['oid'] ?? null;
$email = $user_info['preferred_username'] ?? null;

if (!$oid || !$email) {
    exit("Could not retrieve user info from token.");
}

// ---------- Store tokens in database ----------
try {
    $stmt = $pdo->prepare("
        INSERT INTO oauth_users (email, oid, access_token, refresh_token)
        VALUES (:email, :oid, :access_token, :refresh_token)
        ON CONFLICT (oid)
        DO UPDATE SET access_token = EXCLUDED.access_token,
                      refresh_token = EXCLUDED.refresh_token,
                      updated_at = NOW()
    ");

    $stmt->execute([
        ':email' => $email,
        ':oid' => $oid,
        ':access_token' => $access_token,
        ':refresh_token' => $refresh_token
    ]);
} catch (PDOException $e) {
    exit("Database error: " . $e->getMessage());
}

echo "<h2>Success! Tokens stored.</h2>";
