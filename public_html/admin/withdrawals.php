<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Approval Penarikan";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Fetch Real Withdrawal Data from Database
try {
    $stmt = $pdo->query("
        SELECT 
            t.id,
            t.amount,
            t.status,
            t.created_at,
            t.proof_url,
            p.full_name AS mitra,
            p.affiliate_code AS mitraId,
            p.bank_name AS bank,
            p.account_number AS accountNumber
        FROM transactions t
        JOIN partners p ON t.partner_id = p.id
        WHERE t.type = 'withdraw'
        ORDER BY t.created_at DESC
    ");
    $withdrawals_raw = $stmt->fetchAll();

    $withdrawals = [];
    foreach ($withdrawals_raw as $w) {
        $withdrawals[] = [
            'id' => 'WDR-' . str_pad($w['id'], 3, '0', STR_PAD_LEFT),
            'db_id' => $w['id'],
            'mitra' => $w['mitra'],
            'mitraId' => $w['mitraId'],
            'bank' => $w['bank'] ?: '-',
            'accountNumber' => $w['accountNumber'] ?: '-',
            'amount' => (float)$w['amount'],
            'date' => date('d M Y', strtotime($w['created_at'])),
            'raw_date' => date('Y-m-d', strtotime($w['created_at'])),
            'status' => $w['status'],
            'proof_url' => $w['proof_url'],
        ];
    }
} catch (PDOException $e) {
    $withdrawals = [];
}
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <main class="flex-1 p-4 lg:p-8 pt-20 lg:pt-8 overflow-x-hidden relative w-full max-w-full">
        <div class="space-y-6 max-w-7xl mx-auto">
            
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Approval Penarikan</h1>
                    <p class="text-slate-500">Kelola permintaan pencairan komisi mitra</p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4">
                    <!-- Status Tabs -->
                    <div class="bg-slate-100 p-1 rounded-xl inline-flex overflow-x-auto scrollbar-hide">
                        <button onclick="filterTabs('pending', this)" class="tab-btn bg-white text-slate-800 shadow-sm px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap">Menunggu Approval</button>
                        <button onclick="filterTabs('success', this)" class="tab-btn text-slate-500 hover:text-slate-700 px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap">History Transfer</button>
                        <button onclick="filterTabs('rejected', this)" class="tab-btn text-slate-500 hover:text-slate-700 px-4 py-2 rounded-lg text-sm font-bold transition-all whitespace-nowrap">History Penolakan</button>
                    </div>

                    <!-- Additional Filters -->
                    <div class="flex items-center gap-2">
                        <input type="date" id="dateFilter" onchange="applyFilters()" class="h-10 px-3 bg-white border border-slate-200 rounded-lg text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer" />
                        <select id="bankFilter" onchange="applyFilters()" class="h-10 px-3 bg-white border border-slate-200 rounded-lg text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 max-w-[160px]">
                            <option value="">Semua Bank</option>
                            <option value="BCA">BCA</option>
                            <option value="BRI">BRI</option>
                            <option value="BNI">BNI</option>
                            <option value="Mandiri">Mandiri</option>
                            <option value="BSI">BSI</option>
                            <option value="CIMB Niaga">CIMB Niaga</option>
                            <option value="Danamon">Danamon</option>
                            <option value="Permata">Permata</option>
                            <option value="BTN">BTN</option>
                            <option value="Jago">Jago</option>
                            <option value="SeaBank">SeaBank</option>
                            <option value="GoPay">GoPay</option>
                            <option value="OVO">OVO</option>
                            <option value="Dana">Dana</option>
                            <option value="ShopeePay">ShopeePay</option>
                            <option value="LinkAja">LinkAja</option>
                        </select>
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
                                <th class="p-4">Mitra</th>
                                <th class="p-4">Bank & No. Rek</th>
                                <th class="p-4">Nominal</th>
                                <th class="p-4 text-center rounded-tr-lg">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            <?php foreach($withdrawals as $item): ?>
                            <tr class="hover:bg-slate-50/50 group transaction-row" data-status="<?php echo $item['status']; ?>" data-bank="<?php echo htmlspecialchars(strtolower($item['bank'])); ?>" data-date="<?php echo $item['raw_date']; ?>" data-search="<?php echo strtolower($item['id'] . ' ' . $item['mitra'] . ' ' . $item['mitraId']); ?>" style="<?php echo $item['status'] !== 'pending' ? 'display:none;' : ''; ?>">
                                <td class="p-4">
                                    <p class="font-bold text-slate-900 truncate max-w-[100px]"><?php echo $item['id']; ?></p>
                                    <p class="text-slate-500 text-xs"><?php echo $item['date']; ?></p>
                                </td>
                                <td class="p-4">
                                    <p class="font-bold text-slate-700"><?php echo $item['mitra']; ?></p>
                                    <p class="text-slate-400 text-xs"><?php echo $item['mitraId']; ?></p>
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded text-xs"><?php echo $item['bank']; ?></span>
                                        <span class="text-slate-600 font-mono"><?php echo $item['accountNumber']; ?></span>
                                    </div>
                                </td>
                                <td class="p-4 font-bold text-slate-900 text-lg">
                                    Rp <?php echo number_format($item['amount'], 0, ',', '.'); ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if($item['status'] == 'pending'): ?>
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="processWithdrawal('approve', <?php echo $item['db_id']; ?>, '<?php echo addslashes($item['mitra']); ?>')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white flex items-center gap-1 rounded-lg font-bold text-xs transition-colors">
                                                <i data-lucide="check-circle-2" class="h-4 w-4"></i> Approve
                                            </button>
                                            <button onclick="processWithdrawal('reject', <?php echo $item['db_id']; ?>, '<?php echo addslashes($item['mitra']); ?>')" class="p-1.5 text-red-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <i data-lucide="x-circle" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase <?php echo $item['status'] == 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                                                <?php echo $item['status'] == 'success' ? 'Berhasil' : 'Ditolak'; ?>
                                            </span>
                                            <?php if($item['status'] == 'success' && !empty($item['proof_url'])): ?>
                                                <button onclick="viewProof('<?php echo htmlspecialchars($item['proof_url']); ?>')" class="text-[10px] text-emerald-600 font-bold hover:underline">Lihat Bukti</button>
                                            <?php endif; ?>
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
function processWithdrawal(action, transactionId, mitraName) {
    const isApprove = action === 'approve';
    
    if (isApprove) {
        Swal.fire({
            title: 'Upload Bukti Transfer',
            html: `Setujui penarikan untuk <strong>${mitraName}</strong>? Silakan upload bukti transfer.`,
            input: 'file',
            inputAttributes: {
                'accept': 'image/*',
                'aria-label': 'Upload bukti transfer Anda'
            },
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Approve & Upload',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                if (!result.value) {
                    Swal.fire('Peringatan', 'Harap pilih file bukti transfer.', 'warning');
                    return;
                }
                submitWithdrawal(action, transactionId, result.value);
            }
        });
    } else {
        Swal.fire({
            title: 'Konfirmasi Penolakan',
            html: `Tolak permintaan penarikan dari <strong>${mitraName}</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Tolak',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                submitWithdrawal(action, transactionId, null);
            }
        });
    }
}

function submitWithdrawal(action, transactionId, file) {
    const formData = new FormData();
    formData.append('transaction_id', transactionId);
    formData.append('action', action);
    if (file) {
        formData.append('proof', file);
    }

    fetch('../ajax/update_withdrawal.php', {
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

function viewProof(url) {
    Swal.fire({
        title: 'Bukti Transfer',
        imageUrl: '../' + url,
        imageAlt: 'Bukti Transfer',
        customClass: {
            image: 'rounded-xl max-w-full'
        },
        confirmButtonText: 'Tutup',
        confirmButtonColor: '#64748b'
    });
}

let currentTab = 'pending';
function filterTabs(status, btn) {
    currentTab = status;
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-white', 'text-slate-800', 'shadow-sm');
        b.classList.add('text-slate-500', 'hover:text-slate-700');
    });
    btn.classList.add('bg-white', 'text-slate-800', 'shadow-sm');
    btn.classList.remove('text-slate-500', 'hover:text-slate-700');
    
    applyFilters();
}

function applyFilters() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const bankFilter = document.getElementById('bankFilter') ? document.getElementById('bankFilter').value.toLowerCase() : '';
    const dateFilter = document.getElementById('dateFilter') ? document.getElementById('dateFilter').value : '';
    
    document.querySelectorAll('.transaction-row').forEach(row => {
        const matchStatus = row.dataset.status === currentTab || currentTab === 'all';
        const matchSearch = row.dataset.search.includes(searchTerm);
        const matchBank = bankFilter === '' || row.dataset.bank.includes(bankFilter);
        const matchDate = dateFilter === '' || row.dataset.date === dateFilter;
        
        if (matchStatus && matchSearch && matchBank && matchDate) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
