<?php
require_once 'db.php';

$sql = "
CREATE TABLE IF NOT EXISTS oauth_users (
    oid TEXT PRIMARY KEY,
    username TEXT,
    email TEXT,
    access_token TEXT,
    id_token TEXT
);
";

try {
    $pdo->exec($sql);
    echo "Table oauth_users ready.";
} catch (PDOException $e) {
    die("DB error: " . $e->getMessage());
}
