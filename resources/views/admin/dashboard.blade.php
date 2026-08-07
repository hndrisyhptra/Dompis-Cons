@extends('layouts.admin')

@section('content')

@php
    $completionRate = $completionRate ?? 0;

    // Cek apakah user sedang memfilter program PT2 (bisa mencakup 'PT 2', 'PT2', dll)
    $selectedProgram = strtoupper(request('program', ''));
    $isPt2Selected = str_contains($selectedProgram, 'PT2') || str_contains($selectedProgram, 'PT 2') || str_contains($selectedProgram, 'PT-2');

    // Hitung total PT2 Go-Live
    $totalGolivePt2 = \App\Models\Project::where('is_golive', 1)->count();

    $mainCards = [
        [
            'label' => 'Total LOP',
            'value' => $totalLop ?? 0,
            'desc' => 'Seluruh LOP terdaftar',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-slate-500 lucide lucide-file-spreadsheet"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/></svg>',
            'border' => 'border-blue-200',
            'text' => 'text-blue-900',
            'bg' => 'bg-blue-50',
        ],
        [
            'label' => 'BOQ Ready',
            'value' => $boqReady ?? 0,
            'desc' => 'LOP sudah memiliki BOQ',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package-open-icon lucide-package-open"><path d="M12 22v-9"/><path d="M15.17 2.21a1.67 1.67 0 0 1 1.63 0L21 4.57a1.93 1.93 0 0 1 0 3.36L8.82 14.79a1.655 1.655 0 0 1-1.64 0L3 12.43a1.93 1.93 0 0 1 0-3.36z"/><path d="M20 13v3.87a2.06 2.06 0 0 1-1.11 1.83l-6 3.08a1.93 1.93 0 0 1-1.78 0l-6-3.08A2.06 2.06 0 0 1 4 16.87V13"/><path d="M21 12.43a1.93 1.93 0 0 0 0-3.36L8.83 2.2a1.64 1.64 0 0 0-1.63 0L3 4.57a1.93 1.93 0 0 0 0 3.36l12.18 6.86a1.636 1.636 0 0 0 1.63 0z"/></svg>',
            'border' => 'border-blue-200',
            'text' => 'text-blue-700',
            'bg' => 'bg-blue-50',
        ],
        [
            'label' => 'Sudah Assign',
            'value' => $assignedLop ?? 0,
            'desc' => 'Sudah dibagikan ke Waspang',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-check-icon lucide-user-check"><path d="m16 11 2 2 4-4"/><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
            'border' => 'border-indigo-200',
            'text' => 'text-indigo-700',
            'bg' => 'bg-indigo-50',
        ],
        [
            'label' => 'Completed',
            'value' => $completedApproval ?? 0,
            'desc' => 'Progress selesai 100%',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big-icon lucide-circle-check-big"><path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/></svg>',
            'border' => 'border-emerald-200',
            'text' => 'text-emerald-700',
            'bg' => 'bg-emerald-50',
        ],
    ];

    // Jika filter program PT2 dipilih, masukkan kartu Go-Live ke dalam array kartu utama
    if ($isPt2Selected) {
        $mainCards[] = [
            'label' => 'PT2 Go-Live',
            'value' => $totalGolivePt2,
            'desc' => 'Approval Golive by SDI',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check-icon lucide-shield-check"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>',
            'border' => 'border-indigo-300',
            'text' => 'text-indigo-800',
            'bg' => 'bg-indigo-100/60',
        ];
    }
@endphp

<div class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6">

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
                        <select name="region" id="regionSelect" onchange="updateBranchDropdown(); document.getElementById('filterForm').submit();"
                                class="w-full h-10 px-3 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-800 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition">
                            <option value="">Semua Region</option>
                            <option value="JATIM" {{ request('region') == 'JATIM' ? 'selected' : '' }}>JATIM</option>
                            <option value="JATENG DIY" {{ request('region') == 'JATENG DIY' ? 'selected' : '' }}>JATENG DIY</option>
                            <option value="BALNUS" {{ request('region') == 'BALNUS' ? 'selected' : '' }}>BALNUS</option>
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

                    {{-- Program Filter --}}
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-bold uppercase text-slate-500">Program</label>
                        <select name="program" onchange="document.getElementById('filterForm').submit()"
                                class="w-full h-10 px-3 rounded-xl bg-slate-50 border border-slate-200 text-sm font-medium text-slate-800 outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500 transition">
                            <option value="">Semua Program</option>
                            @foreach($programs ?? [] as $program)
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

        {{-- MAIN KPI (DISESUAIKAN MENJADI 5 GRID) --}}
       <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-{{ count($mainCards) }} gap-4">
            @foreach($mainCards as $card)
                <div class="rounded-3xl bg-white dark:bg-slate-900 border {{ $card['border'] }} dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">{{ $card['label'] }}</p>
                            <p class="text-3xl font-black {{ $card['text'] }} dark:text-white mt-2">{{ number_format($card['value']) }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $card['desc'] }}</p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl {{ $card['bg'] }} flex items-center justify-center text-2xl shrink-0">
                            {!! $card['icon'] !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- PIPELINE & EVIDENCE --}}
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
            {{-- PIPELINE --}}
            <div class="xl:col-span-12 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">
                <h2 class="text-lg font-black text-slate-900 dark:text-white mb-5">Alur Progress</h2>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    @foreach($stageSummary ?? [] as $stage)
                        @php
                            $classes = [
                                'amber' => 'bg-amber-50 border-amber-200 text-amber-700',
                                'red' => 'bg-red-50 border-red-200 text-red-700',
                                'blue' => 'bg-blue-50 border-blue-200 text-blue-700',
                                'orange' => 'bg-orange-50 border-orange-200 text-orange-700',
                                'emerald' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
                            ];
                            $class = $classes[$stage['color']] ?? 'bg-slate-50 border-slate-200 text-slate-700';
                        @endphp
                        <div class="rounded-3xl border p-5 {{ $class }}">
                            <p class="text-xs font-black uppercase">{{ $stage['label'] }}</p>
                            <p class="text-3xl font-black mt-2">{{ number_format($stage['value']) }}</p>
                            <p class="text-[10px] mt-1 opacity-80 leading-tight">{{ $stage['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- TABEL REKAP COLLAPSIBLE PER REGION --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-slate-800">Rekap Assignment & Status Project</h2>
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
                                <td class="px-3 py-4 text-center font-black text-slate-700 text-sm">{{ $reg['total'] }}</td>
                                <td class="px-3 py-4 text-center"><span class="px-3 py-1 rounded-lg bg-blue-50 text-blue-700 font-black">{{ $reg['assigned'] }}</span></td>
                                <td class="px-3 py-4 text-center"><span class="px-3 py-1 rounded-lg bg-amber-50 text-amber-700 font-black">{{ $reg['waiting'] }}</span></td>
                                <td class="px-3 py-4 text-center"><span class="px-3 py-1 rounded-lg bg-emerald-50 text-emerald-700 font-black">{{ $reg['completed'] }}</span></td>
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
                                    <td class="px-3 py-3 text-center font-bold text-slate-600">{{ $br['total'] }}</td>
                                    <td class="px-3 py-3 text-center"><span class="text-blue-600 font-bold">{{ $br['assigned'] }}</span></td>
                                    <td class="px-3 py-3 text-center"><span class="text-amber-600 font-bold">{{ $br['waiting'] }}</span></td>
                                    <td class="px-3 py-3 text-center"><span class="text-emerald-600 font-bold">{{ $br['completed'] }}</span></td>
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

    </div>
</div>

{{-- TABEL MATRIX STATUS PROGRESS PER PROGRAM (UNFILTERED) --}}
        <div class="mt-5 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-slate-800">Matriks Progress per Program</h2>
                    <p class="text-xs text-slate-500 mt-1">Data global (unfiltered). Tidak akan berubah saat Anda memilih filter di atas.</p>
                </div>
            </div>

            <div class="overflow-x-auto pb-4">
                <table class="w-full text-xs border-collapse">
                    <thead class="bg-slate-100/60 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th rowspan="2" class="px-6 py-3 text-left border-r border-slate-200/60 align-middle whitespace-nowrap sticky left-0 bg-slate-100/90 backdrop-blur-sm z-10">
                                Wilayah (Region / Branch)
                            </th>
                            @foreach($programs ?? [] as $prog)
                                <th colspan="4" class="px-3 py-2 text-center border-b border-r border-slate-200/60 whitespace-nowrap">{{ $prog }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($programs ?? [] as $prog)
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
                                
                                @foreach($programs as $prog)
                                    @php 
                                        $stats = $reg['programs'][$prog]; 
                                        $totalProyek = $stats['preparation'] + $stats['instalasi'] + $stats['finishing'];
                                        $persentase = $totalProyek > 0 ? round(($stats['finishing'] / $totalProyek) * 100) : 0;
                                    @endphp
                                    <td class="px-3 py-4 text-center font-bold text-slate-700 bg-blue-50/20">{{ $stats['preparation'] ?: '-' }}</td>
                                    <td class="px-3 py-4 text-center font-bold text-slate-700 bg-amber-50/20">{{ $stats['instalasi'] ?: '-' }}</td>
                                    <td class="px-3 py-4 text-center font-bold text-slate-700 bg-emerald-50/20">{{ $stats['finishing'] ?: '-' }}</td>
                                    <td class="px-3 py-4 text-center font-black text-indigo-700 bg-indigo-50/20 border-r border-slate-200/60">{{ $persentase }}%</td>
                                @endforeach
                            </tr>
                            
                            @foreach($reg['branches'] as $br)
                                <tr class="hidden bg-slate-50/50 hover:bg-slate-100/50 transition matrix-reg-{{ $i }} group-branch">
                                    <td class="px-6 py-3 pl-[3.25rem] border-r border-slate-200/60 sticky left-0 bg-slate-50/90 z-10">
                                        <span class="font-bold text-slate-600 whitespace-nowrap">• {{ $br['name'] }}</span>
                                    </td>
                                    
                                    @foreach($programs as $prog)
                                        @php 
                                            $stats = $br['programs'][$prog]; 
                                            $totalProyek = $stats['preparation'] + $stats['instalasi'] + $stats['finishing'];
                                            $persentase = $totalProyek > 0 ? round(($stats['finishing'] / $totalProyek) * 100) : 0;
                                        @endphp
                                        <td class="px-3 py-3 text-center text-blue-600 font-semibold">{{ $stats['preparation'] ?: '-' }}</td>
                                        <td class="px-3 py-3 text-center text-amber-600 font-semibold">{{ $stats['instalasi'] ?: '-' }}</td>
                                        <td class="px-3 py-3 text-center text-emerald-600 font-semibold">{{ $stats['finishing'] ?: '-' }}</td>
                                        <td class="px-3 py-3 text-center text-indigo-600 font-black border-r border-slate-200/60">{{ $persentase }}%</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="{{ 1 + (count($programs ?? []) * 4) }}" class="px-6 py-10 text-center text-slate-400 font-medium">
                                    Tidak ada data project terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

{{-- SCRIPT FILTER & COLLAPSIBLE --}}
<script>
    function toggleRegion(regionClass, iconId) {
        const rows = document.querySelectorAll('.' + regionClass);
        let isHidden = false;
        rows.forEach(row => {
            if (row.classList.contains('hidden')) {
                row.classList.remove('hidden');
                isHidden = true;
            } else {
                row.classList.add('hidden');
            }
        });

        const icon = document.getElementById(iconId);
        if (icon) {
            if (isHidden) {
                icon.style.transform = 'rotate(90deg)';
                icon.parentElement.classList.replace('bg-blue-100', 'bg-blue-600');
                icon.parentElement.classList.replace('text-blue-600', 'text-white');
            } else {
                icon.style.transform = 'rotate(0deg)';
                icon.parentElement.classList.replace('bg-blue-600', 'bg-blue-100');
                icon.parentElement.classList.replace('text-white', 'text-blue-600');
            }
        }
    }

    const regionMapping = {
        'JATIM': ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
        'JATENG DIY': ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
        'BALNUS': ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES']
    };

    function updateBranchDropdown() {
        const regionSelect = document.getElementById('regionSelect');
        const branchSelect = document.getElementById('branchSelect');
        const selectedRegion = regionSelect.value.toUpperCase(); 
        const currentBranch = "{{ request('branch') }}".toUpperCase(); 
        
        branchSelect.innerHTML = '<option value="">Semua Branch</option>';
        
        if (selectedRegion && regionMapping[selectedRegion]) {
            regionMapping[selectedRegion].forEach(function(branch) {
                let option = document.createElement('option');
                option.value = branch; 
                option.text = branch;
                if(branch.toUpperCase() === currentBranch) {
                    option.selected = true;
                }
                branchSelect.appendChild(option);
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        updateBranchDropdown();
    });
</script>

@endsection