@extends('layouts.waspang') {{-- Sesuaikan dengan nama file layout utama mobile/waspang Anda --}}

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

    @php
        $evidences = $project->evidences ?? collect();
        $boqItems = $project->boqItems ?? collect();
        $boqTotal = $boqItems->count();

        $boqUploaded = $boqItems->filter(function ($boq) use ($evidences) {
            return $evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->count() > 0;
        })->count();

        $instalasiUploadedComplete = $boqTotal > 0 && $boqUploaded == $boqTotal;
    @endphp

    {{-- HEADER --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem]">
        <div class="flex items-center gap-3">
            <a href="{{ route('waspang.projects.show', $project->id_project) }}"
                class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 inline-flex items-center justify-center text-2xl font-medium transition active:scale-95">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left-icon lucide-chevron-left">
                    <path d="m15 18-6-6 6-6"/>
                </svg>
            </a>
            <h1 class="text-xl font-bold">Step 2 - Instalasi</h1>
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
                    <div class="mx-auto w-8 h-8 rounded-full {{ $instalasiUploadedComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">
                        {{ $instalasiUploadedComplete ? '✓' : '2' }}
                    </div>
                    <p class="mt-2 text-xs font-bold text-white">Instalasi</p>
                </a>
                {{-- STEP 3 --}}
                @if($instalasiUploadedComplete)
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

    {{-- STEP 2 LIST --}}
    <div class="px-4 mt-6">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Step 2 Instalasi</h2>
                <p class="text-[11px] text-gray-500">Tap item untuk melihat foto/riwayat & upload</p>
            </div>
            @if($instalasiUploadedComplete)
                <span class="px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-[10px] font-bold">Complete</span>
            @else
                <span class="px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold">{{ $boqUploaded }}/{{ $boqTotal }} Item</span>
            @endif
        </div>

        <div class="space-y-3">
            @forelse($project->boqItems as $boq)
                @php
                    $photos = $evidences->where('stage', 'instalasi')->where('evidence_type', 'progress_boq')->where('boq_item_id', $boq->id_boq)->sortByDesc('created_at');
                    $firstPhoto = $photos->first();
                    $status = optional($firstPhoto)->status;
                    $isUploaded = $photos->count() > 0;
                    $rejectedPhoto = $photos->where('status', 'rejected')->sortByDesc('created_at')->first();
                    $reviewNote = optional($rejectedPhoto)->review_note;
                @endphp

                <div x-data="{ open: false }" class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">
                    {{-- CARD HEADER TRIGGER BUTTON --}}
                    <button type="button" @click="open = !open" class="w-full p-4 flex items-center justify-between gap-3 text-left">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold shrink-0 {{ $isUploaded ? 'bg-green-50 text-green-600' : 'bg-gray-50 text-gray-400' }}">
                                {{ $isUploaded ? '✓' : $loop->iteration }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm font-bold text-gray-900 tracking-tight">{{ $boq->designator }}</h3>
                                <div class="text-[11px] text-gray-400 space-y-0.5 mt-0.5">
                                    <p>Plan: <span class="font-semibold text-gray-700">{{ number_format($boq->quantity_plan, 0, ',', '.') }} {{ $boq->unit }}</span></p>
                                    
                                   @php
                                        $designatorUpper = strtoupper($boq->designator);
                                        $relationCategory = strtoupper(trim($boq->progress_category ?? ($boq->designatorData?->progress_category ?? '')));
                                        
                                        // Penanda warna: Biru untuk Kabel/Tiang (KPI Utama), Slate untuk item lainnya
                                        $isKpi = in_array($relationCategory, ['KABEL', 'TIANG']) || str_contains($designatorUpper, 'KABEL') || str_contains($designatorUpper, 'TIANG');
                                        
                                        $bgClass = $isKpi ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-400' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
                                    @endphp

                                    {{-- Sekarang semua jenis item tanpa terkecuali akan menampilkan nilai aktualnya di card luar --}}
                                    <p class="flex items-center gap-1">
                                        Aktual: 
                                        <span class="font-black px-1.5 py-0.5 rounded-md {{ $bgClass }}">
                                            {{ number_format($boq->quantity_actual ?? 0, 0, ',', '.') }} {{ $boq->unit }}
                                        </span>
                                    </p>
                                    
                                    <p class="text-[10px] font-medium text-gray-400">{{ $photos->count() }} Eviden</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                                {{ !$status ? 'bg-gray-100 text-gray-500' : '' }}
                                {{ $status == 'approved' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $status == 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $status == 'rejected' ? 'bg-red-100 text-red-700' : '' }}">
                                {{ $status ?? 'Belum' }}
                            </span>
                            <i class="fa-solid text-[10px] text-gray-400 transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </div>
                    </button>

                    {{-- CARD EXPAND AREA --}}
                    <div x-show="open" x-transition class="border-t border-gray-50 bg-gray-50/30 p-4 space-y-3">
                        @if($reviewNote)
                            <div class="rounded-xl border border-red-100 bg-red-50/50 p-3 text-xs text-red-700 leading-relaxed">
                                <p class="font-bold mb-0.5"><i class="fa-solid fa-circle-exclamation mr-1"></i> Catatan Revisi Admin:</p>
                                {{ $reviewNote }}
                            </div>
                        @endif

                        @include('waspang.partials.revision-history', ['histories' => $revisionHistories[$boq->id_boq] ?? collect()])

                        @if($photos->count() > 0)
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($photos as $photo)
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
                            <p class="text-xs text-gray-400 italic">Belum ada lampiran foto eviden.</p>
                        @endif

                        <button type="button" onclick="openUploadModal('{{ $boq->id_boq }}', @js($boq->item_name), '{{ $boq->quantity_plan }}', '{{ $boq->unit }}', '{{ $boq->quantity_actual ?? '' }}')"
                                class="h-9 w-full rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold transition shadow-xs">
                            <i class="fa-solid fa-camera mr-1"></i> Upload / Update Eviden
                        </button>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl border border-gray-200 p-6 text-center text-xs text-gray-400">Belum ada daftar item BOQ terpetakan.</div>
            @endforelse
        </div>
    </div>

    {{-- BOTTOM ACTION BUTTON --}}
    <div class="px-4 mt-6">
        @if($instalasiUploadedComplete)
            <a href="{{ route('waspang.projects.pengukuran', $project->id_project) }}" class="h-11 w-full rounded-xl bg-blue-700 text-white inline-flex items-center justify-center text-sm font-bold shadow-sm hover:bg-blue-800 transition">
                Next Step 3 - Pengukuran <i class="fa-solid fa-chevron-right ml-2 text-xs"></i>
            </a>
        @else
            <button disabled class="h-11 w-full rounded-xl bg-gray-200 text-gray-400 inline-flex items-center justify-center text-sm font-bold cursor-not-allowed">
                Lengkapi Seluruh Eviden Instalasi
            </button>
        @endif
    </div>

    {{-- MODAL UPLOAD OVERLAY --}}
    <div id="uploadModal" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs animate-fade-in">
        <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            {{-- HEADER MODAL (WARNA BIRU MODERN) --}}
            <div class="bg-blue-700 text-white px-5 py-4 flex items-start justify-between shrink-0">
                <div>
                    <h2 class="text-lg font-black tracking-tight">
                        Upload Eviden & Qty Actual
                    </h2>
                    <p id="selectedBoqName" class="text-xs text-blue-100 mt-1 font-medium break-all line-clamp-1">
                        item BOQ
                    </p>
                </div>

                <button type="button"
                        onclick="closeUploadModal()"
                        class="w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 text-white font-black text-lg flex items-center justify-center transition">
                    ×
                </button>
            </div>

            {{-- FORM SUBMIT --}}
            <form id="uploadForm" method="POST" action="{{ route('waspang.evidence.upload', $project->id_project) }}" class="flex flex-col min-h-0 overflow-y-auto p-5 space-y-4">
                @csrf
                <input type="hidden" id="boq_item_id">
                <input type="hidden" id="latitude">
                <input type="hidden" id="longitude">

                {{-- INFORMASI GRID TARGET DAN AKTUAL --}}
                <div class="grid grid-cols-3 gap-2 bg-gray-50 p-3 rounded-2xl border border-gray-100 text-xs shrink-0">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Target Plan</p>
                        <p class="text-sm font-black text-gray-900 mt-0.5"><span id="planQuantity">0</span> <span id="planUnit" class="text-[10px] font-normal text-gray-400"></span></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wide">Aktual Saat ini</p>
                        <p class="text-sm font-black text-blue-700 mt-0.5"><span id="currentActualQuantity">0</span> <span class="text-[10px] font-normal text-blue-400 span-unit"></span></p>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-500 block uppercase tracking-wide">Input Progress</label>
                        <input type="number" step="0.01" id="quantity_actual" placeholder="Isi Qty" class="mt-0.5 w-full h-8 rounded-lg border-gray-300 bg-white font-mono font-bold text-xs text-blue-600 focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none px-2">
                    </div>
                </div>

                {{-- AREA DROPZONE SELECT/UPLOAD FOTO (KEREN & MODERN) --}}
                <div class="text-xs">
                    <label class="text-xs font-black text-gray-600 block mb-1.5">
                        Pilih Eviden / Multiple Select
                    </label>

                    <label class="flex flex-col items-center justify-center w-full min-h-[125px] border-2 border-dashed border-blue-300 rounded-2xl bg-blue-50/40 cursor-pointer hover:bg-blue-50 transition p-4">
                        <div class="text-center">
                            <div class="mx-auto w-11 h-11 rounded-xl bg-blue-700 text-white flex items-center justify-center text-xl font-black shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera-icon lucide-camera">
                                    <path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/>
                                </svg>
                            </div>

                            <p class="mt-2.5 text-xs font-black text-blue-800">
                                Pilih Eviden Progress
                            </p>

                            <p class="text-[10px] text-gray-400 mt-0.5">
                                JPG, PNG, WEBP · Auto Compress
                            </p>
                        </div>

                        <input
                            type="file"
                            id="photoInput"
                            accept="image/*"
                            multiple
                            class="hidden">
                    </label>

                    {{-- PREVIEW MULTIPLE FOTO YANG AKAN DIUPLOAD --}}
                    <div id="previewWrapper" class="mt-3 hidden animate-fade-in">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">
                                Preview Foto (<span id="photoCount">0</span>)
                            </p>

                            <button type="button"
                                    id="clearAllPhotos"
                                    class="text-[11px] font-bold text-red-600 hover:text-red-700 transition">
                                Hapus Semua
                            </button>
                        </div>

                        <div id="previewContainer" class="grid grid-cols-3 gap-2">
                            {{-- Element preview item disuntikkan secara dinamis lewat Javascript --}}
                        </div>
                    </div>
                </div>

                {{-- INPUT CATATAN / DESKRIPSI --}}
                <div class="text-xs">
                    <label class="text-xs font-black text-gray-600 block">
                        Catatan Tambahan Progress <span class="text-gray-400">(Opsional)</span>
                    </label>

                    <textarea name="description"
                              rows="3"
                              placeholder="Contoh: Penarikan kabel span ke-4 selesai dilakukan..."
                              class="mt-1.5 w-full rounded-2xl border border-gray-300 px-3 py-2 text-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none transition resize-none"></textarea>
                </div>

                {{-- ACTION BUTTONS --}}
                <div class="grid grid-cols-2 gap-2 pt-2 shrink-0">
                    <button type="button"
                            onclick="closeUploadModal()"
                            class="h-11 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-black transition">
                        Batal
                    </button>

                    <button type="submit"
                            class="h-11 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-black shadow-md transition">
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
// Container State Global untuk menampung data file uploader secara independen
const issueUploaders = {};
let selectedFiles = []; // Khusus menampung array file eviden progress

/**
 * ==========================================
 * CONTROLLER UTAMA: MODAL UPLOAD EVIDEN
 * ==========================================
 */
function openUploadModal(boqId, boqName, quantityPlan, unit, quantityActual) {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');

    // Suntik data teks pendukung acuan kerja
    document.getElementById('boq_item_id').value = boqId;
    document.getElementById('selectedBoqName').innerText = boqName;
    document.getElementById('planQuantity').innerText = quantityPlan;
    document.getElementById('planUnit').innerText = unit;
    
    // Konversi float aman untuk indikator aktual saat ini
    document.getElementById('currentActualQuantity').innerText = quantityActual ? parseFloat(quantityActual) : '0';
    
    // Set satuan dinamis ke teks label
    document.querySelectorAll('.span-unit').forEach(span => span.innerText = unit);
    
    // Tampilkan nilai lama di form input jika sudah pernah diisi
    document.getElementById('quantity_actual').value = quantityActual ? quantityActual : '';

    // Reset list unggahan foto setiap kali modal baru dibuka
    clearAllPhotosAction();

    // Tarik metadata Geolocation koordinat waspang di lapangan
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
        }, function(error) {
            console.warn("Gagal mendapatkan koordinat GPS: ", error.message);
        }, { enableHighAccuracy: true });
    }
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    document.getElementById('uploadModal').classList.remove('flex');
}

/**
 * LOGIKA PREVIEW & RESET MULTIPLE FOTO EVIDEN
 */
document.getElementById('photoInput').addEventListener('change', async function(e) {
    const files = Array.from(e.target.files);
    
    for (const file of files) {
        if (!file.type.startsWith('image/')) continue;
        
        // Kompres gambar otomatis ke lebar maks 1280px dengan kualitas 75%
        const compressed = await compressImage(file, 1280, 0.75);
        selectedFiles.push({
            file: compressed,
            url: URL.createObjectURL(compressed)
        });
    }
    
    renderEvidencePreview();
    document.getElementById('photoInput').value = ''; // Reset pointer element
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
            <button type="button" onclick="removeEvidencePhoto(${index})" 
                    class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/75 text-white text-xs font-black flex items-center justify-center transition hover:bg-black">
                ×
            </button>
            <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[9px] px-1.5 py-0.5 truncate font-medium">
                ${formatFileSize(item.file.size)}
            </div>
        `;
        container.appendChild(card);
    });
}

function removeEvidencePhoto(index) {
    if (selectedFiles[index]) {
        URL.revokeObjectURL(selectedFiles[index].url);
    }
    selectedFiles.splice(index, 1);
    renderEvidencePreview();
}

function clearAllPhotosAction() {
    selectedFiles.forEach(item => URL.revokeObjectURL(item.url));
    selectedFiles = [];
    renderEvidencePreview();
}

document.getElementById('clearAllPhotos').addEventListener('click', clearAllPhotosAction);

/**
 * PROSES SUBMIT FETCH FORM EVIDEN & AKTUAL VIA AJAX
 */
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (selectedFiles.length === 0) {
        Swal.fire({ 
            title: 'Pilih Foto!', 
            text: 'Mohon lampirkan minimal 1 foto fisik sebagai bukti progress lapangan.', 
            icon: 'warning', 
            confirmButtonColor: '#1D4ED8', 
            customClass: { popup: 'rounded-3xl' } 
        });
        return;
    }

    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('stage', 'instalasi');
    formData.append('evidence_type', 'progress_boq');
    formData.append('boq_item_id', document.getElementById('boq_item_id').value);
    formData.append('latitude', document.getElementById('latitude').value);
    formData.append('longitude', document.getElementById('longitude').value);
    formData.append('description', document.getElementsByName('description')[0].value);
    
    const qtyActualValue = document.getElementById('quantity_actual').value;
    if (qtyActualValue !== '') {
        formData.append('quantity_actual', qtyActualValue);
    }

    selectedFiles.forEach((item) => {
        formData.append('photos[]', item.file);
    });

    fetch(e.target.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (response.ok) {
            closeUploadModal();
            Swal.fire({
                title: 'Berhasil Disimpan!',
                text: 'Eviden progress lapangan dan kuantitas aktual berhasil diperbarui.',
                icon: 'success',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
                customClass: { popup: 'rounded-3xl' }
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({ 
                title: 'Gagal Memproses!', 
                text: 'Terjadi kegagalan validasi atau status ENUM ditolak sistem.', 
                icon: 'error', 
                confirmButtonColor: '#1D4ED8', 
                customClass: { popup: 'rounded-3xl' } 
            });
        }
    })
    .catch(() => {
        Swal.fire({ 
            title: 'Gangguan Jaringan!', 
            text: 'Gagal menghubungi server. Periksa kembali koneksi internet di lapangan.', 
            icon: 'warning', 
            confirmButtonColor: '#1D4ED8', 
            customClass: { popup: 'rounded-3xl' } 
        });
    });
});

/**
 * ==========================================
 * UTILITIES GLOBAL ENGINE (IMAGE PROCESSING)
 * ==========================================
 */
async function compressImage(file, maxWidth = 1280, quality = 0.75) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;

                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }

                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    const compressedFile = new File(
                        [blob],
                        file.name.replace(/\.[^/.]+$/, '') + '.jpg',
                        { type: 'image/jpeg', lastModified: Date.now() }
                    );
                    resolve(compressedFile);
                }, 'image/jpeg', quality);
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    });
}

function formatFileSize(bytes) {
    if (bytes < 1024 * 1024) {
        return Math.round(bytes / 1024) + ' KB';
    }
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}
</script>
@endsection