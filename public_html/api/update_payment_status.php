<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Gunakan metode POST.']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$invoiceId = $input['invoice_id'] ?? null;
$status = $input['status'] ?? 'menunggu_konfirmasi_admin';

if (empty($invoiceId)) {
    echo json_encode(['status' => 'error', 'message' => 'Invoice ID wajib disertakan!']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE surveys SET status = 'confirmed' WHERE id = (SELECT survey_id FROM invoices WHERE id = ?)");
    $stmt->execute([$invoiceId]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Status pembayaran berhasil diupdate'
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
