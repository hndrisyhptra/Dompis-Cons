@php
    // 1. DATA PROJECT & EVIDENCES
    $evidences = $project->evidences ?? collect();
    $boqItems = $project->boqItems ?? collect();
    
    // Filter BOQ Material (M-)
    $materialBoqItems = $boqItems->filter(fn($boq) => str_starts_with($boq->designator, 'M-'));
    $boqTotal = $materialBoqItems->count();

    // 2. CEK KELENGKAPAN STEP 1 (PERSIAPAN)
    $barangTibaPhotos = $evidences->where('stage', 'persiapan')->where('evidence_type', 'barang_tiba');
    $perizinanPhotos = $evidences->where('stage', 'persiapan')->where('evidence_type', 'perizinan');
    
    $barangTibaUploaded = $barangTibaPhotos->count() > 0;
    $perizinanUploaded = $perizinanPhotos->count() > 0;
    $step1Done = $barangTibaUploaded && $perizinanUploaded;

    // Cek Rejected Step 1
    $step1Rejected = $barangTibaPhotos->where('status', 'rejected')->isNotEmpty() || 
                     $perizinanPhotos->where('status', 'rejected')->isNotEmpty();

    // 3. CEK KELENGKAPAN STEP 2 (INSTALASI)
    $boqUploaded = $materialBoqItems->filter(function ($boq) use ($evidences) {
        return $evidences->where('stage', 'instalasi')
                         ->where('evidence_type', 'progress_boq')
                         ->where('boq_item_id', $boq->id_boq)
                         ->count() > 0;
    })->count();
    
    $step2Done = $boqTotal > 0 && $boqUploaded >= $boqTotal;
    
    // Cek Rejected Step 2
    $step2Rejected = $evidences->where('stage', 'instalasi')->where('status', 'rejected')->isNotEmpty();

    // 4. CEK KELENGKAPAN STEP 3 (PENGUKURAN)
    // Berdasarkan logika lama, Pengukuran Complete mengikuti Instalasi Complete (Bisa disesuaikan jika ada bukti ukur)
    $step3Done = $step2Done; 
    
    // Cek Rejected Step 3 (Misal stage pengukuran)
    $step3Rejected = $evidences->where('stage', 'pengukuran')->where('status', 'rejected')->isNotEmpty();

    // 5. CEK KELENGKAPAN STEP 4 (FINISHING)
    $finishingUploaded = $materialBoqItems->filter(function ($boq) use ($evidences) {
        return $evidences->where('stage', 'finishing')->where('boq_item_id', $boq->id_boq)->count() > 0;
    })->count();
    
    $step4Done = $boqTotal > 0 && $finishingUploaded >= $boqTotal;
    
    // Cek Rejected Step 4
    $step4Rejected = $evidences->where('stage', 'finishing')->where('status', 'rejected')->isNotEmpty();

    // 6. DETEKSI HALAMAN AKTIF & TOMBOL BACK
    $route = Route::currentRouteName();
    
    $isStep1 = $route === 'waspang.projects.show';
    $isStep2 = $route === 'waspang.projects.instalasi';
    $isStep3 = $route === 'waspang.projects.pengukuran'; 
    $isStep4 = $route === 'waspang.projects.finishing'; 

    $backUrl = route('waspang.inbox'); 
    $title = 'Step 1 - Persiapan';

    if ($isStep1) {
        $backUrl = route('waspang.inbox'); 
        $title = 'Step 1 - Persiapan';
    } elseif ($isStep2) {
        $backUrl = route('waspang.projects.show', $project->id_project); 
        $title = 'Step 2 - Instalasi';
    } elseif ($isStep3) {
        $backUrl = route('waspang.projects.instalasi', $project->id_project);
        $title = 'Step 3 - Pengukuran';
    } elseif ($isStep4) {
        $backUrl = route('waspang.projects.pengukuran', $project->id_project); 
        $title = 'Step 4 - Finishing';
    }
@endphp

<div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem]">
    
    {{-- HEADER KEMBALI & JUDUL --}}
    <div class="flex items-center gap-3">
        <a href="{{ $backUrl }}" 
            class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 inline-flex items-center justify-center text-2xl font-medium transition active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <h1 class="text-xl font-bold">{{ $title }}</h1>
    </div>

    {{-- STEPPER PROGRESS BAR (Bebas Klik asalkan Step Sebelumnya Selesai) --}}
    <div class="relative px-2 mt-4 mb-2">
        {{-- Garis Penghubung --}}
        <div class="absolute top-4 left-10 right-10 h-1 bg-blue-300/60 rounded-full"></div>
        
        <div class="relative grid grid-cols-4 text-center">
            
            {{-- STEP 1: PERSIAPAN --}}
            <a href="{{ route('waspang.projects.show', $project->id_project) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shadow-sm transition-all
                    {{ $step1Rejected ? 'bg-red-100 text-red-600 ring-4 ring-red-500/50' : 
                       ($step1Done ? 'bg-green-100 text-green-700' : 
                       ($isStep1 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-400 text-white')) }}">
                    {{ $step1Rejected ? '!' : ($step1Done ? '✓' : '1') }}
                </div>
                <p class="mt-2 text-xs font-bold {{ $step1Rejected ? 'text-red-300' : ($step1Done || $isStep1 ? 'text-white' : 'text-blue-100') }}">Persiapan</p>
            </a>

            {{-- STEP 2: INSTALASI --}}
            @if($step1Done || $isStep2)
                <a href="{{ route('waspang.projects.instalasi', $project->id_project) }}" class="z-10 block transition hover:scale-110">
                    <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shadow-sm transition-all
                        {{ $step2Rejected ? 'bg-red-100 text-red-600 ring-4 ring-red-500/50' : 
                           ($step2Done ? 'bg-green-100 text-green-700' : 
                           ($isStep2 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-400 text-white')) }}">
                        {{ $step2Rejected ? '!' : ($step2Done ? '✓' : '2') }}
                    </div>
                    <p class="mt-2 text-xs font-bold {{ $step2Rejected ? 'text-red-300' : ($step2Done || $isStep2 ? 'text-white' : 'text-blue-100') }}">Instalasi</p>
                </a>
            @else
                <div class="z-10 block opacity-50 cursor-not-allowed">
                    <div class="mx-auto w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center text-sm font-bold">2</div>
                    <p class="mt-2 text-xs font-bold text-blue-200">Instalasi</p>
                </div>
            @endif

            {{-- STEP 3: PENGUKURAN --}}
            @if($step2Done || $isStep3)
                <a href="{{ route('waspang.projects.pengukuran', $project->id_project) }}" class="z-10 block transition hover:scale-110">
                    <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shadow-sm transition-all
                        {{ $step3Rejected ? 'bg-red-100 text-red-600 ring-4 ring-red-500/50' : 
                           ($step3Done ? 'bg-green-100 text-green-700' : 
                           ($isStep3 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-400 text-white')) }}">
                        {{ $step3Rejected ? '!' : ($step3Done ? '✓' : '3') }}
                    </div>
                    <p class="mt-2 text-xs font-bold {{ $step3Rejected ? 'text-red-300' : ($step3Done || $isStep3 ? 'text-white' : 'text-blue-100') }}">Pengukuran</p>
                </a>
            @else
                <div class="z-10 block opacity-50 cursor-not-allowed">
                    <div class="mx-auto w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center text-sm font-bold">3</div>
                    <p class="mt-2 text-xs font-bold text-blue-200">Pengukuran</p>
                </div>
            @endif

            {{-- STEP 4: FINISHING --}}
            @if($step3Done || $isStep4)
                <a href="{{ route('waspang.projects.finishing', $project->id_project) }}" class="z-10 block transition hover:scale-110">
                    <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shadow-sm transition-all
                        {{ $step4Rejected ? 'bg-red-100 text-red-600 ring-4 ring-red-500/50' : 
                           ($step4Done ? 'bg-green-100 text-green-700' : 
                           ($isStep4 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-400 text-white')) }}">
                        {{ $step4Rejected ? '!' : ($step4Done ? '✓' : '4') }}
                    </div>
                    <p class="mt-2 text-xs font-bold {{ $step4Rejected ? 'text-red-300' : ($step4Done || $isStep4 ? 'text-white' : 'text-blue-100') }}">Finishing</p>
                </a>
            @else
                <div class="z-10 block opacity-50 cursor-not-allowed">
                    <div class="mx-auto w-8 h-8 rounded-full bg-blue-400 text-white flex items-center justify-center text-sm font-bold">4</div>
                    <p class="mt-2 text-xs font-bold text-blue-200">Finishing</p>
                </div>
            @endif

        </div>
    </div>
</div>