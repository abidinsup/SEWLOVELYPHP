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

if (empty($userId)) {
    echo json_encode(['status' => 'error', 'message' => 'user_id wajib diisi']);
    exit;
}

try {
    // Ambil notifikasi targeted untuk user ini + notifikasi broadcast (user_id IS NULL)
    $stmt = $pdo->prepare("
        SELECT id, user_id, title, body, type, is_read, data, created_at
        FROM notifications
        WHERE user_id = ? OR user_id IS NULL
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();

    // Format data
    $result = [];
    foreach ($notifications as $notif) {
        $result[] = [
            'id' => (int)$notif['id'],
            'title' => $notif['title'],
            'body' => $notif['body'],
            'type' => $notif['type'],
            'is_read' => (bool)$notif['is_read'],
            'data' => $notif['data'] ? json_decode($notif['data'], true) : null,
            'created_at' => $notif['created_at'],
            'is_broadcast' => is_null($notif['user_id'])
        ];
    }

    // Hitung unread
    $stmtUnread = $pdo->prepare("
        SELECT COUNT(*) FROM notifications 
        WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0
    ");
    $stmtUnread->execute([$userId]);
    $unreadCount = (int)$stmtUnread->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'unread_count' => $unreadCount,
        'notifications' => $result
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
