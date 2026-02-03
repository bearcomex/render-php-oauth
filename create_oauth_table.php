<?php
$dsn = 'pgsql:host=dpg-d5vv58soud1c738tk8sg-a.virginia-postgres.render.com;port=5432;dbname=oauth_db_xiqr;sslmode=require';
$user = 'oauth_db_xiqr_user';
$pass = 'm9MGFrvs6EbuxQACEDh9HWy43KCaKlsV';

try {
    $pdo = new PDO($dsn, $user, $pass);

    $sql = "
    CREATE TABLE IF NOT EXISTS oauth_users (
        id SERIAL PRIMARY KEY,
        user_email VARCHAR(255) NOT NULL,
        oid VARCHAR(255) UNIQUE NOT NULL,
        access_token TEXT NOT NULL,
        refresh_token TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT NOW(),
        updated_at TIMESTAMP DEFAULT NOW()
    );
    ";

    $pdo->exec($sql);
    echo "✅ Table 'oauth_users' created or already exists!";
} catch (PDOException $e) {
    echo "⚠️ Error: " . $e->getMessage();
}
