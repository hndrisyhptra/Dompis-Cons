@extends('layouts.admin')

@section('content')
@php
    $project = $lop->project;
    $inputs = $curveData['inputs'];
    $target = $curveData['target'];
    $latestRealization = $curveData['realization']->last();
    $formatDate = fn ($date) => $date?->format('d M Y') ?? '-';
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span class="rounded-lg bg-indigo-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">Kurva-S PT3</span>
                    <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-[10px] font-black text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $project?->program ?? '-' }}</span>
                </div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">Kurva-S Target &amp; Realisasi</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $lop->lop_name }} · PID {{ $project?->pid ?? '-' }} · {{ $lop->branch ?? '-' }} / {{ $lop->sto ?? '-' }}
                </p>
            </div>
            <a href="{{ url()->previous() }}"
               class="inline-flex shrink-0 items-center justify-center rounded-lg bg-gray-100 px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                ← Kembali
            </a>
        </div>
    </section>

    @if($curveData['warnings']->isNotEmpty())
        <section class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
            <p class="text-sm font-black text-amber-800 dark:text-amber-300">Catatan kualitas data</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-amber-700 dark:text-amber-400">
                @foreach($curveData['warnings'] as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="grid grid-cols-2 gap-3 lg:grid-cols-6">
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs text-gray-500">Start Date</p>
            <p class="mt-1 font-black text-gray-900 dark:text-white">{{ $formatDate($inputs['start_date']) }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs text-gray-500">Perizinan</p>
            <p class="mt-1 font-black text-gray-900 dark:text-white">{{ $inputs['permit_days'] !== null ? $inputs['permit_days'].' hari' : '-' }}</p>
            <p class="mt-1 truncate text-[10px] text-gray-400" title="{{ $inputs['permit_category'] }}">{{ $inputs['permit_category'] ?? 'Belum dipilih' }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs text-gray-500">Material Delivery</p>
            <p class="mt-1 font-black text-gray-900 dark:text-white">{{ $inputs['delivery_days'] !== null ? $inputs['delivery_days'].' hari' : '-' }}</p>
            <p class="mt-1 text-[10px] text-gray-400">{{ $inputs['delivery_rule'] }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs text-gray-500">Instalasi</p>
            <p class="mt-1 font-black text-gray-900 dark:text-white">{{ $inputs['installation_days'] }} hari</p>
            <p class="mt-1 text-[10px] text-gray-400">{{ $inputs['boq_source'] }}</p>
        </div>
        <div class="rounded-lg border border-blue-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs text-blue-700 dark:text-blue-300">Total Bobot Waktu</p>
            <p class="mt-1 font-black text-blue-700 dark:text-blue-300">{{ $inputs['total_days'] !== null ? $inputs['total_days'].' hari' : '-' }}</p>
            <p class="mt-1 text-[10px] text-blue-500">Hari kalender</p>
        </div>
        <div class="rounded-lg border border-emerald-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs text-emerald-700 dark:text-emerald-300">Realisasi Terakhir</p>
            <p class="mt-1 font-black text-emerald-700 dark:text-emerald-300">{{ $latestRealization ? number_format($latestRealization['progress'], 1).'%' : '-' }}</p>
            <p class="mt-1 text-[10px] text-emerald-600">{{ $latestRealization['date'] ?? 'Belum ada aktivitas terukur' }}</p>
        </div>
    </section>

    @if($curveData['ready'])
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-base font-black text-gray-900 dark:text-white">Grafik Kurva-S per LOP</h2>
                    <p class="mt-0.5 text-xs text-gray-400">Bobot dihitung proporsional terhadap akumulasi durasi setiap tahap.</p>
                </div>
                <div class="flex items-center gap-4 text-xs font-bold">
                    <span class="inline-flex items-center gap-1.5 text-blue-700 dark:text-blue-300"><span class="h-2.5 w-2.5 rounded-lg bg-blue-600"></span>Target</span>
                    <span class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-300"><span class="h-2.5 w-2.5 rounded-lg bg-emerald-500"></span>Realisasi</span>
                </div>
            </div>
            <div class="h-[380px] w-full">
                <canvas id="lopSCurveChart"></canvas>
            </div>
            @if(! $target['fi_completed_at'])
                <p class="mt-4 rounded-lg bg-purple-50 px-3 py-2 text-xs font-semibold text-purple-700 dark:bg-purple-950/30 dark:text-purple-300">
                    Target Golive belum dibentuk. Periode 3 hari baru dimulai setelah Capture Valins, PDF ABD &amp; Valid4, File KML, dan Mancore lengkap.
                </p>
            @endif
        </section>

        <section class="grid gap-6 xl:grid-cols-5">
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 xl:col-span-3">
                <h2 class="text-base font-black text-gray-900 dark:text-white">Target dan Realisasi Milestone</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[620px] text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 text-left text-xs uppercase tracking-wide text-gray-400 dark:border-gray-800">
                                <th class="py-2 pr-3">Milestone</th>
                                <th class="px-3 py-2">Target</th>
                                <th class="px-3 py-2">Realisasi</th>
                                <th class="py-2 pl-3 text-right">Deviasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($curveData['milestones'] as $milestone)
                                <tr class="border-b border-gray-50 dark:border-gray-800/60">
                                    <td class="py-3 pr-3 font-bold text-gray-700 dark:text-gray-200">{{ $milestone['label'] }}</td>
                                    <td class="px-3 py-3 text-gray-500">{{ $formatDate($milestone['target']) }}</td>
                                    <td class="px-3 py-3 text-gray-500">{{ $formatDate($milestone['actual']) }}</td>
                                    <td class="py-3 pl-3 text-right">
                                        @if($milestone['variance_days'] === null)
                                            <span class="text-gray-400">-</span>
                                        @elseif($milestone['variance_days'] > 0)
                                            <span class="font-bold text-red-600">+{{ $milestone['variance_days'] }} hari</span>
                                        @else
                                            <span class="font-bold text-emerald-600">{{ $milestone['variance_days'] }} hari</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 xl:col-span-2">
                <h2 class="text-base font-black text-gray-900 dark:text-white">Dasar Perhitungan Instalasi</h2>
                <div class="mt-4 space-y-3">
                    @foreach(['KABEL' => 1000, 'TIANG' => 10, 'GALIAN' => 100] as $category => $productivity)
                        @php
                            $volume = $inputs['volumes'][$category] ?? 0;
                            $days = (int) round($volume / $productivity, 0, PHP_ROUND_HALF_UP);
                        @endphp
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800/60">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-black text-gray-700 dark:text-gray-200">{{ $category }}</p>
                                <span class="rounded-lg bg-white px-2 py-1 text-xs font-black text-blue-700 dark:bg-gray-900 dark:text-blue-300">{{ $days }} hari</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ number_format($volume, 2, ',', '.') }} ÷ {{ number_format($productivity, 0, ',', '.') }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="mt-4 text-[11px] leading-relaxed text-gray-400">Pasangan Material/Jasa dengan pair_code yang sama dihitung satu kali. Pembulatan menggunakan half-up.</p>
            </div>
        </section>
    @else
        <section class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-lg bg-gray-100 text-2xl dark:bg-gray-800">📈</div>
            <h2 class="mt-4 text-lg font-black text-gray-900 dark:text-white">Kurva-S belum dapat dihitung</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm text-gray-500">Lengkapi Start Date, kategori Perizinan, dan pemetaan Branch/STO terlebih dahulu. Sistem tidak menggunakan tanggal fallback agar target tetap dapat diaudit.</p>
        </section>
    @endif
</div>
@endsection

@if($curveData['ready'])
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const target = @js($curveData['target']['series']);
        const realization = @js($curveData['realization']);
        const labels = [...new Set([...target, ...realization].map(point => point.date))].sort();
        const valuesFor = (points) => {
            const values = new Map(points.map(point => [point.date, point.progress]));
            return labels.map(label => values.has(label) ? values.get(label) : null);
        };

        new Chart(document.getElementById('lopSCurveChart'), {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Target',
                        data: valuesFor(target),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, .10)',
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        spanGaps: true,
                        tension: .28,
                    },
                    {
                        label: 'Realisasi',
                        data: valuesFor(realization),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, .10)',
                        borderWidth: 3,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        spanGaps: true,
                        tension: .28,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { callback: value => value + '%' },
                        title: { display: true, text: 'Progress Kumulatif' },
                    },
                    x: { title: { display: true, text: 'Tanggal' } },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.dataset.label}: ${Number(context.parsed.y).toFixed(1)}%`,
                        },
                    },
                },
            },
        });
    });
</script>
@endpush
@endif
