<?php
// --- CONFIG ---
$token_file = __DIR__ . '/tokens.json';
$graph_url = 'https://graph.microsoft.com/v1.0/me';

// --- Get latest token ---
if (!file_exists($token_file)) {
    die("No tokens found.");
}

$all_tokens = json_decode(file_get_contents($token_file), true);
if (!$all_tokens) {
    die("Token file empty or invalid.");
}

// Get the most recent token
$latest_token = end($all_tokens);
$access_token = $latest_token['access_token'] ?? null;
if (!$access_token) {
    die("No access token available.");
}

// --- Call Microsoft Graph ---
$opts = [
    "http" => [
        "method" => "GET",
        "header" => "Authorization: Bearer $access_token\r\n" .
                    "Accept: application/json\r\n"
    ]
];

$context = stream_context_create($opts);
$response = @file_get_contents($graph_url, false, $context);

if ($response === false) {
    echo "Graph API call failed.";
    exit;
}

// Decode response
$data = json_decode($response, true);

// Display in browser (or log somewhere secure)
echo "<pre>";
print_r($data);
echo "</pre>";
