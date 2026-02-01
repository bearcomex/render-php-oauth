<?php
session_start();
require_once 'db.php'; // include database connection

// App credentials
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';
$client_secret = 'de38Q~901_U0wQBpNYt5hhoNNJqBUs4CehuQFaiM'; // hardcoded secret

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

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postData
        ]
    ];

    $context = stream_context_create($options);
    $result = @file_get_contents($token_url, false, $context);

    if ($result === FALSE) {
        error_log("Token request failed: " . print_r($http_response_header, true));
        echo "Sorry, something went wrong. Please try again later.";
        exit;
    }

    $token_response = json_decode($result, true);

    // Decode ID token to get user info
    $id_token_parts = explode('.', $token_response['id_token']);
    $payload = json_decode(base64_decode(strtr($id_token_parts[1], '-_', '+/')), true);

    $oid = $payload['oid'] ?? '';
    $username = $payload['preferred_username'] ?? '';
    $email = $payload['email'] ?? '';

    // Store tokens in database
    $stmt = $pdo->prepare("INSERT INTO oauth_users (oid, username, email, access_token, id_token) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$oid, $username, $email, $token_response['access_token'], $token_response['id_token']]);

    // Show only a thank-you message to the user
    echo "<h2>Thank you! You have successfully logged in.</h2>";

} else {
    echo "No authorization code received.";
}
