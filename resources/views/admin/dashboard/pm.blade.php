@extends('layouts.admin')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            Dashboard PM
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Progres Monitoring Improvement
        </p>
    </div>
    <div class="bg-white dark:bg-gray-900 px-4 py-2 rounded-xl shadow-xs border border-gray-200 dark:border-gray-800 text-xs text-gray-600 dark:text-gray-400 self-stretch sm:self-auto text-center flex items-center justify-center">
        <i class="fa-regular fa-calendar mr-2"></i> Tanggal Update: {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
    </div>
</div>

{{-- Search & Filter --}}
<div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-4 mb-6 shadow-sm">
    <form method="GET" action="{{ route('dashboard.pm.index') }}" class="space-y-4">
    

        {{-- Filter Dropdown --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            {{-- Program --}}
            <div>
                <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">
                    Program
                </label>
                <select name="program"
                        onchange="this.form.submit()"
                        class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-white cursor-pointer">
                    <option value="">Semua Program</option>
                    @foreach($programs as $program)
                        <option value="{{ $program }}" {{ request('program') == $program ? 'selected' : '' }}>
                            {{ $program }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Branch --}}
            <div>
                <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">
                    Branch
                </label>
                <select name="branch"
                        onchange="this.form.submit()"
                        class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-blue-500 focus:border-blue-500 text-gray-900 dark:text-white cursor-pointer">
                    <option value="">Semua Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch }}" {{ request('branch') == $branch ? 'selected' : '' }}>
                            {{ $branch }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

{{-- INFOGRAPHIC CARDS / WIDGET TOTALS --}}
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 rounded-lg flex items-center justify-center text-xl"><i class="fa-solid fa-layer-group"></i></div>
        <div>
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Total Segment</p>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalSegments }} <span class="text-xs font-normal text-gray-500 dark:text-gray-400">Segmen</span></h3>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 bg-green-100 dark:bg-green-950 text-green-600 dark:text-green-400 rounded-lg flex items-center justify-center text-xl"><i class="fa-solid fa-ruler-combined"></i></div>
        <div>
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Total Panjang FO</p>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalKabelPlan, 0, ',', '.') }} <span class="text-xs font-normal text-gray-500 dark:text-gray-400">m</span></h3>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400 rounded-lg flex items-center justify-center text-xl"><i class="fa-solid fa-cable-car"></i></div>
        <div>
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Aktual Penarikan FO</p>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalKabelActual, 0, ',', '.') }} <span class="text-xs font-normal text-gray-500 dark:text-gray-400">m</span></h3>
            <span class="text-xs font-bold text-amber-600 dark:text-amber-400">{{ number_format($totalKabelPersen, 2, ',', '.') }}%</span>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-950 text-purple-600 dark:text-purple-400 rounded-lg flex items-center justify-center text-xl"><i class="fa-solid fa-tower-broadcast"></i></div>
        <div>
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Total Target Tiang</p>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalTiangPlan, 0, ',', '.') }} <span class="text-xs font-normal text-gray-500 dark:text-gray-400">pcs</span></h3>
        </div>
    </div>
    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs flex items-center gap-4">
        <div class="w-12 h-12 bg-red-100 dark:bg-red-950 text-red-600 dark:text-red-400 rounded-lg flex items-center justify-center text-xl"><i class="fa-solid fa-mountain-sun"></i></div>
        <div>
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wider">Aktual Tanam Tiang</p>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalTiangActual, 0, ',', '.') }} <span class="text-xs font-normal text-gray-500 dark:text-gray-400">pcs</span></h3>
            <span class="text-xs font-bold text-red-600 dark:text-red-400">{{ number_format($totalTiangPersen, 2, ',', '.') }}%</span>
        </div>
    </div>
</section>

{{-- MAIN DATA AREA --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-xs overflow-hidden flex flex-col justify-between">
        <div class="p-4 border-b border-gray-100 dark:border-gray-800">
            <h2 class="font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wider">Progres Per Program</h2>
        </div>
        <div class="overflow-x-auto text-xs">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-900 dark:bg-black text-white text-[10px] uppercase tracking-wider">
                        <th class="p-2.5 border border-gray-700 text-center">No</th>
                        <th class="p-2.5 border border-gray-700">Branch</th>
                        <th class="p-2.5 border border-gray-700">STO</th>
                        <th class="p-2.5 border border-gray-700">Nama LOP</th>
                        <th class="p-2.5 border border-gray-700 text-right">Panjang Kabel (m)</th>
                        <th class="p-2.5 border border-gray-700 text-center" colspan="2">Aktual Penarikan FO</th>
                        <th class="p-2.5 border border-gray-700 text-right">Tiang (pcs)</th>
                        <th class="p-2.5 border border-gray-700 text-center" colspan="2">Aktual Tanam Tiang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                    @forelse($tableData as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-950 transition-colors">
                        <td class="p-2 border border-gray-200 dark:border-gray-800 text-center">{{ $row['no'] }}</td>
                        <td class="p-2 border border-gray-200 dark:border-gray-800 font-semibold text-gray-900 dark:text-white">{{ $row['branch'] }}</td>
                        <td class="p-2 border border-gray-200 dark:border-gray-800 font-mono text-gray-500 dark:text-gray-400">{{ $row['sto'] }}</td>
                        <td class="p-2 border border-gray-200 dark:border-gray-800 text-blue-600 dark:text-blue-400 font-medium">{{ $row['nama_lop'] }}</td>
                        <td class="p-2 border border-gray-200 dark:border-gray-800 text-right font-mono">{{ number_format($row['kabel_plan'], 0, ',', '.') }}</td>
                        <td class="p-2 border border-gray-200 dark:border-gray-800 text-right font-mono">{{ number_format($row['kabel_actual'], 0, ',', '.') }}</td>
                        <td class="p-2 border border-gray-200 dark:border-gray-800 text-center whitespace-nowrap">
                            <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 block">{{ number_format($row['kabel_persen'], 2, ',', '.') }}%</span>
                            <div class="w-16 bg-gray-200 dark:bg-gray-800 h-1.5 rounded-full mx-auto mt-0.5">
                                <div class="bg-amber-500 h-1.5 rounded-full" style="width: {{ min($row['kabel_persen'], 100) }}%"></div>
                            </div>
                        </td>
                        <td class="p-2 border border-gray-200 dark:border-gray-800 text-right font-mono">{{ number_format($row['tiang_plan'], 0, ',', '.') }}</td>
                        <td class="p-2 border border-gray-200 dark:border-gray-800 text-right font-mono">{{ number_format($row['tiang_actual'], 0, ',', '.') }}</td>
                        <td class="p-2 border border-gray-200 dark:border-gray-800 text-center whitespace-nowrap">
                            <span class="text-[10px] font-bold text-green-600 dark:text-green-400 block">{{ number_format($row['tiang_persen'], 2, ',', '.') }}%</span>
                            <div class="w-16 bg-gray-200 dark:bg-gray-800 h-1.5 rounded-full mx-auto mt-0.5">
                                <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ min($row['tiang_persen'], 100) }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="p-4 text-center text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-gray-800">Tidak ada data proyek yang sesuai filter.</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-100 dark:bg-gray-950 font-bold text-gray-900 dark:text-white">
                    <tr>
                        <td colspan="4" class="p-2.5 border border-gray-200 dark:border-gray-800 text-center uppercase tracking-wider">Total</td>
                        <td class="p-2.5 border border-gray-200 dark:border-gray-800 text-right font-mono">{{ number_format($totalKabelPlan, 0, ',', '.') }}</td>
                        <td class="p-2.5 border border-gray-200 dark:border-gray-800 text-right font-mono">{{ number_format($totalKabelActual, 0, ',', '.') }}</td>
                        <td class="p-2.5 border border-gray-200 dark:border-gray-800 text-center text-blue-600 dark:text-blue-400 font-mono">{{ number_format($totalKabelPersen, 2, ',', '.') }}%</td>
                        <td class="p-2.5 border border-gray-200 dark:border-gray-800 text-right font-mono">{{ number_format($totalTiangPlan, 0, ',', '.') }}</td>
                        <td class="p-2.5 border border-gray-200 dark:border-gray-800 text-right font-mono">{{ number_format($totalTiangActual, 0, ',', '.') }}</td>
                        <td class="p-2.5 border border-gray-200 dark:border-gray-800 text-center text-blue-600 dark:text-blue-400 font-mono">{{ number_format($totalTiangPersen, 2, ',', '.') }}%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-4 flex flex-col justify-between">
        <div>
            <h2 class="font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wider mb-4">Ringkasan Total Proyek</h2>
            <div class="w-40 h-40 mx-auto relative mb-4">
                <canvas id="summaryDonut"></canvas>
            </div>
        </div>
        @php 
            $totalAll = array_sum($summaryStatus);
            $pSelesai = $totalAll > 0 ? round(($summaryStatus['selesai'] / $totalAll) * 100, 2) : 0;
            $pSedang = $totalAll > 0 ? round(($summaryStatus['sedang'] / $totalAll) * 100, 2) : 0;
            $pRendah = $totalAll > 0 ? round(($summaryStatus['rendah'] / $totalAll) * 100, 2) : 0;
            $pBelum = $totalAll > 0 ? round(($summaryStatus['belum'] / $totalAll) * 100, 2) : 0;
        @endphp
        <div class="text-xs space-y-2 text-gray-700 dark:text-gray-300">
            <div class="flex justify-between items-center"><span class="text-green-600 dark:text-green-400 font-medium"><i class="fa-solid fa-square mr-1.5"></i> Selesai (&ge; 100%)</span> <span class="font-bold font-mono">{{ $summaryStatus['selesai'] }} ({{ $pSelesai }}%)</span></div>
            <div class="flex justify-between items-center"><span class="text-amber-500 dark:text-amber-400 font-medium"><i class="fa-solid fa-square mr-1.5"></i> Sedang (50 - 99%)</span> <span class="font-bold font-mono">{{ $summaryStatus['sedang'] }} ({{ $pSedang }}%)</span></div>
            <div class="flex justify-between items-center"><span class="text-orange-400 dark:text-orange-300 font-medium"><i class="fa-solid fa-square mr-1.5"></i> Rendah (1 - 49%)</span> <span class="font-bold font-mono">{{ $summaryStatus['rendah'] }} ({{ $pRendah }}%)</span></div>
            <div class="flex justify-between items-center"><span class="text-red-500 dark:text-red-400 font-medium"><i class="fa-solid fa-square mr-1.5"></i> Belum Dikerjakan</span> <span class="font-bold font-mono">{{ $summaryStatus['belum'] }} ({{ $pBelum }}%)</span></div>
            <hr class="border-gray-100 dark:border-gray-800 my-2">
            <div class="flex justify-between items-center font-bold text-sm text-gray-900 dark:text-white"><span>TOTAL</span> <span>{{ $totalSegments }} Segmen</span></div>
        </div>
    </div>
</div>

{{-- BOTTOM GAUGES & GRAPHS --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 flex flex-col items-center justify-center">
        <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Progres Penarikan FO</h4>
        <div class="w-36 h-24 relative">
            <canvas id="gaugeFO"></canvas>
            <div class="absolute bottom-0 inset-x-0 text-center">
                <span class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalKabelPersen, 2, ',', '.') }}%</span>
            </div>
        </div>
        <p class="text-[11px] text-gray-400 dark:text-gray-500 font-mono mt-2">{{ number_format($totalKabelActual, 0, ',', '.') }} m / {{ number_format($totalKabelPlan, 0, ',', '.') }} m</p>
    </div>

    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 flex flex-col items-center justify-center">
        <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Progres Tanam Tiang</h4>
        <div class="w-36 h-24 relative">
            <canvas id="gaugeTiang"></canvas>
            <div class="absolute bottom-0 inset-x-0 text-center">
                <span class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($totalTiangPersen, 2, ',', '.') }}%</span>
            </div>
        </div>
        <p class="text-[11px] text-gray-400 dark:text-gray-500 font-mono mt-2">{{ number_format($totalTiangActual, 0, ',', '.') }} pcs / {{ number_format($totalTiangPlan, 0, ',', '.') }} pcs</p>
    </div>

    <div class="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800">
        <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">Grafik Progres Per Bulan</h4>
        <div class="h-32">
            <canvas id="lineMonthly"></canvas>
        </div>
    </div>
</div>

{{-- SCRIPT INTEGRATION FOR CHARTS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 1. Donut Chart - Ringkasan Proyek
    new Chart(document.getElementById('summaryDonut'), {
        type: 'doughnut',
        data: {
            labels: ['Selesai', 'Sedang', 'Rendah', 'Belum Dikerjakan'],
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
        options: { cutout: '75%', plugins: { legend: { display: false } } }
    });

    // 2. Gauge Chart - Penarikan FO
    let kabelPersen = {{ min($totalKabelPersen, 100) }};
    new Chart(document.getElementById('gaugeFO'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [kabelPersen, 100 - kabelPersen],
                backgroundColor: ['#F59E0B', '#E5E7EB'],
                borderWidth: 0
            }]
        },
        options: { rotation: -90, circumference: 180, cutout: '80%', plugins: { legend: { display: false } } }
    });

    // 3. Gauge Chart - Tanam Tiang
    let tiangPersen = {{ min($totalTiangPersen, 100) }};
    new Chart(document.getElementById('gaugeTiang'), {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [tiangPersen, 100 - tiangPersen],
                backgroundColor: ['#10B981', '#E5E7EB'],
                borderWidth: 0
            }]
        },
        options: { rotation: -90, circumference: 180, cutout: '80%', plugins: { legend: { display: false } } }
    });

    // 4. Line Chart Progress Bulanan
    new Chart(document.getElementById('lineMonthly'), {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
            datasets: [
                { label: 'Penarikan FO (%)', data: [10, 15, 22, 35, 48, {{ min($totalKabelPersen, 100) }}], borderColor: '#F59E0B', tension: 0.3, pointRadius: 2 },
                { label: 'Tanam Tiang (%)', data: [20, 28, 40, 55, 68, {{ min($totalTiangPersen, 100) }}], borderColor: '#10B981', tension: 0.3, pointRadius: 2 }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { legend: { display: false } }, 
            scales: { 
                y: { min: 0, max: 100, ticks: { font: { size: 9 } } }, 
                x: { ticks: { font: { size: 9 } } } 
            } 
        }
    });
</script>

@endsection