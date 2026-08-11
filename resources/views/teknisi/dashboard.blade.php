@extends('layouts.teknisi') {{-- Sesuaikan jika Anda punya layout khusus mobile spt layouts.teknisi --}}

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#f8fafc] pb-24 font-sans selection:bg-blue-500 selection:text-white">

    {{-- HEADER USER INFO --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-6 rounded-b-[1.7rem] shadow-md">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs text-blue-200 font-medium">Semangat Pagi..!</p>
                <h1 class="text-xl font-black tracking-tight leading-tight mt-0.5">
                    {{ auth()->user()->name }}
                </h1>
                <p class="text-[11px] text-blue-100/80 mt-1.5 font-medium">
                    {{ now()->translatedFormat('l, d F Y') }}
                </p>
            </div>

            <div class="relative inline-block bg-white/10 p-2 rounded-xl border border-white/10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-white">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <span class="absolute top-1 right-1 w-2 h-2 bg-rose-500 rounded-full ring-2 ring-blue-700"></span>
            </div>
        </div>
    </div>

    {{-- STATISTIK GRID CARDS --}}
    <div class="grid grid-cols-2 gap-3 px-4 -mt-4">
        <!-- Card 1: LOP Assigned -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">LOP Assigned</p>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight mt-1">{{ $totalAssigned }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-extrabold">
                Total Order PT 2
            </span>
        </div>

        <!-- Card 2: On Progress -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">On Progress</p>
            <h2 class="text-2xl font-black text-amber-600 tracking-tight mt-1">{{ $statOnProgress }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-600 text-[10px] font-extrabold">
                Pengerjaan Lapangan
            </span>
        </div>

        <!-- Card 3: Waiting Approval -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">In Review</p>
            <h2 class="text-2xl font-black text-blue-600 tracking-tight mt-1">{{ $statWaitingApproval }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[10px] font-extrabold">
                Waiting Approval
            </span>
        </div>

        <!-- Card 4: Selesai / Finish -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Selesai</p>
            <h2 class="text-2xl font-black text-emerald-600 tracking-tight mt-1">{{ $statFinish }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-extrabold">
                LOP Finish
            </span>
        </div>
    </div>

    {{-- AKSI CEPAT NAVIGASI --}}
    <div class="px-4 mt-6">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">Aksi Cepat</h2>
        <div class="grid grid-cols-2 gap-3">
            {{-- Mengarah ke Route Inbox Teknisi PT2 --}}
            <a href="{{ route('teknisi.pt2.inbox') }}" class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs hover:border-blue-200 transition active:scale-[0.98]">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-inbox-icon lucide-inbox"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                </div>
                <h3 class="font-black text-slate-800 text-sm">Inbox PT2</h3>
                <p class="text-[11px] text-slate-400 font-bold mt-0.5">{{ $statOnProgress }} Total LOP</p>
            </a>

            <a href="{{ route('teknisi.pt2.inbox', ['status' => 'finish']) }}" class="bg-blue-700 rounded-2xl p-4 text-white shadow-md hover:bg-blue-900 transition active:scale-[0.98]">
                <div class="w-8 h-8 rounded-lg bg-white/10 text-emerald-400 flex items-center justify-center mb-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check-icon lucide-badge-check"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>
                </div>
                <h3 class="font-black text-white text-sm">List Selesai</h3>
                <p class="text-[11px] text-emerald-400 font-bold mt-0.5">{{ $statFinish }} LOP Finish</p>
            </a>
        </div>
    </div>

    {{-- PROGRESS WORKING SUMMARY CARDS --}}
    <div class="px-4 mt-6">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">Progress Pekerjaan</h2>
        <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-xs">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Total Order</p>
                    <p class="text-base font-black text-slate-800 tracking-tight mt-0.5">{{ $progressDone }} dari {{ $totalAssigned }} LOP Selesai</p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-bold text-slate-400 uppercase">Update Terakhir</p>
                    <p class="text-[11px] font-bold text-slate-700 mt-0.5">{{ $lastUpdate ? $lastUpdate->diffForHumans() : '-' }}</p>
                </div>
            </div>
            <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-blue-600 rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
            </div>
            <div class="flex items-center justify-between mt-3 text-xs">
                <p class="text-slate-400 font-medium">Progress Persentase</p>
                <p class="font-black text-blue-600">{{ $progressPercent }}%</p>
            </div>
        </div>
    </div>

    {{-- BOTTOM NAV --}}
    @include('teknisi.partials.bottom-nav', ['active' => 'home'])
</div>
@endsection