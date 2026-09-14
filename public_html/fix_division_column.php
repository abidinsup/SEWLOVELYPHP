<?php
/**
 * Migration Script: Fix kolom division pada tabel partners
 * 
 * Jalankan script ini SEKALI di production via browser:
 * https://yourdomain.com/fix_division_column.php
 * 
 * Apa yang dilakukan:
 * 1. Menambahkan kolom 'division' jika belum ada
 * 2. Mengupdate data NULL division → 'marketing'
 * 3. Mengupdate data NULL commission_percentage → 5.00
 */
require_once 'includes/config.php';

echo "<h2>🔧 Migration: Fix Kolom Division & Commission</h2>";
echo "<hr>";

$changes = 0;

// 1. Tambah kolom division jika belum ada
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM partners LIKE 'division'");
    if ($checkCol->rowCount() == 0) {
        $pdo->exec("ALTER TABLE partners ADD COLUMN division VARCHAR(50) DEFAULT 'marketing' AFTER account_holder");
        echo "✅ Kolom 'division' berhasil ditambahkan ke tabel partners.<br>";
        $changes++;
    } else {
        echo "ℹ️ Kolom 'division' sudah ada.<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error tambah kolom division: " . $e->getMessage() . "<br>";
}

// 2. Update semua division yang NULL menjadi 'marketing'
try {
    $stmt = $pdo->exec("UPDATE partners SET division = 'marketing' WHERE division IS NULL OR division = ''");
    if ($stmt > 0) {
        echo "✅ $stmt partner diupdate: division → 'marketing'.<br>";
        $changes++;
    } else {
        echo "ℹ️ Tidak ada partner dengan division NULL.<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error update division: " . $e->getMessage() . "<br>";
}

// 3. Update commission_percentage yang NULL menjadi 5.00
try {
    $stmt = $pdo->exec("UPDATE partners SET commission_percentage = 5.00 WHERE commission_percentage IS NULL");
    if ($stmt > 0) {
        echo "✅ $stmt partner diupdate: commission_percentage → 5.00.<br>";
        $changes++;
    } else {
        echo "ℹ️ Tidak ada partner dengan commission_percentage NULL.<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error update commission: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// 4. Tampilkan semua data partner sebagai verifikasi
echo "<h3>📋 Data Partners Saat Ini:</h3>";
try {
    $stmt = $pdo->query("
        SELECT p.id, p.full_name, p.division, p.commission_percentage, p.status, p.is_active, u.email
        FROM partners p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.id
    ");
    $partners = $stmt->fetchAll();
    
    if (empty($partners)) {
        echo "<p>⚠️ Tidak ada data partner di database.</p>";
    } else {
        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; font-family:sans-serif;'>";
        echo "<tr style='background:#f0f0f0; font-weight:bold;'>";
        echo "<td>ID</td><td>Nama</td><td>Email</td><td>Division</td><td>Commission %</td><td>Status</td><td>Active</td>";
        echo "</tr>";
        foreach ($partners as $p) {
            $rowColor = ($p['division'] === null || $p['division'] === '') ? '#fff3cd' : '#d4edda';
            echo "<tr style='background:$rowColor'>";
            echo "<td>{$p['id']}</td>";
            echo "<td>{$p['full_name']}</td>";
            echo "<td>{$p['email']}</td>";
            echo "<td>" . ($p['division'] ?: '<em>NULL</em>') . "</td>";
            echo "<td>" . ($p['commission_percentage'] ?? '<em>NULL</em>') . "</td>";
            echo "<td>{$p['status']}</td>";
            echo "<td>" . ($p['is_active'] ? 'Ya' : 'Tidak') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "❌ Error menampilkan data: " . $e->getMessage() . "<br>";
}

// 5. Verifikasi schema
echo "<h3>📊 Schema Tabel Partners:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE partners");
    $cols = $stmt->fetchAll();
    echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse; font-family:sans-serif;'>";
    echo "<tr style='background:#f0f0f0; font-weight:bold;'><td>Field</td><td>Type</td><td>Null</td><td>Default</td></tr>";
    foreach ($cols as $c) {
        $highlight = in_array($c['Field'], ['division', 'commission_percentage']) ? "background:#d4edda;" : "";
        echo "<tr style='$highlight'><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td><td>{$c['Default']}</td></tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<hr>";
echo "<p style='color:green; font-weight:bold;'>🎉 Migration selesai! Total perubahan: $changes</p>";
echo "<p>⚠️ <strong>PENTING:</strong> Setelah dijalankan, hapus atau rename file ini dari server production demi keamanan.</p>";
?>
