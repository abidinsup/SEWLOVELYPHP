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

$userId = null;

if (is_array($input) && isset($input['user_id'])) {
    $userId = $input['user_id'];
} else {
    $userId = $_POST['user_id'] ?? null;
}

if (empty($userId)) {
    echo json_encode(['status' => 'error', 'message' => 'User ID wajib disertakan!']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.email, u.role, u.created_at as joined_at,
            p.full_name, p.whatsapp_number, p.birth_date, p.address, 
            p.bank_name, p.account_number, p.account_holder, 
            p.affiliate_code, p.commission_percentage, p.is_active, p.status
        FROM users u
        LEFT JOIN partners p ON u.id = p.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($profile) {
        echo json_encode([
            'status' => 'success',
            'data' => $profile
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Profil tidak ditemukan.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
