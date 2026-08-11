@php
    // 1. AMBIL DATA DARI DATABASE BERDASARKAN LOP
    $survey = $lop->surveys()->first();
    // Menggunakan relasi evidences jika sudah di-eager load, atau panggil manual:
    $evidences = $lop->evidences ?? \App\Models\Pt2Evidence::where('pt2_lop_id', $lop->id_pt2_lop)->get();
    
    // 2. DETEKSI REJECTED EVIDENCES PER STEP
    // Cek apakah ada foto dengan status 'rejected' di masing-masing stage
    $step1Rejected = $evidences->where('stage', 'persiapan')->where('status', 'rejected')->isNotEmpty();
    
    $step2Rejected = $evidences->where('stage', 'instalasi')->where('status', 'rejected')->isNotEmpty();
    
    // Step 3 hanya mencari yang tipe redaman atau foto lainnya
    $step3Rejected = $evidences->where('stage', 'finishing')
                               ->whereIn('evidence_type', ['redaman_port', 'foto_lainnya'])
                               ->where('status', 'rejected')
                               ->isNotEmpty();
                               
    // Step 4 mencari yang selain redaman/foto lainnya (Dismantle ODP/Splitter)
    $step4Rejected = $evidences->where('stage', 'finishing')
                               ->whereNotIn('evidence_type', ['redaman_port', 'foto_lainnya'])
                               ->where('status', 'rejected')
                               ->isNotEmpty();

    // 3. CEK KELENGKAPAN DATA (EKSISTENSI)
    $hasSurvey = $survey ? true : false;
    $hasEvSurvey = $evidences->where('stage', 'persiapan')->isNotEmpty();
    $hasEvInstalasi = $evidences->where('stage', 'instalasi')->isNotEmpty();
    $hasEvRedaman = $evidences->where('stage', 'finishing')->whereIn('evidence_type', ['redaman_port', 'foto_lainnya'])->isNotEmpty();
    $hasDismantle = \App\Models\DismantlePt2::where('pt2_lop_id', $lop->id_pt2_lop)->exists();
    
    // 4. LOGIKA CENTANG SELESAI (Syarat: Data Ada & TIDAK ADA YANG DIREJECT)
    $step1Done = $hasSurvey && $hasEvSurvey && !$step1Rejected; 
    $step2Done = $hasEvInstalasi && !$step2Rejected; 
    $step3Done = $hasEvRedaman && !$step3Rejected;
    $step4Done = $hasDismantle && !$step4Rejected; 
    
    // Asumsi Step 5 selesai jika Mancore sudah diinput oleh Teknisi
    $step5Done = \App\Models\MancorePt2::where('pt2_lop_id', $lop->id_pt2_lop)->exists();

    // 5. DETEKSI HALAMAN AKTIF
    $route = Route::currentRouteName();
    $isStep1 = in_array($route, ['teknisi.pt2.step1', 'teknisi.pt2.step1Eviden']);
    $isStep2 = $route === 'teknisi.pt2.step2Eviden';
    $isStep3 = $route === 'teknisi.pt2.step3Eviden'; 
    $isStep4 = $route === 'teknisi.pt2.step4Eviden'; 
    $isStep5 = $route === 'teknisi.pt2.step5';       

    // 6. LOGIKA TOMBOL BACK YANG LEBIH RAPI
    $backUrl = route('teknisi.pt2.inbox'); 
    
    if ($route === 'teknisi.pt2.step1Eviden') {
        $backUrl = route('teknisi.pt2.step1', $lop->id_pt2_lop); 
    } elseif ($route === 'teknisi.pt2.step2Eviden') {
        $backUrl = route('teknisi.pt2.step1Eviden', $lop->id_pt2_lop); 
    } elseif ($route === 'teknisi.pt2.step3Eviden') {
        $backUrl = route('teknisi.pt2.step2Eviden', $lop->id_pt2_lop);
    } elseif ($route === 'teknisi.pt2.step4Eviden') {
        $backUrl = route('teknisi.pt2.step3Eviden', $lop->id_pt2_lop); 
    } elseif ($route === 'teknisi.pt2.step5') {
        $backUrl = route('teknisi.pt2.step4Eviden', $lop->id_pt2_lop); 
    }
@endphp

<div class="bg-blue-700 text-white px-5 pt-6 pb-5 shadow-md rounded-b-[1.7rem]">
    
    {{-- HEADER KEMBALI & JUDUL --}}
    <div class="flex items-center gap-3">
        <a href="{{ $backUrl }}" 
            class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-white text-2xl font-medium backdrop-blur-sm transition active:scale-95">
            ‹
        </a>
        <div>
            <p class="text-[10px] text-blue-200 font-medium uppercase tracking-widest">Progress LOP</p>
            <h1 class="text-lg font-black tracking-tight leading-tight mt-0.5">{{ $title ?? 'Detail Project' }}</h1>
        </div>
    </div>

    {{-- STEPPER PROGRESS BAR (Bebas Klik) --}}
    <div class="relative px-1 mt-6 mb-2">
        {{-- Garis Penghubung --}}
        <div class="absolute top-3.5 left-6 right-6 h-1 bg-blue-400/50 rounded-full"></div>
        
        <div class="relative grid grid-cols-5 text-center">
            
            {{-- STEP 1: SURVEY --}}
            <a href="{{ route('teknisi.pt2.step1', $lop->id_pt2_lop) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm
                    {{ $step1Rejected ? 'bg-red-100 text-red-600 ring-4 ring-red-500' : 
                       ($step1Done ? 'bg-green-100 text-green-700' : 
                       ($isStep1 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-800 border-2 border-blue-400 text-blue-200')) }}">
                    {{ $step1Rejected ? '!' : ($step1Done ? '✓' : '1') }}
                </div>
                <p class="mt-2 text-[9px] font-bold {{ $step1Rejected ? 'text-red-300' : ($step1Done || $isStep1 ? 'text-white' : 'text-blue-300') }}">Survey</p>
            </a>

            {{-- STEP 2: PROGRESS --}}
            <a href="{{ route('teknisi.pt2.step2Eviden', $lop->id_pt2_lop) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm
                    {{ $step2Rejected ? 'bg-red-100 text-red-600 ring-4 ring-red-500' : 
                       ($step2Done ? 'bg-green-100 text-green-700' : 
                       ($isStep2 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-800 border-2 border-blue-400 text-blue-200')) }}">
                    {{ $step2Rejected ? '!' : ($step2Done ? '✓' : '2') }}
                </div>
                <p class="mt-2 text-[9px] font-bold {{ $step2Rejected ? 'text-red-300' : ($step2Done || $isStep2 ? 'text-white' : 'text-blue-300') }}">Progress</p>
            </a>

            {{-- STEP 3: FINISH --}}
            <a href="{{ route('teknisi.pt2.step3Eviden', $lop->id_pt2_lop) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm
                    {{ $step3Rejected ? 'bg-red-100 text-red-600 ring-4 ring-red-500' : 
                       ($step3Done ? 'bg-green-100 text-green-700' : 
                       ($isStep3 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-800 border-2 border-blue-400 text-blue-200')) }}">
                    {{ $step3Rejected ? '!' : ($step3Done ? '✓' : '3') }}
                </div>
                <p class="mt-2 text-[9px] font-bold {{ $step3Rejected ? 'text-red-300' : ($step3Done || $isStep3 ? 'text-white' : 'text-blue-300') }}">Finish</p>
            </a>

            {{-- STEP 4: UKUR (DISMANTLE) --}}
            <a href="{{ route('teknisi.pt2.step4Eviden', $lop->id_pt2_lop) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm
                    {{ $step4Rejected ? 'bg-red-100 text-red-600 ring-4 ring-red-500' : 
                       ($step4Done ? 'bg-green-100 text-green-700' : 
                       ($isStep4 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-800 border-2 border-blue-400 text-blue-200')) }}">
                    {{ $step4Rejected ? '!' : ($step4Done ? '✓' : '4') }}
                </div>
                <p class="mt-2 text-[9px] font-bold {{ $step4Rejected ? 'text-red-300' : ($step4Done || $isStep4 ? 'text-white' : 'text-blue-300') }}">Dismantle</p>
            </a>

            {{-- STEP 5: SUBMIT --}}
            <a href="{{ route('teknisi.pt2.step5', $lop->id_pt2_lop) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm
                    {{ $step5Done ? 'bg-green-100 text-green-700' : 
                       ($isStep5 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-800 border-2 border-blue-400 text-blue-200') }}">
                    {{ $step5Done ? '✓' : '5' }}
                </div>
                <p class="mt-2 text-[9px] font-bold {{ $step5Done || $isStep5 ? 'text-white' : 'text-blue-300' }}">Mancore</p>
            </a>

        </div>
    </div>
</div>