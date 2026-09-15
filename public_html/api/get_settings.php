<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../includes/config.php';

// Fungsi helper untuk mendapatkan base url untuk gambar
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    // path is usually /api, so we need to go one level up
    $path = str_replace('/api', '', $path);
    return $protocol . "://" . $host . $path;
}

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'app_settings'");
    if ($stmt->rowCount() == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Tabel setting belum ada']);
        exit;
    }

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings");
    $rawSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $baseUrl = getBaseUrl();
    
    $banners = [];
    for ($i = 1; $i <= 3; $i++) {
        $desc = $rawSettings["promo_banner_{$i}_desc"] ?? '';
        $highlight = $rawSettings["promo_banner_{$i}_highlight"] ?? '';
        $image = $rawSettings["promo_banner_{$i}_image"] ?? '';
        
        // Pastikan ada spasi (non-breaking space) agar tidak menempel dengan tulisan Rp di mobile app
        $desc = rtrim($desc) . "\u{00A0}";
        $highlight = "\u{00A0}" . ltrim($highlight);
        
        $imageUrl = !empty($image) ? $baseUrl . "/uploads/banners/" . $image : "";
        
        // Hanya masukkan banner yang aktif
        if (isset($rawSettings["promo_banner_{$i}_active"]) && $rawSettings["promo_banner_{$i}_active"] == '1') {
            $banners[] = [
                'id' => $i,
                'title' => $rawSettings["promo_banner_{$i}_title"] ?? '',
                'desc' => $desc,
                'highlight' => $highlight,
                'image_url' => $imageUrl
            ];
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'banners' => $banners
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil pengaturan.']);
}
