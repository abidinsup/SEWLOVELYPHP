<?php
$calc_path = 'public_html/admin/calculator.php';
$adv_calc_path = 'public_html/admin/curtain_calculator.php';

$calc_content = file_get_contents($calc_path);
$adv_calc_content = file_get_contents($adv_calc_path);

// 1. Extract PHP DB fetches from curtain_calculator
preg_match('/\$fabrics_gorden = (.*?)\$rails_double = (.*?);/s', $adv_calc_content, $matches);
if (count($matches) > 0) {
    $db_fetches = $matches[0];
    // Insert into calculator.php
    $calc_content = str_replace(
        '$active_surveys = $stmt_surveys->fetchAll(PDO::FETCH_ASSOC);', 
        '$active_surveys = $stmt_surveys->fetchAll(PDO::FETCH_ASSOC);' . "\n\n// Advanced Calc\n" . $db_fetches, 
        $calc_content
    );
}

// 2. Extract CSS
preg_match('/<style>(.*?)<\/style>/s', $adv_calc_content, $css_matches);
if (count($css_matches) > 0) {
    $css = "<style>\n" . $css_matches[1] . ".adv-calc-modal { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }\n</style>\n";
    $calc_content = str_replace('?>', "?>\n" . $css, $calc_content);
}

// 3. Extract HTML Body of Advanced Calc (from STEP 1 to RESULT)
preg_match('/(<!-- STEP 1: Pilih Paket -->.*?)<!-- Share Button -->/s', $adv_calc_content, $html_matches);
if (count($html_matches) > 0) {
    $adv_html = $html_matches[1];
    
    // Modify RESULT section in the extracted HTML to match our design
    $adv_html = str_replace(
        'id="resultSection" class="result-card"',
        'id="resultSection" class="result-card mb-6"', // add margin
        $adv_html
    );

    $modal_html = '
<!-- MODAL KALKULATOR GORDEN CUSTOM -->
<div id="advCalcModal" class="adv-calc-modal fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden opacity-0">
    <div class="bg-slate-50 w-full max-w-5xl h-[95vh] rounded-[2rem] shadow-2xl flex flex-col transform scale-95 transition-transform duration-300 mx-4" id="advCalcModalContent">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-slate-200 bg-white rounded-t-[2rem] flex items-center justify-between shrink-0 shadow-sm z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600"><i data-lucide="calculator" class="w-5 h-5"></i></div>
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Kalkulator Gorden Custom</h2>
                    <p class="text-xs text-slate-500">Hitung multi-jendela secara akurat</p>
                </div>
            </div>
            <button type="button" onclick="closeAdvCalc()" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-full transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6 relative custom-scrollbar">
            ' . $adv_html . '
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-slate-200 bg-white rounded-b-[2rem] shrink-0 flex items-center justify-between shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-10">
            <div class="flex flex-col">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Total Harga Gorden</span>
                <span class="text-2xl font-black text-slate-800" id="advCalcTotalBottom">Rp 0</span>
            </div>
            <button type="button" onclick="addAdvCalcToCart()" class="bg-emerald-600 text-white px-8 py-4 rounded-xl font-bold shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 transition-all flex items-center gap-2 active:scale-95 text-sm uppercase tracking-widest">
                <i data-lucide="shopping-cart" class="w-5 h-5"></i> Masukkan ke Keranjang
            </button>
        </div>
    </div>
</div>
';
    // Insert Modal HTML before closing form/main
    $calc_content = str_replace(
        '</form>',
        "</form>\n" . $modal_html,
        $calc_content
    );
}

// 4. Extract Javascript Variables and Logic
preg_match('/\/\/ ==================== DATA FROM PHP ====================.*?\/\/ ==================== INIT ====================/s', $adv_calc_content, $js_matches);
if (count($js_matches) > 0) {
    $js_logic = $js_matches[0];
    
    // We need to slightly adapt JS variables so they don't clash globally, or just wrap them.
    // Actually, calculator.php has variables inside DOMContentLoaded.
    // Let's inject our JS logic at the end of calculator.php scripts, wrapped in a function or just globally.
    
    // Replace totalPrice update to also update advCalcTotalBottom
    $js_logic = str_replace(
        "document.getElementById('totalPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');",
        "document.getElementById('totalPrice').textContent = 'Rp ' + total.toLocaleString('id-ID');\n    document.getElementById('advCalcTotalBottom').textContent = 'Rp ' + total.toLocaleString('id-ID');\n    window.advCalcTotalValue = total;",
        $js_logic
    );

    // Add Modal open/close functions and addToCart function
    $extra_js = '
// ================= ADVANCED MODAL LOGIC =================
function openAdvCalc() {
    if(!document.getElementById(\'item_name\').value) {
        let title = document.getElementById(\'customerSearch\').value ? "Jendela " + document.getElementById(\'customerSearch\').value : "Pesanan Gorden Custom";
        document.getElementById(\'item_name\').value = title;
    }
    const m = document.getElementById(\'advCalcModal\');
    m.classList.remove(\'hidden\');
    setTimeout(() => { m.classList.remove(\'opacity-0\'); document.getElementById(\'advCalcModalContent\').classList.remove(\'scale-95\'); }, 10);
    lucide.createIcons();
}
function closeAdvCalc() {
    const m = document.getElementById(\'advCalcModal\');
    m.classList.add(\'opacity-0\');
    document.getElementById(\'advCalcModalContent\').classList.add(\'scale-95\');
    setTimeout(() => m.classList.add(\'hidden\'), 300);
}

function addAdvCalcToCart() {
    if(!window.advCalcTotalValue || window.advCalcTotalValue === 0) {
        Swal.fire({title: "Kosong", text: "Total harga masih 0. Silakan isi ukuran.", icon: "warning"});
        return;
    }
    
    let itemName = document.getElementById(\'item_name\').value.trim() || "Pesanan Gorden Custom";
    
    // Generate detailed notes from result section
    const pkgNames = {1:\'Gorden Saja\',2:\'Gorden + Rel\',3:\'Vitrase Saja\',4:\'Vitrase + Rel\',5:\'Gorden + Rel + Vitrase + Rel\'};
    let note = `[CUSTOM GORDEN]\nPaket: ${pkgNames[selectedPackage]}\n`;
    
    let windowsInfo = [];
    document.querySelectorAll(\'.window-item\').forEach((el, index) => {
        const w = parseFloat(el.querySelector(\'.window-width\').value) || 0;
        const h = parseFloat(el.querySelector(\'.window-height\').value) || 0;
        windowsInfo.push(`J${index + 1}: ${w}x${h}cm`);
    });
    note += `Ukuran: ${windowsInfo.join(\', \')}\n`;
    
    // Rincian items
    const items = document.getElementById(\'resultDetails\').querySelectorAll(\'.item-detail\');
    items.forEach(item => {
        const label = item.dataset.labeltext || \'\';
        note += `- ${label}\n`;
    });

    const newItem = {
        id: Date.now(),
        name: itemName,
        type: "custom",
        qty: 1,
        unitPrice: window.advCalcTotalValue,
        total: window.advCalcTotalValue,
        note: note.trim()
    };
    
    // This cartItems is globally accessible in calculator.php
    cartItems.push(newItem);
    
    // Reset item name
    document.getElementById(\'item_name\').value = \'\';
    
    // Trigger global renderCart
    renderCart();
    
    closeAdvCalc();
    
    Swal.fire({
        title: "Berhasil",
        text: "Kalkulasi Gorden telah ditambahkan ke Nota.",
        icon: "success",
        timer: 1500,
        showConfirmButton: false
    });
}
';

    // Insert into script block before the end
    $calc_content = str_replace(
        '</script>',
        $js_logic . "\n" . $extra_js . "\nupdateVisibility();\ncalculate();\n</script>",
        $calc_content
    );
}

// 5. Add Button to open modal in calculator.php
$calc_content = str_replace(
    '<h3 class="font-bold text-slate-800" id="calc_title">Kalkulator</h3>',
    '<h3 class="font-bold text-slate-800" id="calc_title">Kalkulator</h3>
    </div>
    <div class="flex items-center gap-3">
        <button type="button" onclick="openAdvCalc()" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 transition-colors border border-emerald-200 shadow-sm"><i data-lucide="calculator" class="w-4 h-4"></i> Hitung Gorden Custom</button>',
    $calc_content
);

file_put_contents($calc_path, $calc_content);
echo "MERGE COMPLETE\n";
