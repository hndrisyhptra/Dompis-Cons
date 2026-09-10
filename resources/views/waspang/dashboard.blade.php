@extends('layouts.waspang') {{-- Sesuaikan dengan nama layout parent mobile Anda --}}

@section('content')
<div class="min-h-screen max-w-md mx-auto bg-[#F8FAFC] pb-24 font-sans selection:bg-blue-500 selection:text-white">

    {{-- HEADER USER INFO --}}
    <div class="bg-[#1565D8] text-white px-5 pt-6 pb-6 rounded-b-[2rem] shadow-lg shadow-slate-900/10">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs text-blue-100 font-medium">Semangat Pagi..!</p>
                <h1 class="text-xl font-black tracking-tight leading-tight mt-0.5">
                    {{ auth()->user()->name }}
                </h1>
                <p class="text-[11px] text-blue-100/80 mt-1.5 font-medium">
                    {{ now()->translatedFormat('l, d F Y') }}
                </p>
            </div>

            <div class="relative inline-flex w-9 h-9 items-center justify-center bg-white/15 rounded-xl border border-white/10">
                <i class="fa-solid fa-bell text-white text-sm"></i>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-rose-400 rounded-full ring-2 ring-[#1565D8]"></span>
            </div>
        </div>
    </div>

    {{-- RE-CALCULATION BLOCK ALIGNED WITH INBOX LOGIC (SINKRONISASI 100%) --}}
    @php
        $allProjects = $projects ?? collect();

        $statPreparation = 0;
        $statInstallation = 0;
        $statFinish = 0;

        foreach ($allProjects as $proj) {
            $evidences = $proj->evidences ?? collect();
            $boqItems = $proj->boqItems ?? collect();

            // 1. PERSIAPAN DONE
            $persiapanDone = $evidences->where('stage', 'persiapan')->where('evidence_type', 'barang_tiba')->where('status', 'approved')->count() > 0
                && $evidences->where('stage', 'persiapan')->where('evidence_type', 'perizinan')->where('status', 'approved')->count() > 0;

            // 2. INSTALASI DONE
            $materialBoqItems = $boqItems->filter(function ($boq) {
                return str_starts_with($boq->designator, 'M-') || optional($boq->designatorData)->type === 'material';
            });
            $boqTotal = $materialBoqItems->count();
            $boqDone = $materialBoqItems->filter(function ($boq) use ($evidences) {
                return $evidences->where('stage', 'instalasi')->where('evidence_type', 'progress_boq')->where('boq_item_id', $boq->id_boq)->where('status', 'approved')->count() > 0;
            })->count();
            $instalasiDone = $boqTotal > 0 && $boqDone == $boqTotal;

            // 3. FINISHING DONE
            $finishingDone = $evidences->where('stage', 'finishing')->where('status', 'approved')->count() > 0;

            // AKURASI KELULUSAN (PENGUKURAN DIABAIKAN KARENA OPSIONAL)
            $isProjFinished = ($persiapanDone && $instalasiDone && $finishingDone);

            if ($isProjFinished) {
                $statFinish++;
            } elseif ($instalasiDone) {
                $statInstallation++;
            } else {
                $statPreparation++;
            }
        }

        // Sinkronisasi data widget progress
        $progressDone = $statFinish;
        $progressPercent = $totalAssigned > 0 ? round(($progressDone / $totalAssigned) * 100) : 0;

        // Mengambil update terakhir aktivitas berkas masuk
        $lastUpdate = optional(
            $allProjects->flatMap(fn($p) => $p->evidences ?? collect())
                ->sortByDesc('updated_at')
                ->first()
        )->updated_at;
    @endphp

    {{-- STATISTIK GRID CARDS --}}
    <div class="grid grid-cols-2 gap-3 px-4 -mt-4">
        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">LOP Assigned</p>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight mt-1">{{ $totalAssigned }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-extrabold">
                Total Order
            </span>
        </div>

        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Preparation</p>
            <h2 class="text-2xl font-black text-red-600 tracking-tight mt-1">{{ $statPreparation }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-red-50 text-red-600 text-[10px] font-extrabold">
                Persiapan
            </span>
        </div>

        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Installation</p>
            <h2 class="text-2xl font-black text-amber-600 tracking-tight mt-1">{{ $statInstallation }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-600 text-[10px] font-extrabold">
                Instalasi
            </span>
        </div>

        <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">LOP Finish</p>
            <h2 class="text-2xl font-black text-emerald-600 tracking-tight mt-1">{{ $statFinish }}</h2>
            <span class="inline-flex mt-2 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-extrabold">
                Ready UT
            </span>
        </div>
    </div>

    {{-- AKSI CEPAT NAVIGASI --}}
    <div class="px-4 mt-6">
        <h2 class="text-xs font-black text-slate-400 uppercase tracking-wider mb-3">Aksi Cepat</h2>

        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('waspang.inbox') }}" class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs hover:border-blue-200 transition active:scale-[0.98]">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#1565D8] flex items-center justify-center mb-2.5">
                    <i class="fa-solid fa-inbox text-sm"></i>
                </div>
                <h3 class="font-black text-slate-900 text-sm">Inbox LOP</h3>
                <p class="text-[11px] text-slate-400 font-bold mt-0.5">
                    {{ $activeProjectsCount }} Project Aktif
                </p>
            </a>

            <a href="{{ route('waspang.ready-ut') }}" class="bg-[#1565D8] rounded-2xl p-4 text-white shadow-md shadow-slate-900/10 hover:bg-[#0F4FAF] transition active:scale-[0.98]">
                <div class="w-8 h-8 rounded-lg bg-white/15 text-emerald-300 flex items-center justify-center mb-2.5">
                    <i class="fa-solid fa-circle-check text-sm"></i>
                </div>
                <h3 class="font-black text-white text-sm">List LOP</h3>
                <p class="text-[11px] text-emerald-300 font-bold mt-0.5">
                    {{ $statFinish }} LOP Finish
                </p>
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
                    <p class="text-base font-black text-slate-900 tracking-tight mt-0.5">
                        {{ $progressDone }} dari {{ $totalAssigned }} LOP Selesai
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-bold text-slate-400 uppercase">Update Terakhir</p>
                    <p class="text-[11px] font-bold text-slate-700 mt-0.5">{{ $lastUpdate ? $lastUpdate->diffForHumans() : '-' }}</p>
                </div>
            </div>

            {{-- PROGRESS BAR ENGINE --}}
            <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                {{-- Menggunakan bg-[#1565D8] untuk warna indigo murni yang solid --}}
                <div class="h-full bg-[#1565D8] rounded-full transition-all duration-500" style="width: {{ $progressPercent }}%"></div>
            </div>

            <div class="flex items-center justify-between mt-3 text-xs">
                <p class="text-slate-400 font-medium">Progress Persentase</p>
                <p class="font-black text-[#1565D8]">{{ $progressPercent }}%</p>
            </div>
        </div>
    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'home'])
</div>
@endsection
