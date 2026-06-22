@extends('layouts.admin')

@section('content')

@php
    $onProgress = max(($assignedLop ?? 0) - ($completedApproval ?? 0), 0);
    $unassigned = max(($totalLop ?? 0) - ($assignedLop ?? 0), 0);
    $completePercent = ($totalLop ?? 0) > 0 ? round(($completedApproval / $totalLop) * 100) : 0;

    $cards = [
        [
            'label' => 'Total LOP',
            'value' => $totalLop,
            'desc' => 'Seluruh LOP terdaftar',
            'color' => 'text-slate-900 dark:text-white',
            'bg' => 'bg-slate-100 dark:bg-slate-800',
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z',
        ],
        [
            'label' => 'Sudah Assign',
            'value' => $assignedLop,
            'desc' => 'Sudah memiliki Waspang',
            'color' => 'text-blue-700 dark:text-blue-400',
            'bg' => 'bg-blue-100 dark:bg-blue-900/40',
            'icon' => 'M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 100-8 4 4 0 000 8zm6 0a3 3 0 100-6 3 3 0 000 6z',
        ],
        [
            'label' => 'Menunggu Review',
            'value' => $waitingApproval,
            'desc' => 'Progress belum 100%',
            'color' => 'text-amber-600 dark:text-amber-400',
            'bg' => 'bg-amber-100 dark:bg-amber-900/40',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        [
            'label' => 'Complete Approval',
            'value' => $completedApproval,
            'desc' => 'Progress selesai 100%',
            'color' => 'text-emerald-700 dark:text-emerald-400',
            'bg' => 'bg-emerald-100 dark:bg-emerald-900/40',
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    ];

    $sections = [
        'Statistik Batch' => $statsByBatch,
        'Statistik Branch' => $statsByBranch,
        'Statistik Program' => $statsByProgram,
    ];
@endphp

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-gray-900 dark:text-white">
                Dashboard Monitoring
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Ringkasan progress LOP, assignment, approval, dan performa project.
            </p>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach($cards as $card)
            <div class="group bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm hover:shadow-lg transition">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            {{ $card['label'] }}
                        </p>
                        <h2 class="text-3xl font-black mt-2 {{ $card['color'] }}">
                            {{ number_format($card['value']) }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $card['desc'] }}
                        </p>
                    </div>

                    <div class="w-11 h-11 rounded-2xl {{ $card['bg'] }} flex items-center justify-center">
                        <svg class="w-6 h-6 {{ $card['color'] }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- {{-- Quick Monitoring --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl p-5 text-white shadow-sm">
            <p class="text-sm text-blue-100">On Progress</p>
            <h3 class="text-4xl font-black mt-2">{{ number_format($onProgress) }}</h3>
            <p class="text-sm text-blue-100 mt-2">
                LOP sudah assign namun belum complete approval.
            </p>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-3xl p-5 text-white shadow-sm">
            <p class="text-sm text-amber-100">Belum Assign</p>
            <h3 class="text-4xl font-black mt-2">{{ number_format($unassigned) }}</h3>
            <p class="text-sm text-amber-100 mt-2">
                Perlu segera dialokasikan ke Waspang.
            </p>
        </div>

        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl p-5 text-white shadow-sm">
            <p class="text-sm text-emerald-100">Completion Rate</p>
            <h3 class="text-4xl font-black mt-2">{{ $completePercent }}%</h3>
            <p class="text-sm text-emerald-100 mt-2">
                Persentase LOP yang sudah selesai approval.
            </p>
        </div>

    </div> -->

    {{-- Section Tables --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        @foreach($sections as $title => $items)

            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">

                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-black text-gray-900 dark:text-white">
                            {{ $title }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-1">
                            Total, assignment, review, dan completion.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/70">
                            <tr class="text-gray-500">
                                <th class="px-4 py-3 text-left">Nama</th>
                                <th class="px-3 py-3 text-center">Total</th>
                                <th class="px-3 py-3 text-center">Assign</th>
                                <th class="px-3 py-3 text-center">Review</th>
                                <th class="px-3 py-3 text-center">Done</th>
                                <th class="px-3 py-3 text-center">%</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($items as $item)
                                @php
                                    $percent = $item['percent'] ?? (
                                        ($item['total'] ?? 0) > 0
                                            ? round(($item['completed'] / $item['total']) * 100)
                                            : 0
                                    );
                                @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                                    <td class="px-4 py-3">
                                        <div class="font-black text-gray-900 dark:text-white">
                                            {{ $item['label'] }}
                                        </div>
                                        <div class="mt-1 h-1.5 w-24 bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden">
                                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-3 text-center font-bold text-gray-700 dark:text-gray-200">
                                        {{ $item['total'] }}
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <span class="inline-flex px-2 py-1 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 font-black">
                                            {{ $item['assigned'] }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <span class="inline-flex px-2 py-1 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 font-black">
                                            {{ $item['waiting'] }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <span class="inline-flex px-2 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 font-black">
                                            {{ $item['completed'] }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-3 text-center font-black text-gray-900 dark:text-white">
                                        {{ $percent }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                                        Belum ada data.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

        @endforeach
    </div>

    {{-- Monitoring tambahan --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <h2 class="text-sm font-black text-gray-900 dark:text-white">
                Rekomendasi Monitoring PM/Admin
            </h2>

            <div class="mt-4 space-y-3 text-sm">
                <div class="flex items-start gap-3">
                    <span class="w-2 h-2 mt-2 rounded-full bg-red-500"></span>
                    <p class="text-gray-600 dark:text-gray-300">
                        <b>Aging Project</b> — LOP yang belum selesai lebih dari 7, 14, 30 hari.
                    </p>
                </div>

                <div class="flex items-start gap-3">
                    <span class="w-2 h-2 mt-2 rounded-full bg-amber-500"></span>
                    <p class="text-gray-600 dark:text-gray-300">
                        <b>Top Problem Project</b> — Project progress rendah, evidence reject, atau belum assign.
                    </p>
                </div>

                <div class="flex items-start gap-3">
                    <span class="w-2 h-2 mt-2 rounded-full bg-blue-500"></span>
                    <p class="text-gray-600 dark:text-gray-300">
                        <b>Performa Waspang</b> — jumlah project aktif, selesai, dan overload.
                    </p>
                </div>

                <div class="flex items-start gap-3">
                    <span class="w-2 h-2 mt-2 rounded-full bg-emerald-500"></span>
                    <p class="text-gray-600 dark:text-gray-300">
                        <b>Performa Mitra</b> — total LOP, selesai, dan completion rate per mitra.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
            <h2 class="text-sm font-black text-gray-900 dark:text-white">
                Prioritas Follow Up Hari Ini
            </h2>

            <div class="mt-4 space-y-3">
                <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40">
                    <p class="text-sm font-black text-red-700 dark:text-red-300">
                        LOP belum assign
                    </p>
                    <p class="text-xs text-red-600 dark:text-red-300 mt-1">
                        Perlu dicek agar tidak tertahan sebelum pekerjaan lapangan.
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-900/20 border border-amber-100 dark:border-amber-900/40">
                    <p class="text-sm font-black text-amber-700 dark:text-amber-300">
                        Evidence menunggu review
                    </p>
                    <p class="text-xs text-amber-600 dark:text-amber-300 mt-1">
                        Perlu approval agar progress bisa naik ke tahap berikutnya.
                    </p>
                </div>

                <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-900/40">
                    <p class="text-sm font-black text-blue-700 dark:text-blue-300">
                        Project belum complete
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">
                        Pantau project aktif yang belum mencapai progress 100%.
                    </p>
                </div>
            </div>
        </div>

    </div>

</div>

@endsection