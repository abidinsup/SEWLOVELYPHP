<?php
require_once 'includes/config.php';
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
ob_start();
include 'admin/calculator.php';
$html = ob_get_clean();
if (strpos($html, 'bg-red-500') !== false) {
    echo "BADGE FOUND!\n";
} else {
    echo "BADGE MISSING!\n";
}
file_put_contents('test_output.html', $html);
