<?php
require_once 'public_html/includes/config.php';
try {
    $sql = "CREATE TABLE IF NOT EXISTS `products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `category` varchar(100) NOT NULL,
        `name` varchar(255) NOT NULL,
        `base_price` decimal(15,2) NOT NULL DEFAULT 0.00,
        `unit` varchar(50) DEFAULT 'Pcs',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $pdo->exec($sql);
    echo "Table 'products' created successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
