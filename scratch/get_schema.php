<?php
require_once 'public_html/includes/config.php';
try {
    $stmt = $pdo->query("DESCRIBE partners");
    $columns = $stmt->fetchAll();
    echo json_encode($columns, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
