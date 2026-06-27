<?php
require_once 'includes/config.php';

echo "<pre>";

// 1. Cek Schema Partners
try {
    $stmt = $pdo->query("DESCRIBE partners");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "=== SCHEMA PARTNERS ===\n";
    foreach($cols as $c) echo $c['Field'] . " | ";
    echo "\n\n";
} catch(Exception $e) {
    echo "Error DESC partners: " . $e->getMessage() . "\n";
}

// 2. Cek Schema Users
try {
    $stmt = $pdo->query("DESCRIBE users");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "=== SCHEMA USERS ===\n";
    foreach($cols as $c) echo $c['Field'] . " | ";
    echo "\n\n";
} catch(Exception $e) {
    echo "Error DESC users: " . $e->getMessage() . "\n";
}

// 3. Tes Insert
try {
    $pdo->beginTransaction();
    $email = 'test_debug_' . rand() . '@test.com';
    
    echo "Mencoba insert ke users...\n";
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'mitra')");
    $stmt->execute([$email, '123']);
    $user_id = $pdo->lastInsertId();
    echo "Insert users sukses! ID: $user_id\n\n";

    echo "Mencoba insert ke partners...\n";
    $stmt = $pdo->prepare("INSERT INTO partners (user_id, full_name, whatsapp_number, birth_date, address, bank_name, account_number, account_holder, affiliate_code, is_active, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'pending')");
    $stmt->execute([$user_id, 'Test Debug', '08123456789', null, 'Alamat', null, null, null, 'AFF-TEST123']);
    echo "Insert partners sukses!\n";
    
    $pdo->rollBack(); // Jangan simpan data test
    echo "\nSemua test berhasil. Data di-rollback.";
} catch(Exception $e) {
    $pdo->rollBack();
    echo "GAGAL SAAT INSERT: " . $e->getMessage();
}
echo "</pre>";
?>
