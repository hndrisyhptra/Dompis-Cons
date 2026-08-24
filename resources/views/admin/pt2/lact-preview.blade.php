@extends('layouts.admin')

@section('content')
@php
    $lop = $record->lop;
    $project = $lop->project ?? null;
    $fields = $record->field_values ?? [];
@endphp
<div class="max-w-4xl mx-auto space-y-4 px-4 py-6">

    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Preview Dokumen LACT</h1>
            <p class="text-sm text-gray-500">{{ $lop->lop_name ?? '-' }} · PID: {{ $project->pid ?? '-' }}</p>
        </div>
        <a href="{{ route('admin.pt2.lact.index') }}" class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 inline-flex items-center text-sm font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
            ← Kembali ke Daftar
        </a>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm space-y-5">

        <div class="flex items-center justify-between">
            <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-emerald-100 text-emerald-700">Final</span>
            <p class="text-xs text-gray-400">
                Digenerate {{ optional($record->generated_at)->format('d M Y H:i') }}
                @if($record->generatedBy) oleh {{ $record->generatedBy->name ?? '-' }} @endif
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach([
                'proyek' => 'Proyek', 'kontrak' => 'Kontrak', 'surat_pesanan' => 'Surat Pesanan',
                'witel' => 'Witel', 'lokasi' => 'Lokasi', 'tempat_tanggal' => 'Tempat, Tanggal',
                'jabatan_penandatangan' => 'Jabatan Penandatangan', 'nama_penandatangan' => 'Nama Penandatangan',
            ] as $key => $label)
                <div class="rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase">{{ $label }}</p>
                    <p class="text-sm font-bold text-slate-900 dark:text-white mt-1 break-words">{{ $fields[$key] ?? '-' }}</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-2xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 p-4 flex items-center justify-between">
            <div>
                <p class="text-[10px] font-black text-indigo-500 uppercase">Slot Foto OPM</p>
                <p class="text-base font-black text-indigo-700 dark:text-indigo-400">{{ $record->opm_slot_count }} titik</p>
            </div>
            <div>
                <p class="text-[10px] font-black text-indigo-500 uppercase">Item BOQ</p>
                <p class="text-base font-black text-indigo-700 dark:text-indigo-400">{{ count($record->boq_snapshot ?? []) }} item</p>
            </div>
        </div>

        <div class="flex gap-2 pt-2 border-t border-gray-100 dark:border-gray-800">
            <a id="lact-download-link" href="{{ route('admin.pt2.lact.download', $record->id_lact_generate) }}" class="flex-1 h-11 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white inline-flex items-center justify-center text-sm font-black">
                📥 Download .docx
            </a>
            <a href="{{ route('admin.pt2.lact.editor', $record->pt2_lop_id) }}" class="flex-1 h-11 rounded-xl border border-slate-300 inline-flex items-center justify-center text-sm font-black text-slate-600 hover:bg-slate-50">
                ✏️ Edit &amp; Generate Ulang
            </a>
        </div>

        <p class="text-[11px] text-gray-400 text-center pt-1">
            Dokumen dapat dibuka &amp; diedit langsung di Microsoft Word setelah didownload. Kop/letterhead perlu diisi manual.
        </p>
    </div>

    {{-- Preview Dokumen (render visual dari file .docx yang baru digenerate) --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-black uppercase text-gray-400 tracking-wider">Preview Dokumen</h2>
            <span id="lact-preview-status" class="text-xs font-bold text-gray-400">Memuat preview...</span>
        </div>
        <div id="lact-preview-container" class="rounded-xl border border-gray-200 dark:border-gray-800 overflow-auto max-h-[75vh] bg-gray-100 dark:bg-gray-950 p-4"></div>
        <p class="text-[11px] text-gray-400 mt-3">
            Preview adalah render visual dari file .docx yang sudah digenerate &mdash; tampilan bisa sedikit berbeda dari Microsoft Word (terutama header/footer &amp; kop kosong). Untuk hasil paling akurat dan bisa diedit, gunakan tombol Download di atas.
        </p>
    </div>

</div>

@if(request('autodownload'))
<script>
    document.getElementById('lact-download-link')?.click();
</script>
@endif

{{-- docx-preview: merender file .docx langsung di browser (client-side),
     supaya admin bisa langsung lihat hasil generate tanpa perlu buka Word. --}}
<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/docx-preview@0.3.3/dist/docx-preview.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const url = @js($docxUrl);
    const statusEl = document.getElementById('lact-preview-status');
    const container = document.getElementById('lact-preview-container');

    if (!url) {
        statusEl.textContent = 'File belum tersedia';
        return;
    }
    if (typeof docx === 'undefined') {
        statusEl.textContent = 'Preview tidak tersedia (gagal memuat library)';
        return;
    }

    fetch(url)
        .then(function (res) {
            if (!res.ok) throw new Error('Gagal mengambil file dokumen (' + res.status + ')');
            return res.blob();
        })
        .then(function (blob) {
            return docx.renderAsync(blob, container, null, {
                className: 'docx-preview',
                inWrapper: true,
                ignoreWidth: false,
                ignoreHeight: false,
                ignoreFonts: false,
                breakPages: true,
                experimental: true,
            });
        })
        .then(function () {
            statusEl.textContent = '';
        })
        .catch(function (err) {
            console.error('LACT docx preview error:', err);
            statusEl.textContent = 'Gagal memuat preview';
            container.innerHTML = '<p class="text-xs text-red-500 text-center py-8">Preview gagal dimuat. Silakan gunakan tombol Download untuk membuka dokumen di Word.</p>';
        });
});
</script>
@endsection
