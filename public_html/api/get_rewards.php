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

try {
    // Ambil data poin partner
    $stmt = $pdo->prepare("SELECT id, reward_points FROM partners WHERE user_id = ?");
    $stmt->execute([$userId]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partner) {
        echo json_encode(['status' => 'error', 'message' => 'Mitra tidak ditemukan.']);
        exit;
    }

    // Daftar hadiah statis sesuai aturan sistem (Loyalty)
    $rewards = [
        [
            'id' => 1,
            'name' => 'Uang Tunai (Rp 150.000)',
            'points_required' => 3,
            'image' => 'https://images.unsplash.com/photo-1580519542036-ed47f3e42d17?w=600&h=400&fit=crop',
            'description' => 'Bonus uang tunai langsung ke rekening Anda'
        ],
        [
            'id' => 2,
            'name' => 'Kipas Angin / Magic Com',
            'points_required' => 6,
            'image' => 'https://images.unsplash.com/photo-1618506469810-282bb698d822?w=600&h=400&fit=crop',
            'description' => 'Peralatan rumah tangga pilihan'
        ],
        [
            'id' => 3,
            'name' => 'Smart TV',
            'points_required' => 30,
            'image' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?w=600&h=400&fit=crop',
            'description' => 'Smart TV 32 Inch'
        ],
        [
            'id' => 4,
            'name' => 'Sepeda Motor',
            'points_required' => 360,
            'image' => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=600&h=400&fit=crop',
            'description' => 'Satu unit sepeda motor'
        ],
        [
            'id' => 5,
            'name' => 'Paket Umroh',
            'points_required' => 650,
            'image' => 'https://images.unsplash.com/photo-1565552643952-b43cb314f4fa?w=600&h=400&fit=crop',
            'description' => 'Perjalanan Umroh eksklusif'
        ]
    ];

    echo json_encode([
        'status' => 'success',
        'data' => [
            'points' => (int)$partner['reward_points'],
            'rewards' => $rewards
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
