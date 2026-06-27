<?php
// Script untuk memperbaiki tabel partners yang kurang kolom is_active dan status
require_once 'includes/config.php';

echo "<h2>Memperbarui Database...</h2>";

try {
    // Tambah kolom is_active
    try {
        $pdo->exec("ALTER TABLE partners ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 1");
        echo "✅ Kolom 'is_active' berhasil ditambahkan.<br>";
    } catch (PDOException $e) {
        // Abaikan jika sudah ada
        echo "ℹ️ Info 'is_active': Sudah ada atau error lain (" . $e->getMessage() . ").<br>";
    }

    // Tambah kolom status
    try {
        $pdo->exec("ALTER TABLE partners ADD COLUMN status varchar(20) DEFAULT 'pending'");
        echo "✅ Kolom 'status' berhasil ditambahkan.<br>";
    } catch (PDOException $e) {
        // Abaikan jika sudah ada
        echo "ℹ️ Info 'status': Sudah ada atau error lain (" . $e->getMessage() . ").<br>";
    }

    echo "<br>🎉 Update tabel partners selesai! Silakan kembali ke halaman register dan coba daftar lagi.";
    
} catch (Exception $e) {
    echo "❌ Terjadi error sistem: " . $e->getMessage();
}
?>
