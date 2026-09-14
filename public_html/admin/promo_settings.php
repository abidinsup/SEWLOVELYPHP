<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Pengaturan Promo";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

$successMessage = '';
$errorMessage = '';

// Check if table exists
$tableExists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'app_settings'");
    $tableExists = ($stmt->rowCount() > 0);
} catch (PDOException $e) {}

$uploadDir = '../uploads/banners/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!$tableExists) {
        $errorMessage = "Tabel app_settings belum ada. Silakan jalankan script migrasi.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = ?");
            
            for ($i = 1; $i <= 3; $i++) {
                $active = isset($_POST["promo_banner_{$i}_active"]) ? '1' : '0';
                $title = $_POST["promo_banner_{$i}_title"] ?? '';
                $desc = $_POST["promo_banner_{$i}_desc"] ?? '';
                $highlight = $_POST["promo_banner_{$i}_highlight"] ?? '';
                
                $stmt->execute([$active, "promo_banner_{$i}_active"]);
                $stmt->execute([$title, "promo_banner_{$i}_title"]);
                $stmt->execute([$desc, "promo_banner_{$i}_desc"]);
                $stmt->execute([$highlight, "promo_banner_{$i}_highlight"]);
                
                // Handle Image Upload
                if (isset($_FILES["promo_banner_{$i}_image"]) && $_FILES["promo_banner_{$i}_image"]['error'] === UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES["promo_banner_{$i}_image"]['tmp_name'];
                    $fileName = $_FILES["promo_banner_{$i}_image"]['name'];
                    $fileSize = $_FILES["promo_banner_{$i}_image"]['size'];
                    $fileType = $_FILES["promo_banner_{$i}_image"]['type'];
                    
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                    
                    if (in_array($fileType, $allowedTypes) && $fileSize <= 2 * 1024 * 1024) {
                        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                        $newFileName = "banner_{$i}_" . time() . "." . $extension;
                        $destPath = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($fileTmpPath, $destPath)) {
                            $stmt->execute([$newFileName, "promo_banner_{$i}_image"]);
                        } else {
                            $errorMessage .= "Gagal mengupload gambar untuk Banner $i. ";
                        }
                    } else {
                        $errorMessage .= "Format gambar Banner $i tidak valid (hanya JPG/PNG) atau ukuran lebih dari 2MB. ";
                    }
                }
            }
            
            $pdo->commit();
            if (empty($errorMessage)) {
                $successMessage = "Pengaturan berhasil disimpan.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errorMessage = "Gagal menyimpan pengaturan: " . $e->getMessage();
        }
    }
}

// Fetch current settings
$settings = [];
for ($i = 1; $i <= 3; $i++) {
    $settings["promo_banner_{$i}_active"] = '1';
    $settings["promo_banner_{$i}_title"] = "";
    $settings["promo_banner_{$i}_desc"] = "";
    $settings["promo_banner_{$i}_highlight"] = "";
    $settings["promo_banner_{$i}_image"] = "";
}

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
        <div class="space-y-6 max-w-5xl mx-auto">
            
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Pengaturan Promo Banner</h1>
                <p class="text-slate-500">Kelola hingga 3 banner promo di halaman dashboard aplikasi mitra</p>
            </div>

            <?php if (!$tableExists): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-start gap-3">
                <i data-lucide="alert-triangle" class="h-5 w-5 text-amber-600 mt-0.5"></i>
                <div>
                    <p class="text-amber-800 font-bold">Database Belum Siap</p>
                    <p class="text-amber-700 text-sm mt-1">Tabel `app_settings` belum ada atau belum diupdate. Silakan jalankan file `migrate_promo_v2.php` di browser.</p>
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

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <!-- Tab Navigation (Desktop) -->
                <div class="flex space-x-1 p-1 bg-slate-200/50 rounded-2xl max-w-md">
                    <button type="button" onclick="showTab(1)" id="tab-btn-1" class="flex-1 py-2.5 text-sm font-bold rounded-xl bg-white shadow-sm text-slate-900 transition-all">Banner 1</button>
                    <button type="button" onclick="showTab(2)" id="tab-btn-2" class="flex-1 py-2.5 text-sm font-bold rounded-xl text-slate-500 hover:text-slate-700 transition-all">Banner 2</button>
                    <button type="button" onclick="showTab(3)" id="tab-btn-3" class="flex-1 py-2.5 text-sm font-bold rounded-xl text-slate-500 hover:text-slate-700 transition-all">Banner 3</button>
                </div>

                <?php for ($i = 1; $i <= 3; $i++): ?>
                <div id="tab-content-<?php echo $i; ?>" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 lg:p-8 <?php echo $i > 1 ? 'hidden' : ''; ?>">
                    <div class="flex items-center justify-between mb-6 pb-6 border-b border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-emerald-50 rounded-xl">
                                <i data-lucide="image" class="h-5 w-5 text-emerald-600"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-lg text-slate-900">Banner <?php echo $i; ?></h2>
                                <p class="text-sm text-slate-500">Pengaturan untuk banner ke-<?php echo $i; ?></p>
                            </div>
                        </div>
                        
                        <label for="toggle_banner_<?php echo $i; ?>" class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="toggle_banner_<?php echo $i; ?>" name="promo_banner_<?php echo $i; ?>_active" value="1" class="peer opacity-0 absolute w-0 h-0" <?php echo $settings["promo_banner_{$i}_active"] == '1' ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-slate-200 rounded-full peer-checked:bg-emerald-500 transition-colors duration-300"></div>
                            <div class="absolute left-[2px] top-[2px] w-5 h-5 bg-white rounded-full transition-transform duration-300 peer-checked:translate-x-full shadow-sm border border-slate-200 pointer-events-none"></div>
                            <span class="ml-3 text-sm font-bold text-slate-700">Aktif</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Kolom Teks -->
                        <div class="space-y-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Judul Promo</label>
                                <textarea name="promo_banner_<?php echo $i; ?>_title" rows="2"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent text-sm font-bold text-slate-900"><?php echo htmlspecialchars($settings["promo_banner_{$i}_title"]); ?></textarea>
                                <p class="text-xs text-slate-400 mt-1">Gunakan \n untuk baris baru.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Deskripsi Promo</label>
                                <textarea name="promo_banner_<?php echo $i; ?>_desc" rows="2"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent text-sm text-slate-700"><?php echo htmlspecialchars($settings["promo_banner_{$i}_desc"]); ?></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Teks Highlight (Warna Kuning)</label>
                                <input type="text" name="promo_banner_<?php echo $i; ?>_highlight" value="<?php echo htmlspecialchars($settings["promo_banner_{$i}_highlight"]); ?>"
                                    class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-transparent text-sm font-bold text-amber-500" />
                            </div>
                        </div>

                        <!-- Kolom Gambar -->
                        <div class="space-y-4">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Gambar Banner</label>
                            
                            <?php if (!empty($settings["promo_banner_{$i}_image"])): ?>
                                <div class="mb-4 rounded-xl overflow-hidden border border-slate-200 relative group aspect-[2/1]">
                                    <img src="../uploads/banners/<?php echo $settings["promo_banner_{$i}_image"]; ?>" alt="Banner <?php echo $i; ?>" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <span class="text-white text-sm font-bold">Gambar Saat Ini</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-4 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 flex items-center justify-center aspect-[2/1]">
                                    <div class="text-center text-slate-400">
                                        <i data-lucide="image" class="h-8 w-8 mx-auto mb-2 opacity-50"></i>
                                        <p class="text-sm">Belum ada gambar</p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="relative group">
                                <input type="file" name="promo_banner_<?php echo $i; ?>_image" id="file-<?php echo $i; ?>" accept=".jpg,.jpeg,.png" class="hidden" onchange="updateFileName(<?php echo $i; ?>)">
                                <label for="file-<?php echo $i; ?>" class="w-full flex items-center justify-center gap-2 h-12 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-sm cursor-pointer transition-colors border border-slate-200">
                                    <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                                    <span id="filename-<?php echo $i; ?>">Pilih Gambar Baru</span>
                                </label>
                            </div>
                            <p class="text-[10px] text-slate-400 text-center">Format: JPG, PNG. Maksimal 2MB. Rekomendasi ukuran: 600x300 px.</p>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>

                <div class="flex justify-end sticky bottom-6 z-10 pt-4">
                    <button type="submit" name="save_settings" <?php echo !$tableExists ? 'disabled' : ''; ?>
                        class="bg-[#63e5ff] hover:bg-cyan-400 text-slate-900 px-8 py-4 rounded-2xl font-bold shadow-xl shadow-cyan-400/30 transition-all flex items-center gap-2 text-lg w-full md:w-auto justify-center <?php echo !$tableExists ? 'opacity-50 cursor-not-allowed' : 'hover:-translate-y-1'; ?>">
                        <i data-lucide="save" class="h-5 w-5"></i>
                        Simpan Semua Pengaturan
                    </button>
                </div>
            </form>

        </div>
    </main>
</div>

<script>
    function showTab(index) {
        // Hide all
        for(let i=1; i<=3; i++) {
            document.getElementById('tab-content-'+i).classList.add('hidden');
            const btn = document.getElementById('tab-btn-'+i);
            btn.classList.remove('bg-white', 'shadow-sm', 'text-slate-900');
            btn.classList.add('text-slate-500');
        }
        // Show active
        document.getElementById('tab-content-'+index).classList.remove('hidden');
        const activeBtn = document.getElementById('tab-btn-'+index);
        activeBtn.classList.add('bg-white', 'shadow-sm', 'text-slate-900');
        activeBtn.classList.remove('text-slate-500');
    }

    function updateFileName(index) {
        const input = document.getElementById('file-'+index);
        const label = document.getElementById('filename-'+index);
        if (input.files && input.files.length > 0) {
            label.textContent = input.files[0].name;
        } else {
            label.textContent = 'Pilih Gambar Baru';
        }
    }
</script>

<?php include '../includes/footer.php'; ?>
