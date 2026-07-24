@extends('layouts.teknisi') {{-- Sesuaikan layout parent mobile Anda --}}

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans selection:bg-blue-500 selection:text-white">

    {{-- HEADER --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-6 rounded-b-[1.7rem] shadow-md mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-black tracking-tight leading-tight mt-0.5">Inbox PT2</h1>
                <p class="text-[11px] text-blue-100/80 mt-1.5 font-medium">Daftar LOP yang ditugaskan</p>
            </div>
            <a href="{{ route('teknisi.pt2.dashboard') }}" class="p-2 bg-white/10 rounded-xl border border-white/10 hover:bg-white/20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>
        </div>
    </div>

    {{-- LIST PROJECT --}}
    <div class="px-4 space-y-4">
        @forelse($projects as $project)
            <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-xs relative overflow-hidden">
                {{-- Status Badge --}}
                @if($project->is_golive)
                    <div class="absolute top-0 right-0 bg-emerald-500 text-white text-[9px] font-bold px-3 py-1 rounded-bl-lg">GO LIVE</div>
                @elseif($project->pt2Survey)
                    <div class="absolute top-0 right-0 bg-amber-500 text-white text-[9px] font-bold px-3 py-1 rounded-bl-lg">ON PROGRESS</div>
                @else
                    <div class="absolute top-0 right-0 bg-blue-500 text-white text-[9px] font-bold px-3 py-1 rounded-bl-lg">NEW</div>
                @endif

                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide mb-1">PID Project</p>
                <h2 class="text-lg font-black text-slate-800 tracking-tight">{{ $project->pid }}</h2>
                
                <div class="flex items-center gap-2 mt-3 text-xs text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 text-blue-500"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" /></svg>
                    <span class="truncate">{{ $project->customer_id ?? 'TIF / Exbis' }}</span>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-50 flex justify-between items-center">
                    <div class="text-[10px] text-slate-400 font-medium">
                        Assign: <span class="text-slate-700 font-bold">{{ $project->created_at->format('d M Y') }}</span>
                    </div>

                    {{-- Tombol Eksekusi --}}
                    @if(!$project->is_golive)
                        <a href="{{ route('teknisi.pt2.step1', $project->id) }}" class="bg-blue-600 text-white text-[11px] font-bold px-4 py-2 rounded-lg hover:bg-blue-700 transition shadow-sm">
                            {{ $project->pt2Survey ? 'Lanjut Kerjakan' : 'Mulai Survey' }}
                        </a>
                    @else
                        <span class="bg-slate-100 text-slate-400 text-[11px] font-bold px-4 py-2 rounded-lg">Selesai</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-10">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-slate-600">Belum ada project</h3>
                <p class="text-xs text-slate-400 mt-1">Belum ada LOP PT2 yang di-assign ke Anda.</p>
            </div>
        @endforelse
    </div>

    {{-- BOTTOM NAV --}}
    @include('teknisi.partials.bottom-nav', ['active' => 'inbox'])
</div>
@endsection