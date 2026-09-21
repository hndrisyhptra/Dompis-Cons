@extends('layouts.admin')

@section('content')

@php
    $completionRate = $completionRate ?? 0;
 
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
        <div class="rounded-[0.5rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            <div>
                <p class="text-xs font-black text-blue-700 uppercase tracking-widest">Analytics Dashboard</p>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">Dashboard Monitoring</h1>
            </div> 
            <div class="rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-4 min-w-[220px]">
                <p class="text-xs text-slate-500 font-bold uppercase">Completion Rate</p>
                <div class="flex items-end justify-between gap-3 mt-2">
                    <p class="text-3xl font-black text-emerald-700">{{ $completionRate }}%</p>
                    <span class="text-xs font-black text-slate-500">{{ number_format($completedApproval ?? 0) }}/{{ number_format($totalLop ?? 0) }}</span>
                </div>
                <div class="mt-3 h-2 rounded-lg bg-slate-200 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-lg bg-emerald-500" style="width: {{ min($completionRate, 100) }}%"></div>
                </div>
            </div>
        </div> 
        {{-- ============================================================= --}}
        {{-- FILTER PANEL --}}
        {{-- ============================================================= --}}
        <div
            class="bg-white dark:bg-slate-900
                   rounded-lg
                   border border-slate-200 dark:border-slate-800
                   p-5
                   shadow-sm" > 
            <form
                method="GET"
                action="{{ route('dashboard') }}"
                id="filterForm" >

                <div
                    class="grid grid-cols-1
                           sm:grid-cols-2
                           lg:grid-cols-5
                           gap-4
                           items-end" >

                    {{-- REGION --}}
                    <div class="space-y-1.5">

                        <label
                            for="regionSelect"
                            class="block text-[11px]
                                   font-bold uppercase
                                   text-slate-500 dark:text-slate-400" >
                            Region
                        </label>

                        <select
                            name="region"
                            id="regionSelect"
                            onchange="handleRegionChange()"
                            class="w-full h-10 px-3
                                   rounded-lg
                                   bg-slate-50 dark:bg-slate-950
                                   border border-slate-200 dark:border-slate-700
                                   text-sm font-medium
                                   text-slate-800 dark:text-slate-200
                                   outline-none
                                   focus:ring-2
                                   focus:ring-blue-100 dark:focus:ring-blue-500/20
                                   focus:border-blue-500 dark:focus:border-blue-500
                                   transition" >

                            <option value="">
                                Semua Region
                            </option>

                            @foreach(array_keys($regionMapping) as $region)

                                <option
                                    value="{{ $region }}"
                                    {{ strtoupper(request('region', '')) === $region ? 'selected' : '' }}>
                                    {{ $region }}
                                </option> 
                            @endforeach 
                        </select> 
                    </div> 
                    {{-- BRANCH --}}
                    <div class="space-y-1.5"> 
                        <label
                            for="branchSelect"
                            class="block text-[11px]
                                   font-bold uppercase
                                   text-slate-500 dark:text-slate-400" >
                            Branch
                        </label>

                        <select
                            name="branch"
                            id="branchSelect"
                            onchange="document.getElementById('filterForm').submit()"
                            class="w-full h-10 px-3
                                   rounded-lg
                                   bg-slate-50 dark:bg-slate-950
                                   border border-slate-200 dark:border-slate-700
                                   text-sm font-medium
                                   text-slate-800 dark:text-slate-200
                                   outline-none
                                   focus:ring-2
                                   focus:ring-blue-100 dark:focus:ring-blue-500/20
                                   focus:border-blue-500
                                   transition" >
                            <option value="">
                                Semua Branch
                            </option>
                        </select> 
                    </div> 
                    {{-- PROGRAM --}}
                    <div class="space-y-1.5"> 
                        <label
                            class="block text-[11px]
                                   font-bold uppercase
                                   text-slate-500 dark:text-slate-400" >
                            Program PT 3
                        </label>

                        <select
                            name="program"
                            onchange="document.getElementById('filterForm').submit()"
                            class="w-full h-10 px-3
                                   rounded-lg
                                   bg-slate-50 dark:bg-slate-950
                                   border border-slate-200 dark:border-slate-700
                                   text-sm font-medium
                                   text-slate-800 dark:text-slate-200
                                   outline-none
                                   focus:ring-2
                                   focus:ring-blue-100 dark:focus:ring-blue-500/20
                                   focus:border-blue-500
                                   transition" > 
                            <option value="">
                                Semua Program
                            </option> 
                            @foreach($regularPrograms as $program) 
                                <option
                                    value="{{ $program }}"
                                    {{ request('program') == $program ? 'selected' : '' }}>
                                    {{ $program }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    {{-- STATUS --}}
                    <div class="space-y-1.5">
                        <label
                            class="block text-[11px]
                                   font-bold uppercase
                                   text-slate-500 dark:text-slate-400">
                            Status LOP
                        </label>

                        <select
                            name="status"
                            onchange="document.getElementById('filterForm').submit()"
                            class="w-full h-10 px-3
                                   rounded-lg
                                   bg-slate-50 dark:bg-slate-950
                                   border border-slate-200 dark:border-slate-700
                                   text-sm font-medium
                                   text-slate-800 dark:text-slate-200
                                   outline-none
                                   focus:ring-2
                                   focus:ring-blue-100 dark:focus:ring-blue-500/20
                                   focus:border-blue-500
                                   transition">

                            <option value="">
                                Semua Status
                            </option> 
                            @foreach($statusOptions as $statusCode => $statusLabel)

                                <option
                                    value="{{ $statusCode }}"
                                    @selected(request('status') === $statusCode)>
                                    {{ $statusLabel }}
                                </option>

                            @endforeach
                        </select>
                    </div>
                    {{-- RESET --}}
                    @if(
                        request('program') ||
                        request('branch') ||
                        request('region') ||
                        request('status')
                    )

                        <div>
                            <a
                                href="{{ route('dashboard') }}"
                                class="flex items-center justify-center
                                       h-10 px-4
                                       rounded-lg
                                       border border-dashed
                                       border-red-300 dark:border-red-500/40
                                       text-xs font-bold
                                       text-red-600 dark:text-red-400
                                       hover:bg-red-50 dark:hover:bg-red-500/10
                                       transition w-full">
                                Reset Filter
                            </a>
                        </div>

                    @endif

                </div>
            </form>
        </div>

        {{-- RINGKASAN & ALUR PROGRESS PT 3 (1 widget ringkas, klik angka = modal daftar LOP) --}}
        <div class="bg-white dark:bg-slate-900 rounded-[0.5rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Ringkasan &amp; Alur Progress PT 3</h2>
                    <p class="text-xs text-slate-500 mt-1">Klik angka untuk lihat daftar LOP-nya. Mengikuti filter yang aktif.</p>
                </div>

                <button type="button"
                        @click="show({type:'assignment', region:'', branch:'', metric:''})"
                        class="flex items-center gap-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg px-4 py-2.5 self-start sm:self-auto hover:border-blue-300 dark:hover:border-blue-700 transition text-left">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center shrink-0 text-blue-600 dark:text-blue-400">
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
                    <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-lg {{ $step['icon_bg'] }} {{ $step['icon_text'] }} flex items-center justify-center shrink-0">
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

                        <div class="mt-3 h-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 overflow-hidden">
                            <div class="h-full rounded-lg {{ $step['bar'] }}" style="width: {{ min($percent, 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- TABEL REKAP COLLAPSIBLE PER REGION --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-5 md:px-6 py-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-wider text-slate-800 dark:text-slate-100">
                            Rekap Assignment & Status Project PT 3
                        </h2>
                    </div>
                    <span class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 text-[10px] font-black uppercase tracking-wider">
                        PT 3
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[950px] text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/70 border-b border-slate-200 dark:border-slate-700">

                            <th class="px-6 py-3.5 text-left text-[10px] font-black uppercase tracking-wider
                                    text-slate-500 dark:text-slate-400">
                                Region / Branch
                            </th>

                            <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-wider
                                    text-slate-500 dark:text-slate-400">
                                Total LOP
                            </th>

                            <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-wider
                                    text-blue-600 dark:text-blue-400">
                                Assign
                            </th>

                            <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-wider
                                    text-rose-600 dark:text-rose-400">
                                Blm Assign
                            </th>

                            <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-wider
                                    text-amber-600 dark:text-amber-400">
                                In Review
                            </th>

                            <th class="px-3 py-3.5 text-center text-[10px] font-black uppercase tracking-wider
                                    text-emerald-600 dark:text-emerald-400">
                                Complete
                            </th>

                            <th class="px-6 py-3.5 text-right text-[10px] font-black uppercase tracking-wider
                                    text-slate-500 dark:text-slate-400">
                                Progress Rate
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">

                        @forelse($statsByRegion ?? [] as $i => $reg)

                            {{-- REGION --}}
                            <tr class="group cursor-pointer bg-white dark:bg-slate-900 hover:bg-blue-50/50 dark:hover:bg-blue-500/[0.06] transition-colors duration-200" onclick="toggleRegion('region-{{ $i }}', 'icon-{{ $i }}')">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400 group-hover:bg-blue-100 dark:group-hover:bg-blue-500/20 transition-colors">
                                            <svg id="icon-{{ $i }}" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200" >
                                                <path d="m9 18 6-6-6-6"/>
                                            </svg>
                                        </div> 
                                        <div>
                                            <p class="font-black text-sm text-slate-800 dark:text-slate-100">
                                                {{ $reg['region'] }}
                                            </p>
                                        </div> 
                                    </div>
                                </td>

                                <td class="px-3 py-4 text-center">
                                    <button type="button" class="min-w-[44px] px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition" @click.stop="show({ type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'total' })" >
                                        {{ $reg['total'] }}
                                    </button>
                                </td>

                                <td class="px-3 py-4 text-center">
                                    <button type="button" class="min-w-[44px] px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 font-black hover:bg-blue-100 dark:hover:bg-blue-500/20 transition" @click.stop="show({ type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'assigned' })" >
                                        {{ $reg['assigned'] }}
                                    </button>
                                </td>

                                <td class="px-3 py-4 text-center">
                                    <button type="button" class="min-w-[44px] px-3 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400
                                            font-black hover:bg-rose-100 dark:hover:bg-rose-500/20 transition" @click.stop="show({ type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'unassigned' })" >
                                        {{ $reg['total'] - $reg['assigned'] }}
                                    </button>
                                </td>

                                <td class="px-3 py-4 text-center">
                                    <button
                                        type="button" class="min-w-[44px] px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400
                                            font-black hover:bg-amber-100 dark:hover:bg-amber-500/20 transition" @click.stop="show({ type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'waiting' })" >
                                        {{ $reg['waiting'] }}
                                    </button>
                                </td>

                                <td class="px-3 py-4 text-center">
                                    <button
                                        type="button" class="min-w-[44px] px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-black hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition" @click.stop="show({ type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'completed' })" >
                                        {{ $reg['completed'] }}
                                    </button>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">

                                        <div class="w-24 lg:w-28 h-2 rounded-lg
                                                    bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                            <div
                                                class="h-full rounded-lg bg-emerald-500 transition-all duration-500"
                                                style="width: {{ min($reg['percent'], 100) }}%">
                                            </div>
                                        </div>

                                        <span class="min-w-[42px] text-right font-black
                                                    text-slate-800 dark:text-slate-200">
                                            {{ $reg['percent'] }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            {{-- BRANCH --}}
                            @foreach($reg['branches'] as $br)
                                <tr
                                    class="hidden region-{{ $i }}
                                        bg-slate-50/70 dark:bg-slate-950/40
                                        hover:bg-slate-100 dark:hover:bg-slate-800/70
                                        transition-colors">
                                    <td class="px-6 py-3 pl-[4.25rem]">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-lg bg-slate-300 dark:bg-slate-600"></span>

                                            <span class="font-bold text-slate-600 dark:text-slate-300">
                                                {{ $br['name'] }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <button
                                            type="button"
                                            class="font-black text-slate-600 dark:text-slate-300 hover:underline"
                                            @click.stop="show({
                                                type:'assignment',
                                                region:'{{ $reg['region'] }}',
                                                branch:'{{ $br['name'] }}',
                                                metric:'total'
                                            })">
                                            {{ $br['total'] }}
                                        </button>
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <button
                                            type="button"
                                            class="font-black text-blue-600 dark:text-blue-400 hover:underline"
                                            @click.stop="show({
                                                type:'assignment',
                                                region:'{{ $reg['region'] }}',
                                                branch:'{{ $br['name'] }}',
                                                metric:'assigned'
                                            })" >
                                            {{ $br['assigned'] }}
                                        </button>
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <button
                                            type="button"
                                            class="font-black text-rose-600 dark:text-rose-400 hover:underline"
                                            @click.stop="show({
                                                type:'assignment',
                                                region:'{{ $reg['region'] }}',
                                                branch:'{{ $br['name'] }}',
                                                metric:'unassigned'
                                            })" >
                                            {{ $br['total'] - $br['assigned'] }}
                                        </button>
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <button
                                            type="button"
                                            class="font-black text-amber-600 dark:text-amber-400 hover:underline"
                                            @click.stop="show({
                                                type:'assignment',
                                                region:'{{ $reg['region'] }}',
                                                branch:'{{ $br['name'] }}',
                                                metric:'waiting'
                                            })" >
                                            {{ $br['waiting'] }}
                                        </button>
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <button
                                            type="button"
                                            class="font-black text-emerald-600 dark:text-emerald-400 hover:underline"
                                            @click.stop="show({
                                                type:'assignment',
                                                region:'{{ $reg['region'] }}',
                                                branch:'{{ $br['name'] }}',
                                                metric:'completed'
                                            })" >
                                            {{ $br['completed'] }}
                                        </button>
                                    </td>

                                    <td class="px-6 py-3 text-right">
                                        <span class="inline-flex min-w-[48px] justify-center px-2.5 py-1 rounded-lg
                                                    bg-slate-100 dark:bg-slate-800
                                                    text-slate-600 dark:text-slate-300
                                                    font-black">
                                            {{ $br['percent'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @endforeach

                        @empty
                            <tr>
                                <td colspan="7"
                                    class="px-6 py-12 text-center text-slate-400 dark:text-slate-500 font-medium">
                                    Tidak ada data statistik tersedia berdasarkan filter.
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>

        {{-- MATRIX PROJECT REGULAR --}}
        <div class="mt-5 bg-white dark:bg-slate-900 rounded-lg
                    border border-slate-200 dark:border-slate-800
                    overflow-hidden shadow-sm">

            <div class="px-5 md:px-6 py-5 border-b border-slate-200 dark:border-slate-800
                        bg-slate-50/70 dark:bg-slate-900">

                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-sm font-black uppercase tracking-wider
                                text-slate-800 dark:text-slate-100">
                            Matriks Progress Project PT 3
                        </h2>

                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Program PT 3: OSP, OLO, HEM, NODE B, EKSBIS.
                        </p>
                    </div>

                    <span class="hidden sm:inline-flex px-3 py-1.5 rounded-lg
                                bg-blue-50 dark:bg-blue-500/10
                                text-blue-700 dark:text-blue-400
                                text-[10px] font-black uppercase">
                        PT 3
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto pb-3">
                <table class="w-full min-w-max text-xs border-collapse">

                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/70">

                            <th
                                rowspan="2"
                                class="px-6 py-4 text-left
                                    border-r border-slate-200 dark:border-slate-700
                                    align-middle whitespace-nowrap
                                    sticky left-0 z-30
                                    bg-slate-50 dark:bg-slate-800
                                    text-[10px] font-black uppercase tracking-wider
                                    text-slate-500 dark:text-slate-400"
                            >
                                Region / Branch
                            </th>

                            @foreach($regularPrograms as $prog)
                                <th
                                    colspan="4"
                                    class="px-4 py-3 text-center
                                        border-r border-b border-slate-200 dark:border-slate-700
                                        whitespace-nowrap
                                        text-[10px] font-black uppercase tracking-wider
                                        text-slate-700 dark:text-slate-200" >
                                    {{ strtoupper(trim($prog)) === 'EKSBIS' ? 'Eksbis' : $prog }}
                                </th>
                            @endforeach
                        </tr>

                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            @foreach($regularPrograms as $prog)

                                <th class="px-3 py-3 text-center whitespace-nowrap
                                        bg-blue-50/70 dark:bg-blue-500/10
                                        text-blue-700 dark:text-blue-400
                                        font-black text-[10px] uppercase">
                                    Prepare
                                </th>

                                <th class="px-3 py-3 text-center whitespace-nowrap
                                        bg-amber-50/70 dark:bg-amber-500/10
                                        text-amber-700 dark:text-amber-400
                                        font-black text-[10px] uppercase">
                                    Progress
                                </th>

                                <th class="px-3 py-3 text-center whitespace-nowrap
                                        bg-emerald-50/70 dark:bg-emerald-500/10
                                        text-emerald-700 dark:text-emerald-400
                                        font-black text-[10px] uppercase">
                                    Finish
                                </th>

                                <th class="px-3 py-3 text-center whitespace-nowrap
                                        border-r border-slate-200 dark:border-slate-700
                                        bg-indigo-50/70 dark:bg-indigo-500/10
                                        text-indigo-700 dark:text-indigo-400
                                        font-black text-[10px] uppercase">
                                    % Done
                                </th>

                            @endforeach
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">

                        @forelse($matrixData ?? [] as $i => $reg)

                            <tr
                                class="cursor-pointer group
                                    bg-white dark:bg-slate-900
                                    hover:bg-blue-50/40 dark:hover:bg-blue-500/[0.06]
                                    transition-colors"
                                onclick="toggleRegion('matrix-reg-{{ $i }}', 'icon-matrix-{{ $i }}')" >

                                <td class="px-6 py-4
                                        border-r border-slate-200 dark:border-slate-700
                                        sticky left-0 z-20
                                        bg-white dark:bg-slate-900
                                        group-hover:bg-blue-50 dark:group-hover:bg-slate-800">

                                    <div class="flex items-center gap-3">

                                        <div class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg
                                                    bg-indigo-50 dark:bg-indigo-500/10
                                                    text-indigo-600 dark:text-indigo-400">

                                            <svg
                                                id="icon-matrix-{{ $i }}"
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="15"
                                                height="15"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="transition-transform duration-200" >
                                                <path d="m9 18 6-6-6-6"/>
                                            </svg>
                                        </div>

                                        <span class="font-black text-sm whitespace-nowrap
                                                    text-slate-800 dark:text-slate-100">
                                            {{ $reg['region'] }}
                                        </span>
                                    </div>
                                </td>

                                @foreach($regularPrograms as $prog)

                                    @php
                                        $stats = $reg['programs'][$prog] ?? [
                                            'preparation' => 0,
                                            'instalasi' => 0,
                                            'finishing' => 0,
                                        ];

                                        $totalProyek =
                                            $stats['preparation'] +
                                            $stats['instalasi'] +
                                            $stats['finishing'];

                                        $persentase = $totalProyek > 0
                                            ? round(($stats['finishing'] / $totalProyek) * 100)
                                            : 0;
                                    @endphp

                                    <td class="px-3 py-4 text-center
                                            bg-blue-50/20 dark:bg-blue-500/[0.03]">

                                        <button
                                            type="button"
                                            class="min-w-[40px] px-2.5 py-1.5 rounded-lg
                                                font-black text-blue-700 dark:text-blue-400
                                                hover:bg-blue-100 dark:hover:bg-blue-500/20 transition"
                                            @click.stop="show({
                                                type:'regular',
                                                region:'{{ $reg['region'] }}',
                                                branch:'',
                                                program:'{{ $prog }}',
                                                metric:'preparation'
                                            })" >
                                            {{ $stats['preparation'] ?: '-' }}
                                        </button>
                                    </td>

                                    <td class="px-3 py-4 text-center
                                            bg-amber-50/20 dark:bg-amber-500/[0.03]">

                                        <button
                                            type="button"
                                            class="min-w-[40px] px-2.5 py-1.5 rounded-lg
                                                font-black text-amber-700 dark:text-amber-400
                                                hover:bg-amber-100 dark:hover:bg-amber-500/20 transition"
                                            @click.stop="show({
                                                type:'regular',
                                                region:'{{ $reg['region'] }}',
                                                branch:'',
                                                program:'{{ $prog }}',
                                                metric:'instalasi'
                                            })" >
                                            {{ $stats['instalasi'] ?: '-' }}
                                        </button>
                                    </td>

                                    <td class="px-3 py-4 text-center
                                            bg-emerald-50/20 dark:bg-emerald-500/[0.03]">

                                        <button
                                            type="button"
                                            class="min-w-[40px] px-2.5 py-1.5 rounded-lg
                                                font-black text-emerald-700 dark:text-emerald-400
                                                hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition"
                                            @click.stop="show({
                                                type:'regular',
                                                region:'{{ $reg['region'] }}',
                                                branch:'',
                                                program:'{{ $prog }}',
                                                metric:'finishing'
                                            })" >
                                            {{ $stats['finishing'] ?: '-' }}
                                        </button>
                                    </td>

                                    <td class="px-3 py-4 text-center
                                            bg-indigo-50/20 dark:bg-indigo-500/[0.03]
                                            border-r border-slate-200 dark:border-slate-700">

                                        <span class="inline-flex justify-center min-w-[50px]
                                                    px-2.5 py-1.5 rounded-lg
                                                    bg-indigo-50 dark:bg-indigo-500/10
                                                    text-indigo-700 dark:text-indigo-400
                                                    font-black">
                                            {{ $persentase }}%
                                        </span>
                                    </td>

                                @endforeach
                            </tr>

                            @foreach($reg['branches'] as $br)

                                <tr
                                    class="hidden matrix-reg-{{ $i }}
                                        bg-slate-50/70 dark:bg-slate-950/40
                                        hover:bg-slate-100 dark:hover:bg-slate-800/70
                                        transition-colors" >

                                    <td class="px-6 py-3 pl-[4.25rem]
                                            border-r border-slate-200 dark:border-slate-700
                                            sticky left-0 z-20
                                            bg-slate-50 dark:bg-slate-950">

                                        <div class="flex items-center gap-2">

                                            <span class="w-1.5 h-1.5 rounded-lg
                                                        bg-slate-300 dark:bg-slate-600">
                                            </span>

                                            <span class="font-bold whitespace-nowrap
                                                        text-slate-600 dark:text-slate-300">
                                                {{ $br['name'] }}
                                            </span>
                                        </div>
                                    </td>

                                    @foreach($regularPrograms as $prog)

                                        @php
                                            $stats = $br['programs'][$prog] ?? [
                                                'preparation' => 0,
                                                'instalasi' => 0,
                                                'finishing' => 0,
                                            ];

                                            $totalProyek =
                                                $stats['preparation'] +
                                                $stats['instalasi'] +
                                                $stats['finishing'];

                                            $persentase = $totalProyek > 0
                                                ? round(($stats['finishing'] / $totalProyek) * 100)
                                                : 0;
                                        @endphp

                                        <td class="px-3 py-3 text-center">
                                            <button
                                                type="button"
                                                class="text-blue-600 dark:text-blue-400
                                                    font-black hover:underline"
                                                @click.stop="show({
                                                    type:'regular',
                                                    region:'{{ $reg['region'] }}',
                                                    branch:'{{ $br['name'] }}',
                                                    program:'{{ $prog }}',
                                                    metric:'preparation'
                                                })" >
                                                {{ $stats['preparation'] ?: '-' }}
                                            </button>
                                        </td>

                                        <td class="px-3 py-3 text-center">
                                            <button
                                                type="button"
                                                class="text-amber-600 dark:text-amber-400
                                                    font-black hover:underline"
                                                @click.stop="show({
                                                    type:'regular',
                                                    region:'{{ $reg['region'] }}',
                                                    branch:'{{ $br['name'] }}',
                                                    program:'{{ $prog }}',
                                                    metric:'instalasi'
                                                })" >
                                                {{ $stats['instalasi'] ?: '-' }}
                                            </button>
                                        </td>

                                        <td class="px-3 py-3 text-center">
                                            <button
                                                type="button"
                                                class="text-emerald-600 dark:text-emerald-400
                                                    font-black hover:underline"
                                                @click.stop="show({
                                                    type:'regular',
                                                    region:'{{ $reg['region'] }}',
                                                    branch:'{{ $br['name'] }}',
                                                    program:'{{ $prog }}',
                                                    metric:'finishing'
                                                })" >
                                                {{ $stats['finishing'] ?: '-' }}
                                            </button>
                                        </td>

                                        <td class="px-3 py-3 text-center
                                                border-r border-slate-200 dark:border-slate-700">

                                            <span class="inline-flex min-w-[48px] justify-center
                                                        px-2 py-1 rounded-lg
                                                        bg-indigo-50 dark:bg-indigo-500/10
                                                        text-indigo-600 dark:text-indigo-400
                                                        font-black">
                                                {{ $persentase }}%
                                            </span>
                                        </td>

                                    @endforeach

                                </tr>

                            @endforeach

                        @empty

                            <tr>
                                <td
                                    colspan="{{ 1 + ($regularPrograms->count() * 4) }}"
                                    class="px-6 py-12 text-center
                                        text-slate-400 dark:text-slate-500 font-medium">
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

        <div class="mt-5 bg-white dark:bg-slate-900 rounded-lg
                    border border-slate-200 dark:border-slate-800
                    overflow-hidden shadow-sm">

            <div class="px-5 md:px-6 py-5
                        border-b border-slate-200 dark:border-slate-800
                        bg-slate-50/70 dark:bg-slate-900">

                <div class="flex items-center justify-between gap-4">

                    <div>
                        <h2 class="text-sm font-black uppercase tracking-wider
                                text-slate-800 dark:text-slate-100">
                            Matriks Progress Project PT 2
                        </h2>

                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Sumber khusus PT 2. Tidak terfilter Program PT 3.
                        </p>
                    </div>

                    <span class="px-3 py-1.5 rounded-lg
                                bg-indigo-50 dark:bg-indigo-500/10
                                text-indigo-700 dark:text-indigo-400
                                text-[10px] font-black uppercase tracking-wider shrink-0">
                        PT 2
                    </span>

                </div>
            </div>

            <div class="overflow-x-auto pb-3">
                <table class="w-full min-w-[850px] text-xs border-collapse">

                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800/70
                                border-b border-slate-200 dark:border-slate-700">

                            <th class="px-6 py-4 text-left
                                    border-r border-slate-200 dark:border-slate-700
                                    text-[10px] font-black uppercase tracking-wider
                                    text-slate-500 dark:text-slate-400">
                                Region / Branch
                            </th>

                            <th class="px-4 py-4 text-center
                                    text-[10px] font-black uppercase tracking-wider
                                    text-blue-600 dark:text-blue-400">
                                Preparation
                            </th>

                            <th class="px-4 py-4 text-center
                                    text-[10px] font-black uppercase tracking-wider
                                    text-amber-600 dark:text-amber-400">
                                Instalasi
                            </th>

                            <th class="px-4 py-4 text-center
                                    text-[10px] font-black uppercase tracking-wider
                                    text-emerald-600 dark:text-emerald-400">
                                Finishing / Go-Live
                            </th>

                            <th class="px-4 py-4 text-center
                                    text-[10px] font-black uppercase tracking-wider
                                    text-indigo-600 dark:text-indigo-400">
                                Total
                            </th>

                            <th class="px-6 py-4 text-right
                                    text-[10px] font-black uppercase tracking-wider
                                    text-slate-500 dark:text-slate-400">
                                % Finish
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">

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

                            <tr
                                class="group cursor-pointer
                                    bg-white dark:bg-slate-900
                                    hover:bg-indigo-50/40 dark:hover:bg-indigo-500/[0.06]
                                    transition-colors"
                                onclick="toggleRegion(
                                    'matrix-pt2-{{ $regionSlug }}',
                                    'icon-pt2-{{ $regionSlug }}'
                                )"
                            >

                                <td class="px-6 py-4
                                        border-r border-slate-200 dark:border-slate-700">

                                    <div class="flex items-center gap-3">

                                        <div class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg
                                                    bg-indigo-50 dark:bg-indigo-500/10
                                                    text-indigo-600 dark:text-indigo-400">

                                            <svg
                                                id="icon-pt2-{{ $regionSlug }}"
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="15"
                                                height="15"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="3"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="transition-transform duration-200"
                                            >
                                                <path d="m9 18 6-6-6-6"/>
                                            </svg>

                                        </div>

                                        <div>
                                            <p class="font-black text-sm
                                                    text-slate-800 dark:text-slate-100">
                                                {{ $reg['region'] }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <button
                                        type="button"
                                        class="min-w-[46px] px-3 py-1.5 rounded-lg
                                            bg-blue-50 dark:bg-blue-500/10
                                            text-blue-700 dark:text-blue-400
                                            font-black
                                            hover:bg-blue-100 dark:hover:bg-blue-500/20 transition"
                                        @click.stop="show({
                                            type:'pt2',
                                            region:'{{ $reg['region'] }}',
                                            branch:'',
                                            metric:'preparation'
                                        })"
                                    >
                                        {{ $regionStats['preparation'] ?? 0 }}
                                    </button>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <button
                                        type="button"
                                        class="min-w-[46px] px-3 py-1.5 rounded-lg
                                            bg-amber-50 dark:bg-amber-500/10
                                            text-amber-700 dark:text-amber-400
                                            font-black
                                            hover:bg-amber-100 dark:hover:bg-amber-500/20 transition"
                                        @click.stop="show({
                                            type:'pt2',
                                            region:'{{ $reg['region'] }}',
                                            branch:'',
                                            metric:'instalasi'
                                        })"
                                    >
                                        {{ $regionStats['instalasi'] ?? 0 }}
                                    </button>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <button
                                        type="button"
                                        class="min-w-[46px] px-3 py-1.5 rounded-lg
                                            bg-emerald-50 dark:bg-emerald-500/10
                                            text-emerald-700 dark:text-emerald-400
                                            font-black
                                            hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition"
                                        @click.stop="show({
                                            type:'pt2',
                                            region:'{{ $reg['region'] }}',
                                            branch:'',
                                            metric:'finishing'
                                        })"
                                    >
                                        {{ $regionStats['finishing'] ?? 0 }}
                                    </button>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <button
                                        type="button"
                                        class="min-w-[46px] px-3 py-1.5 rounded-lg
                                            bg-indigo-50 dark:bg-indigo-500/10
                                            text-indigo-700 dark:text-indigo-400
                                            font-black
                                            hover:bg-indigo-100 dark:hover:bg-indigo-500/20 transition"
                                        @click.stop="show({
                                            type:'pt2',
                                            region:'{{ $reg['region'] }}',
                                            branch:'',
                                            metric:'total'
                                        })"
                                    >
                                        {{ $regionStats['total'] ?? 0 }}
                                    </button>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-3">

                                        <div class="w-24 lg:w-28 h-2 rounded-lg
                                                    bg-slate-100 dark:bg-slate-800 overflow-hidden">

                                            <div
                                                class="h-full rounded-lg bg-emerald-500 transition-all duration-500"
                                                style="width: {{ min($regionStats['percent'] ?? 0, 100) }}%">
                                            </div>
                                        </div>

                                        <span class="min-w-[42px] text-right
                                                    font-black text-indigo-700 dark:text-indigo-400">
                                            {{ $regionStats['percent'] ?? 0 }}%
                                        </span>

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

                                <tr
                                    class="hidden matrix-pt2-{{ $regionSlug }}
                                        bg-slate-50/70 dark:bg-slate-950/40
                                        hover:bg-slate-100 dark:hover:bg-slate-800/70
                                        transition-colors"
                                >

                                    <td class="px-6 py-3 pl-[4.25rem]
                                            border-r border-slate-200 dark:border-slate-700">

                                        <div class="flex items-center gap-2">

                                            <span class="w-1.5 h-1.5 rounded-lg
                                                        bg-slate-300 dark:bg-slate-600">
                                            </span>

                                            <span class="font-bold
                                                        text-slate-600 dark:text-slate-300">
                                                {{ $branch['name'] }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            class="font-black text-blue-600 dark:text-blue-400 hover:underline"
                                            @click.stop="show({
                                                type:'pt2',
                                                region:'{{ $reg['region'] }}',
                                                branch:'{{ $branch['name'] }}',
                                                metric:'preparation'
                                            })"
                                        >
                                            {{ $branchStats['preparation'] ?? 0 }}
                                        </button>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            class="font-black text-amber-600 dark:text-amber-400 hover:underline"
                                            @click.stop="show({
                                                type:'pt2',
                                                region:'{{ $reg['region'] }}',
                                                branch:'{{ $branch['name'] }}',
                                                metric:'instalasi'
                                            })"
                                        >
                                            {{ $branchStats['instalasi'] ?? 0 }}
                                        </button>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            class="font-black text-emerald-600 dark:text-emerald-400 hover:underline"
                                            @click.stop="show({
                                                type:'pt2',
                                                region:'{{ $reg['region'] }}',
                                                branch:'{{ $branch['name'] }}',
                                                metric:'finishing'
                                            })"
                                        >
                                            {{ $branchStats['finishing'] ?? 0 }}
                                        </button>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            class="font-black text-indigo-600 dark:text-indigo-400 hover:underline"
                                            @click.stop="show({
                                                type:'pt2',
                                                region:'{{ $reg['region'] }}',
                                                branch:'{{ $branch['name'] }}',
                                                metric:'total'
                                            })"
                                        >
                                            {{ $branchStats['total'] ?? 0 }}
                                        </button>
                                    </td>

                                    <td class="px-6 py-3 text-right">
                                        <span class="inline-flex justify-center min-w-[50px]
                                                    px-2.5 py-1 rounded-lg
                                                    bg-indigo-50 dark:bg-indigo-500/10
                                                    text-indigo-600 dark:text-indigo-400
                                                    font-black">
                                            {{ $branchStats['percent'] ?? 0 }}%
                                        </span>
                                    </td>

                                </tr>

                            @endforeach

                        @empty

                            <tr>
                                <td colspan="6"
                                    class="px-6 py-12 text-center
                                        text-slate-400 dark:text-slate-500 font-medium">
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

        <div class="relative bg-white dark:bg-slate-900 w-full max-w-4xl max-h-[85vh] rounded-[0.5rem] shadow-2xl flex flex-col overflow-hidden"
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
                        class="w-9 h-9 shrink-0 rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-700 flex items-center justify-center transition">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="overflow-y-auto flex-1">
                <template x-if="loading">
                    <div class="flex items-center justify-center py-16">
                        <div class="w-8 h-8 border-4 border-blue-200 border-t-blue-600 rounded-lg animate-spin"></div>
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
