<?php
/**
 * Migration: Create curtain_fabrics and curtain_rails tables
 * Run once: http://localhost:8080/sewlovely/migrate_curtain_tables.php
 */
require_once 'includes/session.php';
require_once 'includes/config.php';
checkAdmin();

try {
    // Table: curtain_fabrics (Katalog Kain Gorden & Vitrase)
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
    echo "✅ Table curtain_fabrics created.<br>";

    // Table: curtain_rails (Katalog Rel)
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
    echo "✅ Table curtain_rails created.<br>";

    // Insert sample data for fabrics
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
        echo "✅ Sample fabrics inserted.<br>";
    }

    // Insert sample data for rails
    $stmt = $pdo->query("SELECT COUNT(*) FROM curtain_rails");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("
            INSERT INTO curtain_rails (name, type, price_per_meter) VALUES
            ('Rel Aluminium Single', 'single', 45000),
            ('Rel Stainless Single', 'single', 65000),
            ('Rel Aluminium Double (Twin)', 'double', 75000),
            ('Rel Stainless Double (Twin)', 'double', 95000)
        ");
        echo "✅ Sample rails inserted.<br>";
    }

    echo "<br>🎉 Migration completed successfully!";
    echo "<br><a href='admin/curtain_catalog.php'>→ Go to Curtain Catalog Management</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
