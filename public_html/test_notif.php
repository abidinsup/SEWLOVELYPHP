<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

function checkAdmin() {} // Mock

$_SERVER['REQUEST_METHOD'] = 'GET';
require_once 'c:/SEWLOVELY V1/public_html/includes/config.php';
require_once 'c:/SEWLOVELY V1/public_html/includes/firebase_helper.php';

// Try querying the notifications table
try {
    $stmt = $pdo->query("SELECT * FROM notifications LIMIT 1");
    echo "Query success!\n";
} catch (Exception $e) {
    echo "Query failed: " . $e->getMessage() . "\n";
}

// Check if fcm_tokens table exists
try {
    $stmt = $pdo->query("SELECT * FROM fcm_tokens LIMIT 1");
    echo "fcm_tokens exists!\n";
} catch (Exception $e) {
    echo "fcm_tokens missing: " . $e->getMessage() . "\n";
}
