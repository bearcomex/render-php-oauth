<?php
require_once 'db.php';

// Get input parameters from query string
// Example: graph_call.php?user=username@example.com&endpoint=/me
$username = $_GET['user'] ?? '';
$email = $_GET['email'] ?? '';
$endpoint = $_GET['endpoint'] ?? '/me'; // default Graph endpoint

if (empty($username) && empty($email)) {
    die(json_encode([
        'error' => 'Please provide a username or email as ?user= or ?email='
    ], JSON_PRETTY_PRINT));
}

// Build query to fetch access token
if (!empty($username)) {
    $stmt = $pdo->prepare("SELECT access_token FROM oauth_users WHERE username = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$username]);
} else {
    $stmt = $pdo->prepare("SELECT access_token FROM oauth_users WHERE email = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$email]);
}

$token = $stmt->fetchColumn();

if (!$token) {
    die(json_encode([
        'error' => 'No access token found for the provided user/email'
    ], JSON_PRETTY_PRINT));
}

// Prepare Graph API call
$graphUrl = "https://graph.microsoft.com/v1.0" . $endpoint;

$ch = curl_init($graphUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    die(json_encode([
        'error' => 'cURL error: ' . curl_error($ch)
    ], JSON_PRETTY_PRINT));
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Output JSON
header("Content-Type: application/json");
echo json_encode([
    'http_code' => $httpCode,
    'graph_endpoint' => $endpoint,
    'graph_response' => json_decode($response, true)
], JSON_PRETTY_PRINT);
