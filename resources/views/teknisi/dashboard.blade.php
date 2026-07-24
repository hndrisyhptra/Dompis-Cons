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

    {{-- RE-CALCULATION BLOCK KHUSUS PT2 TEKNISI --}}
    @php
        $allProjects = $projects ?? collect();
        
        $statOnProgress = 0;
        $statWaitingApproval = 0;
        $statFinish = 0;

        foreach ($allProjects as $proj) {
            if ($proj->is_golive) {
                // Skenario 3: Project sudah di-approve SDI / GoLive
                $statFinish++;
            } elseif ($proj->pt2Mancore) {
                // Skenario 2: Teknisi sudah selesai Step 5 (Mancore), tapi belum GoLive
                $statWaitingApproval++;
            } else {
                // Skenario 1: Project masih di Step 1 sampai sebelum input Step 5
                $statOnProgress++;
            }
        }

        // Sinkronisasi data widget progress
        $progressDone = $statFinish;
        $progressPercent = $totalAssigned > 0 ? round(($progressDone / $totalAssigned) * 100) : 0;
        
        // Mengambil update terakhir
        $lastUpdate = optional(
            $allProjects->flatMap(fn($p) => $p->evidences ?? collect())
                ->sortByDesc('updated_at')
                ->first()
        )->updated_at;
    @endphp

    {{-- STATISTIK GRID CARDS --}}
    <div class="grid grid-cols-2 gap-3 px-4 -mt-4">
        <!-- Card 1: Tetap (LOP Assigned) -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">LOP Assigned</p>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight mt-1">{{ $totalAssigned }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-extrabold">
                Total Order
            </span>
        </div>

        <!-- Card 2: On Progress (Progress Instalasi & Finish Instalasi) -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">On Progress</p>
            <h2 class="text-2xl font-black text-amber-600 tracking-tight mt-1">{{ $statOnProgress }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-600 text-[10px] font-extrabold">
                Progress & Finish Instalasi
            </span>
        </div>

        <!-- Card 3: Waiting Approval (Step 5 Done) -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">In Review</p>
            <h2 class="text-2xl font-black text-blue-600 tracking-tight mt-1">{{ $statWaitingApproval }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-600 text-[10px] font-extrabold">
                Waiting Approval
            </span>
        </div>

        <!-- Card 4: Selesai / GoLive -->
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Go Live</p>
            <h2 class="text-2xl font-black text-emerald-600 tracking-tight mt-1">{{ $statFinish }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-extrabold">
                Project Selesai
            </span>
        </div>
    </div>

    {{-- AKSI CEPAT NAVIGASI --}}
    <div class="px-4 mt-6">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">Aksi Cepat</h2>
        <div class="grid grid-cols-2 gap-3">
            {{-- Mengarah ke Route Teknisi --}}
            <a href="{{ route('teknisi.pt2.index') }}" class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs hover:border-blue-200 transition active:scale-[0.98]">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center mb-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 13.5l3 3 3-3m-3 3v-6m10.125-3H17.25m3 0v1.125c0 .621-.504 1.125-1.125 1.125H3.75A1.125 1.125 0 012.625 11.25V5.25m17.625 0A1.125 1.125 0 0019.125 4.125H4.875A1.125 1.125 0 003.75 5.25m17.625 0v11.25c0 .621-.504 1.125-1.125 1.125H3.75a1.125 1.125 0 01-1.125-1.125V5.25" />
                    </svg>
                </div>
                <h3 class="font-black text-slate-800 text-sm">Inbox PT2</h3>
                <p class="text-[11px] text-slate-400 font-bold mt-0.5">{{ $activeProjectsCount }} Project Aktif</p>
            </a>

            <a href="#" class="bg-blue-700 rounded-2xl p-4 text-white shadow-md hover:bg-blue-900 transition active:scale-[0.98]">
                <div class="w-8 h-8 rounded-lg bg-white/10 text-emerald-400 flex items-center justify-center mb-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                    </svg>
                </div>
                <h3 class="font-black text-white text-sm">List Selesai</h3>
                <p class="text-[11px] text-emerald-400 font-bold mt-0.5">{{ $statFinish }} LOP Finish</p>
            </a>
        </div>
    </div>

    {{-- PROGRESS WORKRING SUMMARY CARDS --}}
    <div class="px-4 mt-6">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">Progress Pekerjaan</h2>
        <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-xs">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Total Project</p>
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