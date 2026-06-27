<?php
require_once 'includes/config.php';

echo "<h2>🔧 Memperbarui Database Live cPanel...</h2>";

try {
    // 1. Create curtain_fabrics
    $pdo->exec("CREATE TABLE IF NOT EXISTS `curtain_fabrics` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `type` enum('gorden','vitrase') NOT NULL,
        `price_per_meter` decimal(15,2) NOT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Tabel 'curtain_fabrics' aman.<br>";

    // 2. Create curtain_rails
    $pdo->exec("CREATE TABLE IF NOT EXISTS `curtain_rails` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `type` enum('single','double') NOT NULL,
        `price_per_meter` decimal(15,2) NOT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "✅ Tabel 'curtain_rails' aman.<br>";

    // 3. Add columns to invoices
    $cols = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'discount_amount'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
        echo "✅ Kolom 'discount_amount' ditambahkan ke tabel invoices.<br>";
    }
    
    $cols = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'invoice_notes'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN invoice_notes TEXT DEFAULT NULL AFTER payment_status");
        echo "✅ Kolom 'invoice_notes' ditambahkan ke tabel invoices.<br>";
    }
    
    $cols = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'cart_json'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN cart_json LONGTEXT DEFAULT NULL AFTER invoice_notes");
        echo "✅ Kolom 'cart_json' ditambahkan ke tabel invoices.<br>";
    }

    // 4. Add is_active to partners
    $cols = $pdo->query("SHOW COLUMNS FROM partners LIKE 'is_active'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE partners ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER commission_percentage");
        echo "✅ Kolom 'is_active' ditambahkan ke tabel partners.<br>";
    }

    // 5. Update enum in surveys
    $stmt = $pdo->query("SHOW COLUMNS FROM surveys LIKE 'status'");
    $row = $stmt->fetch();
    if ($row && strpos($row['Type'], 'production') === false) {
        $pdo->exec("ALTER TABLE surveys MODIFY COLUMN status ENUM('pending','confirmed','completed','production','installation','done','cancelled') NOT NULL DEFAULT 'pending'");
        echo "✅ Status 'production' ditambahkan ke tabel surveys.<br>";
    }

    echo "<br><h3 style='color:green;'>🎉 Database berhasil diperbarui! Silakan hapus file ini dari cPanel jika sudah selesai.</h3>";
    echo "<a href='admin/calculator.php'>Cek Kalkulator Sekarang</a>";

} catch (PDOException $e) {
    echo "<h3 style='color:red;'>❌ Error Database: " . htmlspecialchars($e->getMessage()) . "</h3>";
}
?>
