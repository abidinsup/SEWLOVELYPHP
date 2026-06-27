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

$userId = $input['user_id'] ?? $_POST['user_id'] ?? null;
if (empty($userId)) {
    echo json_encode(['status' => 'error', 'message' => 'User ID wajib disertakan!']);
    exit;
}

try {
    // Cari ID Mitra
    $stmt = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmt->execute([$userId]);
    $partner = $stmt->fetch();

    if (!$partner) {
        // Jika belum ada data partner, kembalikan 0
        echo json_encode([
            'status' => 'success',
            'data' => [
                'total_income' => 0,
                'total_withdrawn' => 0,
                'balance' => 0,
                'transactions' => []
            ]
        ]);
        exit;
    }

    $partnerId = $partner['id'];

    // Hitung total pendapatan (commission yang success)
    $stmtIncome = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE partner_id = ? AND type = 'commission' AND status = 'success'");
    $stmtIncome->execute([$partnerId]);
    $incomeData = $stmtIncome->fetch();
    $totalIncome = $incomeData['total'] ?? 0;

    // Hitung total ditarik (withdraw yang pending atau success)
    $stmtWithdraw = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE partner_id = ? AND type = 'withdraw' AND status IN ('success', 'pending')");
    $stmtWithdraw->execute([$partnerId]);
    $withdrawData = $stmtWithdraw->fetch();
    $totalWithdrawn = $withdrawData['total'] ?? 0;

    $balance = $totalIncome - $totalWithdrawn;

    // Riwayat transaksi terakhir
    $stmtHistory = $pdo->prepare("
        SELECT id, type, amount, description, status, created_at 
        FROM transactions 
        WHERE partner_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmtHistory->execute([$partnerId]);
    $transactions = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_income' => (float)$totalIncome,
            'total_withdrawn' => (float)$totalWithdrawn,
            'balance' => (float)$balance,
            'transactions' => $transactions
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
