@extends('layouts.admin')

@section('content')

@php
    $result = session('import_result');
@endphp

<div class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6">

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <p class="text-xs font-black text-blue-700 uppercase tracking-widest">
                        Import Data
                    </p>

                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">
                        Bulk Import BOQ
                    </h1>

                    <p class="text-sm text-slate-500 mt-2 max-w-2xl">
                        Upload BOQ matrix berdasarkan PID SAP atau Nama LOP untuk mapping item designator bervolume.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('admin.import.boq.template') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-black hover:bg-slate-200 dark:hover:bg-slate-700">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon lucide-download">
                                <path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/>
                            </svg>
                        </span>
                        <span>Download Template</span>
                    </a>

                    <a href="{{ route('admin.data-boq') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-blue-700 text-white text-sm font-black hover:bg-blue-800 shadow-lg shadow-blue-700/20">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-spreadsheet-icon lucide-file-spreadsheet"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                                <path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/>
                            </svg>
                        </span>
                        <span>Data BOQ</span>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">

                <div class="rounded-3xl bg-white border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">
                                Format File
                            </p>
                            <p class="text-sm font-black text-slate-900 mt-2">
                                XLSX / XLS
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Format BOQ matrix
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-symlink-icon lucide-file-symlink"><path d="M4 11V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.706.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h7"/>
                                <path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="m10 18 3-3-3-3"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white border border-emerald-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-emerald-700 font-bold uppercase">
                                Mapping Header
                            </p>
                            <p class="text-sm font-black text-emerald-700 mt-2">
                                PID SAP / LOP
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Kolom B1 dan seterusnya
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-link-icon lucide-link">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white border border-indigo-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-indigo-700 font-bold uppercase">
                                Sheet Package
                            </p>
                            <p class="text-sm font-black text-indigo-700 mt-2">
                                PAKET 5 / 10
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Nama sheet = package
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-package-icon lucide-package"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/>
                                <path d="M12 22V12"/><polyline points="3.29 7 12 12 20.71 7"/><path d="m7.5 4.27 9 5.15"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white border border-amber-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-amber-700 font-bold uppercase">
                                Upload Terakhir
                            </p>
                            <p class="text-sm font-black text-amber-700 mt-2 truncate max-w-[160px]">
                                {{ $lastImport?->uploader?->name ?? '-' }}
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ $lastImport?->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }} WIB
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-clock-icon lucide-clipboard-clock"><path d="M16 14v2.2l1.6 1"/><path d="M16 4h2a2 2 0 0 1 2 2v.832"/>
                                <path d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h2"/><circle cx="16" cy="16" r="6"/><rect x="8" y="2" width="8" height="4" rx="1"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ALERT --}}
        @if(session('success'))
            <div class="rounded-3xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 text-sm font-bold">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-3xl bg-red-50 border border-red-200 text-red-700 px-5 py-4 text-sm font-bold">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-3xl bg-red-50 border border-red-200 text-red-700 px-5 py-4 text-sm font-bold">
                {{ $errors->first() }}
            </div>
        @endif

                {{-- RESULT --}}
                @if($result)
                    <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">

                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                            <div>
                                <h2 class="text-lg font-black text-slate-900 dark:text-white">
                                    Detail Hasil Import BOQ
                                </h2>
                                <p class="text-sm text-slate-500">
                                    File: <b>{{ $result['file_name'] ?? '-' }}</b>
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    Sheet/Package: <b>{{ $result['sheet_name'] ?? '-' }}</b>
                                </p>
                            </div>

                            <span class="px-4 py-2 rounded-2xl bg-emerald-50 text-emerald-700 text-xs font-black">
                                IMPORT COMPLETE
                            </span>
                        </div>

                        @if(!empty($result['error_message']))
                            <div class="rounded-3xl bg-red-50 border border-red-200 p-5 mb-5">
                                <p class="text-sm font-black text-red-700">
                                    Import gagal
                                </p>
                                <p class="text-xs text-red-600 mt-1">
                                    {{ $result['error_message'] }}
                                </p>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="rounded-3xl bg-slate-50 dark:bg-slate-800 p-5">
                                <p class="text-xs text-slate-500 font-bold">Header BOQ</p>
                                <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">
                                    {{ $result['total_headers'] ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-3xl bg-emerald-50 p-5">
                                <p class="text-xs text-emerald-700 font-bold">LOP Match</p>
                                <p class="text-3xl font-black text-emerald-700 mt-1">
                                    {{ $result['matched_lop'] ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-3xl bg-red-50 p-5">
                                <p class="text-xs text-red-700 font-bold">LOP Tidak Match</p>
                                <p class="text-3xl font-black text-red-700 mt-1">
                                    {{ $result['unmapped_lop'] ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-3xl bg-blue-50 p-5">
                                <p class="text-xs text-blue-700 font-bold">
                                    Data Sudah Ada
                                </p>

                                <p class="text-3xl font-black text-blue-700 mt-1">
                                    {{ $result['existing_boq_headers'] ?? 0 }}
                                </p>

                                <p class="text-xs text-blue-600 mt-2">
                                    PID SAP / Nama LOP yang sudah memiliki BOQ
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                            <div class="rounded-3xl bg-blue-50 p-5">
                                <p class="text-xs text-blue-700 font-bold">Data Baru</p>
                                <p class="text-2xl font-black text-blue-700 mt-1">
                                    {{ $result['imported'] ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-3xl bg-amber-50 p-5">
                                <p class="text-xs text-amber-700 font-bold">Update</p>
                                <p class="text-2xl font-black text-amber-700 mt-1">
                                    {{ $result['updated'] ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-3xl bg-red-50 p-5">
                                <p class="text-xs text-red-700 font-bold">Designator Tidak Ketemu</p>
                                <p class="text-2xl font-black text-red-700 mt-1">
                                    {{ $result['unmapped_designator'] ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-3xl bg-orange-50 p-5">
                                <p class="text-xs text-orange-700 font-bold">Harga Kosong</p>
                                <p class="text-2xl font-black text-orange-700 mt-1">
                                    {{ $result['price_missing'] ?? 0 }}
                                </p>
                            </div>
                        </div>

                        @if(!empty($result['invalid_rows']))
                            <div class="mt-6 rounded-[2rem] border border-red-200 bg-red-50 overflow-hidden">
                                <div class="px-5 py-4 border-b border-red-200">
                                    <h3 class="text-sm font-black text-red-700">
                                        Preview Data Bermasalah
                                    </h3>
                                    <p class="text-xs text-red-600 mt-1">
                                        Menampilkan maksimal 10 data pertama yang gagal validasi/matching.
                                    </p>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-red-100/70">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">Type</th>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">Header</th>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">Row</th>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">Designator</th>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">Qty</th>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">Keterangan</th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-red-200">
                                            @foreach($result['invalid_rows'] as $invalid)
                                                <tr>
                                                    <td class="px-4 py-3 font-bold text-red-700">
                                                        {{ $invalid['type'] ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-3 text-red-700">
                                                        {{ $invalid['header'] ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-3 text-red-700">
                                                        {{ $invalid['row'] ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-3 text-red-700">
                                                        {{ $invalid['designator'] ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-3 text-red-700">
                                                        {{ $invalid['qty'] ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-3 font-bold text-red-700">
                                                        {{ $invalid['reason'] ?? '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                    </div>
                @endif

                {{-- LIVE PROGRESS --}}
                <div id="progressCard"
                     class="hidden bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">
                                Proses Import BOQ Berjalan
                            </h2>
                            <p id="progressFileName" class="text-sm text-slate-500">
                                Membaca file...
                            </p>
                        </div>

                        <div class="text-right">
                            <p id="progressPercentText" class="text-3xl font-black text-blue-700">0%</p>
                            <p class="text-xs text-slate-500 font-bold">Processing</p>
                        </div>
                    </div>

                    <div class="h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mb-6">
                        <div id="progressBar"
                             class="h-full bg-gradient-to-r from-blue-600 to-cyan-500 rounded-full transition-all duration-500"
                             style="width: 0%">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div id="stepReading" class="rounded-3xl bg-blue-50 border border-blue-100 p-4">
                            <p class="text-2xl mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-notebook-pen-icon lucide-notebook-pen"><path d="M13.4 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7.4"/>
                                    <path d="M2 6h4"/><path d="M2 10h4"/><path d="M2 14h4"/><path d="M2 18h4"/><path d="M21.378 5.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"/>
                                </svg>
                            </p>
                            <p class="text-sm font-black text-blue-700">Reading File</p>
                            <p class="text-xs text-blue-600 mt-1">Membaca matrix BOQ</p>
                        </div>

                        <div id="stepValidating" class="rounded-3xl bg-slate-50 border border-slate-100 p-4 opacity-50">
                            <p class="text-2xl mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-list-checks-icon lucide-list-checks">
                                    <path d="M13 5h8"/><path d="M13 12h8"/><path d="M13 19h8"/><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/>
                                </svg>
                            </p>
                            <p class="text-sm font-black text-slate-700">Validating BOQ</p>
                            <p class="text-xs text-slate-500 mt-1">Cek package & volume</p>
                        </div>

                        <div id="stepMatching" class="rounded-3xl bg-slate-50 border border-slate-100 p-4 opacity-50">
                            <p class="text-2xl mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-link-icon lucide-link">
                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                </svg>
                            </p>
                            <p class="text-sm font-black text-slate-700">Matching Data</p>
                            <p class="text-xs text-slate-500 mt-1">LOP & designator</p>
                        </div>

                        <div id="stepComplete" class="rounded-3xl bg-slate-50 border border-slate-100 p-4 opacity-50">
                            <p class="text-2xl mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check-big-icon lucide-circle-check-big">
                                    <path d="M21.801 10A10 10 0 1 1 17 3.335"/><path d="m9 11 3 3L22 4"/>
                                </svg>
                            </p>
                            <p class="text-sm font-black text-slate-700">Import Complete</p>
                            <p class="text-xs text-slate-500 mt-1">Menyiapkan hasil</p>
                        </div>
                    </div>
                </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- MAIN --}}
            <div class="xl:col-span-8 space-y-6">

                {{-- UPLOAD --}}
                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">

                    <div class="flex items-start gap-4 mb-6">
                        <div class="w-14 h-14 rounded-3xl bg-blue-50 text-blue-700 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-upload-icon lucide-cloud-upload">
                                <path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/>
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">
                                Upload File BOQ
                            </h2>
                            <p class="text-sm text-slate-500">
                                A1 = Designator, B1 dst = PID SAP / Nama LOP, A2 dst = Designator, isi cell = volume.
                            </p>
                        </div>
                    </div>

                    <form id="importForm"
                          action="{{ route('admin.import.boq.upload') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="space-y-5">

                        @csrf

                        <label for="file"
                               class="group relative flex flex-col items-center justify-center min-h-[240px] rounded-[2rem] border-2 border-dashed border-blue-200 dark:border-slate-700 bg-blue-50/50 dark:bg-slate-950 hover:bg-blue-50 dark:hover:bg-slate-800 cursor-pointer transition">

                            
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-upload-icon lucide-cloud-upload">
                                    <path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/>
                                </svg>
                            

                            <p class="text-base font-black text-slate-900 dark:text-white">
                                Klik untuk pilih file BOQ
                            </p>

                            <p id="fileName" class="text-sm text-slate-500 mt-1">
                                Belum ada file dipilih
                            </p>

                            <p class="text-xs text-slate-400 mt-3">
                                Support: .xlsx, .xls
                            </p>

                            <input id="file"
                                   type="file"
                                   name="file"
                                   accept=".xlsx,.xls"
                                   required
                                   class="hidden"
                                   onchange="showSelectedFile(this)">
                        </label>

                       <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-2">
                                    Pilih Customer
                                </label>
                                <select name="customer_id" id="customer_id" required
                                        class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm"
                                        onchange="updatePackageDropdown()">
                                    <option value="">-- Pilih Customer --</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id_customer }}">{{ $customer->customer_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase mb-2">
                                    Pilih Package
                                </label>
                                <select name="package_id" id="package_id" required
                                        class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm disabled:opacity-50" disabled>
                                    <option value="">-- Pilih Package --</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase mb-2">
                                Mapping Header BOQ
                            </label>
                            <select name="mapping_by" required
                                    class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm">
                                <option value="pid">By PID SAP</option>
                                <option value="id_ihld">ID IHLD</option>
                                <option value="lop_name">By Nama LOP</option>
                            </select>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <button id="uploadButton" type="submit"
                                    class="flex-1 inline-flex items-center justify-center gap-2 h-12 px-6 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-black shadow-lg shadow-blue-700/20">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rocket-icon lucide-rocket"><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09"/><path d="M9 12a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.4 22.4 0 0 1-4 2z"/><path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 .05 5 .05"/></svg>
                                <span>Start Upload</span>
                            </button>

                            <button type="button" onclick="downloadTemplateWithParams()"
                                   class="flex-1 inline-flex items-center justify-center gap-2 h-12 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-black hover:bg-slate-200 dark:hover:bg-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon lucide-download"><path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/></svg>
                                <span>Download Template</span>
                            </button>
                        </div>
                    </form>
                </div>

            </div>

            {{-- SIDEBAR --}}
            <div class="xl:col-span-4 space-y-5">

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">
                        Aturan BOQ
                    </h2>

                    <div class="mt-4 space-y-3">
                        <div class="rounded-3xl bg-emerald-50 border border-emerald-100 p-4">
                            <p class="text-sm font-black text-emerald-700">Sheet = Package</p>
                            <p class="text-xs text-emerald-600 mt-1">Contoh: PAKET 5 harus ada di master package.</p>
                        </div>

                        <div class="rounded-3xl bg-blue-50 border border-blue-100 p-4">
                            <p class="text-sm font-black text-blue-700">Header Kolom</p>
                            <p class="text-xs text-blue-600 mt-1">B1 dst berisi PID SAP atau Nama LOP.</p>
                        </div>

                        <div class="rounded-3xl bg-amber-50 border border-amber-100 p-4">
                            <p class="text-sm font-black text-amber-700">Volume &gt; 0</p>
                            <p class="text-xs text-amber-600 mt-1">Hanya item designator bervolume yang diimport.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">
                        Upload Terakhir
                    </h2>

                    <div class="mt-4 rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-500 font-bold">Nama File</p>
                        <p class="text-sm font-black text-slate-900 dark:text-white mt-1 break-words">
                            {{ $lastImport?->file_name ?? '-' }}
                        </p>

                        <div class="grid grid-cols-2 gap-3 mt-4">
                            <div>
                                <p class="text-xs text-slate-500 font-bold">Uploader</p>
                                <p class="text-sm font-black text-slate-900 dark:text-white">
                                    {{ $lastImport?->uploader?->name ?? '-' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 font-bold">Waktu</p>
                                <p class="text-sm font-black text-slate-900 dark:text-white">
                                    {{ $lastImport?->created_at?->timezone('Asia/Jakarta')->format('H:i') ?? '-' }} WIB
                                </p>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 mt-3">
                            {{ $lastImport?->created_at?->timezone('Asia/Jakarta')->format('d M Y') ?? '-' }}
                        </p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">
                        History Upload
                    </h2>

                    <div class="mt-4 space-y-3">
                        @forelse($importLogs as $log)
                            <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-black text-slate-900 dark:text-white truncate">
                                        {{ $log->file_name ?? '-' }}
                                    </p>

                                    <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-black">
                                        {{ strtoupper($log->status ?? 'success') }}
                                    </span>
                                </div>

                                <p class="text-xs text-slate-500 mt-2">
                                    Upload oleh <b>{{ $log->uploader?->name ?? '-' }}</b>
                                </p>

                                <p class="text-xs text-slate-500">
                                    {{ $log->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                                </p>

                                <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                                    <div class="rounded-2xl bg-white dark:bg-slate-900 p-2">
                                        <p class="text-[10px] text-slate-500">Import</p>
                                        <p class="font-black text-blue-600">{{ $log->imported }}</p>
                                    </div>

                                    <div class="rounded-2xl bg-white dark:bg-slate-900 p-2">
                                        <p class="text-[10px] text-slate-500">Update</p>
                                        <p class="font-black text-amber-600">{{ $log->updated }}</p>
                                    </div>

                                    <div class="rounded-2xl bg-white dark:bg-slate-900 p-2">
                                        <p class="text-[10px] text-slate-500">Skip</p>
                                        <p class="font-black text-red-600">{{ $log->skipped }}</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 p-5 text-center">
                                <p class="text-sm text-slate-500">
                                    Belum ada history upload.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<script>
    function showSelectedFile(input) {
        const fileName = document.getElementById('fileName');

        if (input.files && input.files[0]) {
            fileName.innerText = input.files[0].name;
        } else {
            fileName.innerText = 'Belum ada file dipilih';
        }
    }

    const importForm = document.getElementById('importForm');

    importForm.addEventListener('submit', function () {
        const fileInput = document.getElementById('file');

        if (!fileInput.files.length) {
            return;
        }

        document.getElementById('progressCard').classList.remove('hidden');
        document.getElementById('progressFileName').innerText = fileInput.files[0].name;

        document.getElementById('uploadButton').disabled = true;
        document.getElementById('uploadButton').classList.add('opacity-60', 'cursor-not-allowed');
        document.getElementById('uploadButton').innerHTML = '<span>⏳</span><span>Uploading...</span>';

        runImportProgress();
    });

    function setProgress(percent, activeStepId) {
        document.getElementById('progressBar').style.width = percent + '%';
        document.getElementById('progressPercentText').innerText = percent + '%';

        const steps = [
            'stepReading',
            'stepValidating',
            'stepMatching',
            'stepComplete'
        ];

        steps.forEach(function (id) {
            const el = document.getElementById(id);
            el.classList.add('opacity-50', 'bg-slate-50', 'border-slate-100');
            el.classList.remove('bg-blue-50', 'border-blue-100', 'bg-emerald-50', 'border-emerald-100');
        });

        const active = document.getElementById(activeStepId);

        if (activeStepId === 'stepComplete') {
            active.classList.remove('opacity-50', 'bg-slate-50', 'border-slate-100');
            active.classList.add('bg-emerald-50', 'border-emerald-100');
        } else {
            active.classList.remove('opacity-50', 'bg-slate-50', 'border-slate-100');
            active.classList.add('bg-blue-50', 'border-blue-100');
        }
    }

    function runImportProgress() {
        setTimeout(() => setProgress(20, 'stepReading'), 200);
        setTimeout(() => setProgress(45, 'stepValidating'), 900);
        setTimeout(() => setProgress(70, 'stepMatching'), 1600);
        setTimeout(() => setProgress(95, 'stepComplete'), 2300);
    }
</script>

<script>
    // Menyuntikkan data packages dari backend ke JavaScript
    const allPackages = @json($packages);

    function updatePackageDropdown() {
        const customerId = document.getElementById('customer_id').value;
        const packageSelect = document.getElementById('package_id');
        
        // Reset dropdown
        packageSelect.innerHTML = '<option value="">-- Pilih Package --</option>';
        
        if (!customerId) {
            packageSelect.disabled = true;
            return;
        }

        packageSelect.disabled = false;

        // Filter package berdasarkan customer_id
        const filteredPackages = allPackages.filter(pkg => pkg.customer_id == customerId);
        
        filteredPackages.forEach(pkg => {
            const option = document.createElement('option');
            option.value = pkg.id_package;
            option.textContent = `${pkg.package_code} - ${pkg.package_name}`;
            packageSelect.appendChild(option);
        });
    }

    function downloadTemplateWithParams() {
        const customerId = document.getElementById('customer_id').value;
        const packageId = document.getElementById('package_id').value;

        if (!customerId || !packageId) {
            alert('Silakan pilih Customer dan Package terlebih dahulu untuk mendownload template yang sesuai.');
            return;
        }

        // Redirect ke route download dengan query string
        const url = `{{ route('admin.import.boq.template') }}?customer_id=${customerId}&package_id=${packageId}`;
        window.location.href = url;
    }
</script>

@endsection