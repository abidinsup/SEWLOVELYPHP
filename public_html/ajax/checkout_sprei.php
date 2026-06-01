<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Fetch Partner ID
        $stmt = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $partner = $stmt->fetch();

        if (!$partner) {
            echo json_encode(['success' => false, 'message' => 'Partner data not found']);
            exit;
        }

        $partner_id = $partner['id'];
        $customer_name = $_POST['name'];
        $customer_phone = $_POST['phone'];
        $customer_address = $_POST['address'];
        $ukuran = $_POST['ukuran'];
        $price = $_POST['price'];
        $notes = $_POST['notes'];
        $full_notes = "Ukuran: " . $ukuran . " | Catatan: " . $notes;

        $pdo->beginTransaction();

        // 1. Insert into surveys (as order record)
        $stmt = $pdo->prepare("INSERT INTO surveys (partner_id, customer_name, customer_phone, customer_address, survey_date, survey_time, calculator_type, status, notes) VALUES (?, ?, ?, ?, CURDATE(), CURTIME(), 'sprei', 'done', ?)");
        $stmt->execute([$partner_id, $customer_name, $customer_phone, $customer_address, $full_notes]);
        $survey_id = $pdo->lastInsertId();

        // 2. Generate Invoice Number (unified format from helpers.php)
        $invoice_number = generateInvoiceNumber($survey_id);

        // 3. Insert into invoices
        $secure_token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO invoices (survey_id, invoice_number, secure_token, total_amount, payment_status) VALUES (?, ?, ?, ?, 'paid')");
        $stmt->execute([$survey_id, $invoice_number, $secure_token, $price]);

        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Checkout successful',
            'invoice_number' => $invoice_number
        ]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
