<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = isset($_POST['invoice_id']) ? intval($_POST['invoice_id']) : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    $paid_amount = isset($_POST['paid_amount']) ? floatval($_POST['paid_amount']) : null;

    if ($invoice_id <= 0 || !in_array($status, ['unpaid', 'partial', 'paid'])) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        exit;
    }

    try {
        if ($paid_amount !== null) {
            $stmt = $pdo->prepare("UPDATE invoices SET payment_status = ?, paid_amount = ? WHERE id = ?");
            $stmt->execute([$status, $paid_amount, $invoice_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE invoices SET payment_status = ? WHERE id = ?");
            $stmt->execute([$status, $invoice_id]);
        }
        
        // --- LOGIKA KOMISI 5% UNTUK POS SPREI ---
        if ($status === 'paid') {
            // Ambil data pesanan
            $stmtData = $pdo->prepare("
                SELECT i.invoice_number, i.total_amount, s.partner_id, s.calculator_type
                FROM invoices i
                JOIN surveys s ON i.survey_id = s.id
                WHERE i.id = ?
            ");
            $stmtData->execute([$invoice_id]);
            $orderData = $stmtData->fetch(PDO::FETCH_ASSOC);

            if ($orderData && $orderData['calculator_type'] === 'sprei') {
                $partner_id = $orderData['partner_id'];
                $total_amount = $orderData['total_amount'];
                $invoice_number = $orderData['invoice_number'];
                $commission_desc = "Komisi POS Sprei (5%) - " . $invoice_number;

                // Cek apakah komisi untuk invoice ini sudah pernah diberikan
                $stmtCheck = $pdo->prepare("SELECT id FROM transactions WHERE partner_id = ? AND type = 'commission' AND description = ?");
                $stmtCheck->execute([$partner_id, $commission_desc]);
                
                if (!$stmtCheck->fetch()) {
                    // Hitung komisi 5%
                    $commission_amount = $total_amount * 0.05;

                    // Masukkan ke transaksi
                    $stmtComm = $pdo->prepare("
                        INSERT INTO transactions (partner_id, type, amount, description, status) 
                        VALUES (?, 'commission', ?, ?, 'success')
                    ");
                    $stmtComm->execute([$partner_id, $commission_amount, $commission_desc]);
                }
            }
        }
        // --- END LOGIKA KOMISI ---

        echo json_encode(['success' => true, 'message' => 'Status pembayaran berhasil diupdate']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan database: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
