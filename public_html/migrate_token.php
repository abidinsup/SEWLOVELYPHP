<?php
require_once 'includes/config.php';

try {
    $pdo->exec("ALTER TABLE invoices ADD COLUMN secure_token VARCHAR(64) DEFAULT NULL AFTER invoice_number");
    $pdo->exec("ALTER TABLE invoices ADD UNIQUE KEY secure_token (secure_token)");
    
    // Generate tokens for existing invoices
    $stmt = $pdo->query("SELECT id FROM invoices WHERE secure_token IS NULL");
    while ($row = $stmt->fetch()) {
        $token = bin2hex(random_bytes(16));
        $updateStmt = $pdo->prepare("UPDATE invoices SET secure_token = ? WHERE id = ?");
        $updateStmt->execute([$token, $row['id']]);
    }
    
    echo "Migration successful.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Migration successful (already applied).";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
