<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Gunakan metode POST.']);
    exit;
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

$userId = $input['user_id'] ?? $_POST['user_id'] ?? null;

if (empty($userId)) {
    echo json_encode(['status' => 'error', 'message' => 'User ID wajib disertakan!']);
    exit;
}

try {
    // Get partner_id from user_id
    $stmtPartner = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmtPartner->execute([$userId]);
    $partner = $stmtPartner->fetch();
    
    if (!$partner) {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }
    $partnerId = $partner['id'];

    // Check if the surveys table exists, if not just return empty for safety
    $stmt = $pdo->prepare("
        SELECT id, customer_name, customer_phone, customer_address, survey_date, survey_time, status, created_at
        FROM surveys 
        WHERE partner_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$partnerId]);
    $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formattedSurveys = [];
    foreach ($surveys as $survey) {
        $displayStatus = 'Menunggu';
        if ($survey['status'] === 'pending') {
            $displayStatus = 'Menunggu';
        } else if (in_array($survey['status'], ['survey', 'waiting_payment', 'production', 'installation', 'in_progress', 'disurvey'])) {
            $displayStatus = 'Diproses';
        } else if (in_array($survey['status'], ['done', 'completed', 'selesai'])) {
            $displayStatus = 'Selesai';
        } else if ($survey['status'] === 'cancelled') {
            $displayStatus = 'Dibatalkan';
        } else {
            // uppercase first letter
            $displayStatus = ucfirst($survey['status']);
        }

        // Format Date
        $dateStr = 'Belum Ada Jadwal';
        if (!empty($survey['survey_date']) && $survey['survey_date'] !== '0000-00-00') {
            try {
                $dateObj = new DateTime($survey['survey_date']);
                $timeStr = !empty($survey['survey_time']) ? ', ' . $survey['survey_time'] . ' WIB' : '';
                // Example output: 15 Jun 2026, 10:00 WIB
                $dateStr = $dateObj->format('d M Y') . $timeStr;
            } catch (Exception $e) {
                $dateStr = 'Format Tanggal Salah';
            }
        }

        $formattedSurveys[] = [
            'id' => $survey['id'],
            'customer' => $survey['customer_name'] ?: 'Customer',
            'wa' => $survey['customer_phone'] ?: '-',
            'location' => $survey['customer_address'] ?: '-',
            'date' => $dateStr,
            'status' => $displayStatus,
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $formattedSurveys
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
}
?>
