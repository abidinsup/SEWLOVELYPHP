<?php
require_once '../includes/session.php';
require_once '../includes/config.php';

// Only admin can manage products
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $category = $_POST['category'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $base_price = str_replace(['Rp', '.', ' '], '', $_POST['base_price'] ?? '0');
            $base_price = (float)$base_price;
            $unit = $_POST['unit'] ?? 'Pcs';

            if (empty($category) || empty($name) || $base_price <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mohon lengkapi data dengan benar.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO products (category, name, base_price, unit) VALUES (?, ?, ?, ?)");
            $stmt->execute([$category, $name, $base_price, $unit]);
            echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan.']);

        } elseif ($action === 'edit') {
            $id = $_POST['id'] ?? 0;
            $category = $_POST['category'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $base_price = str_replace(['Rp', '.', ' '], '', $_POST['base_price'] ?? '0');
            $base_price = (float)$base_price;
            $unit = $_POST['unit'] ?? 'Pcs';

            if (empty($id) || empty($category) || empty($name) || $base_price <= 0) {
                echo json_encode(['success' => false, 'message' => 'Mohon lengkapi data dengan benar.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE products SET category = ?, name = ?, base_price = ?, unit = ? WHERE id = ?");
            $stmt->execute([$category, $name, $base_price, $unit, $id]);
            echo json_encode(['success' => true, 'message' => 'Produk berhasil diupdate.']);

        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? 0;

            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID produk tidak valid.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Produk berhasil dihapus.']);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
