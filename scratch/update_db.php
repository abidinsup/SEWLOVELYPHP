<?php
require_once 'public_html/includes/config.php';
try {
    // Add status column to partners
    $pdo->exec("ALTER TABLE partners ADD COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER account_holder");
    
    // Update existing partners to approved
    $pdo->exec("UPDATE partners SET status = 'approved' WHERE is_active = 1");
    $pdo->exec("UPDATE partners SET status = 'pending' WHERE is_active = 0");
    
    echo "Database updated successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
