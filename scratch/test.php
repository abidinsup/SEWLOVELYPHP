<?php
require 'c:/SEWLOVELY V1/public_html/includes/config.php';
$stmt=$pdo->prepare('SELECT * FROM users WHERE email=?');
$stmt->execute(['admin@sewlovely.com']);
$user=$stmt->fetch();
var_dump($user);
echo password_verify('admin123', $user['password_hash']) ? 'OK' : 'FAIL';
