@extends('layouts.admin')

@section('content')

@php
    $completionRate = $completionRate ?? 0;

    // Program PT 3 yang SELALU ditampilkan di filter dan Matrix PT 3.
    // Tetap muncul walaupun count pada database = 0.
    // PT 2 sengaja tidak dimasukkan ke filter Program.
    $regularPrograms = collect([
        'OSP',
        'OLO',
        'HEM',
        'NODE B',
        'EKSBIS',
    ]);

    // Satu mapping untuk filter Branch sekaligus fallback tampilan Matrix PT 2.
    $regionMapping = [
        'JATIM' => ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
        'JATENG DIY' => ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
        'BALNUS' => ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES'],
    ];

    // Widget "Ringkasan & Alur Progress PT 3": BOQ Ready/Belum BOQ dan Sudah
    // Assign/Belum Assign ditampilkan berpasangan dalam 1 card, ditambah On
    // Progress dan Completed - total cuma 4 card ringkas (dari sebelumnya 9).
    // Total LOP jadi angka acuan di header widget. SEMUA angka bisa diklik
    // untuk membuka modal daftar LOP-nya (lihat matrixDetailModal() & metric
    // yang dikirim harus sinkron dengan DashboardController::matrixDetail()).
    // Semua otomatis ikut berubah saat filter region/branch/program/status
    // diterapkan di halaman ini.
    $totalForPercent = $totalLop ?? 0;
    $pctOf = fn ($value) => $totalForPercent > 0 ? round(($value / $totalForPercent) * 100) : 0;

    $pipelineSteps = [
        [
            'metric' => 'boq_ready',
            'label' => 'BOQ Ready',
            'value' => $boqReady ?? 0,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-package-open"><path d="M12 22v-9"/><path d="M15.17 2.21a1.67 1.67 0 0 1 1.63 0L21 4.57a1.93 1.93 0 0 1 0 3.36L8.82 14.79a1.655 1.655 0 0 1-1.64 0L3 12.43a1.93 1.93 0 0 1 0-3.36z"/><path d="M20 13v3.87a2.06 2.06 0 0 1-1.11 1.83l-6 3.08a1.93 1.93 0 0 1-1.78 0l-6-3.08A2.06 2.06 0 0 1 4 16.87V13"/><path d="M21 12.43a1.93 1.93 0 0 0 0-3.36L8.83 2.2a1.64 1.64 0 0 0-1.63 0L3 4.57a1.93 1.93 0 0 0 0 3.36l12.18 6.86a1.636 1.636 0 0 0 1.63 0z"/></svg>',
            'icon_bg' => 'bg-blue-50 dark:bg-blue-500/10',
            'icon_text' => 'text-blue-600 dark:text-blue-400',
            'bar' => 'bg-blue-500',
            'sub' => ['metric' => 'belum_boq', 'label' => 'Belum BOQ', 'value' => max($totalForPercent - ($boqReady ?? 0), 0)],
        ],
        [
            'metric' => 'assigned',
            'label' => 'Sudah Assign',
            'value' => $assignedLop ?? 0,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-user-check"><path d="m16 11 2 2 4-4"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
            'icon_bg' => 'bg-indigo-50 dark:bg-indigo-500/10',
            'icon_text' => 'text-indigo-600 dark:text-indigo-400',
            'bar' => 'bg-indigo-500',
            'sub' => ['metric' => 'unassigned', 'label' => 'Belum Assign', 'value' => max($totalForPercent - ($assignedLop ?? 0), 0)],
        ],
        [
            'metric' => 'waiting',
            'label' => 'On Progress',
            'value' => $onProgress ?? 0,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-activity"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.48 12H2"/></svg>',
            'icon_bg' => 'bg-sky-50 dark:bg-sky-500/10',
            'icon_text' => 'text-sky-600 dark:text-sky-400',
            'bar' => 'bg-sky-500',
            'sub' => null,
        ],
        [
            'metric' => 'completed',
            'label' => 'Completed',
            'value' => $completedApproval ?? 0,
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-circle-check-big"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>',
            'icon_bg' => 'bg-emerald-50 dark:bg-emerald-500/10',
            'icon_text' => 'text-emerald-600 dark:text-emerald-400',
            'bar' => 'bg-emerald-500',
            'sub' => null,
        ],
    ];

@endphp

<div class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6" x-data="matrixDetailModal()">

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            <div>
                <p class="text-xs font-black text-blue-700 uppercase tracking-widest">Analytics Dashboard</p>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">Dashboard Monitoring</h1>
            </div>

            <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-4 min-w-[220px]">
                <p class="text-xs text-slate-500 font-bold uppercase">Completion Rate</p>
                <div class="flex items-end justify-between gap-3 mt-2">
                    <p class="text-3xl font-black text-emerald-700">{{ $completionRate }}%</p>
                    <span class="text-xs font-black text-slate-500">{{ number_format($completedApproval ?? 0) }}/{{ number_format($totalLop ?? 0) }}</span>
                </div>
                <div class="mt-3 h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ min($completionRate, 100) }}%"></div>
                </div>
            </div>
        </div>

        {{-- FILTER PANEL --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <form method="GET" action="{{ route('dashboard') }}" id="filterForm">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    
                    {{-- Region Filter --}}
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold uppercase text-slate-500">Region</label>
                        <select name="region" id="regionSelect" onchange="handleRegionChange()"
                                class="w-full h-10 px-3 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-800 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition">
                            <option value="">Semua Region</option>
                            @foreach(array_keys($regionMapping) as $region)
                                <option value="{{ $region }}" {{ strtoupper(request('region', '')) === $region ? 'selected' : '' }}>
                                    {{ $region }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Branch Filter --}}
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold uppercase text-slate-500">Branch</label>
                        <select name="branch" id="branchSelect" onchange="document.getElementById('filterForm').submit()"
                                class="w-full h-10 px-3 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-800 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition">
                            <option value="">Semua Branch</option>
                            {{-- Diisi via JS --}}
                        </select>
                    </div>

                    {{-- Program PT 3 Filter --}}
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold uppercase text-slate-500">Program PT 3</label>
                        <select name="program" onchange="document.getElementById('filterForm').submit()"
                                class="w-full h-10 px-3 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-800 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition">
                            <option value="">Semua Program</option>
                            @foreach($regularPrograms as $program)
                                <option value="{{ $program }}" {{ request('program') == $program ? 'selected' : '' }}>{{ $program }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Filter --}}
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold uppercase text-slate-500">Status LOP</label>
                        <select name="status" onchange="document.getElementById('filterForm').submit()"
                                class="w-full h-10 px-3 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-800 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition">
                            <option value="">Semua Status</option>
                            <option value="preparation" {{ request('status') == 'preparation' ? 'selected' : '' }}>Prepare</option>
                            <option value="instalasi" {{ request('status') == 'instalasi' ? 'selected' : '' }}>On Progress</option>
                            <option value="finishing" {{ request('status') == 'finishing' ? 'selected' : '' }}>Finish</option>
                            <option value="drop" {{ request('status') == 'drop' ? 'selected' : '' }}>Drop</option>
                        </select>
                    </div>

                    {{-- Reset Button --}}
                    @if(request('program') || request('branch') || request('region') || request('status'))
                        <div>
                            <a href="{{ route('dashboard') }}" 
                               class="flex items-center justify-center h-10 px-4 rounded-xl border border-dashed border-red-300 text-xs font-bold text-red-600 hover:bg-red-50 transition w-full">
                                Reset Filter
                            </a>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        {{-- RINGKASAN & ALUR PROGRESS PT 3 (1 widget ringkas, klik angka = modal daftar LOP) --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Ringkasan &amp; Alur Progress PT 3</h2>
                    <p class="text-xs text-slate-500 mt-1">Klik angka untuk lihat daftar LOP-nya. Mengikuti filter yang aktif.</p>
                </div>

                <button type="button"
                        @click="show({type:'assignment', region:'', branch:'', metric:''})"
                        class="flex items-center gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-2.5 self-start sm:self-auto hover:border-blue-300 dark:hover:border-blue-700 transition text-left">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center shrink-0 text-blue-600 dark:text-blue-400">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-file-spreadsheet"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-slate-400 leading-none">Total LOP PT 3</p>
                        <p class="text-xl font-black text-slate-900 dark:text-white leading-tight mt-1 hover:underline decoration-2 underline-offset-2">{{ number_format($totalLop ?? 0) }}</p>
                    </div>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($pipelineSteps as $step)
                    @php
                        $percent = $pctOf($step['value']);
                        $subPercent = $step['sub'] ? $pctOf($step['sub']['value']) : null;
                    @endphp
                    <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl {{ $step['icon_bg'] }} {{ $step['icon_text'] }} flex items-center justify-center shrink-0">
                                {!! $step['icon'] !!}
                            </div>
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $step['label'] }}</p>
                        </div>

                        <div class="mt-3 flex items-end justify-between gap-3">
                            <button type="button" class="text-left"
                                    @click="show({type:'assignment', region:'', branch:'', metric:'{{ $step['metric'] }}'})">
                                <p class="text-2xl font-black text-slate-900 dark:text-white leading-none hover:underline decoration-2 underline-offset-2">{{ number_format($step['value']) }}</p>
                                <p class="text-[10px] text-slate-400 mt-1">{{ $percent }}% dari total</p>
                            </button>

                            @if($step['sub'])
                                <button type="button" class="text-right"
                                        @click.stop="show({type:'assignment', region:'', branch:'', metric:'{{ $step['sub']['metric'] }}'})">
                                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400 hover:underline decoration-2 underline-offset-2">{{ number_format($step['sub']['value']) }}</p>
                                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $step['sub']['label'] }} · {{ $subPercent }}%</p>
                                </button>
                            @endif
                        </div>

                        <div class="mt-3 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-full {{ $step['bar'] }}" style="width: {{ min($percent, 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- TABEL REKAP COLLAPSIBLE PER REGION --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-slate-800">Rekap Assignment & Status Project PT 3</h2>
                    <p class="text-xs text-slate-500 mt-1">Klik pada nama Region untuk melihat detail per Branch.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-slate-100/60 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-6 py-3 text-left">Breakdown (Region / Branch)</th>
                            <th class="px-3 py-3 text-center">Total LOP</th>
                            <th class="px-3 py-3 text-center">Assign</th>
                            <th class="px-3 py-3 text-center">In Review</th>
                            <th class="px-3 py-3 text-center">Complete (Done)</th>
                            <th class="px-6 py-3 text-right">Progress Rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        @forelse($statsByRegion ?? [] as $i => $reg)
                            <tr class="cursor-pointer bg-white hover:bg-slate-50 transition" onclick="toggleRegion('region-{{ $i }}', 'icon-{{ $i }}')">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-5 h-5 flex items-center justify-center rounded bg-blue-100 text-blue-600">
                                            <svg id="icon-{{ $i }}" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200"><path d="m9 18 6-6-6-6"/></svg>
                                        </div>
                                        <span class="font-black text-slate-800 text-sm">{{ $reg['region'] }}</span>
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-center font-black text-slate-700 text-sm">
                                    <span class="cursor-pointer hover:underline decoration-2 underline-offset-2" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'total'})">{{ $reg['total'] }}</span>
                                </td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-blue-50 text-blue-700 font-black hover:bg-blue-100" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'assigned'})">{{ $reg['assigned'] }}</span></td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-amber-50 text-amber-700 font-black hover:bg-amber-100" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'waiting'})">{{ $reg['waiting'] }}</span></td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-black hover:bg-emerald-100" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'completed'})">{{ $reg['completed'] }}</span></td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <div class="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $reg['percent'] }}%"></div>
                                        </div>
                                        <span class="font-black text-slate-800">{{ $reg['percent'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                            
                            @foreach($reg['branches'] as $br)
                                <tr class="hidden bg-slate-50/50 hover:bg-slate-100/50 transition region-{{ $i }}">
                                    <td class="px-6 py-3 pl-[3.25rem]">
                                        <span class="font-bold text-slate-600"> • {{ $br['name'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center font-bold text-slate-600">
                                        <span class="cursor-pointer hover:underline decoration-2 underline-offset-2" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', metric:'total'})">{{ $br['total'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-blue-600 font-bold hover:underline" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', metric:'assigned'})">{{ $br['assigned'] }}</span></td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-amber-600 font-bold hover:underline" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', metric:'waiting'})">{{ $br['waiting'] }}</span></td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-emerald-600 font-bold hover:underline" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', metric:'completed'})">{{ $br['completed'] }}</span></td>
                                    <td class="px-6 py-3 text-right font-black text-slate-500">{{ $br['percent'] }}%</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-slate-400 font-medium">Tidak ada data statistik tersedia berdasarkan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MATRIX PROJECT REGULAR --}}
        <div class="mt-5 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-slate-800">Matriks Progress Project PT 3</h2>
                    <p class="text-xs text-slate-500 mt-1">Program PT 3: OSP, OLO, HEM, NODE B, EKSBIS.</p>
                </div>
            </div>

            <div class="overflow-x-auto pb-4">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-slate-100/60 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th rowspan="2" class="px-6 py-3 text-left border-r border-slate-200/60 align-middle whitespace-nowrap sticky left-0 bg-slate-100/90 backdrop-blur-sm z-10">
                                Wilayah (Region / Branch)
                            </th>
                            @foreach($regularPrograms as $prog)
                                <th colspan="4" class="px-3 py-2 text-center border-b border-r border-slate-200/60 whitespace-nowrap">{{ strtoupper(trim($prog)) === 'EKSBIS' ? 'Eksbis' : $prog }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($regularPrograms as $prog)
                                <th class="px-3 py-2 text-center text-blue-600 bg-blue-50/50">Prepare</th>
                                <th class="px-3 py-2 text-center text-amber-600 bg-amber-50/50">Progress</th>
                                <th class="px-3 py-2 text-center text-emerald-600 bg-emerald-50/50">Finish</th>
                                <th class="px-3 py-2 text-center text-indigo-600 bg-indigo-50/50 border-r border-slate-200/60">% Done</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/60">
                        @forelse($matrixData ?? [] as $i => $reg)
                            <tr class="cursor-pointer bg-white hover:bg-slate-50 transition group" onclick="toggleRegion('matrix-reg-{{ $i }}', 'icon-matrix-{{ $i }}')">
                                <td class="px-6 py-4 border-r border-slate-200/60 sticky left-0 bg-white group-hover:bg-slate-50 z-10">
                                    <div class="flex items-center gap-3">
                                        <div class="w-5 h-5 flex items-center justify-center rounded bg-indigo-100 text-indigo-600">
                                            <svg id="icon-matrix-{{ $i }}" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200"><path d="m9 18 6-6-6-6"/></svg>
                                        </div>
                                        <span class="font-black text-slate-800 text-sm whitespace-nowrap">{{ $reg['region'] }}</span>
                                    </div>
                                </td>
                                
                                @foreach($regularPrograms as $prog)
                                    @php 
                                        $stats = $reg['programs'][$prog] ?? [
                                            'preparation' => 0,
                                            'instalasi' => 0,
                                            'finishing' => 0,
                                        ]; 
                                        $totalProyek = $stats['preparation'] + $stats['instalasi'] + $stats['finishing'];
                                        $persentase = $totalProyek > 0 ? round(($stats['finishing'] / $totalProyek) * 100) : 0;
                                    @endphp
                                    <td class="px-3 py-4 text-center font-bold text-slate-700 bg-blue-50/20"><span class="cursor-pointer hover:underline decoration-2 underline-offset-2" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'', program:'{{ $prog }}', metric:'preparation'})">{{ $stats['preparation'] ?: '-' }}</span></td>
                                    <td class="px-3 py-4 text-center font-bold text-slate-700 bg-amber-50/20"><span class="cursor-pointer hover:underline decoration-2 underline-offset-2" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'', program:'{{ $prog }}', metric:'instalasi'})">{{ $stats['instalasi'] ?: '-' }}</span></td>
                                    <td class="px-3 py-4 text-center font-bold text-slate-700 bg-emerald-50/20"><span class="cursor-pointer hover:underline decoration-2 underline-offset-2" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'', program:'{{ $prog }}', metric:'finishing'})">{{ $stats['finishing'] ?: '-' }}</span></td>
                                    <td class="px-3 py-4 text-center font-black text-indigo-700 bg-indigo-50/20 border-r border-slate-200/60">{{ $persentase }}%</td>
                                @endforeach
                            </tr>
                            
                            @foreach($reg['branches'] as $br)
                                <tr class="hidden bg-slate-50/50 hover:bg-slate-100/50 transition matrix-reg-{{ $i }} group-branch">
                                    <td class="px-6 py-3 pl-[3.25rem] border-r border-slate-200/60 sticky left-0 bg-slate-50/90 z-10">
                                        <span class="font-bold text-slate-600 whitespace-nowrap">• {{ $br['name'] }}</span>
                                    </td>
                                    
                                    @foreach($regularPrograms as $prog)
                                        @php 
                                            $stats = $br['programs'][$prog] ?? [
                                                'preparation' => 0,
                                                'instalasi' => 0,
                                                'finishing' => 0,
                                            ]; 
                                            $totalProyek = $stats['preparation'] + $stats['instalasi'] + $stats['finishing'];
                                            $persentase = $totalProyek > 0 ? round(($stats['finishing'] / $totalProyek) * 100) : 0;
                                        @endphp
                                        <td class="px-3 py-3 text-center text-blue-600 font-semibold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', program:'{{ $prog }}', metric:'preparation'})">{{ $stats['preparation'] ?: '-' }}</span></td>
                                        <td class="px-3 py-3 text-center text-amber-600 font-semibold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', program:'{{ $prog }}', metric:'instalasi'})">{{ $stats['instalasi'] ?: '-' }}</span></td>
                                        <td class="px-3 py-3 text-center text-emerald-600 font-semibold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', program:'{{ $prog }}', metric:'finishing'})">{{ $stats['finishing'] ?: '-' }}</span></td>
                                        <td class="px-3 py-3 text-center text-indigo-600 font-black border-r border-slate-200/60">{{ $persentase }}%</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ 1 + ($regularPrograms->count() * 4) }}" class="px-6 py-10 text-center text-slate-400 font-medium">
                                    Tidak ada data project terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>


{{-- ============================================================= --}}
{{-- MATRIX PROJECT PT 2 --}}
{{-- ============================================================= --}}
<div class="mt-5 bg-white dark:bg-slate-900 rounded-[2rem] border border-indigo-200 dark:border-indigo-900 overflow-hidden shadow-sm">
    <div class="px-6 py-5 border-b border-indigo-100 dark:border-indigo-900 bg-indigo-50/50">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-black uppercase tracking-wider text-indigo-900 dark:text-indigo-300">
                    Matriks Progress Project PT 2
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Sumber khusus PT 2. Tidak ter filter Program PT 3.
                </p>
            </div>

            <span class="px-3 py-1.5 rounded-xl bg-indigo-100 text-indigo-700 text-[10px] font-black shrink-0">
                PT 2
            </span>
        </div>
    </div>

    <div class="overflow-x-auto pb-4">
        <table class="w-full text-xs border-collapse">
            <thead class="bg-indigo-50/50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                <tr>
                    <th class="px-6 py-4 text-left border-r border-indigo-100 whitespace-nowrap">Region / Branch</th>
                    <th class="px-4 py-4 text-center text-blue-600">Preparation</th>
                    <th class="px-4 py-4 text-center text-amber-600">Instalasi</th>
                    <th class="px-4 py-4 text-center text-emerald-600">Finishing / Go-Live</th>
                    <th class="px-4 py-4 text-center text-indigo-700">Total</th>
                    <th class="px-6 py-4 text-right">% Finish</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-indigo-100/70">
                @forelse($matrixPt2Data ?? [] as $i => $reg)
                    @php
                        $regionStats = $reg['stats'] ?? [
                            'preparation' => 0,
                            'instalasi' => 0,
                            'finishing' => 0,
                            'total' => 0,
                            'percent' => 0,
                        ];

                        $regionSlug = \Illuminate\Support\Str::slug($reg['region']);
                    @endphp

                    <tr class="cursor-pointer bg-white hover:bg-indigo-50/40 transition"
                        onclick="toggleRegion('matrix-pt2-{{ $regionSlug }}', 'icon-pt2-{{ $regionSlug }}')">
                        <td class="px-6 py-4 border-r border-indigo-100">
                            <div class="flex items-center gap-3">
                                <div class="w-5 h-5 flex items-center justify-center rounded bg-indigo-100 text-indigo-600">
                                    <svg id="icon-pt2-{{ $regionSlug }}"
                                         xmlns="http://www.w3.org/2000/svg"
                                         width="16"
                                         height="16"
                                         viewBox="0 0 24 24"
                                         fill="none"
                                         stroke="currentColor"
                                         stroke-width="3"
                                         stroke-linecap="round"
                                         stroke-linejoin="round"
                                         class="transition-transform duration-200">
                                        <path d="m9 18 6-6-6-6"/>
                                    </svg>
                                </div>

                                <span class="font-black text-slate-800">{{ $reg['region'] }}</span>
                            </div>
                        </td>

                        <td class="px-4 py-4 text-center font-black text-blue-700"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'', metric:'preparation'})">{{ $regionStats['preparation'] ?? 0 }}</span></td>
                        <td class="px-4 py-4 text-center font-black text-amber-700"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'', metric:'instalasi'})">{{ $regionStats['instalasi'] ?? 0 }}</span></td>
                        <td class="px-4 py-4 text-center font-black text-emerald-700"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'', metric:'finishing'})">{{ $regionStats['finishing'] ?? 0 }}</span></td>
                        <td class="px-4 py-4 text-center font-black text-indigo-700"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'', metric:'total'})">{{ $regionStats['total'] ?? 0 }}</span></td>

                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-3">
                                <div class="w-24 h-2 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 rounded-full"
                                         style="width: {{ min($regionStats['percent'] ?? 0, 100) }}%"></div>
                                </div>

                                <span class="font-black text-indigo-700">{{ $regionStats['percent'] ?? 0 }}%</span>
                            </div>
                        </td>
                    </tr>

                    @foreach($reg['branches'] ?? [] as $branch)
                        @php
                            $branchStats = $branch['stats'] ?? [
                                'preparation' => 0,
                                'instalasi' => 0,
                                'finishing' => 0,
                                'total' => 0,
                                'percent' => 0,
                            ];
                        @endphp

                        <tr class="hidden bg-slate-50/70 matrix-pt2-{{ $regionSlug }}">
                            <td class="px-6 py-3 pl-[3.25rem] border-r border-indigo-100">
                                <span class="font-bold text-slate-600">• {{ $branch['name'] }}</span>
                            </td>

                            <td class="px-4 py-3 text-center text-blue-600 font-bold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'{{ $branch['name'] }}', metric:'preparation'})">{{ $branchStats['preparation'] ?? 0 }}</span></td>
                            <td class="px-4 py-3 text-center text-amber-600 font-bold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'{{ $branch['name'] }}', metric:'instalasi'})">{{ $branchStats['instalasi'] ?? 0 }}</span></td>
                            <td class="px-4 py-3 text-center text-emerald-600 font-bold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'{{ $branch['name'] }}', metric:'finishing'})">{{ $branchStats['finishing'] ?? 0 }}</span></td>
                            <td class="px-4 py-3 text-center text-indigo-600 font-black"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'{{ $branch['name'] }}', metric:'total'})">{{ $branchStats['total'] ?? 0 }}</span></td>
                            <td class="px-6 py-3 text-right text-indigo-600 font-black">{{ $branchStats['percent'] ?? 0 }}%</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-slate-400 font-medium">
                            Tidak ada data PT 2 berdasarkan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

    </div>

    {{-- MODAL DETAIL LOP (KLIK ANGKA PADA TABEL MATRIX) --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="close()"></div>

        <div class="relative bg-white dark:bg-slate-900 w-full max-w-4xl max-h-[85vh] rounded-[2rem] shadow-2xl flex flex-col overflow-hidden"
             x-show="open" x-transition>

            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between gap-4 bg-slate-50/50">
                <div>
                    <p class="text-[10px] font-black text-blue-700 uppercase tracking-widest">Detail LOP</p>
                    <h3 class="text-sm md:text-base font-black text-slate-900 dark:text-white mt-1" x-text="title"></h3>
                    <p class="text-xs text-slate-500 mt-1">
                        <span x-text="count"></span> LOP ditemukan
                    </p>
                </div>
                <button type="button" @click="close()"
                        class="w-9 h-9 shrink-0 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="overflow-y-auto flex-1">
                <template x-if="loading">
                    <div class="flex items-center justify-center py-16">
                        <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
                    </div>
                </template>

                <template x-if="!loading && error">
                    <div class="px-6 py-10 text-center text-sm font-semibold text-red-500" x-text="error"></div>
                </template>

                <template x-if="!loading && !error && rows.length === 0">
                    <div class="px-6 py-10 text-center text-sm text-slate-400 font-medium">Tidak ada LOP untuk kategori ini.</div>
                </template>

                <template x-if="!loading && !error && rows.length > 0">
                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-slate-100/60 text-slate-500 font-bold uppercase tracking-wider text-[10px] sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left">No</th>
                                <th class="px-4 py-3 text-left">PID</th>
                                <th class="px-4 py-3 text-left">Project / LOP</th>
                                <th class="px-4 py-3 text-left">Branch / STO</th>
                                <th class="px-4 py-3 text-left">Program</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            <template x-for="row in rows" :key="row.no">
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 text-slate-500 font-semibold" x-text="row.no"></td>
                                    <td class="px-4 py-3 font-bold text-slate-700" x-text="row.pid"></td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-slate-800" x-text="row.project_name"></p>
                                        <p class="text-slate-500" x-text="row.lop_name"></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-700" x-text="row.branch"></p>
                                        <p class="text-slate-400" x-text="row.sto"></p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600 font-semibold" x-text="row.program"></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 font-black text-[10px]" x-text="row.status_label"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a :href="row.detail_url" target="_blank"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-900 text-white text-[10px] font-bold hover:bg-slate-700 transition">
                                            Lihat
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </template>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPT FILTER & COLLAPSIBLE --}}
<script>
    const regionMapping = @json($regionMapping);
    const currentBranch = @json(strtoupper(request('branch', '')));

    // Filter global yang sedang aktif di halaman Dashboard (dipakai sebagai
    // konteks tambahan saat fetch detail LOP untuk modal klik angka matrix).
    const activeDashboardFilters = {
        program: @json(request('program', '')),
        region: @json(request('region', '')),
        branch: @json(request('branch', '')),
        status: @json(request('status', '')),
    };

    function matrixDetailModal() {
        return {
            open: false,
            loading: false,
            error: '',
            title: '',
            rows: [],
            count: 0,

            async show(params) {
                this.open = true;
                this.loading = true;
                this.error = '';
                this.rows = [];
                this.title = '';
                this.count = 0;

                const query = new URLSearchParams({
                    type: params.type || '',
                    region: params.region || '',
                    branch: params.branch || '',
                    program: params.program || '',
                    metric: params.metric || '',
                    f_program: activeDashboardFilters.program,
                    f_region: activeDashboardFilters.region,
                    f_branch: activeDashboardFilters.branch,
                    f_status: activeDashboardFilters.status,
                }).toString();

                try {
                    const res = await fetch(`{{ route('admin.dashboard.matrix-detail') }}?${query}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    const data = await res.json();

                    if (!res.ok) {
                        this.error = data.message || 'Gagal memuat data LOP.';
                    } else {
                        this.title = data.title;
                        this.rows = data.rows || [];
                        this.count = data.count || 0;
                    }
                } catch (e) {
                    this.error = 'Terjadi kesalahan saat memuat data LOP.';
                } finally {
                    this.loading = false;
                }
            },

            close() {
                this.open = false;
            },
        };
    }

    function toggleRegion(regionClass, iconId) {
        const rows = document.querySelectorAll('.' + regionClass);
        let opened = false;

        rows.forEach(row => {
            if (row.classList.contains('hidden')) {
                row.classList.remove('hidden');
                opened = true;
            } else {
                row.classList.add('hidden');
            }
        });

        const icon = document.getElementById(iconId);
        if (icon) {
            icon.style.transform = opened ? 'rotate(90deg)' : 'rotate(0deg)';
        }
    }

    function populateBranchDropdown(preserveCurrent = true) {
        const regionSelect = document.getElementById('regionSelect');
        const branchSelect = document.getElementById('branchSelect');

        if (!regionSelect || !branchSelect) {
            return;
        }

        const selectedRegion = (regionSelect.value || '').toUpperCase();
        const selectedBranch = preserveCurrent ? currentBranch : '';

        branchSelect.innerHTML = '<option value="">Semua Branch</option>';

        let branches = [];

        if (selectedRegion && regionMapping[selectedRegion]) {
            branches = regionMapping[selectedRegion];
        } else {
            branches = Object.values(regionMapping).flat();
        }

        [...new Set(branches)].forEach(branch => {
            const option = document.createElement('option');
            option.value = branch;
            option.textContent = branch;

            if (branch.toUpperCase() === selectedBranch) {
                option.selected = true;
            }

            branchSelect.appendChild(option);
        });
    }

    function handleRegionChange() {
        populateBranchDropdown(false);
        document.getElementById('filterForm').submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        populateBranchDropdown(true);
    });
</script>

@endsection