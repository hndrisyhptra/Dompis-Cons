@extends('layouts.admin')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Program PT 2</h1>
        <p class="text-sm text-emerald-600 font-semibold mt-1">Modul Khusus Konstruksi PT 2 (1 PID Banyak LOP)</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.import.pid') }}" class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition">
            + Import PT 2
        </a>
    </div>
</div>

{{-- Search & Filter --}}
<div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-4 mb-6 shadow-sm">
    <form method="GET" action="{{ route('admin.pt2.index') }}" id="filterForm" class="space-y-4">
        <div class="flex flex-col lg:flex-row gap-3">
            <div class="relative flex-1">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">🔍</span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari PID, Nama LOP, STO, Branch..." class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm pl-11 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <button class="h-11 px-6 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold">Cari Data</button>
            <a href="{{ route('admin.pt2.index') }}" class="h-11 px-5 inline-flex items-center justify-center rounded-2xl border border-gray-300 dark:border-gray-700 text-sm font-bold dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800">Reset</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 border-t border-gray-100 dark:border-gray-800 pt-4 mt-4">
            <div>
                <label class="block text-xs font-black uppercase text-gray-400 mb-1">Region</label>
                <select name="region" onchange="this.form.submit()" class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm focus:ring-emerald-500">
                    <option value="">Semua Region</option>
                    <option value="JATIM" {{ request('region') == 'JATIM' ? 'selected' : '' }}>JATIM</option>
                    <option value="JATENG DIY" {{ request('region') == 'JATENG DIY' ? 'selected' : '' }}>JATENG DIY</option>
                    <option value="BALNUS" {{ request('region') == 'BALNUS' ? 'selected' : '' }}>BALNUS</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-gray-400 mb-1">Branch</label>
                <select name="branch" onchange="this.form.submit()" class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm focus:ring-emerald-500">
                    <option value="">Semua Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch }}" {{ request('branch') == $branch ? 'selected' : '' }}>{{ $branch }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-gray-400 mb-1">Status Project</label>
                <select name="status_project" onchange="this.form.submit()" class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm focus:ring-emerald-500">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status_project') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="close" {{ request('status_project') == 'close' ? 'selected' : '' }}>Close</option>
                    <option value="drop" {{ request('status_project') == 'drop' ? 'selected' : '' }}>Drop (Batal)</option>
                </select>
            </div>
        </div>
    </form>
</div>

{{-- ALERT / NOTIFIKASI --}}
@if(session('success'))
    <div class="mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 text-sm font-bold">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 px-5 py-4 text-sm font-bold">
        {{ session('error') }}
    </div>
@endif

{{-- PT 2 Table List --}}
<div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden" x-data="{ expanded: null }">
    
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-black text-gray-900 dark:text-white">Daftar Project PT 2</h2>
            <p class="text-xs text-gray-500 mt-1">Klik baris PID untuk melihat daftar LOP</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1.5 rounded-lg bg-emerald-100 text-emerald-700 text-xs font-bold">Total: {{ $projects->total() }} PID</span>
        </div>
    </div>

    <div class="overflow-x-auto min-h-[300px]">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500 w-10"></th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">PID / PID SAP</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Lokasi</th>
                    <th class="px-5 py-3 text-center text-xs font-black uppercase text-gray-500">Total LOP</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500 min-w-[150px]">Progress Keseluruhan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($projects as $project)
                    @php 
                        $lopsCount = $project->lops->count(); 
                        $completedLops = 0;
                        $sumProgress = 0;
                        
                        // Menghitung progress secara bersih menggunakan Model
                        foreach($project->lops as $l) {
                            $summ = $l->progressSummary();
                            $sumProgress += $summ['progress'];
                            if($summ['progress'] == 100) $completedLops++;
                        }
                        
                        // Rata-rata progress parent PID agar lebih presisi (misal 1 LOP 0%, 1 LOP 100% = PID Progress 50%)
                        $pidProgress = $lopsCount > 0 ? round($sumProgress / $lopsCount) : 0;
                    @endphp
                    
                    {{-- BARIS UTAMA (PID WADAH) --}}
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition cursor-pointer" @click="expanded === {{ $project->id_pt2_project }} ? expanded = null : expanded = {{ $project->id_pt2_project }}">
                        <td class="px-5 py-4 text-center">
                            <span class="text-emerald-600 font-bold transition-transform duration-200" :class="expanded === {{ $project->id_pt2_project }} ? 'rotate-90 inline-block' : 'inline-block'">▶</span>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-black text-gray-900 dark:text-white">{{ $project->pid ?? '-' }}</p>
                            <p class="text-xs text-gray-500 mt-1">SAP: {{ $project->pid_sap ?? '-' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-gray-800 dark:text-gray-100">{{ $project->branch ?? '-' }}</p>
                            <p class="text-xs text-gray-500 mt-1">STO {{ $project->sto ?? '-' }}</p>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">{{ $lopsCount }} LOP</span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-between mb-1 text-[10px] font-bold text-gray-500">
                                <span>{{ $completedLops }} dari {{ $lopsCount }} LOP Selesai</span>
                                <span class="{{ $pidProgress == 100 ? 'text-emerald-600' : '' }}">{{ $pidProgress }}%</span>
                            </div>
                            <div class="h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full {{ $pidProgress == 100 ? 'bg-emerald-500' : 'bg-blue-500' }} rounded-full transition-all" style="width: {{ $pidProgress }}%"></div>
                            </div>
                        </td>
                    </tr>

                    {{-- BARIS ANAK (DAFTAR LOP) --}}
                    <tr x-show="expanded === {{ $project->id_pt2_project }}" x-collapse x-cloak class="bg-slate-50/80 dark:bg-slate-900/80 border-b-2 border-emerald-200 dark:border-emerald-900">
                        <td colspan="5" class="p-0">
                            <div class="px-6 sm:px-14 py-5">
                                <div class="overflow-visible rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                                    <table class="w-full text-left text-sm">
                                        <thead class="bg-emerald-50 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300 border-b border-emerald-100 dark:border-emerald-800">
                                            <tr>
                                                <th class="px-4 py-3 font-bold text-xs">Project / LOP</th>
                                                <th class="px-4 py-3 font-bold text-xs">Teknisi</th>
                                                <th class="px-4 py-3 font-bold text-xs">Tahapan</th>
                                                <th class="px-4 py-3 font-bold text-xs">Progress</th>
                                                <th class="px-4 py-3 font-bold text-xs text-center w-24">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                            @foreach($project->lops as $lop)
                                                @php
                                                    $assignedUser = $lop->assignment->teknisi ?? null;
                                                    
                                                    // BLADE SANGAT BERSIH: Semua data diambil langsung dari Model
                                                    $summary = $lop->progressSummary();
                                                @endphp
                                                
                                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                                    <td class="px-4 py-4">
                                                        <p class="font-bold text-gray-900 dark:text-white">{{ $lop->lop_name }}</p>
                                                        <p class="text-xs text-gray-500 mt-1">IHLD: {{ $lop->id_ihld ?? '-' }}</p>
                                                    </td>
                                                    <td class="px-4 py-4">
                                                        @if($assignedUser)
                                                            <p class="font-bold text-gray-900 dark:text-white text-xs">{{ $assignedUser->name }}</p>
                                                            <p class="text-[10px] text-emerald-600 font-bold mt-0.5">Teknisi PT 2</p>
                                                        @else
                                                            <span class="px-2 py-1 bg-gray-100 text-gray-500 text-[10px] font-bold rounded border border-gray-200">Belum Assign</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-4">
                                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-black uppercase {{ $summary['badge'] }}">
                                                            {{ $summary['stageLabel'] }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-4 min-w-[120px]">
                                                        <div class="flex justify-between mb-1 text-[10px] font-bold text-gray-500">
                                                            <span>Progress</span>
                                                            <span>{{ $summary['progress'] }}%</span>
                                                        </div>
                                                        <div class="h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                                            <div class="h-full {{ $summary['color'] }} rounded-full transition-all" style="width: {{ $summary['progress'] }}%"></div>
                                                        </div>
                                                    </td>
                                                    
                                                    {{-- KOLOM AKSI DROPDOWN MENU --}}
                                                    <td class="px-4 py-4 text-center">
                                                        <div class="action-menu-container inline-block text-left">
                                                            <button type="button"
                                                                    @click.stop="toggleMenu(event, 'menu-lop-{{ $lop->id_pt2_lop }}', $el)"
                                                                    class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-200 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-white dark:hover:bg-gray-600 dark:hover:text-gray-100">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5h.01M12 12h.01M12 19h.01"/>
                                                                </svg>
                                                            </button>

                                                            {{-- MENU DROPDOWN KHUSUS LOP --}}
                                                            <div id="menu-lop-{{ $lop->id_pt2_lop }}" class="action-menu-dropdown hidden fixed w-56 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-2xl z-[9999] overflow-hidden">
                                                                <div class="flex flex-col text-left py-2">
                                                                    
                                                                    {{-- Detail --}}
                                                                    <button type="button" onclick="openDetailModalPt2({{ $lop->id_pt2_lop }})"
                                                                            class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-800 hover:text-blue-700 transition-colors">
                                                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0x" /></svg>
                                                                        <span class="font-semibold">Detail LOP</span>
                                                                    </button>

                                                                    {{-- Tracking --}}
                                                                    <a href="{{ route('admin.pt2.tracking', $lop->id_pt2_lop) }}"
                                                                    class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-800 hover:text-blue-700 transition-colors">
                                                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25A7.5 7.5 0 1119.5 10.5z" /></svg>
                                                                        <span class="font-semibold">Tracking Progress</span>
                                                                    </a>

                                                                    {{-- Assign --}}
                                                                    <button type="button" onclick="openAssignModalPt2({{ $project->id_pt2_project }}, {{ $lop->id_pt2_lop }}, '{{ addslashes($lop->lop_name) }}')"
                                                                            class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-gray-800 hover:text-amber-700 transition-colors">
                                                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
                                                                        <span class="font-semibold">{{ $assignedUser ? 'Reassign Teknisi' : 'Assign Teknisi' }}</span>
                                                                    </button>
                                                                    
                                                                    {{-- Upload KML --}}
                                                                    <button type="button" onclick="openKmlModalPt2({{ $lop->id_pt2_lop }}, '{{ addslashes($lop->lop_name) }}')"
                                                                            class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                                                                        <span class="font-semibold">Upload KML</span>
                                                                    </button>

                                                                    {{-- Edit Data --}}
                                                                    <button type="button" onclick="openEditLopModalPt2({{ $lop->id_pt2_lop }})"
                                                                            class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                                                         <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" /></svg>
                                                                        <span class="font-semibold">Edit Data LOP</span>
                                                                    </button>

                                                                    {{-- Delete LOP --}}
                                                                    <form method="POST" action="{{ route('admin.pt2.destroyLop', $lop->id_pt2_lop) }}" class="m-0">
                                                                        @csrf @method('DELETE')
                                                                        <button type="submit" onclick="return confirm('Hapus LOP PT 2 ini? Semua eviden terkait akan hilang.')"
                                                                                class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                                                             <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                                            <span class="font-semibold">Delete LOP</span>
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada data PT 2. Silakan Import PID PT 2.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- CUSTOM COMPACT PAGINATION --}}
    @if($projects->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-gray-500 font-bold">
                Menampilkan <span class="text-gray-900 dark:text-white">{{ $projects->firstItem() }}</span> - <span class="text-gray-900 dark:text-white">{{ $projects->lastItem() }}</span> dari <span class="text-gray-900 dark:text-white">{{ $projects->total() }}</span> PID
            </div>
            <div class="flex items-center gap-1 text-xs">
                @if ($projects->onFirstPage())
                    <span class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-400 bg-gray-100 dark:bg-gray-800 font-medium">&laquo; Prev</span>
                @else
                    <a href="{{ $projects->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 font-medium">&laquo; Prev</a>
                @endif

                @foreach ($projects->onEachSide(1)->linkCollection() as $link)
                    @if (is_numeric($link['label']))
                        @if ($link['active'])
                            <span class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-bold">{{ $link['label'] }}</span>
                        @else
                            <a href="{{ $link['url'] }}" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 font-medium">{{ $link['label'] }}</a>
                        @endif
                    @elseif ($link['label'] === '...')
                        <span class="px-2 py-1.5 text-gray-400 font-bold">...</span>
                    @endif
                @endforeach

                @if ($projects->hasMorePages())
                    <a href="{{ $projects->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 font-medium">Next &raquo;</a>
                @else
                    <span class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-400 bg-gray-100 dark:bg-gray-800 font-medium">Next &raquo;</span>
                @endif
            </div>
        </div>
    @endif
</div>

{{-- INCLUDE PARTIALS: Modals dan Scripts --}}
@include('admin.pt2.partials.modals')
@include('admin.pt2.partials.scripts')

{{-- Alpine JS --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

@endsection