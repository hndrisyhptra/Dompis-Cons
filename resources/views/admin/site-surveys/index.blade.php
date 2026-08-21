@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div class="flex items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Hasil Survey Lapangan
            </h1>
            <p class="text-sm text-gray-500">
                Data GPS tiang eksisting, catuan, dan rute kabel dari SDI Surveyor
            </p>
        </div>
    </div>

    {{-- KPI CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-500 font-bold uppercase">Total Survey</p>
                    <p class="text-3xl font-black text-slate-800 dark:text-white mt-2">{{ $stats['total'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Seluruh survey lapangan</p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-500">
                        <rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-500 font-bold uppercase">On Progress</p>
                    <p class="text-3xl font-black text-amber-600 mt-2">{{ $stats['draft'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">Sedang disurvei di lapangan</p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-amber-600">
                        <path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="rounded-3xl bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs text-slate-500 font-bold uppercase">Selesai</p>
                    <p class="text-3xl font-black text-emerald-600 mt-2">{{ $stats['completed'] }}</p>
                    <p class="text-xs text-slate-500 mt-1">KML siap diunduh</p>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- SEARCH & FILTER --}}
    <form method="GET" action="{{ route('admin.site-surveys.index') }}"
        class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        @if(request('status_filter'))
            <input type="hidden" name="status_filter" value="{{ request('status_filter') }}">
        @endif

        <div class="flex flex-col sm:flex-row gap-3">
            <input type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari judul survey, project, PID, atau nama surveyor..."
                class="flex-1 h-11 rounded-xl border-gray-300 text-sm">

            <div class="flex gap-2">
                <button class="h-11 px-5 rounded-xl bg-blue-600 text-white text-sm font-bold">
                    Cari
                </button>

                @if (request('search'))
                    <a href="{{ route('admin.site-surveys.index', ['status_filter' => request('status_filter')]) }}"
                    class="h-11 px-5 rounded-xl border border-gray-300 text-sm font-bold flex items-center">
                        Reset
                    </a>
                @endif
            </div>
        </div>
    </form>

    <div class="flex items-center gap-2">
        <a href="{{ route('admin.site-surveys.index', ['search' => request('search')]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold {{ !request('status_filter') ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600' }}">Semua ({{ $stats['total'] }})</a>
        <a href="{{ route('admin.site-surveys.index', ['status_filter' => 'draft', 'search' => request('search')]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status_filter') == 'draft' ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-600' }}">On Progress ({{ $stats['draft'] }})</a>
        <a href="{{ route('admin.site-surveys.index', ['status_filter' => 'completed', 'search' => request('search')]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold {{ request('status_filter') == 'completed' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600' }}">Selesai ({{ $stats['completed'] }})</a>
    </div>

    {{-- TABLE --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Survey / Project</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Surveyor</th>
                        <th class="px-4 py-3 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Titik</th>
                        <th class="px-4 py-3 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Rute</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase text-gray-500 tracking-wider">Update Terakhir</th>
                        <th class="px-4 py-3 text-center text-[11px] font-black uppercase text-gray-500 tracking-wider">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($surveys as $survey)
                        @php
                            $isDone = $survey->status === 'completed';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 min-w-[220px]">
                                <p class="font-bold text-gray-900 dark:text-white">{{ $survey->displayTitle() }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    @if($survey->project)
                                        PID: {{ $survey->project->pid ?? '-' }}
                                    @else
                                        {{ $survey->project_name ?? '-' }}
                                    @endif
                                </p>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-semibold">
                                {{ $survey->surveyor->name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-blue-600">
                                {{ $survey->points_count }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-slate-700">
                                {{ $survey->routes_count }}
                            </td>
                            <td class="px-4 py-3">
                                @if($isDone)
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-xs font-bold inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Selesai
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-bold inline-flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> On Progress
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                                {{ $survey->updated_at->diffForHumans() }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <a href="{{ route('admin.site-surveys.show', $survey->id) }}"
                                   class="h-9 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition inline-flex items-center gap-1.5">
                                    Lihat Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <p class="font-black text-gray-900 text-lg">Belum Ada Data Survey</p>
                                <p class="text-gray-500 text-sm mt-1">Survey yang dibuat oleh SDI Surveyor akan muncul di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($surveys->hasPages())
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
                {{ $surveys->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
