<?php
// Environment Detection
$http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_local = (strpos($http_host, 'localhost') !== false || strpos($http_host, '127.0.0.1') !== false);

if ($is_local) {
    // Database Configuration untuk XAMPP Lokal
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'sewlovely');
    define('BASE_URL', '/sewlovely/');
} else {
    // Database Configuration untuk cPanel (Production)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'belt2974_sewlovely'); 
    define('DB_PASS', 'yKBxs6fpyKE337');     
    define('DB_NAME', 'belt2974_sewlovely'); 
    define('BASE_URL', '/');
}

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
