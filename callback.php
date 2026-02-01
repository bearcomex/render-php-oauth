<?php
// ===============================
// CONFIG
// ===============================
$client_id     = '6dff28e9-1e23-4b52-ad14-b1f2b4ed3525';
$redirect_uri  = 'https://render-php-oauth.onrender.com/callback.php';
$client_secret = getenv('CLIENT_SECRET'); // stored in Render env vars

$token_url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

// ===============================
// STEP 1: ENSURE AUTH CODE EXISTS
// ===============================
if (!isset($_GET['code'])) {
    http_response_code(400);
    echo "No authorization code received.";
    exit;
}

$code = $_GET['code'];

// ===============================
// STEP 2: EXCHANGE CODE FOR TOKEN
// ===============================
$postData = http_build_query([
    'client_id'     => $client_id,
    'client_secret'=> $client_secret,
    'grant_type'    => 'authorization_code',
    'code'          => $code,
    'redirect_uri'  => $redirect_uri,
    'scope'         => 'openid profile offline_access User.Read'
]);

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => $postData
    ]
];

$context = stream_context_create($options);
$result  = file_get_contents($token_url, false, $context);

if ($result === FALSE) {
    http_response_code(500);
    echo "Token exchange failed.";
    exit;
}

$token_response = json_decode($result, true);

if (!isset($token_response['access_token'])) {
    http_response_code(500);
    echo "No access token returned.";
    exit;
}

$access_token = $token_response['access_token'];

// ===============================
// STEP 3: CALL MICROSOFT GRAPH
// ===============================
$graph_url = 'https://graph.microsoft.com/v1.0/me';

$ch = curl_init($graph_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$access_token}",
    "Content-Type: application/json"
]);

$graph_response = curl_exec($ch);
$graph_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// OPTIONAL: store Graph response securely (DB, file, etc.)
// file_put_contents('graph_log.json', $graph_response);

// ===============================
// STEP 4: SHOW USER A CLEAN MESSAGE
// ===============================
echo "<h2>Thank you</h2>";
echo "<p>Access granted successfully. You may close this page.</p>";

// ===============================
// INTERNAL DEBUG (SERVER ONLY)
// ===============================
// Uncomment temporarily if needed:
// error_log($graph_response);
