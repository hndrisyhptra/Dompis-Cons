@php
    // Section AG: user minta stepper Admin disederhanakan jadi 2 warna
    // murni -- HIJAU kalau sudah di-approve Admin, KUNING kalau belum
    // (apapun alasannya: belum diupload sama sekali, sudah diupload tapi
    // belum di-review, atau sedang dilihat/current step). Sebelumnya ada
    // state ke-3 (biru = "sedang dibuka tapi belum di-approve") yang bikin
    // langkah yang belum disetujui TIDAK selalu kuning -- itu yang diubah
    // di sini. Highlight "sedang dibuka" sekarang cuma berupa ring
    // (border), bukan lagi warna isian lingkaran.
    $route = Route::currentRouteName();
    $isStep1 = $route === 'admin.evidences.review.project';
    $isStep2 = $route === 'admin.evidences.review.instalasi';
    $isStep3 = $route === 'admin.evidences.review.pengukuran';
    $isStep4 = $route === 'admin.evidences.review.finishing';
    $isStep5 = $route === 'admin.evidences.review.golive';

    $summary = $project->progressSummary();

    // Hijau = sudah DISETUJUI admin (approval-gated, sama seperti definisi
    // di progressSummary()). Kuning = SEMUA state selain itu.
    $step1Done = $summary['persiapanDone'] ?? false;
    $step2Done = $summary['instalasiDone'] ?? false;
    $step3Done = $summary['pengukuranDone'] ?? false;
    $step4Done = $summary['finishingDone'] ?? false;

    // Step 5 (FI-OGP Golive) & Step 6 (Golive) BUKAN eviden per-item seperti
    // 4 langkah pertama -- "Done" dibaca dari posisi sequence LOP saat ini
    // (effectiveStageSequence, hold/drop-safe), konsisten dgn tracking.blade.php.
    $seq = $summary['effectiveStageSequence'] ?? null;
    $step5Done = $seq !== null && $seq >= 11; // sudah lewat FI-OGP Golive (di Golive)
    $step6Done = (bool) ($project->lop?->is_golive) || ($seq !== null && $seq >= 11);

    // Helper kelas Tailwind: 2 warna murni (emerald/amber) + ring saat
    // sedang dibuka ($isCurrent).
    $stepCircleClass = function (bool $done, bool $isCurrent) {
        $base = $done ? 'bg-emerald-500 text-white' : 'bg-amber-400 text-white';
        $ring = $isCurrent ? ($done ? 'ring-4 ring-emerald-100 dark:ring-emerald-900/30' : 'ring-4 ring-amber-100 dark:ring-amber-900/30') : '';

        return trim($base.' '.$ring);
    };
    $stepLabelClass = fn (bool $done) => $done ? 'text-emerald-600 dark:text-emerald-500' : 'text-amber-500';
@endphp

<div class="bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-200 dark:border-gray-800 p-5 shadow-sm">

    {{-- PROJECT INFO & TOMBOL KEMBALI --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="min-w-0 flex-1">
            <h1 class="text-lg font-black text-gray-900 dark:text-white truncate tracking-tight">
                {{ $project->project_name }}
            </h1>
            <p class="text-xs font-medium text-gray-500 mt-1 flex items-center gap-2 flex-wrap">
                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded-md text-gray-700 dark:text-gray-300">{{ $project->lop?->branch }}</span>
                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 rounded-md text-gray-700 dark:text-gray-300">{{ $project->lop?->sto }}</span>
                <span>Waspang: <strong class="text-gray-700 dark:text-gray-300">{{ optional($project->assignment)->waspang->name ?? '-' }}</strong></span>
            </p>
        </div>
        <a href="{{ route('admin.evidences.approval') }}" class="h-10 px-4 rounded-xl bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 inline-flex items-center justify-center text-xs font-bold text-gray-700 dark:text-gray-300 transition shrink-0">
            ← Kembali ke Inbox
        </a>
    </div>

    {{-- STEPPER PROGRESS (6 tahap: Persiapan/Instalasi/Pengukuran/Finishing/FI-OGP Golive/Golive) --}}
    <div class="mt-6 flex items-center justify-between px-1 overflow-x-auto">

        {{-- STEP 1: PERSIAPAN --}}
        <a href="{{ route('admin.evidences.review.project', $project->id_project) }}" class="flex flex-col items-center w-14 sm:w-16 group transition-all shrink-0">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm transition-all {{ $stepCircleClass($step1Done, $isStep1) }}">
                {{ $step1Done ? '✓' : '1' }}
            </div>
            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-center {{ $stepLabelClass($step1Done) }}">Persiapan</p>
        </a>

        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-800 mx-1 rounded-full min-w-[12px]"></div>

        {{-- STEP 2: INSTALASI --}}
        <a href="{{ route('admin.evidences.review.instalasi', $project->id_project) }}" class="flex flex-col items-center w-14 sm:w-16 group transition-all shrink-0">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm transition-all {{ $stepCircleClass($step2Done, $isStep2) }}">
                {{ $step2Done ? '✓' : '2' }}
            </div>
            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-center {{ $stepLabelClass($step2Done) }}">Instalasi</p>
        </a>

        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-800 mx-1 rounded-full min-w-[12px]"></div>

        {{-- STEP 3: PENGUKURAN --}}
        <a href="{{ route('admin.evidences.review.pengukuran', $project->id_project) }}" class="flex flex-col items-center w-14 sm:w-16 group transition-all shrink-0">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm transition-all {{ $stepCircleClass($step3Done, $isStep3) }}">
                {{ $step3Done ? '✓' : '3' }}
            </div>
            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-center {{ $stepLabelClass($step3Done) }}">Ukur</p>
        </a>

        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-800 mx-1 rounded-full min-w-[12px]"></div>

        {{-- STEP 4: FINISHING --}}
        <a href="{{ route('admin.evidences.review.finishing', $project->id_project) }}" class="flex flex-col items-center w-14 sm:w-16 group transition-all shrink-0">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm transition-all {{ $stepCircleClass($step4Done, $isStep4) }}">
                {{ $step4Done ? '✓' : '4' }}
            </div>
            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-center {{ $stepLabelClass($step4Done) }}">Finish</p>
        </a>

        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-800 mx-1 rounded-full min-w-[12px]"></div>

        {{-- STEP 5: FI-OGP GOLIVE (upload dokumen Admin) --}}
        <a href="{{ route('admin.evidences.review.golive', $project->id_project) }}" class="flex flex-col items-center w-14 sm:w-16 group transition-all shrink-0">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm transition-all {{ $stepCircleClass($step5Done, $isStep5) }}">
                {{ $step5Done ? '✓' : '5' }}
            </div>
            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-center {{ $stepLabelClass($step5Done) }}">FI-OGP</p>
        </a>

        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-800 mx-1 rounded-full min-w-[12px]"></div>

        {{-- STEP 6: GOLIVE (verifikasi SDI, capture UIM) --}}
        <a href="{{ route('admin.evidences.review.golive', $project->id_project) }}" class="flex flex-col items-center w-14 sm:w-16 group transition-all shrink-0">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm transition-all {{ $stepCircleClass($step6Done, false) }}">
                {{ $step6Done ? '✓' : '6' }}
            </div>
            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider text-center {{ $stepLabelClass($step6Done) }}">Golive</p>
        </a>

    </div>
</div>
