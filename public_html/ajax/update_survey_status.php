<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/helpers.php';

// Only admin can update survey status
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $survey_id = $_POST['survey_id'] ?? 0;
    $new_status = $_POST['status'] ?? '';
    
    $valid_statuses = ['pending', 'survey', 'waiting_payment', 'production', 'installation', 'done', 'cancelled'];
    
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
        exit;
    }

    try {
        // Fetch current status first
        $stmtCurrent = $pdo->prepare("SELECT status FROM surveys WHERE id = ?");
        $stmtCurrent->execute([$survey_id]);
        $current = $stmtCurrent->fetch();

        if (!$current) {
            echo json_encode(['success' => false, 'message' => 'Survey tidak ditemukan']);
            exit;
        }

        $current_status = $current['status'];

        // Validate transition using helpers.php
        if (!isValidStatusTransition($current_status, $new_status)) {
            echo json_encode(['success' => false, 'message' => "Tidak bisa mengubah status dari \"{$current_status}\" ke \"{$new_status}\""]);
            exit;
        }

        $survey_date = $_POST['survey_date'] ?? null;
        $survey_time = $_POST['survey_time'] ?? null;

        if ($survey_date && $survey_time) {
            $stmt = $pdo->prepare("UPDATE surveys SET status = ?, survey_date = ?, survey_time = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$new_status, $survey_date, $survey_time, $survey_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE surveys SET status = ?, updated_at = NOW() WHERE id = ?");
            $result = $stmt->execute([$new_status, $survey_id]);
        }

        if ($result && $stmt->rowCount() > 0) {
            // Auto Commission Logic when status is done
            if ($new_status === 'done') {
                $stmtInv = $pdo->prepare("
                    SELECT i.id as invoice_id, i.total_amount, i.invoice_number, s.partner_id, p.commission_percentage 
                    FROM invoices i
                    JOIN surveys s ON i.survey_id = s.id
                    JOIN partners p ON s.partner_id = p.id
                    WHERE s.id = ? AND i.commission_paid = 0
                ");
                $stmtInv->execute([$survey_id]);
                $inv = $stmtInv->fetch();

                if ($inv) {
                    $commission_desc = "Komisi (Otomatis Selesai) - " . $inv['invoice_number'];
                    $commission_percentage = $inv['commission_percentage'] > 0 ? $inv['commission_percentage'] : 5;
                    $commission_amount = $inv['total_amount'] * ($commission_percentage / 100);

                    $stmtComm = $pdo->prepare("INSERT INTO transactions (partner_id, type, amount, description, status) VALUES (?, 'commission', ?, ?, 'success')");
                    $stmtComm->execute([$inv['partner_id'], $commission_amount, $commission_desc]);

                    $stmtUpd = $pdo->prepare("UPDATE invoices SET commission_paid = 1 WHERE id = ?");
                    $stmtUpd->execute([$inv['invoice_id']]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Status survey berhasil diupdate']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengupdate status']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
