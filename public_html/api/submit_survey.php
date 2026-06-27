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
$customerName = $input['customer_name'] ?? $_POST['customer_name'] ?? '';
$customerWhatsapp = $input['customer_whatsapp'] ?? $_POST['customer_whatsapp'] ?? '';
$address = $input['address'] ?? $_POST['address'] ?? '';
$surveyDate = $input['survey_date'] ?? $_POST['survey_date'] ?? null;
$surveyTime = $input['survey_time'] ?? $_POST['survey_time'] ?? null;
$notes = $input['notes'] ?? $_POST['notes'] ?? '';

if (empty($userId) || empty($customerName) || empty($customerWhatsapp) || empty($address)) {
    echo json_encode(['status' => 'error', 'message' => 'Data wajib belum lengkap!']);
    exit;
}

try {
    // Get partner_id from user_id
    $stmtPartner = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmtPartner->execute([$userId]);
    $partner = $stmtPartner->fetch();
    
    if (!$partner) {
        echo json_encode(['status' => 'error', 'message' => 'Data mitra tidak ditemukan!']);
        exit;
    }
    $partnerId = $partner['id'];

    $surveyDate = !empty($surveyDate) ? $surveyDate : date('Y-m-d');
    $surveyTime = !empty($surveyTime) ? $surveyTime : '00:00';
    $calcType = 'gorden';

    $stmt = $pdo->prepare("
        INSERT INTO surveys (partner_id, customer_name, customer_phone, customer_address, survey_date, survey_time, calculator_type, notes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $partnerId, $customerName, $customerWhatsapp, $address, $surveyDate, $surveyTime, $calcType, $notes
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Pengajuan survey berhasil dikirim!'
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
