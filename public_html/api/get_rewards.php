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
            'image' => 'https://upload.wikimedia.org/wikipedia/commons/4/4e/IDR_100000_2016_Series.jpg',
            'description' => 'Bonus uang tunai langsung ke rekening Anda'
        ],
        [
            'id' => 2,
            'name' => 'Kipas Angin / Magic Com',
            'points_required' => 6,
            'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Desk_fan.jpg/800px-Desk_fan.jpg',
            'description' => 'Peralatan rumah tangga pilihan'
        ],
        [
            'id' => 3,
            'name' => 'Smart TV',
            'points_required' => 30,
            'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/d3/LG_Smart_TV.jpg/800px-LG_Smart_TV.jpg',
            'description' => 'Smart TV 32 Inch'
        ],
        [
            'id' => 4,
            'name' => 'Sepeda Motor',
            'points_required' => 360,
            'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e0/Honda_Vario_150_eSP.jpg/800px-Honda_Vario_150_eSP.jpg',
            'description' => 'Satu unit sepeda motor'
        ],
        [
            'id' => 5,
            'name' => 'Paket Umroh',
            'points_required' => 650,
            'image' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/55/Kaaba_Masjid_Haraam_Makkah.jpg/800px-Kaaba_Masjid_Haraam_Makkah.jpg',
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
