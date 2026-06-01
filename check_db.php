<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sewlovely_db;charset=utf8mb4', 'root', '');
    $stmt = $pdo->query('SELECT survey_id, cart_json FROM invoices');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (Exception $e) {
    echo $e->getMessage();
}
