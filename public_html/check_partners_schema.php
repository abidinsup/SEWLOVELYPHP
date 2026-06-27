<?php
require_once 'includes/config.php';
$stmt = $pdo->query("DESCRIBE partners");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($columns);
echo "</pre>";

$stmt2 = $pdo->query("DESCRIBE users");
$columns2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>USERS:\n";
print_r($columns2);
echo "</pre>";
?>
