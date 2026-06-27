<?php
require_once 'c:/SEWLOVELY V1/public_html/includes/config.php';

// migrate_cart_json
$stmt = $pdo->query("SHOW COLUMNS FROM surveys LIKE 'cart_items'");
if ($stmt->rowCount() === 0) {
    $pdo->exec("ALTER TABLE surveys ADD COLUMN cart_items JSON DEFAULT NULL AFTER calculator_type");
    echo "Added cart_items to surveys.\n";
}

// migrate_discount
$stmt = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'discount_amount'");
if ($stmt->rowCount() === 0) {
    $pdo->exec("ALTER TABLE invoices ADD COLUMN discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER admin_fee");
    echo "Added discount_amount to invoices.\n";
}

// migrate_paid_amount
$stmt = $pdo->query("SHOW COLUMNS FROM invoices LIKE 'paid_amount'");
if ($stmt->rowCount() === 0) {
    $pdo->exec("ALTER TABLE invoices ADD COLUMN paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
    echo "Added paid_amount to invoices.\n";
}

echo "All migrations completed.\n";
