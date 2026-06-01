<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkMitra();

$page_title = "Pesanan Sprei Custom";
include '../includes/header.php';
include '../includes/sidebar_mitra.php';

// Fetch Mitra Data
$stmt = $pdo->prepare("SELECT full_name, affiliate_code FROM partners WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$partner = $stmt->fetch();
$mitra_name = $partner ? $partner['full_name'] : $_SESSION['email'];
$mitra_code = $partner ? $partner['affiliate_code'] : '-';
?>

<style>
.jadwal-card {
    border: 2px solid #e2e8f0;
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    user-select: none;
}
.jadwal-card:hover {
    border-color: #94a3b8;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}
.jadwal-card .card-icon {
    width: 48px; height: 48px;
    border-radius: 14px;
    background: #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px;
    transition: all 0.2s;
}
.jadwal-card .card-icon svg { color: #94a3b8; }
.jadwal-card .card-title { font-size: 14px; font-weight: 900; color: #94a3b8; }
.jadwal-card .card-sub   { font-size: 11px; color: #cbd5e1; margin-top: 4px; }
.jadwal-card .card-check {
    position: absolute; top: 12px; right: 12px;
    width: 24px; height: 24px; border-radius: 50%;
    border: 2px solid #e2e8f0;
    background: #f8fafc;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.2s;
}
.jadwal-card .card-check svg { color: #cbd5e1; width: 13px; height: 13px; stroke-width: 3; }

/* ACTIVE: dipilih → hijau emerald (berlaku untuk keduanya) */
.jadwal-card.selected {
    border-color: #059669;
    background: linear-gradient(135deg, #047857, #10b981);
    box-shadow: 0 8px 24px rgba(16,185,129,0.3);
}
.jadwal-card.selected .card-icon { background: rgba(255,255,255,0.15); }
.jadwal-card.selected .card-icon svg { color: #fff; }
.jadwal-card.selected .card-title { color: #fff; }
.jadwal-card.selected .card-sub   { color: #a7f3d0; }
.jadwal-card.selected .card-check { background: #fff; border-color: #fff; }
.jadwal-card.selected .card-check svg { color: #059669; }
</style>

<div class="flex-1 flex flex-col min-h-screen w-full overflow-x-hidden bg-slate-50">
    <main class="flex-1 p-4 md:p-8 pt-20 md:pt-8 w-full max-w-full">
        <div class="max-w-3xl mx-auto space-y-8 pb-20">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <a href="index.php" class="h-10 w-10 flex items-center justify-center bg-white rounded-xl border border-slate-200 shadow-sm hover:bg-slate-50 transition-colors">
                    <i data-lucide="arrow-left" class="h-5 w-5 text-slate-600"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Pesanan Sprei Custom</h1>
                    <p class="text-slate-500 text-sm">Form pemesanan sprei dengan ukuran dan spesifikasi khusus</p>
                </div>
            </div>

            <!-- Info Banner -->
            <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 flex items-start gap-3">
                <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                    <i data-lucide="info" class="h-4 w-4 text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-emerald-800">Pesanan Sprei Custom</p>
                    <p class="text-xs text-emerald-600 mt-0.5">Proses penjadwalan akan dikonfirmasi oleh admin.</p>
                </div>
            </div>

            <form id="klinikForm" class="space-y-6">

                <!-- Section 1: Identitas Instansi -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="user" class="h-4 w-4 text-emerald-600"></i>
                        </div>
                        <h3 class="font-bold text-slate-800">Data Customer</h3>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Nama Lengkap <span class="text-red-500">*</span></label>
                            <input type="text" id="namaInstansi" placeholder="cth: Budi Santoso" required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Instansi/Perusahaan (Opsional)</label>
                            <input type="text" id="namaPIC" placeholder="cth: PT Maju Bersama / Pribadi"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">No. WhatsApp <span class="text-red-500">*</span></label>
                            <div class="flex shadow-sm rounded-xl overflow-hidden border border-slate-200 focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-500 transition-all bg-slate-50">
                                <div class="flex items-center gap-1 px-3 bg-slate-100 border-r border-slate-200 text-slate-500 font-bold text-sm select-none shrink-0">
                                    <span>+62</span>
                                </div>
                                <input type="tel" id="waInstansi_display" required placeholder="Contoh: 8123456789" class="w-full px-4 py-3 bg-transparent border-0 focus:outline-none focus:ring-0 text-sm" />
                                <input type="hidden" id="waInstansi" value="+62" />
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Alamat Pengiriman <span class="text-red-500">*</span></label>
                            <input type="text" id="alamatInstansi" placeholder="Alamat lengkap instansi" required
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all" />
                        </div>
                    </div>
                </div>

                <!-- Section 2: Jadwal & Catatan -->
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-5">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="calendar" class="h-4 w-4 text-blue-600"></i>
                        </div>
                        <h3 class="font-bold text-slate-800">Jadwal Survey</h3>
                    </div>

                    <!-- Toggle Cards -->
                    <div class="grid grid-cols-2 gap-3">

                        <!-- Belum Ada Jadwal -->
                        <div id="card-belum" class="jadwal-card" onclick="toggleJadwal('belum')">
                            <input type="radio" name="statusJadwal" value="belum" checked class="hidden" />
                            <div class="card-check"><i data-lucide="check"></i></div>
                            <div class="card-icon"><i data-lucide="phone-call" class="h-6 w-6"></i></div>
                            <p class="card-title">Belum Ada Jadwal</p>
                            <p class="card-sub">Tim admin akan menghubungi customer</p>
                        </div>

                        <!-- Sudah Ada Jadwal -->
                        <div id="card-sudah" class="jadwal-card" onclick="toggleJadwal('sudah')">
                            <input type="radio" name="statusJadwal" value="sudah" class="hidden" />
                            <div class="card-check"><i data-lucide="check"></i></div>
                            <div class="card-icon"><i data-lucide="calendar-check" class="h-6 w-6"></i></div>
                            <p class="card-title">Sudah Ada Jadwal</p>
                            <p class="card-sub">Tim admin tinggal konfirmasi ke customer</p>
                        </div>

                    </div>

                    <!-- Date & Time Picker (muncul jika Sudah Ada Jadwal) -->
                    <div id="jadwalPicker" class="hidden">
                        <div class="p-4 bg-emerald-50 border-2 border-emerald-200 rounded-2xl space-y-3">
                            <p class="text-xs font-bold text-emerald-700 flex items-center gap-1.5">
                                <i data-lucide="calendar-clock" class="h-3.5 w-3.5"></i>
                                Tentukan Waktu Survey
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Tanggal</label>
                                    <div class="relative">
                                        <input type="date" id="jadwalTanggal"
                                            onclick="this.showPicker()"
                                            class="w-full px-4 py-3 bg-white border-2 border-emerald-300 rounded-xl text-sm font-semibold text-slate-700 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all shadow-sm hover:border-emerald-400 cursor-pointer" />
                                    </div>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Jam</label>
                                    <div class="relative">
                                        <input type="time" id="jadwalJam"
                                            onclick="this.showPicker()"
                                            class="w-full px-4 py-3 bg-white border-2 border-emerald-300 rounded-xl text-sm font-semibold text-slate-700 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all shadow-sm hover:border-emerald-400 cursor-pointer" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Detail / Keterangan Tambahan</label>
                        <textarea id="catatan" rows="4" placeholder="Ceritakan kebutuhan customer Anda, misalnya: ukuran custom, jenis bahan yang diinginkan, atau informasi lain yang relevan..."
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-none transition-all resize-none"></textarea>
                    </div>
                </div>

                <!-- Info Note -->
                <div class="flex items-start gap-3 p-4 bg-blue-50 border border-blue-100 rounded-2xl">
                    <i data-lucide="info" class="h-4 w-4 text-blue-500 mt-0.5 shrink-0"></i>
                    <p class="text-xs text-blue-700 leading-relaxed">
                        <span class="font-bold">Catatan:</span> Setelah Anda mengajukan survey, tim kami akan menghubungi customer untuk mengkonfirmasi jadwal.
                    </p>
                </div>

                <!-- Submit -->
                <button type="submit" class="w-full h-14 bg-emerald-500 hover:bg-emerald-600 text-white rounded-2xl font-black text-sm uppercase tracking-widest flex items-center justify-center gap-3 shadow-lg shadow-emerald-500/20 transition-all active:scale-[0.98]">
                    <i data-lucide="calendar-check" class="h-5 w-5"></i>
                    Ajukan Survey
                </button>

            </form>
        </div>
    </main>
</div>

<script>
document.getElementById('klinikForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const namaInstansi = document.getElementById('namaInstansi').value;
    const namaPIC     = document.getElementById('namaPIC').value;
    const waInstansi  = document.getElementById('waInstansi').value;
    const alamat      = document.getElementById('alamatInstansi').value;
    const catatan     = document.getElementById('catatan').value || '-';
    const statusJadwal = document.querySelector('input[name="statusJadwal"]:checked').value;

    let jadwalInfo = '';
    let instruksiAdmin = '';

    if (statusJadwal === 'sudah') {
        const tgl = document.getElementById('jadwalTanggal').value;
        const jam = document.getElementById('jadwalJam').value;
        if (!tgl || !jam) {
            Swal.fire({ icon: 'warning', title: 'Lengkapi Jadwal', text: 'Masukkan tanggal dan jam survey yang sudah disepakati.', confirmButtonColor: '#10b981' });
            return;
        }
        const tglFormatted = new Date(tgl + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        jadwalInfo = `\n📅 *Jadwal Survey:*\n• Tanggal: ${tglFormatted}\n• Jam: ${jam} WIB\n`;
        instruksiAdmin = `_✅ Jadwal sudah disepakati dengan customer. Mohon tim konfirmasi ulang ke customer. Terima kasih!_`;
    } else {
        jadwalInfo = `\n📅 *Jadwal Survey:* Belum ditentukan\n`;
        instruksiAdmin = `_📞 Jadwal belum ada. Mohon tim admin menghubungi customer untuk konfirmasi jadwal survey. Terima kasih!_`;
    }

    const message =
        `🛏️ *PENGAJUAN SURVEY SPREI CUSTOM*\n` +
        `━━━━━━━━━━━━━━━━━━━━━━\n\n` +
        `*Mitra:* <?php echo $mitra_name; ?> (<?php echo $mitra_code; ?>)\n\n` +
        `📋 *Data Customer:*\n` +
        `• Nama: ${namaInstansi}\n` +
        `• Instansi/Perusahaan: ${namaPIC || '-'}\n` +
        `• WA Customer: ${waInstansi}\n` +
        `• Alamat: ${alamat}\n` +
        jadwalInfo +
        `\n📝 *Catatan:*\n${catatan}\n\n` +
        instruksiAdmin;

    Swal.fire({
        title: 'Ajukan Survey?',
        html: `<p class="text-sm text-slate-500">Data ini akan disimpan dan tim kami akan menghubungi customer untuk konfirmasi.</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Ya, Ajukan!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const btn = document.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="loader-2" class="h-5 w-5 animate-spin"></i> Mengajukan...';
            btn.disabled = true;

            const formData = new FormData();
            formData.append('type', 'sprei_custom');
            formData.append('name', namaInstansi);
            formData.append('phone', waInstansi);
            formData.append('address', alamat);
            formData.append('notes', `Instansi/Perusahaan: ${namaPIC || '-'} | ${catatan}`);
            
            if (statusJadwal === 'sudah') {
                formData.append('date', document.getElementById('jadwalTanggal').value);
                formData.append('time', document.getElementById('jadwalJam').value);
            } else {
                formData.append('date', '');
                formData.append('time', '');
            }

            fetch('../ajax/submit_survey.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Survey berhasil diajukan.',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        window.open(`https://wa.me/6285159588681?text=${encodeURIComponent(message)}`, '_blank');
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire('Gagal', data.message, 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Terjadi kesalahan jaringan.', 'error');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
    });
});

function toggleJadwal(val) {
    const picker = document.getElementById('jadwalPicker');
    const cardB  = document.getElementById('card-belum');
    const cardS  = document.getElementById('card-sudah');

    // Reset both
    cardB.classList.remove('selected');
    cardS.classList.remove('selected');

    if (val === 'sudah') {
        picker.classList.remove('hidden');
        document.querySelector('input[name="statusJadwal"][value="sudah"]').checked = true;
        cardS.classList.add('selected');
    } else {
        picker.classList.add('hidden');
        document.querySelector('input[name="statusJadwal"][value="belum"]').checked = true;
        cardB.classList.add('selected');
    }
}

// Init on load
toggleJadwal('belum');

// Formatting No. WhatsApp dengan kotak terpisah
const phoneDisplay = document.getElementById('waInstansi_display');
const phoneHidden = document.getElementById('waInstansi');
if (phoneDisplay && phoneHidden) {
    phoneDisplay.addEventListener('input', function() {
        let val = this.value;
        let clean = val.replace(/[^\d]/g, '');
        
        if (clean.startsWith('62')) {
            clean = clean.substring(2);
        } else if (clean.startsWith('0')) {
            clean = clean.substring(1);
        }
        
        this.value = clean;
        phoneHidden.value = clean ? '+62' + clean : '+62';
    });
}
</script>

<?php include '../includes/footer.php'; ?>
