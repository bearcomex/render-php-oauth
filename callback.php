<?php
// Your app credentials
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$client_secret = 'de38Q~901_U0wQBpNYt5hhoNNJqBUs4CehuQFaiM'; // Your actual client secret
$redirect_uri = 'https://render-php-oauth.onrender.com/callback.php';
$tenant_id = 'ecc697bd-ebb8-4055-8d5d-804f28f5cbe0';

if (isset($_GET['code'])) {
    $code = $_GET['code'];

    // Prepare POST data for token request
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
        // Fetch HTTP response headers for more info
        $error = $http_response_header ?? [];
        echo "<h2>Token request failed</h2>";
        echo "<pre>";
        print_r($error);
        echo "</pre>";
    } else {
        $token_response = json_decode($result, true);
        echo "<h2>Token Response</h2><pre>";
        print_r($token_response);
        echo "</pre>";
    }

} else {
    echo "No authorization code received.";
}
