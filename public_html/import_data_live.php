<?php
require_once 'includes/config.php';
echo '<h2>Mengisi Data Master...</h2>';
try {
$pdo->exec('TRUNCATE TABLE `curtain_fabrics`');
$pdo->exec("INSERT INTO `curtain_fabrics` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (1, 'Blackout Premium Polos', 'gorden', 85000.00, 1)");
$pdo->exec("INSERT INTO `curtain_fabrics` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (2, 'Blackout Motif Emboss', 'gorden', 95000.00, 1)");
$pdo->exec("INSERT INTO `curtain_fabrics` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (3, 'Gorden Polos Grade A', 'gorden', 65000.00, 1)");
$pdo->exec("INSERT INTO `curtain_fabrics` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (4, 'Gorden Motif Bunga', 'gorden', 75000.00, 1)");
$pdo->exec("INSERT INTO `curtain_fabrics` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (5, 'Gorden Dimout', 'gorden', 70000.00, 1)");
$pdo->exec("INSERT INTO `curtain_fabrics` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (6, 'Vitrase Polos Putih', 'vitrase', 35000.00, 1)");
$pdo->exec("INSERT INTO `curtain_fabrics` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (7, 'Vitrase Motif Bordir', 'vitrase', 50000.00, 1)");
$pdo->exec("INSERT INTO `curtain_fabrics` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (8, 'Vitrase Organza', 'vitrase', 45000.00, 1)");
$pdo->exec('TRUNCATE TABLE `curtain_rails`');
$pdo->exec("INSERT INTO `curtain_rails` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (1, 'Rel Aluminium Single', 'single', 45000.00, 1)");
$pdo->exec("INSERT INTO `curtain_rails` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (2, 'Rel Stainless Single', 'single', 65000.00, 1)");
$pdo->exec("INSERT INTO `curtain_rails` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (3, 'Rel Aluminium Double (Twin)', 'double', 75000.00, 1)");
$pdo->exec("INSERT INTO `curtain_rails` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES (4, 'Rel Stainless Double (Twin)', 'double', 95000.00, 1)");
echo '✅ Berhasil memasukkan data Gorden, Vitrase, dan Rel.<br>';
} catch(Exception $e) { echo '❌ Error: ' . $e->getMessage(); }
