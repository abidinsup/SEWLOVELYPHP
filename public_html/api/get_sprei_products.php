<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Saat ini data produk sprei masih statis seperti pada checkout_sprei.php.
// Di masa depan, jika ada tabel database untuk produk, bisa diubah query dari database di sini.
$products = [
    ['id' => '1', 'name' => 'Sprei King (180x200)', 'price' => 250000, 'stock' => 10],
    ['id' => '2', 'name' => 'Sprei Queen (160x200)', 'price' => 230000, 'stock' => 15],
    ['id' => '3', 'name' => 'Sprei Single (120x200)', 'price' => 180000, 'stock' => 8],
    ['id' => '4', 'name' => 'Sprei Super Single (100x200)', 'price' => 160000, 'stock' => 5],
    ['id' => '5', 'name' => 'Sprei Extra King (200x200)', 'price' => 280000, 'stock' => 3],
    ['id' => '6', 'name' => 'Bedcover Set King', 'price' => 650000, 'stock' => 4],
    ['id' => '7', 'name' => 'Sprei Katun Jepang 180', 'price' => 450000, 'stock' => 2],
    ['id' => '8', 'name' => 'Sprei Microfiber 160', 'price' => 195000, 'stock' => 20],
];

echo json_encode(['success' => true, 'data' => $products]);
?>
