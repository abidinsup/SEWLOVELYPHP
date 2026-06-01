<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/helpers.php';
checkAdmin();

$page_title = "Status Order";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Fetch Real Data from Database
try {
    $stmt = $pdo->query("SELECT s.*, p.full_name as partner_name, p.affiliate_code 
                         FROM surveys s 
                         JOIN partners p ON s.partner_id = p.id 
                         ORDER BY s.created_at DESC");
    $surveys = $stmt->fetchAll();
} catch (PDOException $e) {
    $surveys = [];
    $error = "Error: " . $e->getMessage();
}

// getStatusBadge() and getTypeLabel() are now loaded from includes/helpers.php
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <main class="flex-1 p-4 lg:p-8 pt-20 lg:pt-8 overflow-x-hidden relative w-full max-w-full">
        <div class="space-y-6 max-w-7xl mx-auto">
            
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Status Order</h1>
                    <p class="text-slate-500 text-sm">Kelola alur kerja dan status pesanan dari pengajuan mitra</p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-2xl border border-slate-100 p-4 flex flex-col md:flex-row gap-4">
                <div class="relative flex-1">
                    <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                    <input type="text" id="searchInput" placeholder="Cari nama customer, telepon, atau mitra..." class="w-full h-10 pl-10 pr-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm" />
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="w-full overflow-x-auto scrollbar-hide -mx-4 px-4 sm:mx-0 sm:px-0">
                <div class="flex gap-2 pb-2 min-w-max">
                    <button data-filter="all" class="filter-btn px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex-shrink-0 bg-slate-900 text-white">Semua</button>
                    <button data-filter="pending" class="filter-btn px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex-shrink-0 bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">Menunggu</button>
                    <button data-filter="survey" class="filter-btn px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex-shrink-0 bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">Proses Survey</button>
                    <button data-filter="waiting_payment" class="filter-btn px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex-shrink-0 bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">Menunggu Pembayaran</button>
                    <button data-filter="production" class="filter-btn px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex-shrink-0 bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">Pengerjaan</button>
                    <button data-filter="installation" class="filter-btn px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex-shrink-0 bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">Pemasangan</button>
                    <button data-filter="done" class="filter-btn px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex-shrink-0 bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">Selesai</button>
                </div>
            </div>

            <!-- Survey List -->
            <div class="grid gap-6">
                <?php foreach($surveys as $survey): 
                    $calc_page = 'calculator.php';
                    if (($survey['calculator_type'] ?? '') === 'rs') {
                        $calc_page = 'calculator_rs.php';
                    } elseif (($survey['calculator_type'] ?? '') === 'kantor') {
                        $calc_page = 'calculator_kantor.php';
                    }
                ?>
                <div class="survey-item bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden flex flex-col group hover:shadow-md transition-all" data-status="<?php echo $survey['status']; ?>">
                    
                    <!-- Card Header -->
                    <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-xs font-bold bg-slate-100 text-slate-600 px-3 py-1 rounded-full uppercase tracking-wider flex items-center gap-1.5">
                                    <i data-lucide="tag" class="h-3.5 w-3.5"></i> <?php echo getTypeLabel($survey['calculator_type']); ?>
                                </span>
                                <?php echo getStatusBadge($survey['status']); ?>
                            </div>
                            <h3 class="text-xl font-bold text-slate-900"><?php echo $survey['customer_name']; ?></h3>
                            <div class="flex items-center gap-2 mt-1">
                                <i data-lucide="user" class="h-4 w-4 text-emerald-600"></i>
                                <span class="text-sm font-medium text-slate-600">
                                    Mitra: <span class="text-slate-900"><?php echo $survey['partner_name']; ?></span> 
                                    <span class="text-slate-400 text-xs">(<?php echo $survey['affiliate_code']; ?>)</span>
                                </span>
                            </div>
                        </div>

                        <!-- Date/Time Badge -->
                        <div class="flex items-center gap-3 bg-slate-50 px-4 py-3 rounded-2xl h-fit border border-slate-100 shrink-0">
                            <div class="text-emerald-600 bg-emerald-100/50 p-2 rounded-xl">
                                <i data-lucide="calendar" class="h-5 w-5"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Jadwal Survey</p>
                                <p class="font-bold text-slate-900">
                                    <?php echo date('d M Y', strtotime($survey['survey_date'])); ?> <span class="text-slate-400 font-normal">| <?php echo $survey['survey_time']; ?></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Details -->
                    <div class="p-6 bg-slate-50/50 grid grid-cols-1 md:grid-cols-2 gap-6 relative">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="bg-blue-100/50 text-blue-600 p-2 rounded-xl shrink-0">
                                    <i data-lucide="map-pin" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 font-bold uppercase mb-0.5">Alamat Lengkap</p>
                                    <p class="text-sm text-slate-900 font-medium leading-relaxed"><?php echo $survey['customer_address']; ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="bg-green-100/50 text-green-600 p-2 rounded-xl shrink-0">
                                    <i data-lucide="phone" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500 font-bold uppercase mb-0.5">WhatsApp Customer</p>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm text-slate-900 font-bold"><?php echo $survey['customer_phone']; ?></p>
                                        <button class="text-xs bg-green-500 text-white px-2 py-0.5 rounded-md hover:bg-green-600 transition-colors font-medium">Chat WA</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap justify-end gap-1.5 border-t md:border-t-0 border-slate-100 pt-4 md:pt-0 mt-auto">
                            <?php if($survey['status'] == 'pending'): ?>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'survey', 'Proses Survey')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #2563eb; padding: 2px 12px;">
                                    <i data-lucide="play-circle" class="h-3 w-3"></i> Mulai Survey
                                </button>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'cancelled', 'Batal')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #ef4444; padding: 2px 12px;">
                                    <i data-lucide="x-circle" class="h-3 w-3"></i> Batalkan
                                </button>
                            <?php elseif($survey['status'] == 'survey'): ?>
                                <a href="<?php echo $calc_page; ?>?survey_id=<?php echo $survey['id']; ?>" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #4f46e5; padding: 2px 12px;">
                                    <i data-lucide="calculator" class="h-3 w-3"></i> Buat Invoice
                                </a>

                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'pending', 'Menunggu Survey')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #64748b; padding: 2px 12px;">
                                    <i data-lucide="arrow-left" class="h-3 w-3"></i> Kembali
                                </button>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'cancelled', 'Batal')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #ef4444; padding: 2px 12px;">
                                    <i data-lucide="x-circle" class="h-3 w-3"></i> Batal
                                </button>
                            <?php elseif($survey['status'] == 'waiting_payment'): ?>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'production', 'Pengerjaan')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #059669; padding: 2px 12px;">
                                    <i data-lucide="check-circle" class="h-3 w-3"></i> Lanjut Pengerjaan
                                </button>
                                <a href="<?php echo $calc_page; ?>?survey_id=<?php echo $survey['id']; ?>" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #9333ea; padding: 2px 12px;">
                                    <i data-lucide="edit-3" class="h-3 w-3"></i> Revisi Invoice
                                </a>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'survey', 'Proses Survey')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #64748b; padding: 2px 12px;">
                                    <i data-lucide="arrow-left" class="h-3 w-3"></i> Kembali
                                </button>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'cancelled', 'Batal')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #ef4444; padding: 2px 12px;">
                                    <i data-lucide="x-circle" class="h-3 w-3"></i> Batal
                                </button>
                            <?php elseif($survey['status'] == 'production'): ?>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'installation', 'Pemasangan')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #ea580c; padding: 2px 12px;">
                                    <i data-lucide="truck" class="h-3 w-3"></i> Pasang
                                </button>
                                <a href="<?php echo $calc_page; ?>?survey_id=<?php echo $survey['id']; ?>" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #9333ea; padding: 2px 12px;">
                                    <i data-lucide="edit-3" class="h-3 w-3"></i> Revisi
                                </a>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'survey', 'Proses Survey')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #64748b; padding: 2px 12px;">
                                    <i data-lucide="arrow-left" class="h-3 w-3"></i> Kembali
                                </button>
                            <?php elseif($survey['status'] == 'installation'): ?>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'done', 'Selesai')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #059669; padding: 2px 12px;">
                                    <i data-lucide="check-circle" class="h-3 w-3"></i> Selesai
                                </button>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'production', 'Pengerjaan')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #64748b; padding: 2px 12px;">
                                    <i data-lucide="arrow-left" class="h-3 w-3"></i> Kembali
                                </button>
                            <?php elseif($survey['status'] == 'done'): ?>
                                <button onclick="updateSurveyStatus(<?php echo $survey['id']; ?>, 'installation', 'Pemasangan')" class="text-white rounded-md font-bold shadow-sm transition-all hover:opacity-90 flex items-center gap-1.5 text-xs uppercase tracking-wide leading-none" style="background-color: #64748b; padding: 2px 12px;">
                                    <i data-lucide="rotate-ccw" class="h-3 w-3"></i> Batal Selesai
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>
</div>

<script>
function updateSurveyStatus(surveyId, newStatus, label) {
    const isCancel = newStatus === 'cancelled';
    Swal.fire({
        title: isCancel ? 'Batalkan Survey?' : 'Konfirmasi Update Status',
        text: isCancel ? 'Survey ini akan dibatalkan dan tidak bisa dikembalikan.' : `Ubah status survey menjadi "${label}"?`,
        icon: isCancel ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: isCancel ? '#dc2626' : '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: isCancel ? 'Ya, Batalkan' : 'Ya, Update',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('survey_id', surveyId);
            formData.append('status', newStatus);

            fetch('../ajax/update_survey_status.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Terjadi kesalahan koneksi', 'error'));
        }
    });
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const surveyItems = document.querySelectorAll('.survey-item');
    const searchInput = document.getElementById('searchInput');

    let currentFilter = 'all';

    function applyFilters() {
        const searchQuery = searchInput.value.toLowerCase();
        
        surveyItems.forEach(item => {
            const status = item.getAttribute('data-status');
            const searchableText = item.textContent.toLowerCase();

            let matchFilter = (currentFilter === 'all') ? true : (status === currentFilter);
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
            filterBtns.forEach(b => {
                b.className = "filter-btn px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex-shrink-0 bg-white text-slate-600 hover:bg-slate-100 border border-slate-200";
            });
            btn.className = "filter-btn px-4 py-2 rounded-xl text-sm font-medium transition-all whitespace-nowrap flex-shrink-0 bg-slate-900 text-white";

            currentFilter = btn.getAttribute('data-filter');
            applyFilters();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            applyFilters();
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
