@php
    // 1. AMBIL DATA DARI DATABASE
    $survey = $project->pt2Survey;
    $evidences = \App\Models\Evidence::where('project_id', $project->id_project)->get();
    
    // 2. CEK KELENGKAPAN PER STEP YANG BENAR
    $hasSurvey = $survey ? true : false;
    
    // Step 1: Eviden Persiapan
    $hasEvSurvey = $evidences->where('stage', 'persiapan')->count() > 0;
    
    // Step 2: Eviden Instalasi
    $hasEvInstalasi = $evidences->where('stage', 'instalasi')->count() > 0;
    
    // Step 3: Eviden Redaman (Disimpan di stage finishing dengan tipe redaman_port)
    $hasEvRedaman = $evidences->where('stage', 'finishing')->where('evidence_type', 'redaman_port')->count() > 0;
    
    // Step 4: Cek apakah ada record di tabel dismantles
    $hasDismantle = \Illuminate\Support\Facades\DB::table('dismantles')->where('project_id', $project->id_project)->exists();
    
    // 3. LOGIKA CENTANG
    $step1Done = $hasSurvey && $hasEvSurvey; 
    $step2Done = $hasEvInstalasi; 
    $step3Done = $hasEvRedaman;
    $step4Done = $hasDismantle; 
    $step5Done = $project->status_project === 'pending_approval' || $project->is_golive; 

    // 4. DETEKSI HALAMAN AKTIF
    $route = Route::currentRouteName();
    $isStep1 = in_array($route, ['teknisi.pt2.step1', 'teknisi.pt2.step1Eviden']);
    $isStep2 = $route === 'teknisi.pt2.step2Eviden';
    $isStep3 = $route === 'teknisi.pt2.step3Eviden'; 
    $isStep4 = $route === 'teknisi.pt2.step4Eviden'; 
    $isStep5 = $route === 'teknisi.pt2.step5';       

    // 5. LOGIKA TOMBOL BACK
    $backUrl = route('teknisi.pt2.inbox'); 
    
    if ($route === 'teknisi.pt2.step1Eviden') {
        $backUrl = route('teknisi.pt2.step1', $project->id_project); 
    } elseif ($route === 'teknisi.pt2.step2Eviden') {
        $backUrl = route('teknisi.pt2.step1Eviden', $project->id_project); 
    } elseif ($route === 'teknisi.pt2.step3Eviden') {
        $backUrl = route('teknisi.pt2.step2Eviden', $project->id_project);
    } elseif ($route === 'teknisi.pt2.step4Eviden') {
        $backUrl = url('teknisi/pt2/survey/'.$project->id_project.'/step3'); 
    } elseif ($route === 'teknisi.pt2.step5') {
        $backUrl = url('teknisi/pt2/survey/'.$project->id_project.'/step4'); 
    }
@endphp

<div class="bg-blue-700 text-white px-5 pt-6 pb-5 shadow-md rounded-b-[1.7rem]">
    
    {{-- HEADER KEMBALI & JUDUL --}}
    <div class="flex items-center gap-3">
        {{-- TOMBOL BACK DINAMIS --}}
        <a href="{{ $backUrl }}" 
            class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-white text-2xl font-medium backdrop-blur-sm transition active:scale-95">
            ‹
        </a>
        <div>
            <p class="text-[10px] text-blue-200 font-medium uppercase tracking-widest">Project Progress</p>
            <h1 class="text-lg font-black tracking-tight leading-tight mt-0.5">{{ $title ?? 'Detail Project' }}</h1>
        </div>
    </div>

    {{-- STEPPER PROGRESS BAR (Bebas Klik) --}}
    <div class="relative px-1 mt-6 mb-2">
        {{-- Garis Penghubung --}}
        <div class="absolute top-3.5 left-6 right-6 h-1 bg-blue-400/50 rounded-full"></div>
        
        <div class="relative grid grid-cols-5 text-center">
            
            {{-- STEP 1: SURVEY --}}
            <a href="{{ route('teknisi.pt2.step1', $project->id_project) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm
                    {{ $step1Done ? 'bg-green-100 text-green-700' : ($isStep1 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-800 border-2 border-blue-400 text-blue-200') }}">
                    {{ $step1Done ? '✓' : '1' }}
                </div>
                <p class="mt-2 text-[9px] font-bold {{ $step1Done || $isStep1 ? 'text-white' : 'text-blue-300' }}">Survey</p>
            </a>

            {{-- STEP 2: PROGRESS --}}
            <a href="{{ route('teknisi.pt2.step2Eviden', $project->id_project) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm
                    {{ $step2Done ? 'bg-green-100 text-green-700' : ($isStep2 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-800 border-2 border-blue-400 text-blue-200') }}">
                    {{ $step2Done ? '✓' : '2' }}
                </div>
                <p class="mt-2 text-[9px] font-bold {{ $step2Done || $isStep2 ? 'text-white' : 'text-blue-300' }}">Progress</p>
            </a>

            {{-- STEP 3: FINISH --}}
            <a href="{{ route('teknisi.pt2.step3Eviden', $project->id_project) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm
                    {{ $step3Done ? 'bg-green-100 text-green-700' : ($isStep3 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-800 border-2 border-blue-400 text-blue-200') }}">
                    {{ $step3Done ? '✓' : '3' }}
                </div>
                <p class="mt-2 text-[9px] font-bold {{ $step3Done || $isStep3 ? 'text-white' : 'text-blue-300' }}">Finish</p>
            </a>

            {{-- STEP 4: UKUR --}}
            <a href="{{ route('teknisi.pt2.step4Eviden', $project->id_project) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm
                    {{ $step4Done ? 'bg-green-100 text-green-700' : ($isStep4 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-800 border-2 border-blue-400 text-blue-200') }}">
                    {{ $step4Done ? '✓' : '4' }}
                </div>
                <p class="mt-2 text-[9px] font-bold {{ $step4Done || $isStep4 ? 'text-white' : 'text-blue-300' }}">Ukur</p>
            </a>

            {{-- STEP 5: SUBMIT --}}
            <a href="{{ route('teknisi.pt2.step5', $project->id_project) }}" class="z-10 block transition hover:scale-110">
                <div class="mx-auto w-8 h-8 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-sm
                    {{ $step5Done ? 'bg-green-100 text-green-700' : ($isStep5 ? 'bg-white text-blue-700 ring-4 ring-blue-700' : 'bg-blue-800 border-2 border-blue-400 text-blue-200') }}">
                    {{ $step5Done ? '✓' : '5' }}
                </div>
                <p class="mt-2 text-[9px] font-bold {{ $step5Done || $isStep5 ? 'text-white' : 'text-blue-300' }}">Submit</p>
            </a>

        </div>
    </div>
</div>