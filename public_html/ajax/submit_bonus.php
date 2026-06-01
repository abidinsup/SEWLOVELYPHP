<?php
require_once '../includes/session.php';
require_once '../includes/config.php';

// Only admin can give manual bonuses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partner_id = $_POST['partner_id'] ?? 0;
    $amount = str_replace(['Rp', '.', ' '], '', $_POST['amount'] ?? '0');
    $amount = (float)$amount;
    $description = $_POST['description'] ?? '';

    if (empty($partner_id) || $amount <= 0 || empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Mohon lengkapi semua data dengan benar.']);
        exit;
    }

    try {
        // Prepare description with prefix to easily identify it later
        $full_description = 'Bonus Manual: ' . trim($description);

        $stmt = $pdo->prepare("INSERT INTO transactions (partner_id, type, amount, description, status) VALUES (?, 'commission', ?, ?, 'success')");
        $result = $stmt->execute([$partner_id, $amount, $full_description]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Bonus berhasil diberikan dan ditambahkan ke saldo mitra.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memberikan bonus.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
