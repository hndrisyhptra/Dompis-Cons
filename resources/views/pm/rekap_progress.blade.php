@extends('layouts.pm') @section('content')
<div class="space-y-6">

    {{-- Header Khusus Program --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
            Rekap Progress: <span class="text-blue-600">{{ $activeProgram }}</span>
        </h1>   
        <p class="text-sm text-gray-500 dark:text-gray-400">Pantau kinerja progres Penarikan FO / Tanam Tiang untuk program {{ $activeProgram }}.</p>
    </div>

    {{-- 1. TOP WIDGETS (Sama persis seperti desain Anda, tapi saya buang bagian filter) --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-6">
        {{-- Total Segmen --}}
        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center text-lg shadow-xs"><i class="fa-solid fa-bookmark"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Segmen</p>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ number_format($totalSegments, 0, ',', '.') }}</h3>
            </div>
        </div>

        {{-- Plan FO --}}
        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center text-lg shadow-xs"><i class="fa-solid fa-network-wired"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Target FO</p>
                <h3 class="text-xl font-black text-gray-900 dark:text-white mt-0.5">{{ number_format($totalKabelPlan, 0, ',', '.') }} <span class="text-xs font-medium text-gray-400">m</span></h3>
            </div>
        </div>

        {{-- Aktual FO --}}
        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-500 text-white rounded-xl flex items-center justify-center text-lg shadow-xs"><i class="fa-solid fa-network-wired"></i></div>
            <div class="w-full">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Aktual FO</p>
                <div class="flex items-baseline justify-between mt-0.5">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ number_format($totalKabelActual, 0, ',', '.') }} <span class="text-xs font-medium text-gray-400">m</span></h3>
                    <span class="text-xs font-extrabold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-1.5 py-0.5 rounded-md">{{ number_format($totalKabelPersen, 1, ',', '.') }}%</span>
                </div>
            </div>
        </div>

        {{-- Plan Tiang --}}
        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-green-50 dark:bg-green-950/40 text-green-600 dark:text-green-400 rounded-xl flex items-center justify-center text-lg shadow-xs"><i class="fa-solid fa-bolt"></i></div>
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Target Tiang</p>
                <h3 class="text-xl font-black text-gray-900 dark:text-white mt-0.5">{{ number_format($totalTiangPlan, 0, ',', '.') }} <span class="text-xs font-medium text-gray-400">pcs</span></h3>
            </div>
        </div>

        {{-- Aktual Tiang --}}
        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 bg-green-500 text-white rounded-xl flex items-center justify-center text-lg shadow-xs"><i class="fa-solid fa-bolt"></i></div>
            <div class="w-full">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Aktual Tiang</p>
                <div class="flex items-baseline justify-between mt-0.5">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white">{{ number_format($totalTiangActual, 0, ',', '.') }} <span class="text-xs font-medium text-gray-400">pcs</span></h3>
                    <span class="text-xs font-extrabold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-950/50 px-1.5 py-0.5 rounded-md">{{ number_format($totalTiangPersen, 1, ',', '.') }}%</span>
                </div>
            </div>
        </div>
    </section>

    {{-- CARD TOTAL NILAI (REAL-TIME) --}}
    <section class="mb-8">
        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center text-lg shadow-xs"><i class="fa-solid fa-sack-dollar"></i></div>
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Nilai — {{ $activeProgram }}</p>
                    <h3 class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-0.5">Rp {{ number_format($totalNilaiProgram ?? 0, 0, ',', '.') }}</h3>
                </div>
            </div>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 max-w-xs sm:text-right">Nilai Material (harga designator &times; qty actual) + Nilai Jasa (harga designator &times; qty actual Material pasangannya), real-time.</p>
        </div>
    </section>

    {{-- 2. FULL WIDTH TABLE --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs overflow-hidden mb-8">
        <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h2 class="font-bold text-gray-900 dark:text-white text-sm uppercase tracking-wider">
                <i class="fa-solid fa-table-list text-blue-600 dark:text-blue-400 mr-2"></i> Detail Progres Segmen
            </h2>

            {{-- Search (fitur baru) + Per Page, satu form GET yang sama supaya
                 program & filter lain (branch, kalau nanti dipakai) tetap
                 kebawa lewat withQueryString() di controller. --}}
            <form method="GET" id="perPageForm" class="flex items-center gap-2 w-full sm:w-auto">
                <input type="hidden" name="program" value="{{ $activeProgram }}">

                <input type="text"
                       name="search"
                       value="{{ $search ?? '' }}"
                       placeholder="Cari nama LOP, STO, branch, PID..."
                       class="h-8 flex-1 sm:w-64 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 text-xs px-3">

                <button type="submit" class="h-8 px-3 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shrink-0">
                    Cari
                </button>

                @if(!empty($search))
                    <a href="{{ route('pm.rekap_progress', ['program' => $activeProgram]) }}"
                       class="h-8 px-3 inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 shrink-0">
                        Reset
                    </a>
                @endif

                <select name="per_page" onchange="document.getElementById('perPageForm').submit()" class="h-8 rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 text-xs py-0 shrink-0">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 baris</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 baris</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto min-h-[300px]">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-950/60 text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                        <th class="p-3.5 text-center w-12">No</th>
                        <th class="p-3.5">Branch / STO</th>
                        <th class="p-3.5">Nama LOP</th>
                        <th class="p-3.5 text-center">Plan FO</th>
                        <th class="p-3.5 text-center w-32">Aktual FO</th>
                        <th class="p-3.5 text-center">Plan Tiang</th>
                        <th class="p-3.5 text-center w-32">Aktual Tiang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                    @forelse($tableData as $row)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="p-3.5 text-center font-mono">{{ $row['no'] }}</td>
                            <td class="p-3.5">
                                <div class="font-bold text-gray-900 dark:text-white">{{ $row['branch'] }}</div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">{{ $row['sto'] }}</div>
                            </td>
                            <td class="p-3.5 font-semibold text-blue-600 dark:text-blue-400">{{ $row['nama_lop'] }}</td>
                            <td class="p-3.5 text-center font-mono">{{ number_format($row['kabel_plan'], 0, ',', '.') }} <span class="text-[10px]">m</span></td>
                            <td class="p-3.5">
                                <div class="flex justify-between font-mono text-[10px] mb-1">
                                    <span>{{ number_format($row['kabel_actual'], 0, ',', '.') }} m</span>
                                    <span class="font-bold text-amber-600 dark:text-amber-400">{{ number_format($row['kabel_persen'], 1) }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full"><div class="bg-amber-500 h-full rounded-full" style="width: {{ min($row['kabel_persen'], 100) }}%"></div></div>
                            </td>
                            <td class="p-3.5 text-center font-mono">{{ number_format($row['tiang_plan'], 0, ',', '.') }} <span class="text-[10px]">pcs</span></td>
                            <td class="p-3.5">
                                <div class="flex justify-between font-mono text-[10px] mb-1">
                                    <span>{{ number_format($row['tiang_actual'], 0, ',', '.') }} pcs</span>
                                    <span class="font-bold text-green-600 dark:text-green-400">{{ number_format($row['tiang_persen'], 1) }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-gray-800 h-1.5 rounded-full"><div class="bg-green-500 h-full rounded-full" style="width: {{ min($row['tiang_persen'], 100) }}%"></div></div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-gray-500 dark:text-gray-400">Tidak ada data untuk program ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($lopsData->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/40">
                {{ $lopsData->onEachSide(1)->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

   {{-- 3. BOTTOM GAUGE --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
        {{-- Gauge FO --}}
        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex flex-col items-center justify-center">
            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Persentase Penarikan FO: {{ $activeProgram }}</h4>
            <div class="w-40 h-24 relative">
                <canvas id="gaugeFO"></canvas>
                <div class="absolute bottom-0 inset-x-0 text-center text-2xl font-black text-gray-900 dark:text-white">
                    {{ number_format($totalKabelPersen, 1, ',', '.') }}%
                </div>
            </div>
            {{-- Tambahan Angka Rasio Plan vs Aktual --}}
            <p class="text-[11px] font-mono text-gray-500 dark:text-gray-400 mt-3 bg-gray-50 dark:bg-gray-950 px-3 py-1 rounded-full border border-gray-100 dark:border-gray-800">
                <span class="font-bold text-amber-600 dark:text-amber-500">{{ number_format($totalKabelActual, 0, ',', '.') }}</span> 
                / {{ number_format($totalKabelPlan, 0, ',', '.') }} m
            </p>
        </div>

        {{-- Gauge Tiang --}}
        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs flex flex-col items-center justify-center">
            <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Persentase Tanam Tiang: {{ $activeProgram }}</h4>
            <div class="w-40 h-24 relative">
                <canvas id="gaugeTiang"></canvas>
                <div class="absolute bottom-0 inset-x-0 text-center text-2xl font-black text-gray-900 dark:text-white">
                    {{ number_format($totalTiangPersen, 1, ',', '.') }}%
                </div>
            </div>
            {{-- Tambahan Angka Rasio Plan vs Aktual --}}
            <p class="text-[11px] font-mono text-gray-500 dark:text-gray-400 mt-3 bg-gray-50 dark:bg-gray-950 px-3 py-1 rounded-full border border-gray-100 dark:border-gray-800">
                <span class="font-bold text-green-600 dark:text-green-500">{{ number_format($totalTiangActual, 0, ',', '.') }}</span> 
                / {{ number_format($totalTiangPlan, 0, ',', '.') }} pcs
            </p>
        </div>

        {{-- Line Graph Bulanan --}}
        <div class="bg-white dark:bg-gray-900 p-5 rounded-2xl border border-gray-200/80 dark:border-gray-800/80 shadow-xs">
            <h4 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-4">Tren Progres Lapangan</h4>
            <div class="h-32">
                <canvas id="lineMonthly"></canvas>
            </div>
        </div>
    </div>

</div>
@endsection

{{-- Panggil CDN Chart.js (Hapus baris ini jika aplikasi Anda sudah me-load Chart.js di layout utama) --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Konfigurasi Warna Standar
    const colorEmpty = 'rgba(229, 231, 235, 0.4)'; // Gray-200 transparan
    const colorFO = '#f59e0b'; // Amber-500
    const colorTiang = '#10b981'; // Emerald-500
    
    // Ambil variabel persentase dari Controller (Dibatasi maksimal 100 agar grafik tidak rusak)
    const valFO = Math.min({{ $totalKabelPersen }}, 100);
    const valTiang = Math.min({{ $totalTiangPersen }}, 100);

    // =========================================================
    // GAUGE 1: EFISIENSI FO (Setengah Doughnut)
    // =========================================================
    const ctxFO = document.getElementById('gaugeFO');
    if (ctxFO) {
        new Chart(ctxFO, {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Sisa Target'],
                datasets: [{
                    data: [valFO, 100 - valFO],
                    backgroundColor: [colorFO, colorEmpty],
                    borderWidth: 0,
                    borderRadius: [10, 0] // Membuat ujung grafik melengkung
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                rotation: -90,      // Memutar grafik mulai dari sisi kiri (jam 9)
                circumference: 180, // Memotong grafik hanya setengah lingkaran (180 derajat)
                cutout: '80%',      // Ketebalan garis
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });
    }

    // =========================================================
    // GAUGE 2: EFISIENSI TIANG (Setengah Doughnut)
    // =========================================================
    const ctxTiang = document.getElementById('gaugeTiang');
    if (ctxTiang) {
        new Chart(ctxTiang, {
            type: 'doughnut',
            data: {
                labels: ['Selesai', 'Sisa Target'],
                datasets: [{
                    data: [valTiang, 100 - valTiang],
                    backgroundColor: [colorTiang, colorEmpty],
                    borderWidth: 0,
                    borderRadius: [10, 0]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                rotation: -90,
                circumference: 180,
                cutout: '80%',
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                }
            }
        });
    }

    // =========================================================
    // LINE CHART: TREN PROGRES LAPANGAN (OTOMATIS)
    // =========================================================
    const ctxLine = document.getElementById('lineMonthly');
    if (ctxLine) {
        new Chart(ctxLine, {
            type: 'line',
            data: {
                // Mengambil array bulan otomatis dari Controller (misal sampai Juli)
                labels: @json($chartLabels),
                datasets: [
                    { 
                        label: 'FO (%)', 
                        // Mengambil array data FO otomatis dari Controller
                        data: @json($chartDataFO), 
                        borderColor: '#F59E0B', 
                        backgroundColor: 'transparent', 
                        tension: 0.3, 
                        pointRadius: 3, 
                        borderWidth: 2 
                    },
                    { 
                        label: 'Tiang (%)', 
                        // Mengambil array data Tiang otomatis dari Controller
                        data: @json($chartDataTiang), 
                        borderColor: '#10B981', 
                        backgroundColor: 'transparent', 
                        tension: 0.3, 
                        pointRadius: 3, 
                        borderWidth: 2 
                    }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { display: false } }, 
                scales: { 
                    y: { 
                        min: 0, 
                        max: 100, 
                        ticks: { font: { size: 9 }, stepSize: 25, color: '#9ca3af' }, 
                        grid: { color: 'rgba(243, 244, 246, 0.1)' } 
                    }, 
                    x: { 
                        grid: { display: false }, 
                        ticks: { font: { size: 9 }, color: '#9ca3af' } 
                    } 
                } 
            }
        });
    }
});
</script>