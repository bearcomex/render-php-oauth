<?php
session_start();
require_once 'db.php'; // Must contain PDO connection to Render Postgres

// Azure App credentials
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';
$client_secret = getenv('CLIENT_SECRET'); // Set this in Render environment

// Multi-tenant token endpoint
$token_url = "https://login.microsoftonline.com/common/oauth2/v2.0/token";

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Prepare POST data
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
        echo "<h2>Token request failed</h2>";

        // Show HTTP headers for debugging
        $error_headers = $http_response_header ?? [];
        echo "<h3>Headers:</h3><pre>";
        print_r($error_headers);
        echo "</pre>";

        // Attempt to show body for detailed error message
        $stream = fopen($token_url, 'r', false, $context);
        if ($stream) {
            $body = stream_get_contents($stream);
            fclose($stream);
            echo "<h3>Body:</h3><pre>";
            print_r($body);
            echo "</pre>";
        }
        exit;
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

} else {
    echo "No authorization code received.";
}
