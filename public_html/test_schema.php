<?php
require 'c:/SEWLOVELY V1/public_html/includes/config.php';
$stmt = $pdo->query('SHOW COLUMNS FROM fcm_tokens');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
