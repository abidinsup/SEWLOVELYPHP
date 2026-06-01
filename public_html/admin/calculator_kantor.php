<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
require_once '../includes/helpers.php';
checkAdmin();

// ── POST: Simpan Invoice ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['survey_id'])) {
    $survey_id      = intval($_POST['survey_id']);
    $total_raw      = str_replace(['.', ' '], '', $_POST['total_amount'] ?? '0');
    $discount_raw   = str_replace(['.', ' '], '', $_POST['discount_amount'] ?? '0');
    $invoice_notes  = $_POST['invoice_notes'] ?? '';
    $cart_json      = $_POST['cart_json'] ?? '';
    $grand_total    = max(0, intval($total_raw) - intval($discount_raw));
    $invoice_number = generateInvoiceNumber($survey_id);

    try {
        $pdo->beginTransaction();

        $pdo->prepare("UPDATE surveys SET status='waiting_payment' WHERE id=?")->execute([$survey_id]);
        $pdo->prepare("DELETE FROM invoices WHERE survey_id=?")->execute([$survey_id]);
        $secure_token = bin2hex(random_bytes(16));
        $pdo->prepare("INSERT INTO invoices (survey_id,invoice_number,secure_token,total_amount,discount_amount,payment_status,invoice_notes,cart_json) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$survey_id, $invoice_number, $secure_token, $grand_total, intval($discount_raw), 'unpaid', $invoice_notes, $cart_json]);

        $stmtD = $pdo->prepare("SELECT s.*,i.invoice_number,i.secure_token,i.total_amount,i.discount_amount FROM surveys s JOIN invoices i ON i.survey_id=s.id WHERE s.id=?");
        $stmtD->execute([$survey_id]);
        $fd = $stmtD->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        $base   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].str_replace('admin/calculator_kantor.php','',$_SERVER['PHP_SELF']);
        $link   = $base.'view_invoice.php?token='.$fd['secure_token'];
        $disc   = intval($fd['discount_amount'])>0 ? "*DISKON   :* -Rp ".number_format($fd['discount_amount'],0,',','.')."\n" : '';
        $items  = !empty($invoice_notes) ? "*ITEM PESANAN:*\n".$invoice_notes."\n----------------------------\n" : '';

        $wa = "============================\n      *SEWLOVELY HOMESET*   \n============================\n".
              "*NO. NOTA :* ".$fd['invoice_number']."\n".
              "*TANGGAL  :* ".date('d/m/Y')."\n".
              "*PELANGGAN:* ".$fd['customer_name']."\n".
              "----------------------------\n".$items.$disc.
              "*TOTAL    :* Rp ".number_format($fd['total_amount'],0,',','.')."\n".
              "----------------------------\n"."Detail: ".$link."\n============================\nTerima kasih telah memesan!";

        $ph = preg_replace('/[^0-9]/','', $fd['customer_phone']);
        if (substr($ph,0,1)==='0') $ph='62'.substr($ph,1);
        $waLink = "https://wa.me/".$ph."?text=".urlencode($wa);

        echo "<script>
        document.addEventListener('DOMContentLoaded',function(){
            Swal.fire({
                title:'<div class=\"text-2xl font-black text-slate-900 mb-2\">Invoice Berhasil Dibuat!</div>',
                html:`<div class=\"space-y-4\">
                    <div class=\"bg-violet-50 p-6 rounded-[2rem] border border-violet-100 mb-6\">
                        <div class=\"w-16 h-16 bg-violet-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-600/20 mx-auto mb-4\">
                            <i data-lucide=\"check-circle\" class=\"w-8 h-8 text-white\"></i>
                        </div>
                        <p class=\"text-violet-800 font-bold text-sm uppercase tracking-widest\">Invoice Berhasil Dibuat</p>
                        <p class=\"text-violet-600/70 font-medium text-xs mt-1\">".$fd['invoice_number']."</p>
                    </div>
                    <div class=\"grid grid-cols-1 gap-3\">
                        <a href=\"print_spk.php?id=".$survey_id."\" target=\"_blank\" class=\"flex items-center justify-center gap-3 w-full py-4 bg-violet-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-violet-600/20 hover:bg-violet-700 transition-all\">
                            <i data-lucide=\"printer\" class=\"w-5 h-5\"></i> Cetak Nota
                        </a>
                        <a href=\"".$waLink."\" target=\"_blank\" class=\"flex items-center justify-center gap-3 w-full py-4 bg-emerald-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-emerald-600/20 hover:bg-emerald-700 transition-all\">
                            <i data-lucide=\"message-circle\" class=\"w-5 h-5\"></i> Kirim Nota ke WA
                        </a>
                        <a href=\"surveys.php\" class=\"flex items-center justify-center gap-3 w-full py-4 bg-slate-100 text-slate-500 rounded-2xl font-bold text-sm uppercase tracking-widest hover:bg-slate-200 transition-all\">
                            Selesai / Kembali
                        </a>
                    </div>
                </div>`,
                showConfirmButton:false,allowOutsideClick:false,
                customClass:{popup:'rounded-[3rem] p-10'},
                didOpen:()=>{ if(typeof lucide!=='undefined') lucide.createIcons(); }
            });
        });
        </script>";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = "Gagal menyimpan: ".$e->getMessage();
    }
}

// ── Data ─────────────────────────────────────────────────────────────────────
$active_surveys = $pdo->query("SELECT s.id,s.customer_name,s.customer_phone,s.calculator_type,p.full_name as partner_name,i.discount_amount
    FROM surveys s JOIN partners p ON s.partner_id=p.id
    LEFT JOIN invoices i ON i.survey_id=s.id
    WHERE s.status IN ('survey','waiting_payment','production')
    AND (s.calculator_type = 'kantor' OR s.calculator_type IS NULL OR s.calculator_type = '')
    ORDER BY s.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$selected_id = $_GET['survey_id'] ?? '';
$page_title  = "Kalkulator Gorden Kantor";
include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<style>
.result-card { background: linear-gradient(135deg,#7c3aed,#a855f7); border-radius:1.5rem; padding:2rem; color:#fff; }
.item-row { background:#f8fafc; border:1px solid #e2e8f0; border-radius:1rem; padding:1rem; position:relative; transition:all .2s; }
.item-row:hover { border-color:#c4b5fd; box-shadow:0 4px 12px rgba(124,58,237,.08); }
.product-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:8px; font-size:11px; font-weight:800; background:#ede9fe; color:#7c3aed; }
.luas-badge { font-size:11px; font-weight:700; color:#6d28d9; background:#f5f3ff; padding:2px 8px; border-radius:6px; }
input.calc-input { width:100%; height:40px; padding:0 10px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; font-weight:700; background:#fff; transition:all .2s; }
input.calc-input:focus { outline:none; border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.12); }
select.calc-select { width:100%; height:40px; padding:0 10px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; font-weight:700; background:#fff; cursor:pointer; transition:all .2s; }
select.calc-select:focus { outline:none; border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.12); }
.btn-del { position:absolute; top:10px; right:10px; background:#fff; border:1.5px solid #fee2e2; color:#ef4444; border-radius:8px; padding:5px 8px; cursor:pointer; transition:all .2s; }
.btn-del:hover { background:#fef2f2; border-color:#ef4444; }
.lbl { font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.08em; display:block; margin-bottom:4px; padding-left:2px; }

/* Custom Violet Utility Styles for compiled CSS fallback */
.bg-violet-50 { background-color: #f5f3ff !important; }
.bg-violet-100 { background-color: #ede9fe !important; }
.bg-violet-600 { background-color: #7c3aed !important; }
.bg-violet-700 { background-color: #6d28d9 !important; }
.hover\:bg-violet-100:hover { background-color: #ede9fe !important; }
.hover\:bg-violet-700:hover { background-color: #6d28d9 !important; }
.text-violet-50 { color: #f5f3ff !important; }
.text-violet-100 { color: #ede9fe !important; }
.text-violet-200 { color: #ddd6fe !important; }
.text-violet-600 { color: #7c3aed !important; }
.text-violet-700 { color: #6d28d9 !important; }
.text-violet-800 { color: #5b21b6 !important; }
.shadow-violet-500\/25 { box-shadow: 0 10px 15px -3px rgba(124, 58, 237, 0.25) !important; }
.shadow-violet-600\/20 { box-shadow: 0 10px 15px -3px rgba(109, 40, 217, 0.2) !important; }
.border-violet-100 { border-color: #ede9fe !important; }
.pl-9 { padding-left: 36px !important; }
</style>

<div class="flex-1 flex flex-col min-h-screen w-full bg-slate-50">
  <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm md:mt-0 mt-16 print:hidden">
    <div class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Kalkulator Gorden Kantor</h1>
        <p class="text-xs text-slate-500 hidden md:block">Roller, Venetian, Vertical, Roman, Zebra & Wooden Blind — hitung per m²</p>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <span style="font-size:11px;background:#ede9fe;color:#6d28d9;font-weight:900;padding:5px 14px;border-radius:999px;text-transform:uppercase;letter-spacing:.08em;">Kantor</span>
      </div>
    </div>
  </header>

  <main class="flex-1 p-4 md:p-8 pb-28 md:pb-8">
    <div class="max-w-4xl mx-auto space-y-6">

      <?php if(isset($error)): ?>
      <div class="bg-red-50 text-red-600 p-4 rounded-xl text-sm font-medium border border-red-100 flex gap-2 items-center">
        <i data-lucide="alert-circle" class="h-5 w-5"></i><?= $error ?>
      </div>
      <?php endif; ?>

      <form method="POST" id="posForm" class="space-y-6">

        <!-- 1. Pilih Customer -->
        <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
          <label class="text-xs font-black text-slate-400 uppercase tracking-widest mb-1 block">Pilih Customer</label>
          <h2 class="text-lg font-bold text-slate-800 mb-4">Riwayat Survey Aktif</h2>
          <select name="survey_id" id="survey_selector" required
            class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 rounded-xl text-sm font-medium">
            <option value="">-- Pilih Customer --</option>
            <?php foreach($active_surveys as $s): ?>
            <option value="<?= $s['id'] ?>" <?= ($selected_id==$s['id'])?'selected':'' ?>
              data-discount="<?= floatval($s['discount_amount']??0) ?>">
              <?= htmlspecialchars($s['customer_name']) ?> (<?= htmlspecialchars($s['customer_phone']) ?>) — <?= htmlspecialchars($s['partner_name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- 2. Daftar Item Produk -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:12px;">
              <div style="width:32px;height:32px;background:#ede9fe;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#7c3aed;font-weight:900;font-size:14px;">2</div>
              <h3 style="font-weight:700;color:#1e293b;font-size:15px;margin:0;">Daftar Item Produk</h3>
            </div>
            <button type="button" onclick="addItem()"
              style="font-size:12px;background:#f5f3ff;color:#7c3aed;border:none;padding:8px 14px;border-radius:12px;font-weight:700;display:flex;align-items:center;gap:6px;cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.08);">
              <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              Tambah Item
            </button>
          </div>

          <div id="itemList" class="space-y-3"></div>
          <p id="emptyHint" style="text-align:center;color:#94a3b8;font-size:13px;padding:32px 0;font-weight:500;">
            Klik <strong>Tambah Item</strong> untuk mulai menambahkan produk
          </p>
        </div>

        <!-- 3. Hasil & Total -->
        <div class="result-card">
          <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
            <div style="width:42px;height:42px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            </div>
            <div>
              <h3 style="font-weight:700;font-size:17px;margin:0;">Rincian Quotation</h3>
              <p style="color:rgba(255,255,255,.7);font-size:11px;margin:3px 0 0;">Harga per m² × luas × qty</p>
            </div>
          </div>

          <div id="resultDetails" style="display:flex; flex-direction:column; gap:8px; margin-bottom:20px;"></div>

          <!-- Diskon -->
          <div style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.12);padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,.2);margin-bottom:16px;">
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="opacity:.8"><line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>
              <span style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;opacity:.9;">Diskon</span>
            </div>
            <div style="position:relative;flex:1;">
              <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:12px;font-weight:800;color:#6d28d9;">Rp</span>
              <input type="text" id="discount_input" value="0" placeholder="0"
                style="width:100%;padding:8px 12px 8px 32px;background:rgba(255,255,255,.92);border:none;border-radius:8px;font-size:13px;font-weight:700;color:#1e293b;outline:none;box-sizing:border-box;"
                oninput="fmtRp(this);recalcTotal()">
            </div>
          </div>

          <div style="border-top:1px solid rgba(255,255,255,.2);padding-top:16px;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-weight:700;font-size:16px;">GRAND TOTAL</span>
            <span style="font-size:28px;font-weight:900;" id="totalPrice">Rp 0</span>
          </div>
        </div>

        <!-- Hidden fields -->
        <input type="hidden" id="final_total_amount"   name="total_amount">
        <input type="hidden" id="final_discount_amount" name="discount_amount">
        <input type="hidden" id="final_invoice_notes"   name="invoice_notes">
        <input type="hidden" id="final_cart_json"       name="cart_json">
        <input type="hidden" name="payment_status" value="unpaid">

        <button type="submit" id="btn_checkout" class="w-full h-14 bg-violet-600 hover:bg-violet-700 text-white rounded-2xl font-bold text-sm uppercase tracking-widest flex items-center justify-center gap-3 shadow-lg shadow-violet-600/20 transition-all active:scale-[0.98]">
          <i data-lucide="receipt" class="h-5 w-5"></i> Buat Invoice &amp; POS
        </button>

      </form>
    </div>
  </main>
</div>

<script>
// ─── Produk ────────────────────────────────────────────────────────────────
const PRODUCTS = [
  'Roller Blind','Venetian Blind','Vertical Blind',
  'Roman Shade','Zebra Blind','Wooden Blind'
];

let itemCount = 0;
let rawTotal  = 0;

// ─── Tambah Item ──────────────────────────────────────────────────────────
function addItem(data = null) {
  itemCount++;
  const id   = itemCount;
  const wrap = document.getElementById('itemList');
  document.getElementById('emptyHint').style.display = 'none';

  const opts = PRODUCTS.map((p,i) =>
    `<option value="${p}" ${data?.product===p||(!data&&i===0)?'selected':''}>${p}</option>`
  ).join('');

  const div = document.createElement('div');
  div.className = 'item-row';
  div.dataset.id = id;
  div.innerHTML = `
    <!-- Baris 1: Produk + Lebar + Tinggi + Qty + tombol hapus -->
    <div style="display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap; margin-bottom:12px; padding-right:44px;">
      <div style="flex:2; min-width:160px;">
        <span class="lbl">Produk</span>
        <select class="calc-select i-product" onchange="recalcAll()" style="width:100%;">
          ${opts}
        </select>
      </div>
      <div style="flex:1; min-width:90px;">
        <span class="lbl">Lebar (cm)</span>
        <input type="number" class="calc-input i-lebar" value="${data?.lebar??100}" min="1" step="0.1" oninput="recalcAll()">
      </div>
      <div style="flex:1; min-width:90px;">
        <span class="lbl">Tinggi (cm)</span>
        <input type="number" class="calc-input i-tinggi" value="${data?.tinggi??100}" min="1" step="0.1" oninput="recalcAll()">
      </div>
      <div style="flex:1; min-width:70px;">
        <span class="lbl">Qty</span>
        <input type="number" class="calc-input i-qty" value="${data?.qty??1}" min="1" step="1" oninput="recalcAll()">
      </div>
    </div>

    <!-- Tombol Hapus absolute -->
    <button type="button" class="btn-del" onclick="removeItem(${id})" style="position:absolute;top:12px;right:12px;background:#fff;border:1.5px solid #fee2e2;color:#ef4444;border-radius:8px;padding:6px 9px;cursor:pointer;display:flex;align-items:center;">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M9 6V4h6v2"></path></svg>
    </button>

    <!-- Baris 2: Harga + Luas + Subtotal -->
    <div style="display:flex; align-items:flex-end; gap:10px; flex-wrap:wrap;">
      <div style="flex:2; min-width:150px;">
        <span class="lbl">Harga / m²</span>
        <div style="position:relative;">
          <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);font-size:12px;font-weight:800;color:#94a3b8;pointer-events:none;">Rp</span>
          <input type="text" class="calc-input i-harga" value="${data?.harga??'0'}" placeholder="230.019"
            style="padding-left:32px;"
            oninput="fmtRp(this);recalcAll()">
        </div>
      </div>
      <div style="flex:1; min-width:100px;">
        <span class="lbl">Luas (m²)</span>
        <div class="i-luas" style="height:40px;display:flex;align-items:center;padding:0 12px;border-radius:10px;background:#f5f3ff;font-size:13px;font-weight:700;color:#6d28d9;border:1.5px solid #ede9fe;">0.000 m²</div>
      </div>
      <div style="flex:1; min-width:110px; text-align:right;">
        <span class="lbl" style="text-align:right;display:block;">Subtotal</span>
        <div class="i-subtotal" style="font-size:17px;font-weight:900;color:#6d28d9;padding:4px 0;">Rp 0</div>
      </div>
    </div>
  `;
  wrap.appendChild(div);
  lucide.createIcons();
  recalcAll();
}

// ─── Hapus Item ───────────────────────────────────────────────────────────
function removeItem(id) {
  const el = document.querySelector(`.item-row[data-id="${id}"]`);
  if (el) el.remove();
  if (document.querySelectorAll('.item-row').length === 0)
    document.getElementById('emptyHint').style.display = '';
  recalcAll();
}

// ─── Format Rupiah ─────────────────────────────────────────────────────────
function fmtRp(el) {
  let v = el.value.replace(/\./g,'').replace(/[^\d]/g,'');
  if (!v) { el.value='0'; return; }
  el.value = parseInt(v,10).toLocaleString('id-ID');
}

function parseRp(str) {
  return parseFloat(str.replace(/\./g,'').replace(',','.')) || 0;
}

// ─── Hitung Semua ─────────────────────────────────────────────────────────
function recalcAll() {
  let total   = 0;
  let details = [];
  let noteLines = [];
  let cartItems = [];

  document.querySelectorAll('.item-row').forEach((row, idx) => {
    const no      = idx + 1;
    const product = row.querySelector('.i-product').value;
    const lebar   = parseFloat(row.querySelector('.i-lebar').value) || 0;
    const tinggi  = parseFloat(row.querySelector('.i-tinggi').value) || 0;
    const qty     = parseInt(row.querySelector('.i-qty').value) || 1;
    const harga   = parseRp(row.querySelector('.i-harga').value);

    const luas     = (lebar / 100) * (tinggi / 100);
    const subtotal = Math.round(luas * harga * qty);

    // Update UI dalam card
    row.querySelector('.i-luas').textContent    = luas.toLocaleString('id-ID', {maximumFractionDigits:3}) + ' m²';
    row.querySelector('.i-subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');

    total += subtotal;

    // Pipe format untuk print_spk.php
    // | No | Nama Produk | Qty | Harga Satuan | Total |
    const nameFull = product;
    const hargaFmt = harga.toLocaleString('id-ID');
    const totalFmt = subtotal.toLocaleString('id-ID');
    noteLines.push(`| ${no} | ${nameFull} | ${qty} | ${hargaFmt} | ${totalFmt} |`);

    // Result card detail
    details.push(`
      <div class="flex items-start justify-between bg-white/10 p-3 rounded-xl">
        <div>
          <p class="font-bold text-sm">${product}</p>
          <p class="text-violet-100 text-[11px] mt-0.5">
            ${lebar}cm × ${tinggi}cm = ${luas.toLocaleString('id-ID', {maximumFractionDigits:3})} m² × Rp ${harga.toLocaleString('id-ID')} × ${qty} unit
          </p>
        </div>
        <div class="text-right shrink-0 ml-4">
          <p class="font-black">Rp ${subtotal.toLocaleString('id-ID')}</p>
          <p class="text-violet-100 text-[11px]">${luas.toLocaleString('id-ID', {maximumFractionDigits:3})} m²</p>
        </div>
      </div>
    `);

    cartItems.push({ no, product, lebar, tinggi, luas: parseFloat(luas.toFixed(3)), qty, harga, subtotal });
  });

  document.getElementById('resultDetails').innerHTML =
    details.length ? details.join('') :
    '<p class="text-center text-violet-200 text-sm py-4">Belum ada item ditambahkan</p>';

  rawTotal = total;
  document.getElementById('final_invoice_notes').value = noteLines.join('\n');
  document.getElementById('final_cart_json').value     = JSON.stringify(cartItems);
  recalcTotal();
}

function recalcTotal() {
  const disc      = parseRp(document.getElementById('discount_input').value);
  const grandTotal = Math.max(0, rawTotal - disc);
  document.getElementById('totalPrice').textContent       = 'Rp ' + grandTotal.toLocaleString('id-ID');
  document.getElementById('final_total_amount').value     = rawTotal;
  document.getElementById('final_discount_amount').value  = disc;
}

// ─── Validasi Submit ──────────────────────────────────────────────────────
document.getElementById('posForm').addEventListener('submit', function(e) {
  if (!document.getElementById('survey_selector').value) {
    e.preventDefault();
    Swal.fire('Peringatan','Silakan pilih customer terlebih dahulu!','warning');
    return;
  }
  if (document.querySelectorAll('.item-row').length === 0) {
    e.preventDefault();
    Swal.fire('Peringatan','Tambahkan minimal satu item produk!','warning');
    return;
  }
  if (rawTotal <= 0) {
    e.preventDefault();
    Swal.fire('Peringatan','Total pesanan tidak boleh 0!','warning');
    return;
  }
});

// ─── Sinkron diskon dari customer ────────────────────────────────────────
document.getElementById('survey_selector').addEventListener('change', function() {
  const opt = this.options[this.selectedIndex];
  if (opt && opt.value) {
    const d = parseFloat(opt.dataset.discount)||0;
    document.getElementById('discount_input').value = d>0 ? d.toLocaleString('id-ID') : '0';
    recalcTotal();
  }
});

// ─── Init ─────────────────────────────────────────────────────────────────
const initOpt = document.getElementById('survey_selector').selectedOptions[0];
if (initOpt && initOpt.value) {
  const d = parseFloat(initOpt.dataset.discount)||0;
  document.getElementById('discount_input').value = d>0 ? d.toLocaleString('id-ID') : '0';
}

// Auto tambah 1 item contoh Venetian Blind sesuai request user
addItem({ product:'Venetian Blind', lebar:115, tinggi:160, qty:1, harga:'230.019' });
recalcTotal();
</script>

<?php include '../includes/footer.php'; ?>
