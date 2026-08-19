@extends('layouts.admin')

@section('content')
@php
    $activeImportUuid = request('import_uuid');

    // History PID sekarang sepenuhnya memakai import_processes.
    $historyItems = ($importProcesses ?? collect())->take(5);
    $lastHistory  = $lastProcess ?? $historyItems->first();

    $lastStatus = strtolower($lastHistory?->status ?? '');
    $lastStatusLabel = match ($lastStatus) {
        'queued' => 'Menunggu',
        'processing' => 'Diproses',
        'completed' => 'Selesai',
        'failed' => 'Gagal',
        'cancelled' => 'Dibatalkan',
        default => 'Belum Ada',
    };
    $lastStatusClass = match ($lastStatus) {
        'queued' => 'bg-amber-100 text-amber-700',
        'processing' => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-emerald-100 text-emerald-700',
        'failed' => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-slate-200 text-slate-700',
        default => 'bg-slate-100 text-slate-500',
    };

    $lastUploaderName = $lastHistory?->uploader?->name
        ?? $lastHistory?->uploader?->full_name
        ?? $lastHistory?->uploader?->username
        ?? $lastHistory?->uploader?->email
        ?? ($lastHistory?->uploaded_by ? 'User #' . $lastHistory->uploaded_by : '-');

    $makeInitials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $parts = array_values(array_filter($parts));

        if (empty($parts)) {
            return '?';
        }

        return collect($parts)
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    };

    $lastUploaderInitials = $makeInitials($lastUploaderName);

    $queueHealth = $queueHealth ?? [
        'state' => 'normal',
        'label' => 'Normal',
        'description' => 'Tidak ada antrean PID yang tertahan.',
        'queued_count' => 0,
        'processing_count' => 0,
        'driver' => config('queue.default'),
    ];

    $queueHealthClass = match ($queueHealth['state'] ?? 'normal') {
        'processing' => 'bg-blue-100 text-blue-700',
        'waiting' => 'bg-amber-100 text-amber-700',
        'warning' => 'bg-red-100 text-red-700',
        default => 'bg-emerald-100 text-emerald-700',
    };

    $queueDotClass = match ($queueHealth['state'] ?? 'normal') {
        'processing' => 'bg-blue-500 animate-pulse',
        'waiting' => 'bg-amber-500',
        'warning' => 'bg-red-500',
        default => 'bg-emerald-500',
    };
@endphp

<div class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <p class="text-xs font-black text-blue-700 uppercase tracking-widest">Import Data</p>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">Bulk Import PID</h1>
                    <p class="text-sm text-slate-500 mt-2 max-w-3xl">
                        File disimpan terlebih dahulu lalu diproses oleh background worker. Regular menggunakan 1 PID = 1 LOP, sedangkan PT 2 mendukung 1 PID dengan banyak LOP berdasarkan ID IHLD.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('admin.import.pid.template') }}"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-black hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Download Template
                    </a>

                    <a href="{{ route('admin.data-pid') }}"
                       class="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-blue-700 text-white text-sm font-black hover:bg-blue-800 shadow-lg shadow-blue-700/20 transition">
                        Data PID
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <div class="rounded-3xl bg-blue-50 border border-blue-100 p-5">
                    <p class="text-xs text-blue-700 font-bold uppercase">Regular</p>
                    <p class="text-lg font-black text-blue-800 mt-1">1 PID = 1 LOP</p>
                    <p class="text-xs text-blue-600 mt-1">PID yang sama di satu file dianggap duplikat.</p>
                </div>

                <div class="rounded-3xl bg-emerald-50 border border-emerald-100 p-5">
                    <p class="text-xs text-emerald-700 font-bold uppercase">Program PT 2</p>
                    <p class="text-lg font-black text-emerald-800 mt-1">1 PID = Banyak LOP</p>
                    <p class="text-xs text-emerald-600 mt-1">LOP dibedakan oleh kombinasi PID SAP + ID IHLD.</p>
                </div>

                @if($lastHistory?->uuid)
                    <a href="{{ route('admin.import.pid', ['import_uuid' => $lastHistory->uuid]) }}"
                       class="group rounded-3xl bg-amber-50 border border-amber-100 p-5 hover:border-amber-300 hover:shadow-sm transition">
                @else
                    <div class="rounded-3xl bg-amber-50 border border-amber-100 p-5">
                @endif
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-amber-700 font-bold uppercase">Upload Terakhir</p>
                            <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase {{ $lastStatusClass }}">
                                {{ $lastStatusLabel }}
                            </span>
                        </div>

                        <p class="text-sm font-black text-amber-900 mt-2 truncate">
                            {{ $lastHistory?->original_file_name ?? '-' }}
                        </p>

                        <div class="flex items-center gap-2 mt-2 min-w-0">
                            <div class="w-7 h-7 shrink-0 rounded-full bg-amber-200 text-amber-800 flex items-center justify-center text-[9px] font-black">
                                {{ $lastUploaderInitials }}
                            </div>
                            <p class="text-[10px] text-amber-700 truncate">
                                Diunggah oleh <span class="font-black">{{ $lastUploaderName }}</span>
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[10px] text-amber-700 mt-2">
                            <span>{{ strtoupper($lastHistory?->project_type ?? '-') }}</span>
                            <span>•</span>
                            <span>{{ number_format($lastHistory?->total_rows ?? 0) }} row</span>
                            <span>•</span>
                            <span>{{ number_format($lastHistory?->valid_rows ?? 0) }} valid</span>
                            @if(($lastHistory?->invalid_rows ?? 0) > 0)
                                <span>•</span>
                                <span class="font-black text-red-600">{{ number_format($lastHistory->invalid_rows) }} invalid</span>
                            @endif
                        </div>

                        <p class="text-[10px] text-amber-600 mt-2">
                            {{ $lastHistory?->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') ?? '-' }} WIB
                        </p>
                @if($lastHistory?->uuid)
                    </a>
                @else
                    </div>
                @endif
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

        {{-- ACTIVE IMPORT: HANYA MUNCUL SETELAH UPLOAD / ADA UUID --}}
        <div id="progressCard"
             class="{{ $activeImportUuid ? '' : 'hidden' }} bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">

            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-lg font-black text-slate-900 dark:text-white">Status Import PID</h2>
                        <span id="progressStatusBadge"
                              class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-black uppercase">
                            MEMUAT
                        </span>
                    </div>

                    <p id="progressFileName" class="text-sm font-black text-slate-800 dark:text-slate-100 mt-2">
                        Memuat informasi import...
                    </p>
                    <p id="progressUploader" class="text-[10px] font-bold text-slate-400 mt-1">
                        Uploader: -
                    </p>
                    <p id="progressStage" class="text-xs text-slate-500 mt-1">
                        Menghubungkan ke status background process...
                    </p>
                </div>

                <div class="text-left md:text-right">
                    <p id="progressPercentText" class="text-3xl font-black text-blue-700">0%</p>
                    <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">Real Progress</p>
                </div>
            </div>

            <div class="mt-5 h-3 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                <div id="progressBar"
                     class="h-full bg-blue-600 rounded-full transition-all duration-500"
                     style="width: 0%"></div>
            </div>

            {{-- FINAL COMPLETION SUMMARY --}}
            <div id="importCompletionSummary"
                 class="hidden mt-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-4 md:p-5">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex items-start gap-3 min-w-0">
                        <div id="completionIcon"
                             class="w-10 h-10 shrink-0 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black">
                            ✓
                        </div>

                        <div class="min-w-0">
                            <p id="completionTitle"
                               class="text-sm font-black text-slate-900 dark:text-white">
                                Import selesai
                            </p>
                            <p id="completionText"
                               class="text-xs text-slate-500 mt-1 leading-relaxed">
                                -
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 shrink-0">
                        <a href="{{ route('admin.data-pid') }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-xs font-black transition">
                            Lihat Data PID
                        </a>

                        <a id="downloadErrorButton"
                           href="#"
                           class="hidden inline-flex items-center justify-center px-4 py-2 rounded-xl bg-red-50 hover:bg-red-100 border border-red-200 text-red-700 text-xs font-black transition">
                            Download Error CSV
                        </a>

                        <a href="{{ route('admin.import.pid') }}"
                           class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-black hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            Import Baru
                        </a>
                    </div>
                </div>
            </div>

            {{-- RESULT COUNTER --}}
            <div id="importResultStats" class="hidden grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3 mt-5">
                @foreach([
                    ['importTotalRows', 'Total Row', 'slate'],
                    ['importProcessedRows', 'Diproses', 'blue'],
                    ['importValid', 'Valid', 'emerald'],
                    ['importInvalid', 'Invalid', 'red'],
                    ['importCreated', 'Created', 'blue'],
                    ['importUpdated', 'Updated', 'amber'],
                    ['importUnchanged', 'Tidak Berubah', 'indigo'],
                    ['importSkipped', 'Skip', 'slate'],
                ] as [$id, $label, $tone])
                    <div class="rounded-2xl bg-slate-50 dark:bg-slate-800 p-4">
                        <p class="text-[10px] font-black uppercase text-slate-500">{{ $label }}</p>
                        <p id="{{ $id }}" class="text-2xl font-black text-slate-900 dark:text-white mt-1">0</p>
                    </div>
                @endforeach
            </div>

            {{-- DETAIL SUMMARY PID --}}
            <div id="importSummaryBox" class="hidden mt-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-4">
                <p class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase">Detail Hasil</p>
                <div id="importSummaryDetail" class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3"></div>
            </div>

            {{-- FATAL ERROR --}}
            <div id="importFatalError" class="hidden mt-4 rounded-2xl bg-red-50 border border-red-200 p-4">
                <p class="text-xs font-black text-red-700 uppercase">Import Gagal</p>
                <p id="importFatalErrorMessage" class="text-xs text-red-600 mt-1 break-words"></p>
            </div>

            {{-- INVALID ROW PREVIEW --}}
            <div id="importErrorPreview" class="hidden mt-5 rounded-2xl border border-red-200 overflow-hidden">
                <div class="px-4 py-3 bg-red-50 border-b border-red-200">
                    <p class="text-xs font-black text-red-700">Preview Data Invalid</p>
                    <p class="text-[10px] text-red-600 mt-1">Menampilkan maksimal 10 error pertama.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-red-50/50">
                            <tr>
                                <th class="px-3 py-2 text-left">Row</th>
                                <th class="px-3 py-2 text-left">PID SAP</th>
                                <th class="px-3 py-2 text-left">ID IHLD</th>
                                <th class="px-3 py-2 text-left">Nama LOP</th>
                                <th class="px-3 py-2 text-left">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="importErrorRows" class="divide-y divide-red-100"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            {{-- MAIN UPLOAD --}}
            <div class="xl:col-span-8">
                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 md:p-6 shadow-sm">
                    <h2 class="text-lg font-black text-slate-900 dark:text-white">Upload File PID</h2>
                    <p class="text-sm text-slate-500 mt-1">
                        File hanya dikirim melalui request web. Parsing, validasi, dan penyimpanan data dilakukan oleh background worker.
                    </p>

                    <form id="importForm"
                          action="{{ route('admin.import.pid.upload') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="space-y-5 mt-6">
                        @csrf

                        <label for="file"
                               class="flex flex-col items-center justify-center min-h-[180px] rounded-[2rem] border-2 border-dashed border-blue-200 dark:border-slate-700 bg-blue-50/50 dark:bg-slate-950 cursor-pointer hover:bg-blue-50 dark:hover:bg-slate-800 transition">
                            <p class="text-base font-black text-slate-900 dark:text-white">Klik untuk pilih file PID</p>
                            <p id="fileName" class="text-sm text-slate-500 mt-1">Belum ada file dipilih</p>
                            <p id="fileMeta" class="text-[11px] text-slate-400 mt-1">Maksimal 100 MB</p>
                            <p class="text-xs text-slate-400 mt-3">Support: .xlsx, .xls, .csv</p>
                            <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv" required class="hidden" onchange="showSelectedFile(this)">
                        </label>

                        {{-- PROJECT TYPE --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">
                                Kategori Project <span class="text-red-500">*</span>
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <label class="flex items-center gap-2 p-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 cursor-pointer">
                                    <input type="radio" name="project_type" value="internal"
                                           {{ old('project_type', 'internal') === 'internal' ? 'checked' : '' }}
                                           onchange="toggleProjectType()">
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">TIF / Regular</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 cursor-pointer">
                                    <input type="radio" name="project_type" value="external"
                                           {{ old('project_type') === 'external' ? 'checked' : '' }}
                                           onchange="toggleProjectType()">
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Exbis</span>
                                </label>

                                <label class="flex items-center gap-2 p-3 rounded-2xl border border-emerald-200 bg-emerald-50 cursor-pointer">
                                    <input type="radio" name="project_type" value="pt2"
                                           {{ old('project_type') === 'pt2' ? 'checked' : '' }}
                                           onchange="toggleProjectType()">
                                    <span class="text-sm font-bold text-emerald-700">Program PT 2</span>
                                </label>
                            </div>
                        </div>

                        {{-- CUSTOMER EXTERNAL --}}
                        <div id="external_customer_wrapper" class="hidden">
                            <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-2">Customer Exbis</label>
                            <input type="hidden" name="customer_id" id="hidden_customer_id" value="{{ old('customer_id', 1) }}">

                            <select id="customer_id_select"
                                    onchange="document.getElementById('hidden_customer_id').value = this.value"
                                    class="w-full h-11 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm px-3">
                                <option value="">-- Pilih Customer Exbis --</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id_customer }}"
                                            {{ (string) old('customer_id') === (string) $customer->id_customer ? 'selected' : '' }}>
                                        {{ $customer->customer_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- INFO PT2 --}}
                        <div id="pt2ImportInfo" class="hidden rounded-2xl bg-emerald-50 border border-emerald-200 p-4">
                            <p class="text-xs font-black text-emerald-700 uppercase">Aturan Import PT 2</p>
                            <div class="text-xs text-emerald-700 mt-2 space-y-1 leading-relaxed">
                                <p>• Satu PID dapat memiliki banyak LOP.</p>
                                <p>• ID IHLD menjadi identitas LOP di dalam PID.</p>
                                <p>• Upload data identik tidak mereset progress, BOQ, evidence, atau assignment.</p>
                                <p>• PID sama + IHLD baru akan menambah LOP baru.</p>
                            </div>
                        </div>

                        <button id="uploadButton"
                                type="submit"
                                class="w-full sm:w-auto h-12 px-7 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-black shadow-lg shadow-blue-700/20 transition">
                            Upload & Proses Background
                        </button>
                    </form>

                    <div id="uploadingInfo" class="hidden mt-5 rounded-2xl bg-blue-50 border border-blue-100 p-4 text-sm font-bold text-blue-700">
                        File sedang dikirim ke server. Setelah upload selesai, proses akan dilanjutkan oleh background worker.
                    </div>
                </div>
            </div>

            {{-- SIDEBAR --}}
            <div class="xl:col-span-4 space-y-5">

                {{-- BACKGROUND QUEUE HEALTH --}}
                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">System Status</p>
                            <h2 class="text-sm font-black text-slate-900 dark:text-white mt-0.5">Background Queue</h2>
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
                            <p class="text-[9px] uppercase font-bold text-slate-400">Menunggu</p>
                            <p class="text-lg font-black text-amber-600 mt-0.5">
                                {{ number_format($queueHealth['queued_count'] ?? 0) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-slate-50 dark:bg-slate-950 p-2.5">
                            <p class="text-[9px] uppercase font-bold text-slate-400">Diproses</p>
                            <p class="text-lg font-black text-blue-600 mt-0.5">
                                {{ number_format($queueHealth['processing_count'] ?? 0) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-slate-50 dark:bg-slate-950 p-2.5">
                            <p class="text-[9px] uppercase font-bold text-slate-400">Driver</p>
                            <p class="text-[11px] font-black text-slate-700 dark:text-slate-200 mt-1.5 uppercase truncate">
                                {{ $queueHealth['driver'] ?? '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Mandatory Field</h2>
                    <div class="mt-4 space-y-3">
                        @foreach([
                            ['PID SAP', 'Identitas parent project.'],
                            ['ID IHLD', 'Identitas LOP. Wajib terutama untuk multi-LOP PT 2.'],
                            ['Nama LOP', 'Nama pekerjaan pada level LOP.'],
                        ] as [$label, $desc])
                            <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-4">
                                <p class="text-sm font-black text-emerald-700">{{ $label }}</p>
                                <p class="text-xs text-emerald-600 mt-1">{{ $desc }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Format Header</h2>
                    <p class="text-xs text-slate-500 mt-3 leading-relaxed font-mono break-words">
                        pid, pid_sap, project_name, nama_lop, program, execution_type, status_project, id_ihld, tematik, sto, branch, batch, no_sp, tgl_sp, tgl_toc, mitra_name
                    </p>
                    <p class="text-[10px] text-slate-400 mt-2">
                        project_name bersifat optional; untuk PT 2 digunakan sebagai nama parent project.
                    </p>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-black text-slate-900 dark:text-white">History Upload</h2>
                            <p class="text-[10px] text-slate-400 mt-0.5">5 proses PID terakhir dari semua user</p>
                        </div>
                        <span class="text-[9px] font-black text-slate-400 uppercase">Terbaru</span>
                    </div>

                    <div class="mt-3 space-y-2 max-h-[290px] overflow-y-auto pr-1">
                        @forelse($historyItems as $log)
                            @php
                                $historyStatus = strtolower($log->status ?? '');
                                $historyLabel = match ($historyStatus) {
                                    'queued' => 'Menunggu',
                                    'processing' => 'Diproses',
                                    'completed' => 'Selesai',
                                    'failed' => 'Gagal',
                                    'cancelled' => 'Batal',
                                    default => strtoupper($historyStatus ?: '-'),
                                };
                                $historyBadge = match ($historyStatus) {
                                    'queued' => 'bg-amber-100 text-amber-700',
                                    'processing' => 'bg-blue-100 text-blue-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'failed' => 'bg-red-100 text-red-700',
                                    'cancelled' => 'bg-slate-200 text-slate-700',
                                    default => 'bg-slate-100 text-slate-500',
                                };

                                $historyUploaderName = $log->uploader?->name
                                    ?? $log->uploader?->full_name
                                    ?? $log->uploader?->username
                                    ?? $log->uploader?->email
                                    ?? ($log->uploaded_by ? 'User #' . $log->uploaded_by : '-');

                                $historyUploaderInitials = $makeInitials($historyUploaderName);
                            @endphp

                            <a href="{{ route('admin.import.pid', ['import_uuid' => $log->uuid]) }}"
                               class="block rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950 px-3 py-2.5 hover:border-blue-200 hover:bg-blue-50/40 dark:hover:bg-slate-900 transition">
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
                                                <span class="font-bold text-slate-500">{{ $historyUploaderName }}</span>
                                                • {{ strtoupper($log->project_type ?? '-') }}
                                                • {{ $log->created_at?->timezone('Asia/Jakarta')->format('d M H:i') ?? '-' }} WIB
                                            </p>
                                        </div>
                                    </div>

                                    <span class="shrink-0 px-2 py-1 rounded-full text-[8px] font-black uppercase {{ $historyBadge }}">
                                        {{ $historyLabel }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2 text-[9px] text-slate-500">
                                    <span><b class="text-blue-600">{{ number_format($log->created_count ?? 0) }}</b> created</span>
                                    <span><b class="text-amber-600">{{ number_format($log->updated_count ?? 0) }}</b> updated</span>
                                    <span><b class="text-indigo-600">{{ number_format($log->unchanged_count ?? 0) }}</b> unchanged</span>
                                    @if(($log->invalid_rows ?? 0) > 0)
                                        <span><b class="text-red-600">{{ number_format($log->invalid_rows) }}</b> invalid</span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-950 px-3 py-4 text-center">
                                <p class="text-xs font-bold text-slate-500">Belum ada history upload PID.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const activeImportUuid = @js($activeImportUuid);
    const importStatusUrl = @js(
        $activeImportUuid
            ? route('admin.import.pid.status', $activeImportUuid)
            : null
    );

    function showSelectedFile(input) {
        const fileName = document.getElementById('fileName');
        const fileMeta = document.getElementById('fileMeta');
        const file = input.files?.[0];

        if (!fileName) return;

        if (!file) {
            fileName.innerText = 'Belum ada file dipilih';
            if (fileMeta) fileMeta.innerText = 'Maksimal 100 MB';
            return;
        }

        const extension = file.name.includes('.')
            ? file.name.split('.').pop().toUpperCase()
            : 'FILE';

        const sizeMb = file.size / (1024 * 1024);

        fileName.innerText = file.name;

        if (fileMeta) {
            fileMeta.innerText = `${extension} • ${sizeMb.toFixed(sizeMb >= 10 ? 1 : 2)} MB`;
        }
    }

    function toggleProjectType() {
        const checked = document.querySelector('input[name="project_type"]:checked');
        if (!checked) return;

        const type = checked.value;
        const external = document.getElementById('external_customer_wrapper');
        const customerSelect = document.getElementById('customer_id_select');
        const hiddenCustomer = document.getElementById('hidden_customer_id');
        const pt2Info = document.getElementById('pt2ImportInfo');

        if (!external || !customerSelect || !hiddenCustomer || !pt2Info) return;

        external.classList.add('hidden');
        pt2Info.classList.add('hidden');
        customerSelect.required = false;

        if (type === 'external') {
            external.classList.remove('hidden');
            customerSelect.required = true;
            hiddenCustomer.value = customerSelect.value || '';
            return;
        }

        hiddenCustomer.value = '1';

        if (type === 'pt2') {
            pt2Info.classList.remove('hidden');
        }
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = String(value ?? '');
        return div.innerHTML;
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('id-ID').format(Number(value || 0));
    }

    function setNumber(id, value) {
        const element = document.getElementById(id);
        if (element) element.innerText = formatNumber(value);
    }

    function statusLabel(status) {
        const labels = {
            queued: 'Menunggu',
            processing: 'Diproses',
            completed: 'Selesai',
            failed: 'Gagal',
            cancelled: 'Dibatalkan'
        };

        return labels[status] || String(status || '-').toUpperCase();
    }

    function renderStatusBadge(status) {
        const badge = document.getElementById('progressStatusBadge');
        if (!badge) return;

        const tone = {
            queued: 'bg-amber-50 text-amber-700',
            processing: 'bg-blue-50 text-blue-700',
            completed: 'bg-emerald-50 text-emerald-700',
            failed: 'bg-red-50 text-red-700',
            cancelled: 'bg-slate-100 text-slate-700'
        };

        badge.className = 'px-3 py-1 rounded-full text-[10px] font-black uppercase ' +
            (tone[status] || 'bg-slate-100 text-slate-600');
        badge.innerText = statusLabel(status);
    }

    function renderSummary(summary) {
        const box = document.getElementById('importSummaryBox');
        const detail = document.getElementById('importSummaryDetail');
        if (!box || !detail) return;

        const entries = Object.entries(summary || {})
            .filter(([, value]) => ['number', 'string'].includes(typeof value));

        if (!entries.length) {
            box.classList.add('hidden');
            detail.innerHTML = '';
            return;
        }

        box.classList.remove('hidden');
        detail.innerHTML = entries.map(([key, value]) => `
            <div class="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-3">
                <p class="text-[9px] uppercase font-black text-slate-500">
                    ${escapeHtml(String(key).replaceAll('_', ' '))}
                </p>
                <p class="text-base font-black text-slate-900 dark:text-white mt-1">
                    ${escapeHtml(String(value))}
                </p>
            </div>
        `).join('');
    }

    function renderErrors(errors) {
        const preview = document.getElementById('importErrorPreview');
        const rows = document.getElementById('importErrorRows');
        if (!preview || !rows) return;

        if (!Array.isArray(errors) || !errors.length) {
            preview.classList.add('hidden');
            rows.innerHTML = '';
            return;
        }

        preview.classList.remove('hidden');
        rows.innerHTML = errors.map(error => `
            <tr>
                <td class="px-3 py-2">${escapeHtml(error.row_number ?? '-')}</td>
                <td class="px-3 py-2">${escapeHtml(error.pid_sap ?? '-')}</td>
                <td class="px-3 py-2">${escapeHtml(error.id_ihld ?? '-')}</td>
                <td class="px-3 py-2">${escapeHtml(error.nama_lop ?? '-')}</td>
                <td class="px-3 py-2 text-red-700 font-semibold">${escapeHtml(error.message ?? '-')}</td>
            </tr>
        `).join('');
    }

    function renderFatalError(data) {
        const box = document.getElementById('importFatalError');
        const message = document.getElementById('importFatalErrorMessage');
        if (!box || !message) return;

        if (data.status === 'failed') {
            box.classList.remove('hidden');
            message.innerText = data.error_message || 'Import gagal diproses.';
            return;
        }

        box.classList.add('hidden');
        message.innerText = '';
    }

    function renderCompletionSummary(data) {
        const box = document.getElementById('importCompletionSummary');
        const icon = document.getElementById('completionIcon');
        const title = document.getElementById('completionTitle');
        const text = document.getElementById('completionText');
        const downloadButton = document.getElementById('downloadErrorButton');

        if (!box || !icon || !title || !text || !downloadButton) return;

        const isTerminal = ['completed', 'failed', 'cancelled'].includes(data.status);

        if (!isTerminal) {
            box.classList.add('hidden');
            downloadButton.classList.add('hidden');
            downloadButton.removeAttribute('href');
            return;
        }

        box.classList.remove('hidden');

        const processed = formatNumber(data.processed_rows);
        const valid = formatNumber(data.valid_rows);
        const invalid = formatNumber(data.invalid_rows);
        const created = formatNumber(data.created_count);
        const updated = formatNumber(data.updated_count);
        const unchanged = formatNumber(data.unchanged_count);
        const skipped = formatNumber(data.skipped_count);

        if (data.status === 'completed') {
            icon.className = 'w-10 h-10 shrink-0 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black';
            icon.innerText = '✓';

            title.innerText = Number(data.invalid_rows || 0) > 0
                ? 'Import selesai dengan catatan'
                : 'Import selesai';

            text.innerText =
                `${processed} row diproses, ${valid} valid, ${created} dibuat, ` +
                `${updated} diperbarui, ${unchanged} tidak berubah, ` +
                `${skipped} dilewati, dan ${invalid} invalid.`;
        } else if (data.status === 'failed') {
            icon.className = 'w-10 h-10 shrink-0 rounded-2xl bg-red-100 text-red-700 flex items-center justify-center font-black';
            icon.innerText = '!';

            title.innerText = 'Import gagal diproses';
            text.innerText = data.error_message
                || `${processed} row sempat diproses sebelum proses dihentikan.`;
        } else {
            icon.className = 'w-10 h-10 shrink-0 rounded-2xl bg-slate-200 text-slate-700 flex items-center justify-center font-black';
            icon.innerText = '×';

            title.innerText = 'Import dibatalkan';
            text.innerText = `${processed} row telah diproses sebelum import dibatalkan.`;
        }

        if (Number(data.invalid_rows || 0) > 0 && data.error_download_url) {
            downloadButton.href = data.error_download_url;
            downloadButton.classList.remove('hidden');
        } else {
            downloadButton.classList.add('hidden');
            downloadButton.removeAttribute('href');
        }
    }

    function renderImportStatus(data) {
        const card = document.getElementById('progressCard');
        const stats = document.getElementById('importResultStats');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressPercentText');
        const fileName = document.getElementById('progressFileName');
        const uploader = document.getElementById('progressUploader');
        const stage = document.getElementById('progressStage');

        card?.classList.remove('hidden');
        stats?.classList.remove('hidden');

        const progress = Math.max(0, Math.min(100, Number(data.progress || 0)));

        if (progressBar) progressBar.style.width = progress + '%';
        if (progressText) progressText.innerText = progress + '%';
        if (fileName) fileName.innerText = data.file_name || '-';
        if (uploader) {
            const uploaderName = data.uploader?.name || (data.uploaded_by ? `User #${data.uploaded_by}` : '-');
            uploader.innerText = `Uploader: ${uploaderName}`;
        }
        if (stage) stage.innerText = data.stage || statusLabel(data.status);

        setNumber('importTotalRows', data.total_rows);
        setNumber('importProcessedRows', data.processed_rows);
        setNumber('importValid', data.valid_rows);
        setNumber('importInvalid', data.invalid_rows);
        setNumber('importCreated', data.created_count);
        setNumber('importUpdated', data.updated_count);
        setNumber('importUnchanged', data.unchanged_count);
        setNumber('importSkipped', data.skipped_count);

        renderStatusBadge(data.status);
        renderSummary(data.summary || {});
        renderFatalError(data);
        renderErrors(data.errors || []);
        renderCompletionSummary(data);
    }

    async function checkImportStatus() {
        if (!activeImportUuid || !importStatusUrl) return;

        try {
            const response = await fetch(importStatusUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const payload = await response.json();
            if (!payload.success || !payload.data) {
                throw new Error('Response status import tidak valid.');
            }

            renderImportStatus(payload.data);

            if (!['completed', 'failed', 'cancelled'].includes(payload.data.status)) {
                setTimeout(checkImportStatus, 2000);
            }
        } catch (error) {
            console.error('Gagal membaca status import:', error);

            const stage = document.getElementById('progressStage');
            if (stage) {
                stage.innerText = 'Koneksi status terganggu. Mencoba kembali...';
            }

            setTimeout(checkImportStatus, 3000);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleProjectType();

        const importForm = document.getElementById('importForm');
        if (importForm) {
            importForm.addEventListener('submit', function () {
                const file = document.getElementById('file');
                if (!file?.files?.length) return;

                const button = document.getElementById('uploadButton');
                const info = document.getElementById('uploadingInfo');

                if (button) {
                    button.disabled = true;
                    button.classList.add('opacity-60', 'cursor-not-allowed');
                    button.innerText = 'Mengunggah file...';
                }

                info?.classList.remove('hidden');
            });
        }

        if (activeImportUuid && importStatusUrl) {
            checkImportStatus();
        }
    });
</script>
@endsection