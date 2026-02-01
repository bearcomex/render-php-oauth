<?php
// App credentials
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$tenant_id = 'ecc697bd-ebb8-4055-8d5d-804f28f5cbe0';
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';

// Use environment variable for secret (never hardcode in public repo)
$client_secret = getenv('CLIENT_SECRET');

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Prepare POST data
    $postData = http_build_query([
        'client_id' => $client_id,
        'scope' => 'openid',
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
    $token_url = "https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/token";

    $result = @file_get_contents($token_url, false, $context);

    if ($result === FALSE) {
        echo "<h2>Token request failed</h2>";

        // Show HTTP headers for debugging
        $error_headers = $http_response_header ?? [];
        echo "<h3>Headers:</h3><pre>";
        print_r($error_headers);
        echo "</pre>";

        // Show body if available
        $stream = fopen($token_url, 'r', false, $context);
        if ($stream) {
            $body = stream_get_contents($stream);
            fclose($stream);
            echo "<h3>Body:</h3><pre>";
            print_r($body);
            echo "</pre>";
        }
    } else {
        $token_response = json_decode($result, true);
        echo "<h2>Token Response</h2><pre>";
        print_r($token_response);
        echo "</pre>";
    }
} else {
    echo "No authorization code received.";
}
