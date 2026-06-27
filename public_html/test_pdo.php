<?php
require_once 'includes/config.php';
try {
    $stmt1 = $pdo->query("SELECT id, name, price_per_meter FROM curtain_fabrics WHERE type = 'gorden' AND is_active = 1 ORDER BY name");
    $res = $stmt1->fetchAll();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM surveys WHERE status = 'pending'");
    echo 'COUNT: ' . $stmt->fetchColumn() . "\n";
} catch(Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
