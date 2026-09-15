<?php
/**
 * Migrasi Database: Lainnya → Affiliate
 * 
 * Cara pakai:
 * 1. Deploy ke server via Git
 * 2. Buka di browser: https://domainmu.com/migrate_affiliate_db.php
 * 3. File ini akan otomatis terhapus setelah berhasil
 */

require_once __DIR__ . '/includes/config.php';

header('Content-Type: text/html; charset=UTF-8');
echo "<h2>🔄 Migrasi Division: Lainnya → Affiliate</h2>";

try {
    // Step 1: Tambah 'affiliate' ke ENUM (sementara biarkan 'lainnya')
    $pdo->exec("ALTER TABLE partners MODIFY division ENUM('marketing','security','lainnya','affiliate') NOT NULL DEFAULT 'marketing'");
    echo "<p>✅ Step 1: ENUM ditambah 'affiliate'</p>";

    // Step 2: Migrasi data lama
    $stmt = $pdo->query("UPDATE partners SET division='affiliate' WHERE division='lainnya'");
    $affected = $stmt->rowCount();
    echo "<p>✅ Step 2: $affected baris dimigrasi dari 'lainnya' ke 'affiliate'</p>";

    // Step 3: Hapus 'lainnya' dari ENUM
    $pdo->exec("ALTER TABLE partners MODIFY division ENUM('marketing','security','affiliate') NOT NULL DEFAULT 'marketing'");
    echo "<p>✅ Step 3: 'lainnya' dihapus dari ENUM</p>";

    echo "<h3 style='color:green'>🎉 Migrasi selesai!</h3>";

    // Hapus file ini otomatis setelah sukses
    unlink(__FILE__);
    echo "<p><i>File migrasi ini sudah dihapus otomatis dari server.</i></p>";

} catch (PDOException $e) {
    echo "<h3 style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h3>";
    echo "<p>Hubungi developer untuk bantuan.</p>";
}
?>
