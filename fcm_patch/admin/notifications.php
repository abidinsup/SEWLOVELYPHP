<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/firebase_helper.php';
checkAdmin();

$page_title = "Push Notifikasi";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Handle form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    
    if (empty($title) || empty($body)) {
        $errorMessage = 'Judul dan isi notifikasi wajib diisi!';
    } else {
        $result = sendBroadcastNotification($pdo, $title, $body);
        $successMessage = "Notifikasi berhasil dikirim ke {$result['sent']} dari {$result['total']} device.";
        if ($result['failed'] > 0) {
            $successMessage .= " ({$result['failed']} gagal)";
        }
    }
}

// Fetch notification history
try {
    $stmt = $pdo->query("
        SELECT id, user_id, title, body, type, created_at 
        FROM notifications 
        ORDER BY created_at DESC 
        LIMIT 50
    ");
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $notifications = [];
}
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <main class="flex-1 p-4 lg:p-8 pt-20 lg:pt-8 overflow-x-hidden relative w-full max-w-full">
        <div class="space-y-6 max-w-4xl mx-auto">
            
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Push Notifikasi</h1>
                <p class="text-slate-500">Kirim notifikasi promosi ke semua pengguna aplikasi</p>
            </div>

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

            <!-- Send Notification Form -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 lg:p-8">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2.5 bg-cyan-50 rounded-xl">
                        <i data-lucide="send" class="h-5 w-5 text-cyan-600"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-lg text-slate-900">Kirim Notifikasi Broadcast</h2>
                        <p class="text-sm text-slate-500">Notifikasi akan dikirim ke semua pengguna yang terdaftar</p>
                    </div>
                </div>

                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Judul Notifikasi</label>
                        <input type="text" name="title" placeholder="Contoh: Promo Akhir Tahun! 🎉" required
                            class="w-full h-12 px-4 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent text-sm font-medium" />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Isi Pesan</label>
                        <textarea name="body" rows="4" placeholder="Contoh: Dapatkan diskon 20% untuk semua produk sprei. Berlaku sampai 31 Desember!" required
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:border-transparent text-sm resize-none"></textarea>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" name="send_notification" 
                            class="bg-[#63e5ff] hover:bg-cyan-400 text-slate-900 px-8 py-3 rounded-xl font-bold shadow-lg shadow-cyan-400/20 transition-all flex items-center gap-2">
                            <i data-lucide="send" class="h-4 w-4"></i>
                            Kirim ke Semua User
                        </button>
                        <p class="text-xs text-slate-400">Notifikasi akan langsung dikirim ke semua device</p>
                    </div>
                </form>
            </div>

            <!-- Notification History -->
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-slate-100 rounded-xl">
                            <i data-lucide="history" class="h-5 w-5 text-slate-600"></i>
                        </div>
                        <div>
                            <h2 class="font-bold text-lg text-slate-900">Riwayat Notifikasi</h2>
                            <p class="text-sm text-slate-500">50 notifikasi terakhir yang dikirim</p>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-slate-50">
                    <?php if (empty($notifications)): ?>
                    <div class="p-12 text-center">
                        <i data-lucide="bell-off" class="h-12 w-12 text-slate-300 mx-auto mb-4"></i>
                        <p class="text-slate-500 font-medium">Belum ada notifikasi yang dikirim</p>
                    </div>
                    <?php else: ?>
                        <?php foreach($notifications as $notif): ?>
                        <div class="p-4 hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-start gap-4">
                                <div class="p-2 rounded-xl flex-shrink-0 <?php 
                                    echo match($notif['type']) {
                                        'commission' => 'bg-emerald-50',
                                        'withdrawal' => 'bg-blue-50',
                                        'promo' => 'bg-amber-50',
                                        default => 'bg-slate-50'
                                    }; 
                                ?>">
                                    <i data-lucide="<?php 
                                        echo match($notif['type']) {
                                            'commission' => 'wallet',
                                            'withdrawal' => 'banknote',
                                            'promo' => 'megaphone',
                                            default => 'bell'
                                        }; 
                                    ?>" class="h-4 w-4 <?php 
                                        echo match($notif['type']) {
                                            'commission' => 'text-emerald-600',
                                            'withdrawal' => 'text-blue-600',
                                            'promo' => 'text-amber-600',
                                            default => 'text-slate-600'
                                        }; 
                                    ?>"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="font-bold text-sm text-slate-900 truncate"><?php echo htmlspecialchars($notif['title']); ?></p>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase flex-shrink-0 <?php 
                                            echo match($notif['type']) {
                                                'commission' => 'bg-emerald-100 text-emerald-700',
                                                'withdrawal' => 'bg-blue-100 text-blue-700',
                                                'promo' => 'bg-amber-100 text-amber-700',
                                                default => 'bg-slate-100 text-slate-700'
                                            }; 
                                        ?>"><?php echo $notif['type']; ?></span>
                                        <?php if (is_null($notif['user_id'])): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-purple-100 text-purple-700 flex-shrink-0">Broadcast</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-slate-500 truncate"><?php echo htmlspecialchars($notif['body']); ?></p>
                                    <p class="text-xs text-slate-400 mt-1"><?php echo date('d M Y H:i', strtotime($notif['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
