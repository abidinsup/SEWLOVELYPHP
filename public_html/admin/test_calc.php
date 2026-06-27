<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../includes/config.php';
require_once '../includes/session.php';
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
ob_start();
include 'calculator.php';
$html = ob_get_clean();
if (strpos($html, 'bg-red-500') !== false) {
    echo "BADGE FOUND!\n";
} else {
    echo "BADGE MISSING!\n";
}
