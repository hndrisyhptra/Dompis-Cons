@extends('layouts.waspang') {{-- Sesuaikan dengan nama file layout utama mobile/waspang Anda --}}

@section('content')

<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

@php
    $evidences = $project->evidences ?? collect();
    $finishingBoqItems = $project->boqItems ?? collect();

    $materialBoqItems = ($project->boqItems ?? collect())->filter(function ($boq) {
    return optional($boq->designatorData)->type === 'material'
        || optional($boq->designatorDataByCode)->type === 'material';
        });

    $totalEvidence = $evidences->count();
    $approvedEvidence = $evidences->where('status', 'approved')->count();
    $pendingEvidence = $evidences->where('status', 'pending')->count();
    $rejectedEvidence = $evidences->where('status', 'rejected')->count();

    $readyForUt =
        $totalEvidence > 0 &&
        $pendingEvidence == 0 &&
        $rejectedEvidence == 0 &&
        $approvedEvidence == $totalEvidence;
@endphp

{{-- HEADER --}}
<div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem]">

    <div class="flex items-center gap-3">
            <a href="{{ route('waspang.projects.pengukuran', $project->id_project) }}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 inline-flex items-center justify-center text-2xl font-medium transition active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left-icon lucide-chevron-left">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
            </a>
            <h1 class="text-xl font-bold">Step 4 - Finishing</h1>
        </div>

    {{-- Stepper --}}
        <div class="relative px-2 mt-4">
            <div class="absolute top-4 left-10 right-10 h-1 bg-blue-300/60 rounded-full"></div>
            <div class="relative grid grid-cols-4 text-center">
                {{-- STEP 1 --}}
                <a href="{{ route('waspang.projects.show', $project->id_project) }}">
                    <div class="mx-auto w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">✓</div>
                    <p class="mt-2 text-xs text-blue-100">Persiapan</p>
                </a>
                {{-- STEP 2 --}}
                <a href="{{ route('waspang.projects.instalasi', $project->id_project) }}">
                    <div class="mx-auto w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">✓</div>
                    <p class="mt-2 text-xs text-blue-100">Instalasi</p>
                </a>
                {{-- STEP 3 --}}
                <a href="{{ route('waspang.projects.pengukuran', $project->id_project) }}">
                    <div class="mx-auto w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">✓</div>
                    <p class="mt-2 text-xs text-blue-100">Pengukuran</p>
                </a>
                {{-- STEP 4 (AKTIF) --}}
                <a href="{{ route('waspang.projects.finishing', $project->id_project) }}">
                    <div class="mx-auto w-8 h-8 rounded-full {{ $readyForUt ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">
                        {{ $readyForUt ? '✓' : '4' }}
                    </div>
                    <p class="mt-2 text-xs font-bold text-white">Finishing</p>
                </a>
            </div>
        </div>
</div>

{{-- Project Info --}}
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

{{-- UT STATUS --}}
<div class="px-4 mt-5">

    <div class="rounded-2xl border p-4
        {{ $readyForUt ? 'bg-green-50 border-green-200' : 'bg-yellow-50 border-yellow-200' }}">

        <div class="flex items-start gap-3">

            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold shrink-0
                {{ $readyForUt ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ $readyForUt ? '✓' : '!' }}
            </div>

            <div class="min-w-0">

                <h2 class="text-sm font-bold {{ $readyForUt ? 'text-green-800' : 'text-yellow-800' }}">
                    {{ $readyForUt ? 'Berkas Siap Uji Terima' : 'Belum Siap Uji Terima' }}
                </h2>

                <p class="text-xs leading-relaxed mt-1 {{ $readyForUt ? 'text-green-700' : 'text-yellow-800' }}">
                    @if($readyForUt)
                        Semua eviden sudah approved oleh admin. Project siap dibuatkan berkas Uji Terima.
                    @else
                        Tunggu approval admin untuk semua eviden. Jika ada reject, lakukan upload/update ulang.
                    @endif
                </p>

            </div>

        </div>

    </div>

</div>


{{-- APPROVAL SUMMARY --}}
<div class="px-4 mt-4">

    <div class="bg-white rounded-2xl border border-gray-200 p-4">

        <h3 class="text-sm font-bold text-gray-900 mb-3">
            Ringkasan Approval Eviden
        </h3>

        <div class="grid grid-cols-3 gap-2 text-center">

            <div class="rounded-xl bg-green-50 p-3">
                <p class="text-lg font-black text-green-700">
                    {{ $approvedEvidence }}
                </p>
                <p class="text-[11px] text-green-700 font-bold">
                    Approved
                </p>
            </div>

            <div class="rounded-xl bg-yellow-50 p-3">
                <p class="text-lg font-black text-yellow-700">
                    {{ $pendingEvidence }}
                </p>
                <p class="text-[11px] text-yellow-700 font-bold">
                    Pending
                </p>
            </div>

            <div class="rounded-xl bg-red-50 p-3">
                <p class="text-lg font-black text-red-700">
                    {{ $rejectedEvidence }}
                </p>
                <p class="text-[11px] text-red-700 font-bold">
                    Rejected
                </p>
            </div>

        </div>

    </div>

</div>


{{-- FINAL EVIDENCE PER MATERIAL ITEM --}}
<div class="px-4 mt-5">

     <div>
        <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Step 4 Finishing</h2>
        <p class="text-[11px] text-gray-500">Tap item untuk Upload Eviden Final</p>
    </div>

    <div class="space-y-3">

        @forelse($materialBoqItems as $boq)

            @php
                $instalasiPhotos = $evidences
                    ->where('stage', 'instalasi')
                    ->where('evidence_type', 'progress_boq')
                    ->where('boq_item_id', $boq->id_boq)
                    ->sortByDesc('created_at');

                $finalBoqPhotos = $evidences
                    ->where('stage', 'finishing')
                    ->where('evidence_type', 'final_boq')
                    ->where('boq_item_id', $boq->id_boq)
                    ->sortByDesc('created_at');

                $finalStatus = optional($finalBoqPhotos->first())->status;
            @endphp

            <div x-data="{ open: false }"
                 class="bg-white rounded-2xl border border-gray-200 overflow-hidden">

                <button type="button"
                        @click="open = !open"
                        class="w-full p-4 flex items-center justify-between gap-3">

                    <div class="text-left min-w-0">
                        <h3 class="text-sm font-bold truncate">
                            {{ $boq->item_name }}
                        </h3>

                        <p class="text-xs text-gray-500 truncate">
                            {{ $boq->designator ?? '-' }}
                            · Plan {{ $boq->quantity_plan }} {{ $boq->unit }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if($finalBoqPhotos->count() > 0)
                            <span class="px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-[11px] font-bold">
                                Final {{ $finalBoqPhotos->count() }}
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-lg bg-yellow-100 text-yellow-700 text-[11px] font-bold">
                                Belum
                            </span>
                        @endif

                        <span class="text-xs text-gray-400" x-text="open ? '▲' : '▼'"></span>
                    </div>

                </button>

                <div x-show="open"
                     x-transition
                     class="border-t border-gray-100 p-4 space-y-4">

                    <div>
                        <p class="text-xs font-bold text-gray-700 mb-2">
                            Eviden Instalasi Existing
                        </p>

                        @if($instalasiPhotos->count() > 0)
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($instalasiPhotos as $photo)
                                    <a href="{{ asset('storage/' . $photo->file_path) }}"
                                       target="_blank"
                                       class="aspect-square rounded-xl overflow-hidden border border-gray-200 bg-gray-100">
                                        <img src="{{ asset('storage/' . $photo->file_path) }}"
                                             class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-500">
                                Belum ada eviden instalasi untuk item ini.
                            </p>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-bold text-gray-700 mb-2">
                            Eviden Final
                        </p>

                        @if($finalBoqPhotos->count() > 0)
                            <div class="grid grid-cols-3 gap-2 mb-3">
                                @foreach($finalBoqPhotos as $photo)
                                    <div class="relative aspect-square rounded-xl overflow-hidden border border-gray-200 bg-gray-100">
                                        <img src="{{ asset('storage/' . $photo->file_path) }}"
                                             class="w-full h-full object-cover">

                                        @if($photo->status != 'approved')
                                            <form method="POST"
                                                  action="{{ route('waspang.evidence.delete', $photo->id_evidence) }}"
                                                  class="absolute top-1 right-1">
                                                @csrf
                                                @method('DELETE')

                                                <button class="w-6 h-6 rounded-full bg-black/70 text-white text-xs">
                                                    ×
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-500 mb-3">
                                Belum ada eviden final.
                            </p>
                        @endif

                        <button type="button" onclick="openUploadModal('{{ $boq->id_boq }}', @js($boq->item_name), '{{ $boq->designator }}', '{{ $boq->quantity_plan }}', '{{ $boq->unit }}', '{{ $boq->quantity_actual ?? 0 }}')"
                         class="h-9 w-full rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold transition shadow-xs">
                            + Upload Eviden Final
                        </button>
                    </div>

                </div>

            </div>

        @empty

            <div class="bg-white rounded-2xl border border-gray-200 p-4 text-center text-xs text-gray-500">
                Tidak ada item material pada BOQ.
            </div>

        @endforelse

    </div>

</div>


{{-- FINAL ACTION --}}
<div class="px-4 mt-5">

    @if($readyForUt)

        {{-- Mengarah ke halaman review komparasi Plan vs Actual --}}
        <a href="{{ route('waspang.projects.review_final', $project->id_project) }}"
           class="h-11 w-full rounded-2xl bg-blue-700 hover:bg-blue-800 text-white inline-flex items-center justify-center text-sm font-black transition shadow-md">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-check mr-2">
                <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/>
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                <path d="m9 14 2 2 4-4"/>
            </svg>
            Review BOQ Final
        </a>

    @else

        <button disabled
                class="h-11 w-full rounded-2xl bg-gray-300 text-gray-500 inline-flex items-center justify-center text-sm font-bold cursor-not-allowed">
            Menunggu Semua Eviden Approved
        </button>

    @endif

</div>

{{-- MODAL UPLOAD OVERLAY (SERAGAM APPS VIEW) --}}
    <div id="uploadModal" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs animate-fade-in">
        <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            {{-- HEADER MODAL (WARNA BIRU MODERN) --}}
            <div class="bg-blue-700 text-white px-5 py-4 flex items-start justify-between shrink-0">
                <div>
                    <h2 class="text-lg font-black tracking-tight">
                        Upload Eviden Final
                    </h2>
                    <p id="selectedBoqName" class="text-xs text-blue-100 mt-1 font-medium break-all line-clamp-1">
                        Nama komponen item BOQ
                    </p>
                </div>

                <button type="button"
                        onclick="closeUploadModal()"
                        class="w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 text-white font-black text-lg flex items-center justify-center transition">
                    ×
                </button>
            </div>

            {{-- FORM SUBMIT AJAX --}}
            <form id="uploadForm" method="POST" action="{{ route('waspang.evidence.upload', $project->id_project) }}" class="flex flex-col min-h-0 overflow-y-auto p-5 space-y-4">
                @csrf
                <input type="hidden" name="stage" value="finishing">
                <input type="hidden" name="evidence_type" id="evidence_type" value="final_boq">
                <input type="hidden" name="boq_item_id" id="boq_item_id">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                {{-- INFORMASI TARGET TARGET PLAN & ACTUAL (BOX INFO ADJUSTED) --}}
                <div id="selectedBoqBox" class="hidden grid grid-cols-3 gap-2 bg-gray-50 p-3 rounded-2xl border border-gray-100 text-xs shrink-0">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Designator</p>
                        <p id="selectedBoqDesignator" class="text-xs font-black text-gray-900 mt-0.5">-</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wide">Target Plan</p>
                        <p class="text-xs font-black text-blue-700 mt-0.5">
                            <span id="selectedBoqPlan">0</span> <span class="selectedBoqUnit text-[10px] font-normal text-blue-400"></span>
                        </p>
                    </div>
                    {{-- TAMBAHAN KOLOM ACTUAL PROGRESS --}}
                    <div>
                        <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-wide">Qty Actual</p>
                        <p class="text-xs font-black text-emerald-700 mt-0.5">
                            <span id="selectedBoqActual">0</span> <span class="selectedBoqUnit text-[10px] font-normal text-emerald-400"></span>
                        </p>
                    </div>
                </div>

                {{-- AREA DROPZONE SELECT/UPLOAD FOTO --}}
                <div class="text-xs">
                    <label class="text-xs font-black text-gray-600 block mb-1.5">
                        Pilih/Ambil Eviden Final
                    </label>

                    <label class="flex flex-col items-center justify-center w-full min-h-[125px] border-2 border-dashed border-blue-300 rounded-2xl bg-blue-50/40 cursor-pointer hover:bg-blue-50 transition p-4">
                        <div class="text-center">
                            <div class="mx-auto w-11 h-11 rounded-xl bg-blue-700 text-white flex items-center justify-center text-xl font-black shadow-sm">
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera-icon lucide-camera">
                                    <path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/>
                                </svg>
                            </div>

                            <p class="mt-2.5 text-xs font-black text-blue-800">
                                Pilih Eviden Final
                            </p>

                            <p class="text-[10px] text-gray-400 mt-0.5">
                                JPG, PNG, WEBP · Auto Compress
                            </p>
                        </div>

                        <input type="file" id="photoInput" accept="image/*" multiple class="hidden">
                    </label>

                    {{-- PREVIEW MULTIPLE FOTO --}}
                    <div id="previewWrapper" class="mt-3 hidden animate-fade-in">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">
                                Preview Foto (<span id="photoCount">0</span>)
                            </p>

                            <button type="button" id="clearAllPhotos" class="text-[11px] font-bold text-red-600 hover:text-red-700 transition">
                                Hapus Semua
                            </button>
                        </div>

                        <div id="previewContainer" class="grid grid-cols-3 gap-2"></div>
                    </div>
                </div>

                {{-- INPUT CATATAN --}}
                <div class="text-xs">
                    <label class="text-xs font-black text-gray-600 block">
                        Catatan Tambahan Finishing <span class="text-gray-400">(Opsional)</span>
                    </label>

                    <textarea name="description" rows="3" placeholder="Tulis catatan penutupan progress finishing..." class="mt-1.5 w-full rounded-2xl border border-gray-300 px-3 py-2 text-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none transition resize-none"></textarea>
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="grid grid-cols-2 gap-2 pt-2 shrink-0">
                    <button type="button" onclick="closeUploadModal()" class="h-11 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-black transition">
                        Batal
                    </button>

                    <button type="submit" class="h-11 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-black shadow-md transition">
                        Upload
                    </button>
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

// Tambahkan parameter quantityActual di dalam fungsi
function openUploadModal(boqId, boqName, designator, quantityPlan, unit, quantityActual) {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');

    // Suntik data teks pendukung acuan kerja
    document.getElementById('boq_item_id').value = boqId;
    document.getElementById('evidence_type').value = 'final_boq';
    document.getElementById('selectedBoqName').innerText = boqName;

    const infoBox = document.getElementById('selectedBoqBox');
    if (designator && quantityPlan) {
        infoBox.classList.remove('hidden');
        infoBox.classList.add('grid');
        document.getElementById('selectedBoqDesignator').innerText = designator;
        document.getElementById('selectedBoqPlan').innerText = quantityPlan;
        
        // Suntik data actual progress ke dalam modal
        document.getElementById('selectedBoqActual').innerText = quantityActual ?? 0;

        // Set satuan unit ke semua class .selectedBoqUnit
        document.querySelectorAll('.selectedBoqUnit').forEach(el => {
            el.innerText = unit ?? '';
        });
    } else {
        infoBox.classList.add('hidden');
        infoBox.classList.remove('grid');
    }

    clearAllPhotosAction();

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
        }, function(err) {
            console.warn("GPS Lock Bypass: ", err.message);
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
        card.className = 'relative aspect-square rounded-xl overflow-hidden bg-gray-50 border border-gray-200 shadow-xs';
        card.innerHTML = `
            <img src="${item.url}" class="w-full h-full object-cover">
            <button type="button" onclick="removeEvidencePhoto(${index})" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/75 text-white text-xs font-black flex items-center justify-center transition hover:bg-black">×</button>
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
        Swal.fire({ title: 'Pilih Foto!', text: 'Mohon lampirkan minimal 1 foto fisik sebagai bukti progress finishing.', icon: 'warning', confirmButtonColor: '#1D4ED8', customClass: { popup: 'rounded-3xl' } });
        return;
    }

    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('stage', 'finishing');
    formData.append('evidence_type', document.getElementById('evidence_type').value);
    formData.append('boq_item_id', document.getElementById('boq_item_id').value);
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
            Swal.fire({ title: 'Berhasil Disimpan!', text: 'Eviden progress finishing berhasil diperbarui.', icon: 'success', showConfirmButton: false, timer: 1500, timerProgressBar: true, customClass: { popup: 'rounded-3xl' } })
            .then(() => window.location.reload());
        } else {
            Swal.fire({ title: 'Gagal Memproses!', text: 'Terjadi kesalahan sistem atau kendala validasi data.', icon: 'error', confirmButtonColor: '#1D4ED8', customClass: { popup: 'rounded-3xl' } });
        }
    })
    .catch(() => {
        Swal.fire({ title: 'Gangguan Jaringan!', text: 'Gagal menghubungi server. Pastikan koneksi internet di lapangan stabil.', icon: 'warning', confirmButtonColor: '#1D4ED8', customClass: { popup: 'rounded-3xl' } });
    });
});
</script>
@endsection