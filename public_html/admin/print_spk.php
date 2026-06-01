<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

if (!isset($_GET['id'])) {
    die("ID Survey tidak ditemukan.");
}

$survey_id = intval($_GET['id']);

// Fetch survey and invoice data
$stmt = $pdo->prepare("
    SELECT s.*, p.full_name as partner_name, i.invoice_number, i.payment_status, i.total_amount, i.discount_amount, i.paid_amount, i.invoice_notes 
    FROM surveys s 
    LEFT JOIN partners p ON s.partner_id = p.id 
    LEFT JOIN invoices i ON i.survey_id = s.id 
    WHERE s.id = ?
");
$stmt->execute([$survey_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data pesanan tidak ditemukan.");
}

// Company Info
$company_name = "SEWLOVELY HOMESET";
$company_address = "Jl. Raya Solo - Yogyakarta No. 123\nKlaten, Jawa Tengah";
$company_phone = "0851-5958-8681";
$company_email = "hello@sewlovely.com";

// Parse Invoice Notes to Table
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
        // Legacy Support for Old Format
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
    <title>Invoice - <?= htmlspecialchars($data['invoice_number'] ?? 'INV-' . $data['id']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; -webkit-print-color-adjust: exact; }
        
        /* Optimized Print Settings */
        @page {
            size: A4;
            margin: 0; /* Margin handled by body padding for better control */
        }

        @media print {
            .no-print { display: none !important; }
            body { 
                background-color: white !important; 
                padding: 0 !important; 
                margin: 0 !important;
            }
            .print-card { 
                border: none !important; 
                box-shadow: none !important; 
                margin: 0 !important; 
                width: 100% !important; 
                max-width: 100% !important; 
                border-radius: 0 !important;
            }
            
            /* Compact Header for Print */
            .header-section { padding: 2rem 3rem 1rem 3rem !important; }
            .brand-logo { width: 3rem !important; height: 3rem !important; }
            .brand-name { font-size: 1.25rem !important; }
            
            /* Compact Table for Print */
            .table-section { padding: 1rem 3rem !important; }
            .table-row td { padding-top: 0.75rem !important; padding-bottom: 0.75rem !important; }
            
            /* Compact Summary */
            .summary-section { 
                padding-top: 1rem !important; 
                margin-top: 1rem !important;
                page-break-inside: avoid; 
            }
            
            /* Footer Fix */
            .footer-bar { padding: 1.5rem 3rem !important; }
        }

        .invoice-bg-dot { background-image: radial-gradient(#10b981 1px, transparent 1px); background-size: 20px 20px; }
        
        /* Smooth scale for large items */
        .table-row { page-break-inside: avoid; }
    </style>
</head>
<body class="bg-emerald-50/30 p-4 md:p-12 min-h-screen">

    <!-- Action Bar (Hidden on Print) -->
    <div class="max-w-4xl mx-auto mb-8 flex justify-between items-center no-print bg-white p-4 rounded-2xl shadow-sm border border-emerald-100">
        <button onclick="window.close()" class="flex items-center gap-2 text-slate-500 hover:text-emerald-600 font-bold transition-colors px-4 py-2">
            <i data-lucide="arrow-left" class="w-5 h-5"></i> Kembali
        </button>
        <div class="flex gap-3">
            <button onclick="window.print()" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-emerald-600/20 transition-all active:scale-95">
                <i data-lucide="printer" class="w-5 h-5"></i> CETAK INVOICE
            </button>
        </div>
    </div>

    <!-- Main Invoice Container -->
    <div class="max-w-4xl mx-auto bg-white shadow-2xl shadow-emerald-900/5 border border-emerald-100 overflow-hidden print-card relative">
        
        <!-- Top Accent Bar -->
        <div class="h-2 bg-emerald-600"></div>

        <!-- Header -->
        <div class="header-section p-12 pb-8 flex flex-row justify-between items-start gap-8 relative">
            <div class="space-y-6 flex-1">
                <div class="flex items-center gap-4">
                    <div class="brand-logo w-16 h-16 bg-emerald-600 rounded-2xl flex items-center justify-center transition-all">
                        <i data-lucide="home" class="w-8 h-8 text-white"></i>
                    </div>
                    <div>
                        <h1 class="brand-name text-2xl font-extrabold text-slate-900 tracking-tight leading-none"><?= $company_name ?></h1>
                        <p class="text-emerald-600 font-bold text-[10px] uppercase tracking-[0.2em] mt-1 italic">Premium Interior Solutions</p>
                    </div>
                </div>
                
                <div class="space-y-1">
                    <p class="text-sm font-bold text-slate-700">Tagihan Untuk:</p>
                    <h2 class="text-2xl font-extrabold text-slate-900 uppercase tracking-tight"><?= htmlspecialchars($data['customer_name']) ?></h2>
                    <p class="text-sm font-medium text-slate-500 max-w-xs leading-relaxed"><?= nl2br(htmlspecialchars($data['customer_address'])) ?></p>
                    <p class="text-sm font-bold text-slate-900 mt-2 flex items-center gap-2">
                        <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <?= htmlspecialchars($data['customer_phone']) ?>
                    </p>
                </div>
            </div>

            <div class="text-right space-y-8 flex-1">
                <div>
                    <h3 class="text-5xl font-black text-emerald-50 tracking-tighter uppercase leading-none">INVOICE</h3>
                    <p class="text-xl font-extrabold text-slate-900 mt-2">#<?= htmlspecialchars($data['invoice_number'] ?? 'INV-' . $data['id']) ?></p>
                </div>

                <div class="flex flex-col items-end gap-4">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tanggal Terbit</p>
                        <p class="text-sm font-bold text-slate-800"><?= date('d M Y', strtotime($data['created_at'])) ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Status Pembayaran</p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?= ($data['payment_status'] === 'paid') ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                            <?= ($data['payment_status'] === 'paid') ? 'Lunas' : (($data['payment_status'] === 'partial') ? 'DP (Uang Muka)' : 'Belum Bayar') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Divider -->
        <div class="px-12 py-4 flex items-center gap-4">
            <div class="h-px flex-1 bg-emerald-100"></div>
            <div class="w-2 h-2 rounded-full bg-emerald-200"></div>
            <div class="h-px flex-1 bg-emerald-100"></div>
        </div>

        <!-- Table Section -->
        <div class="table-section p-12 pt-4">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-emerald-600">
                        <th class="py-4 px-2 text-[11px] font-black text-slate-900 uppercase tracking-widest w-12">No</th>
                        <th class="py-4 px-2 text-[11px] font-black text-slate-900 uppercase tracking-widest">Nama Produk</th>
                        <th class="py-4 px-2 text-[11px] font-black text-slate-900 uppercase tracking-widest text-center">Qty</th>
                        <th class="py-4 px-2 text-[11px] font-black text-slate-900 uppercase tracking-widest text-right">Harga</th>
                        <th class="py-4 px-2 text-[11px] font-black text-slate-900 uppercase tracking-widest text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-50">
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-400 italic">Detail pesanan tidak tersedia.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <tr class="table-row hover:bg-emerald-50/30 transition-colors">
                            <td class="py-5 px-2 text-sm font-bold text-slate-400"><?= $item['no'] ?></td>
                            <td class="py-5 px-2">
                                <p class="text-sm font-bold text-slate-800 uppercase tracking-tight"><?= htmlspecialchars($item['name']) ?></p>
                            </td>
                            <td class="py-5 px-2 text-center text-sm font-bold text-slate-600"><?= $item['qty'] ?></td>
                            <td class="py-5 px-2 text-right text-sm font-bold text-slate-600">Rp <?= $item['unit_price'] ?></td>
                            <td class="py-5 px-2 text-right text-sm font-black text-emerald-700">Rp <?= $item['total'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Summary -->
            <div class="summary-section mt-12 flex flex-col md:flex-row justify-between items-start gap-12 pt-8 border-t-2 border-emerald-100">
                <div class="space-y-4 max-w-sm">
                    <div class="p-6 bg-emerald-50/50 rounded-2xl border border-emerald-100">
                        <h4 class="text-xs font-black text-emerald-800 uppercase tracking-widest mb-3 flex items-center gap-2">
                            <i data-lucide="info" class="w-3.5 h-3.5"></i> Informasi Penting
                        </h4>
                        <ul class="text-[10px] text-slate-500 space-y-2 font-medium leading-relaxed">
                            <li>• Mohon simpan invoice ini sebagai bukti transaksi resmi.</li>
                            <li>• Barang yang sudah diproses produksi tidak dapat dibatalkan.</li>
                            <li>• Estimasi pengerjaan 7-14 hari kerja setelah DP diterima.</li>
                        </ul>
                    </div>
                </div>

                <div class="w-full md:w-80 space-y-4">
                    <?php 
                        $total_display = floatval($data['total_amount'] ?? 0);
                        $discount_display = floatval($data['discount_amount'] ?? 0);
                        $subtotal_display = $total_display + $discount_display;
                    ?>
                    <div class="flex justify-between items-center px-2">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Subtotal</span>
                        <span class="text-lg font-bold text-slate-700">Rp <?= number_format($subtotal_display, 0, ',', '.') ?></span>
                    </div>

                    <?php if ($discount_display > 0): ?>
                    <div class="flex justify-between items-center px-2">
                        <span class="text-xs font-bold text-amber-600 uppercase tracking-widest">Diskon</span>
                        <span class="text-lg font-bold text-amber-600">- Rp <?= number_format($discount_display, 0, ',', '.') ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="p-6 bg-emerald-600 rounded-3xl text-white shadow-xl shadow-emerald-600/20 relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] opacity-70">Total Tagihan</span>
                                <i data-lucide="wallet" class="w-4 h-4 opacity-50"></i>
                            </div>
                            <div class="text-3xl font-black tracking-tight">
                                Rp <?= number_format($total_display, 0, ',', '.') ?>
                            </div>
                            
                            <?php if ($data['payment_status'] == 'partial' && floatval($data['paid_amount']) > 0): ?>
                            <div class="mt-4 pt-4 border-t border-white/20">
                                <div class="flex justify-between items-center text-sm font-medium mb-1">
                                    <span>Telah Dibayar (DP)</span>
                                    <span>Rp <?= number_format(floatval($data['paid_amount']), 0, ',', '.') ?></span>
                                </div>
                                <div class="flex justify-between items-center text-sm font-bold">
                                    <span>Sisa Tagihan</span>
                                    <span>Rp <?= number_format($total_display - floatval($data['paid_amount']), 0, ',', '.') ?></span>
                                </div>
                            </div>
                            <?php elseif ($data['payment_status'] == 'paid'): ?>
                            <div class="mt-4 pt-4 border-t border-white/20">
                                <div class="flex justify-center items-center gap-2 text-sm font-bold text-emerald-100 bg-white/20 py-2 rounded-xl">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i> LUNAS
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <!-- Background Pattern -->
                        <div class="absolute inset-0 opacity-10 invoice-bg-dot"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Footer Bar -->
        <div class="footer-bar bg-emerald-50 p-8 border-t border-emerald-100 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-4 text-emerald-700/60">
                <div class="flex items-center gap-1.5">
                    <i data-lucide="phone" class="w-3 h-3"></i>
                    <span class="text-[10px] font-bold"><?= $company_phone ?></span>
                </div>
            </div>
            <p class="text-emerald-700/40 font-black text-[9px] uppercase tracking-[0.4em]">Thank you for your business</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto mt-8 text-center no-print pb-12">
        <p class="text-[10px] text-emerald-600/50 font-bold uppercase tracking-widest">© <?= date('Y') ?> <?= $company_name ?> • Premium Homeset Solution</p>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
