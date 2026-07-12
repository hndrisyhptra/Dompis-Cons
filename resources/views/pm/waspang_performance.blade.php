@extends('layouts.pm')

@section('content')
<div class="space-y-6">
    
    {{-- Header Modul --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Kinerja & Produktivitas Waspang</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Analisis metrik kepatuhan unggah eviden dan efisiensi progres lapangan per pengawas.</p>
        </div>
        
        {{-- Filter Branch Cepat --}}
        <form method="GET" action="{{ route('pm.waspang.performance') }}" id="branchFilterForm" class="w-full sm:w-64">
            <select name="branch" onchange="document.getElementById('branchFilterForm').submit()"
                    class="w-full h-10 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-xs font-semibold text-gray-700 dark:text-gray-300">
                <option value="">Semua Wilayah / Branch</option>
                @foreach($branches as $b)
                    <option value="{{ $b }}" {{ request('branch') == $b ? 'selected' : '' }}>{{ $b }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- 1. TOP WIDGET KINERJA --}}
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center text-lg"><i class="fa-solid fa-user-gear"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Waspang Aktif</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $totalWaspangActive }} <span class="text-xs font-medium text-gray-400">Orang</span></h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center text-lg"><i class="fa-solid fa-file-shield"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Eviden Masuk</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ number_format($totalAllEvidences, 0, ',', '.') }} <span class="text-xs font-medium text-gray-400">File</span></h3>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center text-lg"><i class="fa-solid fa-star"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Standar Kepatuhan</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">85% <span class="text-xs font-medium text-gray-400">KPI Target</span></h3>
            </div>
        </div>
    </section>

    {{-- 2. LEADERBOARD / TABLE UTAMA --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 dark:border-gray-800">
            <h2 class="font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wider">
                <i class="fa-solid fa-medal text-amber-500 mr-2"></i> Peringkat Capaian Lapangan Waspang
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 dark:bg-black/40 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                        <th class="p-4 text-center w-12">Rank</th>
                        <th class="p-4">Nama Pengawas</th>
                        <th class="p-4 text-center">Cakupan Segmen</th>
                        <th class="p-4">Rata-rata Progres FO</th>
                        <th class="p-4">Rata-rata Progres Tiang</th>
                        <th class="p-4 text-center">Total Eviden</th>
                        <th class="p-4 text-center">Rata-Rata Skor</th>
                        <th class="p-4 text-center">Predikat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                    @forelse(collect($performanceData)->sortByDesc('avg_score') as $index => $row)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-950/40 transition-colors">
                        <td class="p-4 text-center font-mono font-bold text-gray-400">
                            #{{ $index + 1 }}
                        </td>
                        <td class="p-4 font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[10px] text-gray-500 font-mono">
                                {{ strtoupper(substr($row['name'], 0, 2)) }}
                            </div>
                            {{ $row['name'] }}
                        </td>
                        <td class="p-4 text-center font-semibold font-mono">{{ $row['segments'] }} LOP</td>
                        
                        {{-- Progres FO --}}
                        <td class="p-4 w-44">
                            <div class="flex items-center justify-between font-mono text-[10px] mb-1">
                                <span class="font-bold text-amber-600">{{ number_format($row['kabel_persen'], 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-amber-500 h-full rounded-full" style="width: {{ min($row['kabel_persen'], 100) }}%"></div>
                            </div>
                        </td>

                        {{-- Progres Tiang --}}
                        <td class="p-4 w-44">
                            <div class="flex items-center justify-between font-mono text-[10px] mb-1">
                                <span class="font-bold text-green-600">{{ number_format($row['tiang_persen'], 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-green-500 h-full rounded-full" style="width: {{ min($row['tiang_persen'], 100) }}%"></div>
                            </div>
                        </td>

                        <td class="p-4 text-center font-mono text-gray-600 dark:text-gray-400 font-bold">
                            {{ $row['evidences'] }} File
                        </td>

                        <td class="p-4 text-center font-black font-mono text-sm text-blue-600 dark:text-blue-400">
                            {{ number_format($row['avg_score'], 1) }}%
                        </td>

                        {{-- Predikat Badge --}}
                        <td class="p-4 text-center">
                            <span class="px-2.5 py-1 rounded-full border text-[10px] font-bold uppercase tracking-wide inline-block {{ $row['class'] }}">
                                {{ $row['grade'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-12 text-center text-gray-400 dark:text-gray-500 font-medium">
                            Tidak ada data aktivitas kueri pengawas lapangan saat ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection