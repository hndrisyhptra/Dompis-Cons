<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 2 Instalasi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f7f6f2] text-gray-900">

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
            class="text-3xl leading-none">
                ‹
            </a>

            <h1 class="text-xl font-bold">
                Step 2 - Instalasi
            </h1>

        </div>

            {{-- Stepper --}}
            <div class="relative px-2">

                <div class="absolute top-4 left-10 right-10 h-1 bg-blue-300/60 rounded-full"></div>

                <div class="relative grid grid-cols-4 text-center">

                    {{-- STEP 1: PERSIAPAN --}}
                    <a href="{{ route('waspang.projects.show', $project->id_project) }}">
                        <div class="mx-auto w-8 h-8 rounded-full bg-green-100 text-green-700 flex items-center justify-center text-sm font-bold">
                            ✓
                        </div>
                        <p class="mt-2 text-xs">
                            Persiapan
                        </p>
                    </a>

                    {{-- STEP 2: INSTALASI --}}
                    <a href="{{ route('waspang.projects.instalasi', $project->id_project) }}">
                        <div class="mx-auto w-8 h-8 rounded-full {{ $instalasiUploadedComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">
                            {{ $instalasiUploadedComplete ? '✓' : '2' }}
                        </div>
                        <p class="mt-2 text-xs font-bold">
                            Instalasi
                        </p>
                    </a>

                    {{-- STEP 3: PENGUKURAN --}}
                    @if($instalasiUploadedComplete)

                        <a href="{{ route('waspang.projects.pengukuran', $project->id_project) }}">
                            <div class="mx-auto w-8 h-8 rounded-full {{ $pengukuranComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">
                                {{ $pengukuranComplete ? '✓' : '3' }}
                            </div>
                            <p class="mt-2 text-xs">
                                Pengukuran
                            </p>
                        </a>

                    @else

                        <div class="opacity-50">
                            <div class="mx-auto w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center text-sm font-bold">
                                3
                            </div>
                            <p class="mt-2 text-xs">
                                Pengukuran
                            </p>
                        </div>

                    @endif

                    {{-- STEP 4: FINISHING --}}
                    @if($pengukuranComplete)

                        <a href="{{ route('waspang.projects.finishing', $project->id_project) }}">
                            <div class="mx-auto w-8 h-8 rounded-full {{ $finishingComplete ? 'bg-green-100 text-green-700' : 'bg-white text-blue-700' }} flex items-center justify-center text-sm font-bold">
                                {{ $finishingComplete ? '✓' : '4' }}
                            </div>
                            <p class="mt-2 text-xs">
                                Finishing
                            </p>
                        </a>

                    @else

                        <div class="opacity-50">
                            <div class="mx-auto w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center text-sm font-bold">
                                4
                            </div>
                            <p class="mt-2 text-xs">
                                Finishing
                            </p>
                        </div>

                    @endif

                </div>

            </div>

        </div>

        {{-- Project Info --}}
        <div class="px-2 mt-2">
            <div class="bg-white rounded-2xl border border-gray-200 p-4">

            {{-- Nama LOP --}}
            <div class="mb-4">
                <p class="text-xs text-gray-500">Nama LOP</p>
                <p class="text-sm font-bold leading-snug break-words">
                    {{ $project->project_name }}
                </p>
            </div>

                {{-- Info lainnya --}}
                <div class="grid grid-cols-2 gap-y-4 gap-x-4">

                        <div>
                            <p class="text-xs text-gray-500">STO</p>
                            <p class="text-sm font-bold">
                                {{ $project->lop?->sto ?? '-' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">Branch</p>
                            <p class="text-sm font-bold">
                                {{ $project->lop?->branch ?? '-' }}
                            </p>
                        </div>

                        <div class="col-span-2">
                            <p class="text-xs text-gray-500">Mitra</p>
                            <p class="text-sm font-bold leading-snug break-words">
                                {{ $project->lop?->mitra_name ?? '-' }}
                            </p>
                        </div>

                </div>

            </div>
    </div>

    {{-- STEP 2 LIST --}}
    <div class="px-4 mt-5">

        <div class="flex items-center justify-between mb-3">

            <div>
                <h2 class="text-sm font-bold text-gray-500 uppercase">
                    Step 2 - Instalasi
                </h2>

                <p class="text-xs text-gray-500">
                    Upload eviden sesuai item BOQ
                </p>
            </div>

            @if($instalasiUploadedComplete)
                <span class="px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-[11px] font-bold">
                    Complete
                </span>
            @else
                <span class="px-2.5 py-1 rounded-lg bg-yellow-100 text-yellow-700 text-[11px] font-bold">
                    {{ $boqUploaded }}/{{ $boqTotal }}
                </span>
            @endif

        </div>

        <div class="space-y-3">

            @forelse($project->boqItems as $boq)

            @php
                $photos = $evidences
                    ->where('stage', 'instalasi')
                    ->where('evidence_type', 'progress_boq')
                    ->where('boq_item_id', $boq->id_boq)
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

                {{-- HEADER --}}
                <button type="button"
                        @click="open = !open"
                        class="w-full p-4 flex items-center justify-between gap-3">

                    <div class="flex items-center gap-3 min-w-0">

                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold shrink-0
                            {{ $isUploaded ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $isUploaded ? '✓' : $loop->iteration }}
                        </div>

                        <div class="text-left min-w-0">

                            <h3 class="text-sm font-bold">
                                {{ $boq->designator }}
                            </h3>

                            <p>
                                Qty Plan :
                                <span class="font-semibold text-gray-700">
                                    {{ number_format($boq->quantity_plan) }}
                                    {{ $boq->unit }}
                                </span>
                            </p>

                            @php
                                $category = strtoupper(optional($boq->designatorData)->progress_category ?? '');
                            @endphp

                            @if(in_array($category,['KABEL','TIANG']))

                            <p>
                                Aktual {{ $category }} :
                                <span class="font-bold text-blue-700">
                                    {{ number_format($boq->quantity_actual ?? 0) }}
                                    {{ $boq->unit }}
                                </span>
                            </p>

                            @endif

                            <p class="text-xs text-gray-500 truncate mt-0.5">
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

                {{-- DETAIL --}}
                <div x-show="open"
                    x-transition
                    class="border-t border-gray-100 p-4">

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
                        'histories' => $revisionHistories[$boq->id_boq] ?? collect()
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
                            Belum ada foto eviden untuk item BOQ ini.
                        </p>

                    @endif

                    <button type="button"
                            onclick="openUploadModal(
                            '{{ $boq->id_boq }}',
                            @js($boq->item_name),
                            '{{ $boq->quantity_plan }}',
                            '{{ $boq->unit }}',
                            '{{ $boq->quantity_actual ?? '' }}',
                            '{{ strtoupper(optional($boq->designatorData)->progress_category ?? '') }}'
                            )"
                            class="h-9 w-full rounded-xl bg-blue-700 text-white text-xs font-bold">
                        Upload / Update Eviden
                    </button>

                </div>

            </div>

            @empty

                <div class="bg-white rounded-2xl border border-gray-200 p-5 text-center text-sm text-gray-500">
                    Belum ada item BOQ.
                </div>

            @endforelse

        </div>

    </div>

    {{-- NEXT BUTTON --}}
    <div class="px-4 mt-5">

        @if($instalasiUploadedComplete)

            <a href="{{ route('waspang.projects.pengukuran', $project->id_project) }}"
               class="h-11 w-full rounded-2xl bg-blue-700 text-white inline-flex items-center justify-center text-sm font-bold">
                Next Step 3 - Pengukuran
            </a>

        @else

            <button disabled
                    class="h-11 w-full rounded-2xl bg-gray-300 text-gray-500 inline-flex items-center justify-center text-sm font-bold">
                Lengkapi Eviden Instalasi
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
                        Upload Eviden Instalasi
                    </h2>
                    <p class="text-xs text-gray-500">
                        Pilih foto progress lapangan
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

                <input type="hidden" name="stage" value="instalasi">
                <input type="hidden" name="evidence_type" value="progress_boq">
                <input type="hidden" name="boq_item_id" id="boq_item_id">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

                <div class="p-5 overflow-y-auto space-y-4">

                    <div class="rounded-xl bg-blue-50 text-blue-700 text-xs p-3">
                        Item BOQ:
                        <span id="selectedBoqName" class="font-bold"></span>
                    </div>

                    <input type="file"
                            id="photoInput"
                            name="photos[]"
                            accept="image/*"
                            multiple
                            class="w-full rounded-xl border border-gray-300 p-3 text-sm">

                    <div id="previewContainer"
                         class="grid grid-cols-3 gap-3">
                    </div>

                    <div class="rounded-xl bg-green-50 border border-green-100 p-3">

                        <div class="flex justify-between">

                            <span class="text-xs text-green-700">

                                Qty Plan

                            </span>

                            <span class="font-bold text-green-800">

                                <span id="planQuantity"></span>

                                <span id="planUnit"></span>

                            </span>

                        </div>

                        <div class="flex justify-between mt-2">

                            <span class="text-xs text-blue-700">

                                Qty Actual Saat Ini

                            </span>

                            <span
                                id="currentActual"
                                class="font-bold text-blue-700">

                            </span>

                        </div>

                    </div>
                    <div class="grid grid-cols-2 gap-3">
                            
                        <div>
                            <label
                                id="qtyLabel"
                                class="text-xs font-semibold text-gray-600">

                                Quantity Actual

                            </label>
                            <input type="number"
                                    step="0.01"
                                    name="quantity_actual"
                                    id="quantity_actual"
                                    placeholder="Actual"
                                    class="mt-1 w-full h-10 rounded-xl border-gray-300 text-sm">
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-600">
                                Note
                            </label>
                            <input type="text"
                                   name="description"
                                   placeholder="Note..."
                                   class="mt-1 w-full h-10 rounded-xl border-gray-300 text-sm">
                        </div>

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

function openUploadModal(boqId, boqName, quantityPlan, unit, quantityActual, category)
{
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');

    document.getElementById('boq_item_id').value = boqId;
    document.getElementById('selectedBoqName').innerText = boqName;

    document.getElementById('planQuantity').innerText = quantityPlan;
    document.getElementById('planUnit').innerText = unit;
    
    // Set field value quantity_actual dari data looping database saat ini
    document.getElementById('quantity_actual').value = quantityActual ? quantityActual : '';

    document.getElementById('currentActual').innerText =
    (quantityActual ? quantityActual : 0) + ' ' + unit;

    if(category === 'KABEL' || category === 'TIANG'){

        document.getElementById('qtyLabel').innerText =
            'Update Progress ' + category;

    }else{

        document.getElementById('qtyLabel').innerText =
            'Quantity Actual';

    }

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

ddocument.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (selectedFiles.length === 0) {
        alert('Pilih minimal 1 foto sebagai bukti progress lapangan');
        return;
    }

    // Inisialisasi FormData baru
    const formData = new FormData();

    // Ambil data element manual untuk menjamin keakuratan nilai update berulang
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    formData.append('stage', 'instalasi');
    formData.append('evidence_type', 'progress_boq');
    formData.append('boq_item_id', document.getElementById('boq_item_id').value);
    formData.append('latitude', document.getElementById('latitude').value);
    formData.append('longitude', document.getElementById('longitude').value);
    formData.append('description', document.getElementsByName('description')[0].value);
    
    // Ambil nilai actual murni dari ID Element
    const qtyActualValue = document.getElementById('quantity_actual').value;
    if (qtyActualValue !== '') {
        formData.append('quantity_actual', qtyActualValue);
    }

    // Masukkan file resize dari array javascript 
    selectedFiles.forEach((file) => {
        formData.append('photos[]', file);
    });

    // Eksekusi pengiriman data via Fetch
    fetch(e.target.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // SOLUSI UTAMA: Selama server merespon dengan status sukses (200-299), langsung reload halaman.
        // Cara ini tidak akan memicu error akibat parsing teks/JSON yang tidak sinkron.
        if (response.ok) {
            window.location.reload();
        } else {
            alert('Gagal memproses data. Silakan periksa kembali format inputan Anda.');
        }
    })
    .catch(error => {
        console.error('Fetch Error:', error);
        alert('Terjadi kesalahan jaringan atau kendala server saat mengupload.');
    });
});
</script>

</body>
</html>