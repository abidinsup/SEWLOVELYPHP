<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if (!$id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get claim details
    $stmt = $pdo->prepare("SELECT * FROM reward_claims WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $claim = $stmt->fetch();

    if (!$claim) {
        throw new Exception("Klaim tidak ditemukan atau sudah diproses.");
    }

    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
    
    // Update claim status
    $stmt = $pdo->prepare("UPDATE reward_claims SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);

    // If rejected, refund points to partner
    if ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE partners SET points = points + ? WHERE id = ?");
        $stmt->execute([$claim['points_used'], $claim['partner_id']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Klaim berhasil diproses.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
