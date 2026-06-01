<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/helpers.php';
checkMitra();

$page_title = "Riwayat Survey";
include '../includes/header.php';
include '../includes/sidebar_mitra.php';

// Fetch Surveys for this Mitra
$stmt_partner = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
$stmt_partner->execute([$_SESSION['user_id']]);
$partner = $stmt_partner->fetch();
$partner_id = $partner ? $partner['id'] : 0;

$stmt_surveys = $pdo->prepare("SELECT * FROM surveys WHERE partner_id = ? ORDER BY created_at DESC");
$stmt_surveys->execute([$partner_id]);
$surveys = $stmt_surveys->fetchAll();

// getStatusBadge() is now loaded from includes/helpers.php
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out">
    <main class="flex-1 p-4 md:p-8 overflow-y-auto pb-24 md:pb-8 w-full max-w-full">
        <div class="space-y-6 max-w-7xl mx-auto pt-6">
            
            <!-- Header -->
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900">Riwayat Survey</h1>
                <p class="text-slate-500 text-xs md:text-sm mt-1">Pantau status pengajuan survey Anda di sini</p>
            </div>

            <!-- Main Unified Card -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <!-- Search Header -->
                <div class="p-3 md:p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-stretch md:items-center gap-3 md:gap-4">
                    <h3 class="font-bold text-slate-900 hidden md:block">List Pengajuan Survey</h3>
                    <div class="flex items-center gap-2 w-full md:max-w-md">
                        <div class="relative flex-1">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                            <input type="text" id="searchInput" placeholder="Cari nama atau no. WA..." class="pl-10 bg-slate-50 border border-slate-200 text-sm h-10 w-full focus:bg-white transition-colors rounded-xl font-medium px-3 outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" />
                        </div>
                        <button id="searchBtn" class="bg-emerald-600 text-white font-bold h-10 px-4 rounded-xl hover:bg-emerald-700 shadow-sm shadow-emerald-200 transition-all active:scale-[0.98] shrink-0">Cari</button>
                    </div>
                </div>

                <!-- Tabs Slider -->
                <div class="bg-slate-50/50 border-b border-slate-100 px-3 pt-3 overflow-x-auto scrollbar-hide">
                    <div class="flex gap-2 pb-3 min-w-max">
                        <button data-filter="all" class="filter-btn px-4 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all duration-300 border flex-shrink-0 bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-100">Semua</button>
                        <button data-filter="pending" class="filter-btn px-4 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all duration-300 border flex-shrink-0 bg-white text-slate-500 border-slate-100 hover:bg-slate-50">Menunggu</button>
                        <button data-filter="survey_process" class="filter-btn px-4 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all duration-300 border flex-shrink-0 bg-white text-slate-500 border-slate-100 hover:bg-slate-50">Dalam Proses</button>
                        <button data-filter="done" class="filter-btn px-4 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all duration-300 border flex-shrink-0 bg-white text-slate-500 border-slate-100 hover:bg-slate-50">Selesai</button>
                    </div>
                </div>

                <!-- Mobile Card Layout -->
                <div class="md:hidden p-3 space-y-3 bg-slate-50/30">
                    <?php if (count($surveys) == 0): ?>
                        <div class="text-center p-6 bg-white rounded-xl border border-slate-100">
                            <i data-lucide="inbox" class="h-10 w-10 text-slate-300 mx-auto mb-3"></i>
                            <p class="text-slate-500 text-sm font-medium">Belum ada riwayat survey.</p>
                        </div>
                    <?php else: ?>
                    <?php foreach($surveys as $survey): ?>
                        <div class="survey-item bg-white p-3.5 rounded-xl border border-slate-100 shadow-sm hover:shadow-md transition-all cursor-pointer" data-status="<?php echo $survey['status']; ?>">
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold text-slate-900 text-[15px] leading-tight truncate"><?php echo $survey['customer_name']; ?></p>
                                    <p class="text-[10px] text-slate-400 font-medium mt-0.5"><?php echo $survey['customer_phone']; ?></p>
                                </div>
                                <div class="shrink-0 scale-90 origin-top-right">
                                    <?php echo getStatusBadge($survey['status']); ?>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1 pt-2 border-t border-slate-50">
                                <div class="flex items-center gap-2 text-slate-500">
                                    <i data-lucide="map-pin" class="h-3 w-3 flex-shrink-0 text-slate-400"></i>
                                    <span class="text-[11px] text-slate-600 line-clamp-1"><?php echo $survey['customer_address']; ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-500">
                                    <i data-lucide="calendar" class="h-3 w-3 flex-shrink-0 text-emerald-500"></i>
                                    <span class="text-[11px] font-bold text-slate-700"><?php echo date('d M Y', strtotime($survey['survey_date'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Desktop Table Layout -->
                <div class="hidden md:block overflow-x-auto custom-scrollbar pb-2 px-1">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-slate-400 text-sm uppercase font-bold tracking-wider">
                            <tr>
                                <th class="p-4 whitespace-nowrap min-w-[140px]">Customer</th>
                                <th class="p-4 whitespace-nowrap min-w-[200px]">Alamat / Lokasi</th>
                                <th class="p-4 whitespace-nowrap min-w-[120px]">Tanggal Survey</th>
                                <th class="p-4 text-center whitespace-nowrap min-w-[100px]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php if (count($surveys) == 0): ?>
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <i data-lucide="inbox" class="h-12 w-12 text-slate-300 mb-3"></i>
                                            <span class="font-medium">Belum ada riwayat survey.</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                            <?php foreach($surveys as $survey): ?>
                                <tr class="survey-item hover:bg-slate-50/50 transition-colors cursor-pointer group" data-status="<?php echo $survey['status']; ?>">
                                    <td class="p-4">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-900 group-hover:text-emerald-600 transition-colors line-clamp-1 underline-offset-4 hover:underline"><?php echo $survey['customer_name']; ?></span>
                                            <span class="text-[10px] text-slate-400 font-medium"><?php echo $survey['customer_phone']; ?></span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-slate-500">
                                        <div class="flex items-start gap-1.5 min-w-[200px]">
                                            <i data-lucide="map-pin" class="h-3 w-3 flex-shrink-0 text-slate-400 mt-0.5"></i>
                                            <span class="text-xs line-clamp-2 leading-relaxed"><?php echo $survey['customer_address']; ?></span>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <div class="flex items-center gap-1.5 text-slate-600 whitespace-nowrap min-w-[120px]">
                                            <i data-lucide="calendar" class="h-3.5 w-3.5 text-slate-400"></i>
                                            <span class="font-medium text-xs"><?php echo date('d M Y', strtotime($survey['survey_date'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="min-w-[100px] flex justify-center">
                                            <?php echo getStatusBadge($survey['status']); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const surveyItems = document.querySelectorAll('.survey-item');
    const searchInput = document.getElementById('searchInput');
    const searchBtn = document.getElementById('searchBtn');

    let currentFilter = 'all';

    function applyFilters() {
        const searchQuery = searchInput.value.toLowerCase();
        
        surveyItems.forEach(item => {
            const status = item.getAttribute('data-status');
            
            // Dapatkan seluruh text dari item (Nama, No WA, Alamat, dll)
            const searchableText = item.textContent.toLowerCase();

            let matchFilter = false;
            if (currentFilter === 'all') {
                matchFilter = true;
            } else if (currentFilter === 'survey_process') {
                matchFilter = ['survey', 'waiting_payment', 'production', 'installation'].includes(status);
            } else if (currentFilter === 'done') {
                matchFilter = status === 'done';
            } else if (currentFilter === 'pending') {
                matchFilter = status === 'pending';
            }

            let matchSearch = searchableText.includes(searchQuery);

            if (matchFilter && matchSearch) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active state
            filterBtns.forEach(b => {
                b.className = "filter-btn px-4 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all duration-300 border flex-shrink-0 bg-white text-slate-500 border-slate-100 hover:bg-slate-50";
            });
            btn.className = "filter-btn px-4 py-1.5 rounded-lg text-xs font-bold whitespace-nowrap transition-all duration-300 border flex-shrink-0 bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-100";

            currentFilter = btn.getAttribute('data-filter');
            applyFilters();
        });
    });

    searchBtn.addEventListener('click', () => {
        applyFilters();
    });

    searchInput.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') {
            applyFilters();
        } else {
            // Opsional: filter otomatis saat mengetik
            applyFilters();
        }
    });
});
</script>
