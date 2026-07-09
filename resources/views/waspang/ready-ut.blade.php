@extends('layouts.waspang')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans">

    {{-- HEADER --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem] shadow-md">
        <div class="flex items-center gap-3">
            <a href="{{ route('waspang.dashboard') }}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 inline-flex items-center justify-center text-2xl font-medium transition active:scale-95">
                ‹
            </a>
            <div>
                <h1 class="text-xl font-black tracking-tight">List LOP Selesai</h1>
                <p class="text-xs text-blue-100 mt-0.5">{{ $projects->count() }} LOP Ready UT</p>
            </div>
        </div>      
    </div>

    {{-- SEARCH BAR --}}
    <div class="px-4 mt-4">
        <form method="GET" action="{{ route('waspang.ready-ut') }}">
            <div class="relative">
                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari LOP finish, STO, branch, mitra..."
                    class="w-full h-11 rounded-2xl border border-slate-200 bg-white pl-10 pr-4 text-xs font-bold shadow-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none transition">
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                    🔍
                </div>
            </div>
        </form>
    </div>

    {{-- LIST CARDS --}}
    <div class="px-4 mt-4 space-y-4">

        @forelse($projects as $project)
            @php
                $evidences = $project->evidences ?? collect();
                $boqItems = $project->boqItems ?? collect();

                // 1. PERSIAPAN DONE
                $persiapanDone = $evidences->where('stage', 'persiapan')->where('evidence_type', 'barang_tiba')->where('status', 'approved')->count() > 0 
                    && $evidences->where('stage', 'persiapan')->where('evidence_type', 'perizinan')->where('status', 'approved')->count() > 0;

                // 2. INSTALASI DONE
                $materialBoqItems = $boqItems->filter(function ($boq) {
                    return str_starts_with($boq->designator, 'M-') || optional($boq->designatorData)->type === 'material';
                });
                $boqTotal = $materialBoqItems->count();
                $boqDone = $materialBoqItems->filter(function ($boq) use ($evidences) {
                    return $evidences->where('stage', 'instalasi')->where('evidence_type', 'progress_boq')->where('boq_item_id', $boq->id_boq)->where('status', 'approved')->count() > 0;
                })->count();
                $instalasiDone = $boqTotal > 0 && $boqDone == $boqTotal;

                // 3. PENGUKURAN (Opsional & Fleksibel)
                $hasOtdr = $evidences->where('stage', 'pengukuran')->where('evidence_type', 'otdr')->count() > 0;
                $hasOpm = $evidences->where('stage', 'pengukuran')->where('evidence_type', 'opm')->count() > 0;
                $hasDalam = $evidences->where('stage', 'pengukuran')->where('evidence_type', 'kedalaman')->count() > 0;
                
                $pengukuranApproved = $evidences->where('stage', 'pengukuran')->where('status', 'approved')->count() == $evidences->where('stage', 'pengukuran')->count();
                $pengukuranDone = ($hasOtdr || $hasOpm || $hasDalam) ? $pengukuranApproved : true;

                // 4. FINISHING DONE
                $finishingDone = $evidences->where('stage', 'finishing')->where('status', 'approved')->count() > 0;

                // ATURAN SINKRONISASI KELULUSAN 100% (Sama dengan Dashboard & Inbox)
                $allStepDone = ($persiapanDone && $instalasiDone && $finishingDone);
                $progress = $allStepDone ? 100 : 0; // Karena halaman ini khusus LOP Finish, idealnya selalu 100%

                $lastUpdate = optional($evidences->sortByDesc('updated_at')->first())->updated_at ?? $project->updated_at;
            @endphp

            <div class="bg-white border border-slate-100 border-l-[4px] border-l-emerald-600 rounded-3xl p-4 shadow-xs">

                <div class="flex justify-between items-start gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-black text-slate-800 tracking-tight leading-tight">
                            {{ $project->project_name }}
                        </h2>
                        <p class="text-[11px] text-slate-400 font-bold mt-1">
                           {{ $project->lop?->branch ?? '-' }} · {{ $project->lop?->sto ?? '-' }} · {{ strtoupper($project->execution_type ?? '-') }}
                        </p>
                    </div>

                    <span class="shrink-0 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold tracking-wide bg-emerald-100 text-emerald-700">
                        Selesai
                    </span>
                </div>

                {{-- STEPPER STATUS BADGES --}}
                <div class="flex flex-wrap gap-1 mt-3.5 text-[9px] font-extrabold">
                    <span class="px-2 py-0.5 rounded-md border bg-red-50 border-red-200 text-red-600">
                        ✓ Persiapan
                    </span>

                    <span class="px-2 py-0.5 rounded-md border bg-blue-50 border-blue-200 text-blue-600">
                        ✓ Instalasi
                    </span>

                    <span class="px-2 py-0.5 rounded-md border {{ $pengukuranDone ? 'bg-amber-50 border-amber-200 text-amber-600' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                        ✓ Pengukuran <span class="font-normal text-[8px] text-amber-500">(Opsional)</span>
                    </span>

                    <span class="px-2 py-0.5 rounded-md border bg-emerald-50 border-emerald-200 text-emerald-600">
                        ✓ Finishing
                    </span>
                </div>

                {{-- PROGRESS BAR SOLID BIRU --}}
                <div class="mt-4 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-600 rounded-full transition-all duration-300" style="width: 100%"></div>
                </div>

                <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-slate-50">
                    <div>
                        <p class="text-[10px] text-slate-400 font-medium">Total Progress</p>
                        <p class="text-sm font-black text-blue-700">100%</p>
                    </div>

                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 font-medium">Update Terakhir</p>
                        <p class="text-[11px] font-black text-slate-700">{{ $lastUpdate ? $lastUpdate->diffForHumans() : '-' }}</p>
                    </div>
                </div>

                {{-- FINAL BUTTON ACTION --}}
                <div class="mt-3.5">
                    <a href="{{ route('waspang.projects.review_final', $project->id_project) }}"
                       class="h-10 w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md transition tracking-wide">
                        Review BOQ Final & UT
                    </a>
                </div>

            </div>

        @empty
            <div class="bg-white border border-slate-100 rounded-3xl p-8 text-center text-xs text-slate-400 shadow-xs">
                Belum ada LOP dengan status Selesai / Ready UT.
            </div>
        @endforelse

    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'inbox'])
</div>
@endsection

@section('scripts')
<script>
    let searchTimeout = null;
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { this.form.submit(); }, 500);
        });
    }
</script>
@endsection