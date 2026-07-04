<?php
$host = '127.0.0.1';
$db   = 'sewlovely_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Drop foreign key if exists
    try {
        $pdo->exec("ALTER TABLE fcm_tokens DROP FOREIGN KEY fcm_tokens_ibfk_1");
    } catch (Exception $e) {}

    // Alter user_id to allow NULL
    $pdo->exec("ALTER TABLE fcm_tokens MODIFY user_id INT NULL");
    echo "Migration successful!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
