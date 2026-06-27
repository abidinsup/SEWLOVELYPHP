<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/helpers.php';
checkAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['survey_id'])) {
    $survey_id = $_POST['survey_id'];
    $total_amount = str_replace(['Rp', '.', ' '], '', $_POST['total_amount']);
    $discount_amount = str_replace(['Rp', '.', ' '], '', ($_POST['discount_amount'] ?? '0'));
    $payment_status = $_POST['payment_status'] ?? 'unpaid';
    if ($payment_status === 'belum_lunas') {
        $payment_status = 'unpaid';
    }
    $production_notes = $_POST['production_notes'] ?? '';
    $invoice_notes = $_POST['invoice_notes'] ?? '';
    $cart_json = $_POST['cart_json'] ?? '';
    
    // Grand total after discount
    $grand_total = max(0, intval($total_amount) - intval($discount_amount));
    
    $invoice_number = generateInvoiceNumber($survey_id);

    try {
        $pdo->beginTransaction();

        // Set status to 'waiting_payment' when invoice is generated
        $stmtUpdate = $pdo->prepare("UPDATE surveys SET status = 'waiting_payment', notes = CONCAT(IFNULL(notes,''), '\n[POS Update]: ', ?) WHERE id = ?");
        $stmtUpdate->execute([$production_notes, $survey_id]);

        // Clean up previous invoice if this is a revision
        $stmtDel = $pdo->prepare("DELETE FROM invoices WHERE survey_id = ?");
        $stmtDel->execute([$survey_id]);

        // Create Invoice with discount and cart_json
        $secure_token = bin2hex(random_bytes(16));
        $stmtInv = $pdo->prepare("INSERT INTO invoices (survey_id, invoice_number, secure_token, total_amount, discount_amount, payment_status, invoice_notes, cart_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtInv->execute([$survey_id, $invoice_number, $secure_token, $grand_total, $discount_amount, $payment_status, $invoice_notes, $cart_json]);

        // Fetch full data for WhatsApp / Printing
        $stmtData = $pdo->prepare("SELECT s.*, i.invoice_number, i.secure_token, i.total_amount, i.discount_amount FROM surveys s JOIN invoices i ON i.survey_id = s.id WHERE s.id = ?");
        $stmtData->execute([$survey_id]);
        $fullData = $stmtData->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();
        
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . str_replace('admin/calculator.php', '', $_SERVER['PHP_SELF']);
        $publicLink = $baseUrl . "view_invoice.php?token=" . $fullData['secure_token'];

        $discountLine = '';
        if (intval($fullData['discount_amount']) > 0) {
            $discountLine = "*DISKON   :* -Rp " . number_format($fullData['discount_amount'], 0, ',', '.') . "\n";
        }

        $itemsText = !empty($invoice_notes) ? "*ITEM PESANAN:*\n" . $invoice_notes . "----------------------------\n" : "";

        $waMessage = "============================\n" .
                     "      *SEWLOVELY HOMESET*   \n" .
                     "============================\n" .
                     "*NO. NOTA :* " . $fullData['invoice_number'] . "\n" .
                     "*TANGGAL  :* " . date('d/m/Y') . "\n" .
                     "*PELANGGAN:* " . $fullData['customer_name'] . "\n" .
                     "----------------------------\n" .
                     $itemsText .
                     $discountLine .
                     "*TOTAL    :* Rp " . number_format($fullData['total_amount'], 0, ',', '.') . "\n" .
                     "----------------------------\n" .
                     "Detail rincian pesanan Anda\nsilakan klik link di bawah ini:\n" . $publicLink . "\n" .
                     "============================\n" .
                     "Terima kasih telah memesan!";

        $wa_phone = preg_replace('/[^0-9]/', '', $fullData['customer_phone']);
        if (substr($wa_phone, 0, 1) === '0') {
            $wa_phone = '62' . substr($wa_phone, 1);
        }
        $waLink = "https://wa.me/" . $wa_phone . "?text=" . urlencode($waMessage);
        
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '<div class=\"text-2xl font-black text-slate-900 mb-2\">Invoice Berhasil Dibuat!</div>',
                    html: `
                        <div class=\"space-y-4\">
                            <div class=\"bg-emerald-50 p-6 rounded-[2rem] border border-emerald-100 mb-6\">
                                <div class=\"w-16 h-16 bg-emerald-600 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-600/20 mx-auto mb-4\">
                                    <i data-lucide=\"check-circle\" class=\"w-8 h-8 text-white\"></i>
                                </div>
                                <p class=\"text-emerald-800 font-bold text-sm uppercase tracking-widest\">Invoice Berhasil Dibuat</p>
                                <p class=\"text-emerald-600/70 font-medium text-xs mt-1\">" . $fullData['invoice_number'] . "</p>
                            </div>
                            
                            <div class=\"grid grid-cols-1 gap-3\">
                                <a href=\"print_spk.php?id=" . $survey_id . "\" target=\"_blank\" class=\"flex items-center justify-center gap-3 w-full py-4 bg-emerald-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-emerald-600/20 hover:bg-emerald-700 transition-all\">
                                    <i data-lucide=\"printer\" class=\"w-5 h-5\"></i> Cetak Nota
                                </a>
                                <a href=\"" . $waLink . "\" target=\"_blank\" class=\"flex items-center justify-center gap-3 w-full py-4 bg-emerald-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-emerald-600/20 hover:bg-emerald-700 transition-all\">
                                    <i data-lucide=\"message-circle\" class=\"w-5 h-5\"></i> Kirim Nota ke WA
                                </a>
                                <a href=\"surveys.php\" class=\"flex items-center justify-center gap-3 w-full py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold text-sm uppercase tracking-widest hover:bg-slate-200 transition-all\">
                                    Selesai / Kembali
                                </a>
                            </div>
                        </div>
                    `,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'rounded-[3rem] p-10',
                    },
                    didOpen: () => {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }
                });
            });
        </script>";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Gagal menyimpan data: " . $e->getMessage();
    }
}

// Fetch surveys that need POS processing
$stmt_surveys = $pdo->query("SELECT s.id, s.customer_name, s.customer_phone, s.calculator_type, p.full_name as partner_name, i.cart_json, i.discount_amount 
                             FROM surveys s 
                             JOIN partners p ON s.partner_id = p.id 
                             LEFT JOIN invoices i ON i.survey_id = s.id
                             WHERE s.status IN ('survey', 'waiting_payment', 'production')
                             AND (s.calculator_type IN ('rumah', 'gorden') OR s.calculator_type IS NULL OR s.calculator_type = '')
                             ORDER BY s.created_at DESC");
$active_surveys = $stmt_surveys->fetchAll(PDO::FETCH_ASSOC);

// Fetch catalog data
$fabrics_gorden = $pdo->query("SELECT id, name, price_per_meter FROM curtain_fabrics WHERE type = 'gorden' AND is_active = 1 ORDER BY name")->fetchAll();
$fabrics_vitrase = $pdo->query("SELECT id, name, price_per_meter FROM curtain_fabrics WHERE type = 'vitrase' AND is_active = 1 ORDER BY name")->fetchAll();
$rails_single = $pdo->query("SELECT id, name, price_per_meter FROM curtain_rails WHERE type = 'single' AND is_active = 1 ORDER BY name")->fetchAll();
$rails_double = $pdo->query("SELECT id, name, price_per_meter FROM curtain_rails WHERE type = 'double' AND is_active = 1 ORDER BY name")->fetchAll();

$selected_survey_id = isset($_GET['survey_id']) ? $_GET['survey_id'] : '';
$page_title = "Kalkulator Gorden & POS";
include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<style>
.pkg-card { border: 2px solid #e2e8f0; border-radius: 1rem; padding: 1rem; cursor: pointer; transition: all 0.2s; user-select: none; text-align: center; }
.pkg-card:hover { border-color: #94a3b8; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.pkg-card.active { border-color: #059669; background: linear-gradient(135deg, #047857, #10b981); color: #fff; box-shadow: 0 8px 24px rgba(16,185,129,0.3); }
.pkg-card.active .pkg-icon { background: rgba(255,255,255,0.15); color: #fff; }
.pkg-card.active .pkg-label { color: #fff; }
.pkg-card.active .pkg-desc { color: #a7f3d0; }
.pkg-icon { width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; color: #64748b; }
.pkg-label { font-size: 13px; font-weight: 800; color: #334155; }
.pkg-desc { font-size: 10px; color: #94a3b8; margin-top: 2px; }
.fullness-btn, .fullness-btn-v { border: 2px solid #e2e8f0; border-radius: 12px; padding: 10px 20px; font-weight: 800; cursor: pointer; transition: all 0.2s; font-size: 14px; }
.fullness-btn:hover, .fullness-btn-v:hover { border-color: #94a3b8; }
.fullness-btn.active, .fullness-btn-v.active { border-color: #059669; background: #059669; color: #fff; box-shadow: 0 4px 12px rgba(5,150,105,0.3); }
.result-card { background: linear-gradient(135deg, #047857, #10b981); border-radius: 1.5rem; padding: 2rem; color: #fff; }
</style>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/90 md:mt-0 mt-16 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Kalkulator Gorden & POS</h1>
                    <p class="text-xs text-slate-500 hidden md:block">Pilih customer dan hitung estimasi harga gorden</p>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto pb-24 md:pb-8">
        <div class="max-w-4xl mx-auto space-y-6">
            <?php if(isset($error)): ?>
                <div class="bg-red-50 text-red-600 p-4 rounded-xl text-sm font-medium border border-red-100 flex gap-2 items-center">
                    <i data-lucide="alert-circle" class="h-5 w-5"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="posForm" class="space-y-6">
                <!-- 1. SELECT CUSTOMER -->
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                        <div>
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1 block">Pilih Customer</label>
                            <h2 class="text-lg font-bold text-slate-800">Riwayat Survey Aktif</h2>
                        </div>
                    </div>

                    <!-- Hidden Select for form submission -->
                    <select name="survey_id" id="survey_selector" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-sm font-medium mb-4">
                        <option value="">-- Pilih Customer --</option>
                        <?php foreach($active_surveys as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($selected_survey_id == $s['id']) ? 'selected' : '' ?> data-discount="<?= floatval($s['discount_amount'] ?? 0) ?>">
                                <?= htmlspecialchars($s['customer_name']) ?> (<?= htmlspecialchars($s['customer_phone']) ?>) - <?= htmlspecialchars($s['partner_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- STEP 1: Pilih Paket -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 font-black text-sm">1</div>
                        <h3 class="font-bold text-slate-800">Pilih Paket</h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                        <div class="pkg-card active" onclick="selectPackage(1, this)">
                            <div class="pkg-icon"><i data-lucide="blinds" class="h-5 w-5"></i></div>
                            <p class="pkg-label">Paket 1</p>
                            <p class="pkg-desc">Gorden Saja</p>
                        </div>
                        <div class="pkg-card" onclick="selectPackage(2, this)">
                            <div class="pkg-icon"><i data-lucide="layout-grid" class="h-5 w-5"></i></div>
                            <p class="pkg-label">Paket 2</p>
                            <p class="pkg-desc">Gorden + Rel</p>
                        </div>
                        <div class="pkg-card" onclick="selectPackage(3, this)">
                            <div class="pkg-icon"><i data-lucide="sun" class="h-5 w-5"></i></div>
                            <p class="pkg-label">Paket 3</p>
                            <p class="pkg-desc">Vitrase Saja</p>
                        </div>
                        <div class="pkg-card" onclick="selectPackage(4, this)">
                            <div class="pkg-icon"><i data-lucide="sun" class="h-5 w-5"></i><i data-lucide="plus" class="h-3 w-3 mx-0.5"></i><i data-lucide="grip-horizontal" class="h-5 w-5"></i></div>
                            <p class="pkg-label">Paket 4</p>
                            <p class="pkg-desc">Vitrase + Rel</p>
                        </div>
                        <div class="pkg-card" onclick="selectPackage(5, this)">
                            <div class="pkg-icon"><i data-lucide="layers" class="h-5 w-5"></i></div>
                            <p class="pkg-label">Paket 5</p>
                            <p class="pkg-desc text-[9px]">Gorden+Rel+Vitrase+Rel</p>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Ukuran -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 font-black text-sm">2</div>
                            <h3 class="font-bold text-slate-800">Ukuran Jendela</h3>
                        </div>
                        <button type="button" onclick="addWindow()" class="text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 transition-all shadow-sm">
                            <i data-lucide="plus" class="h-3 w-3"></i> Tambah Jendela
                        </button>
                    </div>
                    
                    <div id="windowList" class="space-y-3">
                        <!-- Window 1 -->
                        <div class="window-item bg-slate-50/70 border border-slate-100 p-4 rounded-2xl relative group">
                            <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-3 window-title">Jendela 1</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Lebar Rel (cm)</label>
                                    <input type="number" value="200" min="30" max="1500" oninput="calculate()" class="window-width w-full h-11 px-3 bg-white border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-sm font-bold text-center transition-all" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Tinggi (cm)</label>
                                    <input type="number" value="250" min="50" max="600" oninput="calculate()" class="window-height w-full h-11 px-3 bg-white border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-sm font-bold text-center transition-all" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sambung Info -->
                    <div id="sambungInfo" class="hidden flex items-start gap-3 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                        <i data-lucide="alert-triangle" class="h-4 w-4 text-amber-600 mt-0.5 shrink-0"></i>
                        <p class="text-xs text-amber-800 font-medium" id="sambungText"></p>
                    </div>
                </div>

                <!-- STEP 3: Lipatan -->
                <div id="stepFullness" class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600 font-black text-sm">3</div>
                        <h3 class="font-bold text-slate-800">Pilih Lipatan (Fullness)</h3>
                    </div>
                    <!-- Gorden Fullness -->
                    <div id="gordenFullnessSection">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 pl-1">Fullness Gorden</p>
                        <div class="flex gap-3">
                            <button type="button" class="fullness-btn" onclick="setFullness('gorden', 2, this)">×2</button>
                            <button type="button" class="fullness-btn active" onclick="setFullness('gorden', 2.5, this)">×2.5</button>
                            <button type="button" class="fullness-btn" onclick="setFullness('gorden', 3, this)">×3</button>
                        </div>
                    </div>
                    <!-- Vitrase Fullness -->
                    <div id="vitraseFullnessSection" class="hidden mt-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 pl-1">Fullness Vitrase</p>
                        <div class="flex gap-3">
                            <button type="button" class="fullness-btn-v active" onclick="setFullness('vitrase', 2, this)">×2</button>
                            <button type="button" class="fullness-btn-v" onclick="setFullness('vitrase', 2.5, this)">×2.5</button>
                            <button type="button" class="fullness-btn-v" onclick="setFullness('vitrase', 3, this)">×3</button>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: Pilih Bahan -->
                <div id="stepMaterial" class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 font-black text-sm">4</div>
                        <h3 class="font-bold text-slate-800">Pilih Bahan</h3>
                    </div>

                    <!-- Gorden -->
                    <div id="selectGorden" class="space-y-2">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Gorden</label>
                        <select id="fabricGorden" onchange="calculate()" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-sm font-medium">
                            <?php foreach($fabrics_gorden as $f): ?>
                            <option value="<?php echo $f['id']; ?>" data-price="<?php echo $f['price_per_meter']; ?>"><?php echo htmlspecialchars($f['name']); ?> — Rp <?php echo number_format($f['price_per_meter'],0,',','.'); ?>/m</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Vitrase -->
                    <div id="selectVitrase" class="space-y-2 hidden">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Vitrase</label>
                        <select id="fabricVitrase" onchange="calculate()" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-sm font-medium">
                            <?php foreach($fabrics_vitrase as $f): ?>
                            <option value="<?php echo $f['id']; ?>" data-price="<?php echo $f['price_per_meter']; ?>"><?php echo htmlspecialchars($f['name']); ?> — Rp <?php echo number_format($f['price_per_meter'],0,',','.'); ?>/m</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Rel -->
                    <div id="selectRel" class="space-y-2 hidden">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1" id="relLabel">Rel</label>
                        <select id="railSelect" onchange="calculate()" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-sm font-medium">
                        </select>
                    </div>
                </div>

                <!-- RESULT -->
                <div id="resultSection" class="result-card">
                    <div class="flex items-center gap-3 mb-5">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <i data-lucide="receipt" class="h-5 w-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg">Rincian Estimasi Harga</h3>
                            <p class="text-emerald-100 text-xs">Harga sudah termasuk ongkos jahit & pasang</p>
                        </div>
                    </div>

                    <div id="resultDetails" class="space-y-3 mb-6"></div>
                    
                    <!-- Discount Input -->
                    <div class="flex items-center gap-3 bg-white/10 p-3 rounded-xl border border-white/20 mb-4">
                        <div class="flex items-center gap-1.5 shrink-0">
                            <i data-lucide="percent" class="h-4 w-4 text-emerald-100"></i>
                            <span class="text-xs font-bold text-emerald-50 uppercase tracking-wide">Diskon</span>
                        </div>
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-emerald-800" style="color: #047857 !important;">Rp</span>
                            <input type="text" id="discount_input" value="0" placeholder="0" class="w-full pl-9 pr-3 py-2 bg-white/90 border border-transparent rounded-lg text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all" style="color: #0f172a !important;" oninput="formatRupiahInput(this); calculateTotal()" />
                        </div>
                    </div>

                    <div class="border-t border-white/20 pt-4 flex items-center justify-between">
                        <span class="font-bold text-lg">TOTAL ESTIMASI</span>
                        <span class="text-3xl font-black" id="totalPrice">Rp 0</span>
                    </div>
                </div>
                
                <!-- Hidden inputs for form submission -->
                <input type="hidden" id="final_total_amount" name="total_amount">
                <input type="hidden" id="final_discount_amount" name="discount_amount">
                <input type="hidden" id="final_invoice_notes" name="invoice_notes">
                <input type="hidden" id="final_cart_json" name="cart_json">
                <input type="hidden" name="payment_status" value="unpaid">
                
                <button type="submit" id="btn_checkout" class="w-full h-14 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold text-sm uppercase tracking-widest flex items-center justify-center gap-3 shadow-lg shadow-emerald-500/20 transition-all active:scale-[0.98]">
                    <i data-lucide="receipt" class="h-5 w-5"></i> Buat Invoice & POS
                </button>

            </form>
        </div>
    </main>
</div>

<script>
// ==================== DATA FROM PHP ====================
const railsSingle = <?php echo json_encode($rails_single); ?>;
const railsDouble = <?php echo json_encode($rails_double); ?>;

// ==================== STATE ====================
let selectedPackage = 1;
let gordenFullness = 2.5;
let vitraseFullness = 2;

const KAIN_WIDTH = 280;
const KELIMAN = 20;

let rawTotal = 0;

// ==================== PACKAGE SELECTION ====================
function selectPackage(pkg, el) {
    selectedPackage = pkg;
    document.querySelectorAll('.pkg-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    lucide.createIcons();
    updateVisibility();
    calculate();
}

function updateVisibility() {
    const p = selectedPackage;
    const hasGorden = [1,2,5].includes(p);
    const hasVitrase = [3,4,5].includes(p);
    const hasRel = [2,4,5].includes(p);
    const needsFullness = true; // all packages now need fullness either for gorden or vitrase

    document.getElementById('selectGorden').classList.toggle('hidden', !hasGorden);
    document.getElementById('selectVitrase').classList.toggle('hidden', !hasVitrase);
    document.getElementById('selectRel').classList.toggle('hidden', !hasRel);
    document.getElementById('stepFullness').classList.toggle('hidden', !needsFullness);
    document.getElementById('gordenFullnessSection').classList.toggle('hidden', !hasGorden);
    document.getElementById('vitraseFullnessSection').classList.toggle('hidden', !hasVitrase);

    // Populate rel dropdown based on package
    if (hasRel) {
        const rails = (p === 5) ? railsDouble : railsSingle;
        const label = (p === 5) ? 'Rel Double (Twin)' : 'Rel';
        document.getElementById('relLabel').textContent = label;
        const sel = document.getElementById('railSelect');
        sel.innerHTML = '';
        rails.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.dataset.price = r.price_per_meter;
            opt.textContent = `${r.name} — Rp ${Number(r.price_per_meter).toLocaleString('id-ID')}/m`;
            sel.appendChild(opt);
        });
        // If no double rails but package 5, fall back to single
        if (rails.length === 0 && p === 5) {
            railsSingle.forEach(r => {
                const opt = document.createElement('option');
                opt.value = r.id;
                opt.dataset.price = r.price_per_meter;
                opt.textContent = `${r.name} — Rp ${Number(r.price_per_meter).toLocaleString('id-ID')}/m`;
                sel.appendChild(opt);
            });
        }
    }
}

// ==================== FULLNESS SELECTION ====================
function setFullness(type, val, el) {
    if (type === 'gorden') {
        gordenFullness = val;
        document.querySelectorAll('.fullness-btn').forEach(b => b.classList.remove('active'));
    } else {
        vitraseFullness = val;
        document.querySelectorAll('.fullness-btn-v').forEach(b => b.classList.remove('active'));
    }
    el.classList.add('active');
    calculate();
}

// ==================== WINDOW MULTIPLIER ====================
function addWindow() {
    const list = document.getElementById('windowList');
    const count = list.querySelectorAll('.window-item').length + 1;
    
    const div = document.createElement('div');
    div.className = 'window-item bg-slate-50/70 border border-slate-100 p-4 rounded-2xl relative group transition-all';
    div.innerHTML = `
        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-wider mb-3 window-title">Jendela ${count}</h4>
        <button type="button" onclick="removeWindow(this)" class="absolute top-3 right-3 text-red-400 hover:text-red-600 bg-white hover:bg-red-50 p-1.5 rounded-lg shadow-sm transition-all"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Lebar Rel (cm)</label>
                <input type="number" value="200" min="30" max="1500" oninput="calculate()" class="window-width w-full h-11 px-3 bg-white border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-sm font-bold text-center transition-all" />
            </div>
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Tinggi (cm)</label>
                <input type="number" value="250" min="50" max="600" oninput="calculate()" class="window-height w-full h-11 px-3 bg-white border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-sm font-bold text-center transition-all" />
            </div>
        </div>
    `;
    list.appendChild(div);
    lucide.createIcons();
    calculate();
}

function removeWindow(btn) {
    btn.closest('.window-item').remove();
    document.querySelectorAll('.window-item').forEach((el, index) => {
        el.querySelector('.window-title').textContent = `Jendela ${index + 1}`;
    });
    calculate();
}

// ==================== CALCULATION ENGINE ====================
function calculate() {
    let windows = [];
    document.querySelectorAll('.window-item').forEach((el, index) => {
        const w = parseFloat(el.querySelector('.window-width').value) || 0;
        const h = parseFloat(el.querySelector('.window-height').value) || 0;
        if(w > 0 && h > 0) {
            windows.push({ w, h, index: index + 1 });
        }
    });

    const p = selectedPackage;
    const pkgLabels = {
        1: "Paket 1 (Gorden Saja)",
        2: "Paket 2 (Gorden + Rel)",
        3: "Paket 3 (Vitrase Saja)",
        4: "Paket 4 (Vitrase + Rel)",
        5: "Paket 5 (Gorden+Rel+Vitrase+Rel)"
    };
    const pkgName = pkgLabels[p] || `Paket ${p}`;

    let details = [];
    let total = 0;
    let sambungMessages = [];

    const optGorden = document.getElementById('fabricGorden').selectedOptions[0];
    const priceGorden = parseFloat(optGorden?.dataset.price || 0);
    
    const optVitrase = document.getElementById('fabricVitrase').selectedOptions[0];
    const priceVitrase = parseFloat(optVitrase?.dataset.price || 0);
    
    const optRel = document.getElementById('railSelect').selectedOptions[0];
    const priceRel = parseFloat(optRel?.dataset.price || 0);

    windows.forEach(win => {
        const width = win.w;
        const height = win.h;
        const winLabel = windows.length > 1 ? ` <span class="bg-white/20 px-1.5 py-0.5 rounded text-[10px]">Jendela ${win.index}</span>` : '';
        const winTextLabel = windows.length > 1 ? ` [Jendela ${win.index}]` : '';

        const tinggiEfektif = height + KELIMAN;
        const jumlahSambung = Math.ceil(tinggiEfektif / KAIN_WIDTH);
        
        if (jumlahSambung > 1) {
            sambungMessages.push(`Jendela ${win.index}: Tinggi perlu disambung ${jumlahSambung - 1}×.`);
        }
        
        const meterRel = Math.max(width, 100) / 100;
        
        if ([1,2,5].includes(p)) {
            const meterKain = (width / 100) * gordenFullness * jumlahSambung;
            const meterRounded = Math.ceil(meterKain * 10) / 10;
            const harga = meterRounded * priceGorden;
            const lbl = (p === 1) ? pkgName : `${pkgName} - Gorden`;
            details.push({ labelHTML: `${lbl}${winLabel}`, labelText: `${lbl}${winTextLabel}`, sub: `${width}cm ÷ 100 × ${gordenFullness} × ${jumlahSambung} = ${meterRounded.toFixed(1)}m`, meter: `${meterRounded.toFixed(1)} m`, price: harga });
            total += harga;
        }
        
        if ([3,4,5].includes(p)) {
            const meterVit = (width / 100) * vitraseFullness * jumlahSambung;
            const meterRounded = Math.ceil(meterVit * 10) / 10;
            const harga = meterRounded * priceVitrase;
            const lbl = (p === 3) ? pkgName : `${pkgName} - Vitrase`;
            details.push({ labelHTML: `${lbl}${winLabel}`, labelText: `${lbl}${winTextLabel}`, sub: `${width}cm ÷ 100 × ${vitraseFullness} × ${jumlahSambung} = ${meterRounded.toFixed(1)}m`, meter: `${meterRounded.toFixed(1)} m`, price: harga });
            total += harga;
        }
        
        if ([2,4,5].includes(p)) {
            const harga = meterRel * priceRel;
            const lbl = `${pkgName} - Rel`;
            details.push({ labelHTML: `${lbl}${winLabel}`, labelText: `${lbl}${winTextLabel}`, sub: `min(${width}cm, 100cm) → ${meterRel.toFixed(1)}m`, meter: `${meterRel.toFixed(1)} m`, price: harga });
            total += harga;
        }
    });

    const sambungInfo = document.getElementById('sambungInfo');
    if (sambungMessages.length > 0) {
        sambungInfo.classList.remove('hidden');
        document.getElementById('sambungText').innerHTML = sambungMessages.join('<br>');
    } else {
        sambungInfo.classList.add('hidden');
    }

    // Render details
    const container = document.getElementById('resultDetails');
    container.innerHTML = details.map(d => `
        <div class="flex items-start justify-between bg-white/10 p-3 rounded-xl item-detail" data-labeltext="${d.labelText}" data-price="${d.price}">
            <div>
                <p class="font-bold text-sm flex items-center gap-2">${d.labelHTML}</p>
                <p class="text-emerald-100 text-[11px] mt-0.5">${d.sub}</p>
            </div>
            <div class="text-right shrink-0 ml-4">
                <p class="font-black">Rp ${d.price.toLocaleString('id-ID')}</p>
                <p class="text-emerald-100 text-[11px]">${d.meter}</p>
            </div>
        </div>
    `).join('');

    rawTotal = total;
    calculateTotal();
}

function formatRupiahInput(input) {
    let value = input.value.replace(/[^,\d]/g, '').toString();
    let split = value.split(',');
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    
    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    
    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    input.value = rupiah;
}

function calculateTotal() {
    let rawDiscount = document.getElementById('discount_input').value.replace(/\./g, '');
    const discount = parseInt(rawDiscount) || 0;
    const grandTotal = Math.max(0, rawTotal - discount);
    document.getElementById('totalPrice').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
    
    // Update hidden inputs
    document.getElementById('final_total_amount').value = rawTotal;
    document.getElementById('final_discount_amount').value = discount;
    
    // Generate invoice notes
    let notes = [];
    const items = document.getElementById('resultDetails').querySelectorAll('.item-detail');
    items.forEach(item => {
        notes.push(`${item.dataset.labeltext}: Rp ${Number(item.dataset.price).toLocaleString('id-ID')}`);
    });
    document.getElementById('final_invoice_notes').value = notes.join('\n');
}

// Form validation before submit
document.getElementById('posForm').addEventListener('submit', function(e) {
    const surveyId = document.getElementById('survey_selector').value;
    if (!surveyId) {
        e.preventDefault();
        Swal.fire('Peringatan', 'Silakan pilih customer terlebih dahulu!', 'warning');
        return;
    }
    
    if (rawTotal <= 0) {
        e.preventDefault();
        Swal.fire('Peringatan', 'Total pesanan tidak boleh 0!', 'warning');
        return;
    }
});

// Setup customer selector change event to handle existing discount
document.getElementById('survey_selector').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.value) {
        const discount = opt.dataset.discount || 0;
        document.getElementById('discount_input').value = Number(discount).toLocaleString('id-ID');
        calculateTotal();
    }
});

// ==================== INIT ====================
updateVisibility();
calculate();

// Initialize discount if survey is pre-selected
const initialSurveyOpt = document.getElementById('survey_selector').selectedOptions[0];
if (initialSurveyOpt && initialSurveyOpt.value) {
    const initDiscount = initialSurveyOpt.dataset.discount || 0;
    document.getElementById('discount_input').value = Number(initDiscount).toLocaleString('id-ID');
    calculateTotal();
}
</script>

<?php include '../includes/footer.php'; ?>
