@extends('layouts.pm')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Dashboard Monitoring</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Rangkuman performa konstruksi LOP, kinerja Waspang, dan kendala lapangan secara real-time.</p>
        </div>
        <div class="flex items-center gap-2 text-sm font-semibold text-gray-600 dark:text-gray-400 bg-white dark:bg-gray-900 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-indigo-500"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Data Terkini: {{ now()->translatedFormat('d F Y') }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total LOP Terdaftar</p>
                    <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ number_format($totalLop) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4c0-.5.2-1 .6-1.4C5 2.2 5.5 2 6 2h12c.5 0 1 .2 1.4.6.4.4.6.9.6 1.4v18l-4-2-4 2-4-2-4 2z"/></svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Total segmen project</p>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Berkas Eviden</p>
                    <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ number_format($totalEvidence) }}</h3>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/></svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Unggahan dokumentasi fisik waspang</p>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm transition hover:shadow-md relative overflow-hidden">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Menunggu Approval</p>
                    <div class="flex items-center gap-2 mt-1">
                        <h3 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ number_format($pendingEvidence) }}</h3>
                        @if($pendingEvidence > 0)
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" x2="15" y1="15" y2="15"/><line x1="9" x2="13" y1="19" y2="19"/></svg>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Memerlukan verifikasi oleh tim Admin</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Flow Progres Konstruksi</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($stageSummary as $stage)
                <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/50 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $stage['label'] }}</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold
                                {{ $stage['color'] == 'indigo' ? 'bg-indigo-100 text-indigo-700' : '' }}
                                {{ $stage['color'] == 'amber' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $stage['color'] == 'emerald' ? 'bg-emerald-100 text-emerald-700' : '' }}
                            ">
                                {{ $stage['value'] }} LOP
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">{{ $stage['desc'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        
        <div class="xl:col-span-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm flex flex-col">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Progres Fisik Per Branch Wilayah</h2>
            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-800 text-xs font-bold uppercase text-gray-400 tracking-wider">
                            <th class="pb-3 font-semibold">Nama Branch</th>
                            <th class="pb-3 text-center font-semibold">Total LOP</th>
                            <th class="pb-3 text-center font-semibold">Assigned</th>
                            <th class="pb-3 text-center font-semibold">On Progress</th>
                            <th class="pb-3 text-center font-semibold">Persentase</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                        @forelse($statsByBranch as $branch)
                            <tr>
                                <td class="py-3.5 font-semibold text-gray-800 dark:text-gray-200">{{ $branch['label'] }}</td>
                                <td class="py-3.5 text-center font-medium text-gray-600 dark:text-gray-400">{{ $branch['total'] }}</td>
                                <td class="py-3.5 text-center text-gray-600 dark:text-gray-400">{{ $branch['assigned'] }}</td>
                                <td class="py-3.5 text-center text-gray-600 dark:text-gray-400">{{ $branch['waiting'] }}</td>
                                <td class="py-3.5">
                                    <div class="flex items-center justify-end gap-3">
                                        <div class="w-24 bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden hidden sm:block">
                                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $branch['percent'] }}%"></div>
                                        </div>
                                        <span class="font-bold text-indigo-600 dark:text-indigo-400 text-xs">{{ $branch['percent'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-400">Belum ada data branch terpetakan</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-1">Beban Kerja Waspang</h2>
            <p class="text-xs text-gray-400 mb-4">Top 8 Pengawas Lapangan dengan beban aktif</p>
            <div class="space-y-3.5">
                @forelse($waspangStats as $waspang)
                    <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/50">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                {{ strtoupper(substr($waspang->name, 0, 2)) }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 line-clamp-1">{{ $waspang->name }}</h4>
                                <p class="text-[11px] text-gray-400">Aktor Pengawas</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-lg shadow-sm">
                            {{ $waspang->total_assignment }} LOP
                        </span>
                    </div>
                @empty
                    <p class="text-center text-sm text-gray-400 py-8">Belum ada tugas waspang didistribusikan.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">LOP Butuh Perhatian</h2>
                <p class="text-xs text-gray-400">Deteksi otomatis LOP yang belum ter-mapping PID atau mendeteksi berkas fisik berstatus Rejected.</p>
            </div>
            <span class="px-2.5 py-1 text-xs font-bold bg-red-50 text-red-700 border border-red-100 rounded-xl">High Priority Alert</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800 text-xs font-bold uppercase text-gray-400 tracking-wider">
                        <th class="pb-3 font-semibold">ID LOP</th>
                        <th class="pb-3 font-semibold">Nama LOP</th>
                        <th class="pb-3 font-semibold">Branch</th>
                        <th class="pb-3 font-semibold">Status Tahapan</th>
                        <th class="pb-3 text-right font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-sm">
                    @forelse($attentionProjects as $item)
                        <tr>
                            <td class="py-3 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $item->id_lop }}</td>
                            <td class="py-3 font-semibold text-gray-800 dark:text-gray-200 max-w-xs truncate">{{ $item->lop_name }}</td>
                            <td class="py-3 text-gray-600 dark:text-gray-400">{{ $item->branch ?? '-' }}</td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 capitalize">
                                    {{ $item->status_progress ?? 'Preparation' }}
                                </span>
                            </td>
                            <td class="py-3 text-right">
                                <a href="{{ route('pm.rekap_progress', ['branch' => $item->branch]) }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    Audit LOP
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-emerald-600 dark:text-emerald-400 font-semibold bg-emerald-50/30 dark:bg-emerald-950/20 rounded-xl">
                                🎉 Bagus! Seluruh LOP terpetakan dengan aman tanpa kendala berkas fisik/reject.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection