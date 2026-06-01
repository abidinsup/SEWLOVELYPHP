<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=sewlovely_db;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add discount_amount column if not exists
    $cols = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'discount_amount'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN discount_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
        echo "Column 'discount_amount' added successfully.\n";
    } else {
        echo "Column 'discount_amount' already exists.\n";
    }

    // Add invoice_notes column if not exists
    $cols2 = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'invoice_notes'")->fetchAll();
    if (empty($cols2)) {
        $pdo->exec("ALTER TABLE invoices ADD COLUMN invoice_notes TEXT DEFAULT NULL AFTER payment_status");
        echo "Column 'invoice_notes' added successfully.\n";
    } else {
        echo "Column 'invoice_notes' already exists.\n";
    }

    echo "Migration complete!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
