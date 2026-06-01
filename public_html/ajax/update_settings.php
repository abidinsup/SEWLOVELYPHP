<?php
require_once '../includes/session.php';
require_once '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_general') {
            $settings = [
                'store_name' => $_POST['store_name'] ?? '',
                'store_address' => $_POST['store_address'] ?? '',
                'store_phone' => $_POST['store_phone'] ?? '',
                'bank_info' => $_POST['bank_info'] ?? ''
            ];

            foreach ($settings as $key => $val) {
                $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$val, $key]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Pengaturan umum berhasil disimpan.']);

        } elseif ($action === 'update_invoice') {
            $invoice_terms = $_POST['invoice_terms'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'invoice_terms'");
            $stmt->execute([$invoice_terms]);
            
            // Handle logo upload
            if (isset($_FILES['invoice_logo']) && $_FILES['invoice_logo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/images/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['invoice_logo']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $file_name = 'logo_' . time() . '.' . $file_extension;
                    $target_file = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['invoice_logo']['tmp_name'], $target_file)) {
                        $logo_url = 'uploads/images/' . $file_name;
                        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'invoice_logo'");
                        $stmt->execute([$logo_url]);
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Pengaturan invoice/nota berhasil disimpan.']);

        } elseif ($action === 'update_commission') {
            $default_commission = $_POST['default_commission'] ?? '5';
            
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'default_commission'");
            $stmt->execute([$default_commission]);
            
            echo json_encode(['success' => true, 'message' => 'Pengaturan komisi berhasil disimpan.']);

        } elseif ($action === 'update_admin') {
            $old_password = $_POST['old_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (strlen($new_password) < 6) {
                echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter.']);
                exit;
            }
            if ($new_password !== $confirm_password) {
                echo json_encode(['success' => false, 'message' => 'Konfirmasi password tidak cocok.']);
                exit;
            }
            
            // Verify old password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($old_password, $user['password_hash'])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$new_hash, $_SESSION['user_id']]);
                echo json_encode(['success' => true, 'message' => 'Password admin berhasil diubah.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Password lama salah.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
