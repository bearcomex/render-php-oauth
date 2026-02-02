<?php
// create_table.php

// Get the DATABASE_URL from environment
$databaseUrl = getenv('DATABASE_URL');
if (!$databaseUrl) {
    die("DATABASE_URL not set in environment");
}

// Parse DATABASE_URL
$parsed = parse_url($databaseUrl);
$dbHost = $parsed['host'];
$dbPort = $parsed['port'] ?? 5432;
$dbName = ltrim($parsed['path'], '/');
$dbUser = $parsed['user'];
$dbPass = $parsed['pass'];

// Connect to Postgres
try {
    $pdo = new PDO("pgsql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("DB connection failed: " . $e->getMessage());
}

// Create table if not exists
$sql = "
CREATE TABLE IF NOT EXISTS oauth_users (
    id SERIAL PRIMARY KEY,
    oid VARCHAR(255),
    username VARCHAR(255),
    email VARCHAR(255),
    access_token TEXT NOT NULL,
    id_token TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);
";

try {
    $pdo->exec($sql);
    echo "Table 'oauth_users' created successfully.";
} catch (Exception $e) {
    die("Failed to create table: " . $e->getMessage());
}
