@extends('layouts.admin')

@section('content')

@php
    // Section AF: halaman baru Stage 5/6 (FI-OGP Golive + Golive). Admin
    // upload dokumen submission di sini (LopGoliveSubmission); status
    // verifikasi SDI (LopGoliveVerification, capture UIM) ditampilkan
    // read-only -- aksi verifikasi itu sendiri ada di halaman SDI terpisah
    // (sdi.golive.show), bukan di sini, supaya wewenangnya tetap terpisah.
    //
    // Revisi (permintaan user): tiap kategori dokumen sekarang boleh
    // MULTIPLE file (bukan cuma 1) -- daftar file yg sudah tersimpan
    // ditampilkan per kategori dgn tombol hapus per-file (route
    // admin.evidences.golive.remove), dan input upload pakai atribut
    // `multiple` supaya bisa pilih banyak file sekaligus.
    $lop = $project->lop;
    $submission = $lop?->goliveSubmission;
    $verification = $lop?->goliveVerification;

    $docs = [
        ['key' => 'capture_valins', 'label' => 'Capture Valins', 'files' => $submission?->captureValinsFiles() ?? [], 'accept' => 'image/*', 'hint' => 'Foto (JPG/PNG/WEBP, maks 5MB per file, boleh pilih beberapa sekaligus)'],
        ['key' => 'abd_valid4', 'label' => 'PDF ABD & Valid4', 'files' => $submission?->abdValid4Files() ?? [], 'accept' => '.pdf', 'hint' => 'PDF, maks 10MB per file, boleh pilih beberapa sekaligus'],
        ['key' => 'kml', 'label' => 'File KML', 'files' => $submission?->kmlFiles() ?? [], 'accept' => '.kml,.xml', 'hint' => 'KML/XML, maks 5MB per file, boleh pilih beberapa sekaligus'],
        ['key' => 'mancore', 'label' => 'Mancore', 'files' => $submission?->mancoreFiles() ?? [], 'accept' => 'image/*,.xls,.xlsx', 'hint' => 'Foto ATAU Excel, maks 10MB per file, boleh pilih beberapa sekaligus'],
    ];
    $docsComplete = $submission?->isComplete() ?? false;
    $isSubmitted = $submission?->isSubmitted() ?? false;
    $isLocked = $submission?->isLocked() ?? false;
@endphp

<div class="max-w-4xl mx-auto space-y-4">

    @include('admin.evidences.partials.stepper')

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm font-bold">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- STEP 5: FI-OGP GOLIVE -- upload/perbarui dokumen --}}
    <div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-200 dark:border-gray-800 p-6 shadow-sm">

        <div class="flex items-center justify-between gap-3 mb-1">
            <h2 class="text-lg font-black text-gray-900 dark:text-white">Step 6 · FI-OGP Golive</h2>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $isSubmitted ? 'bg-blue-100 text-blue-700' : ($docsComplete ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') }}">
                {{ $isSubmitted ? 'Terkunci · Menunggu Verifikasi SDI' : ($docsComplete ? 'Draft Lengkap · Siap Submit' : 'Draft Belum Lengkap') }}
            </span>
        </div>
        <p class="text-xs text-gray-500 mb-5">
            Upload dapat dilakukan bertahap melalui Save Draft. Tombol Submit aktif setelah keempat kategori memiliki minimal satu file. Setelah Submit, dokumen dikunci dan dikirim untuk verifikasi tim SDI.
        </p>

        @if($isLocked)
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mt-0.5 shrink-0"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <div>
                    <p class="text-xs font-black">Dokumen sudah disubmit dan dikunci</p>
                    <p class="mt-1 text-[11px] leading-5">File masih dapat direview, tetapi upload ulang dan penghapusan tidak diperbolehkan selama menunggu verifikasi SDI.</p>
                </div>
            </div>
        @endif

        <form id="golive-draft-form" method="POST" action="{{ route('admin.evidences.golive.draft', $project->id_project) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            @foreach($docs as $doc)
                @php $doneCategory = count($doc['files']) > 0; @endphp
                <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-4">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <label class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $doc['label'] }}</label>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $doneCategory ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400' }}">
                            {{ count($doc['files']) }} file
                        </span>
                    </div>

                    {{-- Daftar file yg SUDAH tersimpan di server, masing-masing bisa
                    dihapus satu-satu (Revisi: kategori sekarang multi-file). --}}
                    @if(count($doc['files']) > 0)
                        <ul class="mb-3 space-y-1.5">
                            @foreach($doc['files'] as $fileIndex => $path)
                                <li class="flex items-center justify-between gap-2 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs">
                                    <a href="{{ Storage::url($path) }}" target="_blank" class="font-bold text-blue-600 hover:underline truncate">
                                        {{ basename($path) }} ↗
                                    </a>
                                    @unless($isLocked)
                                        <button type="submit" form="remove-golive-{{ $doc['key'] }}-{{ $fileIndex }}" class="shrink-0 font-bold text-red-600 hover:underline">✕ Hapus</button>
                                    @endunless
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-xs font-bold text-gray-400 mb-2">Belum ada file</p>
                    @endif

                    <input type="file" id="file-{{ $doc['key'] }}" name="{{ $doc['key'] }}[]" accept="{{ $doc['accept'] }}" multiple
                        onchange="golivePreviewFiles('{{ $doc['key'] }}', this)"
                        {{ $isLocked ? 'disabled' : '' }}
                        class="block w-full text-xs text-gray-600 disabled:cursor-not-allowed disabled:opacity-50 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-[10px] text-gray-400 mt-1">{{ $doc['hint'] }}</p>

                    {{-- Preview file yg BARU dipilih (belum di-submit), + tombol hapus
                    pilihan (reset input) tanpa reload halaman. Murni client-side. --}}
                    <div id="preview-{{ $doc['key'] }}" class="hidden mt-2 space-y-1.5"></div>

                    @if($doc['key'] === 'mancore')
                        <div class="mt-3 flex items-center gap-4 text-xs font-bold text-gray-600 dark:text-gray-300">
                            <span>Jenis input Mancore:</span>
                            <label class="inline-flex items-center gap-1">
                                <input type="radio" name="mancore_input_type" value="photo" {{ ($submission?->mancore_input_type ?? 'photo') === 'photo' ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                Foto
                            </label>
                            <label class="inline-flex items-center gap-1">
                                <input type="radio" name="mancore_input_type" value="excel" {{ $submission?->mancore_input_type === 'excel' ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }}>
                                Excel
                            </label>
                        </div>
                    @endif
                </div>
            @endforeach

            @unless($isLocked)
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <button type="submit" class="h-11 rounded-xl border border-blue-200 bg-blue-50 text-sm font-bold text-blue-700 transition hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950/50 dark:text-blue-300">
                        Save Draft
                    </button>
                    <button type="submit" form="golive-submit-form" {{ $docsComplete ? '' : 'disabled' }}
                            class="h-11 rounded-xl bg-blue-600 text-sm font-bold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500 dark:disabled:bg-gray-700 dark:disabled:text-gray-400">
                        Submit
                    </button>
                </div>
                @unless($docsComplete)
                    <p class="text-center text-[11px] font-semibold text-amber-600">Submit akan aktif setelah Capture Valins, PDF ABD & Valid4, File KML, dan Mancore lengkap.</p>
                @endunless
            @endunless
        </form>

        @if($submission?->draft_saved_at)
            <p class="text-[11px] text-gray-400 mt-3">
                Draft terakhir disimpan: {{ $submission->draft_saved_at->format('d M Y H:i') }}
                oleh {{ $submission->draftSavedBy?->name ?? $submission->draftSavedBy?->username ?? '-' }}
            </p>
        @endif
        @if($submission?->submitted_at)
            <p class="mt-1 text-[11px] font-semibold text-blue-600 dark:text-blue-400">
                Disubmit: {{ $submission->submitted_at->format('d M Y H:i') }}
                oleh {{ $submission->submittedBy?->name ?? $submission->submittedBy?->username ?? '-' }}
            </p>
        @endif

        @unless($isLocked)
            <form id="golive-submit-form" method="POST" action="{{ route('admin.evidences.golive.submit', $project->id_project) }}" class="hidden">
                @csrf
            </form>

            @foreach($docs as $doc)
                @foreach($doc['files'] as $fileIndex => $path)
                    <form id="remove-golive-{{ $doc['key'] }}-{{ $fileIndex }}" method="POST" action="{{ route('admin.evidences.golive.remove', $project->id_project) }}" onsubmit="return confirm('Hapus file ini?');" class="hidden">
                        @csrf
                        <input type="hidden" name="key" value="{{ $doc['key'] }}">
                        <input type="hidden" name="path" value="{{ $path }}">
                    </form>
                @endforeach
            @endforeach
        @endunless
    </div>

    {{-- STEP 6: GOLIVE -- status verifikasi SDI (read-only di sisi Admin) --}}
    <div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-200 dark:border-gray-800 p-6 shadow-sm">

        <div class="flex items-center justify-between gap-3 mb-1">
            <h2 class="text-lg font-black text-gray-900 dark:text-white">Step 7 · Golive (Verifikasi SDI)</h2>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $verification?->capture_uim_path ? 'bg-emerald-100 text-emerald-700' : ($isSubmitted ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500') }}">
                {{ $verification?->capture_uim_path ? 'Sudah Golive' : ($isSubmitted ? 'Menunggu SDI' : 'Belum Disubmit') }}
            </span>
        </div>

        @if($verification?->capture_uim_path)
            <p class="text-xs text-gray-500 mb-3">
                Capture UIM sudah diverifikasi oleh {{ $verification->verifiedBy?->name ?? $verification->verifiedBy?->username ?? '-' }}
                pada {{ optional($verification->verified_at)->format('d M Y H:i') }}.
            </p>
            <a href="{{ Storage::url($verification->capture_uim_path) }}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline">Lihat Capture UIM ↗</a>
        @elseif($isSubmitted)
            <p class="text-xs text-gray-500">
                Dokumen sudah dikirim dan dikunci. LOP akan tercatat bergerak pada tahap Golive setelah SDI mengunggah dan memverifikasi Capture UIM.
            </p>
        @else
            <p class="text-xs text-gray-500">Draft FI-OGP belum disubmit sehingga belum masuk antrean verifikasi SDI.</p>
        @endif
    </div>

</div>

<script>
// Revisi (permintaan user): review (preview) & hapus PILIHAN file (belum
// ter-upload) sebelum submit, sekarang mendukung BANYAK file sekaligus per
// kategori (input file pakai atribut `multiple`). golivePreviewFiles()
// merender daftar nama file yg baru dipilih; goliveClearFiles() reset
// <input type=file multiple> supaya user bisa pilih ulang dari awal.
// Ini TIDAK menyentuh file yg sudah tersimpan di server -- itu dihapus via
// form "✕ Hapus" per-item (submit beneran ke server, lihat
// ProjectController::removeGoliveDocument()).
function golivePreviewFiles(key, input) {
    var wrap = document.getElementById('preview-' + key);
    var files = input.files ? Array.from(input.files) : [];

    wrap.innerHTML = '';

    if (files.length === 0) {
        wrap.classList.add('hidden');
        return;
    }

    var clearRow = document.createElement('div');
    clearRow.className = 'flex items-center justify-between gap-2';

    var countLabel = document.createElement('span');
    countLabel.className = 'text-[10px] font-bold text-blue-600 uppercase tracking-wide';
    countLabel.textContent = files.length + ' file dipilih (belum diupload)';
    clearRow.appendChild(countLabel);

    var clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.className = 'text-[10px] font-bold text-red-600 hover:underline';
    clearBtn.textContent = '✕ Batalkan Semua Pilihan';
    clearBtn.onclick = function () { goliveClearFiles(key); };
    clearRow.appendChild(clearBtn);

    wrap.appendChild(clearRow);

    files.forEach(function (file, idx) {
        var row = document.createElement('div');
        row.className = 'flex items-center gap-2 bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900 rounded-xl px-3 py-2';

        var nameEl = document.createElement('span');
        nameEl.className = 'text-xs font-bold text-blue-700 dark:text-blue-300 truncate flex-1';
        nameEl.textContent = (idx + 1) + '. ' + file.name;
        row.appendChild(nameEl);

        wrap.appendChild(row);
    });

    wrap.classList.remove('hidden');
}

function goliveClearFiles(key) {
    var input = document.getElementById('file-' + key);
    input.value = '';
    var wrap = document.getElementById('preview-' + key);
    wrap.innerHTML = '';
    wrap.classList.add('hidden');
}
</script>

@endsection
