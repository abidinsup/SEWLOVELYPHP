<?php
// proxy_image.php - Proxy gambar banner dengan CORS headers untuk Flutter Web
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$url = $_GET['url'] ?? '';

if (empty($url)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing url parameter']);
    exit();
}

// Hanya izinkan URL dari domain sendiri untuk keamanan
$allowedDomain = 'gorden.beliyukk.com';
$parsedUrl = parse_url($url);
if (!$parsedUrl || !isset($parsedUrl['host']) || $parsedUrl['host'] !== $allowedDomain) {
    http_response_code(403);
    echo json_encode(['error' => 'Domain not allowed']);
    exit();
}

// Hanya izinkan path uploads
if (!isset($parsedUrl['path']) || strpos($parsedUrl['path'], '/uploads/') !== 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Path not allowed']);
    exit();
}

// Fetch gambar dari local file system (lebih cepat daripada HTTP request)
$localPath = $_SERVER['DOCUMENT_ROOT'] . $parsedUrl['path'];

if (!file_exists($localPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Image not found']);
    exit();
}

// Deteksi content type berdasarkan ekstensi file
$ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
];
$contentType = $mimeTypes[$ext] ?? 'application/octet-stream';

header("Content-Type: " . $contentType);
header("Content-Length: " . filesize($localPath));
header("Cache-Control: public, max-age=86400");

readfile($localPath);
