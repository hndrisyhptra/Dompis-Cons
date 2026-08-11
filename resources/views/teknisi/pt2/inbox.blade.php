@extends('layouts.teknisi')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans selection:bg-blue-500 selection:text-white">

    {{-- ALERT NOTIFIKASI SYSTEM --}}
    @if(session('success'))
        <div class="mx-4 mt-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-xs font-bold shadow-xs">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mx-4 mt-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 text-xs font-bold shadow-xs">
            {{ session('error') }}
        </div>
    @endif

    {{-- HEADER --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem] shadow-md">
        <div class="flex items-center gap-3">
            <a href="{{ route('teknisi.pt2.index') }}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 inline-flex items-center justify-center text-2xl font-medium transition active:scale-95">
                ‹
            </a>
            <div>
                <h1 class="text-xl font-black tracking-tight">Inbox LOP PT 2</h1>
                <p class="text-xs text-blue-100 mt-0.5">{{ $assignedLops->count() }} LOP di Assign ke Anda</p>
            </div>
        </div>
    </div>

    {{-- SEARCH BAR --}}
    <div class="px-4 mt-4">
        <form method="GET" action="{{ route('teknisi.pt2.inbox') }}">
            <div class="relative">
                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari LOP, STO, branch, PID..."
                    class="w-full h-11 rounded-2xl border border-slate-200 bg-white pl-10 pr-4 text-xs font-bold shadow-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none transition">
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                    🔍
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARDS PROJECT LOP --}}
    <div class="px-4 mt-4 space-y-4">
        @forelse($assignedLops as $lop)
           @php
                // MENGGUNAKAN QUERY LANGSUNG AGAR DATA TIDAK TUMPANG TINDIH ANTAR LOP
                $survey = \App\Models\SurveyPt2::where('pt2_lop_id', $lop->id_pt2_lop)->first();
                $mancore = \App\Models\MancorePt2::where('pt2_lop_id', $lop->id_pt2_lop)->first();
                $evidences = \App\Models\Pt2Evidence::where('pt2_lop_id', $lop->id_pt2_lop)->get();
                $project = $lop->project;

                // DETEKSI PROGRESS MURNI PER LOP
                $step1Done = $survey ? true : false;
                $step2Done = $evidences->where('stage', 'instalasi')->count() > 0; 
                $step3Done = $evidences->where('stage', 'finishing')->where('evidence_type', 'redaman_port')->count() > 0;
                $step4Done = \App\Models\DismantlePt2::where('pt2_lop_id', $lop->id_pt2_lop)->exists(); 
                $step5Done = $mancore ? true : false;

                // Cek kendala (hanya dianggap kendala jika PM belum approve)
                $isKendala = $survey && $survey->has_kendala == 1 && $survey->pm_approval_status !== 'approved';

                // HITUNG PERSENTASE MURNI BERDASARKAN KERJAAN TEKNISI PER LOP
                $doneStep = 0;
                if ($step1Done) $doneStep++;
                if ($step2Done) $doneStep++;
                if ($step3Done) $doneStep++;
                if ($step4Done) $doneStep++;
                
                // Jika Step 5 (Mancore) selesai, LOP otomatis 100% (karena step 4 dismantle opsional)
                if ($step5Done) {
                    $progress = 100;
                    $allStepDone = true;
                    $step4Done = true; // Paksa UI centang hijau untuk step 4 agar rapi
                } else {
                    $progress = round(($doneStep / 5) * 100);
                    $allStepDone = false;
                }

                // ==============================================================
                // STATUS WORKFLOW LOP PT2
                // ==============================================================

                $isWaitingAdmin = false;
                $isWaitingSdi = false;
                $isGoLive = false;

                /*
                |--------------------------------------------------------------------------
                | 1. GO LIVE
                |--------------------------------------------------------------------------
                | Jika LOP sudah ditandai Go Live, status final.
                | Prioritas tertinggi.
                */
                if ((int) $lop->is_golive === 1) {

                    $isGoLive = true;

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | 2. SEMUA STEP SUDAH SELESAI
                    |--------------------------------------------------------------------------
                    | Setelah Step 5 submit, LOP menunggu approval Admin.
                    */
                    if ($allStepDone) {

                        /*
                        |--------------------------------------------------------------------------
                        | Jika SDI approval sudah pending
                        | berarti Admin sudah approve dan sudah dikirim ke SDI.
                        |--------------------------------------------------------------------------
                        */
                        if (
                            $lop->sdi_approval_status &&
                            strtolower($lop->sdi_approval_status) === 'pending'
                        ) {

                            $isWaitingSdi = true;

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | Belum masuk SDI = menunggu Approval Admin
                            |--------------------------------------------------------------------------
                            */
                            $isWaitingAdmin = true;
                        }
                    }
                }

                // DYNAMIC TEMPLATE DESIGN STYLES
                $borderColor = $allStepDone ? 'border-l-emerald-600' : ($isKendala ? 'border-l-rose-500' : 'border-l-blue-600');
                $progressColor = $allStepDone ? 'bg-emerald-500' : ($isKendala ? 'bg-rose-500' : 'bg-blue-600');
                $lastUpdate = $lop->updated_at;

                // LOGIKA TOMBOL DINAMIS NEXT STEP
                $nextStepUrl = '';
                $nextStepText = '';
                
                if (!$step2Done) {
                    $nextStepUrl = route('teknisi.pt2.step2Eviden', $lop->id_pt2_lop);
                    $nextStepText = 'Lanjut Step 2 →';
                } elseif (!$step3Done) {
                    $nextStepUrl = route('teknisi.pt2.step3Eviden', $lop->id_pt2_lop);
                    $nextStepText = 'Lanjut Step 3 →';
                } else {
                    $nextStepUrl = route('teknisi.pt2.step4Eviden', $lop->id_pt2_lop);
                    $nextStepText = 'Lanjut Step 4/5 →';
                }

                // ==============================================================
                // STATUS BADGE
                // ==============================================================

                $badgeClass = 'bg-amber-100 text-amber-700';
                $badgeText = 'On Progress';

                if ($isGoLive) {

                    $badgeClass = 'bg-emerald-100 text-emerald-700';
                    $badgeText = 'GO LIVE';

                } elseif ($isWaitingSdi) {

                    $badgeClass = 'bg-indigo-100 text-indigo-700';
                    $badgeText = 'Menunggu Approve SDI';

                } elseif ($isWaitingAdmin) {

                    $badgeClass = 'bg-blue-100 text-blue-700';
                    $badgeText = 'Menunggu Approve Admin';

                } elseif ($isKendala) {

                    $badgeClass = 'bg-rose-100 text-rose-700';
                    $badgeText = 'Terkendala';
                }
            @endphp

            <div class="bg-white border border-slate-100 border-l-[4px] {{ $borderColor }} rounded-3xl p-4 shadow-xs relative overflow-hidden">
                
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-slate-800 tracking-tight leading-tight">
                            {{ $lop->lop_name }}
                        </h2>
                        <p class="text-[11px] text-slate-400 font-bold mt-1">
                            PID: {{ $project->pid ?? '-' }} · STO {{ $lop->sto ?? '-' }} · IHLD: {{ $lop->id_ihld ?? '-' }}
                        </p>
                    </div>

                    <span class="shrink-0 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wide {{ $badgeClass }}">
                        {{ $badgeText }}
                    </span>
                </div>

                {{-- STEPPER BADGES KHUSUS PT 2 --}}
                <div class="flex flex-wrap gap-1 mt-3.5 text-[9px] font-extrabold">
                    <span class="px-2 py-0.5 rounded-md border {{ $step1Done ? 'bg-blue-50 border-blue-200 text-blue-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $step1Done ? '✓ 1. Survey' : '○ 1. Survey' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md border {{ $step2Done ? 'bg-blue-50 border-blue-200 text-blue-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $step2Done ? '✓ 2. Instalasi' : '○ 2. Instalasi' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md border {{ $step3Done ? 'bg-blue-50 border-blue-200 text-blue-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $step3Done ? '✓ 3. Finish' : '○ 3. Finish' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md border {{ $step4Done ? 'bg-amber-50 border-amber-200 text-amber-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $step4Done ? '✓ 4. Dismantle' : '○ 4. Dismantle' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md border {{ $step5Done ? 'bg-emerald-50 border-emerald-200 text-emerald-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $step5Done ? '✓ 5. Mancore' : '○ 5. Mancore' }}
                    </span>
                </div>

                {{-- PROGRESS BAR --}}
                <div class="mt-4 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full {{ $progressColor }} rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                </div>

                {{-- KOTAK INFORMASI JIKA KENDALA STEP 1 --}}
                @if($isKendala)
                    <div class="mt-3 rounded-xl bg-rose-50 border border-rose-100 p-3 flex gap-2 items-start">
                        <span class="text-rose-600 font-bold text-xs">⚠️</span>
                        <div class="min-w-0">
                            <p class="text-[11px] font-black text-rose-800">Terkendala Survey (Menunggu PM):</p>
                            <p class="text-[11px] text-rose-700 mt-0.5">{{ $survey->kendala_note }}</p>
                        </div>
                    </div>
                @endif

                {{-- FOOTER INFO CARD --}}
                <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-slate-50">
                    <div>
                        <p class="text-[10px] text-slate-400 font-medium">Total Progress</p>
                        <p class="text-sm font-black {{ $isKendala ? 'text-rose-600' : 'text-blue-700' }}">{{ $progress }}%</p>
                    </div>

                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 font-medium">Update Terakhir</p>
                        <p class="text-[11px] font-black text-slate-700">{{ $lastUpdate ? $lastUpdate->diffForHumans() : '-' }}</p>
                    </div>
                </div>
                {{-- ACTION BUTTONS --}}
                <div class="mt-3.5 pt-1">

                    {{-- ==========================================================
                        GO LIVE
                        ========================================================== --}}
                    @if($isGoLive)

                        <div class="grid grid-cols-3 gap-2">

                            <div class="col-span-2 h-10 flex items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 text-[11px] font-black">
                                🎉 GO LIVE
                            </div>

                            <a href="{{ route('teknisi.pt2.step1', $lop->id_pt2_lop) }}"
                            class="h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-black transition active:scale-[0.98]">
                                Detail
                            </a>

                        </div>

                    {{-- ==========================================================
                        MENUNGGU APPROVE SDI
                        ========================================================== --}}
                    @elseif($isWaitingSdi)

                        <div class="grid grid-cols-3 gap-2">

                            <div class="col-span-2 h-10 flex items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 text-[11px] font-black">
                                ⏳ Menunggu Approve SDI
                            </div>

                            <a href="{{ route('teknisi.pt2.step1', $lop->id_pt2_lop) }}"
                            class="h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-black transition active:scale-[0.98]">
                                Review
                            </a>

                        </div>

                    {{-- ==========================================================
                        MENUNGGU APPROVE ADMIN
                        ========================================================== --}}
                    @elseif($isWaitingAdmin)

                        <div class="grid grid-cols-3 gap-2">

                            <div class="col-span-2 h-10 flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 border border-blue-200 text-[11px] font-black">
                                ⏳ Menunggu Approve Admin
                            </div>

                            <a href="{{ route('teknisi.pt2.step1', $lop->id_pt2_lop) }}"
                            class="h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-black transition active:scale-[0.98]">
                                Review
                            </a>

                        </div>

                    {{-- ==========================================================
                        BELUM SELESAI
                        ========================================================== --}}
                    @else

                        @if(!$step1Done || $isKendala)

                            <a href="{{ route('teknisi.pt2.step1', $lop->id_pt2_lop) }}"
                            class="h-10 w-full flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black shadow-md transition active:scale-[0.98]">

                                {{ $isKendala ? 'Update Survey Kendala' : 'Mulai Step 1 (Survey)' }}

                            </a>

                        @else

                            <div class="grid grid-cols-2 gap-2">

                                <a href="{{ route('teknisi.pt2.step1', $lop->id_pt2_lop) }}"
                                class="h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-black transition active:scale-[0.98]">
                                    Lihat Data
                                </a>

                                <a href="{{ $nextStepUrl }}"
                                class="h-10 flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black shadow-md transition active:scale-[0.98]">
                                    {{ $nextStepText }}
                                </a>

                            </div>

                        @endif

                    @endif

                </div>

            </div>
        @empty
            <div class="bg-white border border-slate-100 rounded-3xl p-8 text-center text-xs text-slate-400 shadow-xs">
                Belum ada LOP PT 2 yang ditugaskan kepada Anda saat ini.
            </div>
        @endforelse
    </div>

    {{-- BOTTOM NAV --}}
    @include('teknisi.partials.bottom-nav', ['active' => 'inbox'])
</div>
@endsection