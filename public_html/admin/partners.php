<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Data Mitra";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Fetch Real Data from Database
try {
    $stmt = $pdo->query("
        SELECT 
            p.id,
            p.full_name AS name,
            u.email,
            p.whatsapp_number AS phone,
            p.created_at AS joinDate,
            p.affiliate_code AS affiliateCode,
            p.commission_percentage AS commissionPercentage,
            p.bank_name AS bankName,
            p.account_number AS accountNumber,
            p.account_holder AS accountHolder,
            p.is_active,
            p.status,
            COALESCE(SUM(CASE WHEN t.type = 'commission' AND t.status = 'success' THEN t.amount ELSE 0 END), 0) AS totalCommission,
            COALESCE(SUM(CASE WHEN t.type = 'withdraw' AND t.status = 'success' THEN t.amount ELSE 0 END), 0) AS totalWithdrawn,
            COALESCE(SUM(CASE WHEN t.type = 'withdraw' AND t.status = 'pending' THEN t.amount ELSE 0 END), 0) AS pendingWithdrawal
        FROM partners p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN transactions t ON t.partner_id = p.id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $partners_raw = $stmt->fetchAll();

    // Format data
    $partners = [];
    foreach ($partners_raw as $p) {
        $partners[] = [
            'id' => 'PTR-' . str_pad($p['id'], 3, '0', STR_PAD_LEFT),
            'db_id' => $p['id'],
            'name' => $p['name'],
            'email' => $p['email'],
            'phone' => $p['phone'],
            'joinDate' => date('d M Y', strtotime($p['joinDate'])),
            'totalSales' => 0,
            'status' => $p['status'],
            'is_active' => $p['is_active'],
            'affiliateCode' => $p['affiliateCode'],
            'commissionPercentage' => $p['commissionPercentage'] ?: 5,
            'bankName' => $p['bankName'] ?: '-',
            'accountNumber' => $p['accountNumber'] ?: '-',
            'accountHolder' => $p['accountHolder'] ?: '-',
            'totalCommission' => (float)$p['totalCommission'],
            'totalWithdrawn' => (float)$p['totalWithdrawn'],
            'availableBalance' => (float)$p['totalCommission'] - (float)$p['totalWithdrawn'],
            'pendingWithdrawal' => (float)$p['pendingWithdrawal'],
        ];
    }
} catch (PDOException $e) {
    $partners = [];
}
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <main class="flex-1 p-4 lg:p-8 pt-20 lg:pt-8 overflow-x-hidden relative w-full max-w-full">
        <div class="space-y-6 max-w-7xl mx-auto">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Data Mitra</h1>
                <p class="text-slate-500">Daftar semua mitra agen dan reseller Sewlovely</p>
            </div>

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <h3 class="font-bold text-slate-900 hidden md:block">List Mitra</h3>
                    <div class="flex items-center gap-2 max-w-md w-full">
                        <div class="relative flex-1">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                            <input type="text" placeholder="Cari nama atau ID..." class="w-full h-10 pl-10 pr-4 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400">
                        </div>
                        <button class="bg-[#63e5ff] text-slate-900 font-bold h-10 px-4 rounded-xl hover:bg-cyan-400 shadow-sm shadow-cyan-400/20 transition-all shrink-0">
                            Cari
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-bold tracking-wider">
                            <tr>
                                <th class="p-4 rounded-tl-lg">ID Mitra</th>
                                <th class="p-4">Nama Lengkap</th>
                                <th class="p-4">Tanggal Gabung</th>
                                <th class="p-4">Total Penjualan</th>
                                <th class="p-4 text-center">Status</th>
                                <th class="p-4 text-right rounded-tr-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php foreach($partners as $partner): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="p-4 font-mono text-xs text-slate-500"><?php echo $partner['id']; ?></td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3 cursor-pointer group" onclick="openDetailModal('<?php echo htmlspecialchars(json_encode($partner)); ?>')">
                                        <div class="h-8 w-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-xs group-hover:bg-blue-100 transition-colors">
                                            <?php echo substr($partner['name'], 0, 1); ?>
                                        </div>
                                        <span class="font-bold text-slate-900 group-hover:text-blue-600 transition-colors underline-offset-4 group-hover:underline">
                                            <?php echo $partner['name']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-600"><?php echo $partner['joinDate']; ?></td>
                                <td class="p-4 font-bold text-emerald-600">Rp <?php echo number_format($partner['totalSales'], 0, ',', '.'); ?></td>
                                <td class="p-4 text-center">
                                    <?php if($partner['status'] == 'approved'): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-bold capitalize bg-emerald-100 text-emerald-700">Aktif</span>
                                    <?php elseif($partner['status'] == 'pending'): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-bold capitalize bg-orange-100 text-orange-700">Pending</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-bold capitalize bg-red-100 text-red-700">Ditolak</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button onclick="openDetailModal('<?php echo htmlspecialchars(json_encode($partner)); ?>')" class="p-2 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Detail Mitra">
                                            <i data-lucide="users" class="h-4 w-4"></i>
                                        </button>
                                        <button onclick="openResetPasswordModal(<?php echo $partner['db_id']; ?>, '<?php echo addslashes($partner['name']); ?>')" class="p-2 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Reset Password">
                                            <i data-lucide="key" class="h-4 w-4"></i>
                                        </button>
                                        <?php if($partner['status'] == 'Active'): ?>
                                            <button onclick="managePartner('deactivate', <?php echo $partner['db_id']; ?>, '<?php echo addslashes($partner['name']); ?>')" class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors" title="Nonaktifkan Mitra">
                                                <i data-lucide="ban" class="h-4 w-4"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="managePartner('activate', <?php echo $partner['db_id']; ?>, '<?php echo addslashes($partner['name']); ?>')" class="p-2 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors" title="Aktifkan Mitra">
                                                <i data-lucide="check-circle" class="h-4 w-4"></i>
                                            </button>
                                            <button onclick="managePartner('delete', <?php echo $partner['db_id']; ?>, '<?php echo addslashes($partner['name']); ?>')" class="p-2 text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus Permanen">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
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

<!-- Modal Container -->
<div id="modalOverlay" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden overflow-y-auto py-10">
    
    <!-- Detail Modal -->
    <div id="detailModal" class="hidden bg-white rounded-3xl shadow-lg border border-slate-100 max-w-2xl w-[90%] md:w-full my-auto animate-in zoom-in duration-300">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white rounded-t-3xl z-10">
            <div>
                <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                    Detail Mitra 
                    <span id="detailStatus" class="px-2 py-0.5 rounded-full text-xs font-bold capitalize ml-2"></span>
                </h2>
                <p class="text-sm text-slate-500 mt-1" id="detailJoinDate"></p>
            </div>
            <button onclick="closeModals()" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[70vh] overflow-y-auto">
            <!-- Personal Info -->
            <div class="space-y-4">
                <h4 class="font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="users" class="h-4 w-4 text-emerald-600"></i> Informasi Pribadi
                </h4>
                <div class="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-100 text-sm">
                    <div>
                        <p class="text-slate-400 text-[10px] uppercase font-bold">Nama Lengkap</p>
                        <p class="font-medium text-slate-900" id="detailName">-</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[10px] uppercase font-bold">Email</p>
                        <p class="font-medium text-slate-900" id="detailEmail">-</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[10px] uppercase font-bold">No. WhatsApp</p>
                        <p class="font-medium text-slate-900" id="detailPhone">-</p>
                    </div>
                </div>
            </div>

            <!-- Affiliate Info -->
            <div class="space-y-4">
                <h4 class="font-bold text-slate-900 flex items-center gap-2">Informasi Afiliasi</h4>
                <div class="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-100 text-sm h-full">
                    <div>
                        <p class="text-slate-400 text-[10px] uppercase font-bold">Kode Afiliasi</p>
                        <div class="flex flex-col items-start gap-1 mt-1">
                            <p class="font-mono font-bold text-emerald-600 bg-emerald-50 inline-block px-2 py-1 rounded" id="detailCode">-</p>
                            <p class="text-[10px] text-slate-500 font-medium">
                                Komisi: <span class="text-emerald-600 font-bold" id="detailCommPct">5%</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bank Info -->
            <div class="space-y-4 md:col-span-2">
                <h4 class="font-bold text-slate-900 flex items-center gap-2">Rekening Bank</h4>
                <div class="bg-white p-4 rounded-xl border border-slate-200 text-sm shadow-sm grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-slate-400 text-[10px] uppercase font-bold">Nama Bank</p>
                        <p class="font-medium text-slate-900" id="detailBank">-</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[10px] uppercase font-bold">No. Rekening</p>
                        <p class="font-mono font-bold text-slate-900 text-lg" id="detailAccount">-</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-[10px] uppercase font-bold">Atas Nama</p>
                        <p class="font-medium text-slate-900" id="detailHolder">-</p>
                    </div>
                </div>
            </div>

            <!-- Financial Info -->
            <div class="space-y-4 md:col-span-2">
                <h4 class="font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="wallet" class="h-4 w-4 text-emerald-600"></i> Informasi Keuangan & Komisi
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100">
                        <p class="text-emerald-600 text-[10px] uppercase font-bold tracking-wider mb-1">Total Komisi</p>
                        <p class="font-bold text-lg text-emerald-900" id="detailTotalComm">Rp 0</p>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-xl border border-orange-100">
                        <p class="text-orange-600 text-[10px] uppercase font-bold tracking-wider mb-1">Sudah Ditarik</p>
                        <p class="font-bold text-lg text-orange-900" id="detailWithdrawn">Rp 0</p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                        <p class="text-blue-600 text-[10px] uppercase font-bold tracking-wider mb-1">Saldo Saat Ini</p>
                        <p class="font-bold text-lg text-blue-900" id="detailBalance">Rp 0</p>
                    </div>
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <p class="text-slate-500 text-[10px] uppercase font-bold tracking-wider mb-1">Penarikan Pending</p>
                        <p class="font-bold text-lg text-slate-700" id="detailPending">Rp 0</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="p-6 border-t border-slate-100 flex justify-end sticky bottom-0 bg-white rounded-b-3xl z-10">
            <button onclick="closeModals()" class="px-6 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-colors">Tutup</button>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetModal" class="hidden bg-white p-6 rounded-3xl shadow-lg border border-slate-100 max-w-sm w-[90%] md:w-full my-auto animate-in zoom-in duration-300">
        <h2 class="text-xl font-bold text-emerald-600 flex items-center gap-2 mb-2">
            <i data-lucide="key" class="h-5 w-5"></i> Ganti Password Mitra
        </h2>
        <p class="text-sm text-slate-500 mb-6">
            Anda akan mengganti password untuk mitra <strong id="resetName"></strong>. Kirimkan password baru ini kepada mitra setelah berhasil diupdate.
        </p>
        <div class="space-y-2 mb-6">
            <label class="text-sm font-bold text-slate-700">Password Baru</label>
            <input type="text" id="resetPasswordInput" placeholder="Masukkan password baru" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500">
            <p class="text-[10px] text-slate-400">Minimal 6 karakter. Gunakan kombinasi huruf dan angka.</p>
        </div>
        <div class="flex gap-3 justify-end">
            <button onclick="closeModals()" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold rounded-xl hover:bg-slate-200 transition-colors">Batal</button>
            <button onclick="submitResetPassword()" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 transition-colors">Update Password</button>
        </div>
    </div>

</div>

<script>
const modalOverlay = document.getElementById('modalOverlay');
const detailModal = document.getElementById('detailModal');
const resetModal = document.getElementById('resetModal');

let currentResetPartnerId = null;

function closeModals() {
    modalOverlay.classList.add('hidden');
    detailModal.classList.add('hidden');
    resetModal.classList.add('hidden');
}

function openDetailModal(partnerJson) {
    const p = JSON.parse(partnerJson);
    
    document.getElementById('detailStatus').innerText = p.status === 'Active' ? 'Aktif' : 'Nonaktif';
    document.getElementById('detailStatus').className = p.status === 'Active' 
        ? "px-2 py-0.5 rounded-full text-xs font-bold capitalize ml-2 bg-emerald-100 text-emerald-700"
        : "px-2 py-0.5 rounded-full text-xs font-bold capitalize ml-2 bg-slate-100 text-slate-500";
    
    document.getElementById('detailJoinDate').innerText = 'Bergabung sejak ' + p.joinDate;
    document.getElementById('detailName').innerText = p.name;
    document.getElementById('detailEmail').innerText = p.email;
    document.getElementById('detailPhone').innerText = p.phone;
    
    document.getElementById('detailCode').innerText = p.affiliateCode;
    document.getElementById('detailCommPct').innerText = p.commissionPercentage + '%';
    
    document.getElementById('detailBank').innerText = p.bankName;
    document.getElementById('detailAccount').innerText = p.accountNumber;
    document.getElementById('detailHolder').innerText = p.accountHolder;

    const formatter = new Intl.NumberFormat('id-ID');
    document.getElementById('detailTotalComm').innerText = 'Rp ' + formatter.format(p.totalCommission);
    document.getElementById('detailWithdrawn').innerText = 'Rp ' + formatter.format(p.totalWithdrawn);
    document.getElementById('detailBalance').innerText = 'Rp ' + formatter.format(p.availableBalance);
    document.getElementById('detailPending').innerText = 'Rp ' + formatter.format(p.pendingWithdrawal);

    modalOverlay.classList.remove('hidden');
    detailModal.classList.remove('hidden');
}

// --- Real AJAX: Manage Partner (activate/deactivate/delete) ---
function managePartner(action, partnerId, name) {
    let title, text, icon, confirmColor, confirmText;

    if (action === 'delete') {
        title = 'Hapus Mitra';
        text = `Hapus data mitra ${name} secara permanen? Data tidak dapat dikembalikan!`;
        icon = 'warning';
        confirmColor = '#dc2626';
        confirmText = 'Ya, Hapus Permanen';
    } else if (action === 'activate') {
        title = 'Aktifkan Mitra';
        text = `Aktifkan kembali mitra ${name}?`;
        icon = 'question';
        confirmColor = '#059669';
        confirmText = 'Ya, Aktifkan';
    } else {
        title = 'Nonaktifkan Mitra';
        text = `Nonaktifkan mitra ${name}? Mitra tidak akan bisa mengakses akun.`;
        icon = 'warning';
        confirmColor = '#d97706';
        confirmText = 'Ya, Nonaktifkan';
    }

    Swal.fire({
        title, text, icon,
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#64748b',
        confirmButtonText: confirmText,
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('partner_id', partnerId);
            formData.append('action', action);

            fetch('../ajax/manage_partner.php', { method: 'POST', body: formData })
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

// --- Real AJAX: Reset Password ---
function openResetPasswordModal(partnerId, name) {
    currentResetPartnerId = partnerId;
    document.getElementById('resetName').innerText = name;
    document.getElementById('resetPasswordInput').value = '';
    modalOverlay.classList.remove('hidden');
    resetModal.classList.remove('hidden');
}

function submitResetPassword() {
    const newPassword = document.getElementById('resetPasswordInput').value.trim();
    if (newPassword.length < 6) {
        Swal.fire('Gagal', 'Password minimal 6 karakter', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('partner_id', currentResetPartnerId);
    formData.append('action', 'reset_password');
    formData.append('new_password', newPassword);

    fetch('../ajax/manage_partner.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        closeModals();
        if (data.success) {
            Swal.fire('Berhasil!', data.message, 'success');
        } else {
            Swal.fire('Gagal', data.message, 'error');
        }
    })
    .catch(() => { closeModals(); Swal.fire('Error', 'Terjadi kesalahan koneksi', 'error'); });
}
</script>

<?php include '../includes/footer.php'; ?>
