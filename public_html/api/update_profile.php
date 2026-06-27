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

$fullName = $input['full_name'] ?? $_POST['full_name'] ?? null;
$birthDate = $input['birth_date'] ?? $_POST['birth_date'] ?? null;
$whatsapp = $input['whatsapp_number'] ?? $_POST['whatsapp_number'] ?? null;
$address = $input['address'] ?? $_POST['address'] ?? null;
$bankName = $input['bank_name'] ?? $_POST['bank_name'] ?? null;
$accountNumber = $input['account_number'] ?? $_POST['account_number'] ?? null;
$accountHolder = $input['account_holder'] ?? $_POST['account_holder'] ?? null;

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
    $stmtCheck = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmtCheck->execute([$userId]);
    $exists = $stmtCheck->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("
            UPDATE partners SET 
                full_name = ?, 
                whatsapp_number = ?, 
                birth_date = ?, 
                address = ?, 
                bank_name = ?, 
                account_number = ?, 
                account_holder = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $fullName, $whatsapp, $birthDate, $address, $bankName, $accountNumber, $accountHolder, $userId
        ]);

        // Jika dia sudah ada tapi affiliate_code nya kosong (karena error sebelumnya), kita buatkan
        $checkAff = $pdo->prepare("SELECT affiliate_code FROM partners WHERE user_id = ?");
        $checkAff->execute([$userId]);
        $affData = $checkAff->fetch();
        if (empty($affData['affiliate_code'])) {
            $baseName = !empty($fullName) ? $fullName : 'USER';
            $newCode = 'AFF-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $baseName), 0, 4)) . rand(100, 999);
            $pdo->prepare("UPDATE partners SET affiliate_code = ? WHERE user_id = ?")->execute([$newCode, $userId]);
        }

    } else {
        $baseName = !empty($fullName) ? $fullName : 'USER';
        $affiliateCode = 'AFF-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $baseName), 0, 4)) . rand(100, 999);
        
        $stmt = $pdo->prepare("
            INSERT INTO partners (user_id, full_name, whatsapp_number, birth_date, address, bank_name, account_number, account_holder, affiliate_code, status, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', 1)
        ");
        $stmt->execute([
            $userId, $fullName, $whatsapp, $birthDate, $address, $bankName, $accountNumber, $accountHolder, $affiliateCode
        ]);
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Profil berhasil diperbarui!'
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
