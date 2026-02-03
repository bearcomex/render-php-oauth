<?php
session_start();

// ---------- App credentials ----------
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';
$client_secret = getenv('CLIENT_SECRET'); // set in Render environment

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
    'scope' => 'openid profile email User.Read',
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
$result = @file_get_contents($token_url, false, $context);

if ($result === FALSE) {
    exit("Token request failed. Please check your client ID, secret, and redirect URI.");
}

$token_response = json_decode($result, true);

// ---------- Extract tokens ----------
$access_token = $token_response['access_token'] ?? null;
$refresh_token = $token_response['refresh_token'] ?? null;
$id_token = $token_response['id_token'] ?? null;

if (!$access_token || !$refresh_token || !$id_token) {
    exit("Token not received from Azure.");
}

// ---------- Decode id_token safely ----------
list(, $payload, ) = explode('.', $id_token);

// Add padding for base64url decoding if needed
$payload .= str_repeat('=', 3 - (strlen($payload) + 3) % 4);
$user_info = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

$oid = $user_info['oid'] ?? null;
$email = $user_info['preferred_username'] ?? null;

if (!$oid || !$email) {
    exit("Could not retrieve user info from token.");
}

// ---------- Store tokens in DB ----------
try {
    $stmt = $pdo->prepare("
        INSERT INTO oauth_users (user_email, oid, access_token, refresh_token)
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

// ---------- Success message ----------
echo "<h2>Thank you! Access granted.</h2>";
