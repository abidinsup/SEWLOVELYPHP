<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkMitra();

$page_title = "Profil Akun";
include '../includes/header.php';
include '../includes/sidebar_mitra.php';

// Fetch Real Partner Data from Database
$partner = [
    'affiliate_code' => '-',
    'full_name' => '-',
    'email' => '-',
    'whatsapp_number' => '-',
    'birth_date' => '',
    'address' => '-',
    'bank_name' => '-',
    'account_number' => '-',
    'account_holder' => '-',
    'created_at' => '',
];

try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.email 
        FROM partners p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $p = $stmt->fetch();
    if ($p) {
        $partner = [
            'affiliate_code' => $p['affiliate_code'],
            'full_name' => $p['full_name'],
            'email' => $p['email'],
            'whatsapp_number' => $p['whatsapp_number'],
            'birth_date' => $p['birth_date'] ?: '',
            'address' => $p['address'] ?: '-',
            'bank_name' => $p['bank_name'] ?: '-',
            'account_number' => $p['account_number'] ?: '-',
            'account_holder' => $p['account_holder'] ?: '-',
            'created_at' => $p['created_at'],
        ];
    }
} catch (PDOException $e) {
    // Keep defaults
}

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach(array_slice($words, 0, 2) as $w) {
        $initials .= strtoupper(substr($w, 0, 1));
    }
    return $initials;
}

function formatDate($dateString) {
    if(!$dateString) return '-';
    return date('d/m/Y', strtotime($dateString));
}
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out">
    <main class="flex-1 p-4 md:p-8 overflow-y-auto pb-24 md:pb-8 w-full max-w-full">
        <div class="max-w-7xl mx-auto space-y-8 pb-20 pt-6">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Profil Akun</h1>
                    <p class="text-gray-500 mt-1">Informasi pribadi dan detail keanggotaan Anda.</p>
                </div>
                <a href="settings.php" class="inline-block">
                    <button class="inline-flex items-center justify-center gap-2 rounded-xl h-12 px-6 border border-slate-200 bg-white hover:bg-slate-50 text-slate-900 font-medium transition-colors">
                        Ubah Profil / Pengaturan
                    </button>
                </a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Main Identity Card -->
                <div class="lg:col-span-12">
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-700 rounded-[2rem] p-6 text-white shadow-xl shadow-emerald-900/20 relative overflow-hidden group">
                        <div class="absolute right-0 top-0 w-96 h-96 bg-white/10 rounded-full blur-3xl -mr-20 -mt-20 group-hover:bg-white/15 transition-all duration-1000"></div>
                        <div class="absolute left-0 bottom-0 w-64 h-64 bg-emerald-400/20 rounded-full blur-3xl -ml-20 -mb-20"></div>

                        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div>
                                <p class="text-emerald-100 text-xs font-medium mb-2 bg-white/10 inline-block px-3 py-1 rounded-full backdrop-blur-md border border-white/10">Kode Afiliasi Anda</p>
                                <div class="flex items-center gap-3">
                                    <h2 class="text-3xl md:text-4xl font-bold tracking-wider font-mono text-white drop-shadow-sm"><?php echo $partner['affiliate_code']; ?></h2>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 bg-white/10 p-4 rounded-[1.5rem] backdrop-blur-md border border-white/10 min-w-[240px]">
                                <div class="h-12 w-12 rounded-full bg-white flex items-center justify-center text-emerald-700 font-bold text-lg shadow-lg ring-4 ring-white/20">
                                    <?php echo getInitials($partner['full_name']); ?>
                                </div>
                                <div>
                                    <p class="font-bold text-lg text-white"><?php echo $partner['full_name']; ?></p>
                                    <p class="text-xs text-emerald-100">Mitra sejak <?php echo $partner['created_at'] ? date('F Y', strtotime($partner['created_at'])) : '-'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Left Column: Biodata -->
                <div class="lg:col-span-6 space-y-6">
                    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm p-8">
                        <div class="flex items-center gap-3 pb-6 border-b border-gray-100 mb-6">
                            <div class="bg-emerald-50 p-3 rounded-2xl">
                                <i data-lucide="user" class="h-6 w-6 text-emerald-600"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-xl text-gray-900">Biodata Diri</h3>
                                <p class="text-sm text-slate-500">Informasi pribadi yang terdaftar</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700">Nama Lengkap</label>
                                    <input type="text" value="<?php echo $partner['full_name']; ?>" readonly class="w-full h-12 px-4 rounded-xl border border-slate-100 bg-slate-50 text-gray-900 focus:outline-none" />
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700">Tanggal Lahir</label>
                                    <input type="text" value="<?php echo formatDate($partner['birth_date']); ?>" readonly class="w-full h-12 px-4 rounded-xl border border-slate-100 bg-slate-50 text-gray-900 focus:outline-none" />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-gray-700">Alamat Email</label>
                                <input type="text" value="<?php echo $partner['email']; ?>" readonly class="w-full h-12 px-4 rounded-xl border border-slate-100 bg-slate-50 text-gray-900 focus:outline-none" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-gray-700">Nomor WhatsApp</label>
                                <input type="text" value="<?php echo $partner['whatsapp_number']; ?>" readonly class="w-full h-12 px-4 rounded-xl border border-slate-100 bg-slate-50 text-gray-900 focus:outline-none" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-gray-700">Alamat Lengkap</label>
                                <textarea readonly class="w-full min-h-[120px] px-4 py-3 rounded-2xl border border-slate-100 bg-slate-50 text-gray-600 focus:outline-none resize-none"><?php echo $partner['address']; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Bank Info -->
                <div class="lg:col-span-6 space-y-6">
                    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm p-8 h-full">
                        <div class="flex items-center gap-3 pb-6 border-b border-gray-100 mb-6">
                            <div class="bg-emerald-50 p-3 rounded-2xl">
                                <i data-lucide="credit-card" class="h-6 w-6 text-emerald-600"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-xl text-gray-900">Rekening Terdaftar</h3>
                                <p class="text-sm text-slate-500">Untuk keperluan pencairan komisi</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-gray-700">Nama Bank</label>
                                <input type="text" value="<?php echo $partner['bank_name']; ?>" readonly class="w-full h-12 px-4 rounded-xl border border-slate-100 bg-slate-50 text-gray-900 focus:outline-none" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-gray-700">Nomor Rekening</label>
                                <input type="text" value="<?php echo $partner['account_number']; ?>" readonly class="w-full h-12 px-4 rounded-xl border border-slate-100 bg-slate-50 text-gray-900 font-mono tracking-wide focus:outline-none" />
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-gray-700">Nama Pemilik Rekening</label>
                                <input type="text" value="<?php echo $partner['account_holder']; ?>" readonly class="w-full h-12 px-4 rounded-xl border border-slate-100 bg-slate-50 text-gray-900 focus:outline-none" />
                            </div>

                            <div class="p-6 bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl border border-emerald-100">
                                <p class="text-sm text-emerald-800 leading-relaxed">
                                    <span class="font-bold block mb-1">Catatan Penting:</span>
                                    Data rekening ini digunakan untuk pencairan komisi
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
