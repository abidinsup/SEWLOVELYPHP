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

if (empty($userId) || empty($token)) {
    echo json_encode(['status' => 'error', 'message' => 'user_id dan token wajib diisi']);
    exit;
}

try {
    // Cek apakah token ini sudah ada untuk user ini
    $stmt = $pdo->prepare("SELECT id FROM fcm_tokens WHERE user_id = ? AND token = ?");
    $stmt->execute([$userId, $token]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update timestamp saja
        $stmt = $pdo->prepare("UPDATE fcm_tokens SET device_info = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$deviceInfo, $existing['id']]);
    } else {
        // Hapus token lama user ini (1 user = 1 device aktif, bisa diubah jika mau multi-device)
        // Jika mau multi-device, comment baris di bawah ini
        $stmt = $pdo->prepare("DELETE FROM fcm_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Insert token baru
        $stmt = $pdo->prepare("INSERT INTO fcm_tokens (user_id, token, device_info) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $token, $deviceInfo]);
    }

    echo json_encode(['status' => 'success', 'message' => 'FCM token berhasil disimpan']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
