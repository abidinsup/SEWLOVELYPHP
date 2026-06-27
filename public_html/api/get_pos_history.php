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

$userId = $input['user_id'] ?? null;

if (empty($userId)) {
    echo json_encode(['status' => 'error', 'message' => 'User ID wajib disertakan!']);
    exit;
}

try {
    // 1. Get partner_id
    $stmtPartner = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmtPartner->execute([$userId]);
    $partner = $stmtPartner->fetch();
    
    if (!$partner) {
        echo json_encode(['status' => 'success', 'data' => []]);
        exit;
    }
    $partnerId = $partner['id'];

    $stmt = $pdo->prepare("
        SELECT i.id, s.customer_name as nama, s.customer_phone as wa, s.customer_address as alamat, 
               s.cart_items as cart, i.total_amount as total, 
               (CASE 
                  WHEN i.payment_status = 'paid' THEN 'paid'
                  WHEN i.payment_status = 'partial' THEN 'partial'
                  WHEN s.status = 'confirmed' THEN 'confirmed'
                  ELSE 'waiting_payment'
               END) as status, 
               i.created_at as date
        FROM invoices i
        JOIN surveys s ON i.survey_id = s.id
        WHERE s.partner_id = ? AND s.calculator_type = 'sprei'
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$partnerId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as &$order) {
        if (!empty($order['cart'])) {
            $decoded = json_decode($order['cart'], true);
            $order['cart'] = $decoded !== null ? $decoded : [];
        } else {
            $order['cart'] = [];
        }
    }

    echo json_encode([
        'status' => 'success',
        'data' => $orders
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
