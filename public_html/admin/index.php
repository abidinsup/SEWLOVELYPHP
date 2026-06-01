<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Admin Dashboard";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Fetch Real Stats from Database
try {
    // Total Mitra
    $stmt = $pdo->query("SELECT COUNT(*) FROM partners");
    $totalMitra = $stmt->fetchColumn();

    // Pending Withdrawals
    $stmt = $pdo->query("SELECT COUNT(*) FROM transactions WHERE type = 'withdraw' AND status = 'pending'");
    $pendingWithdrawals = $stmt->fetchColumn();

    // Total Omset (from invoices)
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE payment_status IN ('paid', 'partial')");
    $totalOmset = $stmt->fetchColumn();

    // Pending Surveys
    $stmt = $pdo->query("SELECT COUNT(*) FROM surveys WHERE status = 'pending'");
    $pendingSurveys = $stmt->fetchColumn();

} catch (PDOException $e) {
    $totalMitra = 0;
    $pendingWithdrawals = 0;
    $totalOmset = 0;
    $pendingSurveys = 0;
}

$stats = [
    'totalOmset' => $totalOmset,
    'pendingWithdrawals' => $pendingWithdrawals,
    'totalMitra' => $totalMitra
];

// Fetch Recent Surveys as Recent Activity (since invoices may not exist yet)
try {
    $stmt = $pdo->query("
        SELECT s.id, s.customer_name, s.calculator_type, s.status, s.created_at,
               p.full_name AS mitraName,
               COALESCE(i.total_amount, 0) AS amount,
               COALESCE(i.payment_status, 'unpaid') AS payment_status
        FROM surveys s
        JOIN partners p ON s.partner_id = p.id
        LEFT JOIN invoices i ON i.survey_id = s.id
        ORDER BY s.created_at DESC
        LIMIT 10
    ");
    $recent_transactions = [];
    foreach ($stmt->fetchAll() as $row) {
        $recent_transactions[] = [
            'id' => 'SRV-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
            'mitraName' => $row['mitraName'],
            'type' => $row['calculator_type'],
            'amount' => (float)$row['amount'],
            'status' => $row['status'] === 'done' ? 'paid' : ($row['status'] === 'cancelled' ? 'cancelled' : 'pending'),
        ];
    }
} catch (PDOException $e) {
    $recent_transactions = [];
}

// Chart Data (monthly survey counts as proxy for activity)
$months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
$chartData2025 = array_fill(0, 12, 0);
$chartData2026 = array_fill(0, 12, 0);

try {
    $stmt = $pdo->query("SELECT YEAR(created_at) AS yr, MONTH(created_at) AS mo, COUNT(*) AS cnt FROM surveys WHERE YEAR(created_at) IN (2025, 2026) GROUP BY yr, mo");
    foreach ($stmt->fetchAll() as $row) {
        $idx = (int)$row['mo'] - 1;
        if ((int)$row['yr'] === 2025) $chartData2025[$idx] = (int)$row['cnt'];
        else $chartData2026[$idx] = (int)$row['cnt'];
    }
} catch (PDOException $e) {}

$max_val = max(1, max(array_merge($chartData2025, $chartData2026))); // Normalization base for heights
?>

<div class="flex-1 flex flex-col min-h-screen w-full overflow-x-hidden">
    <!-- Padding top for mobile header -->
    <main class="flex-1 p-4 lg:p-8 pt-20 lg:pt-8 w-full max-w-full">
        <div class="space-y-8 pb-10 max-w-7xl mx-auto">
            
            <!-- Header & Export Toolbar -->
            <div class="flex flex-col xl:flex-row justify-between items-start xl:items-end gap-6">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900 whitespace-nowrap">Dashboard Admin</h1>
                    <div class="flex items-center gap-3 mt-3">
                        <div class="p-2 bg-emerald-50 rounded-xl text-emerald-600 shadow-sm border border-emerald-100">
                            <i data-lucide="trending-up" class="h-5 w-5"></i>
                        </div>
                        <p class="text-slate-500 font-medium text-sm">
                            Ringkasan performa bisnis <span class="text-slate-800 font-semibold">Sewlovely Homeset</span>
                        </p>
                    </div>
                </div>

                <!-- Export Toolbar -->
                <div class="bg-white p-2 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full xl:w-auto">
                    <div class="hidden sm:flex items-center gap-2 px-3 pl-4 text-slate-400 border-r border-slate-100 mr-1 min-h-[40px]">
                        <i data-lucide="download" class="h-4 w-4"></i>
                        <span class="text-xs font-bold uppercase tracking-wider">Export</span>
                    </div>

                    <div class="flex items-stretch sm:items-center flex-col sm:flex-row gap-2 flex-1 sm:flex-none">
                        <div class="relative group w-full sm:w-auto">
                            <select class="w-full sm:w-[130px] h-11 pl-4 pr-8 appearance-none bg-slate-50 hover:bg-slate-100 border-0 rounded-xl text-slate-700 font-medium text-sm focus:ring-2 focus:ring-emerald-500/20 focus:bg-white transition-all cursor-pointer outline-none">
                                <option value="" disabled selected>Dari Bulan</option>
                                <?php foreach($months as $idx => $m): ?>
                                    <option value="<?php echo $idx+1; ?>"><?php echo $m; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none"></i>
                        </div>

                        <div class="relative group w-full sm:w-auto">
                            <select class="w-full sm:w-[130px] h-11 pl-4 pr-8 appearance-none bg-slate-50 hover:bg-slate-100 border-0 rounded-xl text-slate-700 font-medium text-sm focus:ring-2 focus:ring-emerald-500/20 focus:bg-white transition-all cursor-pointer outline-none">
                                <option value="" selected>Sampai (Ops)</option>
                                <?php foreach($months as $idx => $m): ?>
                                    <option value="<?php echo $idx+1; ?>"><?php echo $m; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none"></i>
                        </div>

                        <div class="relative group w-full sm:w-auto">
                            <select class="w-full sm:w-[100px] h-11 pl-4 pr-8 appearance-none bg-slate-50 hover:bg-slate-100 border-0 rounded-xl text-slate-700 font-medium text-sm focus:ring-2 focus:ring-emerald-500/20 focus:bg-white transition-all cursor-pointer outline-none">
                                <option value="2026" selected>2026</option>
                                <option value="2025">2025</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400 pointer-events-none"></i>
                        </div>
                    </div>
                    
                    <div class="w-full sm:w-px h-px sm:h-8 bg-slate-100 mx-1"></div>

                    <div class="flex items-center gap-2 flex-1 sm:flex-none w-full sm:w-auto">
                        <button class="flex-1 sm:flex-none h-11 px-4 flex items-center justify-center gap-2 bg-[#63e5ff] hover:bg-cyan-400 text-slate-900 font-bold shadow-lg shadow-cyan-400/20 border-0 rounded-xl transition-all">
                            <i data-lucide="file-spreadsheet" class="h-4 w-4"></i> Export
                        </button>
                        <button class="flex-1 sm:flex-none h-11 px-4 flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-bold shadow-lg shadow-amber-500/20 border-0 rounded-xl transition-all">
                            <i data-lucide="download" class="h-4 w-4"></i> Komisi
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1: Omset -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                        <i data-lucide="trending-up" class="h-24 w-24 text-emerald-600"></i>
                    </div>
                    <div class="relative z-10">
                        <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 mb-4">
                            <i data-lucide="trending-up" class="h-6 w-6"></i>
                        </div>
                        <p class="text-slate-500 text-sm font-medium mb-1">Total Omset (Semua)</p>
                        <h3 class="text-2xl font-bold text-slate-900">Rp <?php echo number_format($stats['totalOmset'], 0, ',', '.'); ?></h3>
                        <div class="flex items-center gap-1 text-emerald-600 text-xs font-bold mt-2">
                            <i data-lucide="arrow-up-right" class="h-3 w-3"></i>
                            <span>Update Realtime</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Penarikan Pending -->
                <a href="withdrawals.php" class="block h-full">
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group hover:border-amber-200 transition-colors cursor-pointer h-full">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i data-lucide="wallet" class="h-24 w-24 text-amber-500"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-500 mb-4">
                                <i data-lucide="wallet" class="h-6 w-6"></i>
                            </div>
                            <p class="text-slate-500 text-sm font-medium mb-1">Permintaan Withdrawal</p>
                            <h3 class="text-2xl font-bold text-slate-900"><?php echo $stats['pendingWithdrawals']; ?> Request</h3>
                            <div class="flex items-center gap-1 text-amber-600 text-xs font-bold mt-2">
                                <span>Perlu persetujuan Anda</span>
                                <i data-lucide="arrow-right" class="h-3 w-3 group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Card 3: Mitra -->
                <a href="partners.php" class="block h-full">
                    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm relative overflow-hidden group hover:border-blue-200 transition-colors cursor-pointer h-full">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i data-lucide="users" class="h-24 w-24 text-blue-600"></i>
                        </div>
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 mb-4">
                                <i data-lucide="users" class="h-6 w-6"></i>
                            </div>
                            <p class="text-slate-500 text-sm font-medium mb-1">Total Mitra Aktif</p>
                            <h3 class="text-2xl font-bold text-slate-900"><?php echo $stats['totalMitra']; ?> Mitra</h3>
                            <div class="flex items-center gap-1 text-blue-600 text-xs font-bold mt-2">
                                <i data-lucide="arrow-up-right" class="h-3 w-3 group-hover:translate-x-1 transition-transform"></i>
                                <span>Terdaftar</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- COMPARISON CHART -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 overflow-hidden">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4">
                    <div>
                        <h3 class="font-bold text-slate-900 text-lg">Perbandingan Penjualan Tahunan</h3>
                        <p class="text-sm text-slate-500">Omset 2025 vs 2026</p>
                    </div>
                    <div class="flex items-center gap-4 text-sm font-medium">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-md bg-blue-500"></span>
                            <span class="text-slate-600">2025</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-md bg-emerald-500"></span>
                            <span class="text-slate-600">2026</span>
                        </div>
                    </div>
                </div>

                <div class="h-72 w-full flex items-end justify-between gap-1 md:gap-4">
                    <?php foreach($months as $idx => $m): 
                        $val25 = $chartData2025[$idx];
                        $val26 = $chartData2026[$idx];
                        $h25 = ($val25 / $max_val) * 100;
                        $h26 = ($val26 / $max_val) * 100;
                        if($h25 == 0) $h25 = 1; // min height
                        if($h26 == 0) $h26 = 1;
                    ?>
                    <div class="flex-1 flex flex-col items-center gap-2 group relative h-full justify-end pb-6">
                        <!-- Tooltip -->
                        <div class="absolute bottom-full mb-2 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900 text-white text-xs py-2 px-3 rounded-lg pointer-events-none whitespace-nowrap z-20 shadow-xl border border-slate-700">
                            <div class="font-bold text-slate-300 mb-1 border-b border-slate-700 pb-1"><?php echo $m; ?></div>
                            <div class="flex justify-between gap-4">
                                <span class="text-blue-400">2025:</span>
                                <span>Rp <?php echo number_format($val25*1000000, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between gap-4">
                                <span class="text-emerald-400">2026:</span>
                                <span>Rp <?php echo number_format($val26*1000000, 0, ',', '.'); ?></span>
                            </div>
                        </div>

                        <div class="flex items-end gap-[2px] md:gap-1 w-full justify-center h-full">
                            <div class="w-1.5 md:w-4 bg-blue-200 rounded-t-sm md:rounded-t-md relative overflow-hidden transition-all duration-500 group-hover:bg-blue-300" style="height: <?php echo $h25; ?>%">
                                <div class="absolute bottom-0 left-0 right-0 bg-blue-500 transition-all duration-500" style="height: 100%"></div>
                            </div>
                            <div class="w-1.5 md:w-4 bg-emerald-200 rounded-t-sm md:rounded-t-md relative overflow-hidden transition-all duration-500 group-hover:bg-emerald-300" style="height: <?php echo $h26; ?>%">
                                <div class="absolute bottom-0 left-0 right-0 bg-emerald-500 transition-all duration-500" style="height: 100%"></div>
                            </div>
                        </div>
                        <span class="absolute bottom-0 text-[10px] md:text-xs text-slate-400 font-medium"><?php echo $m; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recent Activity Table -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900">Transaksi Terbaru</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider">
                            <tr>
                                <th class="p-4 rounded-tl-lg">ID Invoice</th>
                                <th class="p-4">Mitra</th>
                                <th class="p-4">Tipe</th>
                                <th class="p-4">Total</th>
                                <th class="p-4 text-center rounded-tr-lg">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php foreach($recent_transactions as $inv): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="p-4 font-bold text-slate-900"><?php echo $inv['id']; ?></td>
                                <td class="p-4 text-slate-600"><?php echo $inv['mitraName']; ?></td>
                                <td class="p-4 text-slate-600 capitalize"><?php echo $inv['type']; ?></td>
                                <td class="p-4 font-bold text-slate-900">Rp <?php echo number_format($inv['amount'], 0, ',', '.'); ?></td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-bold capitalize <?php echo $inv['status'] == 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                                        <?php echo $inv['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
