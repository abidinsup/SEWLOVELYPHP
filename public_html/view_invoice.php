<?php
require_once 'includes/config.php';

if (!isset($_GET['id']) && !isset($_GET['token'])) {
    die("ID / Token Nota tidak ditemukan.");
}

$query_condition = "";
$params = [];

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $query_condition = "i.secure_token = ?";
    $params = [$token];
} else {
    $survey_id = intval($_GET['id']);
    $query_condition = "s.id = ?";
    $params = [$survey_id];
}

// Fetch survey and invoice data
$stmt = $pdo->prepare("
    SELECT s.*, p.full_name as partner_name, i.invoice_number, i.payment_status, i.total_amount, i.invoice_notes 
    FROM surveys s 
    LEFT JOIN partners p ON s.partner_id = p.id 
    LEFT JOIN invoices i ON i.survey_id = s.id 
    WHERE $query_condition
");
$stmt->execute($params);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data nota tidak ditemukan.");
}

// Company Info
$company_name = "SEWLOVELY HOMESET";
$company_phone = "0851-5958-8681";

// Parse Invoice Notes
$items = [];
if (!empty($data['invoice_notes'])) {
    $lines = explode("\n", trim($data['invoice_notes']));
    foreach ($lines as $line) {
        if (trim($line) === '') continue;
        
        // New Pipe Format: | No | Nama Produk | Qty | Harga Satuan | Total |
        if (strpos($line, '|') !== false) {
            $parts = explode('|', trim($line, '| '));
            if (count($parts) >= 4) {
                $name = trim($parts[1]);
                $name = preg_replace('/\s*\([^)]*cm\s*(x|×)\s*[^)]*\)/i', '', $name);
                $items[] = [
                    'no' => trim($parts[0]),
                    'name' => $name,
                    'qty' => trim($parts[2]),
                    'unit_price' => trim($parts[3]),
                    'total' => trim($parts[4])
                ];
            }
        } 
        // Legacy Support
        elseif (preg_match('/^- (.*) \((.*)\): Rp (.*)$/', $line, $matches)) {
            $items[] = [
                'no' => count($items) + 1,
                'name' => $matches[1],
                'qty' => '1',
                'unit_price' => $matches[3],
                'total' => $matches[3]
            ];
        }
        // Support for new Kalkulator Gorden format
        elseif (preg_match('/^(.*): Rp (.*)$/', $line, $matches)) {
            $name = trim($matches[1]);
            $name = preg_replace('/\s*\([^)]*cm\s*(x|×)\s*[^)]*\)/i', '', $name);
            $items[] = [
                'no' => count($items) + 1,
                'name' => $name,
                'qty' => '1',
                'unit_price' => trim($matches[2]),
                'total' => trim($matches[2])
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Digital - <?= htmlspecialchars($data['invoice_number'] ?? 'INV-' . $data['id']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ecfdf5; }
        .invoice-card { background: white; border-radius: 2.5rem; box-shadow: 0 25px 50px -12px rgba(5, 150, 105, 0.1); }
        .status-badge { border-radius: 12px; font-weight: 800; font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; padding: 6px 12px; }
        @media print {
            .no-print { display: none !important; }
            body { background-color: white !important; padding: 0 !important; }
            .invoice-card { box-shadow: none !important; border: none !important; border-radius: 0 !important; }
        }
    </style>
</head>
<body class="min-h-screen py-8 px-4 md:py-16">

    <!-- Action Bar (Hidden on Print) -->
    <div class="max-w-2xl mx-auto mb-8 flex justify-end no-print">
        <button onclick="window.print()" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-2xl font-bold shadow-lg shadow-emerald-600/20 transition-all active:scale-95 text-xs">
            <i data-lucide="printer" class="w-4 h-4"></i> Cetak Invoice
        </button>
    </div>

    <!-- Brand Header -->
    <div class="max-w-2xl mx-auto mb-8 text-center">
        <div class="w-16 h-16 bg-emerald-600 rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-xl shadow-emerald-600/30">
            <i data-lucide="home" class="w-8 h-8 text-white"></i>
        </div>
        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight"><?= $company_name ?></h1>
        <p class="text-emerald-600 text-[10px] font-bold uppercase tracking-[0.2em] mt-1 italic">Premium Interior Solutions</p>
    </div>

    <!-- Main Card -->
    <div class="max-w-2xl mx-auto invoice-card border border-emerald-100 overflow-hidden">
        
        <!-- Top Banner -->
        <div class="bg-emerald-600 p-8 md:p-12 text-white relative overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h2 class="text-4xl font-black tracking-tighter uppercase mb-2">Invoice</h2>
                    <p class="text-emerald-100 font-bold text-sm tracking-wide">#<?= htmlspecialchars($data['invoice_number'] ?? 'INV-'.$data['id']) ?></p>
                </div>
                <div class="text-left md:text-right">
                    <p class="text-emerald-200 font-bold text-[10px] uppercase tracking-widest mb-1">Status Tagihan</p>
                    <span class="status-badge bg-white/20 text-white border border-white/30">
                        <?= ($data['payment_status'] === 'paid') ? 'Lunas' : (($data['payment_status'] === 'partial') ? 'DP / Sebagian' : 'Belum Bayar') ?>
                    </span>
                </div>
            </div>
            <!-- Background Icon -->
            <i data-lucide="scroll-text" class="absolute -right-8 -bottom-8 w-48 h-48 text-white/10 -rotate-12"></i>
        </div>

        <!-- Body -->
        <div class="p-8 md:p-12 space-y-10">
            
            <!-- Parties Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="space-y-1">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Penerima Tagihan</p>
                    <p class="font-extrabold text-slate-900 uppercase tracking-tight"><?= htmlspecialchars($data['customer_name']) ?></p>
                    <p class="text-sm font-medium text-slate-500 leading-relaxed"><?= nl2br(htmlspecialchars($data['customer_address'])) ?></p>
                </div>
                <div class="space-y-1 md:text-right">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Waktu Transaksi</p>
                    <p class="font-bold text-slate-800"><?= date('d F Y', strtotime($data['created_at'])) ?></p>
                    <p class="text-sm font-medium text-slate-500"><?= date('H:i', strtotime($data['created_at'])) ?> WIB</p>
                </div>
            </div>

            <!-- Items List (Mobile Optimized) -->
            <div class="space-y-4">
                <div class="flex justify-between items-center px-1">
                    <h3 class="text-xs font-black text-slate-900 uppercase tracking-widest">Detail Pesanan</h3>
                    <div class="h-px flex-1 bg-emerald-50 mx-4"></div>
                </div>
                
                <div class="space-y-3">
                    <?php if (empty($items)): ?>
                        <p class="text-sm text-slate-400 italic">Data pesanan tidak tersedia.</p>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <div class="p-5 bg-emerald-50/30 rounded-2xl border border-emerald-100 transition-all hover:border-emerald-200">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex gap-3">
                                    <span class="text-xs font-black text-emerald-600 bg-emerald-100 w-6 h-6 flex items-center justify-center rounded-lg"><?= $item['no'] ?></span>
                                    <div>
                                        <p class="text-sm font-black text-slate-800 uppercase tracking-tight"><?= htmlspecialchars($item['name']) ?></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-emerald-700">Rp <?= $item['total'] ?></p>
                                </div>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-emerald-100/50">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Jumlah: <?= $item['qty'] ?></span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Harga: Rp <?= $item['unit_price'] ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary -->
            <div class="pt-6 border-t border-emerald-100">
                <div class="flex justify-between items-center">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Tagihan</p>
                    <p class="text-3xl font-black text-slate-900 tracking-tighter">Rp <?= number_format($data['total_amount'], 0, ',', '.') ?></p>
                </div>
                
                <div class="mt-8 p-6 bg-emerald-600 rounded-3xl flex flex-col items-center text-center gap-3 text-white shadow-xl shadow-emerald-600/20">
                    <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center mb-2">
                        <i data-lucide="<?= ($data['payment_status'] === 'paid') ? 'shield-check' : 'check-circle' ?>" class="w-5 h-5 text-white"></i>
                    </div>
                    <p class="text-xs font-black uppercase tracking-widest">
                        <?= ($data['payment_status'] === 'paid') ? 'Pembayaran Lunas' : 'Pesanan Dalam Produksi' ?>
                    </p>
                    <p class="text-[10px] text-emerald-100 font-medium max-w-[250px]">
                        <?= ($data['payment_status'] === 'paid') ? 'Transaksi ini telah terverifikasi lunas oleh sistem Sewlovely.' : 'Terima kasih! Pesanan Anda saat ini sedang dikerjakan oleh tim kami.' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-emerald-50 p-8 text-center space-y-4">
            <p class="text-[10px] text-emerald-600/60 font-black uppercase tracking-[0.3em]">Official Digital Receipt • <?= date('Y') ?></p>
            <div class="flex justify-center gap-6">
                <a href="tel:<?= $company_phone ?>" class="flex items-center gap-2 text-emerald-600 hover:text-emerald-800 transition-colors">
                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                    <span class="text-[10px] font-bold uppercase tracking-widest">Hubungi Kami</span>
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-2xl mx-auto mt-8 text-center">
        <p class="text-[10px] text-emerald-600/40 font-bold uppercase tracking-widest italic">Simpan link ini sebagai bukti transaksi resmi Anda</p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
