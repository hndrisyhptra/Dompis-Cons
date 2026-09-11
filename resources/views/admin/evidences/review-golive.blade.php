@extends('layouts.admin')

@section('content')

@php
    // Section AF: halaman baru Stage 5/6 (FI-OGP Golive + Golive). Admin
    // upload 4 dokumen submission di sini (LopGoliveSubmission); status
    // verifikasi SDI (LopGoliveVerification, capture UIM) ditampilkan
    // read-only -- aksi verifikasi itu sendiri ada di halaman SDI terpisah
    // (sdi.golive.show), bukan di sini, supaya wewenangnya tetap terpisah.
    $lop = $project->lop;
    $submission = $lop?->goliveSubmission;
    $verification = $lop?->goliveVerification;

    $docs = [
        ['key' => 'capture_valins', 'label' => 'Capture Valins', 'path' => $submission?->capture_valins_path, 'accept' => 'image/*', 'hint' => 'Foto (JPG/PNG/WEBP, maks 5MB)'],
        ['key' => 'abd_valid4', 'label' => 'PDF ABD & Valid4', 'path' => $submission?->abd_valid4_path, 'accept' => '.pdf', 'hint' => 'PDF, maks 10MB'],
        ['key' => 'kml', 'label' => 'File KML', 'path' => $submission?->kml_path, 'accept' => '.kml,.xml', 'hint' => 'KML/XML, maks 5MB'],
        ['key' => 'mancore', 'label' => 'Mancore', 'path' => $submission?->mancore_path, 'accept' => 'image/*,.xls,.xlsx', 'hint' => 'Foto ATAU Excel, maks 10MB'],
    ];
    $docsComplete = collect($docs)->every(fn ($d) => filled($d['path']));
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
            <h2 class="text-lg font-black text-gray-900 dark:text-white">Step 5 · FI-OGP Golive</h2>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $docsComplete ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                {{ $docsComplete ? 'Dokumen Lengkap' : 'Belum Lengkap' }}
            </span>
        </div>
        <p class="text-xs text-gray-500 mb-5">
            Upload keempat dokumen ini. Boleh diunggah satu per satu -- begitu keempatnya lengkap dan seluruh eviden Finishing sudah disetujui, LOP otomatis maju ke tahap FI-OGP Golive.
        </p>

        <form method="POST" action="{{ route('admin.evidences.golive.submit', $project->id_project) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            @foreach($docs as $doc)
                <div class="border border-gray-200 dark:border-gray-800 rounded-2xl p-4">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <label class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $doc['label'] }}</label>
                        @if($doc['path'])
                            <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline">Lihat file tersimpan ↗</a>
                        @else
                            <span class="text-xs font-bold text-gray-400">Belum ada file</span>
                        @endif
                    </div>
                    <input type="file" name="{{ $doc['key'] }}" accept="{{ $doc['accept'] }}"
                        class="block w-full text-xs text-gray-600 dark:text-gray-300 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-[10px] text-gray-400 mt-1">{{ $doc['hint'] }}</p>

                    @if($doc['key'] === 'mancore')
                        <div class="mt-3 flex items-center gap-4 text-xs font-bold text-gray-600 dark:text-gray-300">
                            <span>Jenis input Mancore:</span>
                            <label class="inline-flex items-center gap-1">
                                <input type="radio" name="mancore_input_type" value="photo" {{ ($submission?->mancore_input_type ?? 'photo') === 'photo' ? 'checked' : '' }}>
                                Foto
                            </label>
                            <label class="inline-flex items-center gap-1">
                                <input type="radio" name="mancore_input_type" value="excel" {{ $submission?->mancore_input_type === 'excel' ? 'checked' : '' }}>
                                Excel
                            </label>
                        </div>
                    @endif
                </div>
            @endforeach

            <button type="submit" class="w-full h-11 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold transition">
                Simpan Dokumen FI-OGP Golive
            </button>
        </form>

        @if($submission?->submitted_at)
            <p class="text-[11px] text-gray-400 mt-3">
                Terakhir diunggah: {{ $submission->submitted_at->format('d M Y H:i') }}
                oleh {{ $submission->submittedBy?->name ?? $submission->submittedBy?->username ?? '-' }}
            </p>
        @endif
    </div>

    {{-- STEP 6: GOLIVE -- status verifikasi SDI (read-only di sisi Admin) --}}
    <div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-200 dark:border-gray-800 p-6 shadow-sm">

        <div class="flex items-center justify-between gap-3 mb-1">
            <h2 class="text-lg font-black text-gray-900 dark:text-white">Step 6 · Golive (Verifikasi SDI)</h2>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $verification?->capture_uim_path ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $verification?->capture_uim_path ? 'Sudah Golive' : 'Menunggu SDI' }}
            </span>
        </div>

        @if($verification?->capture_uim_path)
            <p class="text-xs text-gray-500 mb-3">
                Capture UIM sudah diverifikasi oleh {{ $verification->verifiedBy?->name ?? $verification->verifiedBy?->username ?? '-' }}
                pada {{ optional($verification->verified_at)->format('d M Y H:i') }}.
            </p>
            <a href="{{ Storage::url($verification->capture_uim_path) }}" target="_blank" class="text-xs font-bold text-blue-600 hover:underline">Lihat Capture UIM ↗</a>
        @else
            <p class="text-xs text-gray-500">
                Tahap ini diselesaikan oleh tim SDI setelah dokumen FI-OGP Golive di atas lengkap. Admin tidak perlu melakukan apa-apa di sini -- LOP akan otomatis Golive begitu SDI mengunggah capture UIM.
        @endif
    </div>

</div>

@endsection
