<?php
require 'includes/config.php';

try {
    // Check if points column exists in partners table
    $stmt = $pdo->query("SHOW COLUMNS FROM `partners` LIKE 'points'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `partners` ADD COLUMN `points` INT NOT NULL DEFAULT 0 AFTER `status`");
        echo "Added 'points' column to 'partners' table.\n";
    } else {
        echo "'points' column already exists in 'partners' table.\n";
    }

    // Create reward_claims table
    $createTableSql = "
    CREATE TABLE IF NOT EXISTS `reward_claims` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `partner_id` INT NOT NULL,
        `reward_name` VARCHAR(100) NOT NULL,
        `points_used` INT NOT NULL,
        `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`partner_id`) REFERENCES `partners`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $pdo->exec($createTableSql);
    echo "Table 'reward_claims' created or already exists.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
