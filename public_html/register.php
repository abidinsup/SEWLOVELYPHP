<?php
require_once 'includes/session.php';
require_once 'includes/config.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: mitra/index.php");
    }
    exit;
}

$message = '';
$message_type = ''; // 'error' or 'success'

// Nomor WA Admin Sewlovely (ganti dengan nomor WA aktif)
define('ADMIN_WA', '6285159588681');

$wa_redirect = false;
$wa_data     = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name             = trim($_POST['name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $phone            = trim($_POST['phone'] ?? '');
    $birth_date       = $_POST['birth_date'] ?? null;
    $address          = trim($_POST['address'] ?? '');
    $bank_name_select = trim($_POST['bank_name'] ?? '');
    $bank_name_custom = trim($_POST['bank_name_custom'] ?? '');
    // Gunakan nilai ketikan jika pilih 'Lainnya'
    $bank_name        = ($bank_name_select === 'Lainnya' && !empty($bank_name_custom)) ? $bank_name_custom : $bank_name_select;
    $account_number   = trim($_POST['account_number'] ?? '');
    $account_holder   = trim($_POST['account_holder'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($address)) {
        $message = 'Mohon lengkapi semua field yang diwajibkan!';
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Konfirmasi password tidak cocok!';
        $message_type = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('Email sudah terdaftar!');
            }

            $affiliate_code = 'AFF-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $name), 0, 4)) . rand(100, 999);

            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, 'mitra')");
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$email, $password_hash]);
            $user_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO partners (user_id, full_name, whatsapp_number, birth_date, address, bank_name, account_number, account_holder, affiliate_code, is_active, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'pending')");
            $stmt->execute([$user_id, $name, $phone, $birth_date ?: null, $address, $bank_name ?: null, $account_number ?: null, $account_holder ?: null, $affiliate_code]);

            $pdo->commit();

            // Siapkan data untuk redirect WA
            $wa_redirect = true;
            $wa_data = [
                'name'           => $name,
                'email'          => $email,
                'phone'          => $phone,
                'birth_date'     => $birth_date ? date('d/m/Y', strtotime($birth_date)) : '-',
                'address'        => $address,
                'bank_name'      => $bank_name ?: '-',
                'account_number' => $account_number ?: '-',
                'account_holder' => $account_holder ?: '-',
                'affiliate_code' => $affiliate_code,
            ];
            $message = 'Pendaftaran berhasil! Mengarahkan ke WhatsApp untuk konfirmasi...';
            $message_type = 'success';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = $e->getMessage();
            if(strpos($message, 'SQLSTATE') !== false) {
                $message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Daftar Kemitraan - Sewlovely Homeset</title>
    <!-- Tailwind CSS (Static Fallback) -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Tailwind CSS (Dynamic CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Smooth scroll & prevent bounce on iOS */
        html { scroll-behavior: smooth; -webkit-overflow-scrolling: touch; }
        /* Fix iOS input zoom */
        input, select, textarea { font-size: 16px !important; }
        @media (min-width: 768px) {
            input, select, textarea { font-size: inherit !important; }
        }
        /* Sembunyikan icon kalender bawaan browser */
        input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0;
            position: absolute;
            right: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-start md:items-center justify-center p-0 md:p-6 lg:p-8 relative overflow-x-hidden overflow-y-auto">

    <!-- Decorative Background Shapes -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-emerald-400/20 blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-blue-400/20 blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-6xl bg-white md:bg-white/80 md:backdrop-blur-2xl rounded-none md:rounded-[2.5rem] shadow-none md:shadow-[0_32px_64px_-16px_rgba(0,0,0,0.1)] overflow-hidden flex flex-col md:flex-row-reverse relative z-10 md:my-auto md:border border-white/50 min-h-screen md:min-h-0">
        
        <!-- Right Side: Branding (Premium Image & Glass) -->
        <div class="w-full md:w-[40%] relative overflow-hidden text-white hidden md:flex min-h-[700px] group">
            <!-- Background Image -->
            <div class="absolute inset-0 scale-105 group-hover:scale-100 transition-transform duration-1000">
                <img src="assets/images/bg_login.png" alt="Premium Interior" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-tl from-teal-900/90 via-teal-800/60 to-transparent"></div>
            </div>

            <div class="relative z-10 p-10 lg:p-14 flex flex-col justify-center items-center text-center w-full">
                <div class="mb-10 flex flex-col items-center">
                    <a href="login.php" class="inline-flex items-center gap-2 text-white/70 hover:text-white mb-10 transition-colors font-bold text-sm bg-white/10 backdrop-blur-md px-4 py-2 rounded-full border border-white/20">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i> Kembali
                    </a>
                    <div class="w-20 h-20 bg-white/10 backdrop-blur-xl rounded-3xl flex items-center justify-center mb-8 border border-white/20 shadow-2xl transition-transform hover:scale-110 duration-500">
                        <i data-lucide="home" class="h-10 w-10 text-white"></i>
                    </div>
                    <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight mb-4 leading-tight whitespace-nowrap drop-shadow-lg">
                        Sewlovely Homeset
                    </h1>
                    <p class="text-teal-50/90 text-base lg:text-lg font-medium leading-relaxed drop-shadow-md whitespace-nowrap">
                        Platform kemitraan untuk produk home interior
                    </p>
                </div>

                <div class="space-y-6 w-full max-w-sm">
                    <div class="flex items-start gap-4 bg-white/10 backdrop-blur-lg p-4 rounded-2xl border border-white/10 shadow-xl">
                        <div class="bg-emerald-500/20 p-2 rounded-xl border border-emerald-400/30">
                            <i data-lucide="wallet" class="h-5 w-5 text-emerald-300"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm">Komisi Menarik</h4>
                            <p class="text-xs text-white/60">Keuntungan langsung dari setiap penjualan.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4 bg-white/10 backdrop-blur-lg p-4 rounded-2xl border border-white/10 shadow-xl">
                        <div class="bg-blue-500/20 p-2 rounded-xl border border-blue-400/30">
                            <i data-lucide="bar-chart-3" class="h-5 w-5 text-blue-300"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm">Sistem Transparan</h4>
                            <p class="text-xs text-white/60">Pantau semua data via dashboard real-time.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Left Side: Register Form (Clean & Spacious) -->
        <div class="w-full md:w-[60%] px-5 py-8 md:p-12 lg:p-16 flex flex-col justify-center bg-white md:bg-white/60 relative z-10 overflow-y-auto">
            <!-- Mobile Branding -->
            <div class="md:hidden flex items-center justify-between mb-6">
                <a href="login.php" class="text-slate-500 p-2 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                    <i data-lucide="arrow-left" class="h-5 w-5"></i>
                </a>
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 bg-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-600/20">
                        <i data-lucide="home" class="h-4 w-4 text-white"></i>
                    </div>
                    <span class="font-black text-slate-900 text-sm tracking-tight">Sewlovely</span>
                </div>
            </div>

            <div class="mb-6 md:mb-10 text-center md:text-left">
                <h2 class="text-2xl md:text-3xl lg:text-4xl font-black text-slate-900 mb-1 md:mb-2 tracking-tight">Pendaftaran Mitra</h2>
                <p class="text-slate-500 font-medium text-sm md:text-base">Isi form di bawah ini untuk membuat akun baru</p>
            </div>

            <?php if($message): ?>
                <div class="p-4 rounded-2xl mb-8 text-sm font-bold flex items-center gap-3 border <?php echo $message_type === 'error' ? 'bg-red-50 text-red-600 border-red-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'; ?>">
                    <i data-lucide="<?php echo $message_type === 'error' ? 'alert-circle' : 'check-circle'; ?>" class="h-5 w-5"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="space-y-4 md:space-y-6">
                
                <div class="space-y-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">Nama Lengkap</label>
                    <div class="relative group">
                        <i data-lucide="user" class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                        <input type="text" name="name" required placeholder="Budi Santoso" class="w-full h-14 md:h-16 pl-14 pr-6 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm md:text-base" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">Alamat Email</label>
                        <div class="relative group">
                            <i data-lucide="mail" class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                            <input type="email" name="email" required placeholder="nama@email.com" class="w-full h-14 md:h-16 pl-14 pr-6 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm md:text-base" />
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">No. WhatsApp</label>
                        <div class="flex shadow-sm rounded-2xl overflow-hidden border border-slate-200 focus-within:border-emerald-500 focus-within:ring-4 focus-within:ring-emerald-500/10 transition-all bg-slate-50/50 focus-within:bg-white group">
                            <!-- Separate Prefix Box -->
                            <div class="flex items-center gap-2 px-5 bg-slate-100 border-r border-slate-200 text-slate-500 font-bold text-sm md:text-base select-none shrink-0">
                                <i data-lucide="phone" class="h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                                <span>+62</span>
                            </div>
                            <!-- Actual Number Input -->
                            <input type="tel" id="phone_display" required placeholder="Contoh: 8123456789"
                                class="w-full h-14 md:h-16 px-4 bg-transparent border-0 focus:outline-none focus:ring-0 font-medium text-slate-900 text-sm md:text-base" />
                            <!-- Hidden input to submit the full number -->
                            <input type="hidden" name="phone" id="phone" value="+62" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">Tanggal Lahir</label>
                        <div class="relative group cursor-pointer" onclick="this.querySelector('input[type=date]').showPicker ? this.querySelector('input[type=date]').showPicker() : this.querySelector('input[type=date]').focus()">
                            <i data-lucide="calendar" class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors pointer-events-none"></i>
                            <input type="date" name="birth_date" id="birth_date" class="w-full h-14 md:h-16 pl-14 pr-6 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm md:text-base cursor-pointer" />
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">Alamat Lengkap</label>
                        <div class="relative group">
                            <i data-lucide="map-pin" class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                            <input type="text" name="address" required placeholder="Jl. Raya No. 123, Jakarta" class="w-full h-14 md:h-16 pl-14 pr-6 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm md:text-base" />
                        </div>
                    </div>
                </div>

                <!-- Divider Info Rekening -->
                <div class="relative flex items-center gap-3 md:gap-4 py-1 md:py-2">
                    <div class="flex-1 h-px bg-slate-200"></div>
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-50 border border-amber-200 rounded-full">
                        <i data-lucide="landmark" class="h-4 w-4 text-amber-600"></i>
                        <span class="text-[10px] md:text-[11px] font-black text-amber-700 uppercase tracking-wider whitespace-nowrap">Info Pencairan Komisi</span>
                    </div>
                    <div class="flex-1 h-px bg-slate-200"></div>
                </div>

                <!-- Catatan Rekening -->
                <div class="flex items-start gap-3 p-3 md:p-4 bg-amber-50 border border-amber-200 rounded-2xl">
                    <i data-lucide="info" class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5"></i>
                    <p class="text-xs md:text-sm text-amber-800 font-medium leading-relaxed">
                        Isi data rekening bank di bawah ini untuk <strong>pencairan komisi</strong> dari hasil penjualan Anda. Pastikan data rekening benar agar proses pencairan berjalan lancar.
                    </p>
                </div>

                <!-- Pilihan Bank -->
                <div class="space-y-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">Pilihan Bank</label>
                    <div class="relative group">
                        <i data-lucide="landmark" class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors pointer-events-none"></i>
                        <select name="bank_name" id="bankSelect" onchange="toggleCustomBank(this.value)" class="w-full h-14 md:h-16 pl-14 pr-6 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm md:text-base appearance-none cursor-pointer">
                            <option value="">-- Pilih Bank --</option>
                            <option value="BCA">BCA - Bank Central Asia</option>
                            <option value="BRI">BRI - Bank Rakyat Indonesia</option>
                            <option value="BNI">BNI - Bank Negara Indonesia</option>
                            <option value="Mandiri">Bank Mandiri</option>
                            <option value="BSI">BSI - Bank Syariah Indonesia</option>
                            <option value="CIMB Niaga">CIMB Niaga</option>
                            <option value="Danamon">Bank Danamon</option>
                            <option value="Permata">Bank Permata</option>
                            <option value="BTN">BTN - Bank Tabungan Negara</option>
                            <option value="Jago">Bank Jago</option>
                            <option value="SeaBank">SeaBank</option>
                            <option value="GoPay">GoPay</option>
                            <option value="OVO">OVO</option>
                            <option value="Dana">DANA</option>
                            <option value="ShopeePay">ShopeePay</option>
                            <option value="Lainnya">✏️ Lainnya (ketik nama bank)</option>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 pointer-events-none"></i>
                    </div>
                    <!-- Input ketik nama bank (muncul jika pilih Lainnya) -->
                    <div id="customBankWrapper" class="hidden">
                        <div class="relative group">
                            <i data-lucide="pencil" class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-amber-500 pointer-events-none"></i>
                            <input type="text" name="bank_name_custom" id="bankNameCustom" placeholder="Ketik nama bank / e-wallet Anda..." class="w-full h-14 md:h-16 pl-14 pr-6 bg-amber-50 border border-amber-300 focus:bg-white focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm md:text-base" />
                        </div>
                        <p class="text-xs text-amber-600 font-medium pl-1 mt-1">⚠️ Ketik nama bank atau e-wallet yang tidak ada di daftar di atas.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">Nomor Rekening / Akun</label>
                        <div class="relative group">
                            <i data-lucide="credit-card" class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                            <input type="text" name="account_number" placeholder="Contoh: 1234567890" inputmode="numeric" class="w-full h-14 md:h-16 pl-14 pr-6 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm md:text-base" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">Nama Pemilik Rekening</label>
                        <div class="relative group">
                            <i data-lucide="user-check" class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                            <input type="text" name="account_holder" placeholder="Sesuai buku tabungan" class="w-full h-14 md:h-16 pl-14 pr-6 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm md:text-base" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">Password Baru</label>
                        <div class="relative group">
                            <i data-lucide="lock" class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                            <input type="password" name="password" id="password" required placeholder="Min. 6 Karakter" class="w-full h-14 md:h-16 pl-14 pr-14 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm md:text-base" />
                            <button type="button" onclick="togglePassword('password', 'eyeIcon1')" class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 transition-colors p-1">
                                <i data-lucide="eye" id="eyeIcon1" class="h-5 w-5"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">Ulangi Password</label>
                        <div class="relative group">
                            <i data-lucide="lock" class="absolute left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                            <input type="password" name="confirm_password" id="confirm_password" required placeholder="Min. 6 Karakter" class="w-full h-14 md:h-16 pl-14 pr-14 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm md:text-base" />
                            <button type="button" onclick="togglePassword('confirm_password', 'eyeIcon2')" class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 transition-colors p-1">
                                <i data-lucide="eye" id="eyeIcon2" class="h-5 w-5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-2 md:pt-4">
                    <button type="submit" id="submitBtn" class="w-full h-14 md:h-16 bg-emerald-600 hover:bg-emerald-700 text-white rounded-[1.5rem] font-bold text-lg shadow-2xl shadow-emerald-600/30 transition-all hover:-translate-y-1 active:scale-[0.98] flex items-center justify-center gap-3">
                        <i data-lucide="user-plus" class="h-6 w-6"></i> Daftar Kemitraan
                    </button>
                </div>
            </form>

            <div class="mt-6 md:mt-10 text-center text-sm font-medium text-slate-500 pb-8 md:pb-0">
                Sudah memiliki akun? 
                <a href="login.php" class="text-emerald-600 font-black hover:text-emerald-700 hover:underline underline-offset-4">Masuk Sekarang</a>
            </div>

        </div>
    </div>

    <?php if ($wa_redirect && !empty($wa_data)): ?>
    <!-- Modal Diproses + Redirect WA -->
    <div id="successModal" class="fixed inset-0 z-50 flex items-end md:items-center justify-center bg-black/60 backdrop-blur-sm">
        <div class="bg-white rounded-t-3xl md:rounded-3xl shadow-2xl max-w-md w-full p-6 md:p-8 text-center">
            <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i data-lucide="clock" class="h-10 w-10 text-amber-500"></i>
            </div>
            <h3 class="text-2xl font-black text-slate-900 mb-2">Pendaftaran Diproses</h3>
            <p class="text-slate-500 font-medium mb-1">Data Anda telah kami terima.</p>
            <p class="text-slate-400 text-sm mb-8">Silakan kirim konfirmasi via WhatsApp agar tim kami dapat segera memverifikasi dan mengaktifkan akun Anda.</p>
            <div id="waButtonContainer">
                <button onclick="redirectToWA()" class="w-full h-14 bg-green-500 hover:bg-green-600 text-white rounded-2xl font-bold text-base flex items-center justify-center gap-3 transition-all hover:-translate-y-0.5 shadow-lg shadow-green-500/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.553 4.103 1.523 5.824L0 24l6.338-1.51A11.95 11.95 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 0 1-5.001-1.373l-.36-.214-3.727.888.939-3.619-.235-.372A9.796 9.796 0 0 1 2.182 12C2.182 6.578 6.578 2.182 12 2.182S21.818 6.578 21.818 12 17.422 21.818 12 21.818z"/></svg>
                    Kirim Konfirmasi via WhatsApp
                </button>
                <p class="text-xs text-slate-400 mt-4">Setelah mengirim pesan, tutup WhatsApp dan kembali ke sini.</p>
            </div>
            
            <div id="doneContainer" class="hidden mt-4 animate-fade-in">
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-100 mb-6">
                    <p class="text-sm font-medium text-emerald-700">✅ Jendela WhatsApp telah dibuka. Jika pesan belum terkirim, klik tombol WhatsApp lagi di bawah ini, atau jika sudah selesai silakan kembali ke halaman Login.</p>
                </div>
                <a href="login.php" class="w-full h-14 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl font-bold text-base flex items-center justify-center transition-all hover:-translate-y-0.5 shadow-lg shadow-slate-900/30 mb-3">
                    Selesai & Kembali ke Login
                </a>
                <button onclick="window.open(window.lastWaUrl, '_blank')" class="text-sm font-bold text-emerald-600 hover:text-emerald-700 underline underline-offset-4">
                    Kirim ulang pesan WA
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        lucide.createIcons();

        function togglePassword(inputId, iconId) {
            const pwInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            if (pwInput.type === 'password') {
                pwInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                pwInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        function toggleCustomBank(val) {
            const wrapper = document.getElementById('customBankWrapper');
            const input   = document.getElementById('bankNameCustom');
            if (val === 'Lainnya') {
                wrapper.classList.remove('hidden');
                input.required = true;
                input.focus();
            } else {
                wrapper.classList.add('hidden');
                input.required = false;
                input.value = '';
            }
        }

        // Formatting No. WhatsApp dengan kotak terpisah
        const phoneDisplay = document.getElementById('phone_display');
        const phoneHidden = document.getElementById('phone');
        if (phoneDisplay && phoneHidden) {
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

        <?php if ($wa_redirect && !empty($wa_data)): ?>
        function redirectToWA() {
            const adminWA = '<?php echo ADMIN_WA; ?>';
            const barisPesan = [
                "*PENDAFTARAN MITRA BARU - SEWLOVELY HOMESET*",
                "",
                "Halo Admin, saya ingin mendaftarkan diri sebagai Mitra Sewlovely. Berikut data saya:",
                "",
                "*DATA DIRI*",
                "```",
                "Nama Lengkap  : <?php echo addslashes($wa_data['name']); ?>",
                "Email         : <?php echo addslashes($wa_data['email']); ?>",
                "No. WhatsApp  : <?php echo addslashes($wa_data['phone']); ?>",
                "Tanggal Lahir : <?php echo addslashes($wa_data['birth_date']); ?>",
                "Alamat        : <?php echo addslashes($wa_data['address']); ?>",
                "```",
                "",
                "*DATA REKENING*",
                "```",
                "Bank          : <?php echo addslashes($wa_data['bank_name']); ?>",
                "No. Rekening  : <?php echo addslashes($wa_data['account_number']); ?>",
                "Nama Pemilik  : <?php echo addslashes($wa_data['account_holder']); ?>",
                "```",
                "",
                "*KODE AFILIASI*",
                "```",
                "Kode          : <?php echo addslashes($wa_data['affiliate_code']); ?>",
                "```",
                "",
                "Mohon segera diverifikasi. Terima kasih!"
            ];
            const pesan = encodeURIComponent(barisPesan.join('\n'));
            const url = 'https://wa.me/' + adminWA + '?text=' + pesan;
            window.lastWaUrl = url;
            
            // Buka tab WhatsApp
            window.open(url, '_blank');

            // Sembunyikan tombol WA, munculkan tombol selesai
            document.getElementById('waButtonContainer').style.display = 'none';
            document.getElementById('doneContainer').classList.remove('hidden');
        }
        // Auto buka modal
        document.getElementById('successModal')?.scrollIntoView({ behavior: 'smooth' });
        <?php endif; ?>
    </script>
</body>
</html>
