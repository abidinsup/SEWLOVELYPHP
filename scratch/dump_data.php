<?php
$pdo = new PDO('mysql:host=localhost;dbname=sewlovely;charset=utf8mb4', 'root', '');
$fabrics = $pdo->query('SELECT * FROM curtain_fabrics')->fetchAll(PDO::FETCH_ASSOC);
$rails = $pdo->query('SELECT * FROM curtain_rails')->fetchAll(PDO::FETCH_ASSOC);

$sql = "<?php\nrequire_once 'includes/config.php';\necho '<h2>Mengisi Data Master...</h2>';\ntry {\n";

if (!empty($fabrics)) {
    $sql .= "\$pdo->exec('TRUNCATE TABLE `curtain_fabrics`');\n";
    foreach ($fabrics as $f) {
        $name = addslashes($f['name']);
        $type = addslashes($f['type']);
        $price = $f['price_per_meter'];
        $active = $f['is_active'];
        $sql .= "\$pdo->exec(\"INSERT INTO `curtain_fabrics` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES ({$f['id']}, '{$name}', '{$type}', {$price}, {$active})\");\n";
    }
}

if (!empty($rails)) {
    $sql .= "\$pdo->exec('TRUNCATE TABLE `curtain_rails`');\n";
    foreach ($rails as $r) {
        $name = addslashes($r['name']);
        $type = addslashes($r['type']);
        $price = $r['price_per_meter'];
        $active = $r['is_active'];
        $sql .= "\$pdo->exec(\"INSERT INTO `curtain_rails` (`id`, `name`, `type`, `price_per_meter`, `is_active`) VALUES ({$r['id']}, '{$name}', '{$type}', {$price}, {$active})\");\n";
    }
}

$sql .= "echo '✅ Berhasil memasukkan data Gorden, Vitrase, dan Rel.<br>';\n";
$sql .= "} catch(Exception \$e) { echo '❌ Error: ' . \$e->getMessage(); }\n";

file_put_contents('public_html/import_data_live.php', $sql);
echo "import_data_live.php generated!\n";
