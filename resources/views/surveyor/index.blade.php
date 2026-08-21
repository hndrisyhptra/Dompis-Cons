@extends('layouts.surveyor')

@section('title', 'Beranda Survey')

@section('content')

    {{-- Hero Header --}}
    <div class="bg-blue-700 px-5 pt-6 pb-16 rounded-b-[2rem] relative overflow-hidden">

        <div class="relative flex items-center justify-between">
            <div>
                <p class="text-blue-200 text-xs font-semibold tracking-wide uppercase">SDI Surveyor</p>
                <h1 class="text-white text-xl font-black mt-0.5">Halo, {{ explode(' ', auth()->user()->name)[0] ?? 'Surveyor' }}</h1>
                <p class="text-blue-200/80 text-xs mt-1">Siap tagging titik & rute hari ini?</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center border border-white/10">
                    <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                </button>
            </form>
        </div>

        <div class="relative grid grid-cols-3 gap-3 mt-6">
            <div class="glass rounded-2xl p-3 text-center border border-white/20">
                <p class="text-lg font-black text-slate-900">{{ $stats['total'] }}</p>
                <p class="text-[10px] font-bold text-slate-500 uppercase mt-0.5">Total</p>
            </div>
            <div class="glass rounded-2xl p-3 text-center border border-white/20">
                <p class="text-lg font-black text-amber-600">{{ $stats['draft'] }}</p>
                <p class="text-[10px] font-bold text-slate-500 uppercase mt-0.5">On Progress</p>
            </div>
            <div class="glass rounded-2xl p-3 text-center border border-white/20">
                <p class="text-lg font-black text-emerald-600">{{ $stats['completed'] }}</p>
                <p class="text-[10px] font-bold text-slate-500 uppercase mt-0.5">Selesai</p>
            </div>
        </div>
    </div>

    <div class="px-5 -mt-9 relative space-y-4">

        {{-- CTA --}}
        <a href="{{ route('surveyor.create') }}"
           class="flex items-center justify-between bg-white rounded-2xl shadow-lg shadow-slate-900/5 border border-slate-100 px-4 py-3.5 hover:shadow-xl transition">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-blue-600/10 text-blue-700 flex items-center justify-center">
                    <i class="fa-solid fa-plus text-lg"></i>
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900">Mulai Survey Baru</p>
                    <p class="text-[11px] text-slate-500">Tagging tiang, catuan &amp; rute kabel</p>
                </div>
            </div>
            <i class="fa-solid fa-chevron-right text-slate-300"></i>
        </a>

        {{-- Search --}}
        <form method="GET" action="{{ route('surveyor.index') }}" class="relative">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama survey / project..."
                   class="w-full h-11 rounded-2xl border border-slate-200 bg-white pl-10 pr-4 text-sm font-medium shadow-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none transition">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
        </form>

        {{-- Filter Tabs --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <a href="{{ route('surveyor.index') }}"
               class="shrink-0 px-4 py-2 rounded-xl text-xs font-bold {{ !request('status_filter') ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200' }}">Semua</a>
            <a href="{{ route('surveyor.index', ['status_filter' => 'draft']) }}"
               class="shrink-0 px-4 py-2 rounded-xl text-xs font-bold {{ request('status_filter') == 'draft' ? 'bg-amber-500 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200' }}">On Progress</a>
            <a href="{{ route('surveyor.index', ['status_filter' => 'completed']) }}"
               class="shrink-0 px-4 py-2 rounded-xl text-xs font-bold {{ request('status_filter') == 'completed' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200' }}">Selesai</a>
        </div>

        {{-- List --}}
        <div class="space-y-3 pb-4">
            @forelse($surveys as $survey)
                <a href="{{ route('surveyor.show', $survey->id) }}"
                   class="block bg-white rounded-2xl border border-slate-100 shadow-sm p-4 hover:shadow-md transition">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-black text-slate-900 truncate">{{ $survey->displayTitle() }}</p>
                            <p class="text-[11px] text-slate-500 mt-0.5 truncate">
                                @if($survey->project)
                                    PID: {{ $survey->project->pid ?? '-' }}
                                @else
                                    {{ $survey->title }}
                                @endif
                            </p>
                        </div>

                        @if($survey->status === 'completed')
                            <span class="shrink-0 px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700 text-[10px] font-bold border border-emerald-200 whitespace-nowrap">✅ Selesai</span>
                        @else
                            <span class="shrink-0 px-2.5 py-1 rounded-lg bg-amber-100 text-amber-700 text-[10px] font-bold border border-amber-200 whitespace-nowrap animate-pulse">⏳ On Progress</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-4 mt-3 pt-3 border-t border-slate-100 text-[11px] text-slate-500 font-semibold">
                        <span class="flex items-center gap-1.5"><i class="fa-solid fa-tower-broadcast text-blue-500"></i> {{ $survey->points_count }} titik</span>
                        <span class="flex items-center gap-1.5"><i class="fa-solid fa-route text-orange-500"></i> {{ $survey->routes_count }} rute</span>
                        <span class="flex items-center gap-1.5 ml-auto"><i class="fa-regular fa-clock"></i> {{ $survey->updated_at->diffForHumans() }}</span>
                    </div>
                </a>
            @empty
                <div class="text-center py-14 bg-white rounded-2xl border border-dashed border-slate-200">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-400 flex items-center justify-center mx-auto mb-3">
                        <i class="fa-solid fa-map-location-dot text-2xl"></i>
                    </div>
                    <p class="font-black text-slate-800">Belum Ada Survey</p>
                    <p class="text-xs text-slate-500 mt-1 px-8">Yuk mulai survey lapangan pertamamu dengan menekan tombol di bawah.</p>
                </div>
            @endforelse
        </div>

        @if($surveys->hasPages())
            <div class="pb-4">{{ $surveys->links() }}</div>
        @endif
    </div>

@endsection

@section('bottom-nav')
    @include('surveyor.partials.bottom-nav', ['active' => 'home'])
@endsection
