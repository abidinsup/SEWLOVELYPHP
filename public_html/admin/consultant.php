<?php
require_once '../includes/session.php';
require_once '../includes/config.php';
checkAdmin();

$page_title = "Konsultan AI - Sewlovely Homeset";
include '../includes/header.php';
include '../includes/sidebar_admin.php';
?>

<style>
@keyframes scanLine {
    0%, 100% { top: 0%; opacity: 0; }
    10% { opacity: 1; }
    50% { top: 100%; opacity: 1; }
    90% { opacity: 1; }
}
</style>

<div class="flex-1 flex flex-col min-h-screen w-full overflow-x-hidden relative font-sans" style="background-color: #FAFAFA;">
    
    <!-- Soft Light Aurora Background -->
    <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] rounded-full bg-emerald-300 opacity-20 pointer-events-none" style="filter: blur(100px);"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] rounded-full bg-blue-300 opacity-20 pointer-events-none" style="filter: blur(100px);"></div>

    <main class="flex-1 p-4 lg:p-8 pt-20 lg:pt-8 w-full max-w-full relative z-10">
        <div class="max-w-5xl mx-auto h-[calc(100vh-120px)] flex flex-col">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-white flex items-center justify-center border border-slate-100 relative group overflow-hidden" style="box-shadow: 0 8px 30px rgba(0,0,0,0.06);">
                        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity" style="background: linear-gradient(to bottom right, #ecfdf5, #eff6ff);"></div>
                        <i data-lucide="bot" class="h-8 w-8 text-emerald-600 relative z-10 group-hover:scale-110 transition-transform duration-500"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Sewlovely AI Consultant</h1>
                        <p class="text-slate-500 text-sm font-medium flex items-center gap-2 mt-1">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                            Sistem Cerdas Siap Membantu
                        </p>
                    </div>
                </div>
                
                <!-- Clean Multi-Modal Badge -->
                <div class="hidden md:flex items-center gap-3 px-4 py-2 bg-white rounded-full border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center" style="background-color: #ecfdf5; color: #059669;"><i data-lucide="scan-line" class="h-3.5 w-3.5"></i></div>
                        <div class="w-7 h-7 rounded-full flex items-center justify-center" style="background-color: #eff6ff; color: #2563eb;"><i data-lucide="image" class="h-3.5 w-3.5"></i></div>
                        <div class="w-7 h-7 rounded-full flex items-center justify-center" style="background-color: #faf5ff; color: #9333ea;"><i data-lucide="sparkles" class="h-3.5 w-3.5"></i></div>
                    </div>
                    <div class="h-5 w-px bg-slate-200"></div>
                    <span class="text-xs font-bold text-slate-600">Multi-Modal Ready</span>
                </div>
            </div>

            <!-- Chat Container -->
            <div class="flex-1 bg-white border border-slate-200 overflow-hidden flex flex-col relative" style="border-radius: 2rem; box-shadow: 0 20px 60px -15px rgba(0,0,0,0.05); background-color: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px);">
                
                <!-- Chat History -->
                <div id="chatHistory" class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-8 scrollbar-thin scrollbar-thumb-slate-200 scrollbar-track-transparent">
                    
                    <!-- AI Welcome Message -->
                    <div class="flex gap-4 max-w-[90%] md:max-w-[80%] animate-in slide-in-from-left-4 duration-500 fade-in">
                        <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/20">
                            <i data-lucide="sparkles" class="text-white h-6 w-6"></i>
                        </div>
                        <div class="space-y-2">
                            <div class="bg-slate-50 p-5 rounded-3xl rounded-tl-none text-slate-700 text-[15px] leading-relaxed border border-slate-100 shadow-sm">
                                Halo! Saya adalah <strong>Sewlovely AI</strong>, asisten cerdas Anda. 
                                <br><br>
                                Saya siap membantu merekomendasikan desain interior gorden. Anda juga bisa menekan tombol <strong class="text-emerald-600">Kamera</strong> untuk menganalisa jendela secara otomatis.
                            </div>
                            <span class="text-[11px] text-slate-400 font-bold px-2 uppercase tracking-widest">Sistem • Siap</span>
                        </div>
                    </div>



                </div>

                <!-- Input Area -->
                <div class="p-6 bg-white border-t border-slate-100" style="background-color: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px);">
                    <form id="chatForm" class="relative max-w-4xl mx-auto">
                        <div class="relative flex items-center bg-white border border-slate-200 focus-within:border-emerald-400 focus-within:ring-4 focus-within:ring-emerald-50 rounded-full transition-all duration-300 shadow-sm p-2 hover:shadow-md">
                            <button type="button" onclick="startCamera()" title="Kamera AI Jendela" class="h-12 w-12 shrink-0 text-slate-500 hover:text-emerald-600 transition-all duration-300 rounded-full flex items-center justify-center mr-3 group" style="background-color: #f8fafc;">
                                <i data-lucide="camera" class="h-5 w-5 group-hover:scale-110 transition-transform"></i>
                            </button>
                            
                            <textarea 
                                id="chatInput"
                                rows="1" 
                                placeholder="Ketik instruksi atau pertanyaan..." 
                                class="w-full bg-transparent border-none py-3 text-slate-700 placeholder-slate-400 focus:ring-0 text-[15px] resize-none max-h-32 font-medium"
                                style="scrollbar-width: none;"
                            ></textarea>
                            
                            <button type="submit" class="h-12 w-12 shrink-0 bg-emerald-500 hover:bg-emerald-600 text-white rounded-full flex items-center justify-center transition-all hover:scale-105 active:scale-95 ml-3" style="box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);">
                                <i data-lucide="send-horizonal" class="h-5 w-5 ml-0.5"></i>
                            </button>
                        </div>
                    </form>
                    <p class="text-[10px] text-slate-400 mt-4 text-center tracking-wide uppercase font-bold">AI dapat memberikan respons yang tidak akurat. Harap verifikasi dengan katalog.</p>
                </div>

            </div>
        </div>
    </main>
</div>

<!-- Camera Modal -->
<div id="cameraModal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 lg:p-8" style="background-color: rgba(15, 23, 42, 0.8); backdrop-filter: blur(4px);">
    <div class="bg-white shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-300" style="border-radius: 2.5rem;">
        
        <!-- Modal Header -->
        <div class="p-5 flex justify-between items-center bg-slate-50 border-b border-slate-100">
            <h3 class="font-black text-slate-800 text-lg flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                    <i data-lucide="scan-face" class="h-5 w-5"></i>
                </div>
                Visual AI Scanner
            </h3>
            <button type="button" onclick="stopCamera()" class="p-2 text-slate-400 hover:text-red-500 bg-white hover:bg-red-50 rounded-full transition-all border border-slate-200">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <!-- Camera Area -->
        <div class="p-4 bg-black relative flex items-center justify-center min-h-[400px] md:min-h-[500px] overflow-hidden">
            <video id="cameraVideo" class="w-full h-full absolute inset-0 object-cover" autoplay playsinline></video>
            
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none hidden bg-slate-900/80 backdrop-blur-sm z-20" id="cameraLoading">
                <div class="flex flex-col items-center gap-4">
                    <div class="w-16 h-16 border-4 border-slate-700 border-t-emerald-500 rounded-full animate-spin"></div>
                    <span class="text-emerald-400 font-bold tracking-widest text-sm animate-pulse">MEMBUAT OPTIK...</span>
                </div>
            </div>
            
            <!-- Scanning UI Elements -->
            <div class="absolute inset-8 border-2 border-emerald-500/50 pointer-events-none z-10 flex flex-col justify-between overflow-hidden rounded-2xl">
                <!-- Corners -->
                <div class="absolute -top-1 -left-1 w-8 h-8 border-t-4 border-l-4 border-emerald-400"></div>
                <div class="absolute -top-1 -right-1 w-8 h-8 border-t-4 border-r-4 border-emerald-400"></div>
                <div class="absolute -bottom-1 -left-1 w-8 h-8 border-b-4 border-l-4 border-emerald-400"></div>
                <div class="absolute -bottom-1 -right-1 w-8 h-8 border-b-4 border-r-4 border-emerald-400"></div>
                
                <!-- Scanning Line Animation -->
                <div class="w-full h-1 bg-emerald-400 shadow-[0_0_15px_rgba(16,185,129,1)] absolute top-0" style="animation: scanLine 3s ease-in-out infinite;"></div>
            </div>
            
            <div class="absolute bottom-12 z-10 pointer-events-none flex flex-col items-center">
                <div class="px-6 py-2 bg-black/50 backdrop-blur-md rounded-full border border-white/10 text-white text-xs font-black tracking-widest flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    ARAHKAN KE JENDELA
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="p-6 bg-slate-50 border-t border-slate-100 flex justify-center items-center">
            <button type="button" onclick="takeSnapshot()" class="relative h-20 w-20 flex items-center justify-center group focus:outline-none">
                <div class="absolute inset-0 bg-emerald-100 rounded-full scale-110 group-hover:scale-125 transition-transform duration-300 opacity-50"></div>
                <div class="absolute inset-0 rounded-full border-4 border-emerald-200 group-hover:border-emerald-300 transition-colors duration-300"></div>
                <div class="h-16 w-16 bg-emerald-500 rounded-full shadow-[0_10px_20px_rgba(16,185,129,0.3)] group-hover:scale-95 group-active:scale-90 transition-transform duration-200 flex items-center justify-center z-10 border-4 border-white">
                    <i data-lucide="aperture" class="h-6 w-6 text-white group-hover:rotate-90 transition-transform duration-700"></i>
                </div>
            </button>
        </div>
    </div>
</div>

<script>
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatHistory = document.getElementById('chatHistory');

    // Auto-resize textarea
    chatInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    function addMessage(text, isUser = false) {
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const messageHtml = isUser ? `
            <div class="flex gap-4 justify-end animate-in slide-in-from-right-4 duration-300 fade-in">
                <div class="space-y-2 flex flex-col items-end max-w-[90%] md:max-w-[80%]">
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-500 p-5 rounded-3xl rounded-tr-none text-white text-[15px] leading-relaxed shadow-lg shadow-emerald-500/20 font-medium">
                        ${text}
                    </div>
                    <span class="text-[11px] text-slate-400 font-bold px-2 uppercase tracking-widest">${time}</span>
                </div>
            </div>
        ` : `
            <div class="flex gap-4 max-w-[90%] md:max-w-[80%] animate-in slide-in-from-left-4 duration-300 fade-in">
                <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/20">
                    <i data-lucide="sparkles" class="text-white h-6 w-6"></i>
                </div>
                <div class="space-y-2">
                    <div class="bg-slate-50 p-5 rounded-3xl rounded-tl-none text-slate-700 text-[15px] leading-relaxed border border-slate-100 shadow-sm">
                        ${text}
                    </div>
                    <span class="text-[11px] text-slate-400 font-bold px-2 uppercase tracking-widest">AI • ${time}</span>
                </div>
            </div>
        `;

        chatHistory.insertAdjacentHTML('beforeend', messageHtml);
        chatHistory.scrollTop = chatHistory.scrollHeight;
        lucide.createIcons();
    }



    async function callAIResponse(query) {
        // Show typing indicator
        const typingHtml = `
            <div id="typingIndicator" class="flex gap-4 max-w-[90%] md:max-w-[80%] animate-in fade-in zoom-in-95 duration-300">
                <div class="h-12 w-12 rounded-2xl bg-slate-50 flex items-center justify-center shrink-0 border border-slate-100 shadow-sm">
                    <i data-lucide="loader-2" class="text-emerald-500 h-6 w-6 animate-spin"></i>
                </div>
                <div class="bg-slate-50 p-5 rounded-3xl rounded-tl-none flex gap-2 items-center border border-slate-100 shadow-sm">
                    <span class="w-2.5 h-2.5 bg-emerald-400 rounded-full animate-bounce" style="animation-delay: 0s"></span>
                    <span class="w-2.5 h-2.5 bg-emerald-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></span>
                    <span class="w-2.5 h-2.5 bg-emerald-400 rounded-full animate-bounce" style="animation-delay: 0.4s"></span>
                </div>
            </div>
        `;
        chatHistory.insertAdjacentHTML('beforeend', typingHtml);
        chatHistory.scrollTop = chatHistory.scrollHeight;
        lucide.createIcons();

        try {
            const response = await fetch('../ajax/ai_consultant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ query: query })
            });
            const result = await response.json();

            const indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();

            if (result.success) {
                addMessage(result.text);
            } else {
                addMessage("Maaf, terjadi kesalahan saat menghubungi asisten AI. Silakan coba lagi nanti.");
            }
        } catch (error) {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) indicator.remove();
            addMessage("Maaf, koneksi ke server terputus.");
        }
    }
    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = chatInput.value.trim();
        if (text) {
            addMessage(text, true);
            chatInput.value = '';
            chatInput.style.height = 'auto';
            callAIResponse(text);
        }
    });

    // --- Camera AI Feature ---
    let stream = null;

    async function startCamera() {
        const modal = document.getElementById('cameraModal');
        const video = document.getElementById('cameraVideo');
        const loading = document.getElementById('cameraLoading');
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        loading.classList.remove('hidden');
        loading.classList.add('flex');

        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            video.srcObject = stream;
            video.onloadedmetadata = () => {
                loading.classList.add('hidden');
                loading.classList.remove('flex');
            };
        } catch (err) {
            alert("Gagal mengakses kamera: " + err.message + "\n\nPastikan Anda memberikan izin akses kamera atau menggunakan HTTPS/localhost.");
            stopCamera();
        }
    }

    function stopCamera() {
        const modal = document.getElementById('cameraModal');
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function takeSnapshot() {
        const video = document.getElementById('cameraVideo');
        if (!stream) return;

        // Create canvas and draw frame
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Get Base64 image (Quality 0.7 for lighter memory footprint)
        const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
        
        // Stop camera and hide modal
        stopCamera();

        // Show image in chat as user message
        addImageMessage(dataUrl);
        
        // Trigger real AI Image Analysis
        callAIImageAnalysis(dataUrl);
    }

    function addImageMessage(dataUrl) {
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const messageHtml = `
            <div class="flex gap-4 justify-end animate-in slide-in-from-right-4 duration-300 fade-in">
                <div class="space-y-2 flex flex-col items-end max-w-[90%] md:max-w-[80%]">
                    <div class="p-2 rounded-3xl rounded-tr-none bg-white shadow-lg border border-slate-100 relative overflow-hidden group">
                        <img src="${dataUrl}" class="max-w-[260px] sm:max-w-sm rounded-2xl border border-slate-100 object-cover" alt="Foto Jendela" />
                        <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-md rounded-lg px-2 py-1 flex items-center gap-1 shadow-sm border border-slate-100">
                            <i data-lucide="scan-line" class="h-3 w-3 text-emerald-600"></i>
                            <span class="text-[9px] text-slate-700 font-bold tracking-widest uppercase">Scanned</span>
                        </div>
                    </div>
                    <span class="text-[11px] text-slate-400 font-bold px-2 uppercase tracking-widest">${time}</span>
                </div>
            </div>
        `;
        chatHistory.insertAdjacentHTML('beforeend', messageHtml);
        chatHistory.scrollTop = chatHistory.scrollHeight;
        lucide.createIcons();
    }

    async function callAIImageAnalysis(dataUrl) {
        const typingHtml = `
            <div id="typingIndicatorImage" class="flex gap-4 max-w-[90%] md:max-w-[80%] animate-in fade-in duration-300">
                <div class="h-12 w-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/20">
                    <i data-lucide="scan-face" class="text-white h-6 w-6"></i>
                </div>
                <div class="bg-white p-5 rounded-3xl rounded-tl-none flex gap-3 items-center border border-slate-100 shadow-sm">
                    <div class="relative flex h-4 w-4">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500"></span>
                    </div>
                    <span class="text-sm text-emerald-600 font-black tracking-widest uppercase">Menganalisa Visual...</span>
                </div>
            </div>
        `;
        chatHistory.insertAdjacentHTML('beforeend', typingHtml);
        chatHistory.scrollTop = chatHistory.scrollHeight;
        lucide.createIcons();

        try {
            const response = await fetch('../ajax/ai_consultant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ image: dataUrl })
            });
            const result = await response.json();

            const indicator = document.getElementById('typingIndicatorImage');
            if (indicator) indicator.remove();

            if (result.success && result.type === 'recommendation') {
                const rec = result.data;
                const responseHtml = `
                    <div class="mb-5 text-emerald-600 font-black tracking-widest text-xs uppercase flex items-center gap-2 border-b border-slate-200 pb-3">
                        <i data-lucide="check-circle-2" class="h-5 w-5"></i> Analisa Visual Selesai
                    </div>
                    <strong>${rec.title}</strong>
                    <br><br>
                    ${rec.description}
                    <br><br>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">${rec.fabricType}</span>
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold">${rec.style}</span>
                        <div class="flex items-center gap-2 px-3 py-1 bg-slate-100 rounded-full text-xs font-bold">
                            <div class="w-3 h-3 rounded-full border border-slate-300" style="background-color: ${rec.colorHex}"></div>
                            <span>${rec.colorHex}</span>
                        </div>
                    </div>
                `;
                addMessage(responseHtml);
            } else if (result.success && result.type === 'text') {
                addMessage(result.text);
            } else {
                addMessage("Gagal menganalisa gambar. Pastikan gambar cukup terang dan jelas.");
            }
        } catch (error) {
            const indicator = document.getElementById('typingIndicatorImage');
            if (indicator) indicator.remove();
            addMessage("Maaf, terjadi kesalahan koneksi saat menganalisa gambar.");
        }
    }
</script>

<?php include '../includes/footer.php'; ?>
