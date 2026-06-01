<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkMitra();

$page_title = "Dashboard Mitra";
include '../includes/header.php';
include '../includes/sidebar_mitra.php';

// Fetch Mitra Data
$stmt = $pdo->prepare("SELECT full_name FROM partners WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$partner = $stmt->fetch();

$display_name = $partner ? $partner['full_name'] : $_SESSION['email'];
$initials = strtoupper(substr($display_name, 0, 1) . substr(strpos($display_name, ' ') !== false ? substr($display_name, strpos($display_name, ' ') + 1) : '', 0, 1));
?>

<div class="flex-1 flex flex-col min-h-screen w-full overflow-x-hidden">
    <!-- Padding top for mobile header -->
    <main class="flex-1 p-4 md:p-8 pt-20 md:pt-8 w-full max-w-full">
        <div class="max-w-7xl mx-auto space-y-8 pb-20">
            
            <!-- Header Mobile -->
            <div class="flex items-center justify-between md:hidden mb-2">
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-10 bg-emerald-100 rounded-full overflow-hidden border-2 border-white shadow-sm relative flex items-center justify-center">
                        <span class="text-emerald-700 font-bold"><?php echo $initials; ?></span>
                        <span class="absolute bottom-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full"></span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Selamat Datang,</p>
                        <h2 class="text-base font-bold text-gray-900">Halo, <?php echo $display_name; ?>!</h2>
                    </div>
                </div>
            </div>

            <!-- Promo Banner -->
            <div class="relative overflow-hidden rounded-[1.5rem] bg-[#064e3b] text-white p-5 md:p-8 shadow-xl shadow-emerald-900/10 group">
                <div class="absolute right-0 top-0 w-40 h-40 md:w-80 md:h-80 opacity-10 transition-transform duration-1000 group-hover:scale-110">
                    <i data-lucide="sparkles" class="w-full h-full text-white"></i>
                </div>
                <div class="absolute -left-8 -bottom-8 w-24 h-24 bg-emerald-500/20 rounded-full blur-2xl"></div>

                <div class="relative z-10 space-y-2 max-w-[95%] md:max-w-[80%]">
                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[10px] md:text-xs font-bold tracking-wide uppercase border border-emerald-500/30 backdrop-blur-sm">
                        Info Mitra
                    </span>
                    <h3 class="text-xl md:text-3xl lg:text-4xl font-bold leading-tight tracking-tight whitespace-pre-line">Raih Bonusnya! 
Selesaikan 5 Pemasangan</h3>
                    <p class="text-emerald-100/90 text-sm md:text-base max-w-lg mt-2">
                        Selesaikan 5 projek pemasangan dan dapatkan bonus komisi tambahan <span class="font-bold text-yellow-300">Rp 300.000</span>
                    </p>
                </div>
            </div>

            <!-- Categories -->
            <div class="space-y-8">
                <div class="flex items-center justify-between px-1">
                    <h3 class="font-bold text-2xl md:text-3xl text-gray-900 tracking-tight">Produk Gorden</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-3 gap-6 md:gap-8">
                    <!-- Gorden Rumah -->
                    <a href="calculator.php?type=rumah" class="block h-full">
                        <div class="bg-white rounded-[2rem] p-6 md:p-10 flex flex-row sm:flex-col items-center sm:justify-center gap-6 border border-slate-100 shadow-sm hover:shadow-2xl hover:shadow-emerald-900/5 hover:border-emerald-200 hover:-translate-y-2 transition-all duration-300 cursor-pointer group h-full relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-white/0 to-current opacity-5 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-150 text-emerald-600 bg-emerald-50"></div>
                            <div class="w-16 h-16 md:w-24 md:h-24 rounded-3xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:rotate-3 shadow-md group-hover:shadow-lg text-emerald-600 bg-emerald-50">
                                <i data-lucide="home" class="h-8 w-8 md:h-12 md:w-12"></i>
                            </div>
                            <div class="flex-1 sm:flex-none text-left sm:text-center z-10">
                                <span class="text-lg md:text-xl font-bold text-gray-800 group-hover:text-emerald-700 transition-colors block">Gorden Rumah</span>
                                <span class="text-sm text-gray-400 group-hover:text-emerald-600/60 transition-colors sm:hidden mt-0.5 block">Mulai Hitung</span>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Gorden Kantor -->
                    <a href="calculator.php?type=kantor" class="block h-full">
                        <div class="bg-white rounded-[2rem] p-6 md:p-10 flex flex-row sm:flex-col items-center sm:justify-center gap-6 border border-slate-100 shadow-sm hover:shadow-2xl hover:shadow-emerald-900/5 hover:border-emerald-200 hover:-translate-y-2 transition-all duration-300 cursor-pointer group h-full relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-white/0 to-current opacity-5 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-150 text-blue-600 bg-blue-50"></div>
                            <div class="w-16 h-16 md:w-24 md:h-24 rounded-3xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:rotate-3 shadow-md group-hover:shadow-lg text-blue-600 bg-blue-50">
                                <i data-lucide="building-2" class="h-8 w-8 md:h-12 md:w-12"></i>
                            </div>
                            <div class="flex-1 sm:flex-none text-left sm:text-center z-10">
                                <span class="text-lg md:text-xl font-bold text-gray-800 group-hover:text-emerald-700 transition-colors block">Gorden Kantor</span>
                                <span class="text-sm text-gray-400 group-hover:text-emerald-600/60 transition-colors sm:hidden mt-0.5 block">Mulai Hitung</span>
                            </div>
                        </div>
                    </a>

                    <!-- Gorden RS -->
                    <a href="calculator.php?type=rs" class="block h-full">
                        <div class="bg-white rounded-[2rem] p-6 md:p-10 flex flex-row sm:flex-col items-center sm:justify-center gap-6 border border-slate-100 shadow-sm hover:shadow-2xl hover:shadow-emerald-900/5 hover:border-emerald-200 hover:-translate-y-2 transition-all duration-300 cursor-pointer group h-full relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-white/0 to-current opacity-5 rounded-bl-full -mr-10 -mt-10 transition-transform group-hover:scale-150 text-red-500 bg-red-50"></div>
                            <div class="w-16 h-16 md:w-24 md:h-24 rounded-3xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:rotate-3 shadow-md group-hover:shadow-lg text-red-500 bg-red-50">
                                <i data-lucide="plus-square" class="h-8 w-8 md:h-12 md:w-12"></i>
                            </div>
                            <div class="flex-1 sm:flex-none text-left sm:text-center z-10">
                                <span class="text-lg md:text-xl font-bold text-gray-800 group-hover:text-emerald-700 transition-colors block">Gorden RS</span>
                                <span class="text-sm text-gray-400 group-hover:text-emerald-600/60 transition-colors sm:hidden mt-0.5 block">Mulai Hitung</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Produk Lainnya -->
            <div class="space-y-8">
                <div class="flex items-center justify-between px-1">
                    <h3 class="font-bold text-2xl md:text-3xl text-gray-900 tracking-tight">Produk Lainnya</h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-3 gap-6 md:gap-8">
                    <!-- Sprei Standard -->
                    <a href="checkout_sprei.php" class="block h-full">
                        <div class="bg-white rounded-[2rem] p-6 md:p-10 flex flex-row sm:flex-col items-center sm:justify-center gap-6 border border-slate-100 shadow-sm hover:shadow-2xl hover:shadow-emerald-900/5 hover:border-emerald-200 hover:-translate-y-2 transition-all duration-300 cursor-pointer group h-full relative overflow-hidden text-center">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-indigo-500/5 to-transparent rounded-bl-full"></div>
                            <div class="w-16 h-16 md:w-24 md:h-24 rounded-3xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:rotate-3 shadow-md group-hover:shadow-lg text-indigo-600 bg-indigo-50">
                                <i data-lucide="bed" class="h-8 w-8 md:h-12 md:w-12"></i>
                            </div>
                            <div class="flex-1 sm:flex-none text-left sm:text-center z-10">
                                <span class="text-lg md:text-xl font-bold text-gray-800 group-hover:text-emerald-700 transition-colors block">Sprei Rumah</span>
                                <span class="text-xs text-gray-400 mt-1 block">Checkout Langsung</span>
                            </div>
                        </div>
                    </a>

                    <!-- Sprei RS/Klinik -->
                    <a href="sprei_klinik.php" class="block h-full">
                        <div class="bg-white rounded-[2rem] p-6 md:p-10 flex flex-row sm:flex-col items-center sm:justify-center gap-6 border border-slate-100 shadow-sm hover:shadow-2xl hover:shadow-emerald-900/5 hover:border-emerald-200 hover:-translate-y-2 transition-all duration-300 cursor-pointer group h-full relative overflow-hidden text-center">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-emerald-500/5 to-transparent rounded-bl-full"></div>
                            <div class="w-16 h-16 md:w-24 md:h-24 rounded-3xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:rotate-3 shadow-md group-hover:shadow-lg text-emerald-600 bg-emerald-50">
                                <i data-lucide="hospital" class="h-8 w-8 md:h-12 md:w-12"></i>
                            </div>
                            <div class="flex-1 sm:flex-none text-left sm:text-center z-10">
                                <span class="text-lg md:text-xl font-bold text-gray-800 group-hover:text-emerald-700 transition-colors block">Sprei RS/Klinik</span>
                                <span class="text-xs text-gray-400 mt-1 block">Anti Noda & Darah</span>
                            </div>
                        </div>
                    </a>

                    <!-- Sprei Custom -->
                    <a href="sprei_custom.php" class="block h-full">
                        <div class="bg-white rounded-[2rem] p-6 md:p-10 flex flex-row sm:flex-col items-center sm:justify-center gap-6 border border-slate-100 shadow-sm hover:shadow-2xl hover:shadow-emerald-900/5 hover:border-emerald-200 hover:-translate-y-2 transition-all duration-300 cursor-pointer group h-full relative overflow-hidden text-center">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-amber-500/5 to-transparent rounded-bl-full"></div>
                            <div class="w-16 h-16 md:w-24 md:h-24 rounded-3xl flex items-center justify-center transition-all duration-300 group-hover:scale-110 group-hover:rotate-3 shadow-md group-hover:shadow-lg text-amber-600 bg-amber-50">
                                <i data-lucide="palette" class="h-8 w-8 md:h-12 md:w-12"></i>
                            </div>
                            <div class="flex-1 sm:flex-none text-left sm:text-center z-10">
                                <span class="text-lg md:text-xl font-bold text-gray-800 group-hover:text-emerald-700 transition-colors block">Sprei Custom</span>
                                <span class="text-xs text-gray-400 mt-1 block">Ukuran Khusus</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
