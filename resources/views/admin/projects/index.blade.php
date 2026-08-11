@extends('layouts.admin')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Project ID</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">List Project Konstruksi</p>
    </div>
    <div class="flex gap-2">
        <button type="button" onclick="openImportModal()" class="h-10 px-4 inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800">
            Import CSV
        </button>
        <button type="button" onclick="openProjectModal()" class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
            + Input Manual LOP Baru
        </button>
    </div>
</div>

{{-- Search & Filter --}}
<div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-4 mb-6 shadow-sm">
    <form method="GET" action="{{ route('projects.index') }}" class="space-y-4">
        <div class="flex flex-col lg:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari project, STO, branch, mitra..." class="flex-1 h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-blue-500 focus:border-blue-500">
            <button class="h-11 px-6 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold">Cari</button>
            <a href="{{ route('projects.index') }}" class="h-11 px-5 inline-flex items-center justify-center rounded-2xl border border-gray-300 dark:border-gray-700 text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800">Reset</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">Program</label>
                <select name="program" onchange="this.form.submit()" class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Program</option>
                    @foreach($programs as $program)
                        <option value="{{ $program }}" {{ request('program') == $program ? 'selected' : '' }}>{{ $program }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">Branch</label>
                <select name="branch" onchange="this.form.submit()" class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch }}" {{ request('branch') == $branch ? 'selected' : '' }}>{{ $branch }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

{{-- Project Table List --}}
<div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-black text-gray-900 dark:text-white">Daftar Project</h2>
            <p class="text-xs text-gray-500 mt-1">Monitoring progress, assignment, evidence dan KML</p>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700">
                <span class="font-medium">Tampilkan</span>
                <select onchange="window.location.href=this.value" class="bg-transparent border-none text-gray-900 dark:text-white text-xs font-bold focus:ring-0 cursor-pointer p-0 pr-5">
                    @foreach([10, 20, 50, 100] as $val)
                        <option value="{{ request()->fullUrlWithQuery(['per_page' => $val, 'page' => 1]) }}" {{ request('per_page', 10) == $val ? 'selected' : '' }}>{{ $val }}</option>
                    @endforeach
                </select>
                <span class="font-medium">Baris</span>
            </div>
            <span class="px-3 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold whitespace-nowrap">Total: {{ $projects->total() }} Data</span>
        </div>
    </div>

    <div class="overflow-x-auto min-h-[300px]">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Project</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Lokasi</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Waspang</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Tahapan</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Progress</th>
                    <th class="px-5 py-3 text-center text-xs font-black uppercase text-gray-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($projects as $project)
                    @php
                        $summary = $project->progressSummary();
                        $progress = $summary['progress'];
                        $stageLabel = $summary['stageLabel'];
                        
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

                        $programName = $project->program ?? optional($project->lops->first())->program_sap ?? '';
                        $isPT2 = (str_replace(' ', '', strtoupper($programName)) === 'PT2');
                        $labelRole = $isPT2 ? 'Teknisi' : 'Waspang';

                        if ($progress == 100) { $stageBadge = 'bg-green-100 text-green-700'; $progressColor = 'bg-green-600'; } 
                        elseif ($stageLabel === 'Finishing') { $stageBadge = 'bg-purple-100 text-purple-700'; $progressColor = 'bg-purple-600'; } 
                        elseif ($stageLabel === 'Pengukuran') { $stageBadge = 'bg-blue-100 text-blue-700'; $progressColor = 'bg-blue-600'; } 
                        elseif ($stageLabel === 'Instalasi') { $stageBadge = 'bg-yellow-100 text-yellow-700'; $progressColor = 'bg-yellow-600'; } 
                        else { $stageBadge = 'bg-red-100 text-red-700'; $progressColor = 'bg-red-600'; }
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                        <td class="px-5 py-4">
                            <div class="min-w-[220px]">
                                <p class="font-black text-gray-900 dark:text-white leading-snug">{{ $project->project_name }}</p>
                                <p class="text-xs text-gray-500 mt-1">PID: {{ $project->pid ?? '-' }} · {{ strtoupper($project->execution_type ?? '-') }}</p>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-gray-800 dark:text-gray-100">{{ $project->lop?->branch ?? '-' }}</p>
                            <p class="text-xs text-gray-500 mt-1">STO {{ $project->lop?->sto ?? '-' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            @if($assignedUser)
                                <p class="font-bold text-gray-900 dark:text-white">{{ $assignedUser->name }}</p>
                                <p class="text-xs text-green-600 font-bold">Assigned ({{ $assignedRoleBadge }})</p>
                            @else
                                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-bold">Belum diassign</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-3 py-1 rounded-full {{ $stageBadge }} text-xs font-black">{{ $stageLabel }}</span>
                        </td>
                        <td class="px-5 py-4 min-w-[150px]">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-gray-500">Progress</span>
                                <span class="text-sm font-black text-gray-900 dark:text-white">{{ $progress }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full rounded-full {{ $progressColor }}" style="width: {{ $progress }}%"></div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <div class="action-menu-container inline-block text-left">
                                <button type="button" onclick="toggleMenu(event, 'menu-{{ $project->id_project }}', this)" class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-200 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-white dark:hover:bg-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5h.01M12 12h.01M12 19h.01"/></svg>
                                </button>
                                <div id="menu-{{ $project->id_project }}" class="action-menu-dropdown hidden fixed w-56 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-2xl z-[9999] overflow-hidden">
                                    <div class="flex flex-col text-left py-2">
                                        <button type="button" onclick="openDetailModal('detail-modal-{{ $project->id_project }}')" class="w-full px-4 py-2 text-left text-sm font-semibold hover:bg-blue-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300">Detail Project</button>
                                        <a href="{{ route('admin.projects.tracking', $project->id_project) }}" class="w-full px-4 py-2 text-left text-sm font-semibold hover:bg-blue-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300">Tracking Progress</a>
                                        <button type="button" onclick="openAssignModal('{{ $project->id_project }}', '{{ addslashes($project->project_name) }}', '{{ addslashes($programName) }}')" class="w-full px-4 py-2 text-left text-sm font-semibold hover:bg-amber-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300">{{ $assignedUser ? 'Reassign ' . $labelRole : 'Assign ' . $labelRole }}</button>
                                        <button type="button" onclick="openKmlModal('{{ $project->id_project }}', '{{ addslashes($project->project_name) }}')" class="w-full px-4 py-2 text-left text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300">Upload KML</button>
                                        
                                        {{-- JSON Parse Data Method untuk keamanan kutip --}}
                                        <button type="button" 
                                                data-project="{{ json_encode([
                                                    'id' => $project->id_project,
                                                    'project_name' => $project->project_name,
                                                    'branch' => $project->lop?->branch,
                                                    'sto' => $project->lop?->sto,
                                                    'mitra_name' => $project->mitra_name ?? $project->lop?->mitra_name,
                                                    'status' => $project->status_project,
                                                    'latitude' => $project->latitude,
                                                    'longitude' => $project->longitude,
                                                    'location_address' => $project->location_address,
                                                    'boq_items' => $project->boqItems
                                                ]) }}"
                                                onclick="openEditProjectModal(JSON.parse(this.dataset.project))"
                                                class="w-full px-4 py-2 text-left text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300">
                                            Edit Data
                                        </button>

                                        <form method="POST" action="{{ route('projects.destroy',$project->id_project) }}" class="m-0">
                                            @csrf @method('DELETE')
                                            <button type="submit" onclick="return confirm('Hapus project ini?')" class="w-full px-4 py-2 text-left text-sm font-semibold text-red-600 hover:bg-red-50 dark:hover:bg-gray-800">Delete Project</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">Belum ada project.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($projects->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
            {{ $projects->links() }}
        </div>
    @endif
</div>

{{-- INCLUDE PARTIALS --}}
@include('admin.projects.partials.modals')
@include('admin.projects.modals.boq-modal')
@include('admin.projects.partials.scripts')

@endsection