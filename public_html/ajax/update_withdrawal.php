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

        // Update transaction status (without proof)
        $stmt = $pdo->prepare("UPDATE transactions SET status = ? WHERE id = ? AND type = 'withdraw' AND status = 'pending'");
        $result = $stmt->execute([$new_status, $transaction_id]);

        if ($result && $stmt->rowCount() > 0) {
            $response = [
                'success' => true,
                'message' => ($action === 'approve') ? 'Penarikan berhasil disetujui' : 'Penarikan berhasil ditolak'
            ];

            // If approved, fetch mitra data for WhatsApp confirmation
            if ($action === 'approve') {
                $stmt2 = $pdo->prepare("
                    SELECT p.whatsapp_number, p.full_name, p.bank_name, p.account_number, t.amount
                    FROM transactions t
                    JOIN partners p ON t.partner_id = p.id
                    WHERE t.id = ?
                ");
                $stmt2->execute([$transaction_id]);
                $mitra = $stmt2->fetch();

                if ($mitra) {
                    $response['whatsapp_number'] = $mitra['whatsapp_number'];
                    $response['mitra_name'] = $mitra['full_name'];
                    $response['bank_name'] = $mitra['bank_name'];
                    $response['account_number'] = $mitra['account_number'];
                    $response['amount'] = (float)$mitra['amount'];
                }
            }

            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan atau sudah diproses']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
