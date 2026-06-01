<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Kalkulator Gorden";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Fetch catalog data
$fabrics_gorden = $pdo->query("SELECT id, name, price_per_meter FROM curtain_fabrics WHERE type = 'gorden' AND is_active = 1 ORDER BY name")->fetchAll();
$fabrics_vitrase = $pdo->query("SELECT id, name, price_per_meter FROM curtain_fabrics WHERE type = 'vitrase' AND is_active = 1 ORDER BY name")->fetchAll();
$rails_single = $pdo->query("SELECT id, name, price_per_meter FROM curtain_rails WHERE type = 'single' AND is_active = 1 ORDER BY name")->fetchAll();
$rails_double = $pdo->query("SELECT id, name, price_per_meter FROM curtain_rails WHERE type = 'double' AND is_active = 1 ORDER BY name")->fetchAll();
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
                <a href="curtain_catalog.php" class="hover:bg-slate-100 rounded-full h-10 w-10 flex items-center justify-center transition-colors -ml-2">
                    <i data-lucide="arrow-left" class="h-5 w-5 text-slate-700"></i>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Kalkulator Harga Gorden</h1>
                    <p class="text-xs text-slate-500 hidden md:block">Hitung estimasi harga gorden untuk customer</p>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto pb-24 md:pb-8">
        <div class="max-w-4xl mx-auto space-y-6">

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
                        <button class="fullness-btn" onclick="setFullness('gorden', 2, this)">×2</button>
                        <button class="fullness-btn active" onclick="setFullness('gorden', 2.5, this)">×2.5</button>
                        <button class="fullness-btn" onclick="setFullness('gorden', 3, this)">×3</button>
                    </div>
                </div>
                <!-- Vitrase Fullness -->
                <div id="vitraseFullnessSection" class="hidden mt-4">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 pl-1">Fullness Vitrase</p>
                    <div class="flex gap-3">
                        <button class="fullness-btn-v active" onclick="setFullness('vitrase', 2, this)">×2</button>
                        <button class="fullness-btn-v" onclick="setFullness('vitrase', 2.5, this)">×2.5</button>
                        <button class="fullness-btn-v" onclick="setFullness('vitrase', 3, this)">×3</button>
                    </div>
                </div>
            </div>

            <!-- STEP 4: Pilih Bahan -->
            <div id="stepMaterial" class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 font-black text-sm">4</div>
                    <h3 class="font-bold text-slate-800">Pilih Bahan</h3>
                </div>

                <!-- Kain Gorden -->
                <div id="selectGorden" class="space-y-2">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Kain Gorden</label>
                    <select id="fabricGorden" onchange="calculate()" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl text-sm font-medium">
                        <?php foreach($fabrics_gorden as $f): ?>
                        <option value="<?php echo $f['id']; ?>" data-price="<?php echo $f['price_per_meter']; ?>"><?php echo htmlspecialchars($f['name']); ?> — Rp <?php echo number_format($f['price_per_meter'],0,',','.'); ?>/m</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Kain Vitrase -->
                <div id="selectVitrase" class="space-y-2 hidden">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider pl-1">Kain Vitrase</label>
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

                <div class="border-t border-white/20 pt-4 flex items-center justify-between">
                    <span class="font-bold text-lg">TOTAL ESTIMASI</span>
                    <span class="text-3xl font-black" id="totalPrice">Rp 0</span>
                </div>
            </div>

            <!-- Share Button -->
            <button onclick="shareToWA()" class="w-full h-14 bg-green-500 hover:bg-green-600 text-white rounded-2xl font-bold text-sm uppercase tracking-widest flex items-center justify-center gap-3 shadow-lg shadow-green-500/20 transition-all active:scale-[0.98]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.553 4.103 1.523 5.824L0 24l6.338-1.51A11.95 11.95 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.818 9.818 0 0 1-5.001-1.373l-.36-.214-3.727.888.939-3.619-.235-.372A9.796 9.796 0 0 1 2.182 12C2.182 6.578 6.578 2.182 12 2.182S21.818 6.578 21.818 12 17.422 21.818 12 21.818z"/></svg>
                Kirim Estimasi via WhatsApp
            </button>

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

// ==================== CALCULATION ENGINE ====================
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
    let details = [];
    let total = 0;
    let sambungMessages = [];

    const optGorden = document.getElementById('fabricGorden').selectedOptions[0];
    const priceGorden = parseFloat(optGorden?.dataset.price || 0);
    const namaGorden = optGorden?.textContent.split(' — ')[0] || '-';
    
    const optVitrase = document.getElementById('fabricVitrase').selectedOptions[0];
    const priceVitrase = parseFloat(optVitrase?.dataset.price || 0);
    const namaVitrase = optVitrase?.textContent.split(' — ')[0] || '-';
    
    const optRel = document.getElementById('railSelect').selectedOptions[0];
    const priceRel = parseFloat(optRel?.dataset.price || 0);
    const namaRel = optRel?.textContent.split(' — ')[0] || '-';

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
            details.push({ labelHTML: `Kain Gorden${winLabel}`, labelText: `Kain Gorden${winTextLabel}: ${namaGorden}`, sub: `${width}cm ÷ 100 × ${gordenFullness} × ${jumlahSambung} = ${meterRounded.toFixed(1)}m`, meter: `${meterRounded.toFixed(1)} m`, price: harga });
            total += harga;
        }
        
        if ([3,4,5].includes(p)) {
            const meterVit = (width / 100) * vitraseFullness * jumlahSambung;
            const meterRounded = Math.ceil(meterVit * 10) / 10;
            const harga = meterRounded * priceVitrase;
            details.push({ labelHTML: `Kain Vitrase${winLabel}`, labelText: `Kain Vitrase${winTextLabel}: ${namaVitrase}`, sub: `${width}cm ÷ 100 × ${vitraseFullness} × ${jumlahSambung} = ${meterRounded.toFixed(1)}m`, meter: `${meterRounded.toFixed(1)} m`, price: harga });
            total += harga;
        }
        
        if ([2,4,5].includes(p)) {
            const harga = meterRel * priceRel;
            details.push({ labelHTML: `Rel${winLabel}`, labelText: `Rel${winTextLabel}: ${namaRel}`, sub: `min(${width}cm, 100cm) → ${meterRel.toFixed(1)}m`, meter: `${meterRel.toFixed(1)} m`, price: harga });
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

    // Render
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

    document.getElementById('totalPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');
    lucide.createIcons();
}

// ==================== SHARE TO WHATSAPP ====================
function shareToWA() {
    const total = document.getElementById('totalPrice').textContent;
    const pkgNames = {1:'Gorden Saja',2:'Gorden + Rel',3:'Vitrase Saja',4:'Vitrase + Rel',5:'Gorden + Rel + Vitrase + Rel'};

    let lines = [
        '*ESTIMASI HARGA GORDEN*',
        '*Sewlovely Homeset*',
        '━━━━━━━━━━━━━━━━━━━━━━',
        '',
        `📦 *Paket:* ${pkgNames[selectedPackage]}`,
        `🔄 *Fullness Gorden:* ×${gordenFullness}`,
    ];

    if ([3,4,5].includes(selectedPackage)) {
        lines.push(`🔄 *Fullness Vitrase:* ×${vitraseFullness}`);
    }

    lines.push('');
    lines.push('📏 *Daftar Ukuran Jendela:*');
    document.querySelectorAll('.window-item').forEach((el, index) => {
        const w = parseFloat(el.querySelector('.window-width').value) || 0;
        const h = parseFloat(el.querySelector('.window-height').value) || 0;
        lines.push(`• Jendela ${index + 1}: ${w}cm × ${h}cm`);
    });

    lines.push('');
    lines.push('📋 *Rincian:*');

    const items = document.getElementById('resultDetails').querySelectorAll('.item-detail');
    items.forEach(item => {
        const label = item.dataset.labeltext || '';
        const price = item.dataset.price || 0;
        lines.push(`• ${label}: Rp ${Number(price).toLocaleString('id-ID')}`);
    });

    lines.push('');
    lines.push(`💰 *Total: ${total}*`);
    lines.push('');
    lines.push('_Harga sudah termasuk ongkos jahit & pasang._');
    lines.push('_Harga dapat berubah sesuai kondisi lapangan._');

    const msg = encodeURIComponent(lines.join('\n'));
    window.open('https://wa.me/?text=' + msg, '_blank');
}

// ==================== INIT ====================
updateVisibility();
calculate();
</script>

<?php include '../includes/footer.php'; ?>
