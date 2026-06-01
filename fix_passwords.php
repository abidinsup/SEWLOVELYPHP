<?php
require_once 'public_html/includes/config.php';

$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$mitra_pass = password_hash('mitra123', PASSWORD_DEFAULT);

try {
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@sewlovely.com'")->execute([$admin_pass]);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'mitra@sewlovely.com'")->execute([$mitra_pass]);
    echo "Passwords updated successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
