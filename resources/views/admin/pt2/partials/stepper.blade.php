<div class="mt-5 flex items-center justify-between">
    @php
        // LOGIKA PENGECEKAN STATUS PER STEP (Global untuk semua halaman)
        $evidences = $project->evidences ?? collect();
        $survey = $project->pt2Survey; // Ambil relasi survey secara aman

        // Deteksi Reject per step
        $step1Rejected = $evidences->where('stage', 'persiapan')->where('status', 'rejected')->isNotEmpty();
        
        // Step 1: Survey & Persiapan
        $hasSurveyApproved = $survey && $survey->pm_approval_status === 'approved';
        $evPersiapan = $evidences->where('stage', 'persiapan');
        $step1EvApproved = $evPersiapan->count() > 0 && $evPersiapan->where('status', 'approved')->count() == $evPersiapan->count();
        
        $step1Done = $hasSurveyApproved && $step1EvApproved && !$step1Rejected;

        // Step 2: Instalasi
        $evInstalasi = $evidences->where('stage', 'instalasi');
        $step2Done = $evInstalasi->count() > 0 && $evInstalasi->where('status', 'approved')->count() == $evInstalasi->count();

        // Step 3: Redaman & Foto Lainnya (Spesifik evidence_type agar tidak tercampur Dismantle)
        $evStep3 = $evidences->where('stage', 'finishing')->whereIn('evidence_type', ['redaman_port', 'foto_lainnya']);
        $step3Done = $evStep3->count() > 0 && $evStep3->where('status', 'approved')->count() == $evStep3->count();

        // Step 4: Dismantle
        $step4Done = \Illuminate\Support\Facades\DB::table('dismantles')->where('project_id', $project->id_project)->exists();

        // Step 5: Mancore
        $step5Done = \Illuminate\Support\Facades\DB::table('pt2_mancores')->where('project_id', $project->id_project)->exists();

        $steps = [
            1 => ['label' => 'Survey', 'route' => 'admin.pt2.review', 'done' => $step1Done],
            2 => ['label' => 'Instalasi', 'route' => 'admin.pt2.instalasi', 'done' => $step2Done],
            3 => ['label' => 'Redaman', 'route' => 'admin.pt2.redaman', 'done' => $step3Done],
            4 => ['label' => 'Dismantle', 'route' => 'admin.pt2.dismantle', 'done' => $step4Done],
            5 => ['label' => 'Mancore', 'route' => 'admin.pt2.mancore', 'done' => $step5Done],
        ];
    @endphp

    @foreach($steps as $num => $step)
        <a href="{{ route($step['route'], $project->id_project) }}"
           class="flex flex-col items-center w-16 group {{ $currentStep == $num ? '' : 'opacity-60 hover:opacity-100 transition-opacity' }}">
            
            <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition-all
                {{ $step['done'] 
                    ? 'border-emerald-500 bg-emerald-50 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400 shadow-sm' 
                    : ($currentStep == $num 
                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-400 shadow-sm' 
                        : 'border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400 group-hover:border-gray-400') }}">
                
                {{-- Jika step sudah selesai/approved, tampilkan centang. Jika belum, tampilkan angka --}}
                @if($step['done'])
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                @else
                    {{ $num }}
                @endif
            </div>
            
            <p class="mt-1.5 text-[10px] font-bold text-center leading-tight
                {{ $step['done'] 
                    ? 'text-emerald-600 dark:text-emerald-400' 
                    : ($currentStep == $num ? 'text-indigo-700 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400') }}">
                {{ $step['label'] }}
            </p>
        </a>

        @if(!$loop->last)
            <div class="flex-1 h-0.5 mx-1 -mt-5 transition-colors
                {{ $step['done'] ? 'bg-emerald-300 dark:bg-emerald-800/50' : 'bg-gray-200 dark:bg-gray-800' }}"></div>
        @endif
    @endforeach
</div>