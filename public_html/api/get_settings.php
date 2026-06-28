<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../includes/config.php';

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'app_settings'");
    if ($stmt->rowCount() == 0) {
        // Table doesn't exist yet, return defaults
        echo json_encode([
            'status' => 'success',
            'data' => [
                'promo_banner_active' => '1',
                'promo_banner_title' => "Raih Bonusnya!\nSelesaikan 5 Pemasangan",
                'promo_banner_desc' => "Selesaikan 5 projek pemasangan dan\ndapatkan komisi tambahan ",
                'promo_banner_highlight' => "Rp 300.000"
            ]
        ]);
        exit;
    }

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Pastikan ada spasi (non-breaking space) agar tidak menempel dengan tulisan Rp di mobile app
    if (isset($settings['promo_banner_desc'])) {
        $settings['promo_banner_desc'] = rtrim($settings['promo_banner_desc']) . "\u{00A0}";
    }
    if (isset($settings['promo_banner_highlight'])) {
        $settings['promo_banner_highlight'] = "\u{00A0}" . ltrim($settings['promo_banner_highlight']);
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => $settings
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil pengaturan.']);
}
