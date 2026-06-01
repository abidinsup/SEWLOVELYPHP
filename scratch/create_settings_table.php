<?php
require_once 'public_html/includes/config.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS `settings` (
        `setting_key` varchar(50) NOT NULL,
        `setting_value` text DEFAULT NULL,
        PRIMARY KEY (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    
    // Insert default settings if empty
    $defaults = [
        'store_name' => 'Sewlovely Homeset',
        'store_address' => 'Jl. Raya Jahit No. 123, Jakarta',
        'store_phone' => '081234567890',
        'default_commission' => '5',
        'invoice_logo' => '',
        'invoice_terms' => "1. Pembayaran DP minimal 50% dari total tagihan.\n2. Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.\n3. Pelunasan dilakukan saat barang akan dikirim/dipasang.",
        'bank_info' => "BCA 1234567890 a.n Sewlovely Homeset"
    ];
    
    foreach ($defaults as $k => $v) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$k, $v]);
    }

    echo "Table 'settings' created and seeded successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
