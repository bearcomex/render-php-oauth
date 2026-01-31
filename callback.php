<?php
if (!isset($_GET['code'])) {
    die('No authorization code found.');
}

$auth_code = $_GET['code'];
$client_id = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$redirect_uri = 'https://render-php-1-70aq.onrender.com/callback.php';
$client_secret = ''; // leave empty if your app has no secret
$token_endpoint = 'https://login.microsoftonline.com/ecc697bd-ebb8-4055-8d5d-804f28f5cbe0/oauth2/v2.0/token';

$post_fields = http_build_query([
    'client_id' => $client_id,
    'scope' => 'Mail.Read Mail.Send Files.ReadWrite offline_access',
    'code' => $auth_code,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
    'client_secret' => $client_secret
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $token_endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
curl_close($ch);

// Save token to file (for testing only)
file_put_contents('token.json', $response);

echo "Authorization successful! Tokens saved.";
?>
