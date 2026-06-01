<?php
require_once 'public_html/includes/config.php';
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM surveys GROUP BY status");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
