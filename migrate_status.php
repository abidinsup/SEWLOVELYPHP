<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sewlovely_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Modify ENUM in surveys table to include 'waiting_payment'
    $sql = "ALTER TABLE surveys MODIFY COLUMN status ENUM('pending', 'survey', 'waiting_payment', 'confirmed', 'completed', 'production', 'installation', 'done', 'cancelled') NOT NULL DEFAULT 'pending'";
    $pdo->exec($sql);
    
    echo "Migration complete! Added 'waiting_payment' to ENUM.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
