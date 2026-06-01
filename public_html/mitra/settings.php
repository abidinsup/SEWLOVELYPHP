<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkMitra();

$page_title = "Pengaturan Akun";
$save_message = '';
$save_type = '';

// Handle POST - Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("SELECT id FROM partners WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $p = $stmt->fetch();

        if ($p) {
            // Update partner data
            $stmt = $pdo->prepare("
                UPDATE partners SET 
                    full_name = ?, 
                    whatsapp_number = ?, 
                    birth_date = ?, 
                    address = ?,
                    bank_name = ?,
                    account_number = ?,
                    account_holder = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['full_name'],
                $_POST['whatsapp_number'],
                $_POST['birth_date'] ?: null,
                $_POST['address'],
                $_POST['bank_name'],
                $_POST['account_number'],
                $_POST['account_holder'],
                $p['id']
            ]);

            // Update password if provided
            if (!empty($_POST['new_password'])) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$hash, $_SESSION['user_id']]);
                } else {
                    $save_message = 'Password baru tidak cocok dengan konfirmasi!';
                    $save_type = 'error';
                }
            }

            if (empty($save_message)) {
                $save_message = 'success';
                $save_type = 'success';
            }
        }
    } catch (PDOException $e) {
        $save_message = 'Gagal menyimpan: ' . $e->getMessage();
        $save_type = 'error';
    }
}

include '../includes/header.php';
include '../includes/sidebar_mitra.php';

// Fetch Real Partner Data from Database
$partner = [
    'full_name' => '',
    'whatsapp_number' => '',
    'birth_date' => '',
    'address' => '',
    'bank_name' => '',
    'account_number' => '',
    'account_holder' => '',
];

try {
    $stmt = $pdo->prepare("SELECT p.* FROM partners p WHERE p.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $p = $stmt->fetch();
    if ($p) {
        $partner = [
            'full_name' => $p['full_name'],
            'whatsapp_number' => $p['whatsapp_number'],
            'birth_date' => $p['birth_date'] ?: '',
            'address' => $p['address'] ?: '',
            'bank_name' => $p['bank_name'] ?: '',
            'account_number' => $p['account_number'] ?: '',
            'account_holder' => $p['account_holder'] ?: '',
        ];
    }
} catch (PDOException $e) {
    // Keep defaults
}

$banks = ["BCA", "BRI", "BNI", "Mandiri", "BSI", "CIMB Niaga", "Danamon", "Permata", "BTN", "BTPN / Jenius", "Jago", "SeaBank", "GoPay", "OVO", "Dana", "ShopeePay", "LinkAja", "Lainnya"];
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <!-- Header Page -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/90 md:mt-0 mt-16 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="profile.php" class="hover:bg-slate-100 rounded-full h-10 w-10 flex items-center justify-center transition-colors -ml-2">
                    <i data-lucide="arrow-left" class="h-5 w-5 text-slate-700"></i>
                </a>
                <div class="h-6 w-px bg-slate-200 hidden md:block"></div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Pengaturan Akun</h1>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-32 w-full">
        <form action="settings.php" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start" id="settingsForm">
            
            <!-- Left Column: Form -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Data Diri -->
                <div class="bg-white p-6 md:p-8 rounded-[2rem] border border-slate-100 shadow-sm space-y-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                        <div class="bg-emerald-50 p-2.5 rounded-xl">
                            <i data-lucide="user" class="h-5 w-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-lg text-slate-900">Ubah Biodata Diri</h2>
                            <p class="text-xs text-slate-500">Perbarui informasi pribadi Anda</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Nama Lengkap</label>
                            <input type="text" name="full_name" value="<?php echo $partner['full_name']; ?>" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">No. WhatsApp</label>
                            <div class="flex shadow-sm rounded-xl overflow-hidden border border-slate-200 focus-within:border-emerald-500 focus-within:ring-1 focus-within:ring-emerald-500 transition-all bg-slate-50">
                                <div class="flex items-center gap-1 px-3 bg-slate-100 border-r border-slate-200 text-slate-500 font-bold text-sm select-none shrink-0">
                                    <span>+62</span>
                                </div>
                                <input type="tel" id="whatsapp_number_display" required placeholder="Contoh: 8123456789" class="w-full h-12 px-4 bg-transparent border-0 focus:outline-none focus:ring-0 font-medium text-slate-900 text-sm" />
                                <input type="hidden" name="whatsapp_number" id="whatsapp_number" value="<?php echo htmlspecialchars($partner['whatsapp_number']); ?>" />
                            </div>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Tanggal Lahir</label>
                            <input type="date" name="birth_date" value="<?php echo $partner['birth_date']; ?>" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl text-slate-700" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Alamat Lengkap</label>
                            <textarea name="address" required class="w-full min-h-[100px] px-4 py-3 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl resize-none"><?php echo $partner['address']; ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Info Bank -->
                <div class="bg-white p-6 md:p-8 rounded-[2rem] border border-slate-100 shadow-sm space-y-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                        <div class="bg-emerald-50 p-2.5 rounded-xl">
                            <i data-lucide="credit-card" class="h-5 w-5 text-emerald-600"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-lg text-slate-900">Ubah Data Rekening</h2>
                            <p class="text-xs text-slate-500">Rekening untuk pencairan komisi</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Nama Bank</label>
                            <select name="bank_name" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl text-slate-700">
                                <?php foreach($banks as $bank): ?>
                                    <option value="<?php echo $bank; ?>" <?php echo $partner['bank_name'] == $bank ? 'selected' : ''; ?>><?php echo $bank; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Nomor Rekening</label>
                            <input type="text" name="account_number" value="<?php echo $partner['account_number']; ?>" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl font-mono" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Nama Pemilik Rekening</label>
                            <input type="text" name="account_holder" value="<?php echo $partner['account_holder']; ?>" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl" />
                        </div>
                    </div>
                </div>

                <!-- Keamanan -->
                <div class="bg-white p-6 md:p-8 rounded-[2rem] border border-slate-100 shadow-sm space-y-6">
                    <div class="flex items-center gap-3 pb-4 border-b border-gray-100">
                        <div class="bg-slate-100 p-2.5 rounded-xl">
                            <i data-lucide="lock" class="h-5 w-5 text-slate-600"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-lg text-slate-900">Ubah Password</h2>
                            <p class="text-xs text-slate-500">Kosongkan jika tidak ingin mengubah password</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Password Baru</label>
                            <input type="password" name="new_password" placeholder="Min. 6 karakter" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" placeholder="Ulangi password baru" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl" />
                        </div>
                    </div>
                </div>

                <!-- Submit Button (Mobile view) -->
                <div class="block lg:hidden mt-8">
                    <button type="button" onclick="saveSettings()" class="w-full h-14 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-bold text-lg shadow-xl shadow-emerald-600/30 flex items-center justify-center gap-2">
                        <i data-lucide="save" class="h-5 w-5"></i> Simpan Perubahan
                    </button>
                </div>
            </div>

            <!-- Right Column: Sidebar Actions (Desktop) -->
            <div class="hidden lg:block lg:col-span-4 sticky top-24">
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm">
                    <div class="text-center mb-6">
                        <div class="w-20 h-20 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4 border-4 border-white shadow-lg">
                            <?php $words = explode(' ', $partner['full_name']); echo strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1)); ?>
                        </div>
                        <h3 class="font-bold text-lg text-slate-900"><?php echo $partner['full_name']; ?></h3>
                        <p class="text-sm text-slate-500">Mitra Affiliate</p>
                    </div>
                    
                    <button type="button" onclick="saveSettings()" class="w-full h-12 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl font-bold shadow-lg shadow-emerald-600/30 flex items-center justify-center gap-2 transition-all hover:-translate-y-0.5">
                        <i data-lucide="save" class="h-5 w-5"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </main>
</div>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
    <div class="bg-white p-8 rounded-3xl shadow-lg border border-slate-100 text-center max-w-md w-[90%] md:w-full animate-in zoom-in duration-300">
        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i data-lucide="check-circle-2" class="h-10 w-10 text-emerald-600"></i>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 mb-2">Perubahan Disimpan!</h2>
        <p class="text-slate-500 mb-6">
            Data profil Anda berhasil diperbarui.
        </p>
        <button onclick="window.location.href='profile.php'" class="w-full h-12 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold transition-colors">
            Kembali ke Profil
        </button>
    </div>
</div>

<script>
function saveSettings() {
    const form = document.getElementById('settingsForm');
    if(form.checkValidity()) {
        const pass = form.querySelector('[name="new_password"]').value;
        const confirmPass = form.querySelector('[name="confirm_password"]').value;

        if (pass && pass !== confirmPass) {
            Swal.fire('Gagal', 'Password baru tidak cocok dengan konfirmasi!', 'error');
            return;
        }

        // Submit form via POST
        form.submit();
    } else {
        form.reportValidity();
    }
}

// Handle server-side response
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($save_type === 'success'): ?>
        document.getElementById('successModal').classList.remove('hidden');
    <?php elseif ($save_type === 'error'): ?>
        Swal.fire('Gagal', '<?php echo addslashes($save_message); ?>', 'error');
    <?php endif; ?>

    // Formatting No. WhatsApp dengan kotak terpisah
    const phoneDisplay = document.getElementById('whatsapp_number_display');
    const phoneHidden = document.getElementById('whatsapp_number');
    if (phoneDisplay && phoneHidden) {
        let initVal = phoneHidden.value;
        let cleanInit = initVal.replace(/[^\d]/g, '');
        if (cleanInit.startsWith('62')) {
            phoneDisplay.value = cleanInit.substring(2);
        } else if (cleanInit.startsWith('0')) {
            phoneDisplay.value = cleanInit.substring(1);
        } else {
            phoneDisplay.value = cleanInit;
        }
        
        phoneDisplay.addEventListener('input', function() {
            let val = this.value;
            let clean = val.replace(/[^\d]/g, '');
            
            if (clean.startsWith('62')) {
                clean = clean.substring(2);
            } else if (clean.startsWith('0')) {
                clean = clean.substring(1);
            }
            
            this.value = clean;
            phoneHidden.value = clean ? '+62' + clean : '+62';
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
