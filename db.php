<?php
// db.php – database connection to cPanel MySQL
$host = 'localhost';         // If your cPanel allows remote access, use the hostname/IP for Render
$db   = 'bearco79_oauth-db';
$user = 'bearco79_oauth-user';
$pass = 'Loverainbow5@';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
