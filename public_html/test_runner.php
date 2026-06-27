<?php
// test_runner.php
echo "Memulai Pengujian Otomatis API Backend...\n\n";

$baseUrl = 'http://localhost:8080/sewlovely_mobile/api/';

function postJson($url, $data) {
    $ch = curl_init($url);
    $jsonData = json_encode($data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'cURL error: ' . curl_error($ch) . "\n";
    }
    curl_close($ch);
    return json_decode($result, true) ?: $result;
}

// 1. Uji Register
echo "1. Menguji Pendaftaran Akun Baru (Register)...\n";
$email = 'tester' . time() . '@test.com';
$password = '123456';
$regData = [
    'full_name' => 'Mitra Tester Otomatis',
    'email' => $email,
    'whatsapp_number' => '0812' . rand(100000, 999999),
    'password' => $password
];
$regResponse = postJson($baseUrl . 'register.php', $regData);
print_r($regResponse);
echo "\n";

// 2. Mendapatkan User ID dari Database (Bypass Verifikasi Admin)...
echo "2. Mendapatkan User ID dari Database (Bypass Verifikasi Admin)...\n";
require_once 'includes/config.php';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

$userId = $user ? $user['id'] : null;

if (!$userId) {
    echo "GAGAL: Tidak bisa mendapatkan User ID. Tes dihentikan.\n";
    exit;
}
echo "User ID ditemukan: $userId\n\n";

// 3. Uji Ajukan Survey
echo "3. Menguji Pengajuan Survey...\n";
$surveyData = [
    'user_id' => $userId,
    'customer_name' => 'Bapak Joko (Auto Test)',
    'customer_whatsapp' => '081122334455',
    'address' => 'Jl. Merdeka No 45, Jakarta',
    'survey_date' => '2026-07-01',
    'survey_time' => '14:00',
    'notes' => 'Tolong diukur untuk 3 jendela kamar.'
];
$surveyResponse = postJson($baseUrl . 'submit_survey.php', $surveyData);
print_r($surveyResponse);
echo "\n";

// 4. Uji Lacak Survey
echo "4. Menguji Tarik Riwayat Survey...\n";
$getSurveysResponse = postJson($baseUrl . 'get_surveys.php', ['user_id' => $userId]);
print_r($getSurveysResponse);
echo "\n";

// 5. Uji POS Checkout Sprei
echo "5. Menguji POS Checkout Sprei...\n";
$posData = [
    'user_id' => $userId,
    'nama' => 'Ibu Ratna (Auto Test)',
    'wa' => '089988776655',
    'alamat' => 'Perum Indah Blok C2',
    'total' => 480000,
    'cart' => [
        ['productName' => 'Sprei King (180x200)', 'quantity' => 1, 'price' => 250000],
        ['productName' => 'Sprei Queen (160x200)', 'quantity' => 1, 'price' => 230000]
    ]
];
$posResponse = postJson($baseUrl . 'submit_pos_order.php', $posData);
print_r($posResponse);
echo "\n";

// 6. Uji Tarik Riwayat Transaksi POS
echo "6. Menguji Tarik Riwayat Transaksi POS...\n";
$getPosHistoryResponse = postJson($baseUrl . 'get_pos_history.php', ['user_id' => $userId]);
print_r($getPosHistoryResponse);
echo "\n";

echo "Semua pengujian selesai!\n";
?>
