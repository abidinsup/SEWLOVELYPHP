<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Manajemen Produk";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

$categories = [
    'Gorden Rumah',
    'Gorden Kantor',
    'Gorden RS/Klinik',
    'Sprei Rumah',
    'Sprei RS/Klinik',
    'Custom'
];

$products = [];
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
    $raw_products = $stmt->fetchAll();
    
    foreach ($categories as $cat) {
        $products[$cat] = [];
    }
    
    foreach ($raw_products as $p) {
        if (isset($products[$p['category']])) {
            $products[$p['category']][] = $p;
        } else {
            $products[$p['category']] = [$p];
        }
    }
} catch (PDOException $e) {
    // Handle error quietly
}

?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/90 md:mt-0 mt-16 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Katalog Produk & Harga Dasar</h1>
            <button onclick="openAddModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2">
                <i data-lucide="plus" class="h-4 w-4"></i> Tambah Produk
            </button>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto pb-24 md:pb-8">
        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Category Tabs -->
            <div class="bg-white p-2 rounded-2xl border border-slate-100 shadow-sm flex overflow-x-auto scrollbar-hide gap-1 sticky top-20 z-20">
                <?php foreach($categories as $index => $cat): ?>
                    <button onclick="switchTab('<?php echo md5($cat); ?>')" class="tab-btn <?php echo $index === 0 ? 'bg-slate-900 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'; ?> px-5 py-2.5 rounded-xl text-sm font-bold transition-all whitespace-nowrap shrink-0 flex-1" data-target="tab-<?php echo md5($cat); ?>">
                        <?php echo $cat; ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Tab Contents -->
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden min-h-[500px]">
                <?php foreach($categories as $index => $cat): ?>
                    <div id="tab-<?php echo md5($cat); ?>" class="tab-content <?php echo $index === 0 ? 'block' : 'hidden'; ?> animate-in fade-in duration-300">
                        
                        <div class="p-6 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900"><?php echo $cat; ?></h2>
                                <p class="text-sm text-slate-500">Daftar harga acuan dasar untuk POS</p>
                            </div>
                            <?php if($cat !== 'Custom'): ?>
                            <div class="relative w-full sm:w-64">
                                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"></i>
                                <input type="text" onkeyup="filterTable(this, 'table-<?php echo md5($cat); ?>')" placeholder="Cari nama produk..." class="w-full h-10 pl-9 pr-4 bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-cyan-400 focus:ring-1 focus:ring-cyan-400 text-sm" />
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if($cat === 'Custom'): ?>
                            <div class="p-12 text-center flex flex-col items-center justify-center">
                                <div class="w-24 h-24 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center mb-6 shadow-sm">
                                    <i data-lucide="scissors" class="h-10 w-10"></i>
                                </div>
                                <h3 class="text-xl font-bold text-slate-900 mb-2">Produk Custom</h3>
                                <p class="text-slate-500 max-w-md">
                                    Untuk produk dengan kategori Custom, harga dan ukuran akan diinput secara manual langsung pada saat pembuatan Nota (POS). Anda tetap dapat menambahkan acuan dasar jika diperlukan.
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left" id="table-<?php echo md5($cat); ?>">
                                <thead class="bg-slate-50/80 text-slate-500 text-[10px] uppercase font-black tracking-wider border-b border-slate-100">
                                    <tr>
                                        <th class="p-4 w-16 text-center">No</th>
                                        <th class="p-4">Nama Produk / Bahan</th>
                                        <th class="p-4">Harga Dasar</th>
                                        <th class="p-4">Satuan</th>
                                        <th class="p-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-sm">
                                    <?php if(empty($products[$cat]) && $cat !== 'Custom'): ?>
                                        <tr>
                                            <td colspan="5" class="p-10 text-center text-slate-400">
                                                <i data-lucide="package-open" class="h-10 w-10 mx-auto mb-3 opacity-50"></i>
                                                <p>Belum ada produk di kategori ini.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php $no=1; foreach($products[$cat] as $p): ?>
                                    <tr class="hover:bg-slate-50/50 transition-colors product-row">
                                        <td class="p-4 text-center text-slate-400 font-medium"><?php echo $no++; ?></td>
                                        <td class="p-4 font-bold text-slate-900 product-name"><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td class="p-4 font-bold text-emerald-600">Rp <?php echo number_format($p['base_price'], 0, ',', '.'); ?></td>
                                        <td class="p-4">
                                            <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md text-xs font-bold"><?php echo htmlspecialchars($p['unit']); ?></span>
                                        </td>
                                        <td class="p-4 text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($p)); ?>)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Edit">
                                                    <i data-lucide="edit-3" class="h-4 w-4"></i>
                                                </button>
                                                <button onclick="deleteProduct(<?php echo $p['id']; ?>, '<?php echo addslashes($p['name']); ?>')" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Hapus">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </main>
</div>

<!-- Modal Form Product -->
<div id="productModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 w-full max-w-lg mx-4 transform scale-95 transition-transform duration-300" id="productModalContent">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2" id="modalTitle">
                <i data-lucide="package-plus" class="h-5 w-5 text-emerald-600"></i> Tambah Produk
            </h2>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 p-2 hover:bg-slate-50 rounded-xl transition-all">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        
        <form id="productForm" onsubmit="saveProduct(event)">
            <input type="hidden" id="product_id" value="">
            <input type="hidden" id="form_action" value="add">
            
            <div class="p-6 space-y-5">
                <div class="space-y-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Kategori</label>
                    <select id="category" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl transition-all text-sm font-medium text-slate-900">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="space-y-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Nama Produk / Bahan</label>
                    <input type="text" id="name" required placeholder="Contoh: Gorden Blackout Polos" class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl transition-all text-sm font-medium text-slate-900" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Harga Dasar</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-slate-400">Rp</span>
                            <input type="text" id="base_price" required placeholder="0" oninput="formatRupiah(this)" class="w-full h-12 pl-10 pr-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl transition-all font-bold text-slate-900" />
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Satuan</label>
                        <select id="unit" required class="w-full h-12 px-4 bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 rounded-xl transition-all text-sm font-medium text-slate-900">
                            <option value="Meter">Meter</option>
                            <option value="Pcs">Pcs</option>
                            <option value="Set">Set</option>
                            <option value="Yard">Yard</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="p-6 border-t border-slate-100 flex justify-end gap-3 bg-slate-50/50 rounded-b-[2rem]">
                <button type="button" onclick="closeModal()" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition-colors">Batal</button>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2">
                    <i data-lucide="save" class="h-4 w-4"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(targetId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden', 'block'));
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('block'));
    document.getElementById('tab-' + targetId).classList.remove('hidden');
    document.getElementById('tab-' + targetId).classList.add('block');

    document.querySelectorAll('.tab-btn').forEach(btn => {
        if(btn.dataset.target === 'tab-' + targetId) {
            btn.className = "tab-btn bg-slate-900 text-white shadow-md px-5 py-2.5 rounded-xl text-sm font-bold transition-all whitespace-nowrap shrink-0 flex-1";
        } else {
            btn.className = "tab-btn text-slate-500 hover:bg-slate-50 hover:text-slate-900 px-5 py-2.5 rounded-xl text-sm font-bold transition-all whitespace-nowrap shrink-0 flex-1";
        }
    });
}

function filterTable(input, tableId) {
    const filter = input.value.toLowerCase();
    const rows = document.getElementById(tableId).querySelectorAll('.product-row');
    
    rows.forEach(row => {
        const name = row.querySelector('.product-name').innerText.toLowerCase();
        if (name.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function formatRupiah(input) {
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

const modal = document.getElementById('productModal');
const modalContent = document.getElementById('productModalContent');

function openAddModal() {
    document.getElementById('modalTitle').innerHTML = '<i data-lucide="package-plus" class="h-5 w-5 text-emerald-600"></i> Tambah Produk';
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('form_action').value = 'add';
    
    // Get current active tab category
    const activeTabBtn = document.querySelector('.tab-btn.bg-slate-900');
    if(activeTabBtn) {
        document.getElementById('category').value = activeTabBtn.innerText.trim();
    }

    lucide.createIcons();
    showModal();
}

function openEditModal(productData) {
    document.getElementById('modalTitle').innerHTML = '<i data-lucide="edit-3" class="h-5 w-5 text-blue-600"></i> Edit Produk';
    document.getElementById('form_action').value = 'edit';
    
    document.getElementById('product_id').value = productData.id;
    document.getElementById('category').value = productData.category;
    document.getElementById('name').value = productData.name;
    document.getElementById('unit').value = productData.unit;
    
    const priceInput = document.getElementById('base_price');
    priceInput.value = productData.base_price;
    formatRupiah(priceInput);

    lucide.createIcons();
    showModal();
}

function showModal() {
    modal.classList.remove('hidden');
    // slight delay for animation
    setTimeout(() => {
        modal.classList.remove('opacity-0');
        modalContent.classList.remove('scale-95');
    }, 10);
}

function closeModal() {
    modal.classList.add('opacity-0');
    modalContent.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function saveProduct(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('action', document.getElementById('form_action').value);
    formData.append('id', document.getElementById('product_id').value);
    formData.append('category', document.getElementById('category').value);
    formData.append('name', document.getElementById('name').value);
    formData.append('base_price', document.getElementById('base_price').value);
    formData.append('unit', document.getElementById('unit').value);

    fetch('../ajax/manage_product.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Berhasil!',
                text: data.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Gagal', data.message, 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Terjadi kesalahan koneksi', 'error'));
}

function deleteProduct(id, name) {
    Swal.fire({
        title: 'Hapus Produk?',
        html: `Anda yakin ingin menghapus produk <strong>${name}</strong>? Data ini akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            fetch('../ajax/manage_product.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Dihapus!',
                        text: data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Terjadi kesalahan koneksi', 'error'));
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>
