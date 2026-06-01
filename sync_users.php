<?php
require_once 'public_html/includes/config.php';

function ensureUser($pdo, $email, $password, $role, $name) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    if ($user) {
        $pdo->prepare("UPDATE users SET password_hash = ?, role = ? WHERE id = ?")
            ->execute([$hash, $role, $user['id']]);
        $user_id = $user['id'];
        echo "Updated user: $email\n";
    } else {
        $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)")
            ->execute([$email, $hash, $role]);
        $user_id = $pdo->lastInsertId();
        echo "Created user: $email\n";
    }
    
    if ($role === 'mitra') {
        $stmt = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO partners (user_id, full_name, whatsapp_number, affiliate_code) VALUES (?, ?, ?, ?)")
                ->execute([$user_id, $name, '08123456789', 'MITRA-DEMO']);
            echo "Created partner entry for: $name\n";
        }
    }
}

try {
    ensureUser($pdo, 'admin@sewlovely.com', 'admin123', 'admin', 'Administrator');
    ensureUser($pdo, 'mitra@sewlovely.com', 'mitra123', 'mitra', 'Mitra Demo');
    echo "Database sync complete!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
