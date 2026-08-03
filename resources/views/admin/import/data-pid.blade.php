@extends('layouts.admin')

@section('content')

<div x-data="pidPage()" class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6">

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <p class="text-xs font-black text-blue-700 uppercase tracking-widest">
                        PID Monitoring
                    </p>

                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">
                        Data PID
                    </h1>

                    <p class="text-xs text-slate-500 mt-2 max-w-2xl">
                        Daftar data PID dan LOP hasil bulk import. Gunakan halaman ini untuk review, edit, dan validasi data project.
                    </p>
                </div>

                <a href="{{ route('admin.import.pid') }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-blue-700 text-white text-sm font-black hover:bg-blue-800 shadow-lg shadow-blue-700/20">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-upload-icon lucide-cloud-upload">
                            <path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/>
                        </svg>
                    </span>
                    <span>Bulk Import PID</span>
                </a>
            </div>
            {{-- WIDGET CARD KPI (Dinamis Berdasarkan Filter) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">

                {{-- CARD 1: TOTAL PID --}}
                <div class="rounded-3xl bg-white border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">Total Project (PID)</p>
                            <p class="text-3xl font-black text-slate-900 mt-2">{{ number_format($totalPid) }}</p>
                            <p class="text-[10px] text-slate-500 mt-1 font-semibold tracking-wide uppercase">Total sesuai filter</p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-2xl text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-1.22-1.82A2 2 0 0 0 7.53 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/><path d="M8 10v4"/><path d="M12 10v2"/><path d="M16 10v6"/></svg>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: PID MATCH BOQ (BARU) --}}
                <div class="rounded-3xl bg-white border border-slate-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-500 font-bold uppercase">PID Match BOQ</p>
                            <p class="text-3xl font-black text-slate-900 mt-2">{{ number_format($pidMatchBoq) }}</p>
                            <p class="text-[10px] text-slate-500 mt-1 font-semibold tracking-wide uppercase">Telah Memiliki BOQ</p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-2xl text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="m9 15 2 2 4-4"/></svg>
                        </div>
                    </div>
                </div>

                {{-- CARD 3: PROJECT ACTIVE (BARU) --}}
                <div class="rounded-3xl bg-white border border-emerald-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-emerald-700 font-bold uppercase">Project Active</p>
                            <p class="text-3xl font-black text-emerald-700 mt-2">{{ number_format($projectActive) }}</p>
                            <p class="text-[10px] text-emerald-600/70 mt-1 font-semibold tracking-wide uppercase">Status Project: Active</p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-2xl text-emerald-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/></svg>
                        </div>
                    </div>
                </div>

                {{-- CARD 4: PROJECT DROP --}}
                <div class="rounded-3xl bg-white border border-red-200 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-red-700 font-bold uppercase">Project Drop</p>
                            <p class="text-3xl font-black text-red-700 mt-2">{{ number_format($projectDrop) }}</p>
                            <p class="text-[10px] text-red-600/70 mt-1 font-semibold tracking-wide uppercase">Proyek Dibatalkan</p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center text-2xl text-red-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- FILTER PANEL KOMPREHENSIF --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <form method="GET" action="{{ route('admin.data-pid') }}" id="filterForm">
                
                {{-- Baris Pertama: Pencarian --}}
                <div class="mb-5">
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Pencarian Bebas</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">🔎</span>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari PID, PID SAP, Nama LOP, ID IHLD..."
                               class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm pl-11 pr-4 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                {{-- Baris Kedua: Dropdown Filters --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- Region --}}
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Region</label>
                        <select name="region" id="regionSelect" onchange="updateBranchDropdown(); document.getElementById('filterForm').submit();"
                                class="w-full h-11 px-4 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm focus:ring-blue-500 cursor-pointer">
                            <option value="">Semua Region</option>
                            <option value="JATIM" {{ request('region') == 'JATIM' ? 'selected' : '' }}>JATIM</option>
                            <option value="JATENG DIY" {{ request('region') == 'JATENG DIY' ? 'selected' : '' }}>JATENG DIY</option>
                            <option value="BALNUS" {{ request('region') == 'BALNUS' ? 'selected' : '' }}>BALNUS</option>
                        </select>
                    </div>

                    {{-- Branch --}}
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Branch</label>
                        <select name="branch" id="branchSelect" onchange="document.getElementById('filterForm').submit()"
                                class="w-full h-11 px-4 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm focus:ring-blue-500 cursor-pointer">
                            <option value="">Semua Branch</option>
                            {{-- Diisi via JS --}}
                        </select>
                    </div>

                    {{-- Program --}}
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Program</label>
                        <select name="program" onchange="document.getElementById('filterForm').submit()"
                                class="w-full h-11 px-4 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm focus:ring-blue-500 cursor-pointer">
                            <option value="">Semua Program</option>
                            @foreach($programs ?? [] as $prog)
                                <option value="{{ $prog }}" {{ request('program') == $prog ? 'selected' : '' }}>{{ $prog }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Project (Termasuk Drop) --}}
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Status Project</label>
                        <select name="status_project" onchange="document.getElementById('filterForm').submit()"
                                class="w-full h-11 px-4 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm focus:ring-blue-500 cursor-pointer">
                            <option value="">Semua Status</option>
                            <option value="init" {{ request('status_project') == 'init' ? 'selected' : '' }}>Init</option>
                            <option value="active" {{ request('status_project') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="close" {{ request('status_project') == 'close' ? 'selected' : '' }}>Close</option>
                            <option value="bast" {{ request('status_project') == 'bast' ? 'selected' : '' }}>BAST</option>
                            <option value="drop" {{ request('status_project') == 'drop' ? 'selected' : '' }}>Drop (Batal)</option>
                        </select>
                    </div>
                </div>

                {{-- Baris Ketiga: Aksi --}}
                <div class="mt-5 flex justify-end gap-3">
                    @if(request('search') || request('region') || request('branch') || request('program') || request('status_project'))
                        <a href="{{ route('admin.data-pid') }}" class="h-11 px-6 rounded-xl bg-slate-100 text-slate-700 text-sm font-bold flex items-center justify-center hover:bg-slate-200 transition">
                            Reset Filter
                        </a>
                    @endif
                    <button type="submit" class="h-11 px-8 rounded-xl bg-blue-600 text-white text-sm font-black hover:bg-blue-700 shadow-md flex items-center justify-center">
                        Terapkan Filter
                    </button>
                </div>
            </form>
        </div>

        {{-- TABLE DATA PID --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">List Data PID</h2>
                    <p class="text-xs text-slate-500 mt-1">Menampilkan data project terbaru.</p>
                </div>
                <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-black">
                    {{ $projects->total() }} data
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">PID</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">PID SAP</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">Nama LOP</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">STO / Branch</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">Program</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">Status</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($projects as $project)
                            @php
                                $lop = $project->lop;

                                $detailData = [
                                    'id_project' => $project->id_project,
                                    'pid' => $project->pid ?? '-',
                                    'pid_sap' => $project->pid_sap ?? '-',
                                    'project_name' => $project->project_name ?? '-',
                                    'program' => $project->program ?? '-',
                                    'execution_type' => $project->execution_type ?? '-',
                                    'status_project' => $project->status_project ?? '-',
                                    'id_ihld' => $lop?->id_ihld ?? '-',
                                    'lop_name' => $lop?->lop_name ?? $project->project_name ?? '-',
                                    'sto' => $lop?->sto ?? '-',
                                    'branch' => $lop?->branch ?? '-',
                                    'tematik' => $lop?->tematik ?? '-',
                                    'batch' => $lop?->batch ?? '-',
                                    'mitra_name' => $lop?->mitra_name ?? '-',
                                    'no_sp' => $lop?->no_sp ?? '-',
                                    'tgl_sp' => $lop?->tgl_sp ?? '-',
                                    'tgl_toc' => $lop?->tgl_toc ?? '-',
                                    'update_url' => route('admin.import.pid.update', $project->id_project),
                                    'delete_url' => route('admin.import.pid.delete', $project->id_project),
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-black text-slate-900 dark:text-white">{{ $project->pid ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">ID {{ $project->id_project }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-black">
                                        {{ $project->pid_sap ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 min-w-[260px]">
                                    <p class="font-black text-slate-900 dark:text-white truncate max-w-sm">{{ $project->project_name ?? '-' }}</p>
                                    <p class="text-xs text-slate-500 mt-1">IHLD: {{ $lop?->id_ihld ?? '-' }} · Mitra: {{ $lop?->mitra_name ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $lop?->sto ?? '-' }}</p>
                                    <p class="text-xs text-slate-500">{{ $lop?->branch ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-black">
                                        {{ $project->program ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    @php
                                        $status = strtolower($project->status_project ?? 'active');
                                    @endphp
                                    <span class="px-3 py-1.5 rounded-full text-xs font-black
                                        {{ $status === 'active' ? 'bg-emerald-50 text-emerald-700' : '' }}
                                        {{ $status === 'init' ? 'bg-blue-50 text-blue-700' : '' }}
                                        {{ $status === 'close' ? 'bg-slate-100 text-slate-700' : '' }}
                                        {{ $status === 'bast' ? 'bg-amber-50 text-amber-700' : '' }}
                                        {{ $status === 'drop' ? 'bg-red-50 text-red-700' : '' }}
                                        {{ !in_array($status, ['active','init','close','bast','drop']) ? 'bg-slate-100 text-slate-700' : '' }}">
                                        {{ strtoupper($project->status_project ?? '-') }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div class="relative inline-block text-left"
                                        x-data="{ openMenu: false, btnRect: {}, dropUp: false}" @scroll.window="openMenu = false" @resize.window="openMenu = false" @close-menus.window="openMenu = false">
                                        
                                        <button type="button" x-ref="btn"
                                                @click.stop="
                                                    if (openMenu) { openMenu = false; } else {
                                                        $dispatch('close-menus'); 
                                                        btnRect = $refs.btn.getBoundingClientRect();
                                                        dropUp = (window.innerHeight - btnRect.bottom) < 220;
                                                        openMenu = true;
                                                    }
                                                "
                                                class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 font-black">
                                            ⋮
                                        </button>
                                        <template x-teleport="body">
                                            <div x-show="openMenu" @click.outside="openMenu = false" x-cloak
                                                :style="`position: fixed; left: ${btnRect.right}px; top: ${dropUp ? (btnRect.top - 8) : (btnRect.bottom + 8)}px; transform: ${dropUp ? 'translate(-100%, -100%)' : 'translateX(-100%)'};`"
                                                class="w-44 rounded-2xl bg-white border border-slate-200 shadow-2xl z-[9999] overflow-hidden">
                                                <button type="button" @click="openMenu = false; openDetail(@js($detailData))" class="w-full text-left px-4 py-3 text-sm font-bold hover:bg-slate-50 transition-colors">Detail</button>
                                                <button type="button" @click="openMenu = false; openEdit(@js($detailData))" class="w-full text-left px-4 py-3 text-sm font-bold text-amber-700 hover:bg-amber-50 transition-colors">Edit</button>
                                                <form action="{{ route('admin.import.pid.delete', $project->id_project) }}" method="POST" onsubmit="return confirm('Yakin hapus project ini? Semua BOQ dan assignment akan ikut terhapus.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="w-full text-left px-4 py-3 text-sm font-bold text-red-700 hover:bg-red-50 transition-colors">Delete</button>
                                                </form>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="w-16 h-16 rounded-3xl bg-slate-100 mx-auto flex items-center justify-center text-2xl mb-4">📋</div>
                                    <p class="text-sm font-black text-slate-700">Belum ada data PID</p>
                                    <p class="text-xs text-slate-500 mt-1">Silakan sesuaikan filter atau import PID.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- PAGINATION --}}
            @if ($projects->hasPages())
                <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $projects->appends(request()->query())->links() }}
                </div>
            @endif
        </div>
        {{-- TABEL MATRIX STATUS PROJECT PER PROGRAM (UNFILTERED) --}}
        <div x-data="{ openMatrix: false }" class="mt-8 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <button @click="openMatrix = !openMatrix" type="button" class="w-full px-6 py-5 flex justify-between items-center bg-slate-50/50 hover:bg-slate-100/50 transition">
                <div class="text-left">
                    <h2 class="text-sm font-black uppercase tracking-wider text-slate-800">Matriks Status Project per Program</h2>
                    <p class="text-xs text-slate-500 mt-1">Data global (unfiltered). Menghitung jumlah siklus hidup project.</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 transition-transform duration-300" :class="openMatrix ? 'rotate-180' : ''">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </div>
            </button>
            
            <div x-show="openMatrix" x-collapse x-cloak>
                <div class="overflow-x-auto pb-4 border-t border-slate-200">
                    <table class="w-full text-[10px] sm:text-xs border-collapse">
                        <thead class="bg-slate-100/60 text-slate-500 font-bold uppercase tracking-wider text-[9px] sm:text-[10px]">
                            <tr>
                                <th rowspan="2" class="px-6 py-3 text-left border-r border-slate-200/60 align-middle whitespace-nowrap sticky left-0 bg-slate-100/90 z-10">Wilayah</th>
                                @foreach($programs ?? [] as $prog)
                                    {{-- Colspan 5 karena ada: Init, Active, Close, Bast, Drop --}}
                                    <th colspan="5" class="px-3 py-2 text-center border-b border-r border-slate-200/60 whitespace-nowrap">{{ $prog }}</th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($programs ?? [] as $prog)
                                    <th class="px-2 py-2 text-center text-blue-600 bg-blue-50/50">Init</th>
                                    <th class="px-2 py-2 text-center text-amber-600 bg-amber-50/50">Active</th>
                                    <th class="px-2 py-2 text-center text-indigo-600 bg-indigo-50/50">Close</th>
                                    <th class="px-2 py-2 text-center text-emerald-600 bg-emerald-50/50">Bast</th>
                                    <th class="px-2 py-2 text-center text-red-600 bg-red-50/50 border-r border-slate-200/60">Drop</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            @forelse($matrixData ?? [] as $i => $reg)
                                {{-- Baris Region --}}
                                <tr class="cursor-pointer bg-white hover:bg-slate-50 transition group" onclick="toggleRegion('pid-matrix-{{ $i }}', 'icon-pid-{{ $i }}')">
                                    <td class="px-6 py-4 border-r border-slate-200/60 sticky left-0 bg-white group-hover:bg-slate-50 z-10">
                                        <div class="flex items-center gap-3">
                                            <div class="w-5 h-5 flex items-center justify-center rounded bg-indigo-100 text-indigo-600">
                                                <svg id="icon-pid-{{ $i }}" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200"><path d="m9 18 6-6-6-6"/></svg>
                                            </div>
                                            <span class="font-black text-slate-800 text-sm whitespace-nowrap">{{ $reg['region'] }}</span>
                                        </div>
                                    </td>
                                    @foreach($programs as $prog)
                                        @php $stats = $reg['programs'][$prog]; @endphp
                                        <td class="px-2 py-3 text-center font-bold text-slate-700 bg-blue-50/20">{{ $stats['init'] ?: '-' }}</td>
                                        <td class="px-2 py-3 text-center font-bold text-slate-700 bg-amber-50/20">{{ $stats['active'] ?: '-' }}</td>
                                        <td class="px-2 py-3 text-center font-bold text-slate-700 bg-indigo-50/20">{{ $stats['close'] ?: '-' }}</td>
                                        <td class="px-2 py-3 text-center font-bold text-slate-700 bg-emerald-50/20">{{ $stats['bast'] ?: '-' }}</td>
                                        <td class="px-2 py-3 text-center font-black text-red-700 bg-red-50/20 border-r border-slate-200/60">{{ $stats['drop'] ?: '-' }}</td>
                                    @endforeach
                                </tr>
                                {{-- Baris Anak Branch --}}
                                @foreach($reg['branches'] as $br)
                                    <tr class="hidden bg-slate-50/50 hover:bg-slate-100/50 transition pid-matrix-{{ $i }} group-branch">
                                        <td class="px-6 py-3 pl-[3.25rem] border-r border-slate-200/60 sticky left-0 bg-slate-50/90 z-10">
                                            <span class="font-bold text-slate-600 whitespace-nowrap">↳ {{ $br['name'] }}</span>
                                        </td>
                                        @foreach($programs as $prog)
                                            @php $stats = $br['programs'][$prog]; @endphp
                                            <td class="px-2 py-2 text-center text-blue-600 font-semibold">{{ $stats['init'] ?: '-' }}</td>
                                            <td class="px-2 py-2 text-center text-amber-600 font-semibold">{{ $stats['active'] ?: '-' }}</td>
                                            <td class="px-2 py-2 text-center text-indigo-600 font-semibold">{{ $stats['close'] ?: '-' }}</td>
                                            <td class="px-2 py-2 text-center text-emerald-600 font-semibold">{{ $stats['bast'] ?: '-' }}</td>
                                            <td class="px-2 py-2 text-center text-red-600 font-bold border-r border-slate-200/60">{{ $stats['drop'] ?: '-' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="{{ 1 + (count($programs ?? []) * 5) }}" class="px-6 py-10 text-center text-slate-400 font-medium">Tidak ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        
                        {{-- MENGHITUNG DAN MENAMPILKAN GRAND TOTAL --}}
                        @if(!empty($matrixData))
                            @php
                                // Inisialisasi array untuk menampung total per program & status
                                $grandTotals = [];
                                foreach($programs as $prog) {
                                    $grandTotals[$prog] = ['init' => 0, 'active' => 0, 'close' => 0, 'bast' => 0, 'drop' => 0];
                                }
                                
                                // Looping data Region untuk menjumlahkan totalnya
                                foreach($matrixData as $reg) {
                                    foreach($programs as $prog) {
                                        $grandTotals[$prog]['init'] += $reg['programs'][$prog]['init'];
                                        $grandTotals[$prog]['active'] += $reg['programs'][$prog]['active'];
                                        $grandTotals[$prog]['close'] += $reg['programs'][$prog]['close'];
                                        $grandTotals[$prog]['bast'] += $reg['programs'][$prog]['bast'];
                                        $grandTotals[$prog]['drop'] += $reg['programs'][$prog]['drop'];
                                    }
                                }
                            @endphp
                            
                            <tfoot class="bg-slate-100 dark:bg-slate-800 border-t-2 border-slate-300 dark:border-slate-700">
                                <tr>
                                    <td class="px-6 py-4 border-r border-slate-300/60 sticky left-0 bg-slate-100 dark:bg-slate-800 z-10 text-slate-800 dark:text-white font-black uppercase text-xs">
                                        GRAND TOTAL
                                    </td>
                                    @foreach($programs as $prog)
                                        <td class="px-2 py-4 text-center font-black text-blue-700 bg-blue-100/50">{{ $grandTotals[$prog]['init'] ?: '-' }}</td>
                                        <td class="px-2 py-4 text-center font-black text-amber-700 bg-amber-100/50">{{ $grandTotals[$prog]['active'] ?: '-' }}</td>
                                        <td class="px-2 py-4 text-center font-black text-indigo-700 bg-indigo-100/50">{{ $grandTotals[$prog]['close'] ?: '-' }}</td>
                                        <td class="px-2 py-4 text-center font-black text-emerald-700 bg-emerald-100/50">{{ $grandTotals[$prog]['bast'] ?: '-' }}</td>
                                        <td class="px-2 py-4 text-center font-black text-red-700 bg-red-100/50 border-r border-slate-300/60">{{ $grandTotals[$prog]['drop'] ?: '-' }}</td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- MODAL DETAIL & EDIT TETAP SAMA, KODE DISINI DIAMBIL DARI MODAL LAMA ANDA... --}}
    {{-- (Pastikan tag modal <div x-show="showDetail"> dan <div x-show="showEdit"> diletakkan kembali ke sini ya, kodenya persis tidak berubah) --}}

    @include('admin.import.partials.pid-modals') {{-- Atau pastekan manual --}}
</div>

<script>
    // FUNGSI UNTUK TOGGLE CABANG MATRIKS
    function toggleRegion(className, iconId) {
        const rows = document.querySelectorAll('.' + className);
        const icon = document.getElementById(iconId);
        let isHidden = true;
        
        rows.forEach(row => {
            if (row.classList.contains('hidden')) {
                row.classList.remove('hidden');
                isHidden = false;
            } else {
                row.classList.add('hidden');
            }
        });

        if (isHidden) {
            icon.style.transform = 'rotate(0deg)'; // Tutup
        } else {
            icon.style.transform = 'rotate(-180deg)'; // Buka
        }
    }

    // FUNGSI MAPPING DROPDOWN BRANCH
    const regionMapping = {
        'JATIM': ['SIDOARJO', 'SURABAYA', 'MADIUN', 'JEMBER', 'LAMONGAN', 'MALANG'],
        'JATENG DIY': ['YOGYAKARTA', 'SEMARANG', 'PURWOKERTO', 'PEKALONGAN', 'SURAKARTA', 'MAGELANG'],
        'BALNUS': ['DENPASAR', 'KUPANG', 'MATARAM', 'FLORES']
    };

    function updateBranchDropdown() {
        const regionSelect = document.getElementById('regionSelect');
        const branchSelect = document.getElementById('branchSelect');
        const selectedRegion = regionSelect.value.toUpperCase(); 
        const currentBranch = "{{ request('branch') }}".toUpperCase(); 
        
        branchSelect.innerHTML = '<option value="">Semua Branch</option>';
        
        if (selectedRegion && regionMapping[selectedRegion]) {
            regionMapping[selectedRegion].forEach(function(branch) {
                let option = document.createElement('option');
                option.value = branch; 
                option.text = branch;
                if(branch.toUpperCase() === currentBranch) {
                    option.selected = true;
                }
                branchSelect.appendChild(option);
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        updateBranchDropdown();
    });

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
</script>

@endsection