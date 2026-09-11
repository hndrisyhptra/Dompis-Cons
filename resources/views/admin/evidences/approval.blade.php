@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 px-4 py-4 font-sans antialiased text-slate-800 dark:text-slate-200">

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
                    placeholder="Cari Project..."
                    class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 pl-10 text-xs font-semibold shadow-sm focus:ring-2 focus:ring-blue-500 outline-none transition">
                <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</div>
            </form>

            {{-- TOMBOL SWITCH KAWALANKU --}}
            <a href="{{ request()->fullUrlWithQuery(['my_kawal' => request('my_kawal') == '1' ? '0' : '1']) }}"
               class="inline-flex h-10 items-center justify-center gap-2 px-4 rounded-xl border text-xs font-black transition-all shadow-sm
               {{ request('my_kawal') == '1' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700' }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                {{ request('my_kawal') == '1' ? 'Kawalanku Aktif' : 'Semua Kawalan' }}
            </a>
        </div>
    </div>

    {{-- DROPDOWN FILTER BAR (BRANCH & PROGRAM SAP) --}}
    <form method="GET" action="{{ route('admin.evidences.approval') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
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
                <option value="">Semua Program</option>
                @foreach($availablePrograms as $p)
                    <option value="{{ $p }}" {{ request('program') == $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
        </div>
        
        {{-- TOMBOL RESET FILTER GLOBAL --}}
        <div class="flex items-end pb-1">
            @if(request('branch') || request('program') || request('search') || request('my_kawal') == '1')
                <a href="{{ route('admin.evidences.approval') }}" class="h-10 px-4 w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-black transition-all shadow-sm">
                    ✕ Reset Filter
                </a>
            @endif
        </div>
    </form>

    {{-- SYSTEM TABS FILTER STATUS --}}
    <div class="inline-flex flex-wrap items-center gap-1 p-1 rounded-2xl bg-slate-100 dark:bg-slate-800/60 w-full sm:w-auto">
        @php $currentFilter = request('status_filter', 'pending'); @endphp

        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'pending']) }}"
           class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-extrabold tracking-wide transition-all whitespace-nowrap {{ $currentFilter === 'pending' ? 'bg-white dark:bg-slate-900 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            Menunggu Review
            @if($tabCounts !== null)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $currentFilter === 'pending' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    {{ $tabCounts['pending'] }}
                </span>
            @endif
        </a>

        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'active']) }}"
           class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-extrabold tracking-wide transition-all whitespace-nowrap {{ $currentFilter === 'active' ? 'bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            On Progress
            @if($tabCounts !== null)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $currentFilter === 'active' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    {{ $tabCounts['active'] }}
                </span>
            @endif
        </a>

        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'complete']) }}"
           class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-extrabold tracking-wide transition-all whitespace-nowrap {{ $currentFilter === 'complete' ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200' }}">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            Selesai
            @if($tabCounts !== null)
                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-black {{ $currentFilter === 'complete' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' }}">
                    {{ $tabCounts['complete'] }}
                </span>
            @endif
        </a>
    </div>

    {{-- LIST TABLE VIEW --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950/50 border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="p-4 font-black w-2/6">Project & LOP Info</th>
                        <th class="p-4 font-black w-1/6">Waspang & Program</th>
                        <th class="p-4 font-black w-1/4">Progress Approval</th>
                        <th class="p-4 font-black text-center w-1/12">Eviden</th>
                        <th class="p-4 font-black text-right w-1/6">Status & Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($projects as $project)
                        @php
                            $items = $project->evidences ?? collect();
                            $projectId = $project->id_project;
                            $waspang = optional($project->assignment)->waspang;

                            $pendingCount = $items->where('status', 'pending')->count();
                            $approvedCount = $items->where('status', 'approved')->count();
                            $rejectedCount = $items->where('status', 'rejected')->count();

                            // PROGRESS KESELURUHAN: dihitung dari sequence 11-tahap LOP
                            // (persis 100% di Golive), sama persis dengan
                            // Project::progressSummary() yang dipakai di admin lain.
                            $summary = $project->progressSummary();
                            $progressPercent = $summary['progress'] ?? 0;

                            // Section AH/AJ: kolom "Progress Approval" TIDAK lagi
                            // menampilkan rincian angka per-step (Persiapan: 1/2, dst) --
                            // user minta cukup persentase + tahap sekarang, supaya lebih
                            // ringkas & tidak membingungkan. Label & warna tahap dibaca
                            // dari effectiveStageLabel/effectiveStageColor (hold/drop-safe,
                            // sama persis dgn stepper/tracking, Section AF/AG) via
                            // Project::stageColorClasses() -- dipakai baik utk badge
                            // status (kolom kanan) maupun label singkat di kolom Progress.
                            $effectiveLabel = $summary['effectiveStageLabel'] ?? ($project->lop?->stage?->label ?? '-');
                            $effectiveColor = $summary['effectiveStageColor'] ?? null;
                            $stageColors = \App\Models\Project::stageColorClasses($effectiveColor);
                            $isHold = $summary['isHold'] ?? false;
                            $isDrop = $summary['isDrop'] ?? false;
                            $hasPendingEvidence = $pendingCount > 0;

                            // Tombol "Review" diarahkan ke step yang SEDANG berjalan
                            // (dibaca dari effectiveStageSequence, hold/drop-safe) supaya
                            // admin langsung mendarat di step yang relevan, bukan selalu
                            // balik ke Step 1 (Persiapan).
                            $seq = $summary['effectiveStageSequence'] ?? null;
                            $reviewRoute = 'admin.evidences.review.project';
                            if ($seq !== null) {
                                if ($seq >= 10) {
                                    $reviewRoute = 'admin.evidences.review.golive';
                                } elseif ($seq == 9) {
                                    $reviewRoute = 'admin.evidences.review.finishing';
                                } elseif ($seq == 8) {
                                    $reviewRoute = 'admin.evidences.review.pengukuran';
                                } elseif ($seq == 7) {
                                    $reviewRoute = 'admin.evidences.review.instalasi';
                                }
                            }
                        @endphp

                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors group">
                            
                            {{-- KOLOM 1: Project Info --}}
                            <td class="p-4 align-top">
                                <div class="flex items-start gap-3">
                                    <!-- <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-slate-800 border border-blue-100 dark:border-slate-700 text-blue-700 dark:text-blue-400 flex items-center justify-center shrink-0 text-xs font-black">
                                        #{{ $projectId }}
                                    </div> -->
                                    <div class="min-w-0">
                                        <h2 class="text-sm font-black text-slate-800 dark:text-white line-clamp-2 leading-tight" title="{{ $project->project_name }}">
                                            {{ $project->project_name }}
                                        </h2>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                            {{-- BADGE ID IHLD --}}
                                            <span class="inline-flex items-center text-[10px] font-bold font-mono text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 px-1.5 py-0.5 rounded">
                                                {{ $project->lop?->id_ihld ?? '-' }}
                                            </span>
                                            
                                            {{-- BRANCH & STO --}}
                                            <p class="text-[11px] text-slate-400 font-bold flex items-center gap-1.5">
                                                <span class="inline-block w-1.5 h-1.5 rounded-full bg-slate-300 dark:bg-slate-600"></span>
                                                {{ $project->lop?->branch ?? '-' }} 
                                                <span class="mx-0.5">•</span> 
                                                {{ $project->lop?->sto ?? '-' }}
                                            </p>
                                        </div>        
                                    </div>
                                </div>
                            </td>

                            {{-- KOLOM 2: Waspang & Program --}}
                            <td class="p-4 align-top">
                                <div class="space-y-1.5">
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase">Waspang</p>
                                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate">
                                            {{ $waspang->name ?? '-' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase">Program</p>
                                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 font-mono truncate">
                                            {{ $project->lop?->program_sap ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- KOLOM 3: Progress --}}
                            <td class="p-4 align-top">
                                <div class="space-y-2 min-w-[140px]">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 truncate">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $stageColors['dot'] }}"></span>
                                            {{ $effectiveLabel }}
                                        </span>
                                        <span class="text-xs font-black text-slate-700 dark:text-slate-200 shrink-0">{{ round($progressPercent) }}%</span>
                                    </div>
                                    <div class="h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                        <div class="h-full {{ $stageColors['progress'] }} rounded-full transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- KOLOM 4: Eviden Count --}}
                            <td class="p-4 align-top text-center">
                                <div class="flex flex-col gap-1 items-center justify-center font-mono text-[10px] font-black">
                                    <span class="w-16 py-0.5 rounded bg-amber-50 dark:bg-amber-950/50 text-amber-600 border border-amber-200/40" title="Pending">
                                        P: {{ $pendingCount }}
                                    </span>
                                    <span class="w-16 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 border border-emerald-200/40" title="Approved">
                                        A: {{ $approvedCount }}
                                    </span>
                                    <span class="w-16 py-0.5 rounded bg-rose-50 dark:bg-rose-950/50 text-rose-600 border border-rose-200/40" title="Rejected">
                                        R: {{ $rejectedCount }}
                                    </span>
                                </div>
                            </td>

                            {{-- KOLOM 5: Status & Aksi --}}
                            <td class="p-4 align-top text-right">
                                <div class="flex flex-col items-end gap-1.5">
                                    <span class="inline-flex px-2.5 py-1 rounded-md text-[9px] font-extrabold tracking-widest uppercase {{ $stageColors['badge'] }}">
                                        @if($isDrop)
                                            DROP
                                        @elseif($isHold)
                                            HOLD &middot; {{ $effectiveLabel }}
                                        @else
                                            {{ $effectiveLabel }}
                                        @endif
                                    </span>
                                    @if($hasPendingEvidence)
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-[8px] font-extrabold tracking-widest uppercase bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400">
                                            Perlu Review
                                        </span>
                                    @endif
                                    
                                    <a href="{{ route($reviewRoute, $projectId) }}"
                                       class="h-8 px-4 mt-1 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-900 hover:text-white dark:hover:bg-slate-100 dark:hover:text-slate-900 text-slate-700 dark:text-slate-200 inline-flex items-center justify-center gap-1.5 text-xs font-black transition-all shadow-sm group">
                                        Review
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-xs text-slate-400 font-medium">
                                <div class="text-3xl mb-3 opacity-50">📁</div>
                                <p>Tidak ditemukan project yang sesuai dengan kriteria filter saat ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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