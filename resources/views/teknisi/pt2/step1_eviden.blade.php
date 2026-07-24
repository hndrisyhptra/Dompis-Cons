@extends('layouts.teknisi')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">
    
    {{-- Header --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-6 shadow-md rounded-b-[1.7rem]">
        <div class="flex items-center gap-4">
            <a href="{{ route('teknisi.pt2.step1', $project->id_project) }}" class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white backdrop-blur-sm active:scale-95 transition">
                ‹
            </a>
            <div>
                <p class="text-xs text-blue-200 font-medium uppercase tracking-widest">Lanjutan Step 1</p>
                <h1 class="text-lg font-black tracking-tight leading-tight mt-0.5">Upload Eviden (Mode {{ $mode }})</h1>
            </div>
        </div>
    </div>

    <div class="px-5 mt-6">
        
        <div class="mb-5 bg-blue-50 border border-blue-100 p-4 rounded-2xl flex items-start gap-3">
            <span class="text-xl">💡</span>
            <p class="text-xs font-medium text-blue-800 leading-relaxed">
                Anda berada di <strong>Mode {{ $mode }}</strong>. Foto akan <b>otomatis dikompres</b>. Tap ikon silang (✕) untuk menghapus foto dari daftar.
            </p>
        </div>

        <form id="evidenForm" action="{{ route('teknisi.pt2.storeStep1Eviden', $project->id_project) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="space-y-3">
                @foreach($requiredEvidences as $key => $label)
                    {{-- Collapsible Item --}}
                    <details class="group bg-white border border-slate-200 rounded-2xl shadow-sm [&_summary::-webkit-details-marker]:hidden transition-all duration-300">
                        
                        {{-- Summary / Header Area --}}
                        <summary id="summary-{{ $key }}" class="flex items-center justify-between p-4 cursor-pointer rounded-2xl transition">
                            <div class="flex items-center gap-3">
                                <div id="icon-{{ $key }}" class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-sm transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera-icon lucide-camera"><path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/></svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-slate-800">{{ $label }} <span class="text-red-500">*</span></h3>
                                    <p id="count-{{ $key }}" class="text-[10px] text-slate-400 font-medium mt-0.5">Belum ada foto dipilih</p>
                                </div>
                            </div>
                            <span class="text-slate-400 transition group-open:rotate-180">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </span>
                        </summary>

                        {{-- Konten Form Upload --}}
                        <div class="p-4 border-t border-slate-100 bg-slate-50/50 rounded-b-2xl">
                            
                            {{-- Area Klik / Dropzone --}}
                            <label class="relative flex flex-col items-center justify-center w-full h-24 border-2 border-slate-300 border-dashed rounded-xl cursor-pointer bg-white hover:bg-slate-50 transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-6 h-6 mb-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    <p class="text-xs text-slate-500 font-black">Tap untuk Tambah Foto</p>
                                </div>
                                {{-- Trigger Input. Kita ganti onchange menjadi fungsi handleFiles --}}
                                <input type="file" id="input-{{ $key }}" multiple accept="image/*" class="hidden" onchange="handleFiles(this, '{{ $key }}')">
                            </label>

                            {{-- Input Asli yang akan dikirim ke Backend (Disembunyikan) --}}
                            <input type="file" name="evidences[{{ $key }}][]" id="real-input-{{ $key }}" multiple class="hidden">

                            {{-- Loading Indicator Compress --}}
                            <div id="loading-{{ $key }}" class="hidden mt-2 text-[10px] font-bold text-blue-600 animate-pulse text-center">
                                Sedang mengompres foto...
                            </div>

                            {{-- Preview Grid --}}
                            <div id="preview-{{ $key }}" class="grid grid-cols-3 gap-2 mt-3 empty:hidden">
                                {{-- Preview Foto Akan Masuk Di Sini via JS --}}
                            </div>

                        </div>
                    </details>
                @endforeach
            </div>

            {{-- Tombol Submit --}}
            <button type="submit" id="btnSubmit" disabled 
                class="w-full h-12 bg-gray-300 text-gray-500 cursor-not-allowed font-black rounded-xl mt-8 shadow-sm transition-all duration-300 flex items-center justify-center gap-2">
                <span>Upload Eviden & Lanjut</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </button>

        </form>
    </div>
</div>

<script>
    // List kunci (key) wajib dari PHP
    const requiredKeys = @json(array_keys($requiredEvidences));
    
    // Objek penyimpan file (Virtual File System)
    let uploaderData = {};
    requiredKeys.forEach(key => uploaderData[key] = []);

    // 1. ENGINE COMPRESS IMAGE CLIENT SIDE
    function compressImage(file, maxWidth = 1280, quality = 0.75) {
        return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let w = img.width, h = img.height;
                    
                    // Resize jika melebihi maxWidth
                    if (w > maxWidth) { 
                        h = Math.round((h * maxWidth) / w); 
                        w = maxWidth; 
                    }
                    
                    canvas.width = w; 
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, w, h);
                    
                    // Konversi kembali ke file JPEG compress
                    canvas.toBlob((blob) => {
                        let newFileName = file.name.replace(/\.[^/.]+$/, "") + '.jpg';
                        resolve(new File([blob], newFileName, { type: 'image/jpeg', lastModified: Date.now() }));
                    }, 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    // Format ukuran byte ke KB/MB
    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + " B";
        else if (bytes < 1048576) return (bytes / 1024).toFixed(0) + " KB";
        else return (bytes / 1048576).toFixed(1) + " MB";
    }

    // 2. HANDLE INPUT (PROSES COMPRESS DAN MASUKKAN KE VIRTUAL SYSTEM)
    async function handleFiles(input, key) {
        const files = Array.from(input.files);
        if (files.length === 0) return;

        // Munculkan teks loading
        document.getElementById('loading-' + key).classList.remove('hidden');

        for (let file of files) {
            if (file.type.startsWith('image/')) {
                const compressedFile = await compressImage(file, 1280, 0.7); // Kualitas 70%, max width 1280px
                uploaderData[key].push({
                    file: compressedFile,
                    url: URL.createObjectURL(compressedFile)
                });
            }
        }

        // Reset input UI agar bisa upload file yang sama jika dihapus
        input.value = '';
        
        // Sembunyikan loading dan update preview
        document.getElementById('loading-' + key).classList.add('hidden');
        renderPreview(key);
    }

    // 3. RENDER PREVIEW FOTO + TOMBOL HAPUS
    function renderPreview(key) {
        const container = document.getElementById('preview-' + key);
        container.innerHTML = ''; 

        let filesArray = uploaderData[key];

        filesArray.forEach((item, index) => {
            let div = document.createElement('div');
            div.className = 'relative rounded-xl overflow-hidden border border-slate-200 aspect-square shadow-sm group';
            
            div.innerHTML = `
                <img src="${item.url}" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/10"></div>
                
                {{-- Label Ukuran File (Bukti Compress Jalan) --}}
                <div class="absolute bottom-0 inset-x-0 bg-black/60 text-white text-[9px] px-1.5 py-0.5 text-center truncate">
                    ${formatBytes(item.file.size)}
                </div>

                {{-- Tombol Silang (Hapus) --}}
                <button type="button" onclick="removeFile('${key}', ${index})" class="absolute top-1 right-1 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-xs font-black shadow-md opacity-90 hover:opacity-100 hover:scale-110 transition">
                    ✕
                </button>
            `;
            container.appendChild(div);
        });

        updateUIState(key);
        syncInputToForm(key);
    }

    // 4. HAPUS FOTO
    function removeFile(key, index) {
        // Hapus file dari memori browser
        URL.revokeObjectURL(uploaderData[key][index].url);
        // Hapus dari array virtual
        uploaderData[key].splice(index, 1);
        
        // Render ulang
        renderPreview(key);
    }

    // 5. UPDATE TAMPILAN HEADER AKORDEON (CENTANG / BELUM)
    function updateUIState(key) {
        let count = uploaderData[key].length;

        if (count > 0) {
            document.getElementById('summary-' + key).classList.add('bg-green-50/50');
            document.getElementById('icon-' + key).classList.replace('bg-slate-100', 'bg-green-100');
            document.getElementById('icon-' + key).classList.replace('text-slate-400', 'text-green-600');
            
            // Ganti innerText dengan innerHTML dan masukkan kode SVG
            document.getElementById('icon-' + key).innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-square-check-icon lucide-square-check"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="m9 12 2 2 4-4"/></svg>`;

            document.getElementById('count-' + key).innerText = count + ' foto siap upload';
            document.getElementById('count-' + key).classList.replace('text-slate-400', 'text-green-600');
        
        } else {
            document.getElementById('summary-' + key).classList.remove('bg-green-50/50');
            document.getElementById('icon-' + key).classList.replace('bg-green-100', 'bg-slate-100');
            document.getElementById('icon-' + key).innerText = '📷';
            document.getElementById('count-' + key).innerText = 'Belum ada foto dipilih';
            document.getElementById('count-' + key).classList.replace('text-green-600', 'text-slate-400');
        }
    }

    // 6. SYNC DATA VIRTUAL KE INPUT HIDDEN FORM
    function syncInputToForm(key) {
        // Menggunakan DataTransfer untuk menyisipkan File ke dalam input form HTML asli
        const dt = new DataTransfer();
        uploaderData[key].forEach(item => {
            dt.items.add(item.file);
        });
        
        document.getElementById('real-input-' + key).files = dt.files;
        
        checkFormValidity();
    }

    // 7. CEK VALIDITAS TOMBOL SUBMIT
    function checkFormValidity() {
        // Pastikan setiap kategori (key) minimal punya 1 file
        let isValid = requiredKeys.every(key => uploaderData[key].length > 0);
        let btn = document.getElementById('btnSubmit');

        if (isValid) {
            btn.disabled = false;
            btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.add('bg-blue-600', 'hover:bg-blue-700', 'text-white', 'shadow-lg');
        } else {
            btn.disabled = true;
            btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'text-white', 'shadow-lg');
        }
    }
</script>
@endsection