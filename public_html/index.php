<?php
require_once 'includes/config.php';
require_once 'includes/session.php';

// Jika sudah login, bisa langsung redirect atau biarkan tetap di landing page dengan tombol dashboard
// Untuk sekarang, biarkan tetap bisa diakses meskipun sudah login, 
// tapi tombol "Masuk" akan berubah menjadi "Dashboard" nanti jika diperlukan.
$isLoggedIn = isset($_SESSION['user_id']);
$dashboardLink = '';
if ($isLoggedIn) {
    $dashboardLink = ($_SESSION['role'] === 'admin') ? 'admin/index.php' : 'mitra/index.php';
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kemitraan - Sewlovely Homeset</title>
    <!-- Tailwind CSS (Static Fallback) -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Tailwind CSS (Dynamic CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        cream: { 50: '#FDFCFB', 100: '#F9F6F0', 200: '#EAE2D6' },
                        leaf: { 100: '#DDE5D4', 500: '#6B8068', 600: '#546A50', 700: '#3D4F39', 800: '#2A453A', 900: '#1A362D' },
                        bright: { 100: '#D1FAE5', 400: '#34D399', 500: '#10B981', 600: '#059669', 800: '#065F46' },
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #F9F6F0;
            color: #1A362D;
        }

        .glass-nav {
            background: rgba(249, 246, 240, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        @keyframes morph {
            0% {
                border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            }

            50% {
                border-radius: 30% 60% 70% 40% / 50% 60% 30% 60%;
            }

            100% {
                border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            }
        }

        .liquid-shape {
            animation: morph 8s ease-in-out infinite;
        }

        .liquid-shape-fast {
            animation: morph 5s ease-in-out infinite;
        }

        .liquid-shape-delayed {
            animation: morph 7s ease-in-out infinite;
            animation-delay: 2s;
        }

        .droplet {
            border-radius: 50% 50% 50% 0;
            transform: rotate(-45deg);
        }

        .droplet-content {
            transform: rotate(45deg);
        }

        @keyframes blob {
            0% {
                transform: translate(0px, 0px) scale(1);
            }

            33% {
                transform: translate(30px, -50px) scale(1.1);
            }

            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }

            100% {
                transform: translate(0px, 0px) scale(1);
            }
        }

        .animate-blob {
            animation: blob 10s infinite;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #F9F6F0;
        }

        ::-webkit-scrollbar-thumb {
            background: #1A362D;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #2A453A;
        }
    </style>
</head>

<body class="antialiased overflow-x-hidden selection:bg-bright-500 selection:text-white">

    <!-- Floating Navbar -->
    <div class="fixed w-full z-50 top-2 md:top-4 px-4 sm:px-6 lg:px-8 transition-all duration-500"
        id="navbar-container">
        <nav class="max-w-6xl mx-auto backdrop-blur-xl border border-white/40 bg-cream-100/80 rounded-full px-4 md:px-6 py-3 md:py-4 shadow-lg shadow-leaf-900/5 flex justify-between items-center transition-all duration-500"
            id="navbar">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center gap-2 md:gap-3">
                <div
                    class="w-10 h-10 md:w-12 md:h-12 bg-bright-500 rounded-full flex items-center justify-center liquid-shape-fast shadow-md shadow-bright-500/30">
                    <i data-lucide="blinds" class="h-5 w-5 md:h-6 md:w-6 text-white"></i>
                </div>
                <span class="font-bold text-lg md:text-xl tracking-tight text-leaf-900">Sewlovely</span>
            </div>

            <!-- Auth Buttons (Desktop) -->
            <div class="hidden md:flex items-center gap-6">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $dashboardLink; ?>"
                        class="text-sm font-semibold text-leaf-900 hover:text-bright-600 transition-colors">
                        Ke Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php" class="text-sm font-semibold text-leaf-900 hover:text-bright-600 transition-colors">
                        Masuk
                    </a>
                    <a href="register.php"
                        class="bg-leaf-900 hover:bg-bright-500 hover:text-white text-cream-100 px-7 py-3 rounded-full text-sm font-semibold shadow-lg shadow-leaf-900/20 transition-all hover:-translate-y-1">
                        Daftar Mitra
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden flex items-center">
                <button id="mobile-menu-btn"
                    class="text-leaf-900 hover:text-bright-600 focus:outline-none p-2 rounded-full bg-cream-200 transition-colors">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
            </div>
        </nav>

        <!-- Mobile Menu -->
        <div id="mobile-menu"
            class="md:hidden hidden absolute top-full left-4 right-4 mt-2 backdrop-blur-xl border border-white/40 bg-cream-100/90 rounded-[2rem] overflow-hidden transition-all duration-300 origin-top opacity-0 scale-y-95">
            <div class="px-4 py-6 flex flex-col gap-3">
                <?php if ($isLoggedIn): ?>
                    <a href="<?php echo $dashboardLink; ?>"
                        class="text-center px-4 py-3 text-base font-semibold text-leaf-900 bg-cream-200 rounded-full transition-colors">
                        Ke Dashboard
                    </a>
                <?php else: ?>
                    <a href="login.php"
                        class="text-center px-4 py-3 text-base font-semibold text-leaf-900 bg-cream-200 rounded-full transition-colors">
                        Masuk
                    </a>
                    <a href="register.php"
                        class="text-center bg-leaf-900 hover:bg-bright-500 hover:text-white text-cream-100 px-4 py-3 rounded-full text-base font-semibold shadow-lg transition-colors">
                        Daftar Mitra
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section
        class="relative pt-32 pb-20 md:pt-40 md:pb-24 lg:pt-52 lg:pb-32 overflow-hidden min-h-[90vh] flex items-center">
        <!-- Liquid Background Blobs -->
        <div
            class="absolute top-0 right-0 -translate-y-12 translate-x-1/3 w-[300px] h-[300px] md:w-[600px] md:h-[600px] bg-bright-100 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob">
        </div>
        <div
            class="absolute top-40 left-0 -translate-x-1/3 w-[250px] h-[250px] md:w-[500px] md:h-[500px] bg-[#EAE2D6] rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000">
        </div>
        <div
            class="absolute -bottom-20 left-1/3 w-[300px] h-[300px] md:w-[600px] md:h-[600px] bg-leaf-100 rounded-full mix-blend-multiply filter blur-3xl opacity-60 animate-blob animation-delay-4000">
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 md:gap-16 items-center">

                <!-- Hero Content -->
                <div class="max-w-2xl text-center lg:text-left pt-10 lg:pt-0">
                    <div
                        class="inline-flex items-center gap-2 px-3 md:px-4 py-1.5 md:py-2 rounded-full bg-cream-200/50 backdrop-blur-sm text-bright-600 font-semibold text-xs md:text-sm mb-6 md:mb-8 border border-white/40 shadow-sm mx-auto lg:mx-0">
                        <span class="w-2 h-2 md:w-2.5 md:h-2.5 rounded-full bg-bright-500 animate-pulse"></span>
                        Program Kemitraan Eksklusif
                    </div>

                    <h1 class="font-bold text-leaf-900 leading-[1.1] mb-4 md:mb-6">
                        <span class="block text-2xl md:text-4xl text-leaf-700 mb-2 font-medium">Pembeli Rumah
                            Baru</span>
                        <span class="block text-4xl sm:text-5xl md:text-6xl lg:text-[4.5rem] tracking-tight">Pasti Butuh
                            Gorden</span>
                    </h1>

                    <p
                        class="text-base md:text-lg lg:text-xl text-leaf-900/70 mb-8 md:mb-10 leading-relaxed max-w-lg mx-auto lg:mx-0">
                        Bergabunglah menjadi mitra affiliate resmi <span class="font-bold text-bright-600">Sewlovely
                            Homeset</span>. Tawarkan produk interior premium dan dapatkan <strong
                            class="text-leaf-800 border-b-2 border-bright-500">Komisi 5%</strong> dari setiap
                        penjualan.
                    </p>

                    <div class="flex flex-col sm:flex-row gap-4 md:gap-5 justify-center lg:justify-start items-center">
                        <a href="register.php"
                            class="bg-bright-500 hover:bg-bright-600 text-white px-6 py-3.5 md:px-8 md:py-4 rounded-full text-base md:text-lg font-semibold shadow-xl shadow-bright-500/20 transition-all hover:-translate-y-1 text-center flex items-center justify-center gap-2 group">
                            Mulai Sekarang <i data-lucide="arrow-right"
                                class="w-4 h-4 md:w-5 md:h-5 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                        <a href="#keuntungan"
                            class="bg-cream-100 hover:bg-cream-200 text-leaf-900 border border-leaf-900/10 px-6 py-3.5 md:px-8 md:py-4 rounded-full text-base md:text-lg font-semibold transition-all text-center">
                            Pelajari Lebih Lanjut
                        </a>
                    </div>
                </div>

                <!-- Hero Image -->
                <div class="relative w-full max-w-md md:max-w-lg mx-auto lg:ml-auto mt-8 lg:mt-0 group">
                    <!-- Decorative Liquid Border -->
                    <div
                        class="absolute inset-0 bg-gradient-to-tr from-leaf-800 to-bright-500 liquid-shape opacity-20 scale-105 blur-lg">
                    </div>

                    <div class="relative w-full aspect-[4/5] md:aspect-square bg-cream-200 liquid-shape shadow-2xl overflow-hidden border-4 md:border-8 border-white/50 backdrop-blur-sm z-10"
                        style="-webkit-mask-image: -webkit-radial-gradient(white, black);">
                        <img src="assets/images/bg_login.png" alt="Premium Interior"
                            class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-1000 relative z-0">
                    </div>

                    <!-- Floating Glass Badge -->
                    <div class="absolute bottom-6 md:bottom-10 -left-4 md:-left-12 glass-nav p-3 md:p-5 rounded-2xl md:rounded-[2rem] shadow-xl flex items-center gap-3 md:gap-4 animate-bounce z-20"
                        style="animation-duration: 3s;">
                        <div
                            class="w-10 h-10 md:w-14 md:h-14 bg-bright-500 rounded-full liquid-shape-delayed flex items-center justify-center flex-shrink-0">
                            <i data-lucide="percent" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
                        </div>
                        <div>
                            <p class="text-xs md:text-sm font-bold text-leaf-900">Komisi Tinggi</p>
                            <p class="text-[10px] md:text-xs font-medium text-leaf-700">5% per Proyek</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Keuntungan Section -->
    <section id="keuntungan" class="py-20 md:py-32 relative bg-white">
        <!-- Top Wave Transition -->
        <div class="absolute top-0 left-0 w-full overflow-hidden leading-none transform -translate-y-full">
            <svg class="relative block w-full h-[50px] md:h-[100px]" data-name="Layer 1"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path
                    d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V120H0V0C75.29,38.86,152.48,82.26,228.4,92.17,260.6,96.33,291.6,89.5,321.39,56.44Z"
                    fill="#ffffff"></path>
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-16 md:mb-20">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-leaf-900 mb-4 md:mb-6">Mengapa Menjadi Mitra
                    Kami?</h2>
                <p class="text-base md:text-lg text-leaf-700">Bukan sekadar kemitraan biasa, kami memberikan dukungan
                    penuh dan sistem bagi hasil yang mengalir lancar untuk Anda.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 md:gap-10">
                <!-- Card 1 -->
                <div
                    class="bg-cream-100 rounded-[2.5rem] md:rounded-[3rem] p-8 md:p-10 hover:bg-cream-200 transition-colors duration-500 group relative overflow-hidden">
                    <div
                        class="absolute top-0 right-0 w-24 h-24 md:w-32 md:h-32 bg-leaf-100 rounded-bl-full opacity-50 transform translate-x-8 -translate-y-8 group-hover:translate-x-0 group-hover:translate-y-0 transition-transform duration-500">
                    </div>
                    <div
                        class="w-16 h-16 md:w-20 md:h-20 bg-leaf-800 rounded-2xl md:rounded-[2rem] liquid-shape flex items-center justify-center mb-6 md:mb-8 relative z-10 group-hover:bg-bright-500 transition-colors duration-500">
                        <i data-lucide="wallet" class="w-7 h-7 md:w-8 md:h-8 text-cream-100"></i>
                    </div>
                    <h3 class="text-xl md:text-2xl font-bold text-leaf-900 mb-3 md:mb-4 relative z-10">Komisi 5%
                        Langsung</h3>
                    <p class="text-sm md:text-base text-leaf-700 leading-relaxed relative z-10">
                        Dapatkan komisi sebesar 5% dari total nilai proyek untuk setiap pelanggan yang Anda
                        referensikan. Tanpa syarat rumit.
                    </p>
                </div>

                <!-- Card 2 -->
                <div
                    class="bg-cream-100 rounded-[2.5rem] md:rounded-[3rem] p-8 md:p-10 hover:bg-cream-200 transition-colors duration-500 group relative overflow-hidden transform lg:-translate-y-8">
                    <div
                        class="absolute top-0 right-0 w-24 h-24 md:w-32 md:h-32 bg-bright-100 rounded-bl-full opacity-50 transform translate-x-8 -translate-y-8 group-hover:translate-x-0 group-hover:translate-y-0 transition-transform duration-500">
                    </div>
                    <div
                        class="w-16 h-16 md:w-20 md:h-20 bg-bright-500 rounded-2xl md:rounded-[2rem] liquid-shape-fast flex items-center justify-center mb-6 md:mb-8 relative z-10 group-hover:bg-leaf-800 transition-colors duration-500">
                        <i data-lucide="gift" class="w-7 h-7 md:w-8 md:h-8 text-white"></i>
                    </div>
                    <h3 class="text-xl md:text-2xl font-bold text-leaf-900 mb-3 md:mb-4 relative z-10">Bonus Tambahan
                    </h3>
                    <p class="text-sm md:text-base text-leaf-700 leading-relaxed relative z-10">
                        Nikmati berbagai bonus reward tambahan (uang tunai, voucher, hingga hadiah menarik) jika Anda
                        mencapai target penjualan tertentu.
                    </p>
                </div>

                <!-- Card 3 -->
                <div
                    class="bg-cream-100 rounded-[2.5rem] md:rounded-[3rem] p-8 md:p-10 hover:bg-cream-200 transition-colors duration-500 group relative overflow-hidden">
                    <div
                        class="absolute top-0 right-0 w-24 h-24 md:w-32 md:h-32 bg-leaf-100 rounded-bl-full opacity-50 transform translate-x-8 -translate-y-8 group-hover:translate-x-0 group-hover:translate-y-0 transition-transform duration-500">
                    </div>
                    <div
                        class="w-16 h-16 md:w-20 md:h-20 bg-leaf-800 rounded-2xl md:rounded-[2rem] liquid-shape-delayed flex items-center justify-center mb-6 md:mb-8 relative z-10 group-hover:bg-bright-500 transition-colors duration-500">
                        <i data-lucide="gem" class="w-7 h-7 md:w-8 md:h-8 text-cream-100"></i>
                    </div>
                    <h3 class="text-xl md:text-2xl font-bold text-leaf-900 mb-3 md:mb-4 relative z-10">Produk Premium
                    </h3>
                    <p class="text-sm md:text-base text-leaf-700 leading-relaxed relative z-10">
                        Anda akan merekomendasikan produk-produk interior berkualitas tinggi yang sangat mudah dijual
                        dan memiliki reputasi baik.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Cara Kerja Section -->
    <section id="cara-kerja" class="py-20 md:py-32 bg-leaf-900 relative">
        <!-- Liquid Top Curve -->
        <div class="absolute top-0 left-0 w-full overflow-hidden leading-none transform -translate-y-full">
            <svg class="relative block w-full h-[60px] md:h-[150px]" data-name="Layer 1"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path
                    d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z"
                    fill="#1A362D"></path>
            </svg>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-16 md:mb-24">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-cream-50 mb-4 md:mb-6">Cara Kerja Sederhana
                </h2>
                <p class="text-base md:text-lg text-cream-200/80">Hasilkan uang hanya dengan 3 langkah mudah. Kami yang
                    mengurus sisanya.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 md:gap-8 relative">
                <!-- Wavy Connect Line -->
                <div
                    class="hidden md:block absolute top-12 left-[15%] right-[15%] h-[2px] bg-gradient-to-r from-transparent via-bright-500 to-transparent opacity-50">
                </div>

                <!-- Step 1 -->
                <div class="relative text-center group">
                    <div
                        class="relative mx-auto w-24 h-24 md:w-28 md:h-28 mb-6 md:mb-8 transition-transform duration-500 group-hover:scale-110">
                        <div
                            class="w-full h-full bg-bright-500 rounded-[50%_50%_50%_0] -rotate-45 flex items-center justify-center shadow-xl shadow-bright-500/20 border border-white/10">
                            <div class="flex flex-col items-center rotate-45">
                                <i data-lucide="user-plus" class="w-8 h-8 md:w-10 md:h-10 text-white"></i>
                            </div>
                        </div>
                        <div
                            class="absolute -top-1 -right-1 md:-top-2 md:-right-2 w-8 h-8 md:w-10 md:h-10 bg-leaf-800 rounded-full text-white font-bold flex items-center justify-center border-[3px] md:border-4 border-leaf-900 shadow-lg text-sm md:text-base z-10">
                            1</div>
                    </div>
                    <h3 class="text-xl md:text-2xl font-bold text-cream-50 mb-3 md:mb-4">Daftar & Login</h3>
                    <p class="text-sm md:text-base text-cream-200/70 leading-relaxed px-2 md:px-4">Buat akun mitra Anda
                        secara gratis. Setelah disetujui, Anda dapat masuk ke dashboard kemitraan.</p>
                </div>

                <!-- Step 2 -->
                <div class="relative text-center group">
                    <div
                        class="relative mx-auto w-24 h-24 md:w-28 md:h-28 mb-6 md:mb-8 transition-transform duration-500 group-hover:scale-110">
                        <div
                            class="w-full h-full bg-bright-500 rounded-[50%_50%_0_50%] rotate-45 flex items-center justify-center shadow-xl shadow-bright-500/20 border border-white/10">
                            <div class="flex flex-col items-center -rotate-45">
                                <i data-lucide="share-2" class="w-8 h-8 md:w-10 md:h-10 text-white"></i>
                            </div>
                        </div>
                        <div
                            class="absolute -top-1 -right-1 md:-top-2 md:-right-2 w-8 h-8 md:w-10 md:h-10 bg-leaf-800 rounded-full text-white font-bold flex items-center justify-center border-[3px] md:border-4 border-leaf-900 shadow-lg text-sm md:text-base z-10">
                            2</div>
                    </div>
                    <h3 class="text-xl md:text-2xl font-bold text-cream-50 mb-3 md:mb-4">Referensi Klien</h3>
                    <p class="text-sm md:text-base text-cream-200/70 leading-relaxed px-2 md:px-4">Tugas anda hanya isi
                        data customer yang membutuhkan produk kami, lalu jadwalkan visit di aplikasi mitra affiliate.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="relative text-center group">
                    <div
                        class="relative mx-auto w-24 h-24 md:w-28 md:h-28 mb-6 md:mb-8 transition-transform duration-500 group-hover:scale-110">
                        <div
                            class="w-full h-full bg-bright-500 rounded-[50%_50%_50%_0] -rotate-45 flex items-center justify-center shadow-xl shadow-bright-500/20 border border-white/10">
                            <div class="flex flex-col items-center rotate-45">
                                <i data-lucide="banknote" class="w-8 h-8 md:w-10 md:h-10 text-white"></i>
                            </div>
                        </div>
                        <div
                            class="absolute -top-1 -right-1 md:-top-2 md:-right-2 w-8 h-8 md:w-10 md:h-10 bg-leaf-800 rounded-full text-white font-bold flex items-center justify-center border-[3px] md:border-4 border-leaf-900 shadow-lg text-sm md:text-base z-10">
                            3</div>
                    </div>
                    <h3 class="text-xl md:text-2xl font-bold text-cream-50 mb-3 md:mb-4">Terima Komisi</h3>
                    <p class="text-sm md:text-base text-cream-200/70 leading-relaxed px-2 md:px-4">Team kami akan visit
                        ke tempat klien yang anda referensikan, komisi 5% otomatis masuk ke anda setelah pemesanan dan
                        pembayaran selesai.</p>
                </div>
            </div>
        </div>
        <!-- Liquid Bottom Curve -->
        <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none transform translate-y-full z-20">
            <svg class="relative block w-full h-[60px] md:h-[100px] rotate-180" data-name="Layer 1"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path
                    d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z"
                    fill="#1A362D"></path>
            </svg>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 md:py-32 bg-leaf-100 relative overflow-hidden">
        <!-- Liquid Ornaments -->
        <div
            class="absolute top-0 right-0 w-[400px] h-[400px] md:w-[800px] md:h-[800px] bg-white rounded-full mix-blend-overlay filter blur-[60px] md:blur-[100px] opacity-60 translate-x-1/2 -translate-y-1/2">
        </div>
        <div
            class="absolute bottom-0 left-0 w-[300px] h-[300px] md:w-[600px] md:h-[600px] bg-bright-100 rounded-full mix-blend-multiply filter blur-[50px] md:blur-[80px] opacity-40 -translate-x-1/3 translate-y-1/3">
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
            <div
                class="w-20 h-20 md:w-24 md:h-24 bg-bright-500 rounded-full liquid-shape mx-auto mb-8 md:mb-10 flex items-center justify-center shadow-2xl shadow-bright-500/30 text-white">
                <i data-lucide="blinds" class="w-8 h-8 md:w-10 md:h-10"></i>
            </div>
            <h2
                class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold text-leaf-900 mb-6 md:mb-8 tracking-tight">
                Siap Memulai Perjalanan Sukses Anda?</h2>
            <p
                class="text-base md:text-lg lg:text-xl text-leaf-800 mb-10 md:mb-12 max-w-2xl mx-auto leading-relaxed px-2">
                Jangan lewatkan kesempatan untuk memiliki penghasilan tambahan tanpa batas dengan memasarkan produk
                interior berkualitas.
            </p>
            <a href="register.php"
                class="inline-flex items-center justify-center gap-2 md:gap-3 bg-bright-500 hover:bg-bright-600 text-white px-8 py-4 md:px-10 md:py-5 rounded-full text-base md:text-lg font-semibold shadow-2xl shadow-bright-500/20 transition-all hover:scale-105 group">
                Daftar Menjadi Mitra <i data-lucide="arrow-right"
                    class="w-4 h-4 md:w-5 md:h-5 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-leaf-900 text-cream-100 pt-12 pb-8 md:pt-16 md:pb-10 relative z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-center items-center">
                <div class="text-cream-200/60 text-xs md:text-sm font-medium text-center">
                    &copy; <?php echo date('Y'); ?> Sewlovely Homeset. All rights reserved.
                </div>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        // Mobile Menu Toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuBtn.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.contains('hidden');
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                setTimeout(() => {
                    mobileMenu.classList.remove('opacity-0', 'scale-y-95');
                    mobileMenu.classList.add('opacity-100', 'scale-y-100');
                }, 10);
            } else {
                mobileMenu.classList.remove('opacity-100', 'scale-y-100');
                mobileMenu.classList.add('opacity-0', 'scale-y-95');
                setTimeout(() => {
                    mobileMenu.classList.add('hidden');
                }, 300);
            }
        });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const container = document.getElementById('navbar-container');
            const nav = document.getElementById('navbar');
            if (window.scrollY > 20) {
                // Morph into full-width sticky bar
                container.className = 'fixed w-full z-50 transition-all duration-500 top-0';
                nav.className = 'w-full max-w-full mx-auto backdrop-blur-xl bg-cream-50/95 px-6 md:px-12 lg:px-20 py-3 shadow-md flex justify-between items-center transition-all duration-500';
            } else {
                // Morph back to floating pill
                container.className = 'fixed w-full z-50 transition-all duration-500 top-2 md:top-4 px-4 sm:px-6 lg:px-8';
                nav.className = 'w-full max-w-6xl mx-auto backdrop-blur-xl border border-white/40 bg-cream-100/80 rounded-full px-4 md:px-6 py-3 md:py-4 shadow-lg shadow-leaf-900/5 flex justify-between items-center transition-all duration-500';
            }
        });
    </script>
</body>

</html>