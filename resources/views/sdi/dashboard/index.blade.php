@extends('layouts.sdi')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="mb-6 bg-white dark:bg-gray-900 rounded-3xl p-5 shadow-sm border border-gray-200 dark:border-gray-800">
        <h1 class="text-xl font-black text-gray-900 dark:text-white">Dashboard Golive</h1>
        <p class="text-sm text-gray-500 mt-1">Breakdown Region &amp; Branch untuk LOP PT 2 dan PT 3/Reguler. Klik salah satu angka untuk melihat daftar LOP-nya.</p>
    </div>

    @php
        // Revisi (permintaan user): Matrix PT 3 berada di urutan
        // PERTAMA (paling atas), Matrix PT 2 di bawahnya.
        $tables = [
            ['id' => 'reguler', 'title' => 'Matrix PT 3', 'subtitle' => 'Sumber: menu Approval Golive PT 3', 'data' => $matrixReguler, 'accent' => 'emerald'],
            ['id' => 'pt2', 'title' => 'Matrix PT 2', 'subtitle' => 'Sumber: menu Approval Golive PT 2', 'data' => $matrixPt2, 'accent' => 'blue'],
        ];
    @endphp

    @foreach($tables as $table)
        <div class="mb-6 bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <h2 class="text-base font-black text-gray-900 dark:text-white">{{ $table['title'] }}</h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $table['subtitle'] }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-400 border-b border-gray-100 dark:border-gray-800">
                            <th class="py-3 px-5 font-semibold">Region / Branch</th>
                            <th class="py-3 px-5 font-semibold text-center">Total LOP</th>
                            <th class="py-3 px-5 font-semibold text-center">Blm Golive</th>
                            <th class="py-3 px-5 font-semibold text-center">Golive</th>
                            <th class="py-3 px-5 font-semibold text-center">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($table['data'] as $region)
                            @php($regionKey = $table['id'].'-'.$loop->index)
                            {{-- Baris REGION (subtotal, bold) -- KLIK utk expand/collapse
                            baris Branch di bawahnya (accordion per region, permintaan
                            user spy tabel tidak terlalu panjang). Guard event.target di
                            JS supaya klik tombol angka (.lop-cell) TIDAK ikut toggle. --}}
                            <tr class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-800 cursor-pointer select-none" onclick="toggleRegionRow(event, this, '{{ $regionKey }}')">
                                <td class="py-2.5 px-5 font-black text-gray-800 dark:text-gray-100">
                                    <span class="inline-flex items-center gap-2">
                                        <svg class="region-chevron w-3.5 h-3.5 shrink-0 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6l6 6-6 6" />
                                        </svg>
                                        {{ $region['region'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-5 text-center font-black text-gray-800 dark:text-gray-100">
                                    <button type="button" class="lop-cell hover:underline" data-type="{{ $table['id'] }}" data-region="{{ $region['region'] }}" data-branch="" data-status="total">{{ $region['total'] }}</button>
                                </td>
                                <td class="py-2.5 px-5 text-center font-black text-amber-600">
                                    <button type="button" class="lop-cell hover:underline" data-type="{{ $table['id'] }}" data-region="{{ $region['region'] }}" data-branch="" data-status="waiting">{{ $region['belum'] }}</button>
                                </td>
                                <td class="py-2.5 px-5 text-center font-black text-emerald-600">
                                    <button type="button" class="lop-cell hover:underline" data-type="{{ $table['id'] }}" data-region="{{ $region['region'] }}" data-branch="" data-status="golive">{{ $region['golive'] }}</button>
                                </td>
                                <td class="py-2.5 px-5 text-center font-black text-gray-800 dark:text-gray-100">{{ $region['percent'] }}%</td>
                            </tr>
                            {{-- Baris BRANCH (detail per branch dlm region ini) --}}
                            @foreach($region['branches'] as $branch)
                                <tr class="region-branch-{{ $regionKey }} hidden border-b border-gray-50 dark:border-gray-800/60 last:border-0">
                                    <td class="py-2 px-5 pl-9 text-gray-600 dark:text-gray-300">{{ $branch['name'] }}</td>
                                    <td class="py-2 px-5 text-center text-gray-700 dark:text-gray-300">
                                        <button type="button" class="lop-cell hover:underline" data-type="{{ $table['id'] }}" data-region="{{ $region['region'] }}" data-branch="{{ $branch['name'] }}" data-status="total">{{ $branch['total'] }}</button>
                                    </td>
                                    <td class="py-2 px-5 text-center text-amber-600">
                                        <button type="button" class="lop-cell hover:underline" data-type="{{ $table['id'] }}" data-region="{{ $region['region'] }}" data-branch="{{ $branch['name'] }}" data-status="waiting">{{ $branch['belum'] }}</button>
                                    </td>
                                    <td class="py-2 px-5 text-center text-emerald-600">
                                        <button type="button" class="lop-cell hover:underline" data-type="{{ $table['id'] }}" data-region="{{ $region['region'] }}" data-branch="{{ $branch['name'] }}" data-status="golive">{{ $branch['golive'] }}</button>
                                    </td>
                                    <td class="py-2 px-5 text-center text-gray-500">{{ $branch['percent'] }}%</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

</div>

{{-- Modal daftar LOP (permintaan user: "saat di klik angka muncul list
LOP nya") -- diisi via fetch AJAX ke route('sdi.matrix.lops'), tanpa
reload halaman. --}}
<div id="lop-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 id="lop-modal-title" class="text-sm font-black text-gray-900 dark:text-white">Daftar LOP</h3>
            <button type="button" onclick="closeLopModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">✕</button>
        </div>
        <div id="lop-modal-body" class="px-5 py-4 overflow-y-auto space-y-2">
            <p class="text-sm text-gray-400">Memuat...</p>
        </div>
    </div>
</div>

<script>
function closeLopModal() {
    document.getElementById('lop-modal').classList.add('hidden');
}

function openLopModal(type, region, branch, status, label) {
    var modal = document.getElementById('lop-modal');
    var title = document.getElementById('lop-modal-title');
    var body = document.getElementById('lop-modal-body');

    title.textContent = label;
    body.innerHTML = '<p class="text-sm text-gray-400">Memuat...</p>';
    modal.classList.remove('hidden');

    var url = '{{ route('sdi.matrix.lops') }}'
        + '?type=' + encodeURIComponent(type)
        + '&region=' + encodeURIComponent(region)
        + '&branch=' + encodeURIComponent(branch || '')
        + '&status=' + encodeURIComponent(status);

    fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            var lops = data.lops || [];

            if (lops.length === 0) {
                body.innerHTML = '<p class="text-sm text-gray-400">Tidak ada LOP.</p>';
                return;
            }

            var html = '<ul class="space-y-2">';
            lops.forEach(function (lop) {
                var badge = lop.is_golive
                    ? '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Golive</span>'
                    : '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">Waiting</span>';

                html += '<li class="flex items-center justify-between gap-2 bg-slate-50 dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl px-3 py-2 text-xs">'
                    + '<span class="font-bold text-gray-700 dark:text-gray-200 truncate">' + lop.lop_name + '</span>'
                    + badge
                    + '</li>';
            });
            html += '</ul>';

            body.innerHTML = html;
        })
        .catch(function () {
            body.innerHTML = '<p class="text-sm text-red-500">Gagal memuat data.</p>';
        });
}

document.addEventListener('click', function (e) {
    var btn = e.target.closest('.lop-cell');
    if (!btn) {
        return;
    }

    var type = btn.dataset.type;
    var region = btn.dataset.region;
    var branch = btn.dataset.branch;
    var status = btn.dataset.status;

    var statusLabel = { total: 'Total LOP', waiting: 'Belum Golive', golive: 'Sudah Golive' }[status] || 'LOP';
    var scopeLabel = branch ? (region + ' - ' + branch) : region;
    var typeLabel = type === 'pt2' ? 'PT 2' : 'PT 3/Reguler';

    openLopModal(type, region, branch, status, statusLabel + ' · ' + scopeLabel + ' (' + typeLabel + ')');
});

document.getElementById('lop-modal').addEventListener('click', function (e) {
    if (e.target.id === 'lop-modal') {
        closeLopModal();
    }
});

// Revisi (permintaan user): accordion per region pada tabel matrix.
// Default semua branch collapsed (class 'hidden' dari Blade). Klik
// baris region toggle branch-nya; klik tombol angka (.lop-cell) di
// dalam baris region TIDAK memicu toggle (event guard di baris pertama).
function toggleRegionRow(e, rowEl, key) {
    if (e.target.closest('.lop-cell')) {
        return;
    }

    var rows = document.querySelectorAll('.region-branch-' + key);
    if (!rows.length) {
        return;
    }

    var willShow = rows[0].classList.contains('hidden');
    rows.forEach(function (tr) {
        tr.classList.toggle('hidden', !willShow);
    });

    var chevron = rowEl.querySelector('.region-chevron');
    if (chevron) {
        chevron.classList.toggle('rotate-90', willShow);
    }
}
</script>

@endsection
