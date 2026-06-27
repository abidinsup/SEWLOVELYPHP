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

$userId = $input['user_id'] ?? null;
$amount = $input['amount'] ?? 0;

if (empty($userId) || $amount < 50000) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap atau nominal di bawah Rp 50.000']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Cari ID Mitra
    $stmt = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmt->execute([$userId]);
    $partner = $stmt->fetch();

    if (!$partner) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Mitra tidak ditemukan']);
        exit;
    }

    $partnerId = $partner['id'];

    // Hitung total pendapatan (commission yang success)
    $stmtIncome = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE partner_id = ? AND type = 'commission' AND status = 'success' FOR UPDATE");
    $stmtIncome->execute([$partnerId]);
    $incomeData = $stmtIncome->fetch();
    $totalIncome = $incomeData['total'] ?? 0;

    // Hitung total ditarik (withdraw yang pending atau success)
    $stmtWithdraw = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE partner_id = ? AND type = 'withdraw' AND status IN ('success', 'pending') FOR UPDATE");
    $stmtWithdraw->execute([$partnerId]);
    $withdrawData = $stmtWithdraw->fetch();
    $totalWithdrawn = $withdrawData['total'] ?? 0;

    $balance = $totalIncome - $totalWithdrawn;

    if ($amount > $balance) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Saldo tidak mencukupi']);
        exit;
    }

    // Insert withdrawal request
    $stmtInsert = $pdo->prepare("
        INSERT INTO transactions (partner_id, type, amount, description, status) 
        VALUES (?, 'withdraw', ?, 'Penarikan Saldo', 'pending')
    ");
    $stmtInsert->execute([$partnerId, $amount]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Permintaan penarikan berhasil dikirim'
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
