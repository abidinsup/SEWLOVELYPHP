<?php
require_once 'c:/SEWLOVELY V1/public_html/includes/config.php';

// Check if curtain_fabrics exists
$stmt = $pdo->query("SHOW TABLES LIKE 'curtain_fabrics'");
if ($stmt->rowCount() == 0) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `curtain_fabrics` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `type` enum('gorden','vitrase') NOT NULL DEFAULT 'gorden',
            `price_per_meter` decimal(12,2) NOT NULL DEFAULT 0.00,
            `description` text DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

$stmt = $pdo->query("SHOW TABLES LIKE 'curtain_rails'");
if ($stmt->rowCount() == 0) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `curtain_rails` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `type` enum('single','double') NOT NULL DEFAULT 'single',
            `price_per_meter` decimal(12,2) NOT NULL DEFAULT 0.00,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

$stmt = $pdo->query("SELECT COUNT(*) FROM curtain_fabrics");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
        INSERT INTO curtain_fabrics (name, type, price_per_meter, description) VALUES
        ('Blackout Premium Polos', 'gorden', 85000, 'Kain blackout tebal anti cahaya, cocok untuk kamar tidur'),
        ('Blackout Motif Emboss', 'gorden', 95000, 'Kain blackout dengan motif emboss premium'),
        ('Gorden Polos Grade A', 'gorden', 65000, 'Kain gorden polos berkualitas tinggi'),
        ('Gorden Motif Bunga', 'gorden', 75000, 'Kain gorden motif bunga elegan'),
        ('Gorden Dimout', 'gorden', 70000, 'Kain gorden semi-transparan, cahaya redup'),
        ('Vitrase Polos Putih', 'vitrase', 35000, 'Kain vitrase putih tipis transparan'),
        ('Vitrase Motif Bordir', 'vitrase', 50000, 'Kain vitrase dengan motif bordir cantik'),
        ('Vitrase Organza', 'vitrase', 45000, 'Kain vitrase organza premium transparan')
    ");
}

$stmt = $pdo->query("SELECT COUNT(*) FROM curtain_rails");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("
        INSERT INTO curtain_rails (name, type, price_per_meter) VALUES
        ('Rel Aluminium Single', 'single', 45000),
        ('Rel Stainless Single', 'single', 65000),
        ('Rel Aluminium Double (Twin)', 'double', 75000),
        ('Rel Stainless Double (Twin)', 'double', 95000)
    ");
}

echo "Curtain tables migrated.\n";

// migrate_cart_json
$stmt = $pdo->query("SHOW COLUMNS FROM surveys LIKE 'cart_items'");
if ($stmt->rowCount() === 0) {
    $pdo->exec("ALTER TABLE surveys ADD COLUMN cart_items JSON DEFAULT NULL AFTER package_type");
    echo "Added cart_items to surveys.\n";
}

// migrate_discount
$stmt = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'discount_amount'");
if ($stmt->rowCount() === 0) {
    $pdo->exec("ALTER TABLE invoices ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER admin_fee");
    echo "Added discount_amount to invoices.\n";
}

// migrate_paid_amount
$stmt = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'paid_amount'");
if ($stmt->rowCount() === 0) {
    $pdo->exec("ALTER TABLE invoices ADD COLUMN paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
    echo "Added paid_amount to invoices.\n";
}

// migrate_db
$stmt = $pdo->query("SHOW COLUMNS FROM partners LIKE 'is_active'");
if ($stmt->rowCount() === 0) {
    $pdo->exec("ALTER TABLE partners ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER commission_percentage");
}
$stmt = $pdo->query("SHOW COLUMNS FROM surveys LIKE 'status'");
$row = $stmt->fetch();
if ($row && strpos($row['Type'], 'production') === false) {
    $pdo->exec("ALTER TABLE surveys MODIFY COLUMN status ENUM('pending','confirmed','completed','production','installation','done','cancelled') NOT NULL DEFAULT 'pending'");
}

echo "All migrations completed.\n";
