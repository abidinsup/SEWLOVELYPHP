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

                <!-- 2. DAFTAR JENDELA / ITEM -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 font-black text-sm"><i data-lucide="layout" class="h-4 w-4"></i></div>
                            <h3 class="font-bold text-slate-800">Daftar Jendela (Item)</h3>
                        </div>
                        <button type="button" onclick="addWindow()" class="text-xs bg-emerald-50 text-emerald-600 hover:bg-emerald-100 px-3 py-1.5 rounded-xl font-bold flex items-center gap-1.5 transition-all shadow-sm">
                            <i data-lucide="plus" class="h-3 w-3"></i> Tambah Jendela
                        </button>
                    </div>
                    
                    <div id="windowList">
                        <!-- Items will be injected here -->
                    </div>
                    <p id="emptyHint" class="text-center text-slate-400 text-sm py-8 font-medium">
                        Klik <strong>Tambah Jendela</strong> untuk mulai menghitung estimasi
                    </p>
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
const fabricsGorden = <?php echo json_encode($fabrics_gorden); ?>;
const fabricsVitrase = <?php echo json_encode($fabrics_vitrase); ?>;
const railsSingle = <?php echo json_encode($rails_single); ?>;
const railsDouble = <?php echo json_encode($rails_double); ?>;

// Options HTML pre-generation
const optsGorden = fabricsGorden.map(f => `<option value="${f.id}" data-price="${f.price_per_meter}">${f.name} — Rp ${Number(f.price_per_meter).toLocaleString('id-ID')}/m</option>`).join('');
const optsVitrase = fabricsVitrase.map(f => `<option value="${f.id}" data-price="${f.price_per_meter}">${f.name} — Rp ${Number(f.price_per_meter).toLocaleString('id-ID')}/m</option>`).join('');
const optsRailSingle = railsSingle.map(f => `<option value="${f.id}" data-price="${f.price_per_meter}">${f.name} — Rp ${Number(f.price_per_meter).toLocaleString('id-ID')}/m</option>`).join('');
const optsRailDouble = railsDouble.map(f => `<option value="${f.id}" data-price="${f.price_per_meter}">${f.name} — Rp ${Number(f.price_per_meter).toLocaleString('id-ID')}/m</option>`).join('');

const KAIN_WIDTH = 280;
const KELIMAN = 20;

let windowCount = 0;
let rawTotal = 0;

// ==================== WINDOW MULTIPLIER ====================
function addWindow(data = null) {
    windowCount++;
    const id = windowCount;
    const list = document.getElementById('windowList');
    document.getElementById('emptyHint').style.display = 'none';

    const div = document.createElement('div');
    div.className = 'window-item bg-white p-5 rounded-2xl border border-slate-200 shadow-sm relative mb-4 transition-all hover:border-emerald-200';
    div.dataset.id = id;

    div.innerHTML = `
        <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 bg-emerald-100 text-emerald-600 rounded-md flex items-center justify-center font-bold text-xs">${id}</span>
                <input type="text" class="i-name font-bold text-sm text-slate-800 border-none bg-transparent focus:outline-none focus:ring-0 p-0 w-48" value="${data?.name ?? 'Jendela ' + id}" oninput="recalcAll()" placeholder="Nama Jendela">
            </div>
            <button type="button" onclick="removeWindow(${id})" class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 p-1.5 rounded-lg shadow-sm transition-all"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-10 gap-4 mb-3">
            <!-- Paket -->
            <div class="md:col-span-2 space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Paket</label>
                <select class="i-paket w-full h-10 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:border-emerald-500 focus:outline-none" onchange="updateWindowVisibility(${id}); recalcAll()">
                    <option value="1">1 (Gorden)</option>
                    <option value="2">2 (Gorden+Rel)</option>
                    <option value="3">3 (Vitrase)</option>
                    <option value="4">4 (Vitrase+Rel)</option>
                    <option value="5">5 (Gor+Rel+Vit+Rel)</option>
                </select>
            </div>

            <!-- Ukuran -->
            <div class="md:col-span-2 space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Lebar Rel (cm)</label>
                <input type="number" class="i-lebar w-full h-10 px-3 bg-white border border-slate-200 focus:border-emerald-500 focus:outline-none rounded-xl text-xs font-bold text-center" value="${data?.lebar ?? 200}" min="30" oninput="recalcAll()">
            </div>
            <div class="md:col-span-2 space-y-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Tinggi (cm)</label>
                <input type="number" class="i-tinggi w-full h-10 px-3 bg-white border border-slate-200 focus:border-emerald-500 focus:outline-none rounded-xl text-xs font-bold text-center" value="${data?.tinggi ?? 250}" min="50" oninput="recalcAll()">
            </div>
            
            <!-- Fullness -->
            <div class="md:col-span-2 space-y-1 i-gorden-fullness-group">
                <label class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider pl-1">Lipatan Gor.</label>
                <select class="i-gorden-fullness w-full h-10 px-3 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold focus:outline-none" onchange="recalcAll()">
                    <option value="2">x2</option>
                    <option value="2.5" selected>x2.5</option>
                    <option value="3">x3</option>
                </select>
            </div>
            <div class="md:col-span-2 space-y-1 i-vitrase-fullness-group hidden">
                <label class="text-[10px] font-bold text-sky-600 uppercase tracking-wider pl-1">Lipatan Vit.</label>
                <select class="i-vitrase-fullness w-full h-10 px-3 bg-sky-50 text-sky-700 border border-sky-200 rounded-xl text-xs font-bold focus:outline-none" onchange="recalcAll()">
                    <option value="2" selected>x2</option>
                    <option value="2.5">x2.5</option>
                    <option value="3">x3</option>
                </select>
            </div>
        </div>

        <!-- Bahan -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-3 border-t border-slate-100">
            <div class="space-y-1 i-gorden-group">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Kain Gorden</label>
                <select class="i-gorden w-full h-10 px-3 bg-slate-50 border border-slate-200 focus:border-emerald-500 focus:outline-none rounded-xl text-xs font-medium" onchange="recalcAll()">
                    ${optsGorden}
                </select>
            </div>
            <div class="space-y-1 i-vitrase-group hidden">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1">Kain Vitrase</label>
                <select class="i-vitrase w-full h-10 px-3 bg-slate-50 border border-slate-200 focus:border-emerald-500 focus:outline-none rounded-xl text-xs font-medium" onchange="recalcAll()">
                    ${optsVitrase}
                </select>
            </div>
            <div class="space-y-1 i-rel-group hidden">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider pl-1 i-rel-label">Rel</label>
                <select class="i-rel w-full h-10 px-3 bg-slate-50 border border-slate-200 focus:border-emerald-500 focus:outline-none rounded-xl text-xs font-medium" onchange="recalcAll()">
                </select>
            </div>
        </div>
        
        <div class="mt-3 bg-amber-50 p-3 rounded-xl border border-amber-200 text-xs text-amber-800 hidden i-sambung-info font-medium flex items-start gap-2">
            <i data-lucide="alert-triangle" class="h-4 w-4 mt-0.5 shrink-0 text-amber-600"></i>
            <span class="i-sambung-text"></span>
        </div>
    `;

    list.appendChild(div);
    if (typeof lucide !== 'undefined') lucide.createIcons();
    
    // Set explicit values if data provided (for loading cart state if implemented)
    if (data) {
        div.querySelector('.i-paket').value = data.paket;
        div.querySelector('.i-gorden-fullness').value = data.gFullness;
        div.querySelector('.i-vitrase-fullness').value = data.vFullness;
        if(data.gordenId) div.querySelector('.i-gorden').value = data.gordenId;
        if(data.vitraseId) div.querySelector('.i-vitrase').value = data.vitraseId;
    }
    
    updateWindowVisibility(id);
    if (data && data.relId) div.querySelector('.i-rel').value = data.relId;

    recalcAll();
}

function removeWindow(id) {
    const el = document.querySelector(`.window-item[data-id="${id}"]`);
    if (el) el.remove();
    if (document.querySelectorAll('.window-item').length === 0) {
        document.getElementById('emptyHint').style.display = 'block';
    }
    recalcAll();
}

function updateWindowVisibility(id) {
    const el = document.querySelector(`.window-item[data-id="${id}"]`);
    if (!el) return;

    const p = parseInt(el.querySelector('.i-paket').value);
    const hasGorden = [1,2,5].includes(p);
    const hasVitrase = [3,4,5].includes(p);
    const hasRel = [2,4,5].includes(p);

    el.querySelector('.i-gorden-group').classList.toggle('hidden', !hasGorden);
    el.querySelector('.i-gorden-fullness-group').classList.toggle('hidden', !hasGorden);
    
    el.querySelector('.i-vitrase-group').classList.toggle('hidden', !hasVitrase);
    el.querySelector('.i-vitrase-fullness-group').classList.toggle('hidden', !hasVitrase);
    
    el.querySelector('.i-rel-group').classList.toggle('hidden', !hasRel);

    if (hasRel) {
        const label = (p === 5) ? 'Rel Double (Twin)' : 'Rel';
        el.querySelector('.i-rel-label').textContent = label;
        const sel = el.querySelector('.i-rel');
        const prevVal = sel.value; // Remember previous selection
        sel.innerHTML = (p === 5) ? optsRailDouble : optsRailSingle;
        if (sel.innerHTML.trim() === '' && p === 5) {
            sel.innerHTML = optsRailSingle; // fallback
        }
        if (prevVal) sel.value = prevVal; // Try to restore if possible
    }
}

// ==================== CALCULATION ENGINE ====================
function recalcAll() {
    let total = 0;
    let details = [];
    let cartItems = [];
    let noteLines = [];

    document.querySelectorAll('.window-item').forEach((el, index) => {
        const name = el.querySelector('.i-name').value || `Jendela ${index+1}`;
        const p = parseInt(el.querySelector('.i-paket').value);
        const w = parseFloat(el.querySelector('.i-lebar').value) || 0;
        const h = parseFloat(el.querySelector('.i-tinggi').value) || 0;
        
        const gFullness = parseFloat(el.querySelector('.i-gorden-fullness').value) || 2.5;
        const vFullness = parseFloat(el.querySelector('.i-vitrase-fullness').value) || 2;
        
        const optGorden = el.querySelector('.i-gorden').selectedOptions[0];
        const pGorden = parseFloat(optGorden?.dataset.price || 0);
        const nGorden = optGorden ? optGorden.text.split(' —')[0] : '';
        
        const optVitrase = el.querySelector('.i-vitrase').selectedOptions[0];
        const pVitrase = parseFloat(optVitrase?.dataset.price || 0);
        const nVitrase = optVitrase ? optVitrase.text.split(' —')[0] : '';
        
        const optRel = el.querySelector('.i-rel').selectedOptions[0];
        const pRel = parseFloat(optRel?.dataset.price || 0);
        const nRel = optRel ? optRel.text.split(' —')[0] : '';

        if (w > 0 && h > 0) {
            let itemTotal = 0;
            const tinggiEfektif = h + KELIMAN;
            const jumlahSambung = Math.ceil(tinggiEfektif / KAIN_WIDTH);
            
            const sambungInfo = el.querySelector('.i-sambung-info');
            if (jumlahSambung > 1) {
                sambungInfo.classList.remove('hidden');
                el.querySelector('.i-sambung-text').innerHTML = `Tinggi perlu disambung ${jumlahSambung - 1}×.`;
            } else {
                sambungInfo.classList.add('hidden');
            }
            
            const meterRel = Math.max(w, 100) / 100;
            
            let htmlParts = [];
            let textParts = [];

            if ([1,2,5].includes(p)) {
                const meterKain = (w / 100) * gFullness * jumlahSambung;
                const meterRounded = Math.ceil(meterKain * 10) / 10;
                const harga = meterRounded * pGorden;
                itemTotal += harga;
                htmlParts.push(`<span class="block">• Gor (${nGorden}): ${meterRounded.toFixed(1)}m × Rp ${pGorden.toLocaleString('id-ID')} = Rp ${harga.toLocaleString('id-ID')}</span>`);
                textParts.push(`- Gor (${nGorden}): ${meterRounded.toFixed(1)}m x Rp ${pGorden.toLocaleString('id-ID')} = Rp ${harga.toLocaleString('id-ID')}`);
            }
            if ([3,4,5].includes(p)) {
                const meterVit = (w / 100) * vFullness * jumlahSambung;
                const meterRounded = Math.ceil(meterVit * 10) / 10;
                const harga = meterRounded * pVitrase;
                itemTotal += harga;
                htmlParts.push(`<span class="block">• Vit (${nVitrase}): ${meterRounded.toFixed(1)}m × Rp ${pVitrase.toLocaleString('id-ID')} = Rp ${harga.toLocaleString('id-ID')}</span>`);
                textParts.push(`- Vit (${nVitrase}): ${meterRounded.toFixed(1)}m x Rp ${pVitrase.toLocaleString('id-ID')} = Rp ${harga.toLocaleString('id-ID')}`);
            }
            if ([2,4,5].includes(p)) {
                const harga = meterRel * pRel;
                itemTotal += harga;
                htmlParts.push(`<span class="block">• Rel (${nRel}): ${meterRel.toFixed(1)}m × Rp ${pRel.toLocaleString('id-ID')} = Rp ${harga.toLocaleString('id-ID')}</span>`);
                textParts.push(`- Rel (${nRel}): ${meterRel.toFixed(1)}m x Rp ${pRel.toLocaleString('id-ID')} = Rp ${harga.toLocaleString('id-ID')}`);
            }
            
            total += itemTotal;

            const pkgFullNames = {
                1: "1 (Gorden)",
                2: "2 (Gorden+Rel)",
                3: "3 (Vitrase)",
                4: "4 (Vitrase+Rel)",
                5: "5 (Gor+Rel+Vit+Rel)"
            };
            
            details.push(`
                <div class="flex items-start justify-between bg-white/10 p-3 rounded-xl item-detail">
                    <div>
                        <p class="font-bold text-sm flex items-center gap-2">${name} <span class="bg-white/20 px-1.5 py-0.5 rounded text-[10px] uppercase">Paket ${pkgFullNames[p]}</span></p>
                        <div class="text-emerald-100 text-[11px] mt-1 space-y-0.5">
                            ${htmlParts.join('')}
                        </div>
                    </div>
                    <div class="text-right shrink-0 ml-4">
                        <p class="font-black text-sm">Rp ${itemTotal.toLocaleString('id-ID')}</p>
                    </div>
                </div>
            `);

            // Use the classic format so it parses perfectly in print_spk.php / view_invoice.php
            noteLines.push(`${name} (Paket ${pkgFullNames[p]}): Rp ${itemTotal.toLocaleString('id-ID')}`);
            
            cartItems.push({
                no: index+1, name, paket: p, w, h, gFullness, vFullness, 
                gordenId: optGorden?.value, vitraseId: optVitrase?.value, relId: optRel?.value,
                subtotal: itemTotal
            });
        }
    });

    const container = document.getElementById('resultDetails');
    container.innerHTML = details.length ? details.join('') : '<p class="text-center text-emerald-100/70 text-sm py-4 font-medium">Belum ada item ditambahkan</p>';

    rawTotal = total;
    
    document.getElementById('final_cart_json').value = JSON.stringify(cartItems);
    document.getElementById('final_invoice_notes').value = noteLines.join('\n');
    
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

// Setup customer selector change event to handle existing discount and cart
document.getElementById('survey_selector').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.value) {
        const discount = opt.dataset.discount || 0;
        document.getElementById('discount_input').value = Number(discount).toLocaleString('id-ID');
        // Calculate will use this new discount value
        recalcAll();
    }
});

// ==================== INIT ====================
// Add first default window
addWindow();

// Initialize discount if survey is pre-selected
const initialSurveyOpt = document.getElementById('survey_selector').selectedOptions[0];
if (initialSurveyOpt && initialSurveyOpt.value) {
    const initDiscount = initialSurveyOpt.dataset.discount || 0;
    document.getElementById('discount_input').value = Number(initDiscount).toLocaleString('id-ID');
    recalcAll();
}
</script>

<?php include '../includes/footer.php'; ?>
