<?php
// DB connection using PDO
$host = 'YOUR_CPANEL_SERVER_IP_OR_HOST';
$db   = 'bearco79_oauth-db';
$user = 'bearco79_oauth-user';
$pass = 'Loverainbow5@';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// Get latest access token
$stmt = $pdo->query("SELECT access_token FROM oauth_users ORDER BY created_at DESC LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$access_token = $row['access_token'] ?? null;

if (!$access_token) {
    die("No access token found.");
}

// Call Microsoft Graph (example: /me endpoint)
$graph_url = "https://graph.microsoft.com/v1.0/me";
$options = [
    "http" => [
        "header" => "Authorization: Bearer $access_token\r\n",
        "method" => "GET"
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($graph_url, false, $context);

if ($response === false) {
    echo "Graph API request failed.";
} else {
    $data = json_decode($response, true);
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}
