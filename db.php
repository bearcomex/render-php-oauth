<?php

// Render Postgres DB credentials
$host = 'dpg-d5vv58soud1c738tk8sg-a.virginia-postgres.render.com';
$port = '5432';
$dbname = 'oauth_db_xiqr';
$user = 'oauth_db_xiqr_user';
$pass = 'm9MGFrvs6EbuxQACEDh9HWy43KCaKlsV';

// DSN
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

try {
    // Connect to Postgres
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Auto-create oauth_users table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS oauth_users (
            id SERIAL PRIMARY KEY,
            oid VARCHAR(255),
            username VARCHAR(255),
            email VARCHAR(255),
            access_token TEXT NOT NULL,
            id_token TEXT,
            created_at TIMESTAMP DEFAULT NOW()
        )
    ");

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
