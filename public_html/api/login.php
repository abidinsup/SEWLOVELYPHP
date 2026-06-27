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
    echo json_encode(['status' => 'error', 'message' => 'Metode HTTP tidak diizinkan. Gunakan POST.']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$email = '';
$password = '';

if (is_array($input) && isset($input['email'])) {
    $email = trim($input['email']);
    $password = $input['password'] ?? '';
} else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
}

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email dan Password harus diisi!']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.password_hash, u.role, p.is_active, p.status
        FROM users u
        LEFT JOIN partners p ON u.id = p.user_id
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['role'] === 'mitra' && $user['is_active'] == 0) {
            if ($user['status'] === 'pending') {
                echo json_encode(['status' => 'error', 'message' => 'Akun Anda sedang dalam proses verifikasi oleh admin.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Akun Anda dinonaktifkan atau ditolak. Silakan hubungi admin.']);
            }
        } else {
            $userData = [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Login berhasil!',
                'user' => $userData
            ]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email atau password salah!']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
