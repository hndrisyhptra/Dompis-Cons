@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 px-4 py-2 font-sans antialiased text-slate-800 dark:text-slate-200">

    {{-- HEADER & ACTION BAR --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Approval Eviden
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Kawal, tinjau, dan approve eviden lapangan dari Waspang.
            </p>
        </div>

        {{-- FILTER KAWALAN ADM & SEARCH (BOX INTEGRASI) --}}
        <div class="w-full md:w-auto flex flex-col sm:flex-row gap-3">
            <form method="GET" action="{{ route('admin.evidences.approval') }}" class="w-full sm:w-80 relative">
                @if(!request('search'))
                    <input type="hidden" name="status_filter" value="{{ request('status_filter', 'pending') }}">
                    <input type="hidden" name="branch" value="{{ request('branch') }}">
                    <input type="hidden" name="program" value="{{ request('program') }}">
                    <input type="hidden" name="my_kawal" value="{{ request('my_kawal', '0') }}">
                @endif
                
                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari LOP global (Abaikan filter)..."
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 pl-10 text-xs font-semibold shadow-xs focus:ring-2 focus:ring-blue-500 outline-none transition">
                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</div>
            </form>

            {{-- TOMBOL SWITCH KAWALANKU --}}
            <a href="{{ request()->fullUrlWithQuery(['my_kawal' => request('my_kawal') == '1' ? '0' : '1']) }}"
               class="inline-flex h-10 items-center justify-center gap-2 px-4 rounded-xl border text-xs font-black transition-all shadow-xs
               {{ request('my_kawal') == '1' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                {{ request('my_kawal') == '1' ? 'Kawalanku Aktif' : 'Semua Kawalan Admin' }}
            </a>
        </div>
    </div>

    {{-- DROPDOWN FILTER BAR (BRANCH & PROGRAM SAP) --}}
    <form method="GET" action="{{ route('admin.evidences.approval') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs">
        <input type="hidden" name="status_filter" value="{{ request('status_filter', 'pending') }}">
        <input type="hidden" name="my_kawal" value="{{ request('my_kawal', '0') }}">
        <input type="hidden" name="search" value="{{ request('search') }}">

        <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Filter Branch</label>
            <select name="branch" onchange="this.form.submit()" {{ request('search') ? 'disabled' : '' }} class="w-full h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs font-semibold outline-none focus:border-blue-500 text-slate-700 dark:text-slate-300 disabled:bg-slate-100 disabled:opacity-50">
                <option value="">Semua Branch</option>
                @foreach($availableBranches as $b)
                    <option value="{{ $b }}" {{ request('branch') == $b ? 'selected' : '' }}>{{ $b }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Filter Program (SAP)</label>
            <select name="program" onchange="this.form.submit()" {{ request('search') ? 'disabled' : '' }} class="w-full h-10 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 text-xs font-semibold outline-none focus:border-blue-500 text-slate-700 dark:text-slate-300 disabled:bg-slate-100 disabled:opacity-50">
                <option value="">Semua Program SAP</option>
                @foreach($availablePrograms as $p)
                    <option value="{{ $p }}" {{ request('program') == $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        
        {{-- TOMBOL RESET FILTER GLOBAL --}}
        <div class="flex items-end pb-1">
            @if(request('branch') || request('program') || request('search') || request('my_kawal') == '1')
                <a href="{{ route('admin.evidences.approval') }}" class="h-10 px-4 w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-black transition-all shadow-xs">
                    ✕ Reset Filter
                </a>
            @endif
        </div>
    </form>

    {{-- SYSTEM TABS FILTER STATUS --}}
    <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-px overflow-x-auto">
        @php $currentFilter = request('status_filter', 'pending'); @endphp
        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'pending']) }}" 
           class="px-4 py-2.5 border-b-2 text-xs font-extrabold tracking-wide transition whitespace-nowrap {{ $currentFilter === 'pending' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            Menunggu Review
        </a>
        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'all']) }}" 
           class="px-4 py-2.5 border-b-2 text-xs font-extrabold tracking-wide transition whitespace-nowrap {{ $currentFilter === 'all' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            Semua Penugasan
        </a>
        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'complete']) }}" 
           class="px-4 py-2.5 border-b-2 text-xs font-extrabold tracking-wide transition whitespace-nowrap {{ $currentFilter === 'complete' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            Selesai / Ready UT
        </a>
    </div>

    {{-- GRID VIEW CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($projects as $project)
            @php
                $items = $project->evidences ?? collect();
                $projectId = $project->id_project;
                $waspang = optional($project->assignment)->waspang;

                // 1. PERSIAPAN DONE
                $persiapanTotal = 2;
                $persiapanApproved = $items->where('stage', 'persiapan')->where('status', 'approved')->pluck('evidence_type')->unique()->count();

                // 2. INSTALASI DONE
                $materialBoqItems = ($project->boqItems ?? collect())->filter(function ($boq) {
                    return str_starts_with($boq->designator, 'M-') || optional($boq->designatorData)->type === 'material';
                });
                $instalasiTotal = $materialBoqItems->count();
                $instalasiApproved = $materialBoqItems->filter(function ($boq) use ($items) {
                    $boqEvidences = $items->where('stage', 'instalasi')->where('evidence_type', 'progress_boq')->where('boq_item_id', $boq->id_boq);
                    return $boqEvidences->count() > 0 && $boqEvidences->where('status', 'pending')->count() == 0 && $boqEvidences->where('status', 'rejected')->count() == 0;
                })->count();

                // 3. PENGUKURAN (OPSIONAL)
                $hasOtdr = $items->where('stage', 'pengukuran')->where('evidence_type', 'otdr')->count() > 0;
                $hasOpm = $items->where('stage', 'pengukuran')->where('evidence_type', 'opm')->count() > 0;
                $hasDalam = $items->where('stage', 'pengukuran')->where('evidence_type', 'kedalaman')->count() > 0;
                $pengukuranApprovedCount = $items->where('stage', 'pengukuran')->where('status', 'approved')->count();
                
                $pengukuranTotal = ($hasOtdr ? 1 : 0) + ($hasOpm ? 1 : 0) + ($hasDalam ? 1 : 0);
                $pengukuranApproved = $pengukuranApprovedCount;

                // 4. FINISHING DONE
                $finishingRequiredItems = $materialBoqItems->filter(function ($boq) {
                    return optional($boq->designatorData)->requires_finishing_evidence == 1;
                });
                $finishingTotal = $finishingRequiredItems->count();
                $finishingApproved = $finishingRequiredItems->filter(function ($boq) use ($items) {
                    $finalEvidences = $items->where('stage', 'finishing')->where('boq_item_id', $boq->id_boq);
                    return $finalEvidences->count() > 0 && $finalEvidences->where('status', 'pending')->count() == 0 && $finalEvidences->where('status', 'rejected')->count() == 0;
                })->count();

                // COUNTERS
                $pendingCount = $items->where('status', 'pending')->count();
                $approvedCount = $items->where('status', 'approved')->count();
                $rejectedCount = $items->where('status', 'rejected')->count();

                // SINKRONISASI 100%
                $progress = $persiapanApproved + $instalasiApproved + $finishingApproved;
                $total = $persiapanTotal + $instalasiTotal + $finishingTotal;
                
                $progressPercent = $total > 0 ? ($progress / $total) * 100 : 0;
                $isComplete = ($progressPercent >= 100);
            @endphp

            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl overflow-hidden shadow-xs hover:shadow-md hover:border-blue-200 dark:hover:border-slate-700 transition-all duration-200 flex flex-col justify-between">
                
                <div class="p-5 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-slate-800 border border-blue-100 dark:border-slate-700 text-blue-700 dark:text-blue-400 flex items-center justify-center shrink-0 text-xs font-black">
                                #{{ $projectId }}
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-sm font-black text-slate-800 dark:text-white truncate" title="{{ $project->project_name }}">
                                    {{ $project->project_name }}
                                </h2>
                                <p class="text-[11px] text-slate-400 font-bold truncate mt-0.5">
                                    {{ $project->lop?->branch ?? '-' }} · {{ $project->lop?->sto ?? '-' }}
                                </p>
                            </div>
                        </div>

                        <span class="shrink-0 px-2.5 py-0.5 rounded-full text-[9px] font-extrabold tracking-wide uppercase
                            {{ $isComplete ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 border border-emerald-100 dark:border-emerald-900/60' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 border border-amber-100 dark:border-amber-900/60' }}">
                            {{ $isComplete ? 'Ready UT' : 'In Review' }}
                        </span>
                    </div>

                    <div class="bg-slate-50/50 dark:bg-slate-800/40 rounded-2xl p-3 border border-slate-100 dark:border-slate-800/80 text-[11px] space-y-1">
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-medium">Program SAP:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300 max-w-[150px] truncate font-mono">{{ $project->lop?->program_sap ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-medium">Waspang Lapangan:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300 max-w-[150px] truncate">{{ $waspang->name ?? '-' }}</span>
                        </div>
                    </div>

                    {{-- STEP PROGRESS REVIEWS --}}
                    <div class="grid grid-cols-2 gap-1.5 text-[10px] font-bold">
                        <div class="px-2 py-1.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex justify-between items-center">
                            <span class="text-slate-400">1. Persiapan</span>
                            <span class="text-blue-600 font-mono">{{ $persiapanApproved }}/{{ $persiapanTotal }}</span>
                        </div>
                        <div class="px-2 py-1.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex justify-between items-center">
                            <span class="text-slate-400">2. Instalasi</span>
                            <span class="text-blue-600 font-mono">{{ $instalasiApproved }}/{{ $instalasiTotal }}</span>
                        </div>
                        <div class="px-2 py-1.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex justify-between items-center">
                            <span class="text-slate-400">3. Ukur (Opt)</span>
                            <span class="text-amber-600 font-mono">{{ $pengukuranApproved }}/{{ $pengukuranTotal }}</span>
                        </div>
                        <div class="px-2 py-1.5 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/30 dark:bg-slate-800/20 flex justify-between items-center">
                            <span class="text-slate-400">4. Finishing</span>
                            <span class="text-blue-600 font-mono">{{ $finishingApproved }}/{{ $finishingTotal }}</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <div class="flex justify-between items-center text-[10px] font-bold text-slate-400 uppercase tracking-wide">
                            <span>Progress Review</span>
                            <span class="text-slate-700 dark:text-slate-200 font-mono">{{ round($progressPercent) }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full bg-blue-600 rounded-full transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-3.5 bg-slate-50/80 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 text-[9px] font-extrabold tracking-wide font-mono">
                        <span class="px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/50 text-amber-600 border border-amber-200/40">
                            P: {{ $pendingCount }}
                        </span>
                        <span class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 border border-emerald-200/40">
                            A: {{ $approvedCount }}
                        </span>
                        <span class="px-2 py-0.5 rounded-md bg-rose-50 dark:bg-rose-950/50 text-rose-600 border border-rose-200/40">
                            R: {{ $rejectedCount }}
                        </span>
                    </div>

                    <a href="{{ route('admin.evidences.review.project', $projectId) }}"
                       class="h-9 px-4 rounded-xl bg-slate-900 dark:bg-slate-100 hover:bg-black dark:hover:bg-white text-white dark:text-slate-900 inline-flex items-center justify-center gap-1.5 text-xs font-black transition-all shadow-xs group">
                        Review
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-1 md:col-span-2 lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-3xl p-12 text-center text-xs text-slate-400 font-medium">
                <div class="text-3xl mb-2">📁</div>
                Tidak ditemukan project yang sesuai dengan kriteria filter saat ini.
            </div>
        @endforelse
    </div>

    {{-- PAGINATION LINK BAR --}}
    <div class="mt-6">
        {{ $projects->links() }}
    </div>

</div>
@endsection

@section('scripts')
<script>
    let searchTimeout = null;
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    }
</script>
@endsection