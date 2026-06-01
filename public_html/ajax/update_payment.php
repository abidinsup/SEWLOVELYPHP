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
        
        echo json_encode(['success' => true, 'message' => 'Status pembayaran berhasil diupdate']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan database: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
