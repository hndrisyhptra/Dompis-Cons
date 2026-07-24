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
                <h1 class="text-xl font-black tracking-tight">Inbox Project PT 2</h1>
                <p class="text-xs text-blue-100 mt-0.5">{{ $projects->count() }} LOP di Assign ke Anda</p>
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
        @forelse($projects as $project)
            @php
                $survey = $project->pt2Survey;
                $mancore = $project->pt2Mancore ?? null;
                $evidences = $project->evidences ?? collect();

                // DETEKSI PROGRESS PT 2 (Step 1 - 5)
                $step1Done = $survey ? true : false;
                $step2Done = $evidences->where('stage', 'instalasi')->where('evidence_type', 'progress_boq')->count() > 0; 
                $step3Done = $evidences->where('stage', 'finishing')->count() > 0;
                $step4Done = $evidences->where('stage', 'dismantle')->count() > 0; 
                $step5Done = $mancore ? true : false;

                $isKendala = $survey && $survey->has_kendala == 1;
                $isGoLive = $project->is_golive;

                // Hitung persentase kasar untuk UI
                $doneStep = 0;
                if ($step1Done) $doneStep++;
                if ($step2Done) $doneStep++;
                if ($step3Done) $doneStep++;
                if ($step5Done) $doneStep++; // Asumsi dismantle opsional, step wajib ada 4

                $progress = ($isGoLive || $step5Done) ? 100 : round(($doneStep / 4) * 100);
                $allStepDone = ($progress === 100);

                // DYNAMIC TEMPLATE DESIGN STYLES
                $borderColor = $allStepDone ? 'border-l-emerald-600' : ($isKendala ? 'border-l-rose-500' : 'border-l-blue-600');
                $progressColor = $allStepDone ? 'bg-emerald-500' : ($isKendala ? 'bg-rose-500' : 'bg-blue-600');
                
                $lastUpdate = $project->updated_at;
            @endphp

            <div class="bg-white border border-slate-100 border-l-[4px] {{ $borderColor }} rounded-3xl p-4 shadow-xs relative overflow-hidden">
                
                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-slate-800 tracking-tight leading-tight">
                            {{ $project->project_name }}
                        </h2>
                        <p class="text-[11px] text-slate-400 font-bold mt-1">
                            PID: {{ $project->pid ?? '-' }} · {{ $project->lop?->sto ?? '-' }}
                        </p>
                    </div>

                    <span class="shrink-0 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wide
                        {{ $allStepDone ? 'bg-emerald-100 text-emerald-700' : ($isKendala ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700') }}">
                        {{ $isGoLive ? 'GO LIVE' : ($allStepDone ? 'Waiting SDI' : ($isKendala ? 'Terkendala' : 'On Progress')) }}
                    </span>
                </div>

                {{-- STEPPER BADGES KHUSUS PT 2 --}}
                <div class="flex flex-wrap gap-1 mt-3.5 text-[9px] font-extrabold">
                    <span class="px-2 py-0.5 rounded-md border {{ $step1Done ? 'bg-blue-50 border-blue-200 text-blue-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $step1Done ? '✓ 1. Survey' : '○ 1. Survey' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md border {{ $step2Done ? 'bg-blue-50 border-blue-200 text-blue-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $step2Done ? '✓ 2. Prog. Instal' : '○ 2. Prog. Instal' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md border {{ $step3Done ? 'bg-blue-50 border-blue-200 text-blue-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $step3Done ? '✓ 3. Fin. Instal' : '○ 3. Fin. Instal' }}
                    </span>
                    <span class="px-2 py-0.5 rounded-md border {{ $step4Done ? 'bg-amber-50 border-amber-200 text-amber-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        {{ $step4Done ? '✓ 4. Dismantle' : '○ 4. Dismantle (Ops)' }}
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
                    @if($isGoLive)
                        <div class="h-10 w-full flex items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 text-xs font-black">
                            🎉 Project Telah Selesai (Go Live)
                        </div>
                    @elseif($step5Done)
                        <div class="h-10 w-full flex items-center justify-center rounded-xl bg-blue-50 text-blue-600 border border-blue-200 text-xs font-black">
                            ⏳ Menunggu Approval SDI
                        </div>
                    @else
                        {{-- Logika Tombol Dinamis berdasarkan Step yang belum selesai --}}
                        @if(!$step1Done || $isKendala)
                            {{-- JIKA BELUM STEP 1 ATAU SEDANG KENDALA, ARAHKAN KE STEP 1 --}}
                            <a href="{{ route('teknisi.pt2.step1', $project->id_project) }}"
                            class="h-10 w-full flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black shadow-md transition active:scale-[0.98]">
                                {{ $isKendala ? 'Update Survey Kendala' : 'Mulai Step 1 (Survey)' }}
                            </a>
                        @else
                            {{-- JIKA STEP 1 SELESAI, BISA LANJUT STEP BERIKUTNYA --}}
                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('teknisi.pt2.step1', $project->id_project) }}"
                                class="h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-700 text-xs font-black transition active:scale-[0.98]">
                                    Lihat Data Survey
                                </a>
                                
                                {{-- TOMBOL LANJUT STEP 2 (Menuju Halaman Upload Eviden) --}}
                                <a href="{{ route('teknisi.pt2.step1Eviden', $project->id_project) }}"
                                class="h-10 flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black shadow-md transition active:scale-[0.98]">
                                    Lanjut Step 2 →
                                </a>
                            </div>
                        @endif
                    @endif
                </div>

            </div>
        @empty
            <div class="bg-white border border-slate-100 rounded-3xl p-8 text-center text-xs text-slate-400 shadow-xs">
                Belum ada project PT 2 yang ditugaskan kepada Anda saat ini.
            </div>
        @endforelse
    </div>

    {{-- BOTTOM NAV --}}
    @include('teknisi.partials.bottom-nav', ['active' => 'inbox'])
</div>
@endsection