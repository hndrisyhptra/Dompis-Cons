@extends('layouts.admin')

@section('content')
<div x-data="pidPage()" class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <p class="text-xs font-black text-blue-700 uppercase tracking-widest">PID Monitoring</p>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">Data PID</h1>
                    <p class="text-xs text-slate-500 mt-2 max-w-3xl">
                        Regular dan PT 2 dipisahkan agar struktur data tidak tercampur. PT 2 menggunakan satu PID parent dengan banyak LOP child.
                    </p>
                </div>

                <a href="{{ route('admin.import.pid') }}"
                   class="inline-flex items-center justify-center px-5 py-3 rounded-2xl bg-blue-700 text-white text-sm font-black hover:bg-blue-800 shadow-lg shadow-blue-700/20">
                    Bulk Import PID
                </a>
            </div>

            {{-- DATA TYPE TAB --}}
            <div class="mt-6 inline-flex p-1 rounded-2xl bg-slate-100 dark:bg-slate-800">
                <a href="{{ route('admin.data-pid', ['type' => 'regular']) }}"
                   class="px-5 py-2.5 rounded-xl text-sm font-black transition {{ $dataType === 'regular' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500' }}">
                    Regular
                </a>
                <a href="{{ route('admin.data-pid', ['type' => 'pt2']) }}"
                   class="px-5 py-2.5 rounded-xl text-sm font-black transition {{ $dataType === 'pt2' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500' }}">
                    Program PT 2
                </a>
            </div>

            {{-- KPI --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
                <div class="rounded-3xl bg-white border border-slate-200 p-5 shadow-sm">
                    <p class="text-xs text-slate-500 font-bold uppercase">Total PID</p>
                    <p class="text-3xl font-black text-slate-900 mt-2">{{ number_format($totalPid) }}</p>
                    <p class="text-[10px] text-slate-500 mt-1 uppercase font-semibold">Sesuai filter</p>
                </div>

                <div class="rounded-3xl bg-white border border-indigo-200 p-5 shadow-sm">
                    @if($dataType === 'pt2')
                        <p class="text-xs text-indigo-700 font-bold uppercase">Total LOP PT 2</p>
                        <p class="text-3xl font-black text-indigo-700 mt-2">{{ number_format($totalLop) }}</p>
                        <p class="text-[10px] text-indigo-600 mt-1 uppercase font-semibold">Child LOP sesuai filter PID</p>
                    @else
                        <p class="text-xs text-indigo-700 font-bold uppercase">PID Match BOQ</p>
                        <p class="text-3xl font-black text-indigo-700 mt-2">{{ number_format($pidMatchBoq) }}</p>
                        <p class="text-[10px] text-indigo-600 mt-1 uppercase font-semibold">Telah memiliki BOQ</p>
                    @endif
                </div>

                <div class="rounded-3xl bg-white border border-emerald-200 p-5 shadow-sm">
                    <p class="text-xs text-emerald-700 font-bold uppercase">Project Active</p>
                    <p class="text-3xl font-black text-emerald-700 mt-2">{{ number_format($projectActive) }}</p>
                    <p class="text-[10px] text-emerald-600 mt-1 uppercase font-semibold">Status project active</p>
                </div>

                <div class="rounded-3xl bg-white border border-red-200 p-5 shadow-sm">
                    <p class="text-xs text-red-700 font-bold uppercase">Project Drop</p>
                    <p class="text-3xl font-black text-red-700 mt-2">{{ number_format($projectDrop) }}</p>
                    <p class="text-[10px] text-red-600 mt-1 uppercase font-semibold">Project dibatalkan</p>
                </div>
            </div>

            @if($dataType === 'pt2')
                <div class="mt-4 rounded-2xl bg-emerald-50 border border-emerald-100 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <p class="text-xs text-emerald-700">
                        <b>{{ number_format($pidMatchBoq) }}</b> dari {{ number_format($totalPid) }} PID PT 2 sudah memiliki BOQ.
                    </p>
                    <p class="text-xs text-emerald-700">
                        Branch/STO PT 2 dibaca dari <b>pt2_lops</b>, bukan dari parent project.
                    </p>
                </div>
            @endif
        </div>

        {{-- FILTER --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.data-pid') }}" id="filterForm">
                <input type="hidden" name="type" value="{{ $dataType }}">

                <div class="mb-5">
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Pencarian</label>
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari PID, PID SAP, Project, Nama LOP, ID IHLD..."
                           class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm px-4">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Region</label>
                        <select name="region" id="regionSelect" onchange="updateBranchDropdown(); this.form.submit()"
                                class="w-full h-11 px-3 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-sm">
                            <option value="">Semua Region</option>
                            @foreach($regions as $region => $branches)
                                <option value="{{ $region }}" @selected(request('region') === $region)>{{ $region }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Branch</label>
                        <select name="branch" id="branchSelect" onchange="this.form.submit()"
                                class="w-full h-11 px-3 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-sm">
                            <option value="">Semua Branch</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Program</label>
                        <select name="program" onchange="this.form.submit()"
                                class="w-full h-11 px-3 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-sm">
                            <option value="">Semua Program</option>
                            @foreach($programs as $program)
                                <option value="{{ $program }}" @selected(request('program') === $program)>{{ $program }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Status Project</label>
                        <select name="status_project" onchange="this.form.submit()"
                                class="w-full h-11 px-3 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-sm">
                            <option value="">Semua Status</option>
                            @foreach(['init' => 'Init', 'active' => 'Active', 'close' => 'Close', 'bast' => 'BAST', 'drop' => 'Drop'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('status_project') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Per Page</label>
                        <select name="per_page" onchange="this.form.submit()"
                                class="w-full h-11 px-3 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-sm">
                            @foreach([10,20,50] as $size)
                                <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }} data</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    @if(request('search') || request('region') || request('branch') || request('program') || request('status_project'))
                        <a href="{{ route('admin.data-pid', ['type' => $dataType]) }}"
                           class="h-11 px-5 rounded-xl bg-slate-100 text-slate-700 text-sm font-bold inline-flex items-center">
                            Reset
                        </a>
                    @endif
                    <button type="submit" class="h-11 px-7 rounded-xl bg-blue-600 text-white text-sm font-black hover:bg-blue-700">Cari</button>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">
                        {{ $dataType === 'pt2' ? 'List Parent PID PT 2' : 'List PID Regular' }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $dataType === 'pt2' ? 'Klik jumlah LOP untuk membuka child LOP dalam PID.' : 'Regular menggunakan satu LOP untuk setiap PID.' }}
                    </p>
                </div>
                <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-black">{{ number_format($projects->total()) }} PID</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">PID</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">PID SAP</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">Project</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">Program</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">LOP</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">Status</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">Aksi</th>
                        </tr>
                    </thead>

                    @forelse($projects as $project)
                        @php
                            $projectLops = $lopGroups->get($project->id_project, collect());
                            $firstLop = $projectLops->first();
                            $status = strtolower($project->status_project ?? 'active');

                            $detailData = $dataType === 'regular' ? [
                                'id_project' => $project->id_project,
                                'pid' => $project->pid ?? '-',
                                'pid_sap' => $project->pid_sap ?? '-',
                                'project_name' => $project->project_name ?? '-',
                                'program' => $project->program ?? '-',
                                'execution_type' => $project->execution_type ?? '-',
                                'status_project' => $project->status_project ?? '-',
                                'id_ihld' => $firstLop?->id_ihld ?? '-',
                                'lop_name' => $firstLop?->lop_name ?? $project->project_name ?? '-',
                                'sto' => $firstLop?->sto ?? '-',
                                'branch' => $firstLop?->branch ?? '-',
                                'tematik' => $firstLop?->tematik ?? '-',
                                'batch' => $firstLop?->batch ?? '-',
                                'mitra_name' => $firstLop?->mitra_name ?? '-',
                                'no_sp' => $firstLop?->no_sp ?? '-',
                                'tgl_sp' => $firstLop?->tgl_sp ?? '-',
                                'tgl_toc' => $firstLop?->tgl_toc ?? '-',
                            ] : [];
                        @endphp

                        <tbody x-data="{ openLops: false }" class="divide-y divide-slate-200 dark:divide-slate-800">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-black text-slate-900 dark:text-white">{{ $project->pid ?? '-' }}</p>
                                    <p class="text-[10px] text-slate-400">ID {{ $project->id_project }}</p>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-black">{{ $project->pid_sap ?? '-' }}</span>
                                </td>

                                <td class="px-5 py-4 min-w-[240px]">
                                    <p class="font-black text-slate-900 dark:text-white">{{ $project->project_name ?? '-' }}</p>
                                    @if($dataType === 'pt2')
                                        <p class="text-[10px] text-emerald-600 font-bold mt-1">PARENT PROJECT PT 2</p>
                                    @else
                                        <p class="text-xs text-slate-500 mt-1">{{ $firstLop?->branch ?? '-' }} · {{ $firstLop?->sto ?? '-' }}</p>
                                    @endif
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full {{ $dataType === 'pt2' ? 'bg-emerald-50 text-emerald-700' : 'bg-indigo-50 text-indigo-700' }} text-xs font-black">
                                        {{ $project->program ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 min-w-[220px]">
                                    @if($dataType === 'pt2')
                                        <button type="button" @click="openLops = !openLops"
                                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-black hover:bg-emerald-100">
                                            <span>{{ $projectLops->count() }} LOP</span>
                                            <span x-text="openLops ? '▲' : '▼'"></span>
                                        </button>
                                        @if($projectLops->isNotEmpty())
                                            <p class="text-[10px] text-slate-500 mt-1">
                                                {{ $projectLops->pluck('branch')->filter()->unique()->take(3)->implode(', ') }}
                                            </p>
                                        @endif
                                    @else
                                        <p class="font-bold text-slate-900 dark:text-white">{{ $firstLop?->lop_name ?? '-' }}</p>
                                        <p class="text-xs text-slate-500 mt-1">IHLD: {{ $firstLop?->id_ihld ?? '-' }}</p>
                                    @endif
                                </td>

                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full text-xs font-black
                                        {{ $status === 'active' ? 'bg-emerald-50 text-emerald-700' : '' }}
                                        {{ $status === 'init' ? 'bg-blue-50 text-blue-700' : '' }}
                                        {{ $status === 'close' ? 'bg-slate-100 text-slate-700' : '' }}
                                        {{ $status === 'bast' ? 'bg-amber-50 text-amber-700' : '' }}
                                        {{ $status === 'drop' ? 'bg-red-50 text-red-700' : '' }}">
                                        {{ strtoupper($project->status_project ?? '-') }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    @if($dataType === 'regular')
                                        <div class="inline-flex gap-2">
                                            <button type="button" @click="openDetail(@js($detailData))"
                                                    class="px-3 py-2 rounded-xl bg-blue-50 text-blue-700 text-xs font-black hover:bg-blue-100">Detail</button>
                                            <button type="button" @click="openEdit(@js($detailData))"
                                                    class="px-3 py-2 rounded-xl bg-amber-50 text-amber-700 text-xs font-black hover:bg-amber-100">Edit</button>
                                            <form action="{{ route('admin.import.pid.delete', $project->id_project) }}" method="POST"
                                                  onsubmit="return confirm('Yakin hapus project Regular ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="px-3 py-2 rounded-xl bg-red-50 text-red-700 text-xs font-black hover:bg-red-100">Delete</button>
                                            </form>
                                        </div>
                                    @else
                                        <button type="button" @click="openLops = !openLops"
                                                class="px-3 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-black hover:bg-slate-200">
                                            Lihat LOP
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            {{-- CHILD LOP PT2 --}}
                            @if($dataType === 'pt2')
                                <tr x-show="openLops" x-cloak>
                                    <td colspan="7" class="p-0 bg-emerald-50/30 dark:bg-emerald-950/10">
                                        <div class="p-4 md:p-5">
                                            <div class="flex items-center justify-between gap-3 mb-3">
                                                <div>
                                                    <p class="text-xs font-black text-emerald-700 uppercase">LOP dalam PID {{ $project->pid_sap }}</p>
                                                    <p class="text-[10px] text-slate-500 mt-1">Assignment Teknisi, BOQ, evidence, survey, mancore dan dismantle harus mengacu ke pt2_lop_id.</p>
                                                </div>
                                            </div>

                                            <div class="overflow-x-auto rounded-2xl border border-emerald-100 bg-white">
                                                <table class="w-full text-xs">
                                                    <thead class="bg-emerald-50">
                                                        <tr>
                                                            <th class="px-4 py-3 text-left">ID IHLD</th>
                                                            <th class="px-4 py-3 text-left">Nama LOP</th>
                                                            <th class="px-4 py-3 text-left">Branch / STO</th>
                                                            <th class="px-4 py-3 text-left">Batch</th>
                                                            <th class="px-4 py-3 text-center">Progress</th>
                                                            <th class="px-4 py-3 text-center">Teknisi</th>
                                                            <th class="px-4 py-3 text-center">BOQ</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @forelse($projectLops as $lop)
                                                            <tr>
                                                                <td class="px-4 py-3 font-black text-blue-700">{{ $lop->id_ihld ?? '-' }}</td>
                                                                <td class="px-4 py-3 font-bold text-slate-900">{{ $lop->lop_name ?? '-' }}</td>
                                                                <td class="px-4 py-3">
                                                                    <b>{{ strtoupper($lop->branch ?? '-') }}</b>
                                                                    <div class="text-[10px] text-slate-500">{{ strtoupper($lop->sto ?? '-') }}</div>
                                                                </td>
                                                                <td class="px-4 py-3">{{ $lop->batch ?? '-' }}</td>
                                                                <td class="px-4 py-3 text-center">
                                                                    <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-bold">
                                                                        {{ strtoupper($lop->status_progress ?? 'preparation') }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-4 py-3 text-center">
                                                                    @if((int) $lop->assignment_count > 0)
                                                                        <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-black">
                                                                            {{ $lop->assignment_count }} assignment
                                                                        </span>
                                                                    @else
                                                                        <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-black">Belum Assign</span>
                                                                    @endif
                                                                </td>
                                                                <td class="px-4 py-3 text-center font-black text-indigo-700">{{ number_format($lop->boq_count) }} item</td>
                                                            </tr>
                                                        @empty
                                                            <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada LOP PT 2.</td></tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">Belum ada data PID sesuai filter.</td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>

            @if($projects->hasPages())
                <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $projects->links() }}
                </div>
            @endif
        </div>

        {{-- MATRIX REGULAR ONLY --}}
        @if($dataType === 'regular')
            <div x-data="{ openMatrix: false }" class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <button type="button" @click="openMatrix = !openMatrix"
                        class="w-full px-6 py-5 flex justify-between items-center bg-slate-50/50 hover:bg-slate-100/50">
                    <div class="text-left">
                        <h2 class="text-sm font-black uppercase tracking-wider text-slate-800">Matriks Status Project Regular</h2>
                        <p class="text-xs text-slate-500 mt-1">Regular-only. PT 2 tidak dicampur karena satu PID dapat mencakup banyak branch.</p>
                    </div>
                    <span x-text="openMatrix ? '▲' : '▼'" class="text-slate-500"></span>
                </button>

                <div x-show="openMatrix" x-cloak class="overflow-x-auto border-t border-slate-200">
                    <table class="w-full text-[10px] sm:text-xs">
                        <thead class="bg-slate-100/70">
                            <tr>
                                <th rowspan="2" class="px-5 py-3 text-left sticky left-0 bg-slate-100 z-10">Wilayah</th>
                                @foreach($programs as $program)
                                    <th colspan="5" class="px-3 py-2 text-center border-l border-slate-200">{{ $program }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($programs as $program)
                                    <th class="px-2 py-2 text-blue-600">Init</th>
                                    <th class="px-2 py-2 text-amber-600">Active</th>
                                    <th class="px-2 py-2 text-indigo-600">Close</th>
                                    <th class="px-2 py-2 text-emerald-600">BAST</th>
                                    <th class="px-2 py-2 text-red-600">Drop</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @foreach($matrixData as $i => $region)
                                <tr class="bg-white font-bold">
                                    <td class="px-5 py-3 sticky left-0 bg-white z-10">{{ $region['region'] }}</td>
                                    @foreach($programs as $program)
                                        @php $stats = $region['programs'][$program]; @endphp
                                        @foreach(['init','active','close','bast','drop'] as $statusKey)
                                            <td class="px-2 py-3 text-center">{{ $stats[$statusKey] ?: '-' }}</td>
                                        @endforeach
                                    @endforeach
                                </tr>

                                @foreach($region['branches'] as $branch)
                                    <tr class="bg-slate-50/60 text-slate-600">
                                        <td class="px-5 py-2 pl-9 sticky left-0 bg-slate-50 z-10">↳ {{ $branch['name'] }}</td>
                                        @foreach($programs as $program)
                                            @php $stats = $branch['programs'][$program]; @endphp
                                            @foreach(['init','active','close','bast','drop'] as $statusKey)
                                                <td class="px-2 py-2 text-center">{{ $stats[$statusKey] ?: '-' }}</td>
                                            @endforeach
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>

                        @if(!empty($grandTotals))
                            <tfoot class="bg-slate-100 font-black border-t-2 border-slate-300">
                                <tr>
                                    <td class="px-5 py-4 sticky left-0 bg-slate-100 z-10">GRAND TOTAL</td>
                                    @foreach($programs as $program)
                                        @foreach(['init','active','close','bast','drop'] as $statusKey)
                                            <td class="px-2 py-4 text-center">{{ $grandTotals[$program][$statusKey] ?: '-' }}</td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- Existing modal partial dipakai hanya untuk Regular --}}
    @if($dataType === 'regular')
        @include('admin.import.partials.pid-modals')
    @endif
</div>

<script>
    const regionMapping = @json($regions);

    function updateBranchDropdown() {
        const region = document.getElementById('regionSelect').value;
        const branchSelect = document.getElementById('branchSelect');
        const current = @json((string) request('branch'));

        branchSelect.innerHTML = '<option value="">Semua Branch</option>';

        (regionMapping[region] || []).forEach(branch => {
            const option = document.createElement('option');
            option.value = branch;
            option.textContent = branch;
            option.selected = branch.toUpperCase() === current.toUpperCase();
            branchSelect.appendChild(option);
        });
    }

    function pidPage() {
        return {
            showDetail: false,
            showEdit: false,
            selected: {},

            get projectFields() {
                return [
                    { label: 'PID', value: this.selected.pid || '-' },
                    { label: 'PID SAP', value: this.selected.pid_sap || '-' },
                    { label: 'Program', value: this.selected.program || '-' },
                    { label: 'Execution Type', value: this.selected.execution_type || '-' },
                    { label: 'Status Project', value: this.selected.status_project || '-' },
                ];
            },

            get lopFields() {
                return [
                    { label: 'Nama LOP', value: this.selected.lop_name || '-' },
                    { label: 'ID IHLD', value: this.selected.id_ihld || '-' },
                    { label: 'STO', value: this.selected.sto || '-' },
                    { label: 'Branch', value: this.selected.branch || '-' },
                    { label: 'Tematik', value: this.selected.tematik || '-' },
                    { label: 'Batch', value: this.selected.batch || '-' },
                    { label: 'Mitra', value: this.selected.mitra_name || '-' },
                    { label: 'No SP', value: this.selected.no_sp || '-' },
                    { label: 'Tanggal SP', value: this.selected.tgl_sp || '-' },
                    { label: 'Tanggal TOC', value: this.selected.tgl_toc || '-' },
                ];
            },

            openDetail(data) {
                this.selected = data;
                this.showDetail = true;
                document.body.classList.add('overflow-hidden');
            },

            openEdit(data) {
                this.selected = data;
                this.showEdit = true;
                document.body.classList.add('overflow-hidden');
            },

            close() {
                this.showDetail = false;
                this.showEdit = false;
                document.body.classList.remove('overflow-hidden');
            },
        }
    }

    document.addEventListener('DOMContentLoaded', updateBranchDropdown);
</script>
@endsection
