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

$surveyId = $input['survey_id'] ?? null;
$userId = $input['user_id'] ?? null;

if (empty($surveyId) || empty($userId)) {
    echo json_encode(['status' => 'error', 'message' => 'Survey ID dan User ID wajib disertakan!']);
    exit;
}

try {
    // Verifikasi kepemilikan survey (opsional tapi bagus untuk keamanan)
    $stmtPartner = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmtPartner->execute([$userId]);
    $partner = $stmtPartner->fetch();

    if (!$partner) {
        echo json_encode(['status' => 'error', 'message' => 'Mitra tidak ditemukan.']);
        exit;
    }
    
    // Periksa status survey
    $stmtCheck = $pdo->prepare("SELECT status FROM surveys WHERE id = ? AND partner_id = ?");
    $stmtCheck->execute([$surveyId, $partner['id']]);
    $survey = $stmtCheck->fetch();

    if (!$survey) {
        echo json_encode(['status' => 'error', 'message' => 'Survey tidak ditemukan atau bukan milik Anda.']);
        exit;
    }

    if ($survey['status'] !== 'pending' && $survey['status'] !== 'survey') {
        echo json_encode(['status' => 'error', 'message' => 'Hanya survey dengan status Menunggu atau Proses Survey yang dapat dibatalkan.']);
        exit;
    }

    // Lakukan pembatalan
    $stmt = $pdo->prepare("UPDATE surveys SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$surveyId]);

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Survey berhasil dibatalkan.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal membatalkan survey.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
}
?>
