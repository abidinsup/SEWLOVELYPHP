<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

// Fetch Invoices
$stmt = $pdo->query("
    SELECT i.*, s.customer_name, s.status as survey_status, p.full_name as partner_name
    FROM invoices i
    JOIN surveys s ON i.survey_id = s.id
    JOIN partners p ON s.partner_id = p.id
    ORDER BY i.created_at DESC
");
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Approval Pembayaran";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}
?>

<style>
    /* Fix for DP button hover since amber-500/50 might be missing in generated CSS */
    .btn-dp-hover:hover {
        border-color: #f59e0b !important;
        background-color: #fffbeb !important;
    }
    .btn-dp-hover:hover .text-dp-hover {
        color: #b45309 !important;
    }
    
    /* Ensuring red and emerald also have fallbacks */
    .btn-red-hover:hover {
        border-color: #ef4444 !important;
        background-color: #fef2f2 !important;
    }
    .btn-red-hover:hover .text-red-hover {
        color: #b91c1c !important;
    }
    
    .btn-emerald-hover:hover {
        border-color: #10b981 !important;
        background-color: #ecfdf5 !important;
    }
    .btn-emerald-hover:hover .text-emerald-hover {
        color: #047857 !important;
    }
</style>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/90 md:mt-0 mt-16 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Approval Pembayaran & SPK</h1>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto pb-24 md:pb-8">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-sm">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Daftar Tagihan & Status</h2>
                        <p class="text-sm text-slate-500">Ubah status pembayaran untuk menerbitkan SPK ke tim produksi.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs uppercase text-slate-500 tracking-wider">
                                <th class="p-4 font-bold">No. Invoice & Tanggal</th>
                                <th class="p-4 font-bold">Pelanggan & Mitra</th>
                                <th class="p-4 font-bold">Total Tagihan</th>
                                <th class="p-4 font-bold">Status Pembayaran</th>
                                <th class="p-4 font-bold text-right">Aksi SPK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php if(count($invoices) > 0): ?>
                                <?php foreach($invoices as $inv): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="p-4">
                                            <div class="font-bold text-indigo-600"><?= htmlspecialchars($inv['invoice_number']) ?></div>
                                            <div class="text-xs text-slate-500"><?= date('d M Y, H:i', strtotime($inv['created_at'])) ?></div>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-bold text-slate-800"><?= htmlspecialchars($inv['customer_name']) ?></div>
                                            <div class="text-xs text-slate-500">Mitra: <?= htmlspecialchars($inv['partner_name']) ?></div>
                                        </td>
                                        <td class="p-4">
                                            <div class="font-black text-slate-700"><?= formatRupiah($inv['total_amount']) ?></div>
                                            <?php if($inv['payment_status'] == 'partial' && $inv['paid_amount'] > 0): ?>
                                                <div class="text-xs font-bold text-amber-600 mt-1">DP: <?= formatRupiah($inv['paid_amount']) ?></div>
                                                <div class="text-xs font-bold text-red-500">Sisa: <?= formatRupiah($inv['total_amount'] - $inv['paid_amount']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-4">
                                            <div class="flex items-center gap-2">
                                                <?php 
                                                $status_label = '🔴 Belum';
                                                $status_class = 'text-red-600 bg-red-50 border-red-100';
                                                if($inv['payment_status'] == 'partial') {
                                                    $status_label = '🟠 DP';
                                                    $status_class = 'text-amber-600 bg-amber-50 border-amber-100';
                                                } elseif($inv['payment_status'] == 'paid') {
                                                    $status_label = '🟢 Lunas';
                                                    $status_class = 'text-emerald-600 bg-emerald-50 border-emerald-100';
                                                }
                                                ?>
                                                <span class="px-3 py-1 rounded-full text-[11px] font-black border <?= $status_class ?> whitespace-nowrap shadow-sm">
                                                    <?= $status_label ?>
                                                </span>
                                                <button onclick="changeStatus(<?= $inv['id'] ?>, <?= $inv['survey_id'] ?>, '<?= $inv['payment_status'] ?>', <?= $inv['total_amount'] ?>)" 
                                                        class="ml-2 px-2 py-1 rounded-md text-[10px] font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white transition-all uppercase tracking-wider" 
                                                        title="Ubah Status">
                                                    Ubah
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <?php if($inv['payment_status'] == 'partial' || $inv['payment_status'] == 'paid'): ?>
                                                    <a href="../view_invoice.php?token=<?= $inv['secure_token'] ?>" target="_blank" 
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold transition-all shadow-sm"
                                                            title="Lihat Detail Nota">
                                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-slate-400"></i> Lihat
                                                    </a>
                                                    <button onclick="printSPK(<?= $inv['survey_id'] ?>)" 
                                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg text-xs font-bold transition-all shadow-md shadow-emerald-600/20"
                                                            title="Cetak SPK">
                                                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> Cetak
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-xs text-slate-400 font-medium italic bg-slate-50 px-3 py-1 rounded-full border border-slate-100">Menunggu Pembayaran</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-500">
                                        Belum ada data invoice/tagihan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>


<script>
    function changeStatus(invoiceId, surveyId, currentStatus, totalAmount) {
        Swal.fire({
            title: '<div class="text-xl font-black text-slate-800">Update Status Pembayaran</div>',
            html: `
                <p class="text-sm text-slate-500 mb-6">Pilih status pembayaran baru untuk pesanan ini:</p>
                <div class="grid grid-cols-1 gap-3">
                    <button onclick="handleStatusUpdate(${invoiceId}, ${surveyId}, 'unpaid', ${totalAmount})" 
                        class="btn-red-hover flex items-center justify-between px-5 py-4 rounded-2xl bg-white border-2 border-slate-100 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">🔴</span>
                            <div class="text-left">
                                <div class="text-red-hover font-bold text-slate-800 transition-colors">Belum Bayar</div>
                                <div class="text-[10px] text-slate-400">Pesanan ditangguhkan</div>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300"></i>
                    </button>

                    <button onclick="handleStatusUpdate(${invoiceId}, ${surveyId}, 'partial', ${totalAmount})" 
                        class="btn-dp-hover flex items-center justify-between px-5 py-4 rounded-2xl bg-white border-2 border-slate-100 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">🟠</span>
                            <div class="text-left">
                                <div class="text-dp-hover font-bold text-slate-800 transition-colors">DP (Down Payment)</div>
                                <div class="text-[10px] text-slate-400">Siap cetak SPK Produksi</div>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300"></i>
                    </button>

                    <button onclick="handleStatusUpdate(${invoiceId}, ${surveyId}, 'paid', ${totalAmount})" 
                        class="btn-emerald-hover flex items-center justify-between px-5 py-4 rounded-2xl bg-white border-2 border-slate-100 transition-all group">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">🟢</span>
                            <div class="text-left">
                                <div class="text-emerald-hover font-bold text-slate-800 transition-colors">Lunas</div>
                                <div class="text-[10px] text-slate-400">Pembayaran selesai sepenuhnya</div>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300"></i>
                    </button>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'Batal',
            customClass: {
                popup: 'rounded-[2rem] p-8',
                cancelButton: 'mt-4 bg-slate-100 text-slate-600 hover:bg-slate-200 border-none rounded-xl px-8 py-3 font-bold text-sm transition-all'
            },
            didOpen: () => {
                lucide.createIcons();
            }
        });
    }

    function handleStatusUpdate(invoiceId, surveyId, newStatus, totalAmount) {
        Swal.close();
        if (newStatus === 'partial') {
            Swal.fire({
                title: 'Masukkan Jumlah DP',
                input: 'number',
                inputLabel: 'Jumlah DP yang dibayarkan pelanggan (Rp)',
                inputPlaceholder: 'Contoh: 500000',
                inputAttributes: {
                    min: 1,
                    step: 1
                },
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Jumlah DP tidak boleh kosong!';
                    }
                    if (value < 0) {
                        return 'Jumlah DP tidak valid!';
                    }
                    if (value >= totalAmount) {
                        return 'Jumlah DP tidak boleh lebih besar atau sama dengan total tagihan. Jika lunas, pilih status Lunas.';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    updatePaymentStatus(invoiceId, surveyId, newStatus, result.value);
                }
            });
        } else if (newStatus === 'paid') {
            updatePaymentStatus(invoiceId, surveyId, newStatus, totalAmount);
        } else {
            updatePaymentStatus(invoiceId, surveyId, newStatus, 0);
        }
    }

    function updatePaymentStatus(invoiceId, surveyId, newStatus, paidAmount = 0) {
        // Show loading state
        Swal.fire({
            title: 'Memproses...',
            text: 'Mengupdate status pembayaran',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // AJAX Request
        fetch('../ajax/update_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `invoice_id=${invoiceId}&status=${newStatus}&paid_amount=${paidAmount}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                if(newStatus === 'partial' || newStatus === 'paid') {
                    // Berhasil dan siap dicetak
                    Swal.fire({
                        title: 'Pembayaran Diterima!',
                        text: 'Pesanan kini siap diproses oleh tim produksi.',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: '<i class="lucide-printer w-4 h-4 inline"></i> Cetak SPK Sekarang',
                        cancelButtonText: 'Nanti Saja',
                        confirmButtonColor: '#3b82f6',
                        cancelButtonColor: '#94a3b8',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            printSPK(surveyId);
                            // Refresh after a short delay to update button UI
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            window.location.reload();
                        }
                    });
                } else {
                    // Jika diubah ke belum bayar
                    Swal.fire({
                        title: 'Status Diperbarui',
                        text: 'Status dikembalikan ke Belum Bayar.',
                        icon: 'info',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                }
            } else {
                Swal.fire('Error', data.message || 'Gagal update status', 'error');
                // Revert select visually (requires page reload for simplicity)
                setTimeout(() => window.location.reload(), 2000);
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
            console.error(err);
        });
    }

    function printSPK(surveyId) {
        const url = 'print_spk.php?id=' + surveyId;
        // Open in new tab/window for printing
        window.open(url, '_blank', 'width=800,height=600');
    }
</script>

<?php include '../includes/footer.php'; ?>
