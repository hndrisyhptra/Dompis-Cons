@php
    $route = Route::currentRouteName();
    $isStep1 = $route === 'admin.evidences.review.project';
    $isStep2 = $route === 'admin.evidences.review.instalasi';
    $isStep3 = $route === 'admin.evidences.review.pengukuran';
    $isStep4 = $route === 'admin.evidences.review.finishing';

    $evidences = $project->evidences ?? collect();
    $boqItems = $project->boqItems ?? collect();

    // ---------------------------------------------------------
    // 1. LOGIKA STEP 1 (PERSIAPAN)
    // ---------------------------------------------------------
    $barangTiba = $evidences->where('stage', 'persiapan')->where('evidence_type', 'barang_tiba');
    $perizinan = $evidences->where('stage', 'persiapan')->where('evidence_type', 'perizinan');
    
    // Selesai jika kedua jenis eviden di-upload, dan semuanya berstatus 'approved'
    $step1Done = $barangTiba->count() > 0 && $perizinan->count() > 0 &&
                 $barangTiba->where('status', 'approved')->count() == $barangTiba->count() &&
                 $perizinan->where('status', 'approved')->count() == $perizinan->count();

    // ---------------------------------------------------------
    // 2. LOGIKA STEP 2 & 4 (INSTALASI & FINISHING)
    // ---------------------------------------------------------
    // Hitung total BOQ material
    $materialBoqItems = $boqItems->filter(function ($boq) {
        return str_starts_with($boq->designator, 'M-') || optional($boq->designatorData)->type === 'material';
    });
    $boqTotal = $materialBoqItems->count();
    
    $boqInstalasiApproved = 0;
    $boqFinishingApproved = 0;
    
    foreach ($materialBoqItems as $boq) {
        // Cek foto instalasi per BOQ
        $instPhotos = $evidences->where('stage', 'instalasi')->where('boq_item_id', $boq->id_boq);
        if ($instPhotos->count() > 0 && $instPhotos->where('status', 'approved')->count() == $instPhotos->count()) {
            $boqInstalasiApproved++;
        }
        
        // Cek foto finishing per BOQ
        $finPhotos = $evidences->where('stage', 'finishing')->where('boq_item_id', $boq->id_boq);
        if ($finPhotos->count() > 0 && $finPhotos->where('status', 'approved')->count() == $finPhotos->count()) {
            $boqFinishingApproved++;
        }
    }
    
    // Selesai jika semua item BOQ Material sudah memiliki eviden approved (atau jika tidak ada BOQ sama sekali)
    $step2Done = $boqTotal === 0 || $boqInstalasiApproved >= $boqTotal;
    $step4Done = $boqTotal === 0 || $boqFinishingApproved >= $boqTotal;

    // ---------------------------------------------------------
    // 3. LOGIKA STEP 3 (PENGUKURAN) -> OPSIONAL
    // ---------------------------------------------------------
    $pengukuranEvidences = $evidences->where('stage', 'pengukuran');
    
    // Karena opsional, DEFAULT statusnya adalah TRUE (Selesai).
    $step3Done = true; 
    
    // NAMUN, JIKA Waspang meng-upload foto pengukuran, kita harus pastikan semuanya di-approve.
    // Jika ada yang pending/rejected, maka status selesainya dicabut sementara.
    if ($pengukuranEvidences->count() > 0) {
        $step3Done = $pengukuranEvidences->where('status', 'approved')->count() == $pengukuranEvidences->count();
    }
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

    {{-- STEPPER PROGRESS --}}
    <div class="mt-6 flex items-center justify-between px-2">
        
        {{-- STEP 1: PERSIAPAN --}}
        <a href="{{ route('admin.evidences.review.project', $project->id_project) }}" class="flex flex-col items-center w-16 group transition-all">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm transition-all
                {{ $step1Done ? 'bg-emerald-500 text-white' : ($isStep1 ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-500') }}
                {{ $isStep1 ? ($step1Done ? 'ring-4 ring-emerald-100 dark:ring-emerald-900/30' : 'ring-4 ring-blue-100 dark:ring-blue-900/30') : '' }}">
                {{ $step1Done ? '✓' : '1' }}
            </div>
            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider {{ $step1Done ? 'text-emerald-600 dark:text-emerald-500' : ($isStep1 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400') }}">Persiapan</p>
        </a>

        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-800 mx-2 rounded-full"></div>

        {{-- STEP 2: INSTALASI --}}
        <a href="{{ route('admin.evidences.review.instalasi', $project->id_project) }}" class="flex flex-col items-center w-16 group transition-all">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm transition-all
                {{ $step2Done ? 'bg-emerald-500 text-white' : ($isStep2 ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-500') }}
                {{ $isStep2 ? ($step2Done ? 'ring-4 ring-emerald-100 dark:ring-emerald-900/30' : 'ring-4 ring-blue-100 dark:ring-blue-900/30') : '' }}">
                {{ $step2Done ? '✓' : '2' }}
            </div>
            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider {{ $step2Done ? 'text-emerald-600 dark:text-emerald-500' : ($isStep2 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400') }}">Instalasi</p>
        </a>

        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-800 mx-2 rounded-full"></div>

        {{-- STEP 3: PENGUKURAN --}}
        <a href="{{ route('admin.evidences.review.pengukuran', $project->id_project) }}" class="flex flex-col items-center w-16 group transition-all">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm transition-all
                {{ $step3Done ? 'bg-emerald-500 text-white' : ($isStep3 ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-500') }}
                {{ $isStep3 ? ($step3Done ? 'ring-4 ring-emerald-100 dark:ring-emerald-900/30' : 'ring-4 ring-blue-100 dark:ring-blue-900/30') : '' }}">
                {{ $step3Done ? '✓' : '3' }}
            </div>
            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider {{ $step3Done ? 'text-emerald-600 dark:text-emerald-500' : ($isStep3 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400') }}">Ukur</p>
        </a>

        <div class="flex-1 h-0.5 bg-gray-200 dark:bg-gray-800 mx-2 rounded-full"></div>

        {{-- STEP 4: FINISHING --}}
        <a href="{{ route('admin.evidences.review.finishing', $project->id_project) }}" class="flex flex-col items-center w-16 group transition-all">
            <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-black shadow-sm transition-all
                {{ $step4Done ? 'bg-emerald-500 text-white' : ($isStep4 ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-500') }}
                {{ $isStep4 ? ($step4Done ? 'ring-4 ring-emerald-100 dark:ring-emerald-900/30' : 'ring-4 ring-blue-100 dark:ring-blue-900/30') : '' }}">
                {{ $step4Done ? '✓' : '4' }}
            </div>
            <p class="mt-2 text-[10px] font-bold uppercase tracking-wider {{ $step4Done ? 'text-emerald-600 dark:text-emerald-500' : ($isStep4 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400') }}">Finish</p>
        </a>
        
    </div>
</div>