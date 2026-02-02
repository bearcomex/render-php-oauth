<?php
// graph_call.php

$databaseUrl = getenv('DATABASE_URL');
$parsed = parse_url($databaseUrl);
$pdo = new PDO("pgsql:host={$parsed['host']};port={$parsed['port']};dbname=".ltrim($parsed['path'], '/'), $parsed['user'], $parsed['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get latest access token
$stmt = $pdo->query("SELECT access_token FROM oauth_users ORDER BY created_at DESC LIMIT 1");
$token = $stmt->fetchColumn();

if (!$token) {
    die("No tokens found. Complete OAuth flow first.");
}

// Call Microsoft Graph API
$ch = curl_init("https://graph.microsoft.com/v1.0/me");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";
