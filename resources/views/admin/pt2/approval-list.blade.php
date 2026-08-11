@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 px-4 py-4 font-sans antialiased text-slate-800 dark:text-slate-200">

    {{-- HEADER & ACTION BAR --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-100 dark:border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 rounded bg-cyan-100 text-cyan-700 text-[10px] font-black uppercase tracking-wider">Khusus PT 2</span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                Approval Eviden PT 2
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Tinjau kelengkapan Survey, Instalasi, Redaman, dan Dismantle dari Teknisi per LOP.
            </p>
        </div>

        {{-- SEARCH BOX & FILTER FORM --}}
        <div class="w-full md:w-auto relative">
            <form method="GET" action="{{ route('admin.pt2.approval') }}" id="filterForm" class="flex flex-col md:flex-row gap-3">
                <input type="hidden" name="status_filter" value="{{ request('status_filter', 'active') }}">
                
                {{-- Filter Region --}}
                <select name="region" onchange="this.form.submit()" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold shadow-sm focus:ring-2 focus:ring-cyan-500 outline-none">
                    <option value="">Semua Region</option>
                    <option value="JATIM" {{ request('region') == 'JATIM' ? 'selected' : '' }}>JATIM</option>
                    <option value="JATENG DIY" {{ request('region') == 'JATENG DIY' ? 'selected' : '' }}>JATENG DIY</option>
                    <option value="BALNUS" {{ request('region') == 'BALNUS' ? 'selected' : '' }}>BALNUS</option>
                </select>

                {{-- Filter Branch --}}
                <select name="branch" onchange="this.form.submit()" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-2 text-xs font-semibold shadow-sm focus:ring-2 focus:ring-cyan-500 outline-none">
                    <option value="">Semua Branch</option>
                    @foreach($branches as $b)
                        <option value="{{ $b }}" {{ request('branch') == $b ? 'selected' : '' }}>{{ $b }}</option>
                    @endforeach
                </select>

                <div class="relative w-full md:w-64">
                    <input type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari Nama LOP / PID / IHLD..."
                        class="w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2 pl-9 text-xs font-semibold shadow-sm focus:ring-2 focus:ring-cyan-500 outline-none transition">
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</div>
                </div>
            </form>
        </div>
    </div>

    {{-- URUTAN BARU: SYSTEM TABS FILTER STATUS --}}
    <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-px overflow-x-auto">
        @php $currentFilter = request('status_filter', 'active'); @endphp
        
        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'active']) }}" 
           class="px-4 py-2.5 border-b-2 text-xs font-extrabold tracking-wide transition whitespace-nowrap {{ $currentFilter === 'active' ? 'border-cyan-600 text-cyan-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            On Progress
        </a>

        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'pending']) }}" 
           class="px-4 py-2.5 border-b-2 text-xs font-extrabold tracking-wide transition whitespace-nowrap {{ $currentFilter === 'pending' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            Menunggu Review
        </a>

        <a href="{{ request()->fullUrlWithQuery(['status_filter' => 'completed']) }}" 
           class="px-4 py-2.5 border-b-2 text-xs font-extrabold tracking-wide transition whitespace-nowrap {{ $currentFilter === 'completed' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
            Approved / Selesai
        </a>
    </div>

    {{-- LIST TABLE VIEW --}}
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl overflow-hidden shadow-sm mt-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-950/50 border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="p-4 font-black w-2/6">LOP & Project Induk</th>
                        <th class="p-4 font-black w-1/6">Teknisi Bertugas</th>
                        <th class="p-4 font-black w-1/4">Kelengkapan 5 Step</th>
                        <th class="p-4 font-black text-center w-1/12">Eviden</th>
                        <th class="p-4 font-black text-right w-1/6">Status & Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($lops as $lop)
                        @php
                            $project = $lop->project;
                            $evidences = $lop->evidences ?? collect();
                            $teknisi = optional($lop->assignment)->teknisi;
                            $survey = $lop->surveys()->first();
                            $mancore = \App\Models\MancorePt2::where('pt2_lop_id', $lop->id_pt2_lop)->first();

                            // LOGIKA PENGECEKAN 5 STEP PT2
                            $step1 = ($survey && $evidences->where('stage', 'persiapan')->count() > 0) ? 1 : 0;
                            $step2 = $evidences->where('stage', 'instalasi')->count() > 0 ? 1 : 0;
                            $step3 = $evidences->where('stage', 'finishing')->where('evidence_type', 'redaman_port')->count() > 0 ? 1 : 0;
                            $hasDismantle = \App\Models\DismantlePt2::where('pt2_lop_id', $lop->id_pt2_lop)->exists();
                            $step4 = 1; // Opt
                            $step5 = $mancore ? 1 : 0;

                            $progress = $step1 + $step2 + $step3 + $step4 + $step5;
                            $progressPercent = ($progress / 5) * 100;

                            $pendingCount = $evidences->where('status', 'pending')->count();
                            $approvedCount = $evidences->where('status', 'approved')->count();
                            $rejectedCount = $evidences->where('status', 'rejected')->count();

                            // Tentukan status badge
                            $isFullyApproved = ($step5 && $pendingCount == 0 && $rejectedCount == 0 && $approvedCount > 0);
                        @endphp

                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors group">
                            
                            {{-- KOLOM 1: LOP Info --}}
                            <td class="p-4 align-top">
                                <div class="min-w-0">
                                    <h2 class="text-sm font-black text-slate-800 dark:text-white line-clamp-2 leading-tight">
                                        {{ $lop->lop_name }}
                                    </h2>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center text-[10px] font-bold font-mono text-cyan-600 bg-cyan-50 border border-cyan-100 px-1.5 py-0.5 rounded">
                                            IHLD: {{ $lop->id_ihld ?? '-' }}
                                        </span>
                                        <p class="text-[10px] text-slate-500 font-bold flex items-center gap-1.5">
                                            PID: {{ $project->pid ?? '-' }}
                                        </p>
                                    </div>        
                                </div>
                            </td>

                            {{-- KOLOM 2: Teknisi --}}
                            <td class="p-4 align-top">
                                <div>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase">Assigned To</p>
                                    <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate mt-0.5">
                                        {{ $teknisi->name ?? 'Belum ada Teknisi' }}
                                    </p>
                                    <p class="text-[10px] text-slate-400 font-bold mt-0.5">{{ $lop->branch ?? '-' }} - STO {{ $lop->sto ?? '-' }}</p>
                                </div>
                            </td>

                            {{-- KOLOM 3: Progress 5 Step PT2 --}}
                            <td class="p-4 align-top">
                                <div class="space-y-2.5">
                                    {{-- Progress Bar --}}
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full bg-cyan-500 rounded-full transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                                        </div>
                                        <span class="text-[10px] font-black text-slate-700 w-9 text-right">{{ $progress }}/5</span>
                                    </div>
                                    
                                    {{-- Detail Angka Progress --}}
                                    <div class="flex items-center gap-1.5 text-[9px] font-bold">
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500" title="Step 1 Survey">
                                            S1: <span class="{{ $step1 ? 'text-cyan-600' : 'text-slate-400' }}">{{ $step1 ? '✓' : '✗' }}</span>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500" title="Step 2 Instalasi">
                                            S2: <span class="{{ $step2 ? 'text-cyan-600' : 'text-slate-400' }}">{{ $step2 ? '✓' : '✗' }}</span>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500" title="Step 3 Redaman">
                                            S3: <span class="{{ $step3 ? 'text-cyan-600' : 'text-slate-400' }}">{{ $step3 ? '✓' : '✗' }}</span>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500" title="Step 4 Dismantle (Opsional)">
                                            S4: <span class="{{ $hasDismantle ? 'text-cyan-600' : 'text-slate-400' }}">{{ $hasDismantle ? '✓' : 'Opt' }}</span>
                                        </span>
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-500" title="Step 5 Mancore">
                                            S5: <span class="{{ $step5 ? 'text-cyan-600' : 'text-slate-400' }}">{{ $step5 ? '✓' : '✗' }}</span>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- KOLOM 4: Status Foto --}}
                            <td class="p-4 align-top text-center">
                                <div class="flex flex-col gap-1 items-center justify-center font-mono text-[10px] font-black">
                                    <span class="w-16 py-0.5 rounded bg-amber-50 text-amber-600 border border-amber-200/40" title="Pending">
                                        P: {{ $pendingCount }}
                                    </span>
                                    <span class="w-16 py-0.5 rounded bg-emerald-50 text-emerald-600 border border-emerald-200/40" title="Approved">
                                        A: {{ $approvedCount }}
                                    </span>
                                </div>
                            </td>

                            {{-- KOLOM 5: Aksi Review --}}
                            <td class="p-4 align-top text-right">
                                <div class="flex flex-col items-end gap-2.5">
                                    
                                    {{-- DYNAMIC STATUS BADGE --}}
                                    @if($isFullyApproved)
                                        <span class="inline-flex px-2.5 py-1 rounded-md text-[9px] font-extrabold tracking-widest uppercase bg-emerald-100 text-emerald-700">
                                            COMPLETE
                                        </span>
                                    @elseif($step5)
                                        <span class="inline-flex px-2.5 py-1 rounded-md text-[9px] font-extrabold tracking-widest uppercase bg-amber-100 text-amber-700 animate-pulse">
                                            NEED REVIEW
                                        </span>
                                    @else
                                        <span class="inline-flex px-2.5 py-1 rounded-md text-[9px] font-extrabold tracking-widest uppercase bg-cyan-100 text-cyan-700">
                                            ON PROGRESS
                                        </span>
                                    @endif
                                    
                                    <a href="{{ route('admin.pt2.review', $lop->id_pt2_lop) }}"
                                       class="h-8 px-4 rounded-xl bg-cyan-50 border border-cyan-200 hover:bg-cyan-600 hover:text-white text-cyan-700 inline-flex items-center justify-center gap-1.5 text-xs font-black transition-all shadow-sm group">
                                        {{ $isFullyApproved ? 'Lihat Detail' : 'Review LOP' }}
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-xs text-slate-400 font-medium">
                                <div class="text-3xl mb-3 opacity-50">📁</div>
                                <p>Tidak ada data LOP pada status ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6">
        {{ $lops->links() }}
    </div>
</div>
@endsection