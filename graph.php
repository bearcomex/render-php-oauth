<?php
session_start();

/* 1. Ensure we have the token */
if (!isset($_SESSION['access_token'])) {
    http_response_code(401);
    echo "No access token found. Complete the OAuth flow first.";
    exit;
}

$access_token = $_SESSION['access_token'];

/* 2. Microsoft Graph endpoint */
$graph_url = "https://graph.microsoft.com/v1.0/me";

/* 3. Initialize cURL */
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $graph_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
]);

/* 4. Execute request */
$response = curl_exec($ch);
if ($response === false) {
    http_response_code(500);
    echo "Graph API request failed: " . curl_error($ch);
    exit;
}

curl_close($ch);

/* 5. Output the result (for testing) */
header("Content-Type: application/json");
echo $response;
