@extends('layouts.waspang')

@section('content')

<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

    @php
        $evidences = $project->evidences ?? collect();

        $pengukuranItems = [
            [
                'number' => 1,
                'type' => 'otdr',
                'title' => 'Eviden OTDR',
                'desc' => 'Upload foto layar alat hasil pengukuran OTDR.',
            ],
            [
                'number' => 2,
                'type' => 'otdr_sor',
                'title' => 'File (.SOR)',
                'desc' => 'Upload file mentah berformat .sor dari alat ukur.',
            ],
            [
                'number' => 3,
                'type' => 'opm',
                'title' => 'Eviden OPM',
                'desc' => 'Upload foto hasil pengukuran OPM dan isi nama ODP pada catatan.',
            ],
            [
                'number' => 4,
                'type' => 'kedalaman',
                'title' => 'Eviden Kedalaman Galian',
                'desc' => 'Upload foto pengukuran kedalaman galian.',
            ],
            [
                'number' => 5,
                'type' => 'lainnya',
                'title' => 'Eviden Pengukuran Lainnya',
                'desc' => 'Review foto hasil pengukuran lainnya.',
            ],
        ];

        $pengukuranUploaded = 0;

        foreach ($pengukuranItems as $item) {
            $hasUpload = $evidences
                ->where('stage', 'pengukuran')
                ->where('evidence_type', $item['type'])
                ->count() > 0;

            if ($hasUpload) {
                $pengukuranUploaded++;
            }
        }

        $pengukuranTotal = count($pengukuranItems);
        $pengukuranUploadedComplete = $pengukuranUploaded == $pengukuranTotal;
    @endphp

   {{-- HEADER --}}
    @include('waspang.partials.stepper')

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

    {{-- STEP 3 LIST --}}
    <div class="px-4 mt-5">

        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Step 3 Pengukuran</h2>
                <p class="text-[11px] text-gray-500">Tap item untuk Upload eviden OTDR, OPM & Kedalaman Galian</p>
            </div>

            @if($pengukuranUploadedComplete)
                <span class="px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-[11px] font-bold">
                    Complete
                </span>
            @else
                <span class="px-2.5 py-1 rounded-lg bg-yellow-100 text-yellow-700 text-[11px] font-bold">
                    {{ $pengukuranUploaded }}/{{ $pengukuranTotal }}
                </span>
            @endif

        </div>

        <div class="space-y-3">

            @foreach($pengukuranItems as $item)

                @php
                    $photos = $evidences
                        ->where('stage', 'pengukuran')
                        ->where('evidence_type', $item['type'])
                        ->sortByDesc('created_at');

                    // Logika Status Group yang Konsisten
                    $status = null;
                    if ($photos->count() > 0) {
                        if ($photos->where('status', 'rejected')->count() > 0) {
                            $status = 'rejected';
                        } elseif ($photos->where('status', 'pending')->count() > 0) {
                            $status = 'pending';
                        } else {
                            $status = 'approved';
                        }
                    }

                    $isUploaded = $photos->count() > 0;
                    $rejectedPhoto = $photos->where('status', 'rejected')->sortByDesc('created_at')->first();
                    $reviewNote = optional($rejectedPhoto)->review_note;
                @endphp

                <div x-data="{ open: {{ $status == 'rejected' ? 'true' : 'false' }} }"
                     class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs">

                    {{-- HEADER CARD --}}
                    <button type="button"
                            @click="open = !open"
                            class="w-full p-4 flex items-center justify-between gap-3 text-left">

                        <div class="flex items-center gap-3 min-w-0">

                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold shrink-0
                                {{ $status == 'rejected' ? 'bg-red-50 text-red-600' : ($isUploaded ? 'bg-green-50 text-green-600' : 'bg-gray-50 text-gray-400') }}">
                                {{ $status == 'rejected' ? '!' : ($isUploaded ? '✓' : $item['number']) }}
                            </div>

                            <div class="text-left min-w-0">

                                <h3 class="text-sm font-bold text-gray-900 tracking-tight">
                                    {{ $item['title'] }}
                                </h3>

                                <p class="text-xs text-gray-500 truncate mt-0.5">
                                    {{ $photos->count() }} {{ $item['type'] == 'otdr_sor' ? 'file' : 'foto' }}
                                </p>

                            </div>

                        </div>

                        <div class="flex items-center gap-2 shrink-0">

                            <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                                {{ !$status ? 'bg-gray-100 text-gray-500' : '' }}
                                {{ $status == 'approved' ? 'bg-green-100 text-green-700' : '' }}
                                {{ $status == 'pending' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $status == 'rejected' ? 'bg-red-100 text-red-700' : '' }}">
                                {{ $status ?? 'Belum' }}
                            </span>

                            <i class="fa-solid text-[10px] text-gray-400 transition-transform" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>

                        </div>

                    </button>

                    {{-- DETAIL CARD --}}
                    <div x-show="open"
                         x-transition
                         x-cloak
                         class="border-t border-gray-50 bg-gray-50/30 p-4 space-y-3">

                        <p class="text-xs text-gray-500 mb-3">
                            {{ $item['desc'] }}
                        </p>

                        @if($status == 'rejected')
                            <div class="rounded-xl border border-red-100 bg-red-50/50 p-3 text-xs text-red-700 leading-relaxed flex items-start gap-2">
                                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                                <div>
                                    <p class="font-bold mb-0.5">Terdapat Eviden Ditolak</p>
                                    <p>Ketuk pada item/foto bergaris merah di bawah untuk mengunggah ulang (replace) sesuai instruksi Admin.</p>
                                </div>
                            </div>
                        @endif

                        @include('waspang.partials.revision-history', [
                            'histories' => $revisionHistories[$item['type']] ?? collect()
                        ])

                        @if($photos->count() > 0)
                            <div class="grid grid-cols-3 gap-2 mb-3">
                                @foreach($photos as $photo)
                                    <div class="relative aspect-square rounded-xl overflow-hidden bg-gray-100 group flex items-center justify-center transition-all 
                                        {{ $photo->status == 'rejected' ? 'border-2 border-red-500 ring-2 ring-red-200' : 'border border-gray-200' }}">

                                        {{-- INDIKATOR ID FOTO (PERMANEN) --}}
                                        <div class="absolute top-1 left-1 bg-black/60 text-white text-[9px] font-black px-1.5 py-0.5 rounded flex items-center gap-1 z-20 backdrop-blur-sm">
                                            @if($photo->status == 'rejected')
                                                <i class="fa-solid fa-circle-exclamation text-red-400"></i>
                                            @elseif($photo->status == 'approved')
                                                <i class="fa-solid fa-check-circle text-green-400"></i>
                                            @endif
                                            ID-{{ $photo->id_evidence }}
                                        </div>

                                        {{-- JIKA INI ADALAH FILE SOR --}}
                                        @if($item['type'] == 'otdr_sor' || str_ends_with(strtolower($photo->file_path), '.sor'))
                                            <div class="flex flex-col items-center justify-center p-2 w-full h-full {{ $photo->status == 'rejected' ? 'bg-red-50 opacity-80' : 'bg-indigo-50' }}">
                                                <div class="w-10 h-10 rounded-lg flex items-center justify-center font-black mb-1 
                                                    {{ $photo->status == 'rejected' ? 'bg-red-200 text-red-700' : 'bg-indigo-200 text-indigo-700' }}">
                                                    SOR
                                                </div>
                                                <p class="text-[8px] font-bold text-gray-600 truncate w-full text-center mt-1 px-1" title="{{ basename($photo->file_path) }}">
                                                    {{ basename($photo->file_path) }}
                                                </p>
                                            </div>
                                        @else
                                            {{-- JIKA INI ADALAH FOTO NORMAL --}}
                                            <img src="{{ asset('storage/' . $photo->file_path) }}" 
                                                 class="w-full h-full object-cover {{ $photo->status == 'rejected' ? 'opacity-80 grayscale-[20%]' : '' }}">
                                        @endif

                                        {{-- JIKA STATUS REJECTED, TAMPILKAN NOTE & FORM REPLACE --}}
                                        @if($photo->status == 'rejected')
                                            @if(!empty($photo->review_note))
                                                <div class="absolute bottom-0 left-0 right-0 bg-red-600/95 text-white text-[9px] p-1.5 text-center font-bold z-20 backdrop-blur-sm leading-tight border-t border-red-500 line-clamp-2" title="{{ $photo->review_note }}">
                                                    {{ $photo->review_note }}
                                                </div>
                                            @endif

                                            {{-- OVERLAY GANTI FOTO / FILE --}}
                                            <form method="POST" action="{{ route('waspang.evidence.replace', $photo->id_evidence) }}" enctype="multipart/form-data" 
                                                  class="absolute inset-0 z-30 flex items-center justify-center bg-black/60 opacity-0 hover:opacity-100 transition-opacity duration-200">
                                                @csrf
                                                <label class="cursor-pointer bg-blue-600 text-white text-[10px] font-black px-3 py-2 rounded-xl shadow-lg hover:bg-blue-700 transition flex flex-col items-center gap-1">
                                                    <i class="fa-solid fa-camera-rotate text-sm"></i>
                                                    Upload Ulang
                                                    <input type="file" name="file" class="hidden" onchange="this.form.submit()" accept="{{ $item['type'] == 'otdr_sor' ? '.sor' : 'image/*' }}">
                                                </label>
                                            </form>
                                        @endif

                                        {{-- TOMBOL DELETE --}}
                                        @if($photo->status != 'approved')
                                            <form method="POST" action="{{ route('waspang.evidence.delete', $photo->id_evidence) }}" class="absolute top-1 right-1 z-20">
                                                @csrf @method('DELETE')
                                                <button class="w-5 h-5 rounded-full bg-black/70 hover:bg-red-600 text-white text-xs flex items-center justify-center font-bold backdrop-blur-sm transition">
                                                    ×
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-500 mb-3">
                                Belum ada eviden.
                            </p>
                        @endif

                        <button type="button"
                                onclick="openUploadModal('{{ $item['type'] }}', '{{ addslashes($item['title']) }}')"
                                class="h-9 w-full rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold transition shadow-xs">
                            <i class="fa-solid fa-camera mr-1"></i> Upload Tambahan
                        </button>

                    </div>

                </div>

            @endforeach

        </div>

        @if(!$pengukuranUploadedComplete)
            <div class="mt-3 rounded-2xl bg-yellow-50 border border-yellow-200 p-3">
                <p class="text-xs text-yellow-800 leading-relaxed">
                    Upload eviden <strong>OTDR</strong>, <strong>OPM</strong>,
                    <strong>Kedalaman Galian</strong> dan <strong>Pengukuran Lainnya</strong> jika ada.
                </p>
            </div>
        @endif

    </div>

    {{-- NEXT BUTTON --}}
    <div class="px-4 mt-5">
        <a href="{{ route('waspang.projects.finishing', $project->id_project) }}"
           class="h-11 w-full rounded-2xl bg-blue-700 text-white inline-flex items-center justify-center text-sm font-bold shadow-sm hover:bg-blue-800 transition">
            Next Step 4 - Finishing <i class="fa-solid fa-chevron-right ml-2 text-xs"></i>
        </a>
    </div>

    {{-- MODAL UPLOAD OVERLAY --}}
    <div id="uploadModal" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs animate-fade-in">
        <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            <div class="bg-blue-700 text-white px-5 py-4 flex items-start justify-between shrink-0">
                <div>
                    <h2 id="uploadTitle" class="text-lg font-black tracking-tight">
                        Upload Eviden Pengukuran
                    </h2>
                    <p id="selectedEvidenceName" class="text-xs text-blue-100 mt-1 font-medium break-all line-clamp-1">
                        Jenis dokumen/eviden pengukuran
                    </p>
                </div>

                <button type="button"
                        onclick="closeUploadModal()"
                        class="w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 text-white font-black text-lg flex items-center justify-center transition">
                    ×
                </button>
            </div>

            <form id="uploadForm" method="POST" action="{{ route('waspang.evidence.upload', $project->id_project) }}" class="flex flex-col min-h-0 overflow-y-auto p-5 space-y-4">
                @csrf
                <input type="hidden" name="stage" value="pengukuran">
                <input type="hidden" name="evidence_type" id="upload_evidence_type">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                <div id="opmNoteInfo" class="hidden rounded-xl bg-amber-50 border border-amber-200 text-amber-800 p-3 text-xs leading-snug shrink-0 animate-fade-in">
                    <span class="font-bold block uppercase tracking-wider text-[10px] text-amber-600 mb-0.5">⚠️ Perhatian Khusus OPM:</span>
                    Nama ODP wajib diisi pada catatan. Contoh format: <span class="font-mono font-bold">ODP-BDG-FAT-001</span>.
                </div>

                <div class="text-xs">
                    <label class="text-xs font-black text-gray-600 block mb-1.5">
                        Pilih Eviden Pengukuran
                    </label>

                    <label class="flex flex-col items-center justify-center w-full min-h-[125px] border-2 border-dashed border-blue-300 rounded-2xl bg-blue-50/40 cursor-pointer hover:bg-blue-50 transition p-4">
                        <div class="text-center">
                            <div class="mx-auto w-11 h-11 rounded-xl bg-blue-700 text-white flex items-center justify-center text-xl font-black shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-camera-icon lucide-camera">
                                    <path d="M13.997 4a2 2 0 0 1 1.76 1.05l.486.9A2 2 0 0 0 18.003 7H20a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h1.997a2 2 0 0 0 1.759-1.048l.489-.904A2 2 0 0 1 10.004 4z"/><circle cx="12" cy="13" r="3"/>
                                </svg>
                            </div>

                            <p class="mt-2.5 text-xs font-black text-blue-800">
                                Pilih Eviden / File
                            </p>

                            <p class="text-[10px] text-gray-400 mt-0.5">
                                JPG, PNG, WEBP, SOR
                            </p>
                        </div>

                        <input type="file" id="photoInput" accept="image/*,.sor" multiple class="hidden">
                    </label>

                    <div id="previewWrapper" class="mt-3 hidden animate-fade-in">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">
                                Preview Upload (<span id="photoCount">0</span>)
                            </p>

                            <button type="button" id="clearAllPhotos" class="text-[11px] font-bold text-red-600 hover:text-red-700 transition">
                                Hapus Semua
                            </button>
                        </div>

                        <div id="previewContainer" class="grid grid-cols-3 gap-2"></div>
                    </div>
                </div>

                <div class="text-xs">
                    <label class="text-xs font-black text-gray-600 block">
                        Catatan / Detail Keterangan
                    </label>

                    <textarea name="description" id="descriptionInput" rows="3" placeholder="Catatan opsional..." class="mt-1.5 w-full rounded-2xl border border-gray-300 px-3 py-2 text-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none transition resize-none"></textarea>
                </div>

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
window.selectedFiles = [];
window.currentUploadType = 'image';

window.openUploadModal = function(type, title) {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');

    document.getElementById('upload_evidence_type').value = type;
    document.getElementById('uploadTitle').innerText = title;
    document.getElementById('selectedEvidenceName').innerText = title;

    const descriptionInput = document.getElementById('descriptionInput');
    const opmNoteInfo = document.getElementById('opmNoteInfo');
    const photoInput = document.getElementById('photoInput');

    descriptionInput.value = '';

    if (type === 'opm') {
        descriptionInput.placeholder = 'Isi nama ODP, contoh: ODP-BDG-FAT-001 *wajib';
        opmNoteInfo.classList.remove('hidden');
    } else {
        descriptionInput.placeholder = 'Catatan opsional...';
        opmNoteInfo.classList.add('hidden');
    }

    if (type === 'otdr_sor') {
        photoInput.setAttribute('accept', '.sor');
        window.currentUploadType = 'sor';
    } else {
        photoInput.setAttribute('accept', 'image/*');
        window.currentUploadType = 'image';
    }

    clearAllPhotosAction();

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
        }, function(err) {}, { enableHighAccuracy: true });
    }
}

window.closeUploadModal = function() {
    document.getElementById('uploadModal').classList.add('hidden');
    document.getElementById('uploadModal').classList.remove('flex');
}

const photoInputElement = document.getElementById('photoInput');
if (photoInputElement) {
    photoInputElement.onchange = async function(e) {
        const files = Array.from(e.target.files);
        
        for (const file of files) {
            if (window.currentUploadType === 'sor') {
                if (!file.name.toLowerCase().endsWith('.sor')) {
                    Swal.fire('Format Salah', 'Harap pilih file berekstensi .sor', 'error');
                    continue;
                }
                window.selectedFiles.push({ file: file, url: null, is_sor: true, name: file.name });
            } else {
                if (!file.type.startsWith('image/')) continue;
                const compressed = await window.compressImage(file, 1280, 0.75);
                window.selectedFiles.push({ file: compressed, url: URL.createObjectURL(compressed), is_sor: false, name: file.name });
            }
        }
        
        renderEvidencePreview();
        document.getElementById('photoInput').value = '';
    };
}

window.renderEvidencePreview = function() {
    const container = document.getElementById('previewContainer');
    const wrapper = document.getElementById('previewWrapper');
    const countLabel = document.getElementById('photoCount');
    container.innerHTML = '';

    if (window.selectedFiles.length === 0) {
        wrapper.classList.add('hidden');
        return;
    }

    wrapper.classList.remove('hidden');
    countLabel.innerText = window.selectedFiles.length;

    window.selectedFiles.forEach((item, index) => {
        const card = document.createElement('div');
        card.className = 'relative aspect-square rounded-xl overflow-hidden bg-gray-50 border border-gray-200 shadow-sm flex items-center justify-center';
        
        if (item.is_sor) {
            card.innerHTML = `
                <div class="flex flex-col items-center p-2 text-center">
                    <div class="w-8 h-8 bg-indigo-100 text-indigo-700 rounded-lg flex items-center justify-center font-black mb-1 text-xs">SOR</div>
                    <p class="text-[8px] font-bold text-gray-600 truncate w-full px-1">${item.name}</p>
                </div>
                <button type="button" onclick="removeEvidencePhoto(${index})" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/75 text-white text-xs font-black flex items-center justify-center transition hover:bg-black z-10">×</button>
            `;
        } else {
            card.innerHTML = `
                <img src="${item.url}" class="w-full h-full object-cover">
                <button type="button" onclick="removeEvidencePhoto(${index})" class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/75 text-white text-xs font-black flex items-center justify-center transition hover:bg-black">×</button>
            `;
        }
        container.appendChild(card);
    });
}

window.removeEvidencePhoto = function(index) {
    if (window.selectedFiles[index] && !window.selectedFiles[index].is_sor) {
        URL.revokeObjectURL(window.selectedFiles[index].url);
    }
    window.selectedFiles.splice(index, 1);
    renderEvidencePreview();
}

window.clearAllPhotosAction = function() {
    window.selectedFiles.forEach(item => {
        if (!item.is_sor) URL.revokeObjectURL(item.url);
    });
    window.selectedFiles = [];
    renderEvidencePreview();
}

const btnClear = document.getElementById('clearAllPhotos');
if(btnClear) {
    btnClear.onclick = clearAllPhotosAction;
}

window.compressImage = function(file, maxWidth = 1280, quality = 0.75) {
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

const uploadFormElement = document.getElementById('uploadForm');
if (uploadFormElement) {
    uploadFormElement.onsubmit = function(e) {
        e.preventDefault();

        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Memproses...';

        const evidenceType = document.getElementById('upload_evidence_type').value;
        const description = document.getElementById('descriptionInput').value.trim();

        if (window.selectedFiles.length === 0) {
            Swal.fire({ title: 'Pilih File/Foto!', text: 'Mohon lampirkan minimal 1 foto/file.', icon: 'warning', confirmButtonColor: '#1D4ED8' });
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            return;
        }

        if (evidenceType === 'opm' && description === '') {
            Swal.fire({ title: 'Catatan Wajib!', text: 'Nama ODP wajib ditulis untuk pengukuran OPM.', icon: 'error', confirmButtonColor: '#1D4ED8' });
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
            return;
        }

        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        formData.append('stage', 'pengukuran');
        formData.append('evidence_type', evidenceType);
        formData.append('latitude', document.getElementById('latitude').value);
        formData.append('longitude', document.getElementById('longitude').value);
        formData.append('description', description);

        window.selectedFiles.forEach(item => {
            formData.append('photos[]', item.file, item.name); 
        });

        fetch(e.target.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (response.ok) {
                closeUploadModal();
                Swal.fire({ title: 'Berhasil Disimpan!', text: 'Eviden berhasil diupload.', icon: 'success', showConfirmButton: false, timer: 1500 })
                .then(() => window.location.reload());
            } else {
                Swal.fire({ title: 'Gagal Memproses!', text: 'Terjadi kesalahan sistem di server.', icon: 'error', confirmButtonColor: '#1D4ED8' });
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        })
        .catch(() => {
            Swal.fire({ title: 'Gangguan Jaringan!', text: 'Gagal menghubungi server.', icon: 'warning', confirmButtonColor: '#1D4ED8' });
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    };
}
</script>
@endsection