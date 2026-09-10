@extends('layouts.waspang')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#F8FAFC] pb-28">

    {{--
        Revisi stepper: Step 2 "Persiapan Instalasi" (sequence 6) --
        sebelumnya cuma pass-through di dalam Step 1 Persiapan, sekarang
        jadi HALAMAN SENDIRI dgn 2 kartu Barang Tiba/Perizinan -- persis
        pola Step 1 Persiapan SEBELUM refactor 5 sub-step Stage 4d.
        Variabel disiapkan controller (WaspangController::persiapanInstalasi()):
        $project, $lop, $barangTibaPhotos/$barangTibaUploaded/$barangTibaStatus,
        $perizinanPhotos/$perizinanUploaded/$perizinanStatus,
        $persiapanInstalasiUploadedComplete, $kronologis, $kendalaCategories.
    --}}

    {{-- HEADER & STEPPER --}}
    @include('waspang.partials.stepper')

    {{-- PROJECT INFO CARD --}}
    <div class="px-4 mt-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <div class="mb-3">
                <p class="text-xs text-slate-400 font-medium">Nama LOP</p>
                <p class="text-sm font-bold text-slate-900 break-words mt-0.5">{{ $project->project_name }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3 border-t border-slate-50 pt-3">
                <div>
                    <p class="text-xs text-slate-400 font-medium">STO</p>
                    <p class="text-xs font-bold text-slate-800 font-mono mt-0.5">{{ $lop->sto ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-medium">Branch</p>
                    <p class="text-xs font-bold text-slate-800 mt-0.5">{{ $lop->branch ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="px-4 mt-3 space-y-3">

        {{-- KARTU 1: EVIDEN BARANG TIBA --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <div class="p-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shrink-0
                        {{ $barangTibaStatus == 'approved' ? 'bg-emerald-50 text-emerald-600' : ($barangTibaStatus == 'rejected' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-[#1565D8]') }}">
                        @if($barangTibaStatus == 'approved')
                            <i class="fa-solid fa-check"></i>
                        @elseif($barangTibaStatus == 'rejected')
                            <i class="fa-solid fa-exclamation"></i>
                        @else
                            <i class="fa-solid fa-truck-fast"></i>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">1. Eviden Barang Tiba</h3>
                        <p class="text-[10.5px] font-medium text-slate-400 mt-0.5">{{ $barangTibaPhotos->count() }} Eviden Terlampir</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide shrink-0
                    {{ $barangTibaStatus == 'approved' ? 'bg-emerald-100 text-emerald-700' : ($barangTibaStatus == 'rejected' ? 'bg-red-100 text-red-700' : ($barangTibaStatus == 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500')) }}">
                    {{ $barangTibaStatus == 'approved' ? 'Disetujui' : ($barangTibaStatus == 'rejected' ? 'Ditolak' : ($barangTibaStatus == 'pending' ? 'Menunggu Review' : 'Belum Upload')) }}
                </span>
            </div>

            <div class="border-t border-slate-50 bg-slate-50/30 p-4 space-y-3">
                @include('waspang.partials.evidence-photo-grid', ['photos' => $barangTibaPhotos])

                <button type="button" onclick="openUploadModal('persiapan', 'barang_tiba', 'Eviden Barang Tiba', false)"
                        class="h-9 w-full rounded-xl bg-[#1565D8] hover:bg-[#0F4FAF] text-white text-xs font-bold transition shadow-xs">
                    <i class="fa-solid fa-camera mr-1"></i> Upload Eviden Barang Tiba
                </button>
            </div>
        </div>

        {{-- KARTU 2: EVIDEN PERIZINAN --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-xs">
            <div class="p-4 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shrink-0
                        {{ $perizinanStatus == 'approved' ? 'bg-emerald-50 text-emerald-600' : ($perizinanStatus == 'rejected' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-[#1565D8]') }}">
                        @if($perizinanStatus == 'approved')
                            <i class="fa-solid fa-check"></i>
                        @elseif($perizinanStatus == 'rejected')
                            <i class="fa-solid fa-exclamation"></i>
                        @else
                            <i class="fa-solid fa-file-shield"></i>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">2. Eviden Perizinan</h3>
                        <p class="text-[10.5px] font-medium text-slate-400 mt-0.5">{{ $perizinanPhotos->count() }} Eviden Terlampir</p>
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide shrink-0
                    {{ $perizinanStatus == 'approved' ? 'bg-emerald-100 text-emerald-700' : ($perizinanStatus == 'rejected' ? 'bg-red-100 text-red-700' : ($perizinanStatus == 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-500')) }}">
                    {{ $perizinanStatus == 'approved' ? 'Disetujui' : ($perizinanStatus == 'rejected' ? 'Ditolak' : ($perizinanStatus == 'pending' ? 'Menunggu Review' : 'Belum Upload')) }}
                </span>
            </div>

            <div class="border-t border-slate-50 bg-slate-50/30 p-4 space-y-3">
                @include('waspang.partials.evidence-photo-grid', ['photos' => $perizinanPhotos])

                <button type="button" onclick="openUploadModal('persiapan', 'perizinan', 'Eviden Perizinan', false)"
                        class="h-9 w-full rounded-xl bg-[#1565D8] hover:bg-[#0F4FAF] text-white text-xs font-bold transition shadow-xs">
                    <i class="fa-solid fa-camera mr-1"></i> Upload Eviden Perizinan
                </button>
            </div>
        </div>

        {{-- KRONOLOGI PERSIAPAN INSTALASI --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
            <div class="flex items-center justify-between mb-1.5">
                <p class="text-[11px] font-black text-slate-600">Kronologi Persiapan Instalasi</p>
                <button type="button" onclick="openKronologiModal('persiapan_instalasi', 'Persiapan Instalasi')" class="text-[11px] font-black text-[#1565D8]">
                    <i class="fa-solid fa-plus mr-0.5"></i> Tambah
                </button>
            </div>

            @php $piKronologis = $kronologis->where('stage_code', 'persiapan_instalasi'); @endphp

            @if($piKronologis->isEmpty())
                <p class="text-xs text-slate-400 italic">Belum ada kronologi. Setiap aktivitas persiapan instalasi wajib dicatat kronologinya.</p>
            @else
                <div class="space-y-2">
                    @foreach($piKronologis as $k)
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-2.5">
                            <p class="text-[10px] font-black text-[#1565D8]">{{ optional($k->event_date)->format('d M Y') }}</p>
                            <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">{{ $k->note }}</p>
                            <p class="text-[9.5px] text-slate-400 mt-1">{{ $k->creator?->name ?? '-' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @include('waspang.partials.step-action-buttons', ['stageCode' => 'persiapan_instalasi', 'stepLabel' => 'Persiapan Instalasi'])
    </div>

    {{-- CTA FINAL: lanjut ke Step 3 - Instalasi (transisi status_progress
         terjadi di sini, WaspangController::finishPersiapanInstalasi()) --}}
    <div class="px-4 mt-4">
        @if($persiapanInstalasiUploadedComplete && $barangTibaStatus !== 'rejected' && $perizinanStatus !== 'rejected')
            <form method="POST" action="{{ route('waspang.persiapan-instalasi.finish', $project->id_project) }}" onsubmit="return confirm('Persiapan Instalasi selesai & lanjut ke Step 3 Instalasi?')">
                @csrf
                <button type="submit" class="h-11 w-full rounded-xl bg-[#1565D8] hover:bg-[#0F4FAF] text-white inline-flex items-center justify-center text-sm font-bold shadow-sm transition">
                    Lanjut Step 3 - Instalasi <i class="fa-solid fa-chevron-right ml-2 text-xs"></i>
                </button>
            </form>
        @else
            <button disabled class="h-11 w-full rounded-xl bg-slate-200 text-slate-400 inline-flex items-center justify-center text-sm font-bold cursor-not-allowed">
                @if($barangTibaStatus === 'rejected' || $perizinanStatus === 'rejected')
                    Perbaiki Eviden yang Ditolak Terlebih Dahulu
                @else
                    Lengkapi Eviden Barang Tiba &amp; Perizinan
                @endif
            </button>
        @endif
    </div>

    {{-- MODAL UPLOAD OVERLAY (generik, sama pola dgn Step 1) --}}
    <div id="uploadModal" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs animate-fade-in">
        <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            <div class="bg-[#1565D8] text-white px-5 py-4 flex items-start justify-between shrink-0">
                <div>
                    <h2 class="text-lg font-black tracking-tight">Upload Eviden</h2>
                    <p id="selectedBoqName" class="text-xs text-blue-100 mt-1 font-medium break-all line-clamp-1">Nama komponen item BOQ</p>
                </div>
                <button type="button" onclick="closeUploadModal()" class="w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 text-white font-black text-lg flex items-center justify-center transition">×</button>
            </div>

            <form id="uploadForm" method="POST" action="{{ route('waspang.evidence.upload', $project->id_project) }}" class="flex flex-col min-h-0 overflow-y-auto p-5 space-y-4">
                @csrf
                <input type="hidden" name="stage" id="upload_stage" value="persiapan">
                <input type="hidden" name="evidence_type" id="upload_evidence_type">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                {{-- DROPZONE AREA --}}
                <div class="text-xs">
                    <label class="text-xs font-black text-slate-600 block mb-1.5">Pilih/Ambil Eviden</label>
                    <label class="flex flex-col items-center justify-center w-full min-h-[125px] border-2 border-dashed border-blue-300 rounded-2xl bg-blue-50/40 cursor-pointer hover:bg-blue-50 transition p-4">
                        <div class="text-center">
                            <div class="mx-auto w-11 h-11 rounded-xl bg-[#1565D8] text-white flex items-center justify-center text-xl font-black shadow-sm">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                            <p class="mt-2.5 text-xs font-black text-[#0F4FAF]">Pilih Eviden</p>
                            <p id="uploadAcceptHint" class="text-[10px] text-slate-400 mt-0.5">JPG, PNG, WEBP · Auto Compress</p>
                        </div>
                        <input type="file" id="photoInput" accept="image/*" multiple class="hidden">
                    </label>

                    {{-- PREVIEW MULTIPLE IMAGES CONTAINER --}}
                    <div id="previewWrapper" class="mt-3 hidden animate-fade-in">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wide">Preview Eviden (<span id="photoCount">0</span>)</p>
                            <button type="button" id="clearAllPhotos" class="text-[11px] font-bold text-red-600 hover:text-red-700 transition">Hapus Semua</button>
                        </div>
                        <div id="previewContainer" class="grid grid-cols-3 gap-2"></div>
                    </div>
                </div>

                <div class="text-xs">
                    <label class="text-xs font-black text-slate-600 block">Catatan Tambahan / Deskripsi <span class="text-slate-400">(Opsional)</span></label>
                    <textarea name="description" rows="3" placeholder="Tulis keterangan tambahan jika ada..." class="mt-1.5 w-full rounded-2xl border border-slate-300 px-3 py-2 text-xs focus:ring-2 focus:ring-blue-100 focus:border-[#1565D8] outline-none transition resize-none"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2 shrink-0">
                    <button type="button" onclick="closeUploadModal()" class="h-11 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-black transition">Batal</button>
                    <button type="submit" class="h-11 rounded-2xl bg-[#1565D8] hover:bg-[#0F4FAF] text-white text-sm font-black shadow-md transition">Upload</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL KENDALA & KRONOLOGI (universal) --}}
    @include('waspang.partials.kendala-modal')
    @include('waspang.partials.kronologi-modal')

    @include('waspang.partials.bottom-nav', ['active' => 'inbox'])
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .swal2-container { z-index: 10000 !important; }
</style>
<script>
let selectedFiles = [];
let uploadAcceptsPdf = false;

// === AUTO COMPRESS FOTO (sama pola dgn Step 1 Persiapan) =================
// Tidak ada validasi metadata (EXIF) -- setiap foto (bukan PDF) otomatis
// dikompres (resize max width 1280px, JPEG quality 0.75) sebelum diupload.
function compressImage(file, maxWidth = 1280, quality = 0.75) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width, height = img.height;
                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }
                canvas.width = width; canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                canvas.toBlob((blob) => {
                    if (!blob) { resolve(file); return; }
                    resolve(new File([blob], file.name.replace(/\.[^/.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', quality);
            };
            img.onerror = () => resolve(file);
            img.src = event.target.result;
        };
        reader.onerror = () => resolve(file);
        reader.readAsDataURL(file);
    });
}

function openUploadModal(stage, type, title, acceptPdf) {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');

    document.getElementById('upload_stage').value = stage;
    document.getElementById('upload_evidence_type').value = type;
    document.getElementById('selectedBoqName').innerText = title;

    uploadAcceptsPdf = !!acceptPdf;
    const input = document.getElementById('photoInput');
    const hint = document.getElementById('uploadAcceptHint');
    if (uploadAcceptsPdf) {
        input.setAttribute('accept', 'image/*,application/pdf');
        hint.innerText = 'JPG, PNG, WEBP, PDF';
    } else {
        input.setAttribute('accept', 'image/*');
        hint.innerText = 'JPG, PNG, WEBP · Auto Compress';
    }

    clearAllPhotosAction();

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
        }, function(err) {
            console.warn("GPS lock bypass: ", err.message);
        }, { enableHighAccuracy: true });
    }
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    document.getElementById('uploadModal').classList.remove('flex');
}

document.getElementById('photoInput').addEventListener('change', async function(e) {
    const files = Array.from(e.target.files);
    for (const file of files) {
        if (file.type.startsWith('image/')) {
            const compressed = await compressImage(file);
            selectedFiles.push({
                file: compressed,
                url: URL.createObjectURL(compressed)
            });
        } else if (uploadAcceptsPdf && file.type === 'application/pdf') {
            selectedFiles.push({ file: file, url: null });
        }
    }
    renderEvidencePreview();
    document.getElementById('photoInput').value = '';
});

function renderEvidencePreview() {
    const container = document.getElementById('previewContainer');
    const wrapper = document.getElementById('previewWrapper');
    const countLabel = document.getElementById('photoCount');
    container.innerHTML = '';

    if (selectedFiles.length === 0) {
        wrapper.classList.add('hidden');
        return;
    }

    wrapper.classList.remove('hidden');
    countLabel.innerText = selectedFiles.length;

    selectedFiles.forEach((item, index) => {
        const card = document.createElement('div');
        card.className = 'relative aspect-square rounded-xl overflow-hidden bg-slate-50 border border-slate-200';
        if (item.url) {
            card.innerHTML = `
                <img src="${item.url}" class="w-full h-full object-cover">
                <button type="button" onclick="removeEvidencePhoto(${index})" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/75 text-white text-xs font-black flex items-center justify-center transition">×</button>
                <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[9px] px-1.5 py-0.5 truncate font-medium">${formatFileSize(item.file.size)}</div>
            `;
        } else {
            card.innerHTML = `
                <div class="w-full h-full flex flex-col items-center justify-center gap-1 p-1 text-center">
                    <i class="fa-solid fa-file-pdf text-red-500 text-xl"></i>
                    <p class="text-[8px] font-bold text-slate-500 truncate w-full">${item.file.name}</p>
                </div>
                <button type="button" onclick="removeEvidencePhoto(${index})" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/75 text-white text-xs font-black flex items-center justify-center transition">×</button>
            `;
        }
        container.appendChild(card);
    });
}

function removeEvidencePhoto(index) {
    if (selectedFiles[index] && selectedFiles[index].url) URL.revokeObjectURL(selectedFiles[index].url);
    selectedFiles.splice(index, 1);
    renderEvidencePreview();
}

function clearAllPhotosAction() {
    selectedFiles.forEach(item => { if (item.url) URL.revokeObjectURL(item.url); });
    selectedFiles = [];
    renderEvidencePreview();
}

document.getElementById('clearAllPhotos').addEventListener('click', clearAllPhotosAction);

function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (selectedFiles.length === 0) {
        Swal.fire({ title: 'Pilih Eviden!', text: 'Mohon lampirkan minimal 1 foto/file eviden.', icon: 'warning', confirmButtonColor: '#1565D8', customClass: { popup: 'rounded-3xl' } });
        return;
    }

    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('stage', document.getElementById('upload_stage').value);
    formData.append('evidence_type', document.getElementById('upload_evidence_type').value);
    formData.append('latitude', document.getElementById('latitude').value);
    formData.append('longitude', document.getElementById('longitude').value);
    formData.append('description', document.getElementsByName('description')[0].value);

    selectedFiles.forEach(item => formData.append('photos[]', item.file));

    fetch(e.target.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (response.ok) {
            closeUploadModal();
            Swal.fire({ title: 'Berhasil Disimpan!', text: 'Eviden berhasil diperbarui.', icon: 'success', showConfirmButton: false, timer: 1500, timerProgressBar: true, customClass: { popup: 'rounded-3xl' } })
            .then(() => window.location.reload());
        } else {
            Swal.fire({ title: 'Gagal Memproses!', text: 'Terjadi kegagalan validasi atau gangguan sistem.', icon: 'error', confirmButtonColor: '#1565D8', customClass: { popup: 'rounded-3xl' } });
        }
    })
    .catch(() => {
        Swal.fire({ title: 'Gangguan Jaringan!', text: 'Gagal menghubungi server. Periksa kembali internet Anda.', icon: 'warning', confirmButtonColor: '#1565D8', customClass: { popup: 'rounded-3xl' } });
    });
});
</script>
@endsection
