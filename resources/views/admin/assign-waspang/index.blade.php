@extends('layouts.admin')

@section('content')

<div class="space-y-6 max-w-[1600px] mx-auto p-2 sm:p-0">

    {{-- HEADER SECTION --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-100 dark:border-gray-800 pb-5">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white">
                Assign Waspang
            </h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">
                Monitoring beban kerja pengawas lapangan dan manajemen penugasan LOP aktif.
            </p>
        </div>
    </div>

    {{-- FILTER & SEARCH PANEL (CLEAN BORDERLESS STYLE) --}}
    <form method="GET" action="{{ route('assign-waspang.index') }}"
        class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800/60 p-4 shadow-xs">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text"
                    name="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Cari berdasarkan nama atau NIK waspang..."
                    class="w-full h-11 pl-10 pr-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 text-sm focus:ring-2 focus:ring-blue-100 dark:focus:ring-blue-950 focus:border-blue-600 outline-none transition">
            </div>

            <div class="flex gap-2">
                <button class="h-11 px-6 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow-xs transition shrink-0">
                    Cari Data
                </button>

                @if (!empty($search))
                    <a href="{{ route('assign-waspang.index') }}"
                    class="h-11 px-5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm font-bold flex items-center justify-center text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition shrink-0">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    {{-- TABLE WRAPPER MODERNIZE --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800/70 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50/70 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-800 text-gray-400 text-[11px] uppercase tracking-wider font-black">
                        <th class="px-5 py-3.5 text-left">Nama Waspang</th>
                        <th class="px-5 py-3.5 text-left">Total Project</th>
                        <th class="px-5 py-3.5 text-left">Status Project</th>
                        <th class="px-5 py-3.5 text-left">Daftar Project Aktif</th>
                        <th class="px-5 py-3.5 text-center w-36">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($waspangs as $waspang)
                        @php
                            $assignments = $waspang->active_assignments ?? collect();
                            $projectCount = $waspang->active_project_count ?? 0;
                            
                            // Klasifikasi status yang lebih berimbang
                            $isOverload = $projectCount > 3;
                            $isOnProgress = $projectCount > 0 && $projectCount <= 3;
                            
                            // Penanda visual status caps
                            $statusClass = $isOverload 
                                ? 'bg-red-50 text-red-700 border-red-100 dark:bg-red-950/30 dark:text-red-400 dark:border-red-900/30' 
                                : ($isOnProgress ? 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900/30' : 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-400 dark:border-emerald-900/30');
                            
                            $statusLabel = $isOverload ? 'Overload' : ($isOnProgress ? 'On Progress' : 'Idle / Ready');
                        @endphp

                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors duration-150">
                            {{-- KOLOM 1: PROFILE IDENTIFICATION --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center font-black text-sm uppercase shadow-xs shrink-0">
                                        {{ substr($waspang->name, 0, 2) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-gray-900 dark:text-white truncate text-sm tracking-tight">
                                            {{ $waspang->name }}
                                        </p>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 font-mono mt-0.5">
                                            NIK: {{ $waspang->nik ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            {{-- KOLOM 2: TOTAL QUANTITY PROJECT (DETAILED METRICS) --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div>
                                    <span class="font-mono font-black text-sm text-gray-800 dark:text-gray-200">
                                        {{ $waspang->total_project_count ?? 0 }}
                                    </span>
                                    <span class="text-[11px] text-gray-400 font-bold ml-0.5 uppercase tracking-wide">Total Project</span>
                                </div>
                                
                                {{-- Indikator Breakdown Status Project Internal Waspang --}}
                                <div class="flex items-center gap-2 mt-1 text-[10px] font-black tracking-tight">
                                    <span class="flex items-center gap-0.5 text-amber-600 bg-amber-50 dark:bg-amber-950/30 px-1.5 py-0.5 rounded border border-amber-100 dark:border-amber-900/20">
                                        <span class="font-mono">{{ $waspang->active_project_count ?? 0 }}</span> OGP
                                    </span>
                                    <span class="flex items-center gap-0.5 text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30 px-1.5 py-0.5 rounded border border-emerald-100 dark:border-emerald-900/20">
                                        <span class="font-mono">{{ $waspang->finished_project_count ?? 0 }}</span> Done
                                    </span>
                                </div>
                            </td>

                            {{-- KOLOM 3: BADGE CAPSTATUS --}}
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg border text-[11px] font-black uppercase tracking-wide inline-block {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            {{-- KOLOM 4: DAFTAR PROYEK (IDE SOLUSI COLLAPSIBLE PREVENT EXPAND TABLE) --}}
                            <td class="px-5 py-4 min-w-[280px]">
                                @if($assignments->count() > 0)
                                    {{-- Menggunakan pembatas maksimal 2 proyek di layar utama tabel --}}
                                    <div class="flex flex-col gap-1.5 max-w-md" x-data="{ expanded: false }">
                                        @foreach($assignments->take(2) as $assignment)
                                            <div class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/40 px-2 py-1 rounded-md border border-gray-100 dark:border-gray-800 truncate font-medium">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 shrink-0"></span>
                                                <span class="truncate">{{ $assignment->project->project_name ?? '-' }}</span>
                                            </div>
                                        @endforeach

                                        {{-- Jika sisa proyek banyak, bungkus di ekspansi dropdown murni Tailwind/Alpine --}}
                                        @if($assignments->count() > 2)
                                            <div x-show="expanded" x-collapse class="flex flex-col gap-1.5 pt-0.5">
                                                @foreach($assignments->slice(2) as $assignment)
                                                    <div class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800/40 px-2 py-1 rounded-md border border-gray-100 dark:border-gray-800 truncate font-medium">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 shrink-0"></span>
                                                        <span class="truncate">{{ $assignment->project->project_name ?? '-' }}</span>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <button type="button" @click="expanded = !expanded" 
                                                    class="text-left text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:text-blue-700 mt-0.5 flex items-center gap-1 focus:outline-none">
                                                <span x-text="expanded ? '‹ Sembunyikan Proyek' : '+ ' + ({{ $assignments->count() }} - 2) + ' Proyek Lainnya'"></span>
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Belum memegang proyek aktif</span>
                                @endif
                            </td>

                            {{-- KOLOM 5: ACTION --}}
                            <td class="px-5 py-4 text-center whitespace-nowrap">
                                <a href="{{ route('admin.assign-waspang.history', $waspang->id_user) }}"
                                class="inline-flex items-center justify-center h-8 px-4 rounded-xl bg-blue-600 hover:bg-blue-800 dark:bg-blue-800 dark:hover:bg-blue-700 text-white text-xs font-black shadow-xs transition duration-150 tracking-wide">
                                   View History
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-gray-400 font-medium">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    <span>Tidak ada data pengawas (Waspang) ditemukan.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION COMPONENT MODERNIZE --}}
        @if ($waspangs->hasPages())
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-800/20">
                <div class="text-xs text-gray-400 font-medium">
                    Menampilkan <span class="font-bold text-gray-700 dark:text-gray-300">{{ $waspangs->firstItem() }}</span> - <span class="font-bold text-gray-700 dark:text-gray-300">{{ $waspangs->lastItem() }}</span> dari <span class="font-bold text-gray-700 dark:text-gray-300">{{ $waspangs->total() }}</span> entri data.
                </div>

                <div class="flex items-center gap-1.5 text-xs font-bold">
                    {{-- Previous Page Link --}}
                    @if ($waspangs->onFirstPage())
                        <span class="px-3 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 text-gray-300 cursor-not-allowed">‹</span>
                    @else
                        <a href="{{ $waspangs->previousPageUrl() }}" class="px-3 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 text-gray-600 dark:text-gray-300 transition">‹</a>
                    @endif

                    {{-- Page Numbers Element --}}
                    @foreach ($waspangs->getUrlRange(max(1, $waspangs->currentPage() - 1), min($waspangs->lastPage(), $waspangs->currentPage() + 1)) as $page => $url)
                        @if ($page == $waspangs->currentPage())
                            <span class="px-3.5 h-8 flex items-center justify-center rounded-lg bg-blue-600 text-white font-black shadow-xs">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-3.5 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 text-gray-600 dark:text-gray-300 transition">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($waspangs->hasMorePages())
                        <a href="{{ $waspangs->nextPageUrl() }}" class="px-3 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 text-gray-600 dark:text-gray-300 transition">→</a>
                    @else
                        <span class="px-3 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 text-gray-300 cursor-not-allowed">→</span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@endsection