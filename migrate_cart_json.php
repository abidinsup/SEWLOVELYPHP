<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sewlovely_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add cart_json column if not exists
    $cols = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'cart_json'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN cart_json LONGTEXT DEFAULT NULL AFTER invoice_notes");
        echo "Column 'cart_json' added successfully.\n";
    } else {
        echo "Column 'cart_json' already exists.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
