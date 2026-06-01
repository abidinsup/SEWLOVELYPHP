<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Bonus Manual";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Fetch active partners for the dropdown
try {
    $stmt = $pdo->query("SELECT id, full_name, affiliate_code FROM partners WHERE status = 'approved' AND is_active = 1 ORDER BY full_name ASC");
    $partners = $stmt->fetchAll();
} catch (PDOException $e) {
    $partners = [];
}

// Fetch bonus history
try {
    $stmt = $pdo->query("
        SELECT 
            t.id,
            t.amount,
            t.description,
            t.created_at,
            p.full_name AS mitra,
            p.affiliate_code AS mitraId
        FROM transactions t
        JOIN partners p ON t.partner_id = p.id
        WHERE t.type = 'commission' AND t.description LIKE 'Bonus Manual:%' AND t.status = 'success'
        ORDER BY t.created_at DESC
        LIMIT 50
    ");
    $bonus_history = $stmt->fetchAll();
} catch (PDOException $e) {
    $bonus_history = [];
}
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <!-- Header Page -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/90 md:mt-0 mt-16 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Bonus Manual</h1>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto pb-24 md:pb-8">
        <div class="max-w-7xl mx-auto space-y-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Pemberian Bonus -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden sticky top-24">
                        <div class="p-6 border-b border-slate-100 bg-emerald-50/50">
                            <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mb-4 shadow-sm">
                                <i data-lucide="gift" class="h-6 w-6"></i>
                            </div>
                            <h2 class="text-lg font-bold text-slate-900">Form Pemberian Bonus</h2>
                            <p class="text-sm text-slate-500">Berikan bonus langsung ke saldo mitra.</p>
                        </div>
                        
                        <div class="p-6 space-y-5">
                            <form id="bonusForm" onsubmit="submitBonus(event)">
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pilih Mitra</label>
                                        <div class="relative group">
                                            <i data-lucide="user" class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                                            <select id="partner_id" required class="w-full h-12 pl-12 pr-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl transition-all text-sm font-medium text-slate-900 appearance-none">
                                                <option value="">-- Pilih Mitra --</option>
                                                <?php foreach ($partners as $p): ?>
                                                    <option value="<?php echo $p['id']; ?>">
                                                        <?php echo htmlspecialchars($p['full_name']) . ' (' . htmlspecialchars($p['affiliate_code']) . ')'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 pointer-events-none"></i>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nominal Bonus (Rp)</label>
                                        <div class="relative group">
                                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400 group-focus-within:text-emerald-500 transition-colors">Rp</span>
                                            <input type="text" id="amount" required placeholder="0" oninput="formatRupiah(this)" class="w-full h-12 pl-12 pr-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl transition-all text-lg font-bold text-slate-900" />
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Keterangan / Alasan</label>
                                        <div class="relative group">
                                            <i data-lucide="message-square" class="absolute left-4 top-4 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                                            <textarea id="description" required rows="3" placeholder="Contoh: Pencapaian target bulan ini" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl transition-all text-sm font-medium text-slate-900 resize-none"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-6">
                                    <button type="submit" class="w-full h-12 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-lg shadow-emerald-600/30 transition-all hover:-translate-y-1 flex items-center justify-center gap-2">
                                        <i data-lucide="send" class="h-4 w-4"></i> Kirim Bonus
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Bonus -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden h-full flex flex-col">
                        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-50/50">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Riwayat Pemberian Bonus</h2>
                                <p class="text-sm text-slate-500">Daftar bonus manual yang telah diberikan ke mitra.</p>
                            </div>
                            <div class="relative w-full sm:w-64">
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                                <input type="text" id="searchHistory" onkeyup="filterHistory()" placeholder="Cari nama atau keterangan..." class="w-full h-10 pl-9 pr-4 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400 text-sm" />
                            </div>
                        </div>

                        <div class="flex-1 overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50/80 text-slate-500 text-[10px] uppercase font-black tracking-wider border-b border-slate-100">
                                    <tr>
                                        <th class="p-4 whitespace-nowrap">Tanggal</th>
                                        <th class="p-4">Mitra</th>
                                        <th class="p-4">Keterangan</th>
                                        <th class="p-4 text-right">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-sm" id="historyTableBody">
                                    <?php if (empty($bonus_history)): ?>
                                        <tr>
                                            <td colspan="4" class="p-12 text-center text-slate-400">
                                                <div class="flex flex-col items-center justify-center">
                                                    <i data-lucide="history" class="h-12 w-12 text-slate-300 mb-3"></i>
                                                    <p>Belum ada riwayat bonus.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($bonus_history as $hist): ?>
                                            <tr class="hover:bg-slate-50/50 transition-colors history-row" data-search="<?php echo strtolower($hist['mitra'] . ' ' . $hist['description']); ?>">
                                                <td class="p-4 whitespace-nowrap text-slate-500 text-xs">
                                                    <?php echo date('d M Y, H:i', strtotime($hist['created_at'])); ?>
                                                </td>
                                                <td class="p-4">
                                                    <div class="flex items-center gap-3">
                                                        <div class="h-8 w-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                            <?php echo strtoupper(substr($hist['mitra'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <p class="font-bold text-slate-900"><?php echo htmlspecialchars($hist['mitra']); ?></p>
                                                            <p class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($hist['mitraId']); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-4">
                                                    <p class="text-slate-700 font-medium text-xs leading-relaxed max-w-sm">
                                                        <?php echo htmlspecialchars(str_replace('Bonus Manual: ', '', $hist['description'])); ?>
                                                    </p>
                                                </td>
                                                <td class="p-4 text-right font-black text-emerald-600">
                                                    +Rp <?php echo number_format($hist['amount'], 0, ',', '.'); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

<script>
function formatRupiah(input) {
    let value = input.value.replace(/[^,\d]/g, '').toString();
    let split = value.split(',');
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    input.value = rupiah;
}

function submitBonus(e) {
    e.preventDefault();

    const partnerId = document.getElementById('partner_id').value;
    const amount = document.getElementById('amount').value;
    const description = document.getElementById('description').value;

    if (!partnerId || !amount || !description) {
        Swal.fire('Peringatan', 'Mohon lengkapi semua data.', 'warning');
        return;
    }

    const partnerName = document.getElementById('partner_id').options[document.getElementById('partner_id').selectedIndex].text;

    Swal.fire({
        title: 'Konfirmasi Bonus',
        html: `Apakah Anda yakin ingin memberikan bonus sebesar <strong class="text-emerald-600">Rp ${amount}</strong> kepada <strong>${partnerName}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Kirim Bonus',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('partner_id', partnerId);
            formData.append('amount', amount);
            formData.append('description', description);

            fetch('../ajax/submit_bonus.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: data.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Terjadi kesalahan koneksi', 'error');
            });
        }
    });
}

function filterHistory() {
    const searchTerm = document.getElementById('searchHistory').value.toLowerCase();
    const rows = document.querySelectorAll('.history-row');
    
    rows.forEach(row => {
        const text = row.dataset.search;
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
