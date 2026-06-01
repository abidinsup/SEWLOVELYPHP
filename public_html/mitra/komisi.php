<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkMitra();

$page_title = "Komisi & Saldo";
include '../includes/header.php';
include '../includes/sidebar_mitra.php';

// Fetch Real Commission Data from Database
$stats = ['balance' => 0, 'totalEarned' => 0, 'totalWithdrawn' => 0, 'pendingWithdrawal' => 0];
$transactions = [];

try {
    // Get partner_id from session
    $stmt = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $partner = $stmt->fetch();
    $partner_id = $partner ? $partner['id'] : 0;

    if ($partner_id > 0) {
        // Calculate financial stats
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(CASE WHEN type = 'commission' AND status = 'success' THEN amount ELSE 0 END), 0) AS totalEarned,
                COALESCE(SUM(CASE WHEN type = 'withdraw' AND status = 'success' THEN amount ELSE 0 END), 0) AS totalWithdrawn,
                COALESCE(SUM(CASE WHEN type = 'withdraw' AND status = 'pending' THEN amount ELSE 0 END), 0) AS pendingWithdrawal
            FROM transactions
            WHERE partner_id = ?
        ");
        $stmt->execute([$partner_id]);
        $row = $stmt->fetch();

        $stats = [
            'totalEarned' => (float)$row['totalEarned'],
            'totalWithdrawn' => (float)$row['totalWithdrawn'],
            'balance' => (float)$row['totalEarned'] - (float)$row['totalWithdrawn'],
            'pendingWithdrawal' => (float)$row['pendingWithdrawal'],
        ];

        // Fetch transaction history
        $stmt = $pdo->prepare("
            SELECT type, amount, description, status, created_at
            FROM transactions
            WHERE partner_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$partner_id]);
        $transactions = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Keep defaults
}

function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50 pb-24">
    <!-- Header (Desktop Optimized) -->
    <div class="bg-white px-4 sm:px-6 lg:px-8 pt-6 pb-8 rounded-b-[2.5rem] shadow-sm border-b border-slate-100 relative z-10 md:hidden print:hidden mt-16 md:mt-0">
        <div class="max-w-7xl mx-auto">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Komisi & Saldo</h1>
                <p class="text-sm text-slate-500">Kelola pendapatan dan penarikan saldo Anda</p>
            </div>
        </div>
    </div>
    
    <div class="hidden md:block bg-white px-4 sm:px-6 lg:px-8 pt-6 pb-8 rounded-b-[2.5rem] shadow-sm border-b border-slate-100 relative z-10">
        <div class="max-w-7xl mx-auto">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Komisi & Saldo</h1>
                <p class="text-sm text-slate-500">Kelola pendapatan dan penarikan saldo Anda</p>
            </div>
        </div>
    </div>

    <!-- Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8 pb-12 w-full">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- LEFT COLUMN -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Balance Card -->
                <div class="transform transition-all duration-500 hover:scale-[1.01]">
                    <div class="bg-gradient-to-br from-emerald-600 via-teal-700 to-emerald-900 rounded-[2rem] p-6 md:p-8 text-white shadow-xl shadow-emerald-900/20 relative overflow-hidden group">
                        <!-- Background Shapes -->
                        <div class="absolute top-0 right-0 w-80 h-80 bg-white/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 group-hover:bg-white/15 transition-all duration-1000"></div>
                        <div class="absolute bottom-0 left-0 w-64 h-64 bg-emerald-400/20 rounded-full blur-3xl translate-y-1/3 -translate-x-1/3 pointer-events-none"></div>
                        
                        <div class="relative z-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-4">
                                    <p class="text-emerald-50 text-xs font-medium flex items-center gap-2 bg-white/10 px-3 py-1 rounded-full backdrop-blur-md border border-white/10">
                                        <i data-lucide="wallet" class="h-3.5 w-3.5"></i> Saldo Aktif
                                    </p>
                                    <div class="h-2 w-2 bg-green-400 rounded-full animate-pulse shadow-[0_0_10px_#4ade80]"></div>
                                </div>
                                <h2 class="text-3xl md:text-5xl font-bold tracking-tight leading-none text-white drop-shadow-sm">
                                    <?php echo formatCurrency($stats['balance']); ?>
                                </h2>
                            </div>
                            <div class="flex items-center gap-4 w-full md:w-auto min-w-[160px]">
                                <a href="withdraw.php" class="w-full">
                                    <button class="w-full h-12 px-6 bg-white text-emerald-800 hover:bg-emerald-50 font-bold text-base border-0 shadow-lg shadow-black/10 rounded-xl transition-all hover:shadow-xl hover:-translate-y-0.5 active:scale-[0.98]">
                                        Tarik Saldo
                                    </button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Transactions (Desktop) -->
                <div class="hidden lg:block bg-white rounded-[2.5rem] shadow-sm border border-slate-100 p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-emerald-50 rounded-2xl text-emerald-600">
                                <i data-lucide="history" class="h-6 w-6"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-xl text-slate-900">Riwayat Transaksi</h3>
                                <p class="text-sm text-slate-500">Aktivitas pemasukan dan penarikan terbaru</p>
                            </div>
                        </div>
                        <a href="history.php" class="text-sm font-bold text-emerald-600 hover:text-emerald-700 hover:underline transition-all">Lihat Semua Riwayat</a>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach($transactions as $item): ?>
                            <div class="flex items-center gap-6 p-5 bg-slate-50/50 border border-slate-100 hover:border-emerald-200 rounded-[1.5rem] transition-all cursor-pointer group hover:bg-white hover:shadow-md">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 transition-all <?php echo $item['type'] === 'withdraw' ? 'bg-orange-100 text-orange-600' : 'bg-blue-100 text-blue-600'; ?>">
                                    <i data-lucide="<?php echo $item['type'] === 'withdraw' ? 'wallet' : 'trending-up'; ?>" class="h-6 w-6"></i>
                                </div>
                                <div class="flex-1 min-w-0 grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                    <div class="md:col-span-2">
                                        <h4 class="font-bold text-lg text-slate-900 truncate"><?php echo $item['description']; ?></h4>
                                        <p class="text-sm text-slate-500 mt-0.5"><?php echo date('d M Y', strtotime($item['created_at'])); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="block font-bold text-lg <?php echo $item['type'] === 'withdraw' ? 'text-slate-900' : 'text-emerald-600'; ?>">
                                            <?php echo $item['type'] === 'withdraw' ? '-' : '+'; ?> <?php echo formatCurrency($item['amount']); ?>
                                        </span>
                                        <span class="inline-block mt-1 capitalize font-semibold px-2.5 py-0.5 rounded-md text-[10px] tracking-wide bg-emerald-100 text-emerald-700">Berhasil</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="lg:col-span-4 space-y-8">
                <!-- Stats Row -->
                <div class="grid grid-cols-2 lg:grid-cols-1 gap-5">
                    <div class="bg-white p-6 lg:p-8 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col lg:flex-row items-start lg:items-center gap-4 transition-all hover:shadow-md hover:border-blue-100 hover:-translate-y-1 group">
                        <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300 shadow-sm shrink-0">
                            <i data-lucide="arrow-down-left" class="h-7 w-7"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">Total Pendapatan</p>
                            <p class="font-bold text-2xl lg:text-3xl text-slate-900 tracking-tight"><?php echo formatCurrency($stats['totalEarned']); ?></p>
                        </div>
                    </div>
                    <div class="bg-white p-6 lg:p-8 rounded-[2rem] shadow-sm border border-slate-100 flex flex-col lg:flex-row items-start lg:items-center gap-4 transition-all hover:shadow-md hover:border-orange-100 hover:-translate-y-1 group">
                        <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-600 group-hover:bg-orange-600 group-hover:text-white transition-colors duration-300 shadow-sm shrink-0">
                            <i data-lucide="arrow-up-right" class="h-7 w-7"></i>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mb-1">Total Ditarik</p>
                            <p class="font-bold text-2xl lg:text-3xl text-slate-900 tracking-tight"><?php echo formatCurrency($stats['totalWithdrawn']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Mobile History -->
                <div class="lg:hidden space-y-6">
                    <div class="flex items-end justify-between px-2">
                        <h3 class="font-bold text-xl text-slate-900">Riwayat Transaksi</h3>
                        <a href="history.php" class="text-xs font-bold text-emerald-600 hover:text-emerald-500 bg-emerald-50 px-3 py-1.5 rounded-full">Lihat Semua</a>
                    </div>
                    <div class="space-y-4">
                        <?php foreach($transactions as $item): ?>
                            <div class="flex items-center gap-5 p-5 bg-white border border-slate-100 rounded-[1.5rem] shadow-sm hover:-translate-x-1 transition-all">
                                <div class="w-14 h-14 rounded-[1.2rem] flex items-center justify-center shrink-0 <?php echo $item['type'] === 'withdraw' ? 'bg-orange-50 text-orange-500' : 'bg-blue-50 text-blue-600'; ?>">
                                    <i data-lucide="<?php echo $item['type'] === 'withdraw' ? 'wallet' : 'trending-up'; ?>" class="h-6 w-6"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-start mb-1.5">
                                        <h4 class="font-bold text-base text-slate-900 truncate pr-2"><?php echo $item['description']; ?></h4>
                                        <span class="font-extrabold text-sm whitespace-nowrap <?php echo $item['type'] === 'withdraw' ? 'text-slate-900' : 'text-emerald-600'; ?>">
                                            <?php echo $item['type'] === 'withdraw' ? '-' : '+'; ?> <?php echo formatCurrency($item['amount']); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-slate-400 font-medium"><?php echo date('d M Y', strtotime($item['created_at'])); ?></span>
                                        <span class="bg-emerald-100 text-emerald-700 capitalize font-bold px-2.5 py-1 rounded-lg text-[10px] tracking-wide">Berhasil</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
