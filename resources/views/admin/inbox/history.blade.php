@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 px-4 py-2 font-sans antialiased text-slate-800 dark:text-slate-200">

    {{-- HEADER CARD (MODERN EMERALD GRAPH STATE) --}}
    <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            
            <!-- Bagian Kiri: Judul & Deskripsi -->
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">History Project</h1>
                <p class="text-sm text-slate-500 mt-1.5 max-w-md">
                    Daftar seluruh proyek konstruksi yang telah selesai (100%) dan berhasil review eviden.
                </p>
            </div>

            <!-- Bagian Kanan: Stat (Menggunakan aksen Emerald halus) -->
            <div class="flex items-center gap-4 bg-emerald-50/50 px-6 py-4 rounded-xl border border-emerald-100">
                <div class="text-right">
                    <p class="text-[11px] font-semibold uppercase tracking-widest text-emerald-600">Completed</p>
                    <div class="flex items-baseline justify-end gap-1.5 mt-0.5">
                        <span class="text-3xl font-black text-slate-900 font-mono">
                            {{ method_exists($assignments, 'total') ? $assignments->total() : $assignments->count() }}
                        </span>
                        <span class="text-sm font-medium text-emerald-700 uppercase">Project</span>
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
                           placeholder="Cari PID SAP, Nama LOP, STO, Branch, atau Waspang..."
                           class="w-full pl-11 pr-24 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold shadow-xs focus:ring-2 focus:ring-emerald-500 outline-none transition">

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

        {{-- CONTAINER LIST DATA --}}
        <div class="divide-y divide-slate-100 dark:divide-slate-800/60">
            @forelse($assignments as $assignment)
                @php
                    $project = $assignment->project;
                    $lop = $project?->lop;
                    $waspang = $assignment->waspang;

                    $summary = $project?->progressSummary();
                    $progress = $summary['progress'] ?? 100;
                @endphp

                <div class="group hover:bg-emerald-50/20 dark:hover:bg-slate-800/30 transition-all duration-150">
                    <div class="px-6 py-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        
                        {{-- LEFT ACTION INFO --}}
                        <div class="flex items-start gap-4 min-w-0">
                            {{-- EMERALD BULLET DOT INDICATOR --}}
                            <div class="mt-1.5 shrink-0">
                                <div class="w-3 h-3 rounded-full bg-emerald-500 shadow-xs shadow-emerald-400"></div>
                            </div>

                            <div class="min-w-0 space-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono font-black text-slate-800 dark:text-white text-sm tracking-tight">
                                        {{ $project->pid_sap ?? 'PID -' }}
                                    </span>
                                    
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold tracking-wide uppercase bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 border border-emerald-100 dark:border-emerald-900/40">
                                        COMPLETE
                                    </span>

                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold tracking-wide uppercase bg-blue-50 dark:bg-blue-950/40 text-blue-600 border border-blue-100 dark:border-blue-900/40">
                                        {{ $progress }}%
                                    </span>
                                </div>

                                <p class="font-black text-slate-700 dark:text-slate-300 text-sm tracking-tight break-words leading-snug">
                                    {{ $lop->lop_name ?? $project->project_name ?? '-' }}
                                </p>

                                {{-- COMPONENT META SUB --}}
                                <div class="pt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-400 font-bold">
                                    <span class="flex items-center gap-1">STO: <b class="text-slate-600 dark:text-slate-400">{{ $lop->sto ?? '-' }}</b></span>
                                    <span class="hidden sm:inline text-slate-200 dark:text-slate-700">•</span>
                                    <span class="flex items-center gap-1">Branch: <b class="text-slate-600 dark:text-slate-400">{{ $lop->branch ?? '-' }}</b></span>
                                    <span class="hidden sm:inline text-slate-200 dark:text-slate-700">•</span>
                                    <span class="flex items-center gap-1">Program: <b class="text-slate-600 dark:text-slate-400">{{ $lop->program_sap ?? $project->program ?? '-' }}</b></span>
                                    <span class="hidden sm:inline text-slate-200 dark:text-slate-700">•</span>
                                    <span class="flex items-center gap-1 text-emerald-600 dark:text-emerald-400">Waspang: <b class="font-black">{{ $waspang->name ?? '-' }}</b></span>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT BUTTON GROUP ACTION --}}
                        <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-center gap-2 shrink-0 border-t md:border-t-0 border-slate-50 pt-3 md:pt-0">
                            <p class="text-[10px] font-bold text-slate-400 font-mono">
                                Selesai {{ $assignment->created_at ? $assignment->created_at->diffForHumans() : '-' }}
                            </p>

                            <div class="flex items-center gap-2 mt-0 md:mt-1">
                                {{-- BUTTON OPEN LOG TRACKING TRACK --}}
                                <a href="{{ route('admin.projects.tracking', $project->id_project) }}"
                                   class="h-8 px-5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-black transition-all inline-flex items-center shadow-xs shadow-emerald-600/10">
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash">
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    </div>
                    <h3 class="mt-4 text-base font-black text-slate-700 dark:text-slate-300 tracking-tight">
                        Belum Ada Project Selesai
                    </h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs mx-auto">
                        Semua proyek konstruksi yang progresnya telah mencapai target 100% dan lolos review eviden akan tercatat di sini.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
    
    {{-- PAGINATION LINKS PLACEHOLDER --}}
    @if(method_exists($assignments, 'links'))
        <div class="mt-4 p-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs">
            {{ $assignments->links() }}
        </div>
    @endif

</div>
@endsection