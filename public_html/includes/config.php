<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Ganti sesuai cPanel
define('DB_PASS', '');     // Ganti sesuai cPanel
define('DB_NAME', 'sewlovely'); // Ganti sesuai nama database di cPanel
// Konfigurasi URL Utama
// Jika Anda meletakkan web ini di dalam sub-folder (misal: public_html/sewlovely/), ubah menjadi '/sewlovely/'
// Jika di root (domain utama), biarkan '/'
define('BASE_URL', '/sewlovely/');

// Gemini AI API Key
define('GEMINI_API_KEY', 'AIzaSyDYbArK5bLh0jMhmGVUnFjdQ6uIX3c0lNw');

// Timezone
date_default_timezone_set('Asia/Jakarta');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
