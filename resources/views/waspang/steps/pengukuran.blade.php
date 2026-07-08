@extends('layouts.waspang')

@section('content')

<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

    @php
        $evidences = $project->evidences ?? collect();

        $pengukuranItems = [
            [
                'number' => 1,
                'type' => 'otdr',
                'title' => 'Pengukuran OTDR',
                'desc' => 'Upload foto hasil pengukuran OTDR.',
            ],
            [
                'number' => 2,
                'type' => 'opm',
                'title' => 'Pengukuran OPM',
                'desc' => 'Upload foto hasil pengukuran OPM dan isi nama ODP pada catatan.',
            ],
            [
                'number' => 3,
                'type' => 'kedalaman',
                'title' => 'Pengukuran Kedalaman Galian',
                'desc' => 'Upload foto pengukuran kedalaman galian.',
            ],
            [
                'number' => 4,
                'title' => 'Eviden Pengukuran Lainnya',
                'type' => 'lainnya',
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
    <div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem]">
        <div class="flex items-center gap-3">
            <a href="{{ route('waspang.projects.show', $project->id_project) }}" class="text-3xl leading-none">‹</a>
            <h1 class="text-xl font-bold">Step 3 - Pengukuran</h1>
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
                {{-- STEP 3 (AKTIF) --}}
                <a href="{{ route('waspang.projects.pengukuran', $project->id_project) }}">
                    <div class="mx-auto w-8 h-8 rounded-full {{ $pengukuranUploadedComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">
                        {{ $pengukuranUploadedComplete ? '✓' : '3' }}
                    </div>
                    <p class="mt-2 text-xs font-bold text-white">Pengukuran</p>
                </a>
                {{-- STEP 4 --}}
                @if($pengukuranUploadedComplete)
                    <a href="{{ route('waspang.projects.finishing', $project->id_project) }}">
                        <div class="mx-auto w-8 h-8 rounded-full {{ $finishingComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">
                            {{ $finishingComplete ? '✓' : '4' }}
                        </div>
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

                    $firstPhoto = $photos->first();
                    $status = optional($firstPhoto)->status;
                    $isUploaded = $photos->count() > 0;

                    $rejectedPhoto = $photos
                        ->where('status', 'rejected')
                        ->sortByDesc('created_at')
                        ->first();

                    $reviewNote = optional($rejectedPhoto)->review_note;
                @endphp

                <div x-data="{ open: false }"
                     class="bg-white rounded-2xl border border-gray-200 overflow-hidden">

                    {{-- HEADER CARD --}}
                    <button type="button"
                            @click="open = !open"
                            class="w-full p-4 flex items-center justify-between gap-3">

                        <div class="flex items-center gap-3 min-w-0">

                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold shrink-0
                                {{ $isUploaded ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $isUploaded ? '✓' : $item['number'] }}
                            </div>

                            <div class="text-left min-w-0">

                                <h3 class="text-sm font-bold truncate">
                                    {{ $item['title'] }}
                                </h3>

                                <p class="text-xs text-gray-500 truncate">
                                    {{ $photos->count() }} foto
                                    ·
                                    {{ $status ?? 'Belum upload' }}
                                </p>

                            </div>

                        </div>

                        <div class="flex items-center gap-2 shrink-0">

                            @if(!$status)
                                <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[11px] font-bold">
                                    Belum
                                </span>
                            @elseif($status == 'approved')
                                <span class="px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-[11px] font-bold">
                                    Approved
                                </span>
                            @elseif($status == 'pending')
                                <span class="px-2.5 py-1 rounded-lg bg-yellow-100 text-yellow-700 text-[11px] font-bold">
                                    Pending
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg bg-red-100 text-red-700 text-[11px] font-bold">
                                    Rejected
                                </span>
                            @endif

                            <span class="text-xs text-gray-400" x-text="open ? '▲' : '▼'"></span>

                        </div>

                    </button>

                    {{-- DETAIL CARD --}}
                    <div x-show="open"
                         x-transition
                         class="border-t border-gray-100 p-4">

                        <p class="text-xs text-gray-500 mb-3">
                            {{ $item['desc'] }}
                        </p>

                        @if($reviewNote)

                            <div class="mb-3 rounded-xl border border-red-200 bg-red-50 p-3">

                                <p class="text-[11px] font-bold text-red-700 mb-1">
                                    Catatan Revisi Admin
                                </p>

                                <p class="text-xs text-red-700 leading-relaxed">
                                    {{ $reviewNote }}
                                </p>

                            </div>

                        @endif

                        @include('waspang.partials.revision-history', [
                            'histories' => $revisionHistories[$item['type']] ?? collect()
                        ])

                        @if($photos->count() > 0)

                            <div class="grid grid-cols-3 gap-2 mb-3">

                                @foreach($photos as $photo)

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
                                Belum ada foto eviden.
                            </p>

                        @endif

                        <button type="button"
                                onclick="openUploadModal('{{ $item['type'] }}', '{{ addslashes($item['title']) }}')"
                                class="h-9 w-full rounded-xl bg-blue-700 text-white text-xs font-bold">
                            Upload / Update Eviden
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
               class="h-11 w-full rounded-2xl bg-blue-700 text-white inline-flex items-center justify-center text-sm font-bold">
                Next Step 4 - Finishing
            </a>

    </div>

    {{-- MODAL UPLOAD OVERLAY (SERAGAM APPS VIEW) --}}
    <div id="uploadModal" class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center backdrop-blur-xs animate-fade-in">
        <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            {{-- HEADER MODAL (WARNA BIRU MODERN) --}}
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

            {{-- FORM SUBMIT AJAX --}}
            <form id="uploadForm" method="POST" action="{{ route('waspang.evidence.upload', $project->id_project) }}" class="flex flex-col min-h-0 overflow-y-auto p-5 space-y-4">
                @csrf
                <input type="hidden" name="stage" value="pengukuran">
                <input type="hidden" name="evidence_type" id="upload_evidence_type">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                {{-- BANNER INFORMASI KHUSUS OPM --}}
                <div id="opmNoteInfo" class="hidden rounded-xl bg-amber-50 border border-amber-200 text-amber-800 p-3 text-xs leading-snug shrink-0 animate-fade-in">
                    <span class="font-bold block uppercase tracking-wider text-[10px] text-amber-600 mb-0.5">⚠️ Perhatian Khusus OPM:</span>
                    Nama ODP wajib diisi pada catatan. Contoh format: <span class="font-mono font-bold">ODP-BDG-FAT-001</span>.
                </div>

                {{-- AREA DROPZONE SELECT/UPLOAD FOTO --}}
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
                                Pilih Eviden
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
                        Catatan / Detail Keterangan
                    </label>

                    <textarea name="description" id="descriptionInput" rows="3" placeholder="Catatan opsional..." class="mt-1.5 w-full rounded-2xl border border-gray-300 px-3 py-2 text-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none transition resize-none"></textarea>
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

function openUploadModal(type, title) {
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');

    document.getElementById('upload_evidence_type').value = type;
    document.getElementById('uploadTitle').innerText = title;
    document.getElementById('selectedEvidenceName').innerText = title;

    const descriptionInput = document.getElementById('descriptionInput');
    const opmNoteInfo = document.getElementById('opmNoteInfo');

    descriptionInput.value = '';

    // Kondisi khusus untuk tipe pengukuran OPM
    if (type === 'opm') {
        descriptionInput.placeholder = 'Isi nama ODP, contoh: ODP-BDG-FAT-001 *wajib';
        opmNoteInfo.classList.remove('hidden');
    } else {
        descriptionInput.placeholder = 'Catatan opsional...';
        opmNoteInfo.classList.add('hidden');
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

    const evidenceType = document.getElementById('upload_evidence_type').value;
    const description = document.getElementById('descriptionInput').value.trim();

    if (selectedFiles.length === 0) {
        Swal.fire({ title: 'Pilih Foto!', text: 'Mohon lampirkan minimal 1 foto fisik bukti pengukuran.', icon: 'warning', confirmButtonColor: '#1D4ED8', customClass: { popup: 'rounded-3xl' } });
        return;
    }

    // Validasi wajib isi catatan khusus untuk tipe OPM
    if (evidenceType === 'opm' && description === '') {
        Swal.fire({ title: 'Catatan Wajib!', text: 'Nama ODP wajib ditulis pada catatan untuk jenis Pengukuran OPM.', icon: 'error', confirmButtonColor: '#1D4ED8', customClass: { popup: 'rounded-3xl' } });
        return;
    }

    const formData = new FormData();
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('stage', 'pengukuran');
    formData.append('evidence_type', evidenceType);
    formData.append('latitude', document.getElementById('latitude').value);
    formData.append('longitude', document.getElementById('longitude').value);
    formData.append('description', description);

    selectedFiles.forEach(item => formData.append('photos[]', item.file));

    fetch(e.target.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (response.ok) {
            closeUploadModal();
            Swal.fire({ title: 'Berhasil Disimpan!', text: 'Eviden progress pengukuran berhasil diperbarui.', icon: 'success', showConfirmButton: false, timer: 1500, timerProgressBar: true, customClass: { popup: 'rounded-3xl' } })
            .then(() => window.location.reload());
        } else {
            Swal.fire({ title: 'Gagal Memproses!', text: 'Terjadi kesalahan sistem atau kegalaran validasi backend.', icon: 'error', confirmButtonColor: '#1D4ED8', customClass: { popup: 'rounded-3xl' } });
        }
    })
    .catch(() => {
        Swal.fire({ title: 'Gangguan Jaringan!', text: 'Gagal menghubungi server. Pastikan koneksi internet di lapangan stabil.', icon: 'warning', confirmButtonColor: '#1D4ED8', customClass: { popup: 'rounded-3xl' } });
    });
});
</script>
@endsection