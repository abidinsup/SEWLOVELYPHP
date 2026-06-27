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

$userId = $input['user_id'] ?? '';
$customerName = $input['nama'] ?? '';
$customerWhatsapp = $input['wa'] ?? '';
$address = $input['alamat'] ?? '';
$totalAmount = $input['total'] ?? 0;
$cart = $input['cart'] ?? [];

$cartJson = json_encode($cart);

if (empty($userId) || empty($customerName) || empty($customerWhatsapp)) {
    echo json_encode(['status' => 'error', 'message' => 'Data wajib belum lengkap!']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Get partner_id
    $stmtPartner = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmtPartner->execute([$userId]);
    $partner = $stmtPartner->fetch();
    if (!$partner) {
        echo json_encode(['status' => 'error', 'message' => 'Partner tidak ditemukan']);
        exit;
    }
    $partnerId = $partner['id'];

    // 2. Insert into surveys (Create a pseudo-survey for POS)
    $stmtSurvey = $pdo->prepare("
        INSERT INTO surveys (partner_id, customer_name, customer_phone, customer_address, survey_date, survey_time, calculator_type, cart_items, status)
        VALUES (?, ?, ?, ?, CURDATE(), '00:00', 'sprei', ?, 'waiting_payment')
    ");
    $stmtSurvey->execute([
        $partnerId, $customerName, $customerWhatsapp, $address, $cartJson
    ]);
    
    $surveyId = $pdo->lastInsertId();

    // 3. Insert into invoices
    $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($surveyId, 4, '0', STR_PAD_LEFT);
    $secureToken = bin2hex(random_bytes(16));

    $stmtInvoice = $pdo->prepare("
        INSERT INTO invoices (survey_id, invoice_number, secure_token, total_amount, payment_status, cart_json)
        VALUES (?, ?, ?, ?, 'unpaid', ?)
    ");
    $stmtInvoice->execute([
        $surveyId, $invoiceNumber, $secureToken, $totalAmount, $cartJson
    ]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Pesanan berhasil dibuat!',
        'invoice_id' => $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
