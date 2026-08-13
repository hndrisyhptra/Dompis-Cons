@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 px-4 py-2 font-sans antialiased text-slate-800 dark:text-slate-200">

    {{-- HEADER CARD --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 border border-cyan-200 dark:border-cyan-900 shadow-sm relative overflow-hidden">
        <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-50 dark:bg-cyan-900/20 rounded-full blur-3xl -mr-10 -mt-10"></div>
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 relative z-10">
            <div>
                <h1 class="text-2xl font-extrabold text-cyan-900 dark:text-cyan-400 tracking-tight">Inbox PT 2 Admin</h1>
                <p class="text-sm text-slate-500 mt-1.5 max-w-md">
                    Daftar penugasan LOP (Program PT 2) yang sedang Anda kawal dan tugaskan kepada Teknisi.
                </p>
            </div>

            <div class="flex items-center gap-4 bg-cyan-50 dark:bg-cyan-950/50 px-6 py-4 rounded-xl border border-cyan-100 dark:border-cyan-900/50">
                <div class="text-right">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-cyan-500">Total Assign PT 2</p>
                    <div class="flex items-baseline justify-end gap-1.5 mt-0.5">
                        <span class="text-3xl font-black text-cyan-700 dark:text-cyan-300 font-mono">{{ $pt2Lops->total() }}</span>
                        <span class="text-sm font-medium text-cyan-600 uppercase">LOP</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- INTERACTION CONTROLS (SEARCH ENGINE) --}}
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-50 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-800/20">
            <form method="GET" action="{{ url()->current() }}">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari PID SAP, Nama LOP, Branch, atau STO..."
                           class="w-full pl-11 pr-24 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold shadow-xs focus:ring-2 focus:ring-cyan-500 outline-none transition">

                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search">
                            <path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>
                    </div>
                    
                    @if(request('search'))
                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                            <a href="{{ url()->current() }}" class="text-[11px] font-bold text-rose-600 hover:underline">✕ Clear</a>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        {{-- CONTAINER LIST DATA PER LOP --}}
        <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
            @forelse($pt2Lops as $lop)
                @php
                    $project = $lop->project;
                    $teknisi = $lop->assignment->teknisi ?? null;
                    $assignDate = $lop->assignment->created_at ?? null;
                    
                    // Hitung progress dan eviden pending
                    $summary = $lop->progressSummary();
                    $pendingEvidences = $lop->evidences->where('status', 'pending')->count();
                @endphp

                <div class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all duration-150">
                    <div class="px-6 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        {{-- LEFT ACTION INFO --}}
                        <div class="flex items-start gap-4 min-w-0">
                            {{-- STATUS BULLET DOT INDICATOR --}}
                            <div class="mt-1.5 shrink-0">
                                @if($pendingEvidences > 0)
                                    <span class="relative flex h-3 w-3">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                                    </span>
                                @else
                                    <div class="w-3 h-3 rounded-full bg-cyan-500 shadow-xs shadow-cyan-400"></div>
                                @endif
                            </div>

                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono font-black text-slate-800 dark:text-white text-sm tracking-tight">
                                        {{ $project->pid_sap ?? 'PID -' }}
                                    </span>
                                    
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold tracking-wide uppercase bg-cyan-50 dark:bg-cyan-950/40 text-cyan-600 border border-cyan-100 dark:border-cyan-900/40">
                                        PROGRAM PT 2
                                    </span>

                                    @if($pendingEvidences > 0)
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-black tracking-wide uppercase bg-amber-50 dark:bg-amber-950/40 text-amber-600 border border-amber-200/40 animate-pulse">
                                            ⚠️ {{ $pendingEvidences }} Eviden Menunggu Review
                                        </span>
                                    @endif
                                </div>

                                {{-- NAMA LOP (Fokus Utama) --}}
                                <p class="font-black text-slate-700 dark:text-slate-300 text-sm tracking-tight break-words leading-snug">
                                    {{ $lop->lop_name ?? '-' }}
                                </p>

                                {{-- COMPONENT META SUB --}}
                                <div class="pt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-400 font-bold">
                                    <span class="flex items-center gap-1">PID Wadah: <b class="text-slate-600 dark:text-slate-400">{{ $project->pid ?? '-' }}</b></span>
                                    <span class="hidden sm:inline text-slate-200 dark:text-slate-700">•</span>
                                    <span class="flex items-center gap-1">STO/Branch: <b class="text-slate-600 dark:text-slate-400">{{ $lop->sto ?? '-' }} / {{ $lop->branch ?? '-' }}</b></span>
                                    <span class="hidden sm:inline text-slate-200 dark:text-slate-700">•</span>
                                    <span class="flex items-center gap-1 text-cyan-600 dark:text-cyan-400">Teknisi: <b class="font-black">{{ $teknisi->name ?? 'Belum Assign' }}</b></span>
                                    <span class="hidden sm:inline text-slate-200 dark:text-slate-700">•</span>
                                    <span class="flex items-center gap-1">
                                        Progress: <b class="{{ $summary['color'] }} text-white px-1.5 rounded">{{ $summary['progress'] }}%</b>
                                        <span class="text-slate-500">({{ $summary['stageLabel'] }})</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                       {{-- RIGHT BUTTON GROUP ACTION --}}
                        <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-center gap-2 shrink-0 border-t md:border-t-0 border-slate-50 pt-3 md:pt-0">
                            <p class="text-[10px] font-bold text-slate-400 font-mono">
                                Assign {{ $assignDate ? $assignDate->diffForHumans() : '-' }}
                            </p>

                            <div class="flex items-center gap-2 mt-0 md:mt-1">
                                {{-- BUTTON REVIEW (Arahkan ke Halaman Approval PT 2) --}}
                                <a href="{{ route('admin.pt2.approval', ['search' => $lop->lop_name]) }}"
                                   class="h-8 px-3 rounded-lg border border-cyan-200 dark:border-cyan-700 hover:border-cyan-500 hover:bg-cyan-500/10 text-slate-700 dark:text-slate-300 hover:text-cyan-600 text-[11px] font-black transition-all inline-flex items-center gap-1.5 shadow-xs">
                                    @if($pendingEvidences > 0)
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                                        </span>
                                        Review ({{ $pendingEvidences }})
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.9 3.18"/><path d="m2 22 3-3"/><path d="M16 22a6 6 0 1 0 0-12 6 6 0 0 0 0 12Z"/><path d="M16 14v4"/><path d="M16 22v.01"/></svg>
                                        Review LOP
                                    @endif
                                </a>

                                {{-- BUTTON OPEN LOG TRACKING TRACK --}}
                                <a href="{{ route('admin.pt2.tracking', $lop->id_pt2_lop) }}"
                                   class="h-8 px-4 rounded-lg bg-slate-900 dark:bg-slate-100 hover:bg-black dark:hover:bg-white text-white dark:text-slate-900 text-[11px] font-black transition-all inline-flex items-center shadow-xs">
                                    Tracking
                                </a>
                            </div>
                        </div>

                    </div>
                </div>

            @empty
                {{-- EMPTY BOX STATE VIEW --}}
                <div class="py-20 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 flex items-center justify-center mx-auto text-2xl text-slate-300">
                        📁
                    </div>
                    <h3 class="mt-4 text-base font-black text-slate-700 dark:text-slate-300 tracking-tight">
                        Inbox PT 2 Kosong
                    </h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">
                        Belum ada LOP PT 2 aktif yang Anda tugaskan saat ini.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
    
    {{-- PAGINATION LINKS PLACEHOLDER --}}
    @if(method_exists($pt2Lops, 'links'))
        <div class="mt-4">
            {{ $pt2Lops->links() }}
        </div>
    @endif

</div>
@endsection