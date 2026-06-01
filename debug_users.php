<?php
require_once 'public_html/includes/config.php';

try {
    $stmt = $pdo->query("SELECT id, email, password_hash, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "USERS IN DATABASE:\n";
    foreach ($users as $u) {
        echo "ID: {$u['id']} | Email: {$u['email']} | Role: {$u['role']} | Hash: {$u['password_hash']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
