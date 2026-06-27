<?php
/**
 * Script Dummy Data - Testing Withdrawal WhatsApp Confirmation
 * 
 * Jalankan via browser: http://localhost/sewlovely_mobile/insert_dummy_withdrawals.php
 * 
 * Data yang di-insert:
 * - 3 Mitra (dengan nomor WhatsApp)
 * - 5 Transaksi withdrawal (3 pending, 1 success, 1 rejected)
 */

require_once 'includes/config.php';

echo "<h2>🔧 Insert Dummy Data - Withdrawal Testing</h2>";
echo "<pre style='font-family: monospace; background: #1e293b; color: #e2e8f0; padding: 20px; border-radius: 12px; max-width: 700px;'>";

try {
    $pdo->beginTransaction();

    // ============================================
    // 1. Insert Dummy Users (untuk mitra)
    // ============================================
    $password_hash = password_hash('mitra123', PASSWORD_DEFAULT);
    
    $dummy_users = [
        ['mitra_rina@test.com', $password_hash, 'mitra'],
        ['mitra_budi@test.com', $password_hash, 'mitra'],
        ['mitra_sari@test.com', $password_hash, 'mitra'],
    ];

    $user_ids = [];
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
    
    foreach ($dummy_users as $u) {
        $stmt->execute($u);
        $user_ids[] = $pdo->lastInsertId();
        echo "✅ User created: {$u[0]}\n";
    }

    // ============================================
    // 2. Insert Dummy Partners (dengan nomor WA)
    // ============================================
    $dummy_partners = [
        [
            'user_id' => $user_ids[0],
            'full_name' => 'Rina Wulandari',
            'whatsapp_number' => '081234567890',
            'birth_date' => '1995-03-15',
            'address' => 'Jl. Melati No. 12, Bandung',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_holder' => 'Rina Wulandari',
            'affiliate_code' => 'AFF-RINA001',
        ],
        [
            'user_id' => $user_ids[1],
            'full_name' => 'Budi Santoso',
            'whatsapp_number' => '082198765432',
            'birth_date' => '1990-07-22',
            'address' => 'Jl. Kenanga No. 5, Jakarta',
            'bank_name' => 'BRI',
            'account_number' => '0987654321',
            'account_holder' => 'Budi Santoso',
            'affiliate_code' => 'AFF-BUDI002',
        ],
        [
            'user_id' => $user_ids[2],
            'full_name' => 'Sari Dewi',
            'whatsapp_number' => '085312348765',
            'birth_date' => '1998-11-08',
            'address' => 'Jl. Anggrek No. 8, Surabaya',
            'bank_name' => 'GoPay',
            'account_number' => '085312348765',
            'account_holder' => 'Sari Dewi',
            'affiliate_code' => 'AFF-SARI003',
        ],
    ];

    $partner_ids = [];
    $stmt = $pdo->prepare("
        INSERT INTO partners (user_id, full_name, whatsapp_number, birth_date, address, bank_name, account_number, account_holder, affiliate_code, status, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', 1)
        ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
    ");

    foreach ($dummy_partners as $p) {
        $stmt->execute([
            $p['user_id'], $p['full_name'], $p['whatsapp_number'], $p['birth_date'],
            $p['address'], $p['bank_name'], $p['account_number'], $p['account_holder'], $p['affiliate_code']
        ]);
        $partner_ids[] = $pdo->lastInsertId();
        echo "✅ Partner created: {$p['full_name']} (WA: {$p['whatsapp_number']})\n";
    }

    // ============================================
    // 3. Insert Commission Transactions (saldo awal)
    // ============================================
    $commissions = [
        [$partner_ids[0], 'commission', 150000, 'Komisi order INV-001', 'success'],
        [$partner_ids[1], 'commission', 200000, 'Komisi order INV-002', 'success'],
        [$partner_ids[2], 'commission', 175000, 'Komisi order INV-003', 'success'],
    ];

    $stmt = $pdo->prepare("INSERT INTO transactions (partner_id, type, amount, description, status) VALUES (?, ?, ?, ?, ?)");
    foreach ($commissions as $c) {
        $stmt->execute($c);
    }
    echo "\n💰 Komisi awal di-insert untuk 3 mitra\n";

    // ============================================
    // 4. Insert Withdrawal Transactions (untuk testing)
    // ============================================
    $withdrawals = [
        // 3 PENDING - ini yang bisa di-test approve + WhatsApp
        [$partner_ids[0], 'withdraw', 75000, 'Penarikan komisi - Rina', 'pending'],
        [$partner_ids[1], 'withdraw', 120000, 'Penarikan komisi - Budi', 'pending'],
        [$partner_ids[2], 'withdraw', 85500, 'Penarikan komisi - Sari', 'pending'],
        
        // 1 SUCCESS - untuk testing history
        [$partner_ids[0], 'withdraw', 50000, 'Penarikan komisi sebelumnya - Rina', 'success'],
        
        // 1 REJECTED - untuk testing history
        [$partner_ids[1], 'withdraw', 30000, 'Penarikan ditolak - Budi', 'rejected'],
    ];

    $stmt = $pdo->prepare("INSERT INTO transactions (partner_id, type, amount, description, status) VALUES (?, ?, ?, ?, ?)");
    foreach ($withdrawals as $w) {
        $stmt->execute($w);
    }

    echo "\n📋 Withdrawal transactions di-insert:\n";
    echo "   🟡 3x PENDING  (siap di-approve untuk test WhatsApp)\n";
    echo "   🟢 1x SUCCESS  (history)\n";
    echo "   🔴 1x REJECTED (history)\n";

    $pdo->commit();

    echo "\n" . str_repeat("─", 50) . "\n";
    echo "🎉 SEMUA DUMMY DATA BERHASIL DI-INSERT!\n";
    echo str_repeat("─", 50) . "\n\n";
    echo "📌 Langkah Testing:\n";
    echo "1. Buka halaman Admin → Approval Penarikan\n";
    echo "2. Klik 'Approve' pada salah satu penarikan pending\n";
    echo "3. Konfirmasi approve (tanpa upload file)\n";
    echo "4. Setelah berhasil, klik 'Kirim via WhatsApp'\n";
    echo "5. WhatsApp terbuka dengan pesan template ke mitra\n\n";
    echo "📌 Data Mitra untuk Testing:\n";
    echo "┌───────────────────┬────────────────┬───────┬──────────────┐\n";
    echo "│ Nama              │ WA             │ Bank  │ No. Rek      │\n";
    echo "├───────────────────┼────────────────┼───────┼──────────────┤\n";
    echo "│ Rina Wulandari    │ 081234567890   │ BCA   │ 1234567890   │\n";
    echo "│ Budi Santoso      │ 082198765432   │ BRI   │ 0987654321   │\n";
    echo "│ Sari Dewi         │ 085312348765   │ GoPay │ 085312348765 │\n";
    echo "└───────────────────┴────────────────┴───────┴──────────────┘\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    
    // Jika duplicate key, suggest cleanup
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "\n⚠️  Data dummy mungkin sudah ada. Jalankan cleanup dulu:\n";
        echo "    DELETE FROM transactions WHERE description LIKE '%Penarikan komisi%' OR description LIKE '%Komisi order%';\n";
        echo "    DELETE FROM partners WHERE affiliate_code IN ('AFF-RINA001','AFF-BUDI002','AFF-SARI003');\n";
        echo "    DELETE FROM users WHERE email LIKE '%@test.com';\n";
    }
}

echo "</pre>";
?>
