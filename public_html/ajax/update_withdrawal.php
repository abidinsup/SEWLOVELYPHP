<?php
require_once '../includes/session.php';
require_once '../includes/config.php';

// Only admin can manage withdrawals
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = $_POST['transaction_id'] ?? 0;
    $action = $_POST['action'] ?? ''; // 'approve' or 'reject'

    if (!in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
        exit;
    }

    try {
        $new_status = ($action === 'approve') ? 'success' : 'rejected';
        $proof_url = null;

        // Handle file upload if present
        if ($action === 'approve' && isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/proofs/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_extension = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
            $file_name = 'proof_' . $transaction_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['proof']['tmp_name'], $target_file)) {
                $proof_url = 'uploads/proofs/' . $file_name;
            }
        }

        if ($proof_url) {
            $stmt = $pdo->prepare("UPDATE transactions SET status = ?, proof_url = ? WHERE id = ? AND type = 'withdraw' AND status = 'pending'");
            $result = $stmt->execute([$new_status, $proof_url, $transaction_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ? AND type = 'withdraw' AND status = 'pending'");
            $result = $stmt->execute([$new_status, $transaction_id]);
        }

        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => ($action === 'approve') ? 'Penarikan berhasil disetujui' : 'Penarikan berhasil ditolak']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan atau sudah diproses']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
