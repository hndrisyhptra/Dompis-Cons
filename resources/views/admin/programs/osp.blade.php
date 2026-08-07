@extends('layouts.admin')

@section('content')

<div class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6">

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <p class="text-xs font-black text-blue-700 uppercase tracking-widest">Program Monitoring</p>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">Project OSP</h1>
                    <p class="text-xs text-slate-500 mt-2 max-w-2xl">Daftar project khusus program OSP.</p>
                </div>
            </div>
        </div>
        {{-- FILTER PANEL --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <form method="GET" action="{{ route('program.osp') }}" id="filterForm">
                <div class="mb-5">
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">Pencarian Bebas</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">🔎</span>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari PID, Nama LOP, ID IHLD, dll..."
                               class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm pl-11 pr-4 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Region</label>
                        <select name="region" id="regionSelect" onchange="updateBranchDropdown(); document.getElementById('filterForm').submit();"
                                class="w-full h-11 px-4 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm focus:ring-blue-500">
                            <option value="">Semua Region</option>
                            <option value="JATIM" {{ request('region') == 'JATIM' ? 'selected' : '' }}>JATIM</option>
                            <option value="JATENG DIY" {{ request('region') == 'JATENG DIY' ? 'selected' : '' }}>JATENG DIY</option>
                            <option value="BALNUS" {{ request('region') == 'BALNUS' ? 'selected' : '' }}>BALNUS</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Branch</label>
                        <select name="branch" id="branchSelect" onchange="document.getElementById('filterForm').submit()"
                                class="w-full h-11 px-4 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm focus:ring-blue-500">
                            <option value="">Semua Branch</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase mb-2">Status Project</label>
                        <select name="status_project" onchange="document.getElementById('filterForm').submit()"
                                class="w-full h-11 px-4 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm focus:ring-blue-500">
                            <option value="">Semua Status</option>
                            <option value="init" {{ request('status_project') == 'init' ? 'selected' : '' }}>Init</option>
                            <option value="active" {{ request('status_project') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="close" {{ request('status_project') == 'close' ? 'selected' : '' }}>Close</option>
                            <option value="bast" {{ request('status_project') == 'bast' ? 'selected' : '' }}>BAST</option>
                            <option value="drop" {{ request('status_project') == 'drop' ? 'selected' : '' }}>Drop (Batal)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    @if(request('search') || request('region') || request('branch') || request('status_project'))
                        <a href="{{ route('program.osp') }}" class="h-11 px-6 rounded-xl bg-slate-100 text-slate-700 text-sm font-bold flex items-center justify-center hover:bg-slate-200">Reset</a>
                    @endif
                    <button type="submit" class="h-11 px-8 rounded-xl bg-blue-600 text-white text-sm font-black hover:bg-blue-700">Terapkan Filter</button>
                </div>
            </form>
        </div>

         <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            @foreach([
                ['title' => 'Total LOP', 'val' => $totalLop, 'color' => 'blue'],
                ['title' => 'Active LOP', 'val' => $activelop, 'color' => 'amber'],
                ['title' => 'Complete', 'val' => $complete, 'color' => 'emerald'],
                ['title' => 'Drop', 'val' => $drop, 'color' => 'red']
            ] as $card)
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $card['title'] }}</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($card['val']) }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-{{$card['color']}}-50 text-{{$card['color']}}-600 flex items-center justify-center">
                    <div class="w-2 h-2 rounded-full bg-current"></div>
                </div>
            </div>
            @endforeach
        </div>
        {{-- MATRIKS STATUS PROJECT UNFILTERED --}}
        <div x-data="{ openMatrix: false }" class="mt-8 bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <button @click="openMatrix = !openMatrix" type="button" class="w-full px-6 py-5 flex justify-between items-center bg-slate-50/50 hover:bg-slate-100/50 transition">
                <div class="text-left">
                    <h2 class="text-sm font-black uppercase tracking-wider text-slate-800">Matriks Status Project (Unfiltered)</h2>
                    <p class="text-xs text-slate-500 mt-1">Total keseluruhan siklus project untuk program ini berdasarkan Region & Branch.</p>
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
                                <th class="px-6 py-3 text-left border-b border-slate-200/60 whitespace-nowrap">Wilayah (Region / Branch)</th>
                                <th class="px-4 py-3 text-center text-blue-600 bg-blue-50/50 border-b border-slate-200/60">Init</th>
                                <th class="px-4 py-3 text-center text-amber-600 bg-amber-50/50 border-b border-slate-200/60">Active</th>
                                <th class="px-4 py-3 text-center text-indigo-600 bg-indigo-50/50 border-b border-slate-200/60">Close</th>
                                <th class="px-4 py-3 text-center text-emerald-600 bg-emerald-50/50 border-b border-slate-200/60">Bast</th>
                                <th class="px-4 py-3 text-center text-red-600 bg-red-50/50 border-b border-slate-200/60">Drop</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200/60">
                            @php
                                $grandTotals = ['init' => 0, 'active' => 0, 'close' => 0, 'bast' => 0, 'drop' => 0];
                            @endphp

                            @forelse($matrixData as $i => $reg)
                                @php
                                    foreach(['init', 'active', 'close', 'bast', 'drop'] as $st) {
                                        $grandTotals[$st] += $reg['stats'][$st];
                                    }
                                @endphp
                                {{-- Baris Region --}}
                                <tr class="cursor-pointer bg-white hover:bg-slate-50 transition group" onclick="toggleRegion('matrix-branch-{{ $i }}', 'icon-mat-{{ $i }}')">
                                    <td class="px-6 py-4 font-black text-slate-800 text-sm whitespace-nowrap">
                                        <span id="icon-mat-{{ $i }}" class="inline-block transition-transform duration-200 mr-2 text-indigo-500">▶</span>
                                        {{ $reg['region'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-slate-700 bg-blue-50/20">{{ $reg['stats']['init'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-center font-bold text-slate-700 bg-amber-50/20">{{ $reg['stats']['active'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-center font-bold text-slate-700 bg-indigo-50/20">{{ $reg['stats']['close'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-center font-bold text-slate-700 bg-emerald-50/20">{{ $reg['stats']['bast'] ?: '-' }}</td>
                                    <td class="px-4 py-3 text-center font-black text-red-700 bg-red-50/20">{{ $reg['stats']['drop'] ?: '-' }}</td>
                                </tr>
                                
                                {{-- Baris Anak Branch --}}
                                @foreach($reg['branches'] as $br)
                                    <tr class="hidden bg-slate-50/50 hover:bg-slate-100/50 transition matrix-branch-{{ $i }}">
                                        <td class="px-4 py-3 pl-[3.5rem] text-slate-600 font-bold whitespace-nowrap">
                                            ↳ {{ $br['name'] }}
                                        </td>
                                        <!-- <td class="px-4 py-2 text-center text-blue-600 font-semibold">{{ $br['stats']['init'] ?: '-' }}</td> -->
                                        <td class="px-4 py-2 text-center text-amber-600 font-semibold">{{ $br['stats']['active'] ?: '-' }}</td>
                                        <td class="px-4 py-2 text-center text-indigo-600 font-semibold">{{ $br['stats']['close'] ?: '-' }}</td>
                                        <!-- <td class="px-4 py-2 text-center text-emerald-600 font-semibold">{{ $br['stats']['bast'] ?: '-' }}</td> -->
                                        <td class="px-4 py-2 text-center text-red-600 font-bold">{{ $br['stats']['drop'] ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-slate-400">Tidak ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        
                        {{-- GRAND TOTAL --}}
                        <tfoot class="bg-slate-100 border-t-2 border-slate-300">
                            <tr>
                                <td class="px-6 py-4 text-slate-800 font-black uppercase text-xs">GRAND TOTAL</td>
                                <td class="px-4 py-4 text-center font-black text-blue-700 bg-blue-100/50">{{ $grandTotals['init'] ?: '-' }}</td>
                                <td class="px-4 py-4 text-center font-black text-amber-700 bg-amber-100/50">{{ $grandTotals['active'] ?: '-' }}</td>
                                <td class="px-4 py-4 text-center font-black text-indigo-700 bg-indigo-100/50">{{ $grandTotals['close'] ?: '-' }}</td>
                                <td class="px-4 py-4 text-center font-black text-emerald-700 bg-emerald-100/50">{{ $grandTotals['bast'] ?: '-' }}</td>
                                <td class="px-4 py-4 text-center font-black text-red-700 bg-red-100/50">{{ $grandTotals['drop'] ?: '-' }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- TABLE DATA --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">Daftar Project</h2>
                    <p class="text-xs text-slate-500 mt-1">Monitoring progress, assignment, evidence dan KML</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold whitespace-nowrap">Total: {{ $projects->total() }} Data</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Project</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Lokasi</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Waspang</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Tahapan</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Progress</th>
                            <th class="px-5 py-3 text-center text-xs font-black uppercase text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800" x-data="{ expanded: null }">
                        @forelse($projects as $project)
                            @php
                                $summary = $project->progressSummary();
                                $progress = $summary['progress'];
                                $stageLabel = $summary['stageLabel'];
                                
                                $lops = $project->lops ?? collect();
                                $lopsCount = $lops->count();
                                $firstLop = $lops->first();

                                $assignmentData = $project->assignment;
                                $assignedUser = null;
                                $assignedRoleBadge = '';

                                if ($assignmentData) {
                                    if ($assignmentData->waspang_id) {
                                        $assignedUser = $assignmentData->waspang ?? \App\Models\User::find($assignmentData->waspang_id);
                                        $assignedRoleBadge = 'Waspang';
                                    } elseif ($assignmentData->teknisi_id) {
                                        $assignedUser = \App\Models\User::find($assignmentData->teknisi_id);
                                        $assignedRoleBadge = 'Teknisi';
                                    }
                                }

                                $programName = $project->program ?? optional($firstLop)->program_sap ?? '';
                                $isPT2 = (str_replace(' ', '', strtoupper($programName)) === 'PT2');
                                $labelRole = $isPT2 ? 'Teknisi' : 'Waspang';

                                if ($progress == 100) { $stageBadge = 'bg-green-100 text-green-700'; $progressColor = 'bg-green-600'; } 
                                elseif ($stageLabel === 'Finishing') { $stageBadge = 'bg-purple-100 text-purple-700'; $progressColor = 'bg-purple-600'; } 
                                elseif ($stageLabel === 'Pengukuran') { $stageBadge = 'bg-blue-100 text-blue-700'; $progressColor = 'bg-blue-600'; } 
                                elseif ($stageLabel === 'Instalasi') { $stageBadge = 'bg-yellow-100 text-yellow-700'; $progressColor = 'bg-yellow-600'; } 
                                else { $stageBadge = 'bg-red-100 text-red-700'; $progressColor = 'bg-red-600'; }
                            @endphp

                            {{-- BARIS UTAMA (PROJECT / WADAH) --}}
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition {{ $lopsCount > 1 ? 'cursor-pointer' : '' }}"
                                @if($lopsCount > 1) 
                                    @click="expanded === {{ $project->id_project }} ? expanded = null : expanded = {{ $project->id_project }}" 
                                @endif>
                                
                                <td class="px-5 py-4">
                                    <div class="min-w-[220px]">
                                        <p class="font-black text-slate-900 dark:text-white leading-snug">{{ $project->project_name }}</p>
                                        <p class="text-xs text-slate-500 mt-1">PID: {{ $project->pid ?? '-' }} · {{ strtoupper($project->execution_type ?? '-') }}</p>
                                        
                                        {{-- TAMPILKAN INDIKATOR JIKA LOP > 1 --}}
                                        @if($lopsCount > 1)
                                            <div class="flex items-center gap-2 mt-2">
                                                <span class="text-[10px] font-black px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">{{ $lopsCount }} LOP</span>
                                                <span class="text-[10px] text-blue-600 font-bold uppercase tracking-wide">Klik lihat detail ⤓</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-800 dark:text-slate-100">{{ $project->lop?->branch ?? '-' }}</p>
                                    <p class="text-xs text-slate-500 mt-1">STO {{ $project->lop?->sto ?? '-' }}</p>
                                </td>
                                
                                <td class="px-5 py-4">
                                    @if($assignedUser)
                                        <p class="font-bold text-slate-900 dark:text-white">{{ $assignedUser->name }}</p>
                                        <p class="text-xs text-green-600 font-bold">Assigned ({{ $assignedRoleBadge }})</p>
                                    @else
                                        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-500 text-xs font-bold">Belum diassign</span>
                                    @endif
                                </td>
                                
                                <td class="px-5 py-4">
                                    <span class="px-3 py-1 rounded-full {{ $stageBadge }} text-xs font-black">{{ $stageLabel }}</span>
                                    <div class="flex gap-1 mt-2">
                                        <span class="w-2.5 h-2.5 rounded-full {{ in_array($stageLabel, ['Persiapan','Instalasi','Pengukuran','Finishing']) ? 'bg-red-500' : 'bg-slate-300' }}"></span>
                                        <span class="w-2.5 h-2.5 rounded-full {{ in_array($stageLabel, ['Instalasi','Pengukuran','Finishing']) ? 'bg-yellow-500' : 'bg-slate-300' }}"></span>
                                        <span class="w-2.5 h-2.5 rounded-full {{ in_array($stageLabel, ['Pengukuran','Finishing']) ? 'bg-blue-500' : 'bg-slate-300' }}"></span>
                                        <span class="w-2.5 h-2.5 rounded-full {{ $stageLabel == 'Finishing' ? 'bg-green-500' : 'bg-slate-300' }}"></span>
                                    </div>
                                </td>
                                
                                <td class="px-5 py-4 min-w-[150px]">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-bold text-slate-500">Progress</span>
                                        <span class="text-sm font-black text-slate-900 dark:text-white">{{ $progress }}%</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                        <div class="h-full rounded-full {{ $progressColor }}" style="width: {{ $progress }}%"></div>
                                    </div>
                                </td>
                                
                                <td class="px-5 py-4 text-center">
                                    <div class="action-menu-container inline-block text-left">
                                        {{-- TAMBAHKAN @click.stop AGAR BARIS TIDAK TER-EXPAND SAAT KLIK TOMBOL AKSI --}}
                                        <button type="button" 
                                                @click.stop="toggleMenu(event, 'menu-{{ $project->id_project }}', $el)" 
                                                class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-200 text-slate-600 hover:bg-slate-200 hover:text-slate-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5h.01M12 12h.01M12 19h.01"/>
                                            </svg>
                                        </button>

                                        <div id="menu-{{ $project->id_project }}" class="action-menu-dropdown hidden fixed w-56 rounded-2xl bg-white border border-slate-200 shadow-2xl z-[9999] overflow-hidden">
                                            <div class="flex flex-col text-left py-2">
                                                <button type="button" onclick="openDetailModal('detail-modal-{{ $project->id_project }}')" class="w-full px-4 py-2 text-left text-sm font-semibold hover:bg-blue-50 hover:text-blue-700">Detail Project</button>
                                                <a href="{{ route('admin.projects.tracking', $project->id_project) }}" class="w-full px-4 py-2 text-left text-sm font-semibold hover:bg-blue-50 hover:text-blue-700">Tracking Progress</a>
                                                <button type="button" onclick="openAssignModal('{{ $project->id_project }}', @js($project->project_name), @js($programName))" class="w-full px-4 py-2 text-left text-sm font-semibold hover:bg-amber-50 hover:text-amber-700">{{ $assignedUser ? 'Reassign ' . $labelRole : 'Assign ' . $labelRole }}</button>
                                                <button type="button" onclick="openKmlModal('{{ $project->id_project }}', @js($project->project_name))" class="w-full px-4 py-2 text-left text-sm font-semibold hover:bg-slate-100">Upload KML</button>
                                                <button type="button" onclick="openEditProjectModal({ id:'{{ $project->id_project }}' })" class="w-full px-4 py-2 text-left text-sm font-semibold hover:bg-slate-100">Edit Data</button>
                                                
                                                <form method="POST" action="{{ route('projects.destroy',$project->id_project) }}" class="m-0">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" onclick="return confirm('Hapus project ini?')" class="w-full px-4 py-2 text-left text-sm font-semibold text-red-600 hover:bg-red-50">Delete Project</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            {{-- BARIS ANAK (DAFTAR LOP YANG BISA DI-EXPAND) --}}
                            @if($lopsCount > 1)
                            <tr x-show="expanded === {{ $project->id_project }}" x-collapse x-cloak class="bg-slate-50/80 dark:bg-slate-900/80 border-b-2 border-blue-200 dark:border-blue-900">
                                <td colspan="6" class="p-0">
                                    <div class="px-6 md:px-12 py-5">
                                        <p class="text-xs font-black text-slate-500 uppercase mb-3 tracking-wider">
                                            Daftar {{ $lopsCount }} LOP Terdaftar di PID Ini
                                        </p>
                                        
                                        <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800">
                                            <table class="w-full text-left text-xs">
                                                <thead class="bg-slate-100 dark:bg-slate-700/50 text-slate-500 dark:text-slate-400">
                                                    <tr>
                                                        <th class="px-4 py-3 font-bold uppercase w-12 text-center">No</th>
                                                        <th class="px-4 py-3 font-bold uppercase">Nama LOP</th>
                                                        <th class="px-4 py-3 font-bold uppercase">ID IHLD</th>
                                                        <th class="px-4 py-3 font-bold uppercase">Mitra</th>
                                                        <th class="px-4 py-3 font-bold uppercase">Tematik / Batch</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                                                    @foreach($lops as $idx => $lopItem)
                                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
                                                            <td class="px-4 py-3 font-medium text-slate-500 text-center">{{ $idx + 1 }}</td>
                                                            <td class="px-4 py-3 font-black text-slate-800 dark:text-slate-200">{{ $lopItem->lop_name ?? '-' }}</td>
                                                            <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">{{ $lopItem->id_ihld ?? '-' }}</td>
                                                            <td class="px-4 py-3 font-bold text-slate-600 dark:text-slate-300">{{ $lopItem->mitra_name ?? '-' }}</td>
                                                            <td class="px-4 py-3 font-medium text-slate-500">
                                                                {{ $lopItem->tematik ?? '-' }} / {{ $lopItem->batch ?? '-' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endif

                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">Belum ada project di program ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
           @if ($projects->hasPages())
    <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-950/50">
        {{-- Teks Info Halaman --}}
        <div class="text-xs text-slate-500 font-bold">
            Menampilkan <span class="text-slate-800 dark:text-slate-200">{{ $projects->firstItem() }}</span> - 
            <span class="text-slate-800 dark:text-slate-200">{{ $projects->lastItem() }}</span> dari 
            <span class="text-slate-800 dark:text-slate-200">{{ $projects->total() }}</span> data 
            (Halaman {{ $projects->currentPage() }} dari {{ $projects->lastPage() }})
        </div>

        {{-- Pagination dengan Angka Terbatas --}}
        <div class="flex items-center gap-1 text-xs">
            {{-- Tombol Previous --}}
            @if ($projects->onFirstPage())
                <span class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 bg-slate-100 dark:bg-slate-900 cursor-not-allowed font-medium">
                    &laquo; Prev
                </span>
            @else
                <a href="{{ $projects->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition font-medium">
                    &laquo; Prev
                </a>
            @endif

            {{-- Generasi Angka dengan Pembatasan onEachSide(1) --}}
            @foreach ($projects->onEachSide(1)->linkCollection() as $link)
                @if (is_numeric($link['label']))
                    @if ($link['active'])
                        <span class="px-3 py-1.5 rounded-lg bg-blue-600 text-white font-bold shadow-xs">
                            {{ $link['label'] }}
                        </span>
                    @else
                        <a href="{{ $link['url'] }}" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition font-medium">
                            {{ $link['label'] }}
                        </a>
                    @endif
                @elseif ($link['label'] === '...')
                    <span class="px-2 py-1.5 text-slate-400 font-bold">...</span>
                @endif
            @endforeach

            {{-- Tombol Next --}}
            @if ($projects->hasMorePages())
                <a href="{{ $projects->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition font-medium">
                    Next &raquo;
                </a>
            @else
                <span class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-600 bg-slate-100 dark:bg-slate-900 cursor-not-allowed font-medium">
                    Next &raquo;
                </span>
            @endif
        </div>
    </div>
@endif
        </div>
    </div>

    {{-- INCLUDE MODALS & JAVASCRIPT --}}
    @include('admin.programs.partials.program-modals')

</div>
@include('admin.projects.modals.boq-modal')

@include('admin.programs.partials.program-scripts')

@endsection