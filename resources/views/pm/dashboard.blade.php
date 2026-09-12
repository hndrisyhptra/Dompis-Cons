@extends('layouts.pm')

@section('content')
<div class="space-y-6" x-data="matrixDetailModal()">
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

    @php
        // Widget "Ringkasan & Alur Progress PT 3": BOQ Ready/Belum BOQ dan
        // Sudah Assign/Belum Assign ditampilkan berpasangan dalam 1 card,
        // ditambah On Progress dan Completed - total cuma 4 card ringkas.
        // Total LOP jadi angka acuan di header widget. SEMUA angka bisa
        // diklik untuk membuka modal daftar LOP-nya (matrixDetailModal() di
        // atas, metric harus sinkron dengan DashboardPmController::matrixDetail()).
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

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Ringkasan &amp; Alur Progress PT 3</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Klik angka untuk lihat daftar LOP-nya.</p>
            </div>

            <button type="button"
                    @click="show({type:'assignment', region:'', branch:'', metric:''})"
                    class="flex items-center gap-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 self-start sm:self-auto hover:border-indigo-300 dark:hover:border-indigo-700 transition text-left">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center shrink-0 text-indigo-600 dark:text-indigo-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 lucide lucide-file-spreadsheet"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/></svg>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-gray-400 leading-none">Total LOP PT 3</p>
                    <p class="text-xl font-black text-gray-900 dark:text-white leading-tight mt-1 hover:underline decoration-2 underline-offset-2">{{ number_format($totalLop ?? 0) }}</p>
                </div>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($pipelineSteps as $step)
                @php
                    $percent = $pctOf($step['value']);
                    $subPercent = $step['sub'] ? $pctOf($step['sub']['value']) : null;
                @endphp
                <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl {{ $step['icon_bg'] }} {{ $step['icon_text'] }} flex items-center justify-center shrink-0">
                            {!! $step['icon'] !!}
                        </div>
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $step['label'] }}</p>
                    </div>

                    <div class="mt-3 flex items-end justify-between gap-3">
                        <button type="button" class="text-left"
                                @click="show({type:'assignment', region:'', branch:'', metric:'{{ $step['metric'] }}'})">
                            <p class="text-2xl font-black text-gray-900 dark:text-white leading-none hover:underline decoration-2 underline-offset-2">{{ number_format($step['value']) }}</p>
                            <p class="text-[10px] text-gray-400 mt-1">{{ $percent }}% dari total</p>
                        </button>

                        @if($step['sub'])
                            <button type="button" class="text-right"
                                    @click.stop="show({type:'assignment', region:'', branch:'', metric:'{{ $step['sub']['metric'] }}'})">
                                <p class="text-sm font-bold text-gray-500 dark:text-gray-400 hover:underline decoration-2 underline-offset-2">{{ number_format($step['sub']['value']) }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $step['sub']['label'] }} · {{ $subPercent }}%</p>
                            </button>
                        @endif
                    </div>

                    <div class="mt-3 h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div class="h-full rounded-full {{ $step['bar'] }}" style="width: {{ min($percent, 100) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- REPORTING DEPLOYMENT (DROP/HOLD/PREPARING/PERIZINAN/MATDEL/INSTALASI/
         FI-OGP GOLIVE/GOLIVE + GRAND TOTAL) PER REGION & BRANCH, FILTER
         REGION/BRANCH/PROGRAM --}}
    {{--
        Revisi (permintaan user): judul diganti dari "Rekap Status Progress
        LOP" jadi "Reporting Deployment". Ditambahkan filter Region/Branch/
        Program (client-side, Alpine -- data mentahnya "cube" pre-agregat
        per kombinasi region+branch+program, `stageCube`, dikirim dari
        DashboardPmController::buildIndexData(), dijumlahkan ulang di
        browser tiap filter berubah, TANPA round-trip ke server). Ditambah
        kolom "Grand Total" (jumlah semua kolom status) setelah kolom Golive
        di tiap baris, dan 1 baris footer "Grand Total" yang menjumlahkan
        seluruh baris yang lagi ditampilkan (mengikuti filter aktif).
        Semua angka (termasuk Grand Total) tetap bisa diklik -> modal
        matrixDetailModal() (type 'stage_breakdown', ikut kirim
        program_filter kalau filter Program lagi aktif -- lihat
        DashboardPmController::matrixDetail()).
    --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/50">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-gray-800 dark:text-gray-200">Reporting Deployment</h2>
                    <p class="text-xs text-gray-400 mt-1">Klik nama Region untuk detail per Branch. Klik angka untuk melihat daftar LOP.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select x-model="stageFilterRegion"
                            @change="if (!stageBranchOptions().includes(stageFilterBranch)) stageFilterBranch = ''"
                            class="text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Region</option>
                        <template x-for="r in stageRegions()" :key="'region-'+r">
                            <option :value="r" x-text="r"></option>
                        </template>
                    </select>

                    <select x-model="stageFilterBranch"
                            class="text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Branch</option>
                        <template x-for="b in stageBranchOptions()" :key="'branch-'+b">
                            <option :value="b" x-text="b"></option>
                        </template>
                    </select>

                    <select x-model="stageFilterProgram"
                            class="text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Program</option>
                        <template x-for="p in stagePrograms()" :key="'program-'+p">
                            <option :value="p" x-text="p === 'EKSBIS' ? 'Eksbis' : p"></option>
                        </template>
                    </select>

                    <button type="button" @click="stageResetFilters()"
                            x-show="stageFilterRegion || stageFilterBranch || stageFilterProgram"
                            class="text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 px-2 py-2">
                        Reset
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gray-100/60 dark:bg-gray-950/60 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-6 py-3 text-left whitespace-nowrap">Breakdown (Region / Branch)</th>
                        <th class="px-3 py-3 text-center text-rose-600 dark:text-rose-400 whitespace-nowrap">Drop</th>
                        <th class="px-3 py-3 text-center text-orange-600 dark:text-orange-400 whitespace-nowrap">Hold</th>
                        <th class="px-3 py-3 text-center text-slate-500 dark:text-slate-400 whitespace-nowrap">Preparing</th>
                        <th class="px-3 py-3 text-center text-amber-600 dark:text-amber-400 whitespace-nowrap">Perizinan</th>
                        <th class="px-3 py-3 text-center text-indigo-600 dark:text-indigo-400 whitespace-nowrap">Matdel</th>
                        <th class="px-3 py-3 text-center text-blue-600 dark:text-blue-400 whitespace-nowrap">Instalasi</th>
                        <th class="px-3 py-3 text-center text-purple-600 dark:text-purple-400 whitespace-nowrap">FI-OGP Golive</th>
                        <th class="px-3 py-3 text-center text-emerald-600 dark:text-emerald-400 whitespace-nowrap">Golive</th>
                        <th class="px-6 py-3 text-center text-gray-700 dark:text-gray-300 whitespace-nowrap">Grand Total</th>
                    </tr>
                </thead>
                <template x-for="reg in stageGroupedRows()" :key="reg.region">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr class="cursor-pointer bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition" @click="stageToggle(reg.region)">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-5 h-5 flex items-center justify-center rounded bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200" :class="stageExpanded[reg.region] ? 'rotate-90' : ''"><path d="m9 18 6-6-6-6"/></svg>
                                        </div>
                                        <span class="font-black text-gray-800 dark:text-gray-200 text-sm whitespace-nowrap" x-text="reg.region"></span>
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 font-black hover:bg-rose-100" @click.stop="stageShow(reg.region, '', 'drop')" x-text="reg.drop"></span></td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-orange-50 dark:bg-orange-950/50 text-orange-700 dark:text-orange-400 font-black hover:bg-orange-100" @click.stop="stageShow(reg.region, '', 'hold')" x-text="reg.hold"></span></td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-slate-50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 font-black hover:bg-slate-100" @click.stop="stageShow(reg.region, '', 'preparing')" x-text="reg.preparing"></span></td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 font-black hover:bg-amber-100" @click.stop="stageShow(reg.region, '', 'perizinan')" x-text="reg.perizinan"></span></td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 font-black hover:bg-indigo-100" @click.stop="stageShow(reg.region, '', 'matdel')" x-text="reg.matdel"></span></td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 font-black hover:bg-blue-100" @click.stop="stageShow(reg.region, '', 'instalasi')" x-text="reg.instalasi"></span></td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-400 font-black hover:bg-purple-100" @click.stop="stageShow(reg.region, '', 'fi_ogp_golive')" x-text="reg.fi_ogp_golive"></span></td>
                                <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 font-black hover:bg-emerald-100" @click.stop="stageShow(reg.region, '', 'golive')" x-text="reg.golive"></span></td>
                                <td class="px-6 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-black hover:bg-gray-200" @click.stop="stageShow(reg.region, '', 'total')" x-text="reg.total"></span></td>
                            </tr>

                            <template x-for="br in reg.branches" :key="reg.region + '-' + br.name">
                                <tr class="bg-gray-50/50 dark:bg-gray-950/50 hover:bg-gray-100/50 transition" x-show="stageExpanded[reg.region]">
                                    <td class="px-6 py-3 pl-[3.25rem]">
                                        <span class="font-bold text-gray-600 dark:text-gray-400 whitespace-nowrap" x-text="'• ' + br.name"></span>
                                    </td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-rose-600 dark:text-rose-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'drop')" x-text="br.drop"></span></td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-orange-600 dark:text-orange-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'hold')" x-text="br.hold"></span></td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-slate-600 dark:text-slate-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'preparing')" x-text="br.preparing"></span></td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-amber-600 dark:text-amber-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'perizinan')" x-text="br.perizinan"></span></td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-indigo-600 dark:text-indigo-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'matdel')" x-text="br.matdel"></span></td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-blue-600 dark:text-blue-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'instalasi')" x-text="br.instalasi"></span></td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-purple-600 dark:text-purple-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'fi_ogp_golive')" x-text="br.fi_ogp_golive"></span></td>
                                    <td class="px-3 py-3 text-center"><span class="cursor-pointer text-emerald-600 dark:text-emerald-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'golive')" x-text="br.golive"></span></td>
                                    <td class="px-6 py-3 text-center"><span class="cursor-pointer text-gray-700 dark:text-gray-300 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'total')" x-text="br.total"></span></td>
                                </tr>
                            </template>
                    </tbody>
                </template>

                <tbody x-show="stageGroupedRows().length === 0">
                    <tr>
                        <td colspan="10" class="px-6 py-10 text-center text-gray-400 font-medium">Tidak ada data statistik tersedia.</td>
                    </tr>
                </tbody>
                <tfoot x-show="stageGroupedRows().length > 0">
                    <tr class="bg-gray-100/80 dark:bg-gray-950/80 border-t-2 border-gray-300 dark:border-gray-700">
                        <td class="px-6 py-4 font-black text-gray-900 dark:text-white uppercase text-xs tracking-wide">Grand Total</td>
                        <td class="px-3 py-4 text-center font-black text-rose-700 dark:text-rose-400" x-text="stageGrandTotal().drop"></td>
                        <td class="px-3 py-4 text-center font-black text-orange-700 dark:text-orange-400" x-text="stageGrandTotal().hold"></td>
                        <td class="px-3 py-4 text-center font-black text-slate-700 dark:text-slate-300" x-text="stageGrandTotal().preparing"></td>
                        <td class="px-3 py-4 text-center font-black text-amber-700 dark:text-amber-400" x-text="stageGrandTotal().perizinan"></td>
                        <td class="px-3 py-4 text-center font-black text-indigo-700 dark:text-indigo-400" x-text="stageGrandTotal().matdel"></td>
                        <td class="px-3 py-4 text-center font-black text-blue-700 dark:text-blue-400" x-text="stageGrandTotal().instalasi"></td>
                        <td class="px-3 py-4 text-center font-black text-purple-700 dark:text-purple-400" x-text="stageGrandTotal().fi_ogp_golive"></td>
                        <td class="px-3 py-4 text-center font-black text-emerald-700 dark:text-emerald-400" x-text="stageGrandTotal().golive"></td>
                        <td class="px-6 py-4 text-center font-black text-gray-900 dark:text-white" x-text="stageGrandTotal().total"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- TABEL REKAP ASSIGNMENT & STATUS PROJECT REGULAR (KLIK ANGKA UNTUK DETAIL) --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/50">
            <h2 class="text-sm font-black uppercase tracking-wider text-gray-800 dark:text-gray-200">Rekap Assignment &amp; Status Project PT 3</h2>
            <p class="text-xs text-gray-400 mt-1">Klik nama Region untuk detail per Branch. Klik angka untuk melihat daftar LOP.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gray-100/60 dark:bg-gray-950/60 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-6 py-3 text-left">Breakdown (Region / Branch)</th>
                        <th class="px-3 py-3 text-center">Total LOP</th>
                        <th class="px-3 py-3 text-center">Assign</th>
                        <th class="px-3 py-3 text-center">Blm Assign</th>
                        <th class="px-3 py-3 text-center">In Review</th>
                        <th class="px-3 py-3 text-center">Complete (Done)</th>
                        <th class="px-6 py-3 text-right">Progress Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($statsByRegion ?? [] as $i => $reg)
                        <tr class="cursor-pointer bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition" onclick="toggleRegion('pm-region-{{ $i }}', 'pm-icon-{{ $i }}')">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-5 h-5 flex items-center justify-center rounded bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                                        <svg id="pm-icon-{{ $i }}" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                    <span class="font-black text-gray-800 dark:text-gray-200 text-sm">{{ $reg['region'] }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-4 text-center font-black text-gray-700 dark:text-gray-300 text-sm">
                                <span class="cursor-pointer hover:underline decoration-2 underline-offset-2" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'total'})">{{ $reg['total'] }}</span>
                            </td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 font-black hover:bg-blue-100" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'assigned'})">{{ $reg['assigned'] }}</span></td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 font-black hover:bg-rose-100" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'unassigned'})">{{ $reg['total'] - $reg['assigned'] }}</span></td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 font-black hover:bg-amber-100" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'waiting'})">{{ $reg['waiting'] }}</span></td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 font-black hover:bg-emerald-100" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'', metric:'completed'})">{{ $reg['completed'] }}</span></td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <div class="w-24 h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $reg['percent'] }}%"></div>
                                    </div>
                                    <span class="font-black text-gray-800 dark:text-gray-200">{{ $reg['percent'] }}%</span>
                                </div>
                            </td>
                        </tr>

                        @foreach($reg['branches'] as $br)
                            <tr class="hidden bg-gray-50/50 dark:bg-gray-950/50 hover:bg-gray-100/50 transition pm-region-{{ $i }}">
                                <td class="px-6 py-3 pl-[3.25rem]">
                                    <span class="font-bold text-gray-600 dark:text-gray-400">&bull; {{ $br['name'] }}</span>
                                </td>
                                <td class="px-3 py-3 text-center font-bold text-gray-600 dark:text-gray-400">
                                    <span class="cursor-pointer hover:underline decoration-2 underline-offset-2" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', metric:'total'})">{{ $br['total'] }}</span>
                                </td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-blue-600 dark:text-blue-400 font-bold hover:underline" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', metric:'assigned'})">{{ $br['assigned'] }}</span></td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-rose-600 dark:text-rose-400 font-bold hover:underline" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', metric:'unassigned'})">{{ $br['total'] - $br['assigned'] }}</span></td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-amber-600 dark:text-amber-400 font-bold hover:underline" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', metric:'waiting'})">{{ $br['waiting'] }}</span></td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-emerald-600 dark:text-emerald-400 font-bold hover:underline" @click.stop="show({type:'assignment', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', metric:'completed'})">{{ $br['completed'] }}</span></td>
                                <td class="px-6 py-3 text-right font-black text-gray-500 dark:text-gray-400">{{ $br['percent'] }}%</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-400 font-medium">Tidak ada data statistik tersedia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MATRIX PROGRESS PROJECT REGULAR --}}
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/50">
            <h2 class="text-sm font-black uppercase tracking-wider text-gray-800 dark:text-gray-200">Matriks Progress Project PT 3</h2>
            <p class="text-xs text-gray-400 mt-1">Program PT 3: OSP, OLO, HEM, NODE B, EKSBIS.</p>
        </div>
        <div class="overflow-x-auto pb-4">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gray-100/60 dark:bg-gray-950/60 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th rowspan="2" class="px-6 py-3 text-left border-r border-gray-200/60 dark:border-gray-800 align-middle whitespace-nowrap sticky left-0 bg-gray-100/90 dark:bg-gray-950/90 backdrop-blur-sm z-10">
                            Wilayah (Region / Branch)
                        </th>
                        @foreach($regularPrograms as $prog)
                            <th colspan="4" class="px-3 py-2 text-center border-b border-r border-gray-200/60 dark:border-gray-800 whitespace-nowrap">{{ strtoupper(trim($prog)) === 'EKSBIS' ? 'Eksbis' : $prog }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($regularPrograms as $prog)
                            <th class="px-3 py-2 text-center text-blue-600 bg-blue-50/50 dark:bg-blue-950/20">Prepare</th>
                            <th class="px-3 py-2 text-center text-amber-600 bg-amber-50/50 dark:bg-amber-950/20">Progress</th>
                            <th class="px-3 py-2 text-center text-emerald-600 bg-emerald-50/50 dark:bg-emerald-950/20">Finish</th>
                            <th class="px-3 py-2 text-center text-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 border-r border-gray-200/60 dark:border-gray-800">% Done</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($matrixData ?? [] as $i => $reg)
                        <tr class="cursor-pointer bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition group" onclick="toggleRegion('pm-matrix-reg-{{ $i }}', 'pm-icon-matrix-{{ $i }}')">
                            <td class="px-6 py-4 border-r border-gray-200/60 dark:border-gray-800 sticky left-0 bg-white dark:bg-gray-900 group-hover:bg-gray-50 dark:group-hover:bg-gray-800/50 z-10">
                                <div class="flex items-center gap-3">
                                    <div class="w-5 h-5 flex items-center justify-center rounded bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                                        <svg id="pm-icon-matrix-{{ $i }}" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                    <span class="font-black text-gray-800 dark:text-gray-200 text-sm whitespace-nowrap">{{ $reg['region'] }}</span>
                                </div>
                            </td>

                            @foreach($regularPrograms as $prog)
                                @php
                                    $stats = $reg['programs'][$prog] ?? ['preparation' => 0, 'instalasi' => 0, 'finishing' => 0];
                                    $totalProyek = $stats['preparation'] + $stats['instalasi'] + $stats['finishing'];
                                    $persentase = $totalProyek > 0 ? round(($stats['finishing'] / $totalProyek) * 100) : 0;
                                @endphp
                                <td class="px-3 py-4 text-center font-bold text-gray-700 dark:text-gray-300 bg-blue-50/20 dark:bg-blue-950/10"><span class="cursor-pointer hover:underline decoration-2 underline-offset-2" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'', program:'{{ $prog }}', metric:'preparation'})">{{ $stats['preparation'] ?: '-' }}</span></td>
                                <td class="px-3 py-4 text-center font-bold text-gray-700 dark:text-gray-300 bg-amber-50/20 dark:bg-amber-950/10"><span class="cursor-pointer hover:underline decoration-2 underline-offset-2" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'', program:'{{ $prog }}', metric:'instalasi'})">{{ $stats['instalasi'] ?: '-' }}</span></td>
                                <td class="px-3 py-4 text-center font-bold text-gray-700 dark:text-gray-300 bg-emerald-50/20 dark:bg-emerald-950/10"><span class="cursor-pointer hover:underline decoration-2 underline-offset-2" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'', program:'{{ $prog }}', metric:'finishing'})">{{ $stats['finishing'] ?: '-' }}</span></td>
                                <td class="px-3 py-4 text-center font-black text-indigo-700 dark:text-indigo-400 bg-indigo-50/20 dark:bg-indigo-950/10 border-r border-gray-200/60 dark:border-gray-800">{{ $persentase }}%</td>
                            @endforeach
                        </tr>

                        @foreach($reg['branches'] as $br)
                            <tr class="hidden bg-gray-50/50 dark:bg-gray-950/50 hover:bg-gray-100/50 transition pm-matrix-reg-{{ $i }}">
                                <td class="px-6 py-3 pl-[3.25rem] border-r border-gray-200/60 dark:border-gray-800 sticky left-0 bg-gray-50/90 dark:bg-gray-950/90 z-10">
                                    <span class="font-bold text-gray-600 dark:text-gray-400 whitespace-nowrap">&bull; {{ $br['name'] }}</span>
                                </td>

                                @foreach($regularPrograms as $prog)
                                    @php
                                        $stats = $br['programs'][$prog] ?? ['preparation' => 0, 'instalasi' => 0, 'finishing' => 0];
                                        $totalProyek = $stats['preparation'] + $stats['instalasi'] + $stats['finishing'];
                                        $persentase = $totalProyek > 0 ? round(($stats['finishing'] / $totalProyek) * 100) : 0;
                                    @endphp
                                    <td class="px-3 py-3 text-center text-blue-600 dark:text-blue-400 font-semibold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', program:'{{ $prog }}', metric:'preparation'})">{{ $stats['preparation'] ?: '-' }}</span></td>
                                    <td class="px-3 py-3 text-center text-amber-600 dark:text-amber-400 font-semibold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', program:'{{ $prog }}', metric:'instalasi'})">{{ $stats['instalasi'] ?: '-' }}</span></td>
                                    <td class="px-3 py-3 text-center text-emerald-600 dark:text-emerald-400 font-semibold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'regular', region:'{{ $reg['region'] }}', branch:'{{ $br['name'] }}', program:'{{ $prog }}', metric:'finishing'})">{{ $stats['finishing'] ?: '-' }}</span></td>
                                    <td class="px-3 py-3 text-center text-indigo-600 dark:text-indigo-400 font-black border-r border-gray-200/60 dark:border-gray-800">{{ $persentase }}%</td>
                                @endforeach
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ 1 + ($regularPrograms->count() * 4) }}" class="px-6 py-10 text-center text-gray-400 font-medium">
                                Tidak ada data project terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MATRIX PROGRESS PROJECT PT 2 --}}
    <div class="bg-white dark:bg-gray-900 border border-indigo-200 dark:border-indigo-900 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-5 border-b border-indigo-100 dark:border-indigo-900 bg-indigo-50/50 dark:bg-indigo-950/20">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-indigo-900 dark:text-indigo-300">Matriks Progress Project PT 2</h2>
                    <p class="text-xs text-gray-400 mt-1">Sumber khusus PT 2. Tidak ter-filter Program PT 3.</p>
                </div>
                <span class="px-3 py-1.5 rounded-xl bg-indigo-100 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 text-[10px] font-black shrink-0">PT 2</span>
            </div>
        </div>
        <div class="overflow-x-auto pb-4">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-indigo-50/50 dark:bg-indigo-950/20 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-6 py-4 text-left border-r border-indigo-100 dark:border-indigo-900 whitespace-nowrap">Region / Branch</th>
                        <th class="px-4 py-4 text-center text-blue-600">Preparation</th>
                        <th class="px-4 py-4 text-center text-amber-600">Instalasi</th>
                        <th class="px-4 py-4 text-center text-emerald-600">Finishing / Go-Live</th>
                        <th class="px-4 py-4 text-center text-indigo-700">Total</th>
                        <th class="px-6 py-4 text-right">% Finish</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-indigo-100/70 dark:divide-indigo-900/50">
                    @forelse($matrixPt2Data ?? [] as $i => $reg)
                        @php
                            $regionStats = $reg['stats'] ?? ['preparation' => 0, 'instalasi' => 0, 'finishing' => 0, 'total' => 0, 'percent' => 0];
                            $regionSlug = \Illuminate\Support\Str::slug($reg['region']);
                        @endphp
                        <tr class="cursor-pointer bg-white dark:bg-gray-900 hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 transition" onclick="toggleRegion('pm-matrix-pt2-{{ $regionSlug }}', 'pm-icon-pt2-{{ $regionSlug }}')">
                            <td class="px-6 py-4 border-r border-indigo-100 dark:border-indigo-900">
                                <div class="flex items-center gap-3">
                                    <div class="w-5 h-5 flex items-center justify-center rounded bg-indigo-100 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                                        <svg id="pm-icon-pt2-{{ $regionSlug }}" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                    <span class="font-black text-gray-800 dark:text-gray-200">{{ $reg['region'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center font-black text-blue-700 dark:text-blue-400"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'', metric:'preparation'})">{{ $regionStats['preparation'] ?? 0 }}</span></td>
                            <td class="px-4 py-4 text-center font-black text-amber-700 dark:text-amber-400"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'', metric:'instalasi'})">{{ $regionStats['instalasi'] ?? 0 }}</span></td>
                            <td class="px-4 py-4 text-center font-black text-emerald-700 dark:text-emerald-400"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'', metric:'finishing'})">{{ $regionStats['finishing'] ?? 0 }}</span></td>
                            <td class="px-4 py-4 text-center font-black text-indigo-700 dark:text-indigo-400"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'', metric:'total'})">{{ $regionStats['total'] ?? 0 }}</span></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-3">
                                    <div class="w-24 h-2 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: {{ min($regionStats['percent'] ?? 0, 100) }}%"></div>
                                    </div>
                                    <span class="font-black text-indigo-700 dark:text-indigo-400">{{ $regionStats['percent'] ?? 0 }}%</span>
                                </div>
                            </td>
                        </tr>

                        @foreach($reg['branches'] ?? [] as $branch)
                            @php
                                $branchStats = $branch['stats'] ?? ['preparation' => 0, 'instalasi' => 0, 'finishing' => 0, 'total' => 0, 'percent' => 0];
                            @endphp
                            <tr class="hidden bg-gray-50/70 dark:bg-gray-950/50 pm-matrix-pt2-{{ $regionSlug }}">
                                <td class="px-6 py-3 pl-[3.25rem] border-r border-indigo-100 dark:border-indigo-900">
                                    <span class="font-bold text-gray-600 dark:text-gray-400">&bull; {{ $branch['name'] }}</span>
                                </td>
                                <td class="px-4 py-3 text-center text-blue-600 dark:text-blue-400 font-bold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'{{ $branch['name'] }}', metric:'preparation'})">{{ $branchStats['preparation'] ?? 0 }}</span></td>
                                <td class="px-4 py-3 text-center text-amber-600 dark:text-amber-400 font-bold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'{{ $branch['name'] }}', metric:'instalasi'})">{{ $branchStats['instalasi'] ?? 0 }}</span></td>
                                <td class="px-4 py-3 text-center text-emerald-600 dark:text-emerald-400 font-bold"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'{{ $branch['name'] }}', metric:'finishing'})">{{ $branchStats['finishing'] ?? 0 }}</span></td>
                                <td class="px-4 py-3 text-center text-indigo-600 dark:text-indigo-400 font-black"><span class="cursor-pointer hover:underline" @click.stop="show({type:'pt2', region:'{{ $reg['region'] }}', branch:'{{ $branch['name'] }}', metric:'total'})">{{ $branchStats['total'] ?? 0 }}</span></td>
                                <td class="px-6 py-3 text-right text-indigo-600 dark:text-indigo-400 font-black">{{ $branchStats['percent'] ?? 0 }}%</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-400 font-medium">Tidak ada data PT 2 tersedia.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL DETAIL LOP (KLIK ANGKA PADA TABEL MATRIX) --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="close()"></div>

        <div class="relative bg-white dark:bg-gray-900 w-full max-w-4xl max-h-[85vh] rounded-[2rem] shadow-2xl flex flex-col overflow-hidden"
             x-show="open" x-transition>

            <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 flex items-start justify-between gap-4 bg-gray-50/50 dark:bg-gray-950/50">
                <div>
                    <p class="text-[10px] font-black text-blue-700 dark:text-blue-400 uppercase tracking-widest">Detail LOP</p>
                    <h3 class="text-sm md:text-base font-black text-gray-900 dark:text-white mt-1" x-text="title"></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        <span x-text="count"></span> LOP ditemukan
                    </p>
                </div>
                <button type="button" @click="close()"
                        class="w-9 h-9 shrink-0 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-500 hover:bg-gray-100 hover:text-gray-700 flex items-center justify-center transition">
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
                    <div class="px-6 py-10 text-center text-sm text-gray-400 font-medium">Tidak ada LOP untuk kategori ini.</div>
                </template>

                <template x-if="!loading && !error && rows.length > 0">
                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-gray-100/60 dark:bg-gray-950/60 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider text-[10px] sticky top-0">
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
                        <tbody class="divide-y divide-gray-200/60 dark:divide-gray-800">
                            <template x-for="row in rows" :key="row.no">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 font-semibold" x-text="row.no"></td>
                                    <td class="px-4 py-3 font-bold text-gray-700 dark:text-gray-300" x-text="row.pid"></td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-gray-800 dark:text-gray-200" x-text="row.project_name"></p>
                                        <p class="text-gray-500 dark:text-gray-400" x-text="row.lop_name"></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-gray-700 dark:text-gray-300" x-text="row.branch"></p>
                                        <p class="text-gray-400" x-text="row.sto"></p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400 font-semibold" x-text="row.program"></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 font-black text-[10px]" x-text="row.status_label"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a :href="row.detail_url" target="_blank"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-gray-900 text-white text-[10px] font-bold hover:bg-gray-700 transition">
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

<script>
    function matrixDetailModal() {
        return {
            open: false,
            loading: false,
            error: '',
            title: '',
            rows: [],
            count: 0,

            // Revisi (permintaan user): tabel "Reporting Deployment" (dulu
            // "Rekap Status Progress LOP") -- data mentah "cube" pre-agregat
            // per kombinasi (region, branch, program) dari
            // DashboardPmController::buildIndexData() ($stageCube), filter
            // Region/Branch/Program dihitung ulang di sini (client-side,
            // tanpa round-trip server) tiap kali filter berubah.
            stageCube: @json($stageCube ?? []),
            stageFilterRegion: '',
            stageFilterBranch: '',
            stageFilterProgram: '',
            stageExpanded: {},

            stageRegions() {
                return [...new Set(this.stageCube.map((r) => r.region))].sort();
            },

            stageBranchOptions() {
                return [...new Set(
                    this.stageCube
                        .filter((r) => !this.stageFilterRegion || r.region === this.stageFilterRegion)
                        .map((r) => r.branch)
                )].sort();
            },

            stagePrograms() {
                return [...new Set(this.stageCube.map((r) => r.program))].sort();
            },

            stageResetFilters() {
                this.stageFilterRegion = '';
                this.stageFilterBranch = '';
                this.stageFilterProgram = '';
            },

            stageFilteredCube() {
                return this.stageCube.filter((r) =>
                    (!this.stageFilterRegion || r.region === this.stageFilterRegion) &&
                    (!this.stageFilterBranch || r.branch === this.stageFilterBranch) &&
                    (!this.stageFilterProgram || r.program === this.stageFilterProgram)
                );
            },

            stageGroupedRows() {
                const keys = ['drop', 'hold', 'preparing', 'perizinan', 'matdel', 'instalasi', 'fi_ogp_golive', 'golive'];
                const regionsMap = {};

                for (const row of this.stageFilteredCube()) {
                    if (!regionsMap[row.region]) {
                        const base = { region: row.region, total: 0, branches: {} };
                        keys.forEach((k) => { base[k] = 0; });
                        regionsMap[row.region] = base;
                    }
                    const rg = regionsMap[row.region];
                    keys.forEach((k) => { rg[k] += row[k]; });
                    rg.total += row.total;

                    if (!rg.branches[row.branch]) {
                        const b = { name: row.branch, total: 0 };
                        keys.forEach((k) => { b[k] = 0; });
                        rg.branches[row.branch] = b;
                    }
                    const br = rg.branches[row.branch];
                    keys.forEach((k) => { br[k] += row[k]; });
                    br.total += row.total;
                }

                return Object.values(regionsMap)
                    .map((rg) => ({ ...rg, branches: Object.values(rg.branches).sort((a, b) => a.name.localeCompare(b.name)) }))
                    .sort((a, b) => a.region.localeCompare(b.region));
            },

            stageGrandTotal() {
                const keys = ['drop', 'hold', 'preparing', 'perizinan', 'matdel', 'instalasi', 'fi_ogp_golive', 'golive'];
                const gt = { total: 0 };
                keys.forEach((k) => { gt[k] = 0; });

                for (const row of this.stageFilteredCube()) {
                    keys.forEach((k) => { gt[k] += row[k]; });
                    gt.total += row.total;
                }

                return gt;
            },

            stageToggle(region) {
                this.stageExpanded[region] = !this.stageExpanded[region];
            },

            stageShow(region, branch, metric) {
                this.show({
                    type: 'stage_breakdown',
                    region: region || '',
                    branch: branch || '',
                    metric: metric,
                    program_filter: this.stageFilterProgram || '',
                });
            },

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
                    program_filter: params.program_filter || '',
                    metric: params.metric || '',
                }).toString();

                try {
                    const res = await fetch(`{{ route('pm.dashboard.matrix-detail') }}?${query}`, {
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
</script>
@endsection