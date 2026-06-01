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

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan Password harus diisi!';
    } else {
        $stmt = $pdo->prepare("
            SELECT u.id, u.email, u.password_hash, u.role, p.is_active, p.status
            FROM users u
            LEFT JOIN partners p ON u.id = p.user_id
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if partner is active
            if ($user['role'] === 'mitra' && $user['is_active'] == 0) {
                if ($user['status'] === 'pending') {
                    $error = 'Akun Anda sedang dalam proses verifikasi oleh admin. Mohon tunggu informasi selanjutnya.';
                } else {
                    $error = 'Akun Anda dinonaktifkan atau ditolak. Silakan hubungi admin.';
                }
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                if ($user['role'] === 'admin') {
                    header("Location: admin/index.php");
                    exit;
                } else {
                    header("Location: mitra/index.php");
                    exit;
                }
            }
        } else {
            $error = 'Email atau password salah!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sewlovely Homeset</title>
    <!-- Tailwind CSS (Static Fallback) -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Tailwind CSS (Dynamic CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 md:p-6 lg:p-8 relative overflow-x-hidden overflow-y-auto">

    <!-- Decorative Background Shapes -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-emerald-400/20 blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-blue-400/20 blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-5xl bg-white/80 backdrop-blur-2xl rounded-[2rem] lg:rounded-[2.5rem] shadow-[0_32px_64px_-16px_rgba(0,0,0,0.1)] overflow-hidden flex flex-col md:flex-row relative z-10 my-4 md:my-auto border border-white/50">
        
        <!-- Left Side: Branding & Info (Premium Image & Glass) -->
        <div class="w-full md:w-1/2 relative overflow-hidden text-white hidden md:flex min-h-[450px] lg:min-h-[600px] group">
            <!-- Background Image with Parallax-ish Effect -->
            <div class="absolute inset-0 scale-105 group-hover:scale-100 transition-transform duration-1000">
                <img src="assets/images/bg_login.png" alt="Premium Interior" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-tr from-emerald-900/90 via-emerald-800/60 to-transparent"></div>
            </div>

            <!-- Content Overlay -->
            <div class="relative z-10 p-12 lg:p-16 flex flex-col justify-center items-center text-center w-full">
                <div class="mb-10 flex flex-col items-center">
                    <div class="w-20 h-20 bg-white/10 backdrop-blur-xl rounded-3xl flex items-center justify-center mb-8 border border-white/20 shadow-2xl transition-transform hover:scale-110 duration-500">
                        <i data-lucide="blinds" class="h-10 w-10 text-white"></i>
                    </div>
                    <h1 class="text-3xl lg:text-4xl font-extrabold tracking-tight mb-4 leading-tight whitespace-nowrap drop-shadow-lg">
                        Sewlovely Homeset
                    </h1>
                    <p class="text-emerald-50/90 text-base md:text-lg font-medium leading-relaxed drop-shadow-md whitespace-nowrap">
                        Platform kemitraan untuk produk home interior
                    </p>
                </div>

                <div class="w-full max-w-md">
                    <div class="bg-white/10 backdrop-blur-xl border border-white/20 p-6 rounded-[2rem] shadow-2xl relative overflow-hidden group/card">
                        <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-emerald-400/20 rounded-full blur-3xl group-hover/card:scale-150 transition-transform duration-700"></div>
                        <p class="font-semibold text-white/90 text-lg leading-relaxed relative z-10">
                            Bergabung menjadi mitra dan dapatkan komisi dari setiap penjualan.
                        </p>
                        <div class="flex items-center gap-3 mt-6 relative z-10">
                            <div class="w-10 h-10 rounded-full bg-emerald-500/80 flex items-center justify-center border border-white/30 backdrop-blur-sm">
                                <i data-lucide="check" class="h-5 w-5 text-white"></i>
                            </div>
                            <span class="text-xs font-black tracking-[0.2em] uppercase text-white/80">MITRA SEWLOVELY</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Login Form (Clean & Modern) -->
        <div class="w-full md:w-1/2 p-6 sm:p-8 md:p-12 lg:p-16 flex flex-col justify-center bg-white/60 relative z-10">
            <!-- Mobile Branding -->
            <div class="md:hidden flex flex-col items-center mb-8 text-center">
                <div class="w-16 h-16 bg-emerald-600 rounded-[1.5rem] flex items-center justify-center mb-4 shadow-2xl shadow-emerald-600/30">
                    <i data-lucide="blinds" class="h-8 w-8 text-white"></i>
                </div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Sewlovely Homeset</h1>
            </div>

            <div class="mb-8 md:mb-10 text-center md:text-left">
                <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 mb-2 tracking-tight">Selamat Datang</h2>
                <p class="text-slate-500 font-medium text-sm sm:text-base">Masuk ke dashboard akun Anda</p>
            </div>

            <?php if($error): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-2xl mb-8 text-sm font-bold flex items-center gap-3 border border-red-100 animate-pulse">
                    <i data-lucide="alert-circle" class="h-5 w-5"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em] pl-1">Alamat Email</label>
                    <div class="relative group">
                        <i data-lucide="mail" class="absolute left-4 sm:left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                        <input type="email" name="email" required placeholder="nama@email.com" class="w-full h-14 sm:h-16 pl-12 sm:pl-14 pr-6 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl sm:rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm sm:text-base" />
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between pl-1">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-[0.1em]">Password</label>
                        <a href="#" class="text-[11px] font-black text-emerald-600 hover:text-emerald-700 transition-colors">Lupa Password?</a>
                    </div>
                    <div class="relative group">
                        <i data-lucide="lock" class="absolute left-4 sm:left-5 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 group-focus-within:text-emerald-500 transition-colors"></i>
                        <input type="password" name="password" id="password" required placeholder="••••••••" class="w-full h-14 sm:h-16 pl-12 sm:pl-14 pr-12 sm:pr-14 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl sm:rounded-2xl transition-all font-medium text-slate-900 shadow-sm text-sm sm:text-base" />
                        <button type="button" onclick="togglePassword()" class="absolute right-4 sm:right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 transition-colors p-1">
                            <i data-lucide="eye" id="eyeIcon" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-2 sm:pt-4">
                    <button type="submit" class="w-full h-14 sm:h-16 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl sm:rounded-[1.5rem] font-bold text-base sm:text-lg shadow-xl sm:shadow-2xl shadow-emerald-600/30 transition-all hover:-translate-y-1 active:scale-[0.98] flex items-center justify-center gap-2 sm:gap-3">
                        Masuk Dashboard <i data-lucide="arrow-right" class="h-5 w-5 sm:h-6 sm:w-6"></i>
                    </button>
                </div>
            </form>

            <div class="mt-10 text-center text-sm font-medium text-slate-500">
                Belum punya akun Mitra? 
                <a href="register.php" class="text-emerald-600 font-black hover:text-emerald-700 hover:underline underline-offset-4">Daftar Sekarang</a>
            </div>



        </div>
    </div>

    <script>
        lucide.createIcons();

        function togglePassword() {
            const pwInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (pwInput.type === 'password') {
                pwInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                pwInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }
    </script>
</body>
</html>
