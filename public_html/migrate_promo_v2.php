<?php
/**
 * Migration Script: Promo Banners V2 (3 Banners)
 * 
 * Jalankan script ini SEKALI di production via browser:
 * https://yourdomain.com/migrate_promo_v2.php
 */
require_once 'includes/config.php';

echo "<h2>🔧 Migration: Promo Banners V2 (3 Banners)</h2>";
echo "<hr>";

$settings = [
    // Banner 1 (Default: Existing banner or default text)
    ['promo_banner_1_active', '1'],
    ['promo_banner_1_title', "Raih Bonusnya!\nSelesaikan 5 Pemasangan"],
    ['promo_banner_1_desc', "Selesaikan 5 projek pemasangan dan\ndapatkan komisi tambahan "],
    ['promo_banner_1_highlight', "Rp 300.000"],
    ['promo_banner_1_image', ""],
    
    // Banner 2
    ['promo_banner_2_active', '1'],
    ['promo_banner_2_title', "Promo Spesial!\nGratis Biaya Survey"],
    ['promo_banner_2_desc', "Dapatkan gratis biaya survey untuk\npemasangan gorden minimal 5 set."],
    ['promo_banner_2_highlight', "GRATIS!"],
    ['promo_banner_2_image', ""],
    
    // Banner 3
    ['promo_banner_3_active', '1'],
    ['promo_banner_3_title', "Kejar Target!\nDapatkan Reward Liburan"],
    ['promo_banner_3_desc', "Capai target penjualan bulan ini dan\nmenangkan tiket liburan."],
    ['promo_banner_3_highlight', "Ke Bali!"],
    ['promo_banner_3_image', ""]
];

$changes = 0;

try {
    // Pastikan tabel app_settings ada
    $stmt = $pdo->query("SHOW TABLES LIKE 'app_settings'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `app_settings` (
              `setting_key` varchar(50) NOT NULL,
              `setting_value` text DEFAULT NULL,
              PRIMARY KEY (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        echo "✅ Tabel 'app_settings' berhasil dibuat.<br>";
        $changes++;
    }

    $stmt = $pdo->prepare("INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute([$setting[0], $setting[1]]);
        if ($stmt->rowCount() > 0) {
            echo "✅ Setting '{$setting[0]}' ditambahkan.<br>";
            $changes++;
        }
    }

} catch (PDOException $e) {
    echo "❌ Error database: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p style='color:green; font-weight:bold;'>🎉 Migration selesai! Total penambahan: $changes</p>";
echo "<p>⚠️ <strong>PENTING:</strong> Setelah dijalankan, hapus atau rename file ini dari server production demi keamanan.</p>";
?>
