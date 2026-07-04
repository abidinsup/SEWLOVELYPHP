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
$token = $input['token'] ?? null;
$deviceInfo = $input['device_info'] ?? null;

if (empty($token)) {
    echo json_encode(['status' => 'error', 'message' => 'Token wajib diisi']);
    exit;
}

try {
    // Auto migration to allow NULL user_id and drop foreign key if exists
    try {
        $pdo->exec("ALTER TABLE fcm_tokens DROP FOREIGN KEY fcm_tokens_ibfk_1");
    } catch (Exception $e) {}
    try {
        $pdo->exec("ALTER TABLE fcm_tokens MODIFY user_id INT NULL");
    } catch (Exception $e) {}
    
    // Convert empty string to null for user_id
    if (empty($userId)) {
        $userId = null;
    }
        // Cek apakah token ini sudah ada
        $stmt = $pdo->prepare("SELECT id FROM fcm_tokens WHERE token = ?");
        $stmt->execute([$token]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update user_id dan timestamp saja
            $stmt = $pdo->prepare("UPDATE fcm_tokens SET user_id = ?, device_info = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$userId, $deviceInfo, $existing['id']]);
        } else {
            // Hapus token lama user ini jika ada (1 user = 1 device aktif)
            // Kecuali jika guest (userId = null)
            if ($userId !== null) {
                $stmt = $pdo->prepare("DELETE FROM fcm_tokens WHERE user_id = ?");
                $stmt->execute([$userId]);
            }

        // Insert token baru
        $stmt = $pdo->prepare("INSERT INTO fcm_tokens (user_id, token, device_info) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $token, $deviceInfo]);
    }

    echo json_encode(['status' => 'success', 'message' => 'FCM token berhasil disimpan']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
