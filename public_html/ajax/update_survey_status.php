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

        $stmt = $pdo->prepare("UPDATE surveys SET status = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$new_status, $survey_id]);

        if ($result && $stmt->rowCount() > 0) {
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
