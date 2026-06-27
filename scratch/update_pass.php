<?php
require 'c:/SEWLOVELY V1/public_html/includes/config.php';
$stmt=$pdo->prepare('UPDATE users SET password_hash=? WHERE email=?');
$stmt->execute([password_hash('admin123', PASSWORD_DEFAULT), 'admin@sewlovely.com']);
echo "Password updated successfully.";
