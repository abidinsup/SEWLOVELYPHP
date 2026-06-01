<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Pengaturan Admin";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Fetch all settings
$current_settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $current_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Fallback if table not ready
}

// Function to get setting safely
function get_setting($key, $default = '') {
    global $current_settings;
    return $current_settings[$key] ?? $default;
}
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <!-- Header Page -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/90 md:mt-0 mt-16 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Pengaturan Admin</h1>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto pb-24 md:pb-8">
        <div class="max-w-4xl mx-auto space-y-8">
            
            <!-- Store Identity Section -->
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/50">
                    <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="store" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Identitas Toko</h2>
                        <p class="text-sm text-slate-500">Informasi utama toko yang tampil di sistem.</p>
                    </div>
                </div>
                <div class="p-6">
                    <form onsubmit="saveSettings(event, 'update_general')">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Nama Toko</label>
                                <input type="text" name="store_name" value="<?php echo htmlspecialchars(get_setting('store_name')); ?>" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl transition-all font-medium text-slate-900" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">No. WhatsApp Toko</label>
                                <input type="text" name="store_phone" value="<?php echo htmlspecialchars(get_setting('store_phone')); ?>" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl transition-all font-medium text-slate-900" />
                            </div>
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Alamat Toko</label>
                                <textarea name="store_address" rows="2" class="w-full p-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl transition-all font-medium text-slate-900"><?php echo htmlspecialchars(get_setting('store_address')); ?></textarea>
                            </div>
                            <div class="md:col-span-2 space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Informasi Rekening Bank (Pembayaran)</label>
                                <input type="text" name="bank_info" value="<?php echo htmlspecialchars(get_setting('bank_info')); ?>" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 rounded-xl transition-all font-medium text-slate-900" placeholder="BCA 1234567890 a.n Sewlovely" />
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-600/20 transition-all flex items-center gap-2">
                                <i data-lucide="save" class="h-4 w-4"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Invoice/Nota Section -->
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center gap-4 bg-slate-50/50">
                    <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="file-text" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Pengaturan Nota / Invoice</h2>
                        <p class="text-sm text-slate-500">Logo dan syarat ketentuan pada nota digital.</p>
                    </div>
                </div>
                <div class="p-6">
                    <form onsubmit="saveSettings(event, 'update_invoice')">
                        <div class="space-y-6 mb-6">
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Logo Nota (Upload baru jika ingin ganti)</label>
                                <div class="flex flex-col md:flex-row items-center gap-6">
                                    <div class="w-32 h-32 bg-slate-100 rounded-2xl border-2 border-dashed border-slate-200 flex items-center justify-center overflow-hidden">
                                        <?php if(get_setting('invoice_logo')): ?>
                                            <img src="<?php echo BASE_URL . get_setting('invoice_logo'); ?>" class="w-full h-full object-contain" alt="Current Logo">
                                        <?php else: ?>
                                            <i data-lucide="image" class="h-8 w-8 text-slate-300"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 space-y-2">
                                        <input type="file" name="invoice_logo" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 transition-all cursor-pointer" />
                                        <p class="text-[10px] text-slate-400 italic">Format yang disarankan: PNG Transparan atau JPG. Maks 2MB.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Syarat & Ketentuan Nota</label>
                                <textarea name="invoice_terms" rows="4" class="w-full p-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl transition-all font-medium text-slate-900 text-sm"><?php echo htmlspecialchars(get_setting('invoice_terms')); ?></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2">
                                <i data-lucide="save" class="h-4 w-4"></i> Simpan Pengaturan Nota
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Commission Setting -->
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center gap-4 bg-orange-50/50">
                    <div class="w-10 h-10 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="percent" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Pengaturan Komisi Mitra</h2>
                        <p class="text-sm text-slate-500">Persentase komisi default untuk mitra baru.</p>
                    </div>
                </div>
                <div class="p-6">
                    <form onsubmit="saveSettings(event, 'update_commission')">
                        <div class="flex items-center gap-4 mb-6 max-w-sm">
                            <div class="flex-1 space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Default Komisi (%)</label>
                                <div class="relative">
                                    <input type="number" name="default_commission" value="<?php echo htmlspecialchars(get_setting('default_commission')); ?>" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 rounded-xl transition-all font-bold text-slate-900" />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 font-bold text-slate-400">%</span>
                                </div>
                            </div>
                            <div class="pt-6">
                                <button type="submit" class="px-6 h-12 bg-orange-600 text-white font-bold rounded-xl hover:bg-orange-700 shadow-lg shadow-orange-600/20 transition-all flex items-center gap-2 whitespace-nowrap">
                                    <i data-lucide="save" class="h-4 w-4"></i> Simpan
                                </button>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 italic">Perubahan ini hanya akan berdampak pada pendaftaran mitra baru ke depannya.</p>
                    </form>
                </div>
            </div>

            <!-- Security Section -->
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center gap-4 bg-red-50/50">
                    <div class="w-10 h-10 bg-red-100 text-red-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="shield-check" class="h-5 w-5"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Keamanan & Password Admin</h2>
                        <p class="text-sm text-slate-500">Ganti password akun admin Anda secara berkala.</p>
                    </div>
                </div>
                <div class="p-6">
                    <form onsubmit="saveSettings(event, 'update_admin')">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Password Lama</label>
                                <input type="password" name="old_password" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-500/10 rounded-xl transition-all font-medium text-slate-900" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Password Baru</label>
                                <input type="password" name="new_password" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-500/10 rounded-xl transition-all font-medium text-slate-900" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Ulangi Password Baru</label>
                                <input type="password" name="confirm_password" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-red-500 focus:ring-4 focus:ring-red-500/10 rounded-xl transition-all font-medium text-slate-900" />
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-6 py-2.5 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 shadow-lg shadow-red-600/20 transition-all flex items-center gap-2">
                                <i data-lucide="key" class="h-4 w-4"></i> Ganti Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
function saveSettings(e, action) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', action);

    Swal.fire({
        title: 'Simpan Perubahan?',
        text: "Pastikan data yang Anda masukkan sudah benar.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Menyimpan...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            fetch('../ajax/update_settings.php', {
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
                        if (action === 'update_admin') {
                            form.reset();
                        } else {
                            location.reload();
                        }
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
