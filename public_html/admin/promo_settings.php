<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Pengaturan Promo";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

$successMessage = '';
$errorMessage = '';

// Check if table exists, if not, wait for migration
$tableExists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'app_settings'");
    $tableExists = ($stmt->rowCount() > 0);
} catch (PDOException $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!$tableExists) {
        $errorMessage = "Tabel app_settings belum ada. Silakan jalankan file SQL promo_settings.sql terlebih dahulu di phpMyAdmin.";
    } else {
        $title = $_POST['promo_banner_title'] ?? '';
        $desc = $_POST['promo_banner_desc'] ?? '';
        $highlight = $_POST['promo_banner_highlight'] ?? '';
        
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$title, 'promo_banner_title']);
            $stmt->execute([$desc, 'promo_banner_desc']);
            $stmt->execute([$highlight, 'promo_banner_highlight']);
            
            $pdo->commit();
            $successMessage = "Pengaturan berhasil disimpan.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errorMessage = "Gagal menyimpan pengaturan.";
        }
    }
}

// Fetch current settings
$settings = [
    'promo_banner_active' => '1',
    'promo_banner_title' => "Raih Bonusnya!\nSelesaikan 5 Pemasangan",
    'promo_banner_desc' => "Selesaikan 5 projek pemasangan dan\ndapatkan komisi tambahan ",
    'promo_banner_highlight' => "Rp 300.000"
];

if ($tableExists) {
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings");
        $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        if ($dbSettings) {
            $settings = array_merge($settings, $dbSettings);
        }
    } catch (PDOException $e) {}
}
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <main class="flex-1 p-4 lg:p-8 pt-20 lg:pt-8 overflow-x-hidden relative w-full max-w-full">
        <div class="space-y-6 max-w-4xl mx-auto">
            
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Pengaturan Promo Banner</h1>
                <p class="text-slate-500">Ubah teks banner promo di halaman dashboard aplikasi mitra</p>
            </div>

            <?php if (!$tableExists): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
                <i data-lucide="alert-triangle" class="h-5 w-5 text-amber-600 mt-0.5"></i>
                <div>
                    <p class="text-amber-800 font-bold">Database Belum Siap</p>
                    <p class="text-amber-700 text-sm mt-1">Tabel `app_settings` belum ada di database Anda. Anda harus meng-import file `promo_settings.sql` ke phpMyAdmin (cPanel) terlebih dahulu agar pengaturan ini bisa disimpan.</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
            <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center gap-3">
                <i data-lucide="check-circle-2" class="h-5 w-5 text-emerald-600"></i>
                <p class="text-emerald-800 font-medium"><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
            <div class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-center gap-3">
                <i data-lucide="alert-circle" class="h-5 w-5 text-red-600"></i>
                <p class="text-red-800 font-medium"><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 lg:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2.5 bg-emerald-50 rounded-xl">
                        <i data-lucide="layout-template" class="h-5 w-5 text-emerald-600"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-lg text-slate-900">Banner Promo (Dashboard)</h2>
                        <p class="text-sm text-slate-500">Edit teks yang muncul di banner hijau pada beranda mitra</p>
                    </div>
                </div>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Judul Promo</label>
                        <textarea name="promo_banner_title" rows="2" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent text-sm font-bold text-slate-900"><?php echo htmlspecialchars($settings['promo_banner_title']); ?></textarea>
                        <p class="text-xs text-slate-400 mt-2">Bisa pakai \n untuk baris baru (enter). Contoh: <i>Raih Bonusnya!\nSelesaikan 5 Pemasangan</i></p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Deskripsi Promo</label>
                        <textarea name="promo_banner_desc" rows="2" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent text-sm text-slate-700"><?php echo htmlspecialchars($settings['promo_banner_desc']); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Teks Highlight (Warna Kuning)</label>
                        <input type="text" name="promo_banner_highlight" value="<?php echo htmlspecialchars($settings['promo_banner_highlight']); ?>" required
                            class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent text-sm font-bold text-amber-500" />
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end">
                        <button type="submit" name="save_settings" <?php echo !$tableExists ? 'disabled' : ''; ?>
                            class="bg-[#63e5ff] hover:bg-cyan-400 text-slate-900 px-8 py-3 rounded-xl font-bold shadow-lg shadow-cyan-400/20 transition-all flex items-center gap-2 <?php echo !$tableExists ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
