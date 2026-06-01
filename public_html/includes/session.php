<?php
// Gunakan nama sesi khusus agar tidak bentrok dengan aplikasi lain di cPanel yang sama
session_name('SEWLOVELY_SESS');
session_start();

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        $loginUrl = defined('BASE_URL') ? BASE_URL . 'login.php' : '/login.php';
        header("Location: " . $loginUrl);
        exit;
    }
}

function checkAdmin() {
    checkLogin();
    if ($_SESSION['role'] !== 'admin') {
        // Redirect to mitra dashboard if not admin
        $mitraUrl = defined('BASE_URL') ? BASE_URL . 'mitra/index.php' : '/mitra/index.php';
        header("Location: " . $mitraUrl);
        exit;
    }
}

function checkMitra() {
    checkLogin();
    if ($_SESSION['role'] !== 'mitra') {
        // Redirect to admin dashboard if not mitra
        $adminUrl = defined('BASE_URL') ? BASE_URL . 'admin/index.php' : '/admin/index.php';
        header("Location: " . $adminUrl);
        exit;
    }
}
?>
