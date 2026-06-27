<?php
require_once '../includes/config.php';

try {
    $pdo->beginTransaction();

    // Ambil semua invoice POS Sprei yang sudah lunas
    $stmtInvoices = $pdo->query("
        SELECT i.id as invoice_id, i.invoice_number, i.total_amount, s.partner_id
        FROM invoices i
        JOIN surveys s ON i.survey_id = s.id
        WHERE s.calculator_type = 'sprei' AND i.payment_status = 'paid'
    ");
    $invoices = $stmtInvoices->fetchAll(PDO::FETCH_ASSOC);

    $inserted = 0;

    foreach ($invoices as $orderData) {
        $partner_id = $orderData['partner_id'];
        $total_amount = $orderData['total_amount'];
        $invoice_number = $orderData['invoice_number'];
        $commission_desc = "Komisi POS Sprei (5%) - " . $invoice_number;

        // Cek apakah komisi untuk invoice ini sudah pernah diberikan
        $stmtCheck = $pdo->prepare("SELECT id FROM transactions WHERE partner_id = ? AND type = 'commission' AND description = ?");
        $stmtCheck->execute([$partner_id, $commission_desc]);
        
        if (!$stmtCheck->fetch()) {
            // Hitung komisi 5%
            $commission_amount = $total_amount * 0.05;

            // Masukkan ke transaksi
            $stmtComm = $pdo->prepare("
                INSERT INTO transactions (partner_id, type, amount, description, status) 
                VALUES (?, 'commission', ?, ?, 'success')
            ");
            $stmtComm->execute([$partner_id, $commission_amount, $commission_desc]);
            $inserted++;
        }
    }

    $pdo->commit();

    echo "<h3>Sukses!</h3>";
    echo "<p>Berhasil menambahkan <strong>$inserted</strong> data komisi yang tertinggal untuk pesanan POS Sprei yang sudah lunas.</p>";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
?>
