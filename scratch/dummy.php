<?php
require 'public_html/includes/config.php';
$stmt = $pdo->query("SELECT id FROM partners LIMIT 1");
$partner = $stmt->fetch();
$pid = $partner ? $partner['id'] : 1;
$pdo->exec("INSERT INTO surveys (customer_name, customer_phone, calculator_type, partner_id, status, created_at) VALUES ('Testing POS', '08129999999', 'rumah', $pid, 'survey', NOW())");
echo "Done";
