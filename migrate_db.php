<?php
/**
 * Migration: Add is_active column to partners table
 * Run once to update existing database schema
 */
require_once 'public_html/includes/config.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM partners LIKE 'is_active'");
    if ($stmt->rowCount() === 0) {
        $pdo->exec("ALTER TABLE partners ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER commission_percentage");
        echo "✅ Column 'is_active' added to partners table successfully.\n";
    } else {
        echo "ℹ️ Column 'is_active' already exists. No changes needed.\n";
    }

    // Also add 'production' to surveys status enum if not present
    $stmt = $pdo->query("SHOW COLUMNS FROM surveys LIKE 'status'");
    $row = $stmt->fetch();
    if ($row && strpos($row['Type'], 'production') === false) {
        $pdo->exec("ALTER TABLE surveys MODIFY COLUMN status ENUM('pending','confirmed','completed','production','installation','done','cancelled') NOT NULL DEFAULT 'pending'");
        echo "✅ Added 'production' to surveys status enum.\n";
    } else {
        echo "ℹ️ Surveys status enum already includes 'production'.\n";
    }

    echo "\n🎉 Migration completed!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
