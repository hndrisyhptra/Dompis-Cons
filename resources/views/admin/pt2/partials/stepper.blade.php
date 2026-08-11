<div class="mt-5 flex items-center justify-between">
    @php
        // Pastikan variabel $lop tersedia (di-inject dari blade parent)
        $evidences = $lop->evidences ?? collect();
        $survey = $lop->surveys()->first(); 

        // =========================================================
        // STEP 1: Survey & Persiapan
        // =========================================================
        $evPersiapan = $evidences->where('stage', 'persiapan');
        $isSurveyRejected = $survey && $survey->pm_approval_status === 'rejected';
        $isEv1Rejected = $evPersiapan->where('status', 'rejected')->count() > 0;
        
        $step1Rejected = $isSurveyRejected || $isEv1Rejected;
        
        $hasSurveyApproved = $survey && $survey->pm_approval_status === 'approved';
        $step1EvApproved = $evPersiapan->count() > 0 && $evPersiapan->where('status', 'approved')->count() == $evPersiapan->count();
        
        $step1Done = $hasSurveyApproved && $step1EvApproved && !$step1Rejected;

        // =========================================================
        // STEP 2: Instalasi
        // =========================================================
        $evInstalasi = $evidences->where('stage', 'instalasi');
        $step2Rejected = $evInstalasi->where('status', 'rejected')->count() > 0;
        $step2Done = $evInstalasi->count() > 0 && $evInstalasi->where('status', 'approved')->count() == $evInstalasi->count() && !$step2Rejected;

        // =========================================================
        // STEP 3: Redaman & Foto Lainnya
        // =========================================================
        $evStep3 = $evidences->where('stage', 'finishing')->whereIn('evidence_type', ['redaman_port', 'foto_lainnya']);
        $step3Rejected = $evStep3->where('status', 'rejected')->count() > 0;
        $step3Done = $evStep3->count() > 0 && $evStep3->where('status', 'approved')->count() == $evStep3->count() && !$step3Rejected;

        // =========================================================
        // STEP 4: Dismantle (Otomatis Ceklis jika tidak ada eviden)
        // =========================================================
        // Cek eviden finishing SELAIN redaman & foto lainnya (berarti itu eviden dismantle)
        $evStep4 = $evidences->where('stage', 'finishing')->whereNotIn('evidence_type', ['redaman_port', 'foto_lainnya']);
        $step4Rejected = $evStep4->where('status', 'rejected')->count() > 0;
        
        if ($evStep4->count() > 0) {
            // Jika ADA eviden dismantle, harus tunggu di-approve semua
            $step4Done = $evStep4->where('status', 'approved')->count() == $evStep4->count() && !$step4Rejected;
        } else {
            // Jika TIDAK ADA eviden dismantle yang diupload, otomatis Selesai (karena opsional)
            $step4Done = true;
        }

        // =========================================================
        // STEP 5: Mancore
        // =========================================================
        $mancoreExists = \App\Models\MancorePt2::where('pt2_lop_id', $lop->id_pt2_lop)->exists();
        $step5Rejected = false; // Mancore tidak ada eviden foto, jadi tidak ada reject foto
        $step5Done = $mancoreExists;

        // Susunan Array Step
        $steps = [
            1 => ['label' => 'Survey', 'route' => 'admin.pt2.review', 'done' => $step1Done, 'rejected' => $step1Rejected],
            2 => ['label' => 'Instalasi', 'route' => 'admin.pt2.instalasi', 'done' => $step2Done, 'rejected' => $step2Rejected],
            3 => ['label' => 'Redaman', 'route' => 'admin.pt2.redaman', 'done' => $step3Done, 'rejected' => $step3Rejected],
            4 => ['label' => 'Dismantle', 'route' => 'admin.pt2.dismantle', 'done' => $step4Done, 'rejected' => $step4Rejected],
            5 => ['label' => 'Mancore', 'route' => 'admin.pt2.mancore', 'done' => $step5Done, 'rejected' => $step5Rejected],
        ];
    @endphp

    @foreach($steps as $num => $step)
        @php
            // MENENTUKAN KELAS CSS BERDASARKAN STATUS (REJECT / DONE / PENDING)
            if ($step['rejected']) {
                $circleClass = 'border-rose-500 bg-rose-50 text-rose-600 dark:bg-rose-900/50 dark:text-rose-400 shadow-sm shadow-rose-200 dark:shadow-none';
                $textClass = 'text-rose-600 dark:text-rose-400';
                $lineClass = 'bg-rose-300 dark:bg-rose-800/50';
            } elseif ($step['done']) {
                $circleClass = 'border-emerald-500 bg-emerald-50 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400 shadow-sm';
                $textClass = 'text-emerald-600 dark:text-emerald-400';
                $lineClass = 'bg-emerald-300 dark:bg-emerald-800/50';
            } else {
                if ($currentStep == $num) {
                    $circleClass = 'border-cyan-600 bg-cyan-50 text-cyan-700 dark:bg-cyan-900/50 dark:text-cyan-400 shadow-sm';
                    $textClass = 'text-cyan-700 dark:text-cyan-400';
                } else {
                    $circleClass = 'border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400 group-hover:border-gray-400';
                    $textClass = 'text-gray-500 dark:text-gray-400';
                }
                $lineClass = 'bg-gray-200 dark:bg-gray-800';
            }
        @endphp

        <a href="{{ route($step['route'], $lop->id_pt2_lop) }}"
           class="flex flex-col items-center w-16 group {{ $currentStep == $num ? '' : 'opacity-60 hover:opacity-100 transition-opacity' }}">
            
            <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition-all {{ $circleClass }}">
                
                @if($step['rejected'])
                    {{-- Icon Silang/Tanda Seru jika ada Reject --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                @elseif($step['done'])
                    {{-- Icon Centang jika Approved --}}
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                @else
                    {{-- Angka Step jika belum diproses --}}
                    {{ $num }}
                @endif

            </div>
            
            <p class="mt-1.5 text-[10px] font-bold text-center leading-tight {{ $textClass }}">
                {{ $step['label'] }}
            </p>
        </a>

        @if(!$loop->last)
            <div class="flex-1 h-0.5 mx-1 -mt-5 transition-colors {{ $lineClass }}"></div>
        @endif
    @endforeach
</div>

