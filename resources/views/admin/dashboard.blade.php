@extends('layouts.admin')

@section('content')

@php
    $completionRate = $completionRate ?? 0;

    $mainCards = [
        [
            'label' => 'Total LOP',
            'value' => $totalLop ?? 0,
            'desc' => 'Seluruh LOP terdaftar',
            // Paste kode SVG Anda di sini
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

    $evidenceCards = [
        ['label' => 'Total Evidence', 'value' => $totalEvidence ?? 0, 'color' => 'slate'],
        ['label' => 'Pending', 'value' => $pendingEvidence ?? 0, 'color' => 'amber'],
        ['label' => 'Approved', 'value' => $approvedEvidence ?? 0, 'color' => 'emerald'],
        ['label' => 'Rejected', 'value' => $rejectedEvidence ?? 0, 'color' => 'red'],
    ];

    $sections = [
        'Statistik Branch' => $statsByBranch ?? collect(),
        'Statistik Program' => $statsByProgram ?? collect(),
        'Statistik Batch' => $statsByBatch ?? collect(),
    ];
@endphp

<div class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6">

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <p class="text-xs font-black text-blue-700 uppercase tracking-widest">
                        Analytics Dashboard
                    </p>

                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">
                        Dashboard Monitoring
                    </h1>

                    <!-- <p class="text-sm text-slate-500 mt-2 max-w-2xl">
                        Ringkasan operasional Dompis dari upload PID, BOQ, assignment Waspang, evidence, approval, sampai project complete.
                    </p> -->
                </div>

                <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-4 min-w-[220px]">
                    <p class="text-xs text-slate-500 font-bold uppercase">
                        Completion Rate
                    </p>

                    <div class="flex items-end justify-between gap-3 mt-2">
                        <p class="text-3xl font-black text-emerald-700">
                            {{ $completionRate }}%
                        </p>

                        <span class="text-xs font-black text-slate-500">
                            {{ number_format($completedApproval ?? 0) }}/{{ number_format($totalLop ?? 0) }}
                        </span>
                    </div>

                    <div class="mt-3 h-2 rounded-full bg-slate-200 dark:bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full bg-emerald-500"
                             style="width: {{ min($completionRate, 100) }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- MAIN KPI --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach($mainCards as $card)
                <div class="rounded-3xl bg-white dark:bg-slate-900 border {{ $card['border'] }} dark:border-slate-800 p-5 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">
                                {{ $card['label'] }}
                            </p>

                            <p class="text-3xl font-black {{ $card['text'] }} dark:text-white mt-2">
                                {{ number_format($card['value']) }}
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                {{ $card['desc'] }}
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl {{ $card['bg'] }} flex items-center justify-center text-2xl">
                            {!! $card['icon'] !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- PIPELINE --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-5">
                <div>
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">
                        Alur Progress
                    </h2>
                    <p class="text-sm text-slate-500">
                        Kondisi project berdasarkan flow operasional.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                @foreach($stageSummary ?? [] as $stage)
                    @php
                        $color = $stage['color'] ?? 'slate';

                        $classes = [
                            'amber' => 'bg-amber-50 border-amber-200 text-amber-700',
                            'red' => 'bg-red-50 border-red-200 text-red-700',
                            'blue' => 'bg-blue-50 border-blue-200 text-blue-700',
                            'orange' => 'bg-orange-50 border-orange-200 text-orange-700',
                            'emerald' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
                            'slate' => 'bg-slate-50 border-slate-200 text-slate-700',
                        ];

                        $class = $classes[$color] ?? $classes['slate'];
                    @endphp

                    <div class="rounded-3xl border p-5 {{ $class }}">
                        <p class="text-xs font-black uppercase">
                            {{ $stage['label'] }}
                        </p>

                        <p class="text-3xl font-black mt-2">
                            {{ number_format($stage['value']) }}
                        </p>

                        <p class="text-xs mt-1 opacity-80">
                            {{ $stage['desc'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- EVIDENCE + BOQ --}}
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">

            {{-- Evidence --}}
            <div class="xl:col-span-7 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">
                            Evidence Review
                        </h2>
                        <p class="text-sm text-slate-500">
                            Status approval evidence dari Waspang.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($evidenceCards as $item)
                        @php
                            $colorClass = match($item['color']) {
                                'amber' => 'bg-amber-50 border-amber-100 text-amber-700',
                                'emerald' => 'bg-emerald-50 border-emerald-100 text-emerald-700',
                                'red' => 'bg-red-50 border-red-100 text-red-700',
                                default => 'bg-slate-50 border-slate-100 text-slate-700',
                            };
                        @endphp

                        <div class="rounded-3xl border p-4 {{ $colorClass }}">
                            <p class="text-xs font-bold">
                                {{ $item['label'] }}
                            </p>

                            <p class="text-3xl font-black mt-2">
                                {{ number_format($item['value']) }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 font-bold">Belum BOQ</p>
                        <p class="text-2xl font-black text-amber-700 mt-1">
                            {{ number_format($belumBoq ?? 0) }}
                        </p>
                    </div>

                    <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 font-bold">Belum Assign</p>
                        <p class="text-2xl font-black text-red-700 mt-1">
                            {{ number_format($unassignedLop ?? 0) }}
                        </p>
                    </div>

                    <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 font-bold">On Progress</p>
                        <p class="text-2xl font-black text-blue-700 mt-1">
                            {{ number_format($onProgress ?? 0) }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- BOQ Summary --}}
            <div class="xl:col-span-5 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <h2 class="text-lg font-black text-slate-900 dark:text-white">
                    BOQ Summary
                </h2>

                <p class="text-sm text-slate-500 mt-1">
                    Nilai dan progress BOQ actual.
                </p>

                <div class="mt-5 rounded-3xl bg-emerald-50 border border-emerald-100 p-5">
                    <p class="text-xs text-emerald-700 font-bold uppercase">
                        Total Nilai BOQ
                    </p>

                    <p class="text-2xl font-black text-emerald-700 mt-2">
                        Rp {{ number_format($totalBoqValue ?? 0, 0, ',', '.') }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 font-bold">Item Material</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                            {{ number_format($materialItem ?? 0) }}
                        </p>
                    </div>

                    <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 font-bold">Item Jasa</p>
                        <p class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                            {{ number_format($jasaItem ?? 0) }}
                        </p>
                    </div>
                </div>

                <div class="mt-5">
                    <div class="flex items-center justify-between text-xs mb-2">
                        <span class="font-bold text-slate-500">
                            BOQ Actual Rate
                        </span>
                        <span class="font-black text-slate-900 dark:text-white">
                            {{ $boqActualRate ?? 0 }}%
                        </span>
                    </div>

                    <div class="h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full bg-blue-600"
                             style="width: {{ min($boqActualRate ?? 0, 100) }}%">
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 mt-2">
                        {{ number_format($boqActualItem ?? 0) }} dari {{ number_format($totalBoqItem ?? 0) }} item sudah memiliki actual.
                    </p>
                </div>
            </div>

        </div>

        {{-- STATISTICS TABLES --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            @foreach($sections as $title => $items)
                {{-- Card dikunci tinggi maksimalnya (max-h-[400px]) dan diatur sebagai flex column --}}
                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm flex flex-col max-h-[400px]">
                    
                    {{-- HEADER STATIC (TETAP DI ATAS) --}}
                    <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xs font-black uppercase tracking-wider text-slate-800 dark:text-white">
                                {{ $title }}
                            </h2>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                {{ $items->count() }} Data
                            </span>
                        </div>
                    </div>

                    {{-- SCROLLABLE CONTAINER MURNI TAILWIND (SCROLL KHUSUS PER CARD) --}}
                    <div class="overflow-y-auto overflow-x-auto flex-1 
                                [&::-webkit-scrollbar]:w-1 
                                [&::-webkit-scrollbar]:h-1 
                                [&::-webkit-scrollbar-track]:bg-transparent 
                                [&::-webkit-scrollbar-thumb]:bg-slate-200 
                                dark:[&::-webkit-scrollbar-thumb]:bg-slate-800 
                                [&::-webkit-scrollbar-thumb]:rounded-full 
                                hover:[&::-webkit-scrollbar-thumb]:bg-slate-300
                                dark:hover:[&::-webkit-scrollbar-thumb]:bg-slate-700">
                        
                        <table class="w-full text-[11px] border-collapse">
                            <thead class="bg-slate-50/70 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800 sticky top-0 z-10 backdrop-blur-xs">
                                <tr class="text-slate-400 font-bold uppercase tracking-tight text-[10px]">
                                    <th class="px-4 py-2 text-left">Nama</th>
                                    <th class="px-2 py-2 text-center w-12">Total</th>
                                    <th class="px-2 py-2 text-center w-12">Assign</th>
                                    <th class="px-2 py-2 text-center w-12">Review</th>
                                    <th class="px-2 py-2 text-center w-12">Done</th>
                                    <th class="px-3 py-2 text-center w-14">%</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                @forelse($items as $item)
                                    @php
                                        $percent = $item['percent'] ?? 0;
                                    @endphp

                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-colors duration-150">
                                        <td class="px-4 py-2.5 min-w-[130px]">
                                            <div class="font-bold text-slate-800 dark:text-slate-200 truncate max-w-[140px]" title="{{ $item['label'] }}">
                                                {{ $item['label'] }}
                                            </div>
                                            <div class="mt-1 h-1 w-20 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                                <div class="h-full bg-emerald-500 rounded-full"
                                                    style="width: {{ min($percent, 100) }}%">
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-2 py-2.5 text-center font-mono font-bold text-slate-700 dark:text-slate-300">
                                            {{ $item['total'] }}
                                        </td>

                                        <td class="px-2 py-2.5 text-center">
                                            <span class="inline-block w-7 py-0.5 rounded text-center bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 font-mono font-bold">
                                                {{ $item['assigned'] }}
                                            </span>
                                        </td>

                                        <td class="px-2 py-2.5 text-center">
                                            <span class="inline-block w-7 py-0.5 rounded text-center bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 font-mono font-bold">
                                                {{ $item['waiting'] }}
                                            </span>
                                        </td>

                                        <td class="px-2 py-2.5 text-center">
                                            <span class="inline-block w-7 py-0.5 rounded text-center bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-mono font-bold">
                                                {{ $item['completed'] }}
                                            </span>
                                        </td>

                                        <td class="px-3 py-2.5 text-center font-mono font-black text-slate-900 dark:text-white">
                                            {{ $percent }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-400 italic">
                                            Belum ada data statistik.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            @endforeach
        </div>

    </div>
</div>

@endsection