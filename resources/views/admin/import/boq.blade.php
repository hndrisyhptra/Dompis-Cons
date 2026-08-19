@extends('layouts.admin')

@section('content')

@php
    $activeImportUuid = request('import_uuid');

    $history = $importProcesses ?? collect();
    $lastHistory = $lastProcess ?? $history->first();

    $getUploaderName = function ($process) {
        if (!$process) {
            return '-';
        }

        return $process->uploader?->name
            ?? $process->uploader?->full_name
            ?? $process->uploader?->username
            ?? $process->uploader?->email
            ?? (
                $process->uploaded_by
                    ? 'User #' . $process->uploaded_by
                    : '-'
            );
    };

    $makeInitials = function ($name) {
        $parts = preg_split(
            '/\s+/',
            trim((string) $name)
        ) ?: [];

        $parts = array_values(
            array_filter($parts)
        );

        if (empty($parts)) {
            return '?';
        }

        return collect($parts)
            ->take(2)
            ->map(
                fn ($part) =>
                    mb_strtoupper(
                        mb_substr(
                            $part,
                            0,
                            1
                        )
                    )
            )
            ->implode('');
    };

    $lastUploaderName =
        $getUploaderName($lastHistory);

    $lastUploaderInitials =
        $makeInitials($lastUploaderName);

    $queueHealth = $queueHealth ?? [
        'state' => 'normal',
        'label' => 'Normal',
        'description' => 'Tidak ada antrean BOQ yang tertahan.',
        'queued_count' => 0,
        'processing_count' => 0,
        'driver' => config('queue.default'),
    ];

    $queueHealthClass =
        match ($queueHealth['state'] ?? 'normal') {
            'processing' =>
                'bg-blue-100 text-blue-700',

            'waiting' =>
                'bg-amber-100 text-amber-700',

            'warning' =>
                'bg-red-100 text-red-700',

            default =>
                'bg-emerald-100 text-emerald-700',
        };

    $queueDotClass =
        match ($queueHealth['state'] ?? 'normal') {
            'processing' =>
                'bg-blue-500 animate-pulse',

            'waiting' =>
                'bg-amber-500',

            'warning' =>
                'bg-red-500',

            default =>
                'bg-emerald-500',
        };

    $lastStatus = $lastHistory?->status ?? null;

    [$lastStatusLabel, $lastStatusClass] =
        match ($lastStatus) {
            'queued' => [
                'Menunggu',
                'bg-amber-100 text-amber-700',
            ],

            'processing' => [
                'Diproses',
                'bg-blue-100 text-blue-700',
            ],

            'completed' => [
                'Selesai',
                'bg-emerald-100 text-emerald-700',
            ],

            'failed' => [
                'Gagal',
                'bg-red-100 text-red-700',
            ],

            'cancelled' => [
                'Dibatalkan',
                'bg-slate-200 text-slate-700',
            ],

            default => [
                '-',
                'bg-slate-100 text-slate-500',
            ],
        };
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

                    <p class="text-sm text-slate-500 mt-2 max-w-3xl">
                        Import BOQ matrix untuk Regular, Exbis, dan Program PT 2 dengan pemetaan LOP, validasi package, dan proses background.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">

                    <a href="{{ route('admin.import.boq.template') }}"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-black hover:bg-slate-200 dark:hover:bg-slate-700">
                        Download Template
                    </a>

                    <a href="{{ route('admin.data-boq') }}"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-blue-700 text-white text-sm font-black hover:bg-blue-800 shadow-lg shadow-blue-700/20">
                        Data BOQ
                    </a>

                </div>

            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">

                <div class="rounded-3xl bg-blue-50 border border-blue-100 p-5">
                    <p class="text-[10px] font-black uppercase tracking-wider text-blue-600">
                        Format File
                    </p>

                    <p class="text-lg font-black text-blue-800 mt-1">
                        XLSX / XLS
                    </p>

                    <p class="text-xs text-blue-600 mt-1">
                        Matrix BOQ, maksimal 100 MB.
                    </p>
                </div>

                <div class="rounded-3xl bg-emerald-50 border border-emerald-100 p-5">
                    <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600">
                        Mapping LOP
                    </p>

                    <p class="text-lg font-black text-emerald-800 mt-1">
                        ID IHLD / Nama LOP
                    </p>

                    <p class="text-xs text-emerald-600 mt-1">
                        Mapping PID tidak digunakan untuk PT 2.
                    </p>
                </div>

                <div class="rounded-3xl bg-indigo-50 border border-indigo-100 p-5">
                    <p class="text-[10px] font-black uppercase tracking-wider text-indigo-600">
                        Existing BOQ
                    </p>

                    <p class="text-lg font-black text-indigo-800 mt-1">
                        Tidak Ditimpa
                    </p>

                    <p class="text-xs text-indigo-600 mt-1">
                        Re-import identik dihitung Tidak Berubah.
                    </p>
                </div>

                <a
                    href="{{ $lastHistory ? route('admin.import.boq', ['import_uuid' => $lastHistory->uuid]) : '#' }}"
                    class="block rounded-3xl bg-amber-50 border border-amber-100 p-5 {{ $lastHistory ? 'hover:border-amber-300' : 'pointer-events-none' }}"
                >
                    <div class="flex items-start justify-between gap-3">

                        <div class="min-w-0">
                            <p class="text-[10px] font-black uppercase tracking-wider text-amber-600">
                                Upload Terakhir
                            </p>

                            <p class="text-sm font-black text-amber-800 mt-1 truncate">
                                {{ $lastHistory?->original_file_name ?? '-' }}
                            </p>
                        </div>

                        <span class="shrink-0 px-2.5 py-1 rounded-full text-[8px] font-black uppercase {{ $lastStatusClass }}">
                            {{ $lastStatusLabel }}
                        </span>

                    </div>

                    @if($lastHistory)
                        <div class="flex items-center gap-2 mt-3 min-w-0">

                            <div class="w-7 h-7 shrink-0 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-[9px] font-black">
                                {{ $lastUploaderInitials }}
                            </div>

                            <div class="min-w-0">
                                <p class="text-[10px] text-amber-700 truncate">
                                    {{ $lastUploaderName }}
                                </p>

                                <p class="text-[9px] text-amber-600 mt-0.5">
                                    {{ strtoupper($lastHistory->project_type ?? '-') }}
                                    •
                                    {{ $lastHistory->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }} WIB
                                </p>
                            </div>

                        </div>
                    @endif

                </a>

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


        {{-- LIVE BACKGROUND RESULT --}}
        <div
            id="importStatusPanel"
            class="hidden bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm"
        >

            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">

                <div class="min-w-0">

                    <div class="flex flex-wrap items-center gap-2">

                        <h2 class="text-lg font-black text-slate-900 dark:text-white">
                            Status Import BOQ
                        </h2>

                        <span
                            id="importStatusBadge"
                            class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[9px] font-black uppercase"
                        >
                            -
                        </span>

                    </div>

                    <p
                        id="progressFileName"
                        class="text-sm text-slate-500 mt-1 break-words"
                    >
                        -
                    </p>

                    <p
                        id="progressUploader"
                        class="text-[11px] text-slate-400 mt-1"
                    >
                        Uploader: -
                    </p>

                    <p
                        id="progressStage"
                        class="text-xs font-bold text-blue-600 mt-2"
                    >
                        Menunggu status...
                    </p>

                </div>

                <div class="lg:text-right">
                    <p
                        id="progressPercentText"
                        class="text-3xl font-black text-blue-700"
                    >
                        0%
                    </p>

                    <p
                        id="progressRowText"
                        class="text-[10px] text-slate-400 font-bold mt-1"
                    >
                        0 / 0 row
                    </p>
                </div>

            </div>


            <div class="mt-5 h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">

                <div
                    id="progressBar"
                    class="h-full bg-blue-600 rounded-full transition-all duration-500"
                    style="width: 0%"
                ></div>

            </div>


            {{-- GENERIC COUNTERS --}}
            <div
                id="importResultStats"
                class="hidden grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3 mt-5"
            >

                <div class="rounded-2xl bg-slate-50 dark:bg-slate-950 p-4">
                    <p class="text-[9px] uppercase font-bold text-slate-400">
                        Processed Row
                    </p>
                    <p id="importProcessed" class="text-2xl font-black text-slate-800 dark:text-white mt-1">
                        0
                    </p>
                </div>

                <div class="rounded-2xl bg-emerald-50 p-4">
                    <p class="text-[9px] uppercase font-bold text-emerald-600">
                        Valid Volume
                    </p>
                    <p id="importValid" class="text-2xl font-black text-emerald-700 mt-1">
                        0
                    </p>
                </div>

                <div class="rounded-2xl bg-red-50 p-4">
                    <p class="text-[9px] uppercase font-bold text-red-600">
                        Invalid
                    </p>
                    <p id="importInvalid" class="text-2xl font-black text-red-700 mt-1">
                        0
                    </p>
                </div>

                <div class="rounded-2xl bg-blue-50 p-4">
                    <p class="text-[9px] uppercase font-bold text-blue-600">
                        BOQ Baru
                    </p>
                    <p id="importCreated" class="text-2xl font-black text-blue-700 mt-1">
                        0
                    </p>
                </div>

                <div class="rounded-2xl bg-indigo-50 p-4">
                    <p class="text-[9px] uppercase font-bold text-indigo-600">
                        Tidak Berubah
                    </p>
                    <p id="importUnchanged" class="text-2xl font-black text-indigo-700 mt-1">
                        0
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-100 p-4">
                    <p class="text-[9px] uppercase font-bold text-slate-500">
                        Dilewati
                    </p>
                    <p id="importSkipped" class="text-2xl font-black text-slate-700 mt-1">
                        0
                    </p>
                </div>

            </div>


            {{-- BOQ SPECIFIC SUMMARY --}}
            <div
                id="boqSummaryPanel"
                class="hidden mt-5 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden"
            >

                <div class="px-4 py-3 bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">

                        <div>
                            <p class="text-xs font-black text-slate-700 dark:text-slate-200">
                                Ringkasan BOQ
                            </p>

                            <p
                                id="boqPackageInfo"
                                class="text-[10px] text-slate-400 mt-0.5"
                            >
                                -
                            </p>
                        </div>

                        <p
                            id="boqSheetInfo"
                            class="text-[10px] font-bold text-slate-500"
                        >
                            Sheet: -
                        </p>

                    </div>

                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-px bg-slate-200 dark:bg-slate-800">

                    @foreach([
                        ['boqTotalHeaders', 'Header BOQ'],
                        ['boqMatchedLop', 'LOP Match'],
                        ['boqUnmappedLop', 'LOP Tidak Match'],
                        ['boqExistingHeaders', 'LOP Ada BOQ'],
                        ['boqVolumeItems', 'Volume > 0'],
                        ['boqUnmappedDesignator', 'Designator Error'],
                        ['boqPriceMissing', 'Harga Kosong'],
                        ['boqPackageConflict', 'Konflik Package'],
                    ] as [$id, $label])

                        <div class="bg-white dark:bg-slate-900 p-4">
                            <p class="text-[9px] uppercase font-bold text-slate-400">
                                {{ $label }}
                            </p>

                            <p
                                id="{{ $id }}"
                                class="text-xl font-black text-slate-800 dark:text-white mt-1"
                            >
                                0
                            </p>
                        </div>

                    @endforeach

                </div>

            </div>


            {{-- FATAL ERROR --}}
            <div
                id="fatalErrorPanel"
                class="hidden mt-5 rounded-2xl bg-red-50 border border-red-200 p-4"
            >
                <p class="text-xs font-black text-red-700">
                    Background import gagal
                </p>

                <p
                    id="fatalErrorText"
                    class="text-xs text-red-600 mt-1 break-words"
                >
                    -
                </p>
            </div>


            {{-- INVALID PREVIEW --}}
            <div
                id="importErrorPreview"
                class="hidden mt-5 rounded-2xl border border-red-200 overflow-hidden"
            >

                <div class="px-4 py-3 bg-red-50 border-b border-red-200">
                    <p class="text-xs font-black text-red-700">
                        Preview Data Bermasalah
                    </p>

                    <p class="text-[10px] text-red-600 mt-1">
                        Menampilkan maksimal 10 error pertama. Seluruh error dapat di-download sebagai CSV.
                    </p>
                </div>

                <div class="overflow-x-auto">

                    <table class="w-full text-xs">

                        <thead class="bg-red-50/50">
                            <tr>
                                <th class="px-3 py-2 text-left">Type</th>
                                <th class="px-3 py-2 text-left">Header / LOP</th>
                                <th class="px-3 py-2 text-left">Row</th>
                                <th class="px-3 py-2 text-left">Designator</th>
                                <th class="px-3 py-2 text-left">Qty</th>
                                <th class="px-3 py-2 text-left">Keterangan</th>
                            </tr>
                        </thead>

                        <tbody
                            id="importErrorRows"
                            class="divide-y divide-red-100"
                        ></tbody>

                    </table>

                </div>

            </div>


            {{-- COMPLETION SUMMARY --}}
            <div
                id="importCompletionSummary"
                class="hidden mt-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 md:p-5"
            >

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                    <div class="flex items-start gap-3 min-w-0">

                        <div
                            id="completionIcon"
                            class="w-10 h-10 shrink-0 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black"
                        >
                            ✓
                        </div>

                        <div class="min-w-0">

                            <p
                                id="completionTitle"
                                class="text-sm font-black text-slate-900 dark:text-white"
                            >
                                Import selesai
                            </p>

                            <p
                                id="completionText"
                                class="text-xs text-slate-500 mt-1 leading-relaxed"
                            >
                                -
                            </p>

                        </div>

                    </div>

                    <div class="flex flex-wrap gap-2 shrink-0">

                        <a
                            href="{{ route('admin.data-boq') }}"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-black transition"
                        >
                            Lihat Data BOQ
                        </a>

                        <a
                            id="downloadErrorButton"
                            href="#"
                            class="hidden inline-flex items-center justify-center px-4 py-2 rounded-xl bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 text-xs font-black transition"
                        >
                            Download Error CSV
                        </a>

                        <a
                            href="{{ route('admin.import.boq') }}"
                            class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-black hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                        >
                            Import Baru
                        </a>

                    </div>

                </div>

            </div>

        </div>


        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            {{-- MAIN UPLOAD --}}
            <div class="xl:col-span-8">

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">

                    <h2 class="text-lg font-black text-slate-900 dark:text-white">
                        Upload File BOQ
                    </h2>

                    <p class="text-sm text-slate-500 mt-1">
                        Kolom A berisi designator. Kolom B dan seterusnya mewakili LOP. Hanya cell volume &gt; 0 yang diproses.
                    </p>


                    <form
                        id="importForm"
                        action="{{ route('admin.import.boq.upload') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-5 mt-6"
                    >

                        @csrf

                        <label
                            for="file"
                            class="flex flex-col items-center justify-center min-h-[210px] rounded-[2rem] border-2 border-dashed border-blue-200 dark:border-slate-700 bg-blue-50/50 dark:bg-slate-950 cursor-pointer hover:bg-blue-50 dark:hover:bg-slate-800 transition"
                        >

                            <p class="text-base font-black text-slate-900 dark:text-white">
                                Klik untuk pilih file BOQ
                            </p>

                            <p
                                id="fileName"
                                class="text-sm text-slate-500 mt-1"
                            >
                                Belum ada file dipilih
                            </p>

                            <p
                                id="fileMeta"
                                class="text-[11px] text-slate-400 mt-1"
                            >
                                Maksimal 100 MB
                            </p>

                            <p class="text-xs text-slate-400 mt-3">
                                Support: .xlsx, .xls
                            </p>

                            <input
                                id="file"
                                type="file"
                                name="file"
                                accept=".xlsx,.xls"
                                required
                                class="hidden"
                                onchange="showSelectedFile(this)"
                            >

                        </label>


                        {{-- PROJECT TYPE --}}
                        <div>

                            <label class="block text-xs font-black text-slate-500 uppercase mb-2">
                                Kategori Project
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

                                <label class="flex items-center gap-2 p-3 rounded-2xl border border-slate-200 bg-slate-50 dark:bg-slate-950 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="project_type"
                                        value="internal"
                                        checked
                                        onchange="toggleCustomerType()"
                                    >
                                    <span class="text-sm font-bold">
                                        TIF / Regular
                                    </span>
                                </label>

                                <label class="flex items-center gap-2 p-3 rounded-2xl border border-amber-200 bg-amber-50 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="project_type"
                                        value="external"
                                        onchange="toggleCustomerType()"
                                    >
                                    <span class="text-sm font-bold text-amber-700">
                                        Exbis
                                    </span>
                                </label>

                                <label class="flex items-center gap-2 p-3 rounded-2xl border border-emerald-200 bg-emerald-50 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="project_type"
                                        value="pt2"
                                        onchange="toggleCustomerType()"
                                    >
                                    <span class="text-sm font-bold text-emerald-700">
                                        Program PT 2
                                    </span>
                                </label>

                            </div>

                        </div>


                        <input
                            type="hidden"
                            name="customer_id"
                            id="final_customer_id"
                            value="1"
                        >


                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            {{-- CUSTOMER EXBIS --}}
                            <div
                                id="wrapper_customer_exbis"
                                class="hidden"
                            >

                                <label class="block text-xs font-black text-slate-500 uppercase mb-2">
                                    Customer Exbis
                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    id="select_customer_exbis"
                                    onchange="updateCustomerAndPackages()"
                                    class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm"
                                >
                                    <option value="">
                                        -- Pilih Customer --
                                    </option>

                                    @foreach($customers as $customer)
                                        @if((int) $customer->id_customer !== 1)
                                            <option value="{{ $customer->id_customer }}">
                                                {{ $customer->customer_name }}
                                            </option>
                                        @endif
                                    @endforeach

                                </select>

                            </div>


                            {{-- PACKAGE --}}
                            <div
                                id="wrapper_package"
                                class="md:col-span-2"
                            >

                                <label class="block text-xs font-black text-slate-500 uppercase mb-2">
                                    Package
                                    <span class="text-red-500">*</span>
                                </label>

                                <select
                                    name="package_id"
                                    id="package_id"
                                    required
                                    class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm disabled:opacity-50"
                                >
                                    <option value="">
                                        -- Pilih Package --
                                    </option>
                                </select>

                            </div>

                        </div>


                        {{-- MAPPING --}}
                        <div>

                            <label class="block text-xs font-black text-slate-500 uppercase mb-2">
                                Mapping Header BOQ
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                name="mapping_by"
                                required
                                class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm"
                            >
                                <option value="id_ihld">
                                    By ID IHLD
                                </option>

                                <option value="lop_name">
                                    By Nama LOP
                                </option>
                            </select>

                            <p class="text-[10px] text-slate-400 mt-2">
                                ID IHLD lebih direkomendasikan karena lebih unik daripada Nama LOP.
                            </p>

                        </div>


                        <div
                            id="pt2Info"
                            class="hidden rounded-2xl bg-emerald-50 border border-emerald-200 p-4"
                        >
                            <p class="text-xs font-black text-emerald-700 uppercase">
                                Aturan PT 2
                            </p>

                            <div class="text-xs text-emerald-700 mt-2 space-y-1 leading-relaxed">
                                <p>• BOQ ditempel pada level PT2 LOP.</p>
                                <p>• Satu PID dapat memiliki banyak LOP, sehingga mapping PID tidak digunakan.</p>
                                <p>• Existing BOQ tidak ditimpa saat re-import.</p>
                            </div>
                        </div>


                        <button
                            id="uploadButton"
                            type="submit"
                            class="w-full sm:w-auto h-12 px-7 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-black shadow-lg shadow-blue-700/20"
                        >
                            Start Upload
                        </button>

                    </form>


                    <div
                        id="uploadingInfo"
                        class="hidden mt-5 rounded-2xl bg-blue-50 border border-blue-100 p-4 text-sm font-bold text-blue-700"
                    >
                        File sedang dikirim ke server. Setelah upload selesai, proses BOQ dilanjutkan oleh background worker.
                    </div>

                </div>

            </div>


            {{-- SIDEBAR --}}
            <div class="xl:col-span-4 space-y-5">

                {{-- QUEUE HEALTH --}}
                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">

                    <div class="flex items-start justify-between gap-3">

                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                                System Status
                            </p>

                            <h2 class="text-sm font-black text-slate-900 dark:text-white mt-0.5">
                                Background Queue
                            </h2>
                        </div>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[9px] font-black uppercase {{ $queueHealthClass }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $queueDotClass }}"></span>
                            {{ $queueHealth['label'] ?? 'Normal' }}
                        </span>

                    </div>

                    <p class="text-[11px] text-slate-500 mt-3 leading-relaxed">
                        {{ $queueHealth['description'] ?? '-' }}
                    </p>

                    <div class="grid grid-cols-3 gap-2 mt-4">

                        <div class="rounded-xl bg-slate-50 dark:bg-slate-950 p-2.5">
                            <p class="text-[9px] uppercase font-bold text-slate-400">
                                Menunggu
                            </p>

                            <p class="text-lg font-black text-amber-600 mt-0.5">
                                {{ number_format($queueHealth['queued_count'] ?? 0) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-slate-50 dark:bg-slate-950 p-2.5">
                            <p class="text-[9px] uppercase font-bold text-slate-400">
                                Diproses
                            </p>

                            <p class="text-lg font-black text-blue-600 mt-0.5">
                                {{ number_format($queueHealth['processing_count'] ?? 0) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-slate-50 dark:bg-slate-950 p-2.5">
                            <p class="text-[9px] uppercase font-bold text-slate-400">
                                Driver
                            </p>

                            <p class="text-[11px] font-black text-slate-700 dark:text-slate-200 mt-1.5 uppercase truncate">
                                {{ $queueHealth['driver'] ?? '-' }}
                            </p>
                        </div>

                    </div>

                </div>


                {{-- RULES --}}
                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">

                    <h2 class="text-sm font-black text-slate-900 dark:text-white">
                        Aturan BOQ
                    </h2>

                    <div class="mt-4 space-y-3">

                        <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4">
                            <p class="text-xs font-black text-blue-700">
                                Header LOP
                            </p>

                            <p class="text-[11px] text-blue-600 mt-1">
                                Kolom B dan seterusnya di-map menggunakan ID IHLD atau Nama LOP.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
                            <p class="text-xs font-black text-emerald-700">
                                Existing BOQ Aman
                            </p>

                            <p class="text-[11px] text-emerald-600 mt-1">
                                Item yang sudah ada tidak mengubah quantity actual atau data operasional.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-4">
                            <p class="text-xs font-black text-amber-700">
                                Package Conflict
                            </p>

                            <p class="text-[11px] text-amber-600 mt-1">
                                LOP yang sudah memakai package berbeda akan dianggap invalid dan tidak dioverwrite.
                            </p>
                        </div>

                    </div>

                </div>


                {{-- HISTORY --}}
                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">

                    <div class="flex items-center justify-between gap-3">

                        <div>
                            <h2 class="text-sm font-black text-slate-900 dark:text-white">
                                History Upload
                            </h2>

                            <p class="text-[10px] text-slate-400 mt-0.5">
                                5 import BOQ terakhir dari semua user.
                            </p>
                        </div>

                        <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-500 text-[9px] font-black">
                            {{ $history->count() }}/5
                        </span>

                    </div>


                    <div class="mt-4 space-y-2 max-h-[300px] overflow-y-auto pr-1">

                        @forelse($history as $log)

                            @php
                                $historyUploaderName =
                                    $getUploaderName($log);

                                $historyUploaderInitials =
                                    $makeInitials(
                                        $historyUploaderName
                                    );

                                [$historyLabel, $historyBadge] =
                                    match ($log->status) {
                                        'queued' => [
                                            'Menunggu',
                                            'bg-amber-100 text-amber-700',
                                        ],

                                        'processing' => [
                                            'Diproses',
                                            'bg-blue-100 text-blue-700',
                                        ],

                                        'completed' => [
                                            'Selesai',
                                            'bg-emerald-100 text-emerald-700',
                                        ],

                                        'failed' => [
                                            'Gagal',
                                            'bg-red-100 text-red-700',
                                        ],

                                        'cancelled' => [
                                            'Batal',
                                            'bg-slate-200 text-slate-700',
                                        ],

                                        default => [
                                            strtoupper(
                                                $log->status ?? '-'
                                            ),
                                            'bg-slate-100 text-slate-500',
                                        ],
                                    };
                            @endphp


                            <a
                                href="{{ route('admin.import.boq', ['import_uuid' => $log->uuid]) }}"
                                class="block rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-3 hover:border-blue-200 transition"
                            >

                                <div class="flex items-start justify-between gap-2">

                                    <div class="flex items-start gap-2.5 min-w-0">

                                        <div class="w-7 h-7 shrink-0 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-200 flex items-center justify-center text-[9px] font-black">
                                            {{ $historyUploaderInitials }}
                                        </div>

                                        <div class="min-w-0">

                                            <p class="text-[11px] font-black text-slate-800 dark:text-slate-100 truncate">
                                                {{ $log->original_file_name ?? '-' }}
                                            </p>

                                            <p class="text-[9px] text-slate-400 mt-0.5 truncate">
                                                <span class="font-bold text-slate-500">
                                                    {{ $historyUploaderName }}
                                                </span>
                                                •
                                                {{ strtoupper($log->project_type ?? '-') }}
                                                •
                                                {{ $log->created_at?->timezone('Asia/Jakarta')->format('d M H:i') ?? '-' }} WIB
                                            </p>

                                        </div>

                                    </div>

                                    <span class="shrink-0 px-2 py-1 rounded-full text-[8px] font-black uppercase {{ $historyBadge }}">
                                        {{ $historyLabel }}
                                    </span>

                                </div>


                                <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2 ml-9 text-[9px] text-slate-400">

                                    <span>
                                        <b class="text-blue-600">
                                            {{ number_format($log->created_count ?? 0) }}
                                        </b>
                                        baru
                                    </span>

                                    <span>
                                        <b class="text-indigo-600">
                                            {{ number_format($log->unchanged_count ?? 0) }}
                                        </b>
                                        tetap
                                    </span>

                                    @if((int) ($log->invalid_rows ?? 0) > 0)
                                        <span>
                                            <b class="text-red-600">
                                                {{ number_format($log->invalid_rows) }}
                                            </b>
                                            invalid
                                        </span>
                                    @endif

                                </div>

                            </a>

                        @empty

                            <div class="rounded-2xl bg-slate-50 dark:bg-slate-950 p-5 text-center">
                                <p class="text-sm text-slate-500">
                                    Belum ada history import BOQ.
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
document.addEventListener('DOMContentLoaded', function () {

    const allPackages = @json($packages);

    const activeImportUuid = @js($activeImportUuid);

    const statusUrl = @js(
        $activeImportUuid
            ? route(
                'admin.import.boq.status',
                $activeImportUuid
            )
            : null
    );


    function escapeHtml(value) {
        const div = document.createElement('div');

        div.textContent = String(
            value ?? ''
        );

        return div.innerHTML;
    }


    function formatNumber(value) {
        return new Intl.NumberFormat(
            'id-ID'
        ).format(
            Number(value || 0)
        );
    }


    window.showSelectedFile = function (input) {

        const fileName =
            document.getElementById('fileName');

        const fileMeta =
            document.getElementById('fileMeta');

        const file =
            input.files?.[0];

        if (!fileName) {
            return;
        }

        if (!file) {
            fileName.innerText =
                'Belum ada file dipilih';

            if (fileMeta) {
                fileMeta.innerText =
                    'Maksimal 100 MB';
            }

            return;
        }

        const extension =
            file.name.includes('.')
                ? file.name
                    .split('.')
                    .pop()
                    .toUpperCase()
                : 'FILE';

        const sizeMb =
            file.size / (1024 * 1024);

        fileName.innerText =
            file.name;

        if (fileMeta) {
            fileMeta.innerText =
                `${extension} • ${sizeMb.toFixed(
                    sizeMb >= 10 ? 1 : 2
                )} MB`;
        }
    };


    window.toggleCustomerType = function () {

        const selected =
            document.querySelector(
                'input[name="project_type"]:checked'
            );

        if (!selected) {
            return;
        }

        const type =
            selected.value;

        const wrapperExbis =
            document.getElementById(
                'wrapper_customer_exbis'
            );

        const selectExbis =
            document.getElementById(
                'select_customer_exbis'
            );

        const wrapperPackage =
            document.getElementById(
                'wrapper_package'
            );

        const customerInput =
            document.getElementById(
                'final_customer_id'
            );

        const pt2Info =
            document.getElementById(
                'pt2Info'
            );

        wrapperExbis.classList.add(
            'hidden'
        );

        wrapperPackage.classList.add(
            'md:col-span-2'
        );

        pt2Info.classList.add(
            'hidden'
        );

        selectExbis.required =
            false;

        selectExbis.value =
            '';

        customerInput.value =
            '1';

        if (type === 'external') {

            wrapperExbis.classList.remove(
                'hidden'
            );

            wrapperPackage.classList.remove(
                'md:col-span-2'
            );

            selectExbis.required =
                true;

            customerInput.value =
                '';

        } else if (type === 'pt2') {

            pt2Info.classList.remove(
                'hidden'
            );
        }

        updatePackageDropdown();
    };


    window.updateCustomerAndPackages = function () {

        const selectExbis =
            document.getElementById(
                'select_customer_exbis'
            );

        const customerInput =
            document.getElementById(
                'final_customer_id'
            );

        customerInput.value =
            selectExbis.value;

        updatePackageDropdown();
    };


    function updatePackageDropdown() {

        const customerId =
            document.getElementById(
                'final_customer_id'
            )?.value;

        const packageSelect =
            document.getElementById(
                'package_id'
            );

        if (!packageSelect) {
            return;
        }

        packageSelect.innerHTML =
            '<option value="">-- Pilih Package --</option>';

        if (!customerId) {
            packageSelect.disabled =
                true;

            return;
        }

        packageSelect.disabled =
            false;

        allPackages
            .filter(
                pkg =>
                    String(pkg.customer_id)
                    === String(customerId)
            )
            .forEach(pkg => {

                const option =
                    document.createElement(
                        'option'
                    );

                option.value =
                    pkg.id_package;

                option.textContent =
                    `${pkg.package_code ?? '-'} - ${pkg.package_name ?? '-'}`;

                packageSelect.appendChild(
                    option
                );
            });
    }


    function renderStatusBadge(status) {

        const badge =
            document.getElementById(
                'importStatusBadge'
            );

        if (!badge) {
            return;
        }

        const statusMap = {
            queued: {
                label: 'Menunggu',
                className:
                    'bg-amber-100 text-amber-700',
            },

            processing: {
                label: 'Diproses',
                className:
                    'bg-blue-100 text-blue-700',
            },

            completed: {
                label: 'Selesai',
                className:
                    'bg-emerald-100 text-emerald-700',
            },

            failed: {
                label: 'Gagal',
                className:
                    'bg-red-100 text-red-700',
            },

            cancelled: {
                label: 'Dibatalkan',
                className:
                    'bg-slate-200 text-slate-700',
            },
        };

        const config =
            statusMap[status]
            ?? {
                label:
                    status ?? '-',

                className:
                    'bg-slate-100 text-slate-600',
            };

        badge.className =
            'px-3 py-1 rounded-full text-[9px] font-black uppercase '
            + config.className;

        badge.innerText =
            config.label;
    }


    function renderBoqSummary(summary) {

        const panel =
            document.getElementById(
                'boqSummaryPanel'
            );

        if (!panel) {
            return;
        }

        const hasSummary =
            summary
            && (
                summary.total_headers !== undefined
                || summary.sheet_name
            );

        if (!hasSummary) {
            panel.classList.add(
                'hidden'
            );

            return;
        }

        panel.classList.remove(
            'hidden'
        );

        const options =
            summary.options ?? {};

        const packageInfo =
            document.getElementById(
                'boqPackageInfo'
            );

        const sheetInfo =
            document.getElementById(
                'boqSheetInfo'
            );

        if (packageInfo) {
            packageInfo.innerText =
                `Package: ${options.package_code ?? '-'} - ${options.package_name ?? '-'} • Mapping: ${options.mapping_by ?? '-'}`;
        }

        if (sheetInfo) {
            sheetInfo.innerText =
                `Sheet: ${summary.sheet_name ?? '-'}`;
        }

        const values = {
            boqTotalHeaders:
                summary.total_headers,

            boqMatchedLop:
                summary.matched_lop,

            boqUnmappedLop:
                summary.unmapped_lop,

            boqExistingHeaders:
                summary.existing_boq_headers,

            boqVolumeItems:
                summary.volume_items,

            boqUnmappedDesignator:
                summary.unmapped_designator,

            boqPriceMissing:
                summary.price_missing,

            boqPackageConflict:
                summary.package_conflict,
        };

        Object.entries(
            values
        ).forEach(
            ([id, value]) => {

                const element =
                    document.getElementById(
                        id
                    );

                if (element) {
                    element.innerText =
                        formatNumber(
                            value
                        );
                }
            }
        );
    }


    function renderErrors(errors) {

        const preview =
            document.getElementById(
                'importErrorPreview'
            );

        const rows =
            document.getElementById(
                'importErrorRows'
            );

        if (!preview || !rows) {
            return;
        }

        if (
            !Array.isArray(errors)
            || errors.length === 0
        ) {
            preview.classList.add(
                'hidden'
            );

            rows.innerHTML =
                '';

            return;
        }

        preview.classList.remove(
            'hidden'
        );

        rows.innerHTML =
            errors.map(error => `
                <tr>
                    <td class="px-3 py-2">
                        ${escapeHtml(
                            error.type
                            ?? '-'
                        )}
                    </td>

                    <td class="px-3 py-2">
                        ${escapeHtml(
                            error.header
                            ?? error.nama_lop
                            ?? error.id_ihld
                            ?? error.pid_sap
                            ?? '-'
                        )}
                    </td>

                    <td class="px-3 py-2">
                        ${escapeHtml(
                            error.row_number
                            ?? '-'
                        )}
                    </td>

                    <td class="px-3 py-2">
                        ${escapeHtml(
                            error.designator
                            ?? '-'
                        )}
                    </td>

                    <td class="px-3 py-2">
                        ${escapeHtml(
                            error.qty
                            ?? '-'
                        )}
                    </td>

                    <td class="px-3 py-2 text-red-700 font-semibold">
                        ${escapeHtml(
                            error.message
                            ?? '-'
                        )}
                    </td>
                </tr>
            `).join('');
    }


    function renderFatalError(data) {

        const panel =
            document.getElementById(
                'fatalErrorPanel'
            );

        const text =
            document.getElementById(
                'fatalErrorText'
            );

        if (!panel || !text) {
            return;
        }

        if (
            data.status === 'failed'
            && data.error_message
        ) {
            panel.classList.remove(
                'hidden'
            );

            text.innerText =
                data.error_message;
        } else {
            panel.classList.add(
                'hidden'
            );

            text.innerText =
                '';
        }
    }


    function renderCompletionSummary(data) {

        const box =
            document.getElementById(
                'importCompletionSummary'
            );

        const icon =
            document.getElementById(
                'completionIcon'
            );

        const title =
            document.getElementById(
                'completionTitle'
            );

        const text =
            document.getElementById(
                'completionText'
            );

        const downloadButton =
            document.getElementById(
                'downloadErrorButton'
            );

        if (
            !box
            || !icon
            || !title
            || !text
            || !downloadButton
        ) {
            return;
        }

        const isTerminal =
            [
                'completed',
                'failed',
                'cancelled',
            ].includes(
                data.status
            );

        if (!isTerminal) {
            box.classList.add(
                'hidden'
            );

            return;
        }

        box.classList.remove(
            'hidden'
        );

        const summary =
            data.summary ?? {};

        if (
            data.status
            === 'completed'
        ) {
            icon.className =
                'w-10 h-10 shrink-0 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black';

            icon.innerText =
                '✓';

            title.innerText =
                Number(
                    data.invalid_rows || 0
                ) > 0
                    ? 'Import BOQ selesai dengan catatan'
                    : 'Import BOQ selesai';

            text.innerText =
                `${formatNumber(summary.matched_lop)} dari ${formatNumber(summary.total_headers)} header LOP berhasil dimapping. `
                + `${formatNumber(summary.volume_items)} volume > 0 diperiksa, `
                + `${formatNumber(data.created_count)} item BOQ dibuat, `
                + `${formatNumber(data.unchanged_count)} tidak berubah, `
                + `${formatNumber(summary.unmapped_designator)} designator tidak ditemukan, `
                + `${formatNumber(summary.price_missing)} item tanpa harga, dan `
                + `${formatNumber(data.invalid_rows)} error tercatat.`;

        } else if (
            data.status
            === 'failed'
        ) {
            icon.className =
                'w-10 h-10 shrink-0 rounded-2xl bg-red-100 text-red-700 flex items-center justify-center font-black';

            icon.innerText =
                '!';

            title.innerText =
                'Import BOQ gagal diproses';

            text.innerText =
                data.error_message
                || 'Background import berhenti sebelum selesai.';

        } else {
            icon.className =
                'w-10 h-10 shrink-0 rounded-2xl bg-slate-200 text-slate-700 flex items-center justify-center font-black';

            icon.innerText =
                '×';

            title.innerText =
                'Import BOQ dibatalkan';

            text.innerText =
                `${formatNumber(data.processed_rows)} row telah diproses sebelum import dibatalkan.`;
        }

        if (
            Number(
                data.invalid_rows || 0
            ) > 0
            && data.error_download_url
        ) {
            downloadButton.href =
                data.error_download_url;

            downloadButton.classList.remove(
                'hidden'
            );
        } else {
            downloadButton.classList.add(
                'hidden'
            );

            downloadButton.removeAttribute(
                'href'
            );
        }
    }


    function renderImportStatus(data) {

        const panel =
            document.getElementById(
                'importStatusPanel'
            );

        if (!panel) {
            return;
        }

        panel.classList.remove(
            'hidden'
        );

        const resultStats =
            document.getElementById(
                'importResultStats'
            );

        if (resultStats) {
            resultStats.classList.remove(
                'hidden'
            );
        }

        const progress =
            Math.min(
                100,
                Math.max(
                    0,
                    Number(
                        data.progress || 0
                    )
                )
            );

        document.getElementById(
            'progressBar'
        ).style.width =
            progress + '%';

        document.getElementById(
            'progressPercentText'
        ).innerText =
            progress + '%';

        document.getElementById(
            'progressFileName'
        ).innerText =
            data.file_name ?? '-';

        document.getElementById(
            'progressUploader'
        ).innerText =
            'Uploader: '
            + (
                data.uploader?.name
                ?? '-'
            );

        document.getElementById(
            'progressStage'
        ).innerText =
            data.stage
            ?? data.status
            ?? '-';

        document.getElementById(
            'progressRowText'
        ).innerText =
            `${formatNumber(data.processed_rows)} / ${formatNumber(data.total_rows)} row`;

        document.getElementById(
            'importProcessed'
        ).innerText =
            formatNumber(
                data.processed_rows
            );

        document.getElementById(
            'importValid'
        ).innerText =
            formatNumber(
                data.valid_rows
            );

        document.getElementById(
            'importInvalid'
        ).innerText =
            formatNumber(
                data.invalid_rows
            );

        document.getElementById(
            'importCreated'
        ).innerText =
            formatNumber(
                data.created_count
            );

        document.getElementById(
            'importUnchanged'
        ).innerText =
            formatNumber(
                data.unchanged_count
            );

        document.getElementById(
            'importSkipped'
        ).innerText =
            formatNumber(
                data.skipped_count
            );

        renderStatusBadge(
            data.status
        );

        renderBoqSummary(
            data.summary || {}
        );

        renderFatalError(
            data
        );

        renderErrors(
            data.errors || []
        );

        renderCompletionSummary(
            data
        );
    }


    async function checkImportStatus() {

        if (!statusUrl) {
            return;
        }

        try {
            const response =
                await fetch(
                    statusUrl,
                    {
                        headers: {
                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',
                        },

                        cache:
                            'no-store',
                    }
                );

            if (!response.ok) {
                throw new Error(
                    'HTTP '
                    + response.status
                );
            }

            const result =
                await response.json();

            const data =
                result.data;

            renderImportStatus(
                data
            );

            if (
                ![
                    'completed',
                    'failed',
                    'cancelled',
                ].includes(
                    data.status
                )
            ) {
                setTimeout(
                    checkImportStatus,
                    2000
                );
            }

        } catch (error) {
            console.error(
                'Gagal membaca status import BOQ:',
                error
            );

            setTimeout(
                checkImportStatus,
                4000
            );
        }
    }


    const importForm =
        document.getElementById(
            'importForm'
        );

    importForm?.addEventListener(
        'submit',
        function () {

            const file =
                document.getElementById(
                    'file'
                );

            if (
                !file
                || !file.files.length
            ) {
                return;
            }

            const button =
                document.getElementById(
                    'uploadButton'
                );

            const uploadingInfo =
                document.getElementById(
                    'uploadingInfo'
                );

            if (button) {
                button.disabled =
                    true;

                button.classList.add(
                    'opacity-60',
                    'cursor-not-allowed'
                );

                button.innerText =
                    'Mengunggah file...';
            }

            uploadingInfo?.classList.remove(
                'hidden'
            );
        }
    );


    toggleCustomerType();

    if (
        activeImportUuid
        && statusUrl
    ) {
        checkImportStatus();
    }
});
</script>

@endsection