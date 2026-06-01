<?php
$current_page = basename($_SERVER['PHP_SELF']);
$menu_items = [
    ['icon' => 'layout-dashboard', 'label' => 'Ajukan Survey', 'href' => 'index.php'],
    ['icon' => 'clipboard-list', 'label' => 'Jadwal Survey', 'href' => 'survey.php'],
    ['icon' => 'wallet', 'label' => 'Komisi', 'href' => 'komisi.php'],
    ['icon' => 'user', 'label' => 'Profil Akun', 'href' => 'profile.php']
];

// Fetch Real Mitra Data for Chat Link
$partner_name = "Mitra";
$partner_id = "-";

if (isset($pdo) && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT full_name, affiliate_code FROM partners WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $partner_data = $stmt->fetch();
        if ($partner_data) {
            $partner_name = $partner_data['full_name'];
            $partner_id = $partner_data['affiliate_code'];
        }
    } catch (PDOException $e) {}
}
?>

<!-- Mobile Header -->
<div class="md:hidden print:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-100 z-50 flex items-center justify-between px-4">
    <div class="font-bold text-gray-900">Sewlovely Homeset</div>
    <button class="p-2 text-gray-900 rounded-md hover:bg-gray-100" onclick="toggleSidebar()">
        <i data-lucide="menu" class="h-6 w-6"></i>
    </button>
</div>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 md:hidden print:hidden hidden" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-100 flex flex-col transition-transform duration-300 ease-in-out print:hidden -translate-x-full md:translate-x-0">
    
    <!-- Logo Area -->
    <div class="h-16 flex items-center px-6 border-b border-gray-100">
        <a href="index.php" class="flex items-center space-x-3">
            <div class="relative h-10 w-10 flex items-center justify-center group-hover:scale-105 transition-transform overflow-hidden">
                <i data-lucide="blinds" class="h-6 w-6 text-emerald-500"></i>
            </div>
            <span class="font-bold text-base tracking-tight text-gray-900 whitespace-nowrap">Sewlovely Homeset</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        <?php foreach ($menu_items as $item): ?>
            <?php $isActive = ($current_page == $item['href']) ? true : false; ?>
            <a href="<?php echo $item['href']; ?>" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 <?php echo $isActive ? 'bg-emerald-50 text-emerald-700 shadow-sm shadow-emerald-100/50' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'; ?>">
                <div class="relative">
                    <i data-lucide="<?php echo $item['icon']; ?>" class="h-5 w-5 <?php echo $isActive ? 'text-emerald-600' : 'text-gray-400'; ?>"></i>
                </div>
                <span><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>

        <!-- Action Buttons -->
        <div class="pt-4 mt-4 border-t border-gray-50 space-y-1">
            <a href="https://wa.me/6285159588681?text=Halo%20Admin%20Sewlovely,%20saya%20<?php echo urlencode($partner_name); ?>%20(ID:%20<?php echo urlencode($partner_id); ?>),%20ingin%20bertanya..." target="_blank" rel="noopener noreferrer" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm font-medium text-emerald-600 hover:bg-emerald-50 transition-all duration-200 w-full" title="Chat dengan admin">
                <div class="bg-emerald-100 p-1.5 rounded-lg shrink-0">
                    <i data-lucide="phone" class="h-4 w-4"></i>
                </div>
                <div class="flex flex-col">
                    <span class="leading-none">Chat Admin</span>
                    <span class="text-[10px] text-emerald-500/70 font-normal mt-0.5 whitespace-nowrap">Sebutkan Nama & ID</span>
                </div>
            </a>

            <button onclick="logout()" class="w-full flex justify-start items-center text-red-500 hover:text-red-600 hover:bg-red-50 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200">
                <i data-lucide="log-out" class="mr-3 h-5 w-5 shrink-0"></i>
                Keluar
            </button>
        </div>
    </nav>
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
            title: 'Konfirmasi Keluar',
            text: "Apakah Anda yakin ingin keluar dari aplikasi?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#ef4444',
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
