<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkMitra();

$page_title = "POS Sprei";
include '../includes/header.php';
include '../includes/sidebar_mitra.php';

// Mock Products for Sprei
$products = [
    ['id' => 1, 'name' => 'Sprei King (180x200)', 'price' => 250000, 'stock' => 10],
    ['id' => 2, 'name' => 'Sprei Queen (160x200)', 'price' => 230000, 'stock' => 15],
    ['id' => 3, 'name' => 'Sprei Single (120x200)', 'price' => 180000, 'stock' => 8],
    ['id' => 4, 'name' => 'Sprei Super Single (100x200)', 'price' => 160000, 'stock' => 5],
    ['id' => 5, 'name' => 'Sprei Extra King (200x200)', 'price' => 280000, 'stock' => 3],
    ['id' => 6, 'name' => 'Bedcover Set King', 'price' => 650000, 'stock' => 4],
    ['id' => 7, 'name' => 'Sprei Katun Jepang 180', 'price' => 450000, 'stock' => 2],
    ['id' => 8, 'name' => 'Sprei Microfiber 160', 'price' => 195000, 'stock' => 20],
];

// Fetch Mitra Data
$stmt = $pdo->prepare("SELECT full_name FROM partners WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$partner = $stmt->fetch();
$mitra_name = $partner ? $partner['full_name'] : $_SESSION['email'];
?>

<style>
    /* Reset & Base Styles to match screenshot */
    .pos-container { height: calc(100vh - 64px); }
    @media (max-width: 768px) { .pos-container { height: auto; } }
    
    .card-product {
        border-left: 3px solid #f59e0b !important;
        border-radius: 10px !important;
        transition: all 0.2s;
        padding: 1rem !important; /* Smaller padding */
    }
    .card-product:hover {
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        transform: translateY(-1px);
    }
    
    .nota-sidebar {
        width: 500px; /* Expanded to 500px */
        min-width: 500px;
        background: #fff;
        display: flex;
        flex-direction: column;
        border-left: 1px solid #e2e8f0;
    }
    
    .nota-header {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        color: #2563eb;
    }
    
    .nota-footer {
        background: #f8fafc;
        padding: 1.25rem;
        border-top: 1px solid #e2e8f0;
    }
    
    .btn-draft { background-color: #10b981; }
    .btn-draft:hover { background-color: #059669; }
    .btn-pay { background-color: #2563eb; }
    .btn-pay:hover { background-color: #1d4ed8; }

    .empty-state-icon { opacity: 0.2; }
    
    /* Scrollbar styling */
    .custom-scroll::-webkit-scrollbar { width: 4px; }
    .custom-scroll::-webkit-scrollbar-track { background: transparent; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
</style>

<div class="flex-1 flex flex-col min-h-screen bg-white overflow-hidden">
    <!-- Main Content Area -->
    <div class="flex flex-col md:flex-row flex-1 overflow-hidden pos-container">
        
        <!-- LEFT: CATALOG -->
        <div class="flex-1 flex flex-col bg-[#f8fafc] overflow-hidden">
            <!-- Customer Data Area (Clean & Robust) -->
            <div class="p-4 bg-white border-b border-slate-100 space-y-3 shadow-sm">
                <div class="flex items-center gap-2 mb-1">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white">
                        <i data-lucide="user-plus" class="h-4 w-4"></i>
                    </div>
                    <span class="text-xs font-black text-slate-800 uppercase tracking-widest">Data Pelanggan</span>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <!-- Name Input Wrapper -->
                    <div class="flex items-center gap-3 px-4 h-11 bg-[#f8fafc] border border-slate-200 rounded-xl focus-within:bg-white focus-within:ring-1 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all group">
                        <i data-lucide="user" class="h-4 w-4 text-blue-500/50 group-focus-within:text-blue-500 transition-colors"></i>
                        <input type="text" id="custName" placeholder="Nama Lengkap Pelanggan" class="flex-1 bg-transparent border-none p-0 text-xs font-bold focus:ring-0 outline-none placeholder:text-slate-400" />
                    </div>
                    <!-- Phone Input Wrapper -->
                    <div class="flex items-center gap-3 px-4 h-11 bg-[#f8fafc] border border-slate-200 rounded-xl focus-within:bg-white focus-within:ring-1 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all group">
                        <i data-lucide="phone" class="h-4 w-4 text-blue-500/50 group-focus-within:text-blue-500 transition-colors"></i>
                        <input type="tel" id="custPhone" placeholder="Nomor WhatsApp" class="flex-1 bg-transparent border-none p-0 text-xs font-bold focus:ring-0 outline-none placeholder:text-slate-400" />
                    </div>
                </div>
                <!-- Address Textarea Wrapper (Full width, but Slimmer) -->
                <div class="flex items-center gap-3 px-4 h-11 bg-[#f8fafc] border border-slate-200 rounded-xl focus-within:bg-white focus-within:ring-1 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all group">
                    <i data-lucide="map-pin" class="h-4 w-4 text-blue-500/50 group-focus-within:text-blue-500 transition-colors"></i>
                    <input type="text" id="custAddress" placeholder="Alamat Lengkap Pengiriman" class="flex-1 bg-transparent border-none p-0 text-xs font-bold focus:ring-0 outline-none placeholder:text-slate-400" />
                </div>
            </div>

            <!-- Grid Container (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-6 custom-scroll">
                <div id="productGrid" class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4">
                    <?php foreach($products as $p): ?>
                    <div class="card-product bg-white border border-slate-200 shadow-sm cursor-pointer flex flex-col gap-1.5" onclick="addToCart(<?php echo htmlspecialchars(json_encode($p)); ?>)">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1 text-[#f59e0b] font-bold text-[9px]">
                                <i data-lucide="package" class="h-2.5 w-2.5"></i>
                                <span>ITEM</span>
                            </div>
                            <span class="text-[9px] text-slate-400">Stok: <?php echo $p['stock']; ?></span>
                        </div>
                        <h3 class="font-bold text-slate-800 text-xs leading-tight h-8 overflow-hidden"><?php echo $p['name']; ?></h3>
                        <p class="text-slate-900 font-black text-base mt-auto">Rp <?php echo number_format($p['price'], 0, ',', '.'); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT: SIDEBAR NOTA -->
        <div class="nota-sidebar h-full">
            <!-- Header -->
            <div class="nota-header">
                <div class="flex items-center gap-2">
                    <i data-lucide="shopping-cart" class="h-5 w-5"></i>
                    <h2 class="font-black text-sm tracking-tight">RINCIAN NOTA</h2>
                </div>
                <div class="bg-[#2563eb] text-white px-2 py-0.5 rounded-full font-bold text-[10px]" id="itemCount">0 Item</div>
            </div>

            <!-- Cart Items Scroll -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="cartItems">
                <!-- Empty State -->
                <div id="emptyCart" class="h-full flex flex-col items-center justify-center text-center space-y-3 mt-20">
                    <i data-lucide="shopping-bag" class="h-20 w-20 text-slate-200"></i>
                    <p class="text-slate-300 font-bold text-xs uppercase tracking-widest">BELUM ADA ITEM</p>
                </div>
            </div>

            <div class="nota-footer space-y-4">
                <div class="space-y-2">
                    <div class="flex justify-between text-sm text-slate-500 font-medium">
                        <span>Subtotal</span>
                        <span class="font-bold text-slate-800" id="subtotal">Rp 0</span>
                    </div>
                    <div class="h-px bg-slate-200 my-2"></div>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-slate-900 text-lg uppercase">Total</span>
                        <span class="font-black text-[#2563eb] text-3xl" id="total">Rp 0</span>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <button onclick="processPayment()" class="h-14 btn-pay text-white rounded-xl font-black text-sm uppercase tracking-widest flex items-center justify-center gap-3 shadow-lg shadow-blue-600/20 active:scale-[0.98] transition-transform">
                        <i data-lucide="credit-card" class="h-5 w-5"></i> BAYAR & CETAK SEKARANG
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm hidden">
    <div class="bg-white p-10 rounded-[2.5rem] shadow-2xl text-center max-w-md w-[90%] md:w-full animate-in zoom-in duration-300">
        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i data-lucide="check-circle" class="h-12 w-12 text-emerald-600"></i>
        </div>
        <h2 class="text-2xl font-black text-slate-900 mb-1 tracking-tight">Sukses!</h2>
        <p class="text-slate-400 font-bold text-sm mb-6 uppercase tracking-widest">Transaksi Telah Berhasil</p>
        
        <div id="invoiceSummary" class="bg-slate-50 rounded-3xl p-6 mb-6 border border-slate-100 text-left space-y-3">
            <!-- Dynamic Content -->
        </div>

        <div class="space-y-3">
            <button id="waButton" class="w-full h-14 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black text-sm uppercase tracking-widest shadow-xl shadow-emerald-600/20 flex items-center justify-center gap-3">
                <i data-lucide="message-circle" class="h-6 w-6"></i>
                KIRIM NOTA WA
            </button>
            <button onclick="location.reload()" class="w-full h-12 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl font-bold transition-colors">
                KEMBALI
            </button>
        </div>
    </div>
</div>

<script>
let cart = [];

function addToCart(product) {
    const existing = cart.find(item => item.id === product.id);
    if(existing) {
        existing.qty++;
    } else {
        cart.push({...product, qty: 1});
    }
    renderCart();
}

function updateQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if(item) {
        item.qty += delta;
        if(item.qty <= 0) {
            cart = cart.filter(i => i.id !== id);
        }
        renderCart();
    }
}

function renderCart() {
    const cartItems = document.getElementById('cartItems');
    const emptyCart = document.getElementById('emptyCart');
    const itemCount = document.getElementById('itemCount');
    
    itemCount.innerText = cart.length + ' Item';
    
    if(cart.length === 0) {
        cartItems.innerHTML = '';
        cartItems.appendChild(emptyCart);
        updateTotals();
        return;
    }
    
    if(emptyCart) emptyCart.remove();
    cartItems.innerHTML = cart.map(item => `
        <div class="flex items-center gap-3 bg-white p-3 rounded-xl border border-slate-100 shadow-sm animate-in slide-in-from-right duration-200">
            <div class="flex-1 min-w-0">
                <h4 class="text-xs font-bold text-slate-800 truncate uppercase">${item.name}</h4>
                <p class="text-[10px] text-slate-400 font-bold mt-1">Rp ${item.price.toLocaleString('id-ID')} x ${item.qty}</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="updateQty(${item.id}, -1)" class="w-6 h-6 flex items-center justify-center rounded bg-slate-100 text-slate-500 hover:bg-slate-200">-</button>
                <span class="text-xs font-bold text-slate-700 w-4 text-center">${item.qty}</span>
                <button onclick="updateQty(${item.id}, 1)" class="w-6 h-6 flex items-center justify-center rounded bg-slate-100 text-slate-500 hover:bg-slate-200">+</button>
            </div>
            <div class="text-right min-w-[80px]">
                <p class="text-xs font-black text-slate-900">Rp ${(item.price * item.qty).toLocaleString('id-ID')}</p>
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
    updateTotals();
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    const total = subtotal;
    
    document.getElementById('subtotal').innerText = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('total').innerText = 'Rp ' + total.toLocaleString('id-ID');
}

function filterProducts() {
    // Search feature removed per user request to prioritize customer data input
    return;
}

function processPayment() {
    if(cart.length === 0) return Swal.fire('Error', 'Keranjang masih kosong', 'error');
    const name = document.getElementById('custName').value;
    const phone = document.getElementById('custPhone').value;
    const address = document.getElementById('custAddress').value;
    
    if(!name || !phone || !address) return Swal.fire('Info', 'Mohon lengkapi Nama, WA, & Alamat Pelanggan', 'warning');

    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);

    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="h-4 w-4 animate-spin"></i> PROSES...';
    lucide.createIcons();

    const formData = new FormData();
    formData.append('name', name);
    formData.append('phone', phone);
    formData.append('address', address);
    formData.append('ukuran', cart.map(i => `${i.name} (x${i.qty})`).join(', '));
    formData.append('price', total);
    formData.append('notes', `Items: ${cart.length}`);

    fetch('../ajax/checkout_sprei.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            showSuccess(data.invoice_number, name, total);
        } else {
            Swal.fire('Gagal', data.message, 'error');
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="credit-card" class="h-4 w-4"></i> BAYAR & CETAK';
            lucide.createIcons();
        }
    });
}

function showSuccess(inv, name, total) {
    const summary = document.getElementById('invoiceSummary');
    summary.innerHTML = `
        <div class="flex justify-between text-[10px] text-slate-400 font-black uppercase tracking-widest">
            <span>ID TRANSAKSI</span>
            <span>${inv}</span>
        </div>
        <div class="h-px bg-slate-200 my-4 border-dashed border-t border-slate-300"></div>
        <div class="space-y-2">
            <div class="flex justify-between text-xs font-bold uppercase"><span class="text-slate-400">PELANGGAN</span><span class="text-slate-800">${name}</span></div>
            <div class="flex justify-between text-xs font-bold uppercase"><span class="text-slate-400">TOTAL ITEM</span><span class="text-slate-800">${cart.length} Produk</span></div>
        </div>
        <div class="h-px bg-slate-200 my-4"></div>
        <div class="flex justify-between items-center">
            <span class="font-black text-slate-900 text-sm uppercase tracking-widest">TOTAL LUNAS</span>
            <span class="font-black text-2xl text-blue-600">Rp ${total.toLocaleString('id-ID')}</span>
        </div>
    `;

    const message = `Halo Admin Sewlovely, saya Mitra *<?php echo $mitra_name; ?>* lapor pesanan Sprei Baru!\n\n` +
                    `*ID Transaksi:* ${inv}\n` +
                    `*Nama Pelanggan:* ${name}\n` +
                    `*No. WA:* ${document.getElementById('custPhone').value}\n` +
                    `*Alamat:* ${document.getElementById('custAddress').value}\n\n` +
                    `*Daftar Pesanan:*\n` +
                    cart.map(i => `✅ ${i.name} (x${i.qty})`).join('\n') + `\n\n` +
                    `*Total Transaksi:* Rp ${total.toLocaleString('id-ID')}\n\n` +
                    `_Mohon dicek, bukti transfer saya lampirkan di bawah ini._`;
    
    document.getElementById('waButton').onclick = () => window.open(`https://wa.me/6285159588681?text=${encodeURIComponent(message)}`, '_blank');
    document.getElementById('successModal').classList.remove('hidden');
    lucide.createIcons();
}

function saveDraft() {
    Swal.fire({
        title: 'Draft Disimpan',
        text: 'Pesanan ini telah disimpan sebagai draf',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
}
</script>

<?php include '../includes/footer.php'; ?>
