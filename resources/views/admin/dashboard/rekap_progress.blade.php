@extends('layouts.admin')

@section('content')

{{-- Header Section --}}
<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
    <div>
        <div class="flex items-center gap-2.5 mb-1">
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                Rekap Progress
            </h1>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Monitoring progres Penarikan Kabel dan Tanam Tiang secara real-time.
        </p>
    </div>
    
    <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-900/50 px-4 py-2.5 rounded-2xl border border-gray-200/60 dark:border-gray-800/60 self-start md:self-auto shadow-xs">
        <i class="fa-regular fa-calendar-check text-blue-600 dark:text-blue-400 text-sm"></i>
        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">
            Data per: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
        </span>
    </div>
</div>

{{-- Filter Panel Utama (Region, Branch & Program) --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 p-5 mb-8 shadow-xs">
    <form method="GET" action="{{ route('admin.rekap_progress') }}" id="filterForm">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 items-end">
            
            {{-- Region Filter --}}
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-map-location-dot mr-1 text-gray-300"></i> Region
                </label>
                <div class="relative">
                    <select name="region" id="regionSelect" onchange="updateBranchDropdown(); document.getElementById('filterForm').submit();"
                            class="w-full h-11 pl-4 pr-10 appearance-none rounded-xl bg-gray-50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-800 text-sm font-medium text-gray-800 dark:text-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                        <option value="">Semua Region</option>
                        <option value="JATIM" {{ request('region') == 'JATIM' ? 'selected' : '' }}>JATIM</option>
                        <option value="JATENG DIY" {{ request('region') == 'JATENG DIY' ? 'selected' : '' }}>JATENG DIY</option>
                        <option value="BALNUS" {{ request('region') == 'BALNUS' ? 'selected' : '' }}>BALNUS</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-xs">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
            </div>

            {{-- Branch Filter --}}
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-code-branch mr-1 text-gray-300"></i> Branch
                </label>
                <div class="relative">
                    <select name="branch" id="branchSelect" onchange="document.getElementById('filterForm').submit()"
                            class="w-full h-11 pl-4 pr-10 appearance-none rounded-xl bg-gray-50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-800 text-sm font-medium text-gray-800 dark:text-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                        <option value="">Semua Branch</option>
                        {{-- Opsi branch akan diisi otomatis via JS berdasarkan Region --}}
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-xs">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
            </div>

            {{-- Program Filter --}}
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-diagram-project mr-1 text-gray-300"></i> Program
                </label>
                <div class="relative">
                    <select name="program" onchange="document.getElementById('filterForm').submit()"
                            class="w-full h-11 pl-4 pr-10 appearance-none rounded-xl bg-gray-50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-800 text-sm font-medium text-gray-800 dark:text-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                        <option value="">Semua Program</option>
                        @foreach($programs as $program)
                            <option value="{{ $program }}" {{ request('program') == $program ? 'selected' : '' }}>
                                {{ $program }}
                            </option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-xs">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
            </div>
            {{-- Status Filter --}}
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    <i class="fa-solid fa-spinner mr-1 text-gray-300"></i> Status
                </label>
                <div class="relative">
                    <select name="status" onchange="document.getElementById('filterForm').submit()"
                            class="w-full h-11 pl-4 pr-10 appearance-none rounded-xl bg-gray-50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-800 text-sm font-medium text-gray-800 dark:text-gray-200 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="preparation" {{ request('status') == 'preparation' ? 'selected' : '' }}>Prepare</option>
                        <option value="instalasi" {{ request('status') == 'instalasi' ? 'selected' : '' }}>On Progress</option>
                        <option value="finishing" {{ request('status') == 'finishing' ? 'selected' : '' }}>Finish</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400 text-xs">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
            </div>

            {{-- Reset Button --}}
            @if(request('program') || request('branch') || request('region') || request('status') || request('per_page'))
                <div>
                    <a href="{{ route('admin.rekap_progress') }}" 
                       class="inline-flex items-center justify-center h-11 px-5 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 text-xs font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors w-full sm:w-auto">
                        <i class="fa-solid fa-rotate-left mr-2"></i> Reset Filter
                    </a>
                </div>
            @endif
        </div>
    </form>
</div>

{{-- Infographic Cards Widget (Diperbarui jadi 4 Card Sesuai Permintaan) --}}
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    
    {{-- Card 1: Total LOP --}}
    <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center text-lg shadow-xs shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-bookmark-icon lucide-bookmark">
                <path d="M17 3a2 2 0 0 1 2 2v15a1 1 0 0 1-1.496.868l-4.512-2.578a2 2 0 0 0-1.984 0l-4.512 2.578A1 1 0 0 1 5 20V5a2 2 0 0 1 2-2z"/></svg>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total LOP</p>
            <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $totalSegments }} <span class="text-xs font-medium text-gray-400">Berkas</span></h3>
        </div>
    </div>
    
    {{-- Card 2: Total Nilai BOQ (KHS) --}}
    <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center text-lg shadow-xs shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wallet"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Nilai</p>
            <h3 class="text-lg font-black text-gray-900 dark:text-white mt-0.5">Rp {{ number_format($totalNilaiBoq ?? 0, 0, ',', '.') }}</h3>
        </div>
    </div>
    
    {{-- Card 3: Target & Aktual FO (Digabung) --}}
    <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-500 text-white rounded-xl flex items-center justify-center text-lg shadow-xs shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-network-icon lucide-network">
                <rect x="16" y="16" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="9" y="2" width="6" height="6" rx="1"/><path d="M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"/><path d="M12 12V8"/></svg>
        </div>
        <div class="w-full">
            <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Penarikan FO (Target / Aktual)</p>
            <div class="flex items-baseline justify-between gap-1 mt-1">
                <h3 class="text-base font-black text-gray-900 dark:text-white">
                    {{ number_format($totalKabelPlan, 0, ',', '.') }} <span class="text-xs font-medium text-gray-400 mr-1">m</span> / 
                    <span class="text-sm font-bold text-gray-500">{{ number_format($totalKabelActual, 0, ',', '.') }}<span class="font-normal text-[10px]">m</span></span>
                </h3>
                <span class="text-xs font-extrabold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md">{{ number_format($totalKabelPersen, 1, ',', '.') }}%</span>
            </div>
        </div>
    </div>
    
    {{-- Card 4: Target & Aktual Tiang (Digabung) --}}
    <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 bg-green-500 text-white rounded-xl flex items-center justify-center text-lg shadow-xs shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-utility-pole-icon lucide-utility-pole"><path d="M12 2v20"/><path d="M2 5h20"/><path d="M3 3v2"/><path d="M7 3v2"/><path d="M17 3v2"/><path d="M21 3v2"/><path d="m19 5-7 7-7-7"/></svg>
        </div>
        <div class="w-full">
            <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Tanam Tiang (Target / Aktual)</p>
            <div class="flex items-baseline justify-between gap-1 mt-1">
                <h3 class="text-base font-black text-gray-900 dark:text-white">
                    {{ number_format($totalTiangPlan, 0, ',', '.') }} <span class="text-xs font-medium text-gray-400 mr-1">pcs</span> / 
                    <span class="text-sm font-bold text-gray-500">{{ number_format($totalTiangActual, 0, ',', '.') }}<span class="font-normal text-[10px]">pcs</span></span>
                </h3>
                <span class="text-xs font-extrabold text-green-600 bg-green-50 px-1.5 py-0.5 rounded-md">{{ number_format($totalTiangPersen, 1, ',', '.') }}%</span>
            </div>
        </div>
    </div>
</section>

{{-- Main Data Area --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    
    {{-- Data Table Segmen Proyek --}}
    <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs overflow-hidden">
        
        {{-- Table Header & Per-Page Selector (Di Atas Tabel) --}}
        <div class="p-5 border-b border-gray-100 dark:border-gray-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <h2 class="font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-table-list text-blue-600"></i> Detail Progres Per LOP
            </h2>
            
            {{-- Per Page Form Selector --}}
            <div class="flex items-center gap-2 self-end sm:self-auto">
                <span class="text-xs text-gray-400 font-medium whitespace-nowrap">Tampilkan:</span>
                <div class="relative w-28">
                    <select onchange="updatePerPage(this.value)"
                            class="w-full h-8 pl-3 pr-8 appearance-none rounded-lg bg-gray-50 dark:bg-gray-950/40 border border-gray-200 dark:border-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer py-0">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10 baris</option>
                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20 baris</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 baris</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100 baris</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2.5 pointer-events-none text-gray-400 text-[10px]">
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 dark:bg-black/40 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                        <th class="p-3.5 text-center w-12">No</th>
                        <th class="p-3.5">Branch / STO</th>
                        <th class="p-3.5">Nama LOP</th>
                        <th class="p-3.5 text-center">Panjang FO (m)</th>
                        <th class="p-3.5 text-center w-32">Aktual Penarikan FO</th>
                        <th class="p-3.5 text-center">Tiang (pcs)</th>
                        <th class="p-3.5 text-center w-32">Aktual Tanam Tiang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                    @forelse($tableData as $row)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-950/40 transition-colors">
                        <td class="p-3.5 text-center text-gray-400 font-mono">
                            {{ $row['no'] }}
                        </td>
                        <td class="p-3.5">
                            <div class="font-bold text-gray-900 dark:text-white">{{ $row['branch'] }}</div>
                            <div class="text-[10px] text-gray-400 dark:text-gray-500 font-mono mt-0.5"><i class="fa-solid fa-location-dot mr-1"></i>{{ $row['sto'] }}</div>
                        </td>
                        <td class="p-3.5">
                            <div class="font-semibold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">{{ $row['nama_lop'] }}</div>
                        </td>
                        <td class="p-3.5 text-center font-mono text-gray-900 dark:text-white">{{ number_format($row['kabel_plan'], 0, ',', '.') }} <span class="text-[10px] text-gray-400">m</span></td>
                        <td class="p-3.5">
                            <div class="space-y-1">
                                <div class="flex items-center justify-between font-mono text-[10px]">
                                    <span class="text-black-600">{{ number_format($row['kabel_actual'], 0, ',', '.') }} m</span>
                                    <span class="font-bold text-amber-600 dark:text-amber-400">{{ number_format($row['kabel_persen'], 1, ',', '.') }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ min($row['kabel_persen'], 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-3.5 text-center font-mono text-gray-900 dark:text-white">{{ number_format($row['tiang_plan'], 0, ',', '.') }} <span class="text-[10px] text-gray-400">pcs</span></td>
                        <td class="p-3.5">
                            <div class="space-y-1">
                                <div class="flex items-center justify-between font-mono text-[10px]">
                                    <span class="text-black-600">{{ number_format($row['tiang_actual'], 0, ',', '.') }} pcs</span>
                                    <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($row['tiang_persen'], 1, ',', '.') }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-green-500 h-full rounded-full transition-all duration-500" style="width: {{ min($row['tiang_persen'], 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center text-gray-400 dark:text-gray-500 font-medium bg-gray-50/50 dark:bg-black/20">
                            <div class="flex flex-col items-center justify-center gap-3">
                                <i class="fa-regular fa-folder-open text-4xl text-gray-300 mb-1"></i>
                                <span>Tidak ada segmen data proyek yang cocok dengan kriteria filter saat ini.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if(count($tableData) > 0)
                <tfoot class="bg-gray-50 dark:bg-black/30 font-bold text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-800">
                    <tr>
                        <td colspan="3" class="p-4 text-center uppercase tracking-wider text-xs font-bold text-gray-500 dark:text-gray-400">Total Halaman ini</td>
                        <td class="p-4 text-right font-mono">{{ number_format($tableData ? array_sum(array_column($tableData, 'kabel_plan')) : 0, 0, ',', '.') }}</td>
                        <td class="p-4 text-center font-mono text-amber-600 dark:text-amber-400 text-xs bg-amber-50/30 dark:bg-amber-950/10">Page Summary</td>
                        <td class="p-4 text-right font-mono">{{ number_format($tableData ? array_sum(array_column($tableData, 'tiang_plan')) : 0, 0, ',', '.') }}</td>
                        <td class="p-4 text-center font-mono text-green-600 dark:text-green-400 text-xs bg-green-50/30 dark:bg-green-950/10">Page Summary</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

        {{-- Pagination Links (Di Bawah Tabel) --}}
        @if($lopsData->hasPages())
        <div class="p-4 border-t border-gray-100 dark:border-gray-800/80 bg-gray-50/50 dark:bg-black/10">
            {{ $lopsData->onEachSide(1)->appends(request()->query())->links() }}
        </div>
        @endif
    </div>

    {{-- SISI KANAN: Kumpulan Card Summary (lg:col-span-1) --}}
    <div class="flex flex-col gap-6">
        
        {{-- CARD 1 BARU: Ringkasan Filter & Halaman Ini --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 p-5 shadow-xs">
            <div class="flex justify-between items-center mb-4">
                <h2 class="font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-cubes-stacked text-blue-600"></i> Summary Halaman Ini
                </h2>
                {{-- Indikator menyala jika filter sedang aktif --}}
                @if(request('program') || request('branch'))
                    <span class="relative flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                    </span>
                @endif
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                {{-- Total Segmen / Project (Dinamis) --}}
                <div class="col-span-2 bg-blue-50/50 dark:bg-blue-900/20 p-3 rounded-xl border border-blue-100 dark:border-blue-800/30 flex justify-between items-center mb-1">
                    <span class="text-xs font-bold text-blue-800 dark:text-blue-400 uppercase">Segmen Tampil</span>
                    <span class="font-black text-xl text-blue-600 dark:text-blue-300">{{ number_format($filterSegments, 0, ',', '.') }}</span>
                </div>
                
                {{-- Box Plan FO (Dinamis) --}}
                <div class="bg-gray-50 dark:bg-gray-950/50 p-3 rounded-xl border border-gray-100 dark:border-gray-800/50">
                    <div class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wide">Target FO</div>
                    <div class="font-mono font-bold text-gray-900 dark:text-white mt-1 text-sm">
                        {{ number_format($filterKabelPlan, 0, ',', '.') }} <span class="text-[10px] font-normal text-gray-400">m</span>
                    </div>
                </div>
                
                {{-- Box Aktual FO (Dinamis) --}}
                <div class="bg-amber-50/30 dark:bg-amber-950/20 p-3 rounded-xl border border-amber-100 dark:border-amber-900/30 relative overflow-hidden">
                    <div class="text-[10px] text-amber-600 dark:text-amber-500 font-bold uppercase tracking-wide">Aktual FO</div>
                    <div class="font-mono font-bold text-amber-600 dark:text-amber-400 mt-1 text-sm">
                        {{ number_format($filterKabelActual, 0, ',', '.') }} <span class="text-[10px] font-normal opacity-70">m</span>
                    </div>
                    <div class="absolute right-0 bottom-0 opacity-10">
                        <i class="fa-solid fa-chart-line text-3xl mb-[-5px] mr-[-5px]"></i>
                    </div>
                </div>

                {{-- Box Plan Tiang (Dinamis) --}}
                <div class="bg-gray-50 dark:bg-gray-950/50 p-3 rounded-xl border border-gray-100 dark:border-gray-800/50">
                    <div class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wide">Target Tiang</div>
                    <div class="font-mono font-bold text-gray-900 dark:text-white mt-1 text-sm">
                        {{ number_format($filterTiangPlan, 0, ',', '.') }} <span class="text-[10px] font-normal text-gray-400">pcs</span>
                    </div>
                </div>
                
                {{-- Box Aktual Tiang (Dinamis) --}}
                <div class="bg-green-50/30 dark:bg-green-950/20 p-3 rounded-xl border border-green-100 dark:border-green-900/30 relative overflow-hidden">
                    <div class="text-[10px] text-green-600 dark:text-green-500 font-bold uppercase tracking-wide">Aktual Tiang</div>
                    <div class="font-mono font-bold text-green-600 dark:text-green-400 mt-1 text-sm">
                        {{ number_format($filterTiangActual, 0, ',', '.') }} <span class="text-[10px] font-normal opacity-70">pcs</span>
                    </div>
                    <div class="absolute right-0 bottom-0 opacity-10">
                        <i class="fa-solid fa-chart-line text-3xl mb-[-5px] mr-[-5px]"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- CARD 2: Klasifikasi Progres (Donut Chart) --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 p-5 shadow-xs">
            <div>
                <h2 class="font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wider mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-blue-600"></i> Klasifikasi Progres
                </h2>
                <div class="w-40 h-40 mx-auto relative mb-5">
                    <canvas id="summaryDonut"></canvas>
                </div>
            </div>
            
            @php 
                $totalAll = array_sum($summaryStatus);
                $pSelesai = $totalAll > 0 ? round(($summaryStatus['selesai'] / $totalAll) * 100, 1) : 0;
                $pSedang = $totalAll > 0 ? round(($summaryStatus['sedang'] / $totalAll) * 100, 1) : 0;
                $pRendah = $totalAll > 0 ? round(($summaryStatus['rendah'] / $totalAll) * 100, 1) : 0;
                $pBelum = $totalAll > 0 ? round(($summaryStatus['belum'] / $totalAll) * 100, 1) : 0;
            @endphp
            
            <div class="text-xs space-y-2.5">
                <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-950/50 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800/40">
                    <span class="text-green-600 dark:text-green-400 font-semibold flex items-center gap-2">
                        <span class="w-2 h-2 rounded-sm bg-emerald-500 inline-block"></span> Selesai (&ge; 100%)
                    </span> 
                    <span class="font-bold font-mono text-gray-900 dark:text-white">{{ $summaryStatus['selesai'] }} <span class="text-[10px] text-gray-400 font-normal ml-1">({{ $pSelesai }}%)</span></span>
                </div>
                
                <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-950/50 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800/40">
                    <span class="text-amber-500 dark:text-amber-400 font-semibold flex items-center gap-2">
                        <span class="w-2 h-2 rounded-sm bg-amber-500 inline-block"></span> Sedang (50 - 99%)
                    </span> 
                    <span class="font-bold font-mono text-gray-900 dark:text-white">{{ $summaryStatus['sedang'] }} <span class="text-[10px] text-gray-400 font-normal ml-1">({{ $pSedang }}%)</span></span>
                </div>
                
                <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-950/50 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800/40">
                    <span class="text-orange-400 dark:text-orange-300 font-semibold flex items-center gap-2">
                        <span class="w-2 h-2 rounded-sm bg-orange-400 inline-block"></span> Rendah (1 - 49%)
                    </span> 
                    <span class="font-bold font-mono text-gray-900 dark:text-white">{{ $summaryStatus['rendah'] }} <span class="text-[10px] text-gray-400 font-normal ml-1">({{ $pRendah }}%)</span></span>
                </div>
                
                <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-950/50 p-2.5 rounded-xl border border-gray-100 dark:border-gray-800/40">
                    <span class="text-red-500 dark:text-red-400 font-semibold flex items-center gap-2">
                        <span class="w-2 h-2 rounded-sm bg-red-500 inline-block"></span> Belum Mulai
                    </span> 
                    <span class="font-bold font-mono text-gray-900 dark:text-white">{{ $summaryStatus['belum'] }} <span class="text-[10px] text-gray-400 font-normal ml-1">({{ $pBelum }}%)</span></span>
                </div>
            </div>
        </div>
        
    </div>
</div>

{{-- Bottom Gauge Section --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
    {{-- Gauge FO (Dinamis Berdasarkan Filter) --}}
    <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 flex flex-col items-center justify-center shadow-xs">
        <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4">Persentase Penarikan FO</h4>
        <div class="w-40 h-24 relative">
            <canvas id="gaugeFO"></canvas>
            <div class="absolute bottom-0 inset-x-0 text-center">
                <span class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($totalKabelPersen, 1, ',', '.') }}%</span>
            </div>
        </div>
        <p class="text-[11px] text-gray-500 font-mono mt-3 bg-gray-50 dark:bg-gray-950 px-3 py-1 rounded-full border border-gray-100 dark:border-gray-800/60">
            {{ number_format($totalKabelActual, 0, ',', '.') }} / {{ number_format($totalKabelPlan, 0, ',', '.') }} m
        </p>
    </div>

    {{-- Gauge Tiang (Dinamis Berdasarkan Filter) --}}
    <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 flex flex-col items-center justify-center shadow-xs">
        <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4">Persentase Tanam Tiang</h4>
        <div class="w-40 h-24 relative">
            <canvas id="gaugeTiang"></canvas>
            <div class="absolute bottom-0 inset-x-0 text-center">
                <span class="text-2xl font-black text-gray-900 dark:text-white">{{ number_format($totalTiangPersen, 1, ',', '.') }}%</span>
            </div>
        </div>
        <p class="text-[11px] text-gray-500 font-mono mt-3 bg-gray-50 dark:bg-gray-950 px-3 py-1 rounded-full border border-gray-100 dark:border-gray-800/60">
            {{ number_format($totalTiangActual, 0, ',', '.') }} / {{ number_format($totalTiangPlan, 0, ',', '.') }} pcs
        </p>
    </div>

    {{-- Line Graph Bulanan --}}
    <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs">
        <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4">Tren Progres (Simulasi)</h4>
        <div class="h-32">
            <canvas id="lineMonthly"></canvas>
        </div>
    </div>
</div>

{{-- Chart JS Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Donut Klasifikasi Status
    new Chart(document.getElementById('summaryDonut'), {
        type: 'doughnut',
        data: {
            labels: ['Selesai', 'Sedang', 'Rendah', 'Belum Mulai'],
            datasets: [{
                data: [
                    {{ $summaryStatus['selesai'] }},
                    {{ $summaryStatus['sedang'] }},
                    {{ $summaryStatus['rendah'] }},
                    {{ $summaryStatus['belum'] }}
                ],
                backgroundColor: ['#10B981', '#F59E0B', '#FB923C', '#EF4444'],
                borderWidth: 0
            }]
        },
        options: { 
            cutout: '80%', 
            plugins: { legend: { display: false } },
            maintainAspectRatio: false 
        }
    });

    // 2. Gauge Chart FO
    let kabelPersen = {{ min($totalKabelPersen, 100) }};
    new Chart(document.getElementById('gaugeFO'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [kabelPersen, 100 - kabelPersen],
                backgroundColor: ['#F59E0B', '#F3F4F6'],
                borderWidth: 0
            }]
        },
        options: { rotation: -90, circumference: 180, cutout: '82%', plugins: { legend: { display: false } }, maintainAspectRatio: false }
    });

    // 3. Gauge Chart Tiang
    let tiangPersen = {{ min($totalTiangPersen, 100) }};
    new Chart(document.getElementById('gaugeTiang'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [tiangPersen, 100 - tiangPersen],
                backgroundColor: ['#10B981', '#F3F4F6'],
                borderWidth: 0
            }]
        },
        options: { rotation: -90, circumference: 180, cutout: '82%', plugins: { legend: { display: false } }, maintainAspectRatio: false }
    });

    // 4. Line Chart Tren Bulanan (Dibuat skala proporsional dinamis dari Persentase)
    // Formula agar grafik selalu berujung pada nilai persen terbaru
    let pk = kabelPersen;
    let pt = tiangPersen;
    
    new Chart(document.getElementById('lineMonthly'), {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
            datasets: [
                { 
                    label: 'FO (%)', 
                    data: [pk * 0.15, pk * 0.35, pk * 0.5, pk * 0.75, pk * 0.9, pk], 
                    borderColor: '#F59E0B', backgroundColor: 'transparent', tension: 0.3, pointRadius: 3, borderWidth: 2 
                },
                { 
                    label: 'Tiang (%)', 
                    data: [pt * 0.2, pt * 0.4, pt * 0.6, pt * 0.8, pt * 0.95, pt], 
                    borderColor: '#10B981', backgroundColor: 'transparent', tension: 0.3, pointRadius: 3, borderWidth: 2 
                }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { legend: { display: false } }, 
            scales: { 
                y: { min: 0, max: 100, ticks: { font: { size: 9 }, stepSize: 25 }, grid: { color: '#F3F4F6' } }, 
                x: { grid: { display: false }, ticks: { font: { size: 9 } } } 
            } 
        }
    });
</script>

<script>
    function updatePerPage(val) {
        const filterForm = document.getElementById('filterForm');
        const perPageInput = filterForm.querySelector('input[name="per_page"]');
        if (perPageInput) {
            perPageInput.value = val;
            filterForm.submit();
        } else {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', val);
            window.location.href = url.href;
        }
    }
</script>

<script>
    // Data Mapping Region ke Branch (Baku / Uppercase)
    const regionMapping = {
        'JATIM': ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
        'JATENG DIY': ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
        'BALNUS': ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES']
    };

    function updateBranchDropdown() {
        const regionSelect = document.getElementById('regionSelect');
        const branchSelect = document.getElementById('branchSelect');
        
        // Pastikan nilai pencarian selalu uppercase
        const selectedRegion = regionSelect.value.toUpperCase(); 
        const currentBranch = "{{ request('branch') }}".toUpperCase(); 
        
        // Bersihkan opsi lama
        branchSelect.innerHTML = '<option value="">Semua Branch</option>';
        
        // Isi dengan opsi baru jika region dipilih
        if (selectedRegion && regionMapping[selectedRegion]) {
            regionMapping[selectedRegion].forEach(function(branch) {
                let option = document.createElement('option');
                option.value = branch; 
                option.text = branch;
                
                // Pengecekan case-insensitive (sama-sama uppercase)
                if(branch.toUpperCase() === currentBranch) {
                    option.selected = true;
                }
                
                branchSelect.appendChild(option);
            });
        }
    }

    // Jalankan saat halaman pertama kali diload
    document.addEventListener("DOMContentLoaded", function() {
        updateBranchDropdown();
    });
</script>

@endsection