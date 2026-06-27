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

$email = $input['email'] ?? $_POST['email'] ?? '';
$password = $input['password'] ?? $_POST['password'] ?? '';
$fullName = $input['full_name'] ?? $_POST['full_name'] ?? '';
$whatsapp = $input['whatsapp_number'] ?? $_POST['whatsapp_number'] ?? '';
$birthDate = $input['birth_date'] ?? $_POST['birth_date'] ?? '';
$address = $input['address'] ?? $_POST['address'] ?? '';
$bankName = $input['bank_name'] ?? $_POST['bank_name'] ?? '';
$accountNumber = $input['account_number'] ?? $_POST['account_number'] ?? '';
$accountHolder = $input['account_holder'] ?? $_POST['account_holder'] ?? '';

if (empty($email) || empty($password) || empty($fullName) || empty($whatsapp)) {
    echo json_encode(['status' => 'error', 'message' => 'Data wajib (Email, Password, Nama, WA) belum lengkap!']);
    exit;
}

if (!empty($birthDate)) {
    if (strpos($birthDate, '/') !== false) {
        $parts = explode('/', $birthDate);
        if (count($parts) == 3) {
            $birthDate = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
        }
    }
} else {
    $birthDate = null;
}

try {
    $pdo->beginTransaction();

    // Check if email already exists
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar!']);
        $pdo->rollBack();
        exit;
    }

    // Insert user
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmtUser = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'mitra')");
    $stmtUser->execute([$email, $passwordHash]);
    $userId = $pdo->lastInsertId();

    // Create affiliate code
    $baseName = !empty($fullName) ? $fullName : 'USER';
    $affiliateCode = 'AFF-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $baseName), 0, 4)) . rand(100, 999);

    // Insert partner
    $stmtPartner = $pdo->prepare("
        INSERT INTO partners (user_id, full_name, whatsapp_number, birth_date, address, bank_name, account_number, account_holder, affiliate_code, status, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0)
    ");
    $stmtPartner->execute([
        $userId, $fullName, $whatsapp, $birthDate, $address, $bankName, $accountNumber, $accountHolder, $affiliateCode
    ]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Pendaftaran berhasil! Akun Anda sedang dalam peninjauan admin.'
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
