<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 3 Pengukuran</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f7f6f2] text-gray-900">

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
    <div class="bg-blue-700 text-white px-5 pt-7 pb-6">

        <div class="flex items-center gap-3 mb-7">
            <a href="{{ route('waspang.projects.instalasi', $project->id_project) }}"
               class="text-3xl leading-none">
                ‹
            </a>

            <h1 class="text-xl font-bold">
                Step 3 - Pengukuran
            </h1>
        </div>

        {{-- STEPPER --}}
        <div class="relative px-2">

            <div class="absolute top-4 left-10 right-10 h-1 bg-blue-300/60 rounded-full"></div>

            <div class="relative grid grid-cols-4 text-center">

                <a href="{{ route('waspang.projects.show', $project->id_project) }}">
                    <div class="mx-auto w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">
                        ✓
                    </div>
                    <p class="mt-2 text-xs">Persiapan</p>
                </a>

                <a href="{{ route('waspang.projects.instalasi', $project->id_project) }}">
                    <div class="mx-auto w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">
                        ✓
                    </div>
                    <p class="mt-2 text-xs">Instalasi</p>
                </a>

                <a href="{{ route('waspang.projects.pengukuran', $project->id_project) }}">
                    <div class="mx-auto w-8 h-8 rounded-full {{ $pengukuranUploadedComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">
                        {{ $pengukuranUploadedComplete ? '✓' : '3' }}
                    </div>
                    <p class="mt-2 text-xs font-bold">Pengukuran</p>
                </a>

                @if($pengukuranUploadedComplete)
                    <a href="{{ route('waspang.projects.finishing', $project->id_project) }}">
                        <div class="mx-auto w-8 h-8 rounded-full bg-white text-blue-700 flex items-center justify-center text-sm font-bold">
                            4
                        </div>
                        <p class="mt-2 text-xs">Finishing</p>
                    </a>
                @else
                    <div class="opacity-50">
                        <div class="mx-auto w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center text-sm font-bold">
                            4
                        </div>
                        <p class="mt-2 text-xs">Finishing</p>
                    </div>
                @endif

            </div>

        </div>

    </div>

    {{-- PROJECT INFO --}}
    <div class="px-4 mt-4">

        <div class="bg-white rounded-2xl border border-gray-200 p-4">

            <div class="grid grid-cols-2 gap-y-4 gap-x-4">

                <div>
                    <p class="text-xs text-gray-500">Nama LOP</p>
                    <p class="text-sm font-bold leading-snug">
                        {{ $project->project_name }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">STO</p>
                    <p class="text-sm font-bold">
                        {{ $project->sto }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Branch</p>
                    <p class="text-sm font-bold">
                        {{ $project->branch }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Mitra</p>
                    <p class="text-sm font-bold leading-snug">
                        {{ $project->mitra_name }}
                    </p>
                </div>

            </div>

        </div>

    </div>

    {{-- STEP 3 LIST --}}
    <div class="px-4 mt-5">

        <div class="flex items-center justify-between mb-3">

            <div>
                <h2 class="text-sm font-bold text-gray-500 uppercase">
                    Step 3 - Pengukuran
                </h2>

                <p class="text-xs text-gray-500">
                    Upload eviden OTDR, OPM, dan kedalaman galian
                </p>
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
                    Upload semua eviden <strong>OTDR</strong>, <strong>OPM</strong>, dan
                    <strong>Kedalaman Galian</strong> untuk lanjut ke tahap Finishing.
                    Approval admin dilakukan terpisah.
                </p>
            </div>
        @endif

    </div>

    {{-- NEXT BUTTON --}}
    <div class="px-4 mt-5">

        @if($pengukuranUploadedComplete)

            <a href="{{ route('waspang.projects.finishing', $project->id_project) }}"
               class="h-11 w-full rounded-2xl bg-blue-700 text-white inline-flex items-center justify-center text-sm font-bold">
                Next Step 4 - Finishing
            </a>

        @else

            <button disabled
                    class="h-11 w-full rounded-2xl bg-gray-300 text-gray-500 inline-flex items-center justify-center text-sm font-bold">
                Lengkapi Eviden Pengukuran
            </button>

        @endif

    </div>

    {{-- UPLOAD MODAL --}}
    <div id="uploadModal"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

        <div class="bg-white w-full max-w-md max-h-[90vh] rounded-2xl overflow-hidden flex flex-col">

            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">

                <div>
                    <h2 id="uploadTitle" class="text-lg font-bold">
                        Upload Eviden Pengukuran
                    </h2>
                    <p class="text-xs text-gray-500">
                        Pilih foto, otomatis dikompres sebelum upload
                    </p>
                </div>

                <button type="button"
                        onclick="closeUploadModal()"
                        class="w-10 h-10 rounded-xl border border-gray-300 text-xl">
                    ×
                </button>

            </div>

            <form id="uploadForm"
                  method="POST"
                  action="{{ route('waspang.evidence.upload', $project->id_project) }}"
                  enctype="multipart/form-data"
                  class="flex flex-col min-h-0">
                @csrf

                <input type="hidden" name="stage" value="pengukuran">
                <input type="hidden" name="evidence_type" id="upload_evidence_type">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                <div class="p-5 overflow-y-auto space-y-4">

                    <div class="rounded-xl bg-blue-50 text-blue-700 text-xs p-3">
                        Jenis eviden:
                        <span id="selectedEvidenceName" class="font-bold"></span>
                    </div>

                    <input type="file"
                           id="photoInput"
                           accept="image/*"
                           multiple
                           class="w-full rounded-xl border border-gray-300 p-3 text-sm">

                    <div id="previewContainer"
                         class="grid grid-cols-3 gap-3">
                    </div>

                    <textarea name="description"
                              id="descriptionInput"
                              rows="3"
                              placeholder="Catatan opsional..."
                              class="w-full rounded-xl border-gray-300 text-sm"></textarea>

                    <div id="opmNoteInfo"
                         class="hidden rounded-xl bg-yellow-50 text-yellow-800 text-xs p-3">
                        Untuk pengukuran OPM, isi nama ODP pada catatan.
                        Contoh: ODP-BDG-FAT-001.
                    </div>

                </div>

                <div class="grid grid-cols-2 gap-3 px-5 py-4 border-t border-gray-200">

                    <button type="button"
                            onclick="closeUploadModal()"
                            class="h-10 rounded-xl border border-gray-300 text-sm font-bold">
                        Batal
                    </button>

                    <button type="submit"
                            class="h-10 rounded-xl bg-blue-700 text-white text-sm font-bold">
                        Upload
                    </button>

                </div>

            </form>

        </div>

    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'inbox'])

</div>

<script>
let selectedFiles = [];

function openUploadModal(type, title)
{
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');

    document.getElementById('upload_evidence_type').value = type;
    document.getElementById('uploadTitle').innerText = title;
    document.getElementById('selectedEvidenceName').innerText = title;

    selectedFiles = [];

    document.getElementById('photoInput').value = '';
    document.getElementById('previewContainer').innerHTML = '';

    const descriptionInput = document.getElementById('descriptionInput');
    const opmNoteInfo = document.getElementById('opmNoteInfo');

    descriptionInput.value = '';

    if (type === 'opm') {
        descriptionInput.placeholder = 'Isi nama ODP, contoh: ODP-BDG-FAT-001';
        opmNoteInfo.classList.remove('hidden');
    } else {
        descriptionInput.placeholder = 'Catatan opsional...';
        opmNoteInfo.classList.add('hidden');
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('latitude').value = position.coords.latitude;
            document.getElementById('longitude').value = position.coords.longitude;
        });
    }
}

function closeUploadModal()
{
    document.getElementById('uploadModal').classList.add('hidden');
    document.getElementById('uploadModal').classList.remove('flex');
}

document.getElementById('photoInput').addEventListener('change', async function(e) {
    const files = Array.from(e.target.files);

    for (const file of files) {
        const resized = await resizeImage(file, 1280, 0.8);
        selectedFiles.push(resized);
    }

    renderPreview();
});

function renderPreview()
{
    const container = document.getElementById('previewContainer');
    container.innerHTML = '';

    selectedFiles.forEach((file, index) => {
        const url = URL.createObjectURL(file);

        container.innerHTML += `
            <div class="relative aspect-square rounded-xl overflow-hidden border border-gray-200">
                <img src="${url}" class="w-full h-full object-cover">

                <button type="button"
                        onclick="removeSelectedPhoto(${index})"
                        class="absolute top-1 right-1 w-6 h-6 rounded-full bg-black/70 text-white text-xs">
                    ×
                </button>
            </div>
        `;
    });
}

function removeSelectedPhoto(index)
{
    selectedFiles.splice(index, 1);
    renderPreview();
}

function resizeImage(file, maxWidth = 1280, quality = 0.8)
{
    return new Promise((resolve) => {
        const reader = new FileReader();

        reader.onload = function(event) {
            const img = new Image();

            img.onload = function() {
                const canvas = document.createElement('canvas');

                let width = img.width;
                let height = img.height;

                if (width > maxWidth) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                }

                canvas.width = width;
                canvas.height = height;

                canvas.getContext('2d').drawImage(img, 0, 0, width, height);

                canvas.toBlob(function(blob) {
                    resolve(new File([blob], file.name, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    }));
                }, 'image/jpeg', quality);
            };

            img.src = event.target.result;
        };

        reader.readAsDataURL(file);
    });
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const evidenceType = document.getElementById('upload_evidence_type').value;
    const description = document.getElementById('descriptionInput').value.trim();

    if (selectedFiles.length === 0) {
        alert('Pilih minimal 1 foto');
        return;
    }

    if (evidenceType === 'opm' && description === '') {
        alert('Nama ODP wajib diisi pada catatan untuk Pengukuran OPM');
        return;
    }

    const formData = new FormData(e.target);

    selectedFiles.forEach((file) => {
        formData.append('photos[]', file);
    });

    fetch(e.target.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
    })
    .then(() => window.location.reload())
    .catch(() => alert('Upload gagal'));
});
</script>

</body>
</html>