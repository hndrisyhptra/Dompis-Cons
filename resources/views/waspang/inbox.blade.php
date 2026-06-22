<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox LOP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f7f6f2] text-gray-900">

<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

    @if(session('success'))
        <div class="mx-4 mt-4 rounded-2xl bg-green-100 border border-green-300 text-green-800 px-4 py-3 text-sm font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mx-4 mt-4 rounded-2xl bg-red-100 border border-red-300 text-red-800 px-4 py-3 text-sm font-bold">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem]">

        <div class="flex items-center gap-3">

            <a href="{{ route('waspang.dashboard') }}" class="text-3xl leading-none">
                ‹
            </a>

            <div>
                <h1 class="text-xl font-bold">
                    Inbox LOP
                </h1>
                <p class="text-xs opacity-90">
                    {{ $projects->count() }} Order di Assign
                </p>
            </div>
        </div>
    </div>

    <!-- SEARCH BAR -->
    <div class="px-4 mt-4">

        <form method="GET" action="{{ route('waspang.inbox') }}">

            <div class="relative">

                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari LOP, STO, branch, mitra..."
                    class="w-full h-11 rounded-2xl border border-gray-300 bg-white pl-10 pr-4 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none">

                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    🔍
                </div>

            </div>

        </form>

    </div>

    <div class="px-4 mt-4 space-y-4">

        @forelse($projects as $project)

            @php
                $evidences = $project->evidences ?? collect();
                $boqItems = $project->boqItems ?? collect();

                /*
                |--------------------------------------------------------------------------
                | STEP CHECK
                |--------------------------------------------------------------------------
                */

                $barangTibaUploaded =
                    $evidences
                        ->where('stage', 'persiapan')
                        ->where('evidence_type', 'barang_tiba')
                        ->where('status', 'approved')
                        ->count() > 0;

                $perizinanUploaded =
                    $evidences
                        ->where('stage', 'persiapan')
                        ->where('evidence_type', 'perizinan')
                        ->where('status', 'approved')
                        ->count() > 0;

                $persiapanDone =
                    $barangTibaUploaded &&
                    $perizinanUploaded;

                /*
                |--------------------------------------------------------------------------
                | INSTALASI
                |--------------------------------------------------------------------------
                */

                $materialBoqItems = $boqItems->filter(function ($boq) {
                    return str_starts_with($boq->designator, 'M-');
                });

                $boqTotal = $materialBoqItems->count();

                $boqDone = $materialBoqItems->filter(function ($boq) use ($evidences) {

                    return $evidences
                        ->where('stage', 'instalasi')
                        ->where('evidence_type', 'progress_boq')
                        ->where('boq_item_id', $boq->id_boq)
                        ->where('status', 'approved')
                        ->count() > 0;

                })->count();

                $instalasiDone =
                    $boqTotal > 0 &&
                    $boqDone == $boqTotal;

                /*
                |--------------------------------------------------------------------------
                | PENGUKURAN
                |--------------------------------------------------------------------------
                */

                $otdrUploaded =
                    $evidences
                        ->where('stage', 'pengukuran')
                        ->where('evidence_type', 'otdr')
                        ->where('status','approved')
                        ->count() > 0;

                $opmUploaded =
                    $evidences
                        ->where('stage', 'pengukuran')
                        ->where('evidence_type', 'opm')
                        ->where('status','approved')
                        ->count() > 0;

                $kedalamanUploaded =
                    $evidences
                        ->where('stage', 'pengukuran')
                        ->where('evidence_type', 'kedalaman')
                        ->where('status','approved')
                        ->count() > 0;

                $pengukuranDone =
                    $otdrUploaded &&
                    $opmUploaded &&
                    $kedalamanUploaded;

                /*
                |--------------------------------------------------------------------------
                | FINISHING
                |--------------------------------------------------------------------------
                */

                $finishingDone =
                $evidences
                    ->where('stage', 'finishing')
                    ->where('status', 'approved')
                    ->count() > 0;

                /*
                |--------------------------------------------------------------------------
                | PROGRESS
                |--------------------------------------------------------------------------
                */

                $doneStep = 0;

                if ($persiapanDone) $doneStep++;
                if ($instalasiDone) $doneStep++;
                if ($pengukuranDone) $doneStep++;
                if ($finishingDone) $doneStep++;

                $progress = round(($doneStep / 4) * 100);

                /*
                |--------------------------------------------------------------------------
                | READY UT
                |--------------------------------------------------------------------------
                */

                $approvedCount = $evidences->where('status', 'approved')->count();

                $pendingCount = $evidences->where('status', 'pending')->count();

                $rejectedCount = $evidences->where('status', 'rejected')->count();

                $totalEvidence = $evidences->count();

                $isFinish =
                    $totalEvidence > 0 &&
                    $approvedCount == $totalEvidence &&
                    $pendingCount == 0 &&
                    $rejectedCount == 0;

                /*
                |--------------------------------------------------------------------------
                | STYLE
                |--------------------------------------------------------------------------
                */

                $borderColor = $isFinish
                    ? 'border-l-green-700'
                    : 'border-l-blue-700';

                $progressColor = $isFinish
                    ? 'bg-green-700'
                    : 'bg-blue-700';

                /*
                |--------------------------------------------------------------------------
                | UPDATE TERAKHIR
                |--------------------------------------------------------------------------
                */

                $lastEvidence = $evidences
                    ->sortByDesc('updated_at')
                    ->first();

                $lastUpdate =
                    optional($lastEvidence)->updated_at
                    ?? $project->updated_at;
            @endphp

            <div class="bg-white border border-gray-200 border-l-[3px] {{ $borderColor }} rounded-2xl p-3.5 shadow-sm">

                <div class="flex justify-between gap-3">

                    <div class="min-w-0">
                        <h2 class="text-[17px] font-bold leading-tight">
                            {{ $project->project_name }}
                        </h2>

                        <p class="text-xs text-gray-500 mt-1">
                            {{ $project->lop?->branch }} · {{ $project->lop?->sto }} · {{ strtoupper($project->execution_type) }}
                        </p>
                    </div>

                    @php
                        $allStepDone =
                            $persiapanDone &&
                            $instalasiDone &&
                            $pengukuranDone &&
                            $finishingDone;
                    @endphp

                    <span class="shrink-0 h-fit px-2 py-[3px] rounded-lg text-xs font-bold
                        {{ $allStepDone ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">

                        {{ $allStepDone ? 'Selesai' : 'On Progress' }}

                    </span>

                </div>

                <div class="flex flex-wrap gap-1 mt-3 text-[10px] font-medium">

                    <span class="px-2 py-[3px] rounded-lg
                        {{ $persiapanDone ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $persiapanDone ? '✓ Persiapan' : '○ Persiapan' }}
                    </span>

                    <span class="px-2 py-[3px] rounded-lg
                        {{ $instalasiDone ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $instalasiDone ? '✓ Instalasi' : '○ Instalasi' }}
                    </span>

                    <span class="px-2 py-[3px] rounded-lg
                        {{ $pengukuranDone ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $pengukuranDone ? '✓ Pengukuran' : '○ Pengukuran' }}
                    </span>

                    <span class="px-2 py-[3px] rounded-lg
                        {{ $finishingDone ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $finishingDone ? '✓ Finishing' : '○ Finishing' }}
                    </span>

                </div>

                <div class="mt-4 h-1.5 bg-gray-200 rounded-lg overflow-hidden">
                    <div class="h-full {{ $progressColor }} rounded-lg"
                         style="width: {{ $progress }}%">
                    </div>
                </div>

                <div class="flex items-center justify-between mt-3">

                <div>
                    <p class="text-[11px] text-gray-500">
                        Total Progress
                    </p>

                    <p class="text-sm font-black text-blue-700">
                        {{ $progress }}%
                    </p>
                </div>

                 <div class="text-right">
                    <p class="text-[11px] text-gray-500">
                        Update Terakhir
                    </p>

                    <p class="text-[11px] font-bold text-gray-900">
                        {{ $lastUpdate ? $lastUpdate->diffForHumans() : '-' }}
                    </p>

                    <a href="{{ route('waspang.projects.show', $project->id_project) }}"
                    class="inline-block text-[11px] font-bold text-blue-700 mt-1">
                        Detail →
                    </a>
                </div>

            </div>

               @php
                    $kendalaIssue = $project->issues
                        ->where('status', 'kendala')
                        ->sortByDesc('created_at')
                        ->first();

                    $resumeIssue = $project->issues
                        ->where('status', 'open')
                        ->sortByDesc('updated_at')
                        ->first();

                    $mainButtonText = $resumeIssue ? 'Resume Project' : 'Upload Eviden';
                @endphp

                @php
                $nextRoute = route('waspang.projects.show', $project->id_project);
                @endphp
            <div class="mt-3 grid grid-cols-2 gap-2">

                @if($allStepDone)

                    <button
                        class="h-10 col-span-2 inline-flex items-center justify-center rounded-xl bg-green-700 text-white text-sm font-bold">
                        Siap Uji Terima
                    </button>

                @else

                    @if($kendalaIssue)

                        <button
                            type="button"
                            onclick="openKendalaModal('{{ $project->id_project }}').classList.remove('hidden')"
                            class="h-10 inline-flex items-center justify-center rounded-xl bg-orange-600 text-white text-sm font-bold">
                            Update Kendala
                        </button>

                        <form method="POST" action="{{ route('waspang.projects.issues.resume', $project->id_project) }}">
                            @csrf
                            <button
                                type="submit"
                                class="h-10 w-full inline-flex items-center justify-center rounded-xl bg-green-700 text-white text-sm font-bold">
                                Resume
                            </button>
                        </form>

                    @else

                        <a href="{{ $nextRoute }}"
                        class="h-10 inline-flex items-center justify-center rounded-xl bg-blue-700 text-white text-sm font-bold">
                            {{ $mainButtonText }}
                        </a>

                        <button
                            type="button"
                            onclick="document.getElementById('kendalaModal-{{ $project->id_project }}').classList.remove('hidden')"
                            class="h-10 inline-flex items-center justify-center rounded-xl bg-orange-600 text-white text-sm font-bold">
                            Update Kendala
                        </button>

                    @endif

                @endif

            </div>

            <div id="kendalaModal-{{ $project->id_project }}"
     class="hidden fixed inset-0 z-[9999] bg-black/60 px-4 flex items-center justify-center">

    <div class="bg-white rounded-3xl w-full max-w-sm shadow-2xl overflow-hidden">

        <div class="bg-orange-600 text-white px-5 py-4 flex items-start justify-between">
            <div>
                <h2 class="text-lg font-black">
                    Update Kendala
                </h2>
                <p class="text-xs text-orange-100 mt-1">
                    {{ $project->project_name }}
                </p>
            </div>

            <button type="button"
                    onclick="closeKendalaModal('{{ $project->id_project }}')"
                    class="w-9 h-9 rounded-full bg-white/20 text-white font-black">
                ×
            </button>
        </div>

        <form method="POST"
              action="{{ route('waspang.projects.issues.store', $project->id_project) }}"
              enctype="multipart/form-data"
              class="p-5 space-y-4"
              data-issue-uploader="{{ $project->id_project }}">

            @csrf

            <div>
                <label class="text-xs font-black text-gray-600">
                    Jenis Kendala
                </label>

                <select name="issue_type"
                        required
                        class="mt-1 w-full h-11 rounded-2xl border border-gray-300 px-3 text-sm font-semibold focus:ring-2 focus:ring-orange-100 focus:border-orange-600 outline-none">
                    <option value="">Pilih kendala</option>
                    <option value="perizinan">Perizinan</option>
                    <option value="material">Material</option>
                    <option value="akses_lokasi">Akses Lokasi</option>
                    <option value="cuaca">Cuaca</option>
                    <option value="teknis">Teknis Lapangan</option>
                    <option value="lainnya">Lainnya</option>
                </select>
            </div>

            <div>
                <label class="text-xs font-black text-gray-600">
                    Keterangan Kendala
                </label>

                <textarea name="description"
                          required
                          rows="4"
                          placeholder="Contoh: belum bisa lanjut karena izin warga belum keluar..."
                          class="mt-1 w-full rounded-2xl border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-orange-100 focus:border-orange-600 outline-none"></textarea>
            </div>

            <div>
                <label class="text-xs font-black text-gray-600">
                    Foto Kendala <span class="text-gray-400">(opsional, bisa banyak)</span>
                </label>

                <label class="mt-1 flex flex-col items-center justify-center w-full min-h-[120px] border-2 border-dashed border-orange-300 rounded-3xl bg-orange-50 cursor-pointer hover:bg-orange-100 transition">
                    <div class="text-center px-4">
                        <div class="mx-auto w-12 h-12 rounded-2xl bg-orange-600 text-white flex items-center justify-center text-2xl font-black">
                            +
                        </div>

                        <p class="mt-3 text-sm font-black text-orange-700">
                            Pilih Foto Kendala
                        </p>

                        <p class="text-xs text-gray-500 mt-1">
                            JPG, PNG, WEBP · otomatis dikompres
                        </p>
                    </div>

                    <input
                        type="file"
                        name="photos[]"
                        accept="image/*"
                        multiple
                        class="hidden issue-photo-input"
                        data-project-id="{{ $project->id_project }}"
                    >
                </label>

                <div class="mt-3 hidden issue-preview-wrapper" data-project-id="{{ $project->id_project }}">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-black text-gray-600">
                            Preview Foto
                        </p>

                        <button type="button"
                                class="text-xs font-black text-red-600 issue-clear-all"
                                data-project-id="{{ $project->id_project }}">
                            Hapus Semua
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2 issue-preview-grid" data-project-id="{{ $project->id_project }}"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1">
                <button type="button"
                        onclick="closeKendalaModal('{{ $project->id_project }}')"
                        class="h-11 rounded-2xl bg-gray-100 text-gray-700 text-sm font-black">
                    Batal
                </button>

                <button type="submit"
                        class="h-11 rounded-2xl bg-orange-600 text-white text-sm font-black">
                    Kirim Kendala
                </button>
            </div>
        </form>
    </div>
</div>

            </div>

            @if($kendalaIssue)
                <div class="mt-3 rounded-xl bg-orange-50 border border-orange-200 p-3">
                    <p class="text-xs font-bold text-orange-700">
                        Project sedang terkendala
                    </p>
                    <p class="text-xs text-orange-600 mt-1">
                        {{ $kendalaIssue->description }}
                    </p>
                </div>
            @endif

        @empty

            <div class="bg-white border border-gray-200 rounded-2xl p-6 text-center text-gray-500">
                Belum ada LOP yang diassign.
            </div>

        @endforelse

    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'inbox'])

</div>

<script>
    let searchTimeout = null;
    const searchInput = document.querySelector('input[name="search"]');

    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    }
</script>

<script>
const issueUploaders = {};

function openKendalaModal(projectId) {
    const modal = document.getElementById('kendalaModal-' + projectId);

    if (!modal) {
        alert('Modal kendala tidak ditemukan untuk project ID: ' + projectId);
        return;
    }

    modal.classList.remove('hidden');
}

function closeKendalaModal(projectId) {
    const modal = document.getElementById('kendalaModal-' + projectId);

    if (!modal) return;

    modal.classList.add('hidden');
}

function initIssueUploader(projectId) {
    if (issueUploaders[projectId]) return issueUploaders[projectId];

    issueUploaders[projectId] = {
        files: [],
    };

    return issueUploaders[projectId];
}

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
                        {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        }
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

function syncIssueInput(projectId) {
    const uploader = initIssueUploader(projectId);
    const input = document.querySelector(`.issue-photo-input[data-project-id="${projectId}"]`);

    if (!input) return;

    const dataTransfer = new DataTransfer();

    uploader.files.forEach(item => {
        dataTransfer.items.add(item.file);
    });

    input.files = dataTransfer.files;
}

function renderIssuePreview(projectId) {
    const uploader = initIssueUploader(projectId);

    const wrapper = document.querySelector(`.issue-preview-wrapper[data-project-id="${projectId}"]`);
    const grid = document.querySelector(`.issue-preview-grid[data-project-id="${projectId}"]`);

    if (!wrapper || !grid) return;

    grid.innerHTML = '';

    if (uploader.files.length === 0) {
        wrapper.classList.add('hidden');
        return;
    }

    wrapper.classList.remove('hidden');

    uploader.files.forEach((item, index) => {
        const card = document.createElement('div');
        card.className = 'relative aspect-square rounded-2xl overflow-hidden bg-gray-100 border border-gray-200 shadow-sm';

        card.innerHTML = `
            <img src="${item.url}" class="w-full h-full object-cover">

            <button type="button"
                    class="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/75 text-white text-sm font-black flex items-center justify-center"
                    onclick="removeIssuePhoto('${projectId}', ${index})">
                ×
            </button>

            <div class="absolute bottom-0 left-0 right-0 bg-black/55 text-white text-[10px] px-1 py-1 truncate">
                ${formatFileSize(item.file.size)}
            </div>
        `;

        grid.appendChild(card);
    });
}

function removeIssuePhoto(projectId, index) {
    const uploader = initIssueUploader(projectId);

    if (uploader.files[index]) {
        URL.revokeObjectURL(uploader.files[index].url);
    }

    uploader.files.splice(index, 1);

    renderIssuePreview(projectId);
    syncIssueInput(projectId);
}

function clearIssuePhotos(projectId) {
    const uploader = initIssueUploader(projectId);

    uploader.files.forEach(item => {
        URL.revokeObjectURL(item.url);
    });

    uploader.files = [];

    renderIssuePreview(projectId);
    syncIssueInput(projectId);
}

document.addEventListener('change', async function (event) {
    if (!event.target.classList.contains('issue-photo-input')) return;

    const input = event.target;
    const projectId = input.dataset.projectId;
    const uploader = initIssueUploader(projectId);

    const selectedFiles = Array.from(input.files);

    for (const file of selectedFiles) {
        if (!file.type.startsWith('image/')) continue;

        const compressedFile = await compressImage(file, 1280, 0.75);

        uploader.files.push({
            file: compressedFile,
            url: URL.createObjectURL(compressedFile),
        });
    }

    renderIssuePreview(projectId);
    syncIssueInput(projectId);
});

document.addEventListener('click', function (event) {
    if (!event.target.classList.contains('issue-clear-all')) return;

    const projectId = event.target.dataset.projectId;

    clearIssuePhotos(projectId);
});
</script>

<script>
function evidenceUploader() {
    return {
        files: [],
        previews: [],

        async handleFiles(event) {
            const selectedFiles = Array.from(event.target.files);

            for (const file of selectedFiles) {
                if (!file.type.startsWith('image/')) continue;

                const compressedFile = await this.compressImage(file, 1280, 0.75);

                this.files.push(compressedFile);

                this.previews.push({
                    url: URL.createObjectURL(compressedFile),
                    size: this.formatSize(compressedFile.size),
                });
            }

            this.syncInput();
        },

        removeFile(index) {
            URL.revokeObjectURL(this.previews[index].url);

            this.files.splice(index, 1);
            this.previews.splice(index, 1);

            this.syncInput();
        },

        clearAll() {
            this.previews.forEach(item => URL.revokeObjectURL(item.url));

            this.files = [];
            this.previews = [];

            this.syncInput();
        },

        syncInput() {
            const dataTransfer = new DataTransfer();

            this.files.forEach(file => {
                dataTransfer.items.add(file);
            });

            this.$refs.fileInput.files = dataTransfer.files;
        },

        compressImage(file, maxWidth = 1280, quality = 0.75) {
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
                            const newFile = new File(
                                [blob],
                                file.name.replace(/\.[^/.]+$/, '') + '.jpg',
                                {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                }
                            );

                            resolve(newFile);
                        }, 'image/jpeg', quality);
                    };

                    img.src = event.target.result;
                };

                reader.readAsDataURL(file);
            });
        },

        formatSize(bytes) {
            if (bytes < 1024 * 1024) {
                return Math.round(bytes / 1024) + ' KB';
            }

            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }
    }
}
</script>

</body>
</html>