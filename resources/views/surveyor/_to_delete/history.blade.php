@extends('layouts.surveyor')

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans selection:bg-blue-500 selection:text-white">

    {{-- HEADER --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem] shadow-md">
        <div class="flex items-center gap-3">
            <a href="{{ route('surveyor.index') }}" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 inline-flex items-center justify-center text-2xl font-medium transition active:scale-95">
                ‹
            </a>
            <div>
                <h1 class="text-xl font-black tracking-tight">Riwayat Survey</h1>
                <p class="text-xs text-blue-100 mt-0.5">{{ $stats['total'] }} Survey Tercatat</p>
            </div>
        </div>
    </div>

    {{-- SEARCH BAR --}}
    <div class="px-4 mt-4">
        <form method="GET" action="{{ route('surveyor.history') }}">
            @if(request('status_filter'))
                <input type="hidden" name="status_filter" value="{{ request('status_filter') }}">
            @endif
            <div class="relative">
                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama survey / project..."
                    class="w-full h-11 rounded-2xl border border-slate-200 bg-white pl-10 pr-4 text-xs font-bold shadow-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none transition">
                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">
                    🔍
                </div>
            </div>
        </form>
    </div>

    {{-- FILTER TABS --}}
    <div class="px-4 mt-3 flex items-center gap-2 overflow-x-auto">
        <a href="{{ route('surveyor.history', ['search' => request('search')]) }}"
           class="shrink-0 px-3.5 py-2 rounded-xl text-[11px] font-black {{ !request('status_filter') ? 'bg-blue-700 text-white' : 'bg-white text-slate-500 border border-slate-200' }}">Semua ({{ $stats['total'] }})</a>
        <a href="{{ route('surveyor.history', ['status_filter' => 'draft', 'search' => request('search')]) }}"
           class="shrink-0 px-3.5 py-2 rounded-xl text-[11px] font-black {{ request('status_filter') == 'draft' ? 'bg-amber-500 text-white' : 'bg-white text-slate-500 border border-slate-200' }}">Berjalan ({{ $stats['draft'] }})</a>
        <a href="{{ route('surveyor.history', ['status_filter' => 'completed', 'search' => request('search')]) }}"
           class="shrink-0 px-3.5 py-2 rounded-xl text-[11px] font-black {{ request('status_filter') == 'completed' ? 'bg-emerald-600 text-white' : 'bg-white text-slate-500 border border-slate-200' }}">Selesai ({{ $stats['completed'] }})</a>
    </div>

    {{-- LIST CARDS --}}
    <div class="px-4 mt-4 space-y-3">
        @forelse($surveys as $survey)
            @php
                $isDone = $survey->status === 'completed';
                $accent = $isDone ? 'border-l-emerald-600' : 'border-l-amber-500';
            @endphp
            <a href="{{ route('surveyor.show', $survey->id) }}"
               class="block bg-white rounded-2xl border border-slate-100 border-l-4 {{ $accent }} p-4 shadow-xs hover:border-blue-200 transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-black text-slate-800 truncate">{{ $survey->displayTitle() }}</p>
                        <p class="text-[11px] text-slate-400 font-bold mt-0.5 truncate">
                            @if($survey->project)
                                PID: {{ $survey->project->pid ?? '-' }}
                            @else
                                {{ $survey->title }}
                            @endif
                        </p>
                    </div>
                    <span class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-extrabold {{ $isDone ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                        {{ $isDone ? 'Selesai' : 'Berjalan' }}
                    </span>
                </div>

                <div class="flex items-center gap-4 mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 font-bold">
                    <span>{{ $survey->points_count }} titik</span>
                    <span>{{ $survey->routes_count }} rute</span>
                    <span class="ml-auto text-slate-400 font-medium">{{ $survey->updated_at->diffForHumans() }}</span>
                </div>
            </a>
        @empty
            <div class="bg-white rounded-2xl border border-dashed border-slate-200 py-14 text-center">
                <p class="text-sm font-black text-slate-700">Belum Ada Survey</p>
                <p class="text-[11px] text-slate-400 mt-1 px-8">Survey yang kamu buat akan muncul di sini.</p>
            </div>
        @endforelse
    </div>

    @if($surveys->hasPages())
        <div class="px-4 mt-4">{{ $surveys->links() }}</div>
    @endif

    {{-- BOTTOM NAV --}}
    @include('surveyor.partials.bottom-nav', ['active' => request('status_filter') === 'completed' ? 'selesai' : (request('status_filter') === 'draft' ? 'draft' : '')])
</div>
@endsection
