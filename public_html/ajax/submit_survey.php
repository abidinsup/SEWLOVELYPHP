<?php
require_once '../includes/session.php';
require_once '../includes/config.php';

// Cek apakah mitra sudah login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mitra') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Ambil ID Partner berdasarkan user_id sesi
        $stmt = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $partner = $stmt->fetch();

        if (!$partner) {
            echo json_encode(['success' => false, 'message' => 'Data mitra tidak ditemukan']);
            exit;
        }

        $partner_id = $partner['id'];
        $customer_name = $_POST['name'];
        $customer_phone = $_POST['phone'];
        
        // --- Standarisasi No. WA ---
        // 1. Hapus semua karakter non-angka
        $customer_phone = preg_replace('/[^0-9]/', '', $customer_phone);
        // 2. Jika diawali angka 0, ubah jadi 62
        if (substr($customer_phone, 0, 1) === '0') {
            $customer_phone = '62' . substr($customer_phone, 1);
        }
        // 3. Validasi panjang minimum
        if (strlen($customer_phone) < 10) {
            echo json_encode(['success' => false, 'message' => 'Nomor WhatsApp tidak valid.']);
            exit;
        }
        // --- Akhir Standarisasi ---
        $customer_address = $_POST['address'];
        $survey_date = !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
        $survey_time = !empty($_POST['time']) ? $_POST['time'] : '-';
        $calculator_type = $_POST['type'];
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        // Simpan ke database
        $stmt = $pdo->prepare("INSERT INTO surveys (partner_id, customer_name, customer_phone, customer_address, survey_date, survey_time, calculator_type, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        
        $result = $stmt->execute([
            $partner_id,
            $customer_name,
            $customer_phone,
            $customer_address,
            $survey_date,
            $survey_time,
            $calculator_type,
            $notes
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Survey berhasil dijadwalkan']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data']);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
