<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch Pending Surveys Count
$pending_count = 0;
$pending_partners_count = 0;
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM surveys WHERE status = 'pending'");
        $pending_count = $stmt->fetchColumn();
        $stmt->closeCursor();

        $stmt = $pdo->query("SELECT COUNT(*) FROM partners WHERE status = 'pending'");
        $pending_partners_count = $stmt->fetchColumn();
        $stmt->closeCursor();
    } catch (PDOException $e) {
        error_log("Sidebar Admin PDO Error: " . $e->getMessage());
    }
}

$menu_items = [
    ['icon' => 'layout-dashboard', 'label' => 'Dashboard', 'href' => 'index.php'],
    ['icon' => 'sparkles', 'label' => 'Konsultan AI', 'href' => 'consultant.php'],
    ['icon' => 'home', 'label' => 'POS Gorden Rumah', 'href' => 'calculator.php'],
    ['icon' => 'building-2', 'label' => 'POS Gorden Kantor', 'href' => 'calculator_kantor.php'],
    ['icon' => 'heart-pulse', 'label' => 'POS Gorden RS', 'href' => 'calculator_rs.php'],
    ['icon' => 'calendar', 'label' => 'Status Order', 'href' => 'surveys.php', 'badge' => $pending_count],
    ['icon' => 'credit-card', 'label' => 'Approval Pembayaran', 'href' => 'payments.php'],
    ['icon' => 'user-check', 'label' => 'Approval Data Mitra', 'href' => 'approvals.php', 'badge' => $pending_partners_count],
    ['icon' => 'users', 'label' => 'Data Mitra', 'href' => 'partners.php'],
    ['icon' => 'wallet', 'label' => 'Approval Penarikan', 'href' => 'withdrawals.php'],
    ['icon' => 'gift', 'label' => 'Bonus Manual', 'href' => 'bonus.php'],
    ['icon' => 'package', 'label' => 'Manajemen Produk', 'href' => 'products.php'],
    ['icon' => 'scissors', 'label' => 'Katalog Gorden', 'href' => 'curtain_catalog.php'],
    ['icon' => 'settings', 'label' => 'Pengaturan', 'href' => 'settings.php']
];
?>

<!-- Mobile Header -->
<div class="lg:hidden print:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-slate-200 z-50 flex items-center justify-between px-4">
    <div class="font-bold text-slate-900">Sewlovely Homeset Admin</div>
    <button class="p-2 text-slate-900 rounded-md hover:bg-slate-100" onclick="toggleSidebar()">
        <i data-lucide="menu" class="h-6 w-6"></i>
    </button>
</div>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden print:hidden hidden" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed lg:static inset-y-0 left-0 w-64 bg-white border-r border-slate-200 text-slate-900 z-50 transform transition-transform duration-300 lg:transform-none flex flex-col print:hidden -translate-x-full lg:translate-x-0">
    <div class="p-6">
        <div class="flex items-center gap-3 mb-8">
            <div class="relative h-10 w-10 flex items-center justify-center overflow-hidden">
                <i data-lucide="blinds" class="h-6 w-6 text-[#00CEC8]"></i>
            </div>
            <div>
                <h1 class="font-bold text-lg leading-none whitespace-nowrap text-slate-900">Sewlovely Homeset</h1>
                <span class="text-xs text-slate-500">Owner Panel</span>
            </div>
        </div>

        <nav class="space-y-2">
            <?php foreach ($menu_items as $item): ?>
                <?php $isActive = ($current_page == $item['href']) ? true : false; ?>
                <a href="<?php echo $item['href']; ?>" class="block rounded-xl transition-all duration-200">
                    <div class="flex items-center justify-between w-full px-4 py-3 rounded-xl transition-all duration-200 <?php echo $isActive ? 'bg-[#63e5ff] text-slate-900 font-bold shadow-lg shadow-cyan-400/30' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'; ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="<?php echo $item['icon']; ?>" class="h-5 w-5 <?php echo $isActive ? 'text-slate-900' : ''; ?>"></i>
                            <span class="text-sm"><?php echo $item['label']; ?></span>
                        </div>
                        <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
                            <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm shadow-red-200">
                                <?php echo $item['badge']; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>

            <div class="pt-4 mt-4 border-t border-slate-100">
                <button onclick="logout()" class="flex items-center gap-3 text-slate-500 hover:text-red-500 hover:bg-red-50/50 transition-all duration-200 w-full px-4 py-3 rounded-xl">
                    <i data-lucide="log-out" class="h-5 w-5 shrink-0"></i>
                    <span class="text-sm font-medium">Logout</span>
                </button>
            </div>
        </nav>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    function logout() {
        Swal.fire({
            title: 'Konfirmasi Logout',
            text: "Apakah Anda yakin ingin keluar dari panel admin?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#00CEC8',
            cancelButtonColor: '#f43f5e',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../logout.php';
            }
        });
    }
</script>
