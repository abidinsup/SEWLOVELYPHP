<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Approval Poin Hadiah";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Fetch Reward Claims Data from Database
try {
    // If the table doesn't exist, this will throw an error, so we will ignore it gracefully or show empty.
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.reward_name,
            r.points_used,
            r.status,
            r.created_at,
            p.full_name AS mitra,
            p.affiliate_code AS mitraId,
            p.whatsapp_number AS wa
        FROM reward_claims r
        JOIN partners p ON r.partner_id = p.id
        ORDER BY r.created_at DESC
    ");
    $claims_raw = $stmt->fetchAll();

    $claims = [];
    foreach ($claims_raw as $c) {
        $claims[] = [
            'id' => 'RWD-' . str_pad($c['id'], 3, '0', STR_PAD_LEFT),
            'db_id' => $c['id'],
            'mitra' => $c['mitra'],
            'mitraId' => $c['mitraId'],
            'wa' => $c['wa'] ?: '-',
            'reward_name' => $c['reward_name'],
            'points_used' => (int)$c['points_used'],
            'date' => date('d M Y', strtotime($c['created_at'])),
            'raw_date' => date('Y-m-d', strtotime($c['created_at'])),
            'status' => $c['status'],
        ];
    }
} catch (PDOException $e) {
    // Table might not exist yet
    $claims = [];
}
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <main class="flex-1 p-4 lg:p-8 pt-20 lg:pt-8 overflow-x-hidden relative w-full max-w-full">
        <div class="space-y-6 max-w-7xl mx-auto">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Approval Poin Hadiah</h1>
                    <p class="text-slate-500">Kelola penukaran poin mitra dengan hadiah</p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Status Tabs -->
                    <div class="bg-slate-100 p-1 rounded-xl inline-flex overflow-x-auto scrollbar-hide">
                        <button onclick="filterTabs('pending', this)" class="tab-btn bg-white text-slate-800 shadow-sm px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap">Menunggu Approval</button>
                        <button onclick="filterTabs('approved', this)" class="tab-btn text-slate-500 hover:text-slate-700 px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap">Disetujui</button>
                        <button onclick="filterTabs('rejected', this)" class="tab-btn text-slate-500 hover:text-slate-700 px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap">Ditolak</button>
                    </div>

                    <!-- Additional Filters -->
                    <div class="flex items-center gap-2">
                        <input type="date" id="dateFilter" onchange="applyFilters()" class="h-10 px-3 bg-white border border-slate-200 rounded-lg text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer" />
                    </div>
                </div>
            </div>

            <!-- Notes / Rules Section -->
            <div class="bg-gradient-to-br from-indigo-50 to-blue-50 border border-indigo-100 rounded-3xl p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="bg-indigo-500 text-white p-3 rounded-2xl shrink-0 shadow-lg shadow-indigo-500/30 flex items-center justify-center">
                        <span class="text-2xl leading-none">💡</span>
                    </div>
                    <div class="flex-1 w-full text-slate-700 space-y-4 text-sm">
                        <h2 class="text-lg font-bold text-indigo-900 mb-2">🌟 Rekap Resmi Aturan Sistem Poin & Hadiah (Loyalty)</h2>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-white/60 p-4 rounded-2xl border border-white">
                                <h3 class="font-bold text-indigo-800 mb-2">📌 Aturan Dasar Perolehan Poin</h3>
                                <ul class="list-disc pl-5 space-y-1 text-slate-600">
                                    <li><b>1 Poin = Rp 500.000 Keuntungan Bersih</b> (Omzet dipotong modal, ongkos jahit, dan komisi).</li>
                                    <li>Sistem menggunakan pembulatan ke bawah (floor).</li>
                                </ul>
                                <div class="mt-3 text-xs bg-white p-2 rounded-xl border border-indigo-50 space-y-1">
                                    <p>• Untung Rp 500.000 ➔ <b>1 Poin</b></p>
                                    <p>• Untung Rp 1.200.000 ➔ <b>2 Poin</b></p>
                                    <p>• Untung Rp 400.000 ➔ <b>0 Poin</b></p>
                                </div>
                            </div>
                            
                            <div class="bg-white/60 p-4 rounded-2xl border border-white">
                                <h3 class="font-bold text-indigo-800 mb-2">🎁 Daftar Target Hadiah (Milestone)</h3>
                                <p class="text-xs text-slate-500 mb-2">Budget hadiah perusahaan: 10% dari keuntungan bersih.</p>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="bg-white p-2 rounded-xl border border-indigo-50">
                                        <b>Uang Tunai (Rp 150k)</b><br/>Butuh: 3 Poin<br/><span class="text-slate-400">Untung: Rp 1,5 Juta</span>
                                    </div>
                                    <div class="bg-white p-2 rounded-xl border border-indigo-50">
                                        <b>Kipas Angin/Magic Com</b><br/>Butuh: 6 Poin<br/><span class="text-slate-400">Untung: Rp 3 Juta</span>
                                    </div>
                                    <div class="bg-white p-2 rounded-xl border border-indigo-50">
                                        <b>Smart TV</b><br/>Butuh: 30 Poin<br/><span class="text-slate-400">Untung: Rp 15 Juta</span>
                                    </div>
                                    <div class="bg-white p-2 rounded-xl border border-indigo-50">
                                        <b>Sepeda Motor</b><br/>Butuh: 360 Poin<br/><span class="text-slate-400">Untung: Rp 180 Juta</span>
                                    </div>
                                    <div class="bg-white p-2 rounded-xl border border-indigo-50 col-span-2 text-center">
                                        <b>Paket Umroh</b><br/>Butuh: 650 Poin <span class="text-slate-400">(Perusahaan untung: Rp 325 Juta)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white/60 p-4 rounded-2xl border border-white mt-4">
                            <h3 class="font-bold text-indigo-800 mb-2">⚙️ Bagaimana Sistem Ini Berjalan?</h3>
                            <ul class="space-y-1 text-slate-600 list-decimal pl-4">
                                <li><b>Input Admin:</b> Saat pekerjaan beres, Admin klik "Selesai" di halaman Status Order (Surveys).</li>
                                <li><b>Kalkulasi:</b> Sistem memunculkan pop-up input nominal Keuntungan Bersih.</li>
                                <li><b>Pemberian Poin:</b> Sistem otomatis membagi dengan 500.000 dan menambahkannya ke saldo Mitra.</li>
                                <li><b>Tukar Poin:</b> Jika Mitra menukar poin dengan hadiah, saldonya berkurang sesuai harga hadiah. Untuk umroh, mereka harus fokus menabung.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <!-- Search Bar -->
                <div class="p-4 border-b border-slate-100 flex items-center gap-2">
                    <div class="relative flex-1 max-w-sm">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                        <input type="text" id="searchInput" onkeyup="applyFilters()" placeholder="Cari mitra atau ID..." class="w-full h-10 pl-9 pr-4 bg-slate-50 border-none rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-400 text-sm" />
                    </div>
                    <button onclick="applyFilters()" class="bg-[#63e5ff] hover:bg-cyan-400 text-slate-900 px-6 rounded-xl h-10 font-bold shadow-lg shadow-cyan-400/20 border-0 transition-all">
                        Cari
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider">
                            <tr>
                                <th class="p-4 rounded-tl-lg">ID & Tanggal</th>
                                <th class="p-4">Mitra & WA</th>
                                <th class="p-4">Hadiah</th>
                                <th class="p-4">Poin Dipakai</th>
                                <th class="p-4 text-center rounded-tr-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php if(count($claims) === 0): ?>
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-500">
                                        <i data-lucide="gift" class="h-12 w-12 mx-auto text-slate-300 mb-3"></i>
                                        <p>Belum ada data penukaran poin.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php foreach($claims as $item): ?>
                            <tr class="hover:bg-slate-50/50 group transaction-row" data-status="<?php echo $item['status']; ?>" data-date="<?php echo $item['raw_date']; ?>" data-search="<?php echo strtolower($item['id'] . ' ' . $item['mitra'] . ' ' . $item['mitraId'] . ' ' . $item['reward_name']); ?>" style="<?php echo $item['status'] !== 'pending' ? 'display:none;' : ''; ?>">
                                <td class="p-4">
                                    <p class="font-bold text-slate-900 truncate max-w-[100px]"><?php echo $item['id']; ?></p>
                                    <p class="text-slate-500 text-xs"><?php echo $item['date']; ?></p>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-slate-700"><?php echo htmlspecialchars($item['mitra']); ?></p>
                                    <div class="flex items-center gap-1 mt-1">
                                        <span class="text-slate-400 text-xs"><?php echo $item['mitraId']; ?></span>
                                        <span class="text-slate-300">•</span>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $item['wa']); ?>" target="_blank" class="text-emerald-500 text-xs hover:underline flex items-center gap-1">
                                            <i data-lucide="message-circle" class="w-3 h-3"></i> <?php echo htmlspecialchars($item['wa']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-lg bg-pink-50 flex items-center justify-center text-pink-500">
                                            <i data-lucide="gift" class="h-4 w-4"></i>
                                        </div>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($item['reward_name']); ?></span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-100 text-orange-700 font-bold text-sm">
                                        <i data-lucide="star" class="h-4 w-4 fill-current"></i>
                                        <?php echo $item['points_used']; ?> Poin
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if($item['status'] == 'pending'): ?>
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="processClaim('approve', <?php echo $item['db_id']; ?>, '<?php echo addslashes($item['mitra']); ?>', '<?php echo addslashes($item['reward_name']); ?>')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white flex items-center gap-1 rounded-lg font-bold text-xs transition-colors">
                                                <i data-lucide="check-circle-2" class="h-4 w-4"></i> Approve
                                            </button>
                                            <button onclick="processClaim('reject', <?php echo $item['db_id']; ?>, '<?php echo addslashes($item['mitra']); ?>', '<?php echo addslashes($item['reward_name']); ?>')" class="p-1.5 text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Tolak">
                                                <i data-lucide="x-circle" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?php echo $item['status'] == 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                                                <?php echo $item['status'] == 'approved' ? 'Disetujui' : 'Ditolak'; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
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

<script>
    let currentStatus = 'pending';

    function filterTabs(status, btnElement) {
        currentStatus = status;
        
        // Update UI Tabs
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.className = 'tab-btn text-slate-500 hover:text-slate-700 px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap';
        });
        btnElement.className = 'tab-btn bg-white text-slate-800 shadow-sm px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap';

        applyFilters();
    }

    function applyFilters() {
        const searchText = document.getElementById('searchInput').value.toLowerCase();
        const dateFilter = document.getElementById('dateFilter').value;
        const rows = document.querySelectorAll('.transaction-row');

        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            const searchData = row.getAttribute('data-search');
            const dateStr = row.getAttribute('data-date');
            
            let show = true;

            if (status !== currentStatus) show = false;
            if (searchText && !searchData.includes(searchText)) show = false;
            if (dateFilter && dateStr !== dateFilter) show = false;

            row.style.display = show ? '' : 'none';
        });
    }

    function processClaim(action, id, mitraName, rewardName) {
        let actionText = action === 'approve' ? 'menyetujui' : 'menolak';
        let confirmColor = action === 'approve' ? '#059669' : '#dc2626';

        Swal.fire({
            title: `Konfirmasi ${action === 'approve' ? 'Approval' : 'Penolakan'}`,
            html: `Apakah Anda yakin ingin <b>${actionText}</b> klaim hadiah <br/><b>${rewardName}</b> untuk mitra <b>${mitraName}</b>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#94a3b8',
            confirmButtonText: `Ya, ${action === 'approve' ? 'Approve' : 'Tolak'}!`,
            cancelButtonText: 'Batal',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('action', action);
                
                return fetch('../ajax/process_reward_claim.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error(response.statusText);
                    return response.json();
                })
                .then(data => {
                    if (!data.success) throw new Error(data.message);
                    return data;
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: result.value.message || `Klaim hadiah berhasil ${action === 'approve' ? 'disetujui' : 'ditolak'}.`,
                    icon: 'success',
                    confirmButtonColor: '#00CEC8'
                }).then(() => {
                    window.location.reload();
                });
            }
        });
    }

    // Initialize Lucide icons
    lucide.createIcons();
</script>

<?php include '../includes/footer.php'; ?>
