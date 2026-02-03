<?php
require_once 'db.php';

try {
    $pdo->exec("
        ALTER TABLE oauth_users
        ADD CONSTRAINT oauth_users_oid_pk PRIMARY KEY (oid)
    ");
    echo "✅ DB FIX SUCCESS: oid is now PRIMARY KEY";
} catch (PDOException $e) {
    echo "⚠️ DB FIX RESULT: " . $e->getMessage();
}
