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
        
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . str_replace('admin/calculator_rs.php', '', $_SERVER['PHP_SELF']);
        $publicLink = $baseUrl . "view_invoice.php?token=" . $fullData['secure_token'];

        $discountLine = '';
        if (intval($fullData['discount_amount']) > 0) {
            $discountLine = "*DISKON   :* -Rp " . number_format($fullData['discount_amount'], 0, ',', '.') . "\n";
        }

        $itemsText = !empty($invoice_notes) ? "*ITEM PESANAN:*\n" . $invoice_notes . "\n----------------------------\n" : "";

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
                            <div class=\"bg-sky-50 p-6 rounded-[2rem] border border-sky-100 mb-6\">
                                <div class=\"w-16 h-16 bg-sky-600 rounded-2xl flex items-center justify-center shadow-lg shadow-sky-600/20 mx-auto mb-4\">
                                    <i data-lucide=\"check-circle\" class=\"w-8 h-8 text-white\"></i>
                                </div>
                                <p class=\"text-sky-800 font-bold text-sm uppercase tracking-widest\">Invoice Berhasil Dibuat</p>
                                <p class=\"text-sky-600/70 font-medium text-xs mt-1\">" . $fullData['invoice_number'] . "</p>
                            </div>
                            
                            <div class=\"grid grid-cols-1 gap-3\">
                                <a href=\"print_spk.php?id=" . $survey_id . "\" target=\"_blank\" class=\"flex items-center justify-center gap-3 w-full py-4 bg-sky-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-sky-600/20 hover:bg-sky-700 transition-all\">
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
                             AND (s.calculator_type = 'rs')
                             ORDER BY s.created_at DESC");
$active_surveys = $stmt_surveys->fetchAll(PDO::FETCH_ASSOC);

// Fetch catalog data
$fabrics_gorden = $pdo->query("SELECT id, name, price_per_meter FROM curtain_fabrics WHERE type = 'gorden' AND is_active = 1 ORDER BY name")->fetchAll();
$curtain_rails = $pdo->query("SELECT id, name, price_per_meter FROM curtain_rails WHERE is_active = 1 ORDER BY name")->fetchAll();

$selected_survey_id = isset($_GET['survey_id']) ? $_GET['survey_id'] : '';
$page_title = "Kalkulator Gorden RS & POS";
include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<style>
.bg-sky-theme { background-color: #0284c7; }
.shadow-sky-theme { box-shadow: 0 10px 15px -3px rgba(2, 132, 199, 0.25); }
.result-card { background: linear-gradient(135deg, #0284c7, #38bdf8); border-radius: 1.5rem; padding: 2rem; color: #fff; }
.tooltip-container { position: relative; display: inline-block; cursor: help; }
.tooltip-container .tooltip-text {
    visibility: hidden; width: max-content; max-width: 250px; background-color: #1e293b; color: #fff;
    text-align: center; border-radius: 8px; padding: 8px 12px; font-size: 11px; font-weight: normal;
    position: absolute; z-index: 50; bottom: 125%; left: 50%; transform: translateX(-50%); opacity: 0; transition: opacity 0.3s;
    line-height: 1.5; white-space: pre-wrap; pointer-events: none;
}
.tooltip-container:hover .tooltip-text { visibility: visible; opacity: 1; }

/* Custom Sky Utility Styles for compiled CSS fallback */
.bg-sky-50 { background-color: #f0f9ff !important; }
.bg-sky-100 { background-color: #e0f2fe !important; }
.bg-sky-500 { background-color: #0ea5e9 !important; }
.bg-sky-600 { background-color: #0284c7 !important; }
.bg-sky-700 { background-color: #0369a1 !important; }
.hover\:bg-sky-100:hover { background-color: #e0f2fe !important; }
.hover\:bg-sky-700:hover { background-color: #0369a1 !important; }
.text-sky-50 { color: #f0f9ff !important; }
.text-sky-100 { color: #e0f2fe !important; }
.text-sky-200 { color: #bae6fd !important; }
.text-sky-500 { color: #0ea5e9 !important; }
.text-sky-600 { color: #0284c7 !important; }
.text-sky-600\/70 { color: rgba(2, 132, 199, 0.7) !important; }
.text-sky-700 { color: #0369a1 !important; }
.text-sky-800 { color: #075985 !important; }
.text-sky-900 { color: #0c4a6e !important; }
.border-sky-100 { border-color: #e0f2fe !important; }
.border-sky-200 { border-color: #bae6fd !important; }
.border-sky-500 { border-color: #0ea5e9 !important; }
.shadow-sky-600\/20 { box-shadow: 0 10px 15px -3px rgba(2, 132, 199, 0.2) !important; }
.focus\:border-sky-500:focus { border-color: #0ea5e9 !important; }
.focus\:ring-sky-500\/20:focus { box-shadow: 0 0 0 calc(2px) rgba(14, 165, 233, 0.2) !important; }
.ring-sky-500\/20 { box-shadow: 0 0 0 calc(2px) rgba(14, 165, 233, 0.2) !important; }
</style>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/90 md:mt-0 mt-16 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Kalkulator Gorden RS & POS</h1>
                    <p class="text-xs text-slate-500 hidden md:block">Pilih customer dan hitung estimasi harga gorden rumah sakit</p>
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
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm print:hidden">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                        <div>
                            <label class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1 block">Pilih Customer</label>
                            <h2 class="text-lg font-bold text-slate-800">Riwayat Survey Aktif</h2>
                        </div>
                    </div>

                    <select name="survey_id" id="survey_selector" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 rounded-xl text-sm font-medium mb-4">
                        <option value="">-- Pilih Customer --</option>
                        <?php foreach($active_surveys as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($selected_survey_id == $s['id']) ? 'selected' : '' ?> data-discount="<?= floatval($s['discount_amount'] ?? 0) ?>" data-cart="<?= htmlspecialchars($s['cart_json'] ?? '[]') ?>">
                                <?= htmlspecialchars($s['customer_name']) ?> (<?= htmlspecialchars($s['customer_phone']) ?>) - <?= htmlspecialchars($s['partner_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. MASTER HARGA & SETTING -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4 print:hidden">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 font-black text-sm"><i data-lucide="settings" class="h-4 w-4"></i></div>
                        <h3 class="font-bold text-slate-800">Master Harga & Faktor Hitungan</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Bahan Kain (Katalog)</label>
                            <select id="select_fabric" class="w-full h-10 px-2 border border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 rounded-xl text-sm font-bold bg-slate-50 text-slate-700" onchange="updateFabricPrice()">
                                <?php foreach($fabrics_gorden as $f): ?>
                                    <option value="<?= $f['price_per_meter'] ?>"><?= htmlspecialchars($f['name']) ?> — Rp <?= number_format($f['price_per_meter'],0,',','.') ?>/m</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Rel (Katalog)</label>
                            <select id="select_rail" class="w-full h-10 px-2 border border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 rounded-xl text-sm font-bold bg-slate-50 text-slate-700" onchange="updateRailPrice()">
                                <?php foreach($curtain_rails as $r): ?>
                                    <option value="<?= $r['price_per_meter'] ?>"><?= htmlspecialchars($r['name']) ?> — Rp <?= number_format($r['price_per_meter'],0,',','.') ?>/m</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-3 border-t border-slate-100">
                        <div class="space-y-1">
                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">H. Kain (/m)</label>
                            <input type="number" id="price_fabric" class="w-full h-9 px-2 bg-slate-100 border border-slate-200 rounded-lg text-xs font-bold text-slate-600" readonly />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">H. Rel (/m)</label>
                            <input type="number" id="price_rail" class="w-full h-9 px-2 bg-slate-100 border border-slate-200 rounded-lg text-xs font-bold text-slate-600" readonly />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">H. Jahit (/m)</label>
                            <input type="number" id="price_sew" value="20000" class="w-full h-9 px-2 bg-white border border-slate-200 focus:border-sky-500 rounded-lg text-xs font-bold text-slate-700" oninput="calculate(false)" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">H. Bracket (/pcs)</label>
                            <input type="number" id="price_bracket" value="15000" class="w-full h-9 px-2 bg-white border border-slate-200 focus:border-sky-500 rounded-lg text-xs font-bold text-slate-700" oninput="calculate(false)" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">H. Carrier (/pcs)</label>
                            <input type="number" id="price_carrier" value="2000" class="w-full h-9 px-2 bg-white border border-slate-200 focus:border-sky-500 rounded-lg text-xs font-bold text-slate-700" oninput="calculate(false)" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">H. Joint (/pcs)</label>
                            <input type="number" id="price_joint" value="35000" class="w-full h-9 px-2 bg-white border border-slate-200 focus:border-sky-500 rounded-lg text-xs font-bold text-slate-700" oninput="calculate(false)" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider text-sky-600">Fullness Kain</label>
                            <input type="number" id="factor_fullness" step="0.1" value="2" class="w-full h-9 px-2 bg-sky-50 border border-sky-200 text-sky-800 rounded-lg text-xs font-bold" oninput="calculate(true)" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider text-sky-600">Jarak Bracket (m)</label>
                            <input type="number" id="factor_bracket_dist" step="0.1" value="0.5" class="w-full h-9 px-2 bg-sky-50 border border-sky-200 text-sky-800 rounded-lg text-xs font-bold" oninput="calculate(true)" />
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider text-sky-600">Carrier / m</label>
                            <input type="number" id="factor_carrier_perm" value="10" class="w-full h-9 px-2 bg-sky-50 border border-sky-200 text-sky-800 rounded-lg text-xs font-bold" oninput="calculate(true)" />
                        </div>
                    </div>
                </div>

                <!-- 3. DAFTAR RUANGAN -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4 print:shadow-none print:border-none print:p-0">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-sky-100 rounded-lg flex items-center justify-center text-sky-600 font-black text-sm"><i data-lucide="layout" class="h-4 w-4"></i></div>
                            <h3 class="font-bold text-slate-800">Daftar Ruangan</h3>
                        </div>
                        <button type="button" onclick="addRoom()" class="text-xs bg-sky-50 text-sky-600 hover:bg-sky-100 px-3 py-2 rounded-xl font-bold flex items-center gap-1.5 transition-all shadow-sm print:hidden">
                            <i data-lucide="plus" class="h-3 w-3"></i> Tambah Ruangan
                        </button>
                    </div>

                    <div id="roomList" class="space-y-4">
                        <!-- Rooms will be inserted here -->
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
                            <p class="text-sky-100 text-xs">Termasuk rel, kain, & aksesoris</p>
                        </div>
                    </div>

                    <div id="resultDetails" class="space-y-3 mb-6"></div>
                    
                    <!-- Discount Input -->
                    <div class="flex items-center gap-3 bg-white/10 p-3 rounded-xl border border-white/20 mb-4">
                        <div class="flex items-center gap-1.5 shrink-0">
                            <i data-lucide="percent" class="h-4 w-4 text-sky-100"></i>
                            <span class="text-xs font-bold text-sky-50 uppercase tracking-wide">Diskon</span>
                        </div>
                        <div class="relative flex-1">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-sky-800" style="color: #0369a1 !important;">Rp</span>
                            <input type="text" id="discount_input" value="0" placeholder="0" class="w-full pl-9 pr-3 py-2 bg-white/90 border border-transparent rounded-lg text-sm font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-white/50 transition-all" style="color: #0f172a !important;" oninput="formatRupiahInput(this); calculateTotal()" />
                        </div>
                    </div>

                    <div class="border-t border-white/20 pt-4 flex items-center justify-between">
                        <span class="font-bold text-lg">GRAND TOTAL</span>
                        <span class="text-3xl font-black" id="totalPrice">Rp 0</span>
                    </div>
                </div>
                
                <!-- Hidden inputs for form submission -->
                <input type="hidden" id="final_total_amount" name="total_amount">
                <input type="hidden" id="final_discount_amount" name="discount_amount">
                <input type="hidden" id="final_invoice_notes" name="invoice_notes">
                <input type="hidden" id="final_cart_json" name="cart_json">
                <input type="hidden" name="payment_status" value="unpaid">
                
                <div class="flex gap-4 print:hidden">
                    <button type="submit" id="btn_checkout" class="w-full h-14 bg-sky-theme text-white rounded-2xl font-bold text-sm uppercase tracking-widest flex items-center justify-center gap-3 shadow-lg shadow-sky-theme transition-all active:scale-[0.98]">
                        <i data-lucide="receipt" class="h-5 w-5"></i> Buat Invoice &amp; POS
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<script>
let rawTotal = 0;

function updateFabricPrice() {
    const val = document.getElementById('select_fabric').value;
    document.getElementById('price_fabric').value = val;
    calculate(false);
}

function updateRailPrice() {
    const val = document.getElementById('select_rail').value;
    document.getElementById('price_rail').value = val;
    calculate(false);
}

// ==================== WINDOW MANAGEMENT ====================
function addRoom(roomData = null) {
    const list = document.getElementById('roomList');
    const count = list.querySelectorAll('.room-item').length + 1;
    
    const div = document.createElement('div');
    div.className = 'room-item bg-slate-50/70 border border-slate-100 p-5 rounded-2xl relative group transition-all';
    
    const isManual = roomData ? 'true' : 'false';
    div.setAttribute('data-manual', isManual);
    
    const name = roomData ? roomData.room_name : `Ruangan ${count}`;
    const shape = roomData ? roomData.shape : 'U';
    const w = roomData ? roomData.width : 250;
    const p = roomData ? roomData.length : 250;
    const h = roomData ? roomData.height : 300;
    const beds = roomData ? roomData.beds : 1;
    const notes = roomData ? (roomData.notes || '') : '';
    
    const rel = roomData ? roomData.rel : '';
    const kain = roomData ? roomData.kain : '';
    const bracket = roomData ? roomData.bracket : '';
    const carrier = roomData ? roomData.carrier : '';
    const joint = roomData ? roomData.joint : '';

    div.innerHTML = `
        <div class="flex items-center justify-between mb-3 border-b border-slate-200/50 pb-3">
            <span class="font-bold text-slate-700 text-xs uppercase tracking-widest flex items-center gap-1.5"><i data-lucide="bed" class="w-4 h-4 text-slate-400"></i> Detail Ruangan</span>
            <button type="button" onclick="removeRoom(this)" class="text-red-500 hover:text-red-600 bg-white hover:bg-red-50 w-8 h-8 flex items-center justify-center rounded-xl border border-red-200 shadow-sm transition-all print:hidden"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Nama Ruangan</label>
                <input type="text" value="${name}" class="room-name w-full h-11 px-3 bg-white border border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 rounded-xl text-sm font-bold transition-all" />
            </div>
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1 flex items-center gap-1">Bentuk Tirai <span class="tooltip-container"><i data-lucide="help-circle" class="h-3 w-3 text-slate-400"></i><span class="tooltip-text">U: Lebar + Panjang + Lebar (2 Sudut Joint)<br>L: Lebar + Panjang (1 Sudut Joint)<br>I: Panjang Lurus (0 Sudut Joint)</span></span></label>
                <select class="room-shape w-full h-11 px-3 bg-white border border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 rounded-xl text-sm font-bold transition-all text-slate-700" onchange="resetManual(this)">
                    <option value="U" ${shape === 'U' ? 'selected' : ''}>Bentuk U (Kanan+Depan+Kiri)</option>
                    <option value="L" ${shape === 'L' ? 'selected' : ''}>Bentuk L (Samping+Depan)</option>
                    <option value="I" ${shape === 'I' ? 'selected' : ''}>Bentuk I (Lurus)</option>
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Lebar (cm)</label>
                <input type="number" value="${w}" oninput="resetManual(this)" class="room-w w-full h-11 px-3 bg-white border border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 rounded-xl text-sm font-bold text-center transition-all" />
            </div>
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Panjang (cm)</label>
                <input type="number" value="${p}" oninput="resetManual(this)" class="room-p w-full h-11 px-3 bg-white border border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 rounded-xl text-sm font-bold text-center transition-all" />
            </div>
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Tinggi (cm)</label>
                <input type="number" value="${h}" class="room-h w-full h-11 px-3 bg-white border border-slate-200 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 rounded-xl text-sm font-bold text-center transition-all" />
            </div>
            <div class="space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1 text-sky-600">Jumlah Bed</label>
                <input type="number" value="${beds}" class="room-beds w-full h-11 px-3 bg-sky-50 border border-sky-200 text-sky-800 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/20 rounded-xl text-sm font-bold text-center transition-all" oninput="calculate(false)" />
            </div>
        </div>
        
        <div class="bg-white p-3 rounded-xl border border-slate-200 shadow-sm relative">
            <span class="absolute -top-2 left-3 bg-white px-2 text-[9px] font-black text-sky-500 uppercase tracking-widest border border-sky-100 rounded">Hasil Hitungan Otomatis</span>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-1">
                <div class="space-y-1">
                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Rel (meter)</label>
                    <input type="number" step="0.1" value="${rel}" class="room-val-rel w-full h-9 px-2 bg-slate-50 border border-slate-200 focus:border-sky-500 rounded-lg text-xs font-bold text-center" oninput="setManualFlag(this)" />
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Kain (meter)</label>
                    <input type="number" step="0.1" value="${kain}" class="room-val-kain w-full h-9 px-2 bg-slate-50 border border-slate-200 focus:border-sky-500 rounded-lg text-xs font-bold text-center" oninput="setManualFlag(this)" />
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Bracket (pcs)</label>
                    <input type="number" value="${bracket}" class="room-val-bracket w-full h-9 px-2 bg-slate-50 border border-slate-200 focus:border-sky-500 rounded-lg text-xs font-bold text-center" oninput="setManualFlag(this)" />
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Carrier (pcs)</label>
                    <input type="number" value="${carrier}" class="room-val-carrier w-full h-9 px-2 bg-slate-50 border border-slate-200 focus:border-sky-500 rounded-lg text-xs font-bold text-center" oninput="setManualFlag(this)" />
                </div>
                <div class="space-y-1">
                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Joint (pcs)</label>
                    <input type="number" value="${joint}" class="room-val-joint w-full h-9 px-2 bg-slate-50 border border-slate-200 focus:border-sky-500 rounded-lg text-xs font-bold text-center" oninput="setManualFlag(this)" />
                </div>
            </div>
        </div>
        
        <div class="mt-4 space-y-1">
            <label class="text-[9px] font-bold text-slate-400 uppercase tracking-wider pl-1">Catatan Khusus Ruangan</label>
            <input type="text" value="${notes}" placeholder="Contoh: Tambah 2 Bracket karena gypsum rapuh" class="room-notes w-full h-9 px-3 bg-white border border-slate-200 focus:outline-none focus:border-sky-500 rounded-xl text-xs font-bold text-slate-600" oninput="calculate(false)" />
        </div>
        <div class="room-preview mt-4 p-3 bg-sky-50 border border-sky-100 rounded-xl text-xs text-sky-900 font-medium"></div>
    `;
    list.appendChild(div);
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    calculate(!roomData);
}

function removeRoom(btn) {
    btn.closest('.room-item').remove();
    calculate(false);
}

function setManualFlag(inputEl) {
    const card = inputEl.closest('.room-item');
    card.setAttribute('data-manual', 'true');
    calculate(false);
}

function resetManual(inputEl) {
    const card = inputEl.closest('.room-item');
    card.setAttribute('data-manual', 'false');
    calculate(true);
}

// ==================== CALCULATION ENGINE ====================
// forceRecalc: true if we want to overwrite manual inputs with auto-calculated values (e.g. dimensions changed)
function calculate(forceRecalc = true) {
    let total = 0;
    let details = [];
    let cartItems = [];
    
    // Get master prices & settings
    const pRail = parseFloat(document.getElementById('price_rail').value) || 0;
    const pFabric = parseFloat(document.getElementById('price_fabric').value) || 0;
    const pSew = parseFloat(document.getElementById('price_sew').value) || 0;
    const pBracket = parseFloat(document.getElementById('price_bracket').value) || 0;
    const pCarrier = parseFloat(document.getElementById('price_carrier').value) || 0;
    const pJoint = parseFloat(document.getElementById('price_joint').value) || 0;
    
    const fFullness = parseFloat(document.getElementById('factor_fullness').value) || 2;
    const fBracketDist = parseFloat(document.getElementById('factor_bracket_dist').value) || 0.5;
    const fCarrierPerM = parseFloat(document.getElementById('factor_carrier_perm').value) || 10;

    document.querySelectorAll('.room-item').forEach((el, index) => {
        const name = el.querySelector('.room-name').value;
        const w = parseFloat(el.querySelector('.room-w').value) || 0;
        const p = parseFloat(el.querySelector('.room-p').value) || 0;
        const h = parseFloat(el.querySelector('.room-h').value) || 0;
        const beds = parseInt(el.querySelector('.room-beds').value) || 1;
        const shape = el.querySelector('.room-shape').value;
        const notes = el.querySelector('.room-notes').value || '';
        
        const previewEl = el.querySelector('.room-preview');
        const isManual = el.getAttribute('data-manual') === 'true';
        
        // Target inputs
        const inRel = el.querySelector('.room-val-rel');
        const inKain = el.querySelector('.room-val-kain');
        const inBracket = el.querySelector('.room-val-bracket');
        const inCarrier = el.querySelector('.room-val-carrier');
        const inJoint = el.querySelector('.room-val-joint');

        let autoRelM = 0;
        let autoJointPcs = 0;
        let autoKainM = 0;
        let autoCarrierPcs = 0;
        let autoBracketPcs = 0;
        
        let relCm = 0;
        if (shape === 'U') {
            relCm = w + p + w;
            autoJointPcs = 2;
        } else if (shape === 'L') {
            relCm = w + p;
            autoJointPcs = 1;
        } else {
            relCm = p;
            autoJointPcs = 0;
        }
        
        if (relCm > 0) {
            autoRelM = relCm / 100;
            autoKainM = autoRelM * fFullness;
            autoCarrierPcs = Math.ceil(autoRelM * fCarrierPerM);
            autoBracketPcs = Math.ceil(autoRelM / fBracketDist);
        }

        // If dimensions changed or we explicitly want auto (not overridden), set inputs
        if (!isManual || forceRecalc) {
            inRel.value = autoRelM.toFixed(2);
            inKain.value = autoKainM.toFixed(2);
            inBracket.value = autoBracketPcs;
            inCarrier.value = autoCarrierPcs;
            inJoint.value = autoJointPcs;
        }
        
        // Read active values to compute price
        const activeRel = parseFloat(inRel.value) || 0;
        const activeKain = parseFloat(inKain.value) || 0;
        const activeBracket = parseInt(inBracket.value) || 0;
        const activeCarrier = parseInt(inCarrier.value) || 0;
        const activeJoint = parseInt(inJoint.value) || 0;
        
        if (activeRel > 0 || activeKain > 0) {
            const roomPrice = (activeRel * pRail) + 
                              (activeKain * pFabric) + 
                              (activeKain * pSew) + 
                              (activeBracket * pBracket) + 
                              (activeCarrier * pCarrier) + 
                              (activeJoint * pJoint);
                              
            const roomTotal = roomPrice * beds;
            total += roomTotal;
            
            previewEl.innerHTML = `
                <div class="flex justify-between items-center">
                    <div>
                        Rel: <span class="font-bold text-sky-700">${activeRel.toFixed(2)}m</span> | 
                        Kain: <span class="font-bold text-sky-700">${activeKain.toFixed(2)}m</span> | 
                        Bracket: <span class="font-bold text-sky-700">${activeBracket}pcs</span> | 
                        Carrier: <span class="font-bold text-sky-700">${activeCarrier}pcs</span> | 
                        Joint: <span class="font-bold text-sky-700">${activeJoint}pcs</span>
                        ${isManual ? '<span class="ml-2 px-1.5 py-0.5 text-[9px] bg-amber-100 text-amber-800 rounded font-bold uppercase tracking-wider">Diedit Manual</span>' : ''}
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-sky-600 block">Total Ruangan Ini (${beds} bed):</span>
                        <span class="font-black text-sm text-sky-800">Rp ${roomTotal.toLocaleString('id-ID')}</span>
                    </div>
                </div>
            `;
            
            // Build Invoice details
            let rincianHTML = `
                <div class="flex flex-col bg-white/10 p-3 rounded-xl item-detail" data-labeltext="${name} (${beds} Bed - Tipe ${shape})" data-price="${roomTotal}">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-bold text-sm flex items-center gap-2">${name} <span class="bg-white/20 px-1.5 py-0.5 rounded text-[10px] uppercase">${beds} Bed - Tipe ${shape}</span></p>
                            <p class="text-sky-100 text-[11px] mt-1 space-y-0.5">
                                <span class="block">• Rel: ${activeRel.toFixed(2)}m × Rp ${pRail.toLocaleString('id-ID')}</span>
                                <span class="block">• Kain: ${activeKain.toFixed(2)}m × Rp ${pFabric.toLocaleString('id-ID')}</span>
                                <span class="block">• Jahit: ${activeKain.toFixed(2)}m × Rp ${pSew.toLocaleString('id-ID')}</span>
                                <span class="block">• Komponen: ${activeBracket} Bracket, ${activeCarrier} Carrier, ${activeJoint} Joint</span>
                                ${notes ? `<span class="block text-amber-200 italic font-sans mt-1">• Catatan: "${notes}"</span>` : ''}
                            </p>
                        </div>
                        <div class="text-right shrink-0 ml-4">
                            <p class="font-black">Rp ${roomTotal.toLocaleString('id-ID')}</p>
                            <p class="text-sky-100 text-[11px]">${beds} Bed</p>
                        </div>
                    </div>
                </div>
            `;
            details.push(rincianHTML);
            
            // Push to cart items
            cartItems.push({
                no: index + 1,
                room_name: name,
                shape: shape,
                width: w,
                length: p,
                height: h,
                beds: beds,
                rel: activeRel,
                kain: activeKain,
                bracket: activeBracket,
                carrier: activeCarrier,
                joint: activeJoint,
                notes: notes,
                subtotal: roomTotal
            });
        } else {
            previewEl.innerHTML = `Mohon lengkapi ukuran (Lebar / Panjang).`;
        }
    });

    const container = document.getElementById('resultDetails');
    container.innerHTML = details.join('');

    rawTotal = total;
    calculateTotal();

    document.getElementById('final_cart_json').value = JSON.stringify(cartItems);
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
    items.forEach((item, index) => {
        const priceStr = Number(item.dataset.price).toLocaleString('id-ID');
        notes.push(`${index + 1}. ${item.dataset.labeltext}: Rp ${priceStr}`);
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

function loadCartFromSelector(opt) {
    if (!opt || !opt.value) return;

    // Load discount
    const discount = opt.dataset.discount || 0;
    document.getElementById('discount_input').value = Number(discount).toLocaleString('id-ID');

    // Load cart items
    const cartStr = opt.dataset.cart || '';
    if (cartStr.trim() !== '') {
        try {
            const cartItems = JSON.parse(cartStr);
            if (Array.isArray(cartItems) && cartItems.length > 0) {
                const list = document.getElementById('roomList');
                list.innerHTML = ''; // Clear existing rooms
                cartItems.forEach(item => {
                    addRoom(item);
                });
            }
        } catch (e) {
            console.error("Gagal parsing cart_json:", e);
        }
    }
    calculate(false);
}

// Setup customer selector change event to handle existing discount and cart data
document.getElementById('survey_selector').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    
    // Add default rooms only if this customer has no cart_json
    const cartStr = opt.dataset.cart || '';
    if (!cartStr.trim() || cartStr === '[]') {
        const list = document.getElementById('roomList');
        list.innerHTML = ''; // Clear existing rooms
        addRoom();
        addRoom();
    } else {
        loadCartFromSelector(opt);
    }
});

// ==================== INIT ====================
setTimeout(() => {
    // Initial fetch from select boxes and initial calculation
    updateFabricPrice();
    updateRailPrice();

    // Initialize discount/cart if survey is pre-selected
    const initialSurveyOpt = document.getElementById('survey_selector').selectedOptions[0];
    if (initialSurveyOpt && initialSurveyOpt.value) {
        const cartStr = initialSurveyOpt.dataset.cart || '';
        if (!cartStr.trim() || cartStr === '[]') {
            addRoom();
            addRoom();
        } else {
            loadCartFromSelector(initialSurveyOpt);
        }
    } else {
        addRoom();
        addRoom();
    }
}, 200);
</script>

<?php include '../includes/footer.php'; ?>
