<?php
require_once '../includes/session.php';
require_once '../includes/config.php';

// Only admin can manage partners
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partner_id = $_POST['partner_id'] ?? 0;
    $action = $_POST['action'] ?? ''; // 'activate', 'deactivate', 'delete', 'reset_password'

    try {
        switch ($action) {
            case 'activate':
            case 'approve':
                $stmt = $pdo->prepare("UPDATE partners SET is_active = 1, status = 'approved' WHERE id = ?");
                $stmt->execute([$partner_id]);
                $msg = $action === 'approve' ? 'Mitra berhasil disetujui' : 'Mitra berhasil diaktifkan';
                echo json_encode(['success' => true, 'message' => $msg]);
                break;

            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE partners SET is_active = 0 WHERE id = ?");
                $stmt->execute([$partner_id]);
                echo json_encode(['success' => true, 'message' => 'Mitra berhasil dinonaktifkan']);
                break;

            case 'reject':
                // Hapus data user & partner agar email bisa dipakai daftar ulang
                $stmt = $pdo->prepare("SELECT user_id FROM partners WHERE id = ?");
                $stmt->execute([$partner_id]);
                $p = $stmt->fetch();
                if ($p) {
                    // Delete user (cascades to partner due to FK)
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$p['user_id']]);
                    echo json_encode(['success' => true, 'message' => 'Pendaftaran mitra ditolak dan data telah dihapus. Mitra dapat mendaftar ulang.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Mitra tidak ditemukan']);
                }
                break;

            case 'delete':
                // Get user_id first for cascade delete
                $stmt = $pdo->prepare("SELECT user_id FROM partners WHERE id = ?");
                $stmt->execute([$partner_id]);
                $p = $stmt->fetch();
                if ($p) {
                    // Delete user (cascades to partner due to FK)
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$p['user_id']]);
                    echo json_encode(['success' => true, 'message' => 'Mitra berhasil dihapus']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Mitra tidak ditemukan']);
                }
                break;

            case 'reset_password':
                $new_password = $_POST['new_password'] ?? '';
                if (strlen($new_password) < 6) {
                    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
                    break;
                }

                $stmt = $pdo->prepare("SELECT user_id FROM partners WHERE id = ?");
                $stmt->execute([$partner_id]);
                $p = $stmt->fetch();
                if ($p) {
                    $hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$hash, $p['user_id']]);
                    echo json_encode(['success' => true, 'message' => 'Password berhasil diupdate']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Mitra tidak ditemukan']);
                }
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
