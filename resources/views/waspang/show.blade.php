@extends('layouts.waspang')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

    @php
        $evidences = $project->evidences ?? collect();

        $barangTibaPhotos = $evidences
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'barang_tiba')
            ->sortByDesc('created_at');

        $perizinanPhotos = $evidences
            ->where('stage', 'persiapan')
            ->where('evidence_type', 'perizinan')
            ->sortByDesc('created_at');

        $barangTibaStatus = optional($barangTibaPhotos->first())->status;
        $perizinanStatus = optional($perizinanPhotos->first())->status;

        $barangTibaRejected = $barangTibaPhotos->where('status', 'rejected')->sortByDesc('created_at')->first();
        $barangTibaReviewNote = optional($barangTibaRejected)->review_note;

        $perizinanRejected = $perizinanPhotos->where('status', 'rejected')->sortByDesc('created_at')->first();
        $perizinanReviewNote = optional($perizinanRejected)->review_note;

        $barangTibaUploaded = $barangTibaPhotos->count() > 0;
        $perizinanUploaded = $perizinanPhotos->count() > 0;
        $persiapanUploadedComplete = $barangTibaUploaded && $perizinanUploaded;

        $boqItems = $project->boqItems ?? collect();
        $materialBoqItems = $boqItems->filter(function ($boq) {
            return str_starts_with($boq->designator, 'M-');
        });

        $boqTotal = $materialBoqItems->count();
        $boqUploaded = $materialBoqItems->filter(function ($boq) use ($evidences) {
            return $evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->count() > 0;
        })->count();

        $instalasiComplete = $boqTotal > 0 && $boqUploaded >= $boqTotal;
        $pengukuranComplete = $instalasiComplete;

        $finishingUploaded = $materialBoqItems->filter(function ($boq) use ($evidences) {
            return $evidences->where('stage', 'finishing')->where('boq_item_id', $boq->id_boq)->count() > 0;
        })->count();
        $finishingComplete = $boqTotal > 0 && $finishingUploaded >= $boqTotal;
    @endphp

    {{-- HEADER --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem]">
        <div class="flex items-center gap-3">
            <a href="{{ route('waspang.inbox') }}" 
                class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 inline-flex items-center justify-center text-2xl font-medium transition active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left-icon lucide-chevron-left">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
            </a>
            <h1 class="text-xl font-bold">Step 1 - Persiapan</h1>
        </div>

        {{-- STEPPER PROGRESS APPS --}}
        <div class="relative px-2 mt-4">
            <div class="absolute top-4 left-10 right-10 h-1 bg-blue-300/60 rounded-full"></div>
            <div class="relative grid grid-cols-4 text-center">
                {{-- STEP 1 --}}
                <a href="{{ route('waspang.projects.show', $project->id_project) }}">
                    <div class="mx-auto w-8 h-8 rounded-full {{ $persiapanUploadedComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">
                        {{ $persiapanUploadedComplete ? '✓' : '1' }}
                    </div>
                    <p class="mt-2 text-xs font-bold text-white">Persiapan</p>
                </a>
                {{-- STEP 2 --}}
                @if($persiapanUploadedComplete)
                    <a href="{{ route('waspang.projects.instalasi', $project->id_project) }}">
                        <div class="mx-auto w-8 h-8 rounded-full {{ $instalasiComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">{{ $instalasiComplete ? '✓' : '2' }}</div>
                        <p class="mt-2 text-xs text-blue-100">Instalasi</p>
                    </a>
                @else
                    <div class="opacity-50">
                        <div class="mx-auto w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center text-sm font-bold">2</div>
                        <p class="mt-2 text-xs text-blue-200">Instalasi</p>
                    </div>
                @endif
                {{-- STEP 3 --}}
                @if($instalasiComplete)
                    <a href="{{ route('waspang.projects.pengukuran', $project->id_project) }}">
                        <div class="mx-auto w-8 h-8 rounded-full {{ $pengukuranComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">{{ $pengukuranComplete ? '✓' : '3' }}</div>
                        <p class="mt-2 text-xs text-blue-100">Pengukuran</p>
                    </a>
                @else
                    <div class="opacity-50">
                        <div class="mx-auto w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center text-sm font-bold">3</div>
                        <p class="mt-2 text-xs text-blue-200">Pengukuran</p>
                    </div>
                @endif
                {{-- STEP 4 --}}
                @if($pengukuranComplete)
                    <a href="{{ route('waspang.projects.finishing', $project->id_project) }}">
                        <div class="mx-auto w-8 h-8 rounded-full {{ $finishingComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">{{ $finishingComplete ? '✓' : '4' }}</div>
                        <p class="mt-2 text-xs text-blue-100">Finishing</p>
                    </a>
                @else
                    <div class="opacity-50">
                        <div class="mx-auto w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center text-sm font-bold">4</div>
                        <p class="mt-2 text-xs text-blue-200">Finishing</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- PROJECT INFO CARD --}}
    <div class="px-4 mt-4">
        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs">
            <div class="mb-3">
                <p class="text-xs text-gray-400 font-medium">Nama LOP</p>
                <p class="text-sm font-bold text-gray-900 break-words mt-0.5">{{ $project->project_name }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3 border-t border-gray-50 pt-3">
                <div>
                    <p class="text-xs text-gray-400 font-medium">STO</p>
                    <p class="text-xs font-bold text-gray-800 font-mono mt-0.5">{{ $project->lop?->sto ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 font-medium">Branch</p>
                    <p class="text-xs font-bold text-gray-800 mt-0.5">{{ $project->lop?->branch ?? '-' }}</p>
                </div>
                <div class="col-span-2 border-t border-gray-50 pt-2">
                    <p class="text-xs text-gray-400 font-medium">Mitra Pelaksana</p>
                    <p class="text-xs font-bold text-gray-800 mt-0.5 break-words">{{ $project->lop?->mitra_name ?? '-' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- MAIN CONTENT: CARD LIST EVIDEN PERSIAPAN --}}
    <div class="px-4 mt-6">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Step 1 Persiapan</h2>
                <p class="text-[11px] text-gray-500">Lengkapi item di bawah untuk lanjut instalasi</p>
            </div>
            @if($persiapanUploadedComplete)
                <span class="px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-[10px] font-bold">Complete</span>
            @else
                <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold">Pending Berkas</span>
            @endif
        </div>

        <div class="space-y-3">
            {{-- CARD 1: BARANG TIBA --}}
            <div x-data="{ open: false }" class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
                <button type="button" @click="open = !open" class="w-full p-4 flex items-center justify-between gap-3 text-left">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 {{ $barangTibaUploaded ? 'bg-green-50 text-green-600' : 'bg-gray-50 text-gray-400' }}">
                            {{ $barangTibaUploaded ? '✓' : '1' }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-gray-900 tracking-tight">Eviden Barang Tiba</h3>
                            <div class="text-[11px] text-gray-400 space-y-0.5 mt-0.5">
                                <p class="text-[10px] font-medium text-gray-400">{{ $barangTibaPhotos->count() }} Foto Bukti Terlampir</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                            {{ !$barangTibaStatus ? 'bg-gray-100 text-gray-500' : '' }}
                            {{ $barangTibaStatus == 'approved' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $barangTibaStatus == 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                            {{ $barangTibaStatus == 'rejected' ? 'bg-red-100 text-red-700' : '' }}">
                            {{ $barangTibaStatus ?? 'Belum' }}
                        </span>
                        <i class="fa-solid text-[10px] text-gray-400 transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                </button>

                <div x-show="open" x-transition class="border-t border-gray-50 bg-gray-50/30 p-4 space-y-3">
                    @if($barangTibaReviewNote)
                        <div class="rounded-xl border border-red-100 bg-red-50/50 p-3 text-xs text-red-700 leading-relaxed">
                            <p class="font-bold mb-0.5"><i class="fa-solid fa-circle-exclamation mr-1"></i> Catatan Revisi Admin:</p>
                            {{ $barangTibaReviewNote }}
                        </div>
                    @endif

                    @if($barangTibaPhotos->count() > 0)
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($barangTibaPhotos as $photo)
                                <div class="relative aspect-square rounded-xl overflow-hidden border border-gray-200 bg-gray-100">
                                    <img src="{{ asset('storage/' . $photo->file_path) }}" class="w-full h-full object-cover">
                                    @if($photo->status != 'approved')
                                        <form method="POST" action="{{ route('waspang.evidence.delete', $photo->id_evidence) }}" class="absolute top-1 right-1">
                                            @csrf @method('DELETE')
                                            <button class="w-5 h-5 rounded-full bg-black/70 text-white text-xs flex items-center justify-center font-bold">×</button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400 italic">Belum ada lampiran foto dokumen barang tiba.</p>
                    @endif

                    <button type="button" onclick="openUploadModal('barang_tiba', 'Eviden Barang Tiba')"
                            class="h-9 w-full rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold transition shadow-xs">
                        <i class="fa-solid fa-camera mr-1"></i> Upload / Update Eviden
                    </button>
                </div>
            </div>

            {{-- CARD 2: PERIZINAN --}}
            <div x-data="{ open: false }" class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
                <button type="button" @click="open = !open" class="w-full p-4 flex items-center justify-between gap-3 text-left">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 {{ $perizinanUploaded ? 'bg-green-50 text-green-600' : 'bg-gray-50 text-gray-400' }}">
                            {{ $perizinanUploaded ? '✓' : '2' }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-gray-900 tracking-tight">Eviden Perizinan</h3>
                            <div class="text-[11px] text-gray-400 space-y-0.5 mt-0.5">
                                <p class="text-[10px] font-medium text-gray-400">{{ $perizinanPhotos->count() }} Foto Bukti Terlampir</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                            {{ !$perizinanStatus ? 'bg-gray-100 text-gray-500' : '' }}
                            {{ $perizinanStatus == 'approved' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $perizinanStatus == 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                            {{ $perizinanStatus == 'rejected' ? 'bg-red-100 text-red-700' : '' }}">
                            {{ $perizinanStatus ?? 'Belum' }}
                        </span>
                        <i class="fa-solid text-[10px] text-gray-400 transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    </div>
                </button>

                <div x-show="open" x-transition class="border-t border-gray-50 bg-gray-50/30 p-4 space-y-3">
                    @if($perizinanReviewNote)
                        <div class="rounded-xl border border-red-100 bg-red-50/50 p-3 text-xs text-red-700 leading-relaxed">
                            <p class="font-bold mb-0.5"><i class="fa-solid fa-circle-exclamation mr-1"></i> Catatan Revisi Admin:</p>
                            {{ $perizinanReviewNote }}
                        </div>
                    @endif

                    @if($perizinanPhotos->count() > 0)
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($perizinanPhotos as $photo)
                                <div class="relative aspect-square rounded-xl overflow-hidden border border-gray-200 bg-gray-100">
                                    <img src="{{ asset('storage/' . $photo->file_path) }}" class="w-full h-full object-cover">
                                    @if($photo->status != 'approved')
                                        <form method="POST" action="{{ route('waspang.evidence.delete', $photo->id_evidence) }}" class="absolute top-1 right-1">
                                            @csrf @method('DELETE')
                                            <button class="w-5 h-5 rounded-full bg-black/70 text-white text-xs flex items-center justify-center font-bold">×</button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400 italic">Belum ada lampiran foto dokumen surat izin lapangan.</p>
                    @endif

                    <button type="button" onclick="openUploadModal('perizinan', 'Eviden Perizinan')"
                            class="h-9 w-full rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold transition shadow-xs">
                        <i class="fa-solid fa-camera mr-1"></i> Upload / Update Eviden
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- BOTTOM BUTTON NAVIGATION ACTION --}}
    <div class="px-4 mt-6">
        @if($persiapanUploadedComplete)
            <a href="{{ route('waspang.projects.instalasi', $project->id_project) }}" class="h-11 w-full rounded-xl bg-blue-700 text-white inline-flex items-center justify-center text-sm font-bold shadow-sm hover:bg-blue-800 transition">
                Next Step 2 - Instalasi <i class="fa-solid fa-chevron-right ml-2 text-xs"></i>
            </a>
        @else
            <button disabled class="h-11 w-full rounded-xl bg-gray-200 text-gray-400 inline-flex items-center justify-center text-sm font-bold cursor-not-allowed">
                Lengkapi Berkas Eviden Persiapan
            </button>
        @endif
    </div>

    {{-- MODAL UPLOAD OVERLAY (PRECISELY LIKE INSTALASI BLADE COPIED SHAPE) --}}
    <div id="uploadModal" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs animate-fade-in">
        <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            <div class="bg-blue-700 text-white px-5 py-4 flex items-start justify-between shrink-0">
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
                    <label class="text-xs font-black text-gray-600 block mb-1.5">Pilih/Ambil Eviden</label>
                    <label class="flex flex-col items-center justify-center w-full min-h-[125px] border-2 border-dashed border-blue-300 rounded-2xl bg-blue-50/40 cursor-pointer hover:bg-blue-50 transition p-4">
                        <div class="text-center">
                            <div class="mx-auto w-11 h-11 rounded-xl bg-blue-700 text-white flex items-center justify-center text-xl font-black shadow-sm">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                            <p class="mt-2.5 text-xs font-black text-blue-800">Pilih Eviden</p>
                            <p class="text-[10px] text-gray-400 mt-0.5">JPG, PNG, WEBP · Auto Compress</p>
                        </div>
                        <input type="file" id="photoInput" accept="image/*" multiple class="hidden">
                    </label>

                    {{-- PREVIEW MULTIPLE IMAGES CONTAINER --}}
                    <div id="previewWrapper" class="mt-3 hidden animate-fade-in">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Preview Foto (<span id="photoCount">0</span>)</p>
                            <button type="button" id="clearAllPhotos" class="text-[11px] font-bold text-red-600 hover:text-red-700 transition">Hapus Semua</button>
                        </div>
                        <div id="previewContainer" class="grid grid-cols-3 gap-2"></div>
                    </div>
                </div>

                <div class="text-xs">
                    <label class="text-xs font-black text-gray-600 block">Catatan Tambahan Progress <span class="text-gray-400">(Opsional)</span></label>
                    <textarea name="description" rows="3" placeholder="Tulis keterangan berkas tambahan jika ada..." class="mt-1.5 w-full rounded-2xl border border-gray-300 px-3 py-2 text-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none transition resize-none"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-2 pt-2 shrink-0">
                    <button type="button" onclick="closeUploadModal()" class="h-11 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-black transition">Batal</button>
                    <button type="submit" class="h-11 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-black shadow-md transition">Kirim Bukti</button>
                </div>
            </form>
        </div>
    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'inbox'])
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let selectedFiles = [];

function openUploadModal(type, title) {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');

    document.getElementById('upload_evidence_type').value = type;
    document.getElementById('selectedBoqName').innerText = title;

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
        if (!file.type.startsWith('image/')) continue;
        const compressed = await compressImage(file, 1280, 0.75);
        selectedFiles.push({
            file: compressed,
            url: URL.createObjectURL(compressed)
        });
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
        card.className = 'relative aspect-square rounded-xl overflow-hidden bg-gray-50 border border-gray-200';
        card.innerHTML = `
            <img src="${item.url}" class="w-full h-full object-cover">
            <button type="button" onclick="removeEvidencePhoto(${index})" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/75 text-white text-xs font-black flex items-center justify-center transition">×</button>
            <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[9px] px-1.5 py-0.5 truncate font-medium">${formatFileSize(item.file.size)}</div>
        `;
        container.appendChild(card);
    });
}

function removeEvidencePhoto(index) {
    if (selectedFiles[index]) URL.revokeObjectURL(selectedFiles[index].url);
    selectedFiles.splice(index, 1);
    renderEvidencePreview();
}

function clearAllPhotosAction() {
    selectedFiles.forEach(item => URL.revokeObjectURL(item.url));
    selectedFiles = [];
    renderEvidencePreview();
}

document.getElementById('clearAllPhotos').addEventListener('click', clearAllPhotosAction);

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
                    resolve(new File([blob], file.name.replace(/\.[^/.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', quality);
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
}

function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (selectedFiles.length === 0) {
        Swal.fire({ title: 'Pilih Foto!', text: 'Mohon lampirkan minimal 1 foto fisik berkas persiapan.', icon: 'warning', confirmButtonColor: '#1D4ED8', customClass: { popup: 'rounded-3xl' } });
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
            Swal.fire({ title: 'Berhasil Disimpan!', text: 'Eviden berkas persiapan berhasil diperbarui.', icon: 'success', showConfirmButton: false, timer: 1500, timerProgressBar: true, customClass: { popup: 'rounded-3xl' } })
            .then(() => window.location.reload());
        } else {
            Swal.fire({ title: 'Gagal Memproses!', text: 'Terjadi kegagalan validasi atau gangguan sistem.', icon: 'error', confirmButtonColor: '#1D4ED8', customClass: { popup: 'rounded-3xl' } });
        }
    })
    .catch(() => {
        Swal.fire({ title: 'Gangguan Jaringan!', text: 'Gagal menghubungi server. Periksa kembali internet Anda.', icon: 'warning', confirmButtonColor: '#1D4ED8', customClass: { popup: 'rounded-3xl' } });
    });
});
</script>
@endsection