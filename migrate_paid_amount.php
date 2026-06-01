<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sewlovely_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add paid_amount column if not exists
    $cols = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'paid_amount'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN paid_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER discount_amount");
        echo "Column 'paid_amount' added successfully.\n";
    } else {
        echo "Column 'paid_amount' already exists.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
