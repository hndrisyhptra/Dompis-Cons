<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 4 Finishing</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f7f6f2] text-gray-900">

<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

@php
    $evidences = $project->evidences ?? collect();

    $finishingPhotos = $evidences
        ->where('stage', 'finishing')
        ->where('evidence_type', 'final_site')
        ->sortByDesc('created_at');

    $finishingStatus = optional($finishingPhotos->first())->status;
    $finishingUploaded = $finishingPhotos->count() > 0;

    $finishingRejected = $finishingPhotos
        ->where('status', 'rejected')
        ->sortByDesc('created_at')
        ->first();

    $finishingReviewNote = optional($finishingRejected)->review_note;

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
<div class="bg-blue-700 text-white px-5 pt-7 pb-6">

    <div class="flex items-center gap-3 mb-7">
        <a href="{{ route('waspang.projects.pengukuran', $project->id_project) }}"
           class="text-3xl leading-none">
            ‹
        </a>

        <h1 class="text-xl font-bold">
            Step 4 - Finishing
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
                <div class="mx-auto w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">
                    ✓
                </div>
                <p class="mt-2 text-xs">Pengukuran</p>
            </a>

            <a href="{{ route('waspang.projects.finishing', $project->id_project) }}">
                <div class="mx-auto w-8 h-8 rounded-full {{ $readyForUt ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">
                    {{ $readyForUt ? '✓' : '4' }}
                </div>
                <p class="mt-2 text-xs font-bold">Finishing</p>
            </a>

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

{{-- CARD FINISHING --}}
<div class="px-4 mt-5">

    <div class="flex items-center justify-between mb-3">

        <div>
            <h2 class="text-sm font-bold text-gray-500 uppercase">
                Step 4 - Finishing
            </h2>

            <p class="text-xs text-gray-500">
                Upload dokumentasi kondisi akhir pekerjaan
            </p>
        </div>

        @if($finishingUploaded)
            <span class="px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-[11px] font-bold">
                Uploaded
            </span>
        @else
            <span class="px-2.5 py-1 rounded-lg bg-yellow-100 text-yellow-700 text-[11px] font-bold">
                Belum
            </span>
        @endif

    </div>

    <div x-data="{ open: true }"
         class="bg-white rounded-2xl border border-gray-200 overflow-hidden">

        <button type="button"
                @click="open = !open"
                class="w-full p-4 flex items-center justify-between gap-3">

            <div class="flex items-center gap-3 min-w-0">

                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold shrink-0
                    {{ $finishingUploaded ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $finishingUploaded ? '✓' : '4' }}
                </div>

                <div class="text-left min-w-0">
                    <h3 class="text-sm font-bold truncate">
                        Eviden Finishing / Final Site
                    </h3>

                    <p class="text-xs text-gray-500 truncate">
                        {{ $finishingPhotos->count() }} foto
                        ·
                        {{ $finishingStatus ?? 'Belum upload' }}
                    </p>
                </div>

            </div>

            <div class="flex items-center gap-2 shrink-0">

                @if(!$finishingStatus)
                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[11px] font-bold">
                        Belum
                    </span>
                @elseif($finishingStatus == 'approved')
                    <span class="px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-[11px] font-bold">
                        Approved
                    </span>
                @elseif($finishingStatus == 'pending')
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

        <div x-show="open"
             x-transition
             class="border-t border-gray-100 p-4">

            <p class="text-xs text-gray-500 mb-3">
                Upload foto kondisi akhir lokasi, labeling, marking, atau dokumentasi final pekerjaan.
            </p>

            @if($finishingReviewNote)

                <div class="mb-3 rounded-xl border border-red-200 bg-red-50 p-3">

                    <p class="text-[11px] font-bold text-red-700 mb-1">
                        Catatan Revisi Admin
                    </p>

                    <p class="text-xs text-red-700 leading-relaxed">
                        {{ $finishingReviewNote }}
                    </p>

                </div>

            @endif

            @if($finishingPhotos->count() > 0)

                <div class="grid grid-cols-3 gap-2 mb-3">

                    @foreach($finishingPhotos as $photo)

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
                    Belum ada foto finishing.
                </p>

            @endif

            <button type="button"
                    onclick="openUploadModal()"
                    class="h-9 w-full rounded-xl bg-blue-700 text-white text-xs font-bold">
                Upload / Update Eviden
            </button>

        </div>

    </div>

</div>

{{-- FINAL ACTION --}}
<div class="px-4 mt-5">

    @if($readyForUt)

        <button type="button"
                class="h-11 w-full rounded-2xl bg-green-700 text-white inline-flex items-center justify-center text-sm font-bold">
            Siap Generate Berkas Uji Terima
        </button>

    @else

        <button disabled
                class="h-11 w-full rounded-2xl bg-gray-300 text-gray-500 inline-flex items-center justify-center text-sm font-bold">
            Menunggu Semua Eviden Approved
        </button>

    @endif

</div>

{{-- UPLOAD MODAL --}}
<div id="uploadModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white w-full max-w-md max-h-[90vh] rounded-2xl overflow-hidden flex flex-col">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">

            <div>
                <h2 class="text-lg font-bold">
                    Upload Eviden Finishing
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

            <input type="hidden" name="stage" value="finishing">
            <input type="hidden" name="evidence_type" value="final_site">
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">

            <div class="p-5 overflow-y-auto space-y-4">

                <input type="file"
                       id="photoInput"
                       accept="image/*"
                       multiple
                       class="w-full rounded-xl border border-gray-300 p-3 text-sm">

                <div id="previewContainer"
                     class="grid grid-cols-3 gap-3">
                </div>

                <textarea name="description"
                          rows="3"
                          placeholder="Catatan finishing..."
                          class="w-full rounded-xl border-gray-300 text-sm"></textarea>

                <div class="rounded-xl bg-blue-50 text-blue-700 text-xs p-3">
                    Foto akan di-resize otomatis maksimal 1280px sebelum dikirim.
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

function openUploadModal()
{
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');

    selectedFiles = [];
    document.getElementById('photoInput').value = '';
    document.getElementById('previewContainer').innerHTML = '';

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

    if (selectedFiles.length === 0) {
        alert('Pilih minimal 1 foto');
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