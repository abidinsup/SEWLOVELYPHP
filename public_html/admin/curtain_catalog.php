<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Katalog Gorden";
include '../includes/header.php';
include '../includes/sidebar_admin.php';

// Fetch data
$fabrics = $pdo->query("SELECT * FROM curtain_fabrics ORDER BY type, name")->fetchAll();
$rails = $pdo->query("SELECT * FROM curtain_rails ORDER BY type, name")->fetchAll();
?>

<div class="flex-1 flex flex-col min-h-screen w-full transition-all duration-300 ease-in-out bg-slate-50">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm backdrop-blur-md bg-white/90 md:mt-0 mt-16 print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">Katalog Kain & Rel Gorden</h1>
                <p class="text-xs text-slate-500 hidden md:block">Kelola harga kain dan rel untuk kalkulator gorden</p>
            </div>
            <div class="flex gap-2">
                <a href="curtain_calculator.php" class="bg-cyan-500 hover:bg-cyan-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-cyan-500/20 transition-all flex items-center gap-2">
                    <i data-lucide="calculator" class="h-4 w-4"></i> <span class="hidden sm:inline">Kalkulator</span>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 p-4 md:p-8 overflow-y-auto pb-24 md:pb-8">
        <div class="max-w-7xl mx-auto space-y-6">

            <!-- Tab Switch -->
            <div class="relative w-full sm:w-auto inline-flex mb-2">
                <style>
                    .no-scrollbar::-webkit-scrollbar { display: none; }
                    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
                </style>
                <div class="bg-slate-200/60 p-1.5 rounded-[1.25rem] inline-flex gap-1 overflow-x-auto max-w-full no-scrollbar shadow-inner">
                    <button onclick="switchMainTab('fabrics', this)" class="main-tab shrink-0 flex items-center justify-center gap-2.5 bg-white text-emerald-700 shadow-sm px-6 py-2.5 rounded-xl text-sm font-bold transition-all whitespace-nowrap border border-slate-200/50">
                        <i data-lucide="scissors" class="h-4 w-4"></i> Katalog Kain
                    </button>
                    <button onclick="switchMainTab('rails', this)" class="main-tab shrink-0 flex items-center justify-center gap-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-200/50 px-6 py-2.5 rounded-xl text-sm font-bold transition-all whitespace-nowrap border border-transparent">
                        <i data-lucide="grip-horizontal" class="h-4 w-4"></i> Katalog Rel
                    </button>
                </div>
            </div>

            <!-- ==================== FABRICS TAB ==================== -->
            <div id="tab-fabrics" class="space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">Total: <strong><?php echo count($fabrics); ?></strong> kain terdaftar</p>
                    <button onclick="openFabricModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2">
                        <i data-lucide="plus" class="h-4 w-4"></i> Tambah Kain
                    </button>
                </div>

                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50/80 text-slate-500 text-[10px] uppercase font-black tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="p-4 w-12">No</th>
                                    <th class="p-4">Nama Kain</th>
                                    <th class="p-4">Jenis</th>
                                    <th class="p-4">Harga/Meter</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-sm">
                                <?php if(empty($fabrics)): ?>
                                <tr><td colspan="6" class="p-10 text-center text-slate-400">Belum ada data kain.</td></tr>
                                <?php endif; ?>
                                <?php $no=1; foreach($fabrics as $f): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 text-center text-slate-400"><?php echo $no++; ?></td>
                                    <td class="p-4">
                                        <p class="font-bold text-slate-900"><?php echo htmlspecialchars($f['name']); ?></p>
                                        <?php if($f['description']): ?>
                                        <p class="text-xs text-slate-400 mt-0.5 truncate max-w-[200px]"><?php echo htmlspecialchars($f['description']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold <?php echo $f['type'] == 'gorden' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700'; ?>">
                                            <?php echo ucfirst($f['type']); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-emerald-600">Rp <?php echo number_format($f['price_per_meter'], 0, ',', '.'); ?></td>
                                    <td class="p-4">
                                        <button onclick="toggleFabric(<?php echo $f['id']; ?>)" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase cursor-pointer hover:opacity-80 transition <?php echo $f['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?>">
                                            <?php echo $f['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </button>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button onclick='openFabricModal(<?php echo json_encode($f); ?>)' class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"><i data-lucide="edit-3" class="h-4 w-4"></i></button>
                                            <button onclick="deleteFabric(<?php echo $f['id']; ?>, '<?php echo addslashes($f['name']); ?>')" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ==================== RAILS TAB ==================== -->
            <div id="tab-rails" class="space-y-4 hidden">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500">Total: <strong><?php echo count($rails); ?></strong> rel terdaftar</p>
                    <button onclick="openRailModal()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg shadow-emerald-600/20 transition-all flex items-center gap-2">
                        <i data-lucide="plus" class="h-4 w-4"></i> Tambah Rel
                    </button>
                </div>

                <div class="bg-white rounded-[2rem] border border-slate-100 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50/80 text-slate-500 text-[10px] uppercase font-black tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="p-4 w-12">No</th>
                                    <th class="p-4">Nama Rel</th>
                                    <th class="p-4">Tipe</th>
                                    <th class="p-4">Harga/Meter</th>
                                    <th class="p-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 text-sm">
                                <?php if(empty($rails)): ?>
                                <tr><td colspan="5" class="p-10 text-center text-slate-400">Belum ada data rel.</td></tr>
                                <?php endif; ?>
                                <?php $no=1; foreach($rails as $r): ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="p-4 text-center text-slate-400"><?php echo $no++; ?></td>
                                    <td class="p-4 font-bold text-slate-900"><?php echo htmlspecialchars($r['name']); ?></td>
                                    <td class="p-4">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold <?php echo $r['type'] == 'single' ? 'bg-slate-100 text-slate-700' : 'bg-amber-50 text-amber-700'; ?>">
                                            <?php echo $r['type'] == 'single' ? 'Single' : 'Double (Twin)'; ?>
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-emerald-600">Rp <?php echo number_format($r['price_per_meter'], 0, ',', '.'); ?></td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <button onclick='openRailModal(<?php echo json_encode($r); ?>)' class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"><i data-lucide="edit-3" class="h-4 w-4"></i></button>
                                            <button onclick="deleteRail(<?php echo $r['id']; ?>, '<?php echo addslashes($r['name']); ?>')" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- ==================== MODAL FABRIC ==================== -->
<div id="fabricModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 w-full max-w-lg mx-4 transform scale-95 transition-transform duration-300" id="fabricModalContent">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900" id="fabricModalTitle">Tambah Kain</h2>
            <button onclick="closeModal('fabricModal')" class="text-slate-400 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 p-2 rounded-full transition-colors"><i data-lucide="x" class="h-5 w-5"></i></button>
        </div>
        <form id="fabricForm" onsubmit="saveFabric(event)">
            <input type="hidden" id="fabric_id" value="">
            <input type="hidden" id="fabric_action" value="add_fabric">
            <div class="p-6 space-y-5">
                <div class="space-y-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Nama Kain</label>
                    <input type="text" id="fabric_name" required placeholder="Contoh: Blackout Premium Polos" class="w-full h-12 px-4 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl text-sm font-semibold transition-all" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Jenis</label>
                        <select id="fabric_type" required class="w-full h-12 px-4 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl text-sm font-semibold transition-all cursor-pointer">
                            <option value="gorden">Gorden</option>
                            <option value="vitrase">Vitrase</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Harga / Meter</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">Rp</span>
                            <input type="text" id="fabric_price" required placeholder="0" oninput="formatRupiah(this)" class="w-full h-12 pl-10 pr-4 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl font-bold transition-all" />
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Deskripsi <span class="text-slate-300">(opsional)</span></label>
                    <input type="text" id="fabric_desc" placeholder="Keterangan singkat tentang kain" class="w-full h-12 px-4 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl text-sm transition-all" />
                </div>
            </div>
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3 bg-slate-50/30 rounded-b-[2rem]">
                <button type="button" onclick="closeModal('fabricModal')" class="inline-flex items-center justify-center px-6 py-2.5 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all shadow-sm">Batal</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-600/20 transition-all">
                    <i data-lucide="save" class="h-4 w-4"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL RAIL ==================== -->
<div id="railModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm hidden opacity-0 transition-opacity duration-300">
    <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 w-full max-w-lg mx-4 transform scale-95 transition-transform duration-300" id="railModalContent">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900" id="railModalTitle">Tambah Rel</h2>
            <button onclick="closeModal('railModal')" class="text-slate-400 hover:text-slate-700 bg-slate-50 hover:bg-slate-100 p-2 rounded-full transition-colors"><i data-lucide="x" class="h-5 w-5"></i></button>
        </div>
        <form id="railForm" onsubmit="saveRail(event)">
            <input type="hidden" id="rail_id" value="">
            <input type="hidden" id="rail_action" value="add_rail">
            <div class="p-6 space-y-5">
                <div class="space-y-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Nama Rel</label>
                    <input type="text" id="rail_name" required placeholder="Contoh: Rel Aluminium Double" class="w-full h-12 px-4 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl text-sm font-semibold transition-all" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Tipe</label>
                        <select id="rail_type" required class="w-full h-12 px-4 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl text-sm font-semibold transition-all cursor-pointer">
                            <option value="single">Single (1 jalur)</option>
                            <option value="double">Double / Twin (2 jalur)</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider pl-1">Harga / Meter</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400 text-sm">Rp</span>
                            <input type="text" id="rail_price" required placeholder="0" oninput="formatRupiah(this)" class="w-full h-12 pl-10 pr-4 bg-slate-50/50 border border-slate-200 focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 rounded-xl font-bold transition-all" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-slate-100 flex justify-end gap-3 bg-slate-50/30 rounded-b-[2rem]">
                <button type="button" onclick="closeModal('railModal')" class="inline-flex items-center justify-center px-6 py-2.5 bg-white border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50 hover:text-slate-900 transition-all shadow-sm">Batal</button>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-600/20 transition-all">
                    <i data-lucide="save" class="h-4 w-4"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// ==================== TAB SWITCHING ====================
function switchMainTab(tab, btn) {
    document.getElementById('tab-fabrics').classList.toggle('hidden', tab !== 'fabrics');
    document.getElementById('tab-rails').classList.toggle('hidden', tab !== 'rails');
    document.querySelectorAll('.main-tab').forEach(b => {
        b.className = 'main-tab shrink-0 flex items-center justify-center gap-2.5 text-slate-500 hover:text-slate-700 hover:bg-slate-200/50 px-6 py-2.5 rounded-xl text-sm font-bold transition-all whitespace-nowrap border border-transparent';
    });
    btn.className = 'main-tab shrink-0 flex items-center justify-center gap-2.5 bg-white text-emerald-700 shadow-sm px-6 py-2.5 rounded-xl text-sm font-bold transition-all whitespace-nowrap border border-slate-200/50';
    localStorage.setItem('activeCurtainTab', tab);
}

document.addEventListener('DOMContentLoaded', () => {
    const activeTab = localStorage.getItem('activeCurtainTab');
    if (activeTab === 'rails') {
        const btn = document.querySelector(`button[onclick*="switchMainTab('rails'"]`);
        if (btn) switchMainTab('rails', btn);
    }
});

// ==================== MODAL HELPERS ====================
function showModal(id) {
    const m = document.getElementById(id);
    m.classList.remove('hidden');
    setTimeout(() => { m.classList.remove('opacity-0'); m.querySelector('[id$=Content]').classList.remove('scale-95'); }, 10);
    lucide.createIcons();
}
function closeModal(id) {
    const m = document.getElementById(id);
    m.classList.add('opacity-0');
    m.querySelector('[id$=Content]').classList.add('scale-95');
    setTimeout(() => m.classList.add('hidden'), 300);
}
function formatRupiah(input) {
    let v = input.value.replace(/[^\d]/g, '');
    input.value = v.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// ==================== FABRIC CRUD ====================
function openFabricModal(data) {
    document.getElementById('fabricForm').reset();
    if (data) {
        document.getElementById('fabricModalTitle').textContent = 'Edit Kain';
        document.getElementById('fabric_action').value = 'edit_fabric';
        document.getElementById('fabric_id').value = data.id;
        document.getElementById('fabric_name').value = data.name;
        document.getElementById('fabric_type').value = data.type;
        document.getElementById('fabric_desc').value = data.description || '';
        const p = document.getElementById('fabric_price'); p.value = Math.round(parseFloat(data.price_per_meter)); formatRupiah(p);
    } else {
        document.getElementById('fabricModalTitle').textContent = 'Tambah Kain';
        document.getElementById('fabric_action').value = 'add_fabric';
        document.getElementById('fabric_id').value = '';
    }
    showModal('fabricModal');
}
function saveFabric(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', document.getElementById('fabric_action').value);
    fd.append('id', document.getElementById('fabric_id').value);
    fd.append('name', document.getElementById('fabric_name').value);
    fd.append('type', document.getElementById('fabric_type').value);
    fd.append('price_per_meter', document.getElementById('fabric_price').value);
    fd.append('description', document.getElementById('fabric_desc').value);
    fetch('../ajax/manage_curtain.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => {
            if (d.success) { Swal.fire({title:'Berhasil!',text:d.message,icon:'success',timer:1500,showConfirmButton:false}).then(()=>location.reload()); }
            else Swal.fire('Gagal', d.message, 'error');
        }).catch(() => Swal.fire('Error', 'Koneksi gagal', 'error'));
}
function deleteFabric(id, name) {
    Swal.fire({ title:'Hapus Kain?', html:`Hapus <strong>${name}</strong> secara permanen?`, icon:'warning', showCancelButton:true, confirmButtonColor:'#dc2626', cancelButtonColor:'#64748b', confirmButtonText:'Ya, Hapus', cancelButtonText:'Batal', reverseButtons:true
    }).then(r => { if(r.isConfirmed) {
        const fd = new FormData(); fd.append('action','delete_fabric'); fd.append('id',id);
        fetch('../ajax/manage_curtain.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
            if(d.success) Swal.fire({title:'Dihapus!',text:d.message,icon:'success',timer:1500,showConfirmButton:false}).then(()=>location.reload());
            else Swal.fire('Gagal',d.message,'error');
        });
    }});
}
function toggleFabric(id) {
    const fd = new FormData(); fd.append('action','toggle_fabric'); fd.append('id',id);
    fetch('../ajax/manage_curtain.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success) location.reload(); else Swal.fire('Gagal',d.message,'error');
    });
}

// ==================== RAIL CRUD ====================
function openRailModal(data) {
    document.getElementById('railForm').reset();
    if (data) {
        document.getElementById('railModalTitle').textContent = 'Edit Rel';
        document.getElementById('rail_action').value = 'edit_rail';
        document.getElementById('rail_id').value = data.id;
        document.getElementById('rail_name').value = data.name;
        document.getElementById('rail_type').value = data.type;
        const p = document.getElementById('rail_price'); p.value = Math.round(parseFloat(data.price_per_meter)); formatRupiah(p);
    } else {
        document.getElementById('railModalTitle').textContent = 'Tambah Rel';
        document.getElementById('rail_action').value = 'add_rail';
        document.getElementById('rail_id').value = '';
    }
    showModal('railModal');
}
function saveRail(e) {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action', document.getElementById('rail_action').value);
    fd.append('id', document.getElementById('rail_id').value);
    fd.append('name', document.getElementById('rail_name').value);
    fd.append('type', document.getElementById('rail_type').value);
    fd.append('price_per_meter', document.getElementById('rail_price').value);
    fetch('../ajax/manage_curtain.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => {
            if (d.success) { Swal.fire({title:'Berhasil!',text:d.message,icon:'success',timer:1500,showConfirmButton:false}).then(()=>location.reload()); }
            else Swal.fire('Gagal', d.message, 'error');
        }).catch(() => Swal.fire('Error', 'Koneksi gagal', 'error'));
}
function deleteRail(id, name) {
    Swal.fire({ title:'Hapus Rel?', html:`Hapus <strong>${name}</strong> secara permanen?`, icon:'warning', showCancelButton:true, confirmButtonColor:'#dc2626', cancelButtonColor:'#64748b', confirmButtonText:'Ya, Hapus', cancelButtonText:'Batal', reverseButtons:true
    }).then(r => { if(r.isConfirmed) {
        const fd = new FormData(); fd.append('action','delete_rail'); fd.append('id',id);
        fetch('../ajax/manage_curtain.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
            if(d.success) Swal.fire({title:'Dihapus!',text:d.message,icon:'success',timer:1500,showConfirmButton:false}).then(()=>location.reload());
            else Swal.fire('Gagal',d.message,'error');
        });
    }});
}
</script>

<?php include '../includes/footer.php'; ?>
