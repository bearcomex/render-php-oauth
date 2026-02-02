<?php
// --- DB connection ---
$db_host = 'dpg-d5vv58soud1c738tk8sg-a.virginia-postgres.render.com';
$db_name = 'oauth_db_xiqr';
$db_user = 'oauth_db_xiqr_user';
$db_pass = 'm9MGFrvs6EbuxQACEDh9HWy43KCaKlsV';

try {
    $pdo = new PDO("pgsql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all rows
    $stmt = $pdo->query("SELECT * FROM oauth_users ORDER BY created_at DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<pre>";
    print_r($rows);
    echo "</pre>";
} catch (PDOException $e) {
    echo "DB error: " . $e->getMessage();
}
