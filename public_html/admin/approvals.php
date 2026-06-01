<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Approval Data Mitra";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Fetch Pending Partners
try {
    $stmt = $pdo->query("
        SELECT 
            p.*, 
            u.email,
            u.created_at as registration_date
        FROM partners p
        JOIN users u ON p.user_id = u.id
        WHERE p.status = 'pending'
        ORDER BY u.created_at DESC
    ");
    $pending_partners = $stmt->fetchAll();
} catch (PDOException $e) {
    $pending_partners = [];
}
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <!-- Header Page -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/90 md:mt-0 mt-16 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Approval Data Mitra</h1>
            <div class="flex items-center gap-2">
                <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-bold">
                    <?php echo count($pending_partners); ?> Menunggu Persetujuan
                </span>
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto pb-24 md:pb-8">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <?php if (empty($pending_partners)): ?>
                <div class="bg-white p-12 rounded-[2.5rem] border border-slate-100 shadow-sm text-center">
                    <div class="w-24 h-24 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="check-circle-2" class="h-12 w-12"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-900 mb-2">Semua Data Terverifikasi</h2>
                    <p class="text-slate-500 max-w-md mx-auto">
                        Tidak ada pengajuan mitra baru yang menunggu persetujuan saat ini.
                    </p>
                    <a href="partners.php" class="inline-flex items-center gap-2 mt-8 text-emerald-600 font-bold hover:underline">
                        Lihat Data Mitra <i data-lucide="arrow-right" class="h-4 w-4"></i>
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($pending_partners as $partner): ?>
                        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-xl hover:shadow-slate-200/50 transition-all duration-300 group overflow-hidden flex flex-col">
                            <div class="p-6 flex-1">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="h-14 w-14 rounded-2xl flex items-center justify-center text-white text-xl font-bold shadow-md" style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);">
                                        <?php echo strtoupper(substr($partner['full_name'], 0, 1)); ?>
                                    </div>
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                        <?php echo date('d M Y', strtotime($partner['registration_date'])); ?>
                                    </span>
                                </div>
                                
                                <h3 class="text-lg font-bold text-slate-900 mb-1 group-hover:text-orange-600 transition-colors">
                                    <?php echo htmlspecialchars($partner['full_name']); ?>
                                </h3>
                                <p class="text-sm text-slate-500 mb-4 flex items-center gap-2">
                                    <i data-lucide="mail" class="h-3 w-3"></i> <?php echo htmlspecialchars($partner['email']); ?>
                                </p>
                                
                                <div class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-100 text-sm mb-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-400 text-xs">WhatsApp</span>
                                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($partner['whatsapp_number']); ?></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-400 text-xs">Tgl Lahir</span>
                                        <span class="font-bold text-slate-700"><?php echo $partner['birth_date'] ? date('d M Y', strtotime($partner['birth_date'])) : '-'; ?></span>
                                    </div>
                                    <div class="pt-2 border-t border-slate-200">
                                        <span class="text-slate-400 text-xs block mb-1">Alamat</span>
                                        <p class="text-slate-700 font-medium leading-relaxed line-clamp-2">
                                            <?php echo htmlspecialchars($partner['address'] ?: '-'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-4 bg-slate-50 border-t border-slate-100 flex gap-2">
                                <button onclick="handleApproval('approve', <?php echo $partner['id']; ?>, '<?php echo addslashes($partner['full_name']); ?>')" class="flex-1 h-11 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-200 transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="check" class="h-4 w-4"></i> Setujui
                                </button>
                                <button onclick="handleApproval('reject', <?php echo $partner['id']; ?>, '<?php echo addslashes($partner['full_name']); ?>')" class="flex-1 h-11 bg-white hover:bg-red-50 text-red-600 border border-red-100 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="x" class="h-4 w-4"></i> Tolak
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
function handleApproval(action, partnerId, name) {
    const title = action === 'approve' ? 'Setujui Mitra' : 'Tolak Mitra';
    const text = action === 'approve' 
        ? `Apakah Anda yakin ingin menyetujui ${name} sebagai mitra?` 
        : `Apakah Anda yakin ingin menolak pendaftaran ${name}?`;
    const confirmButtonColor = action === 'approve' ? '#059669' : '#dc2626';
    const confirmButtonText = action === 'approve' ? 'Ya, Setujui' : 'Ya, Tolak';

    Swal.fire({
        title: title,
        text: text,
        icon: action === 'approve' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: confirmButtonColor,
        cancelButtonColor: '#64748b',
        confirmButtonText: confirmButtonText,
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('partner_id', partnerId);
            formData.append('action', action);

            fetch('../ajax/manage_partner.php', {
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
</script>

<?php include '../includes/footer.php'; ?>
