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
$rewardName = $input['reward_name'] ?? $_POST['reward_name'] ?? null;
$pointsRequired = $input['points_required'] ?? $_POST['points_required'] ?? null;

if (empty($userId) || empty($rewardName) || empty($pointsRequired)) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap. user_id, reward_name, dan points_required wajib diisi.']);
    exit;
}

$pointsRequired = (int)$pointsRequired;

if ($pointsRequired <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Poin yang dibutuhkan tidak valid.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Dapatkan Partner ID dan Point
    $stmt = $pdo->prepare("SELECT id, reward_points FROM partners WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partner) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Mitra tidak ditemukan.']);
        exit;
    }

    $partnerId = $partner['id'];
    $currentPoints = (int)$partner['reward_points'];

    // 2. Cek ketersediaan poin
    if ($currentPoints < $pointsRequired) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Poin Anda tidak mencukupi untuk klaim hadiah ini.']);
        exit;
    }

    // 3. Kurangi Poin
    $stmtUpdate = $pdo->prepare("UPDATE partners SET reward_points = reward_points - ? WHERE id = ?");
    $stmtUpdate->execute([$pointsRequired, $partnerId]);

    // 4. Masukkan ke tabel reward_claims
    $stmtClaim = $pdo->prepare("INSERT INTO reward_claims (partner_id, reward_name, points_used, status) VALUES (?, ?, ?, 'pending')");
    $stmtClaim->execute([$partnerId, $rewardName, $pointsRequired]);

    // 5. Catat ke history poin
    $desc = "Klaim hadiah " . $rewardName;
    $stmtHist = $pdo->prepare("INSERT INTO reward_points_history (partner_id, points, type, description) VALUES (?, ?, 'redeem', ?)");
    $stmtHist->execute([$partnerId, -$pointsRequired, $desc]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Klaim hadiah berhasil diajukan. Silakan tunggu konfirmasi dari Admin.'
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
