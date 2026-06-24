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
                        Bulk Import PID
                    </h1>

                    <p class="text-sm text-slate-500 mt-2 max-w-2xl">
                        Upload file Excel/CSV untuk validasi, create, dan update data PID serta LOP secara otomatis.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('admin.import.pid.template') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-black hover:bg-slate-200 dark:hover:bg-slate-700">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon lucide-download">
                                <path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/>
                            </svg>
                        </span>
                        <span>Download Template</span>
                    </a>

                    <a href="{{ route('admin.data-pid') }}"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-blue-700 text-white text-sm font-black hover:bg-blue-800 shadow-lg shadow-blue-700/20">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-spreadsheet-icon lucide-file-spreadsheet"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                                <path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/>
                            </svg>
                        </span>
                        <span>Data PID</span>
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
                                XLSX / XLS / CSV
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Support file import PID
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
                                Validasi Wajib
                            </p>
                            <p class="text-sm font-black text-emerald-700 mt-2">
                                PID SAP + LOP
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Mandatory field
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-ticket-check-icon lucide-ticket-check">
                                <path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="m9 12 2 2 4-4"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl bg-white border border-indigo-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-indigo-700 font-bold uppercase">
                                Mode Import
                            </p>
                            <p class="text-sm font-black text-indigo-700 mt-2">
                                Create / Update
                            </p>
                            <p class="text-xs text-slate-500 mt-1">
                                Upsert data project
                            </p>
                        </div>

                        <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil-ruler-icon lucide-pencil-ruler"><path d="M13 7 8.7 2.7a2.41 2.41 0 0 0-3.4 0L2.7 5.3a2.41 2.41 0 0 0 0 3.4L7 13"/>
                                <path d="m8 6 2-2"/><path d="m18 16 2-2"/><path d="m17 11 4.3 4.3c.94.94.94 2.46 0 3.4l-2.6 2.6c-.94.94-2.46.94-3.4 0L11 17"/><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>
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

        {{-- LIVE PROGRESS --}}
                <div id="progressCard"
                     class="hidden bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">
                                Proses Import Berjalan
                            </h2>
                            <p id="progressFileName" class="text-sm text-slate-500">
                                Membaca file...
                            </p>
                        </div>

                        <div class="text-right">
                            <p id="progressPercentText" class="text-3xl font-black text-blue-700">
                                0%
                            </p>
                            <p class="text-xs text-slate-500 font-bold">
                                Processing
                            </p>
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
                            <p class="text-2xl mb-2">📖</p>
                            <p class="text-sm font-black text-blue-700">Reading File</p>
                            <p class="text-xs text-blue-600 mt-1">Membaca Excel/CSV</p>
                        </div>

                        <div id="stepValidating" class="rounded-3xl bg-slate-50 border border-slate-100 p-4 opacity-50">
                            <p class="text-2xl mb-2">🔍</p>
                            <p class="text-sm font-black text-slate-700">Validating Data</p>
                            <p class="text-xs text-slate-500 mt-1">Cek PID SAP & LOP</p>
                        </div>

                        <div id="stepFinalizing" class="rounded-3xl bg-slate-50 border border-slate-100 p-4 opacity-50">
                            <p class="text-2xl mb-2">⚙️</p>
                            <p class="text-sm font-black text-slate-700">Finalizing Import</p>
                            <p class="text-xs text-slate-500 mt-1">Create / update data</p>
                        </div>

                        <div id="stepComplete" class="rounded-3xl bg-slate-50 border border-slate-100 p-4 opacity-50">
                            <p class="text-2xl mb-2">✅</p>
                            <p class="text-sm font-black text-slate-700">Import Complete</p>
                            <p class="text-xs text-slate-500 mt-1">Menyiapkan hasil</p>
                        </div>
                    </div>
                </div>

                {{-- RESULT --}}
                @if($result)
                    @php
                        $total = max((int) ($result['total_rows'] ?? 0), 1);
                        $validPercent = round((($result['valid_rows'] ?? 0) / $total) * 100);
                        $invalidPercent = round((($result['invalid_rows_count'] ?? 0) / $total) * 100);
                    @endphp

                    <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">

                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                            <div>
                                <h2 class="text-lg font-black text-slate-900 dark:text-white">
                                    Detail Hasil Pengecekan Import
                                </h2>
                                <p class="text-sm text-slate-500">
                                    File: <b>{{ $result['file_name'] ?? '-' }}</b>
                                </p>
                            </div>

                            <span class="px-4 py-2 rounded-2xl bg-emerald-50 text-emerald-700 text-xs font-black">
                                IMPORT COMPLETE
                            </span>
                        </div>

                        @if(!empty($result['missing_headers']))
                            <div class="rounded-3xl bg-red-50 border border-red-200 p-5 mb-5">
                                <p class="text-sm font-black text-red-700">
                                    Header wajib tidak ditemukan
                                </p>
                                <p class="text-xs text-red-600 mt-1">
                                    {{ implode(', ', $result['missing_headers']) }}
                                </p>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="rounded-3xl bg-slate-50 dark:bg-slate-800 p-5">
                                <p class="text-xs text-slate-500 font-bold">Total Row</p>
                                <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">
                                    {{ $result['total_rows'] ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-3xl bg-emerald-50 dark:bg-emerald-900/20 p-5">
                                <p class="text-xs text-emerald-700 dark:text-emerald-300 font-bold">Valid Row</p>
                                <p class="text-3xl font-black text-emerald-700 dark:text-emerald-300 mt-1">
                                    {{ $result['valid_rows'] ?? 0 }}
                                </p>
                                <div class="mt-3 h-2 rounded-full bg-emerald-100 overflow-hidden">
                                    <div class="h-full bg-emerald-600 rounded-full" style="width: {{ $validPercent }}%"></div>
                                </div>
                            </div>

                            <div class="rounded-3xl bg-red-50 dark:bg-red-900/20 p-5">
                                <p class="text-xs text-red-700 dark:text-red-300 font-bold">Invalid Row</p>
                                <p class="text-3xl font-black text-red-700 dark:text-red-300 mt-1">
                                    {{ $result['invalid_rows_count'] ?? 0 }}
                                </p>
                                <div class="mt-3 h-2 rounded-full bg-red-100 overflow-hidden">
                                    <div class="h-full bg-red-500 rounded-full" style="width: {{ $invalidPercent }}%"></div>
                                </div>
                            </div>

                            <div class="rounded-3xl bg-blue-50 dark:bg-blue-900/20 p-5">
                                <p class="text-xs text-blue-700 dark:text-blue-300 font-bold">Processed Row</p>
                                <p class="text-3xl font-black text-blue-700 dark:text-blue-300 mt-1">
                                    {{ $result['processed_rows'] ?? $result['valid_rows'] ?? 0 }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                            <div class="rounded-3xl bg-blue-50 p-5">
                                <p class="text-xs text-blue-700 font-bold">Project Baru</p>
                                <p class="text-2xl font-black text-blue-700 mt-1">
                                    {{ $result['project_imported'] ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-3xl bg-amber-50 p-5">
                                <p class="text-xs text-amber-700 font-bold">Update Project</p>
                                <p class="text-2xl font-black text-amber-700 mt-1">
                                    {{ $result['project_updated'] ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-3xl bg-indigo-50 p-5">
                                <p class="text-xs text-indigo-700 font-bold">LOP Baru</p>
                                <p class="text-2xl font-black text-indigo-700 mt-1">
                                    {{ $result['lop_imported'] ?? 0 }}
                                </p>
                            </div>

                            <div class="rounded-3xl bg-orange-50 p-5">
                                <p class="text-xs text-orange-700 font-bold">Update LOP</p>
                                <p class="text-2xl font-black text-orange-700 mt-1">
                                    {{ $result['lop_updated'] ?? 0 }}
                                </p>
                            </div>
                        </div>

                        @if(!empty($result['invalid_rows']))
                            <div class="mt-6 rounded-[2rem] border border-red-200 bg-red-50 overflow-hidden">
                                <div class="px-5 py-4 border-b border-red-200">
                                    <h3 class="text-sm font-black text-red-700">
                                        Preview Data Tidak Memenuhi Syarat
                                    </h3>
                                    <p class="text-xs text-red-600 mt-1">
                                        Menampilkan maksimal 10 baris pertama yang gagal validasi.
                                    </p>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-red-100/70">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">Row</th>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">PID</th>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">PID SAP</th>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">Nama LOP</th>
                                                <th class="px-4 py-3 text-left text-xs font-black text-red-700 uppercase">Keterangan</th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-red-200">
                                            @foreach($result['invalid_rows'] as $invalid)
                                                <tr>
                                                    <td class="px-4 py-3 font-bold text-red-700">
                                                        {{ $invalid['row'] }}
                                                    </td>

                                                    <td class="px-4 py-3 text-red-700">
                                                        {{ $invalid['pid'] ?? '-' }}
                                                    </td>

                                                    <td class="px-4 py-3 text-red-700">
                                                        {{ $invalid['pid_sap'] ?? '-' }}
                                                    </td>

                                                    <td class="px-4 py-3 text-red-700">
                                                        {{ $invalid['nama_lop'] ?? '-' }}
                                                    </td>

                                                    <td class="px-4 py-3 font-bold text-red-700">
                                                        {{ $invalid['reason'] }}
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

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- MAIN --}}
            <div class="xl:col-span-8 space-y-6">

                {{-- UPLOAD CARD --}}
                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">

                     <div class="flex items-start gap-4 mb-6">
                        <div class="w-14 h-14 rounded-3xl bg-blue-50 text-blue-700 flex items-center justify-center text-2xl">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-upload-icon lucide-cloud-upload">
                                <path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/>
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-lg font-black text-slate-900 dark:text-white">
                                Upload File PID
                            </h2>
                            <p class="text-sm text-slate-500">
                                Sistem akan membaca file, validasi mandatory field, menampilkan hasil pengecekan, lalu menyimpan data valid.
                            </p>
                        </div>
                    </div>

                    <form id="importForm"
                          action="{{ route('admin.import.pid.upload') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="space-y-5">

                        @csrf

                        <label for="file"
                               class="group relative flex flex-col items-center justify-center min-h-[200px] rounded-[2rem] border-2 border-dashed border-blue-200 dark:border-slate-700 bg-blue-50/50 dark:bg-slate-950 hover:bg-blue-50 dark:hover:bg-slate-800 cursor-pointer transition">

                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-upload-icon lucide-cloud-upload">
                                <path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/>
                            </svg>

                            <p class="text-base font-black text-slate-900 dark:text-white">
                                Klik untuk pilih file PID
                            </p>

                            <p id="fileName" class="text-sm text-slate-500 mt-1">
                                Belum ada file dipilih
                            </p>

                            <p class="text-xs text-slate-400 mt-3">
                                Support: .xlsx, .xls, .csv
                            </p>

                            <input id="file"
                                   type="file"
                                   name="file"
                                   accept=".xlsx,.xls,.csv"
                                   required
                                   class="hidden"
                                   onchange="showSelectedFile(this)">
                        </label>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <button id="uploadButton"
                                    class="inline-flex items-center justify-center gap-2 h-12 px-6 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-black shadow-lg shadow-blue-700/20">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-rocket-icon lucide-rocket"><path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5"/><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09"/><path d="M9 12a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22.4 22.4 0 0 1-4 2z"/>
                                    <path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 .05 5 .05"/>
                                </svg>
                                <span>Start Upload</span>
                            </button>

                            <a href="{{ route('admin.import.pid.template') }}"
                               class="inline-flex items-center justify-center gap-2 h-12 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-black hover:bg-slate-200 dark:hover:bg-slate-700">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download-icon lucide-download">
                                    <path d="M12 15V3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/>
                                </svg>
                                <span>Download Template</span>
                            </a>
                        </div>
                    </form>
                </div>

            </div>

            {{-- SIDEBAR --}}
            <div class="xl:col-span-4 space-y-5">

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">
                        Mandatory Field
                    </h2>

                    <div class="mt-4 space-y-3">
                        <div class="rounded-3xl bg-emerald-50 border border-emerald-100 p-4">
                            <p class="text-sm font-black text-emerald-700">PID SAP</p>
                            <p class="text-xs text-emerald-600 mt-1">Wajib diisi untuk mapping project.</p>
                        </div>

                        <div class="rounded-3xl bg-emerald-50 border border-emerald-100 p-4">
                            <p class="text-sm font-black text-emerald-700">Nama LOP</p>
                            <p class="text-xs text-emerald-600 mt-1">Wajib diisi untuk membuat data LOP.</p>
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
                        Format Header
                    </h2>

                    <div class="mt-4 rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-4">
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed font-mono">
                            pid, pid_sap, nama_lop, program, execution_type, status_project, id_ihld, tematik, sto, branch, batch, no_sp, tgl_sp, tgl_toc, mitra_name
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
            'stepFinalizing',
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
        setTimeout(() => setProgress(75, 'stepFinalizing'), 1600);
        setTimeout(() => setProgress(95, 'stepComplete'), 2300);
    }
</script>

@endsection