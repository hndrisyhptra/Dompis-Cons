@extends('layouts.admin')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">

    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            Project ID
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            List Project Konstruksi
        </p>
    </div>

    <div class="flex gap-2">
        <!-- <button type="button"
                onclick="openImportModal()"
                class="h-10 px-4 inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800">
            Import CSV
        </button> -->

        <!-- <button type="button"
                onclick="openProjectModal()"
                class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
            + Input Manual LOP Baru
        </button> -->
    </div>

</div>

        {{-- Search & Filter --}}
        <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-4 mb-6 shadow-sm">

            <form method="GET" action="{{ route('projects.index') }}" class="space-y-4">

                {{-- Search --}}
                <div class="flex flex-col lg:flex-row gap-3">
                    <input type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari project, STO, branch, mitra..."
                        class="flex-1 h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-blue-500 focus:border-blue-500">

                    <button class="h-11 px-6 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold">
                        Cari
                    </button>

                    <a href="{{ route('projects.index') }}"
                    class="h-11 px-5 inline-flex items-center justify-center rounded-2xl border border-gray-300 dark:border-gray-700 text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                        Reset
                    </a>
                </div>

                {{-- Filter Dropdown --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                    {{-- Program --}}
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">
                            Program
                        </label>

                        <select name="program"
                                onchange="this.form.submit()"
                                class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Program</option>

                            @foreach($programs as $program)
                                <option value="{{ $program }}" {{ request('program') == $program ? 'selected' : '' }}>
                                    {{ $program }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Branch --}}
                    <div>
                        <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">
                            Branch
                        </label>

                        <select name="branch"
                                onchange="this.form.submit()"
                                class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Semua Branch</option>

                            @foreach($branches as $branch)
                                <option value="{{ $branch }}" {{ request('branch') == $branch ? 'selected' : '' }}>
                                    {{ $branch }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

            </form>

        </div>
{{-- Project Table List --}}
<div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">

    {{-- HEADER TABEL: Judul & Dropdown Per Page --}}
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-black text-gray-900 dark:text-white">Daftar Project</h2>
            <p class="text-xs text-gray-500 mt-1">Monitoring progress, assignment, evidence dan KML</p>
        </div>

        <div class="flex items-center gap-3 w-full sm:w-auto">
            {{-- DROPDOWN JUMLAH BARIS --}}
            <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700">
                <span class="font-medium">Tampilkan</span>
                <select onchange="window.location.href=this.value" 
                        class="bg-transparent border-none text-gray-900 dark:text-white text-xs font-bold focus:ring-0 cursor-pointer p-0 pr-5">
                    @foreach([10, 20, 50, 100] as $val)
                        <option value="{{ request()->fullUrlWithQuery(['per_page' => $val, 'page' => 1]) }}" 
                            {{ request('per_page', 10) == $val ? 'selected' : '' }}>
                            {{ $val }}
                        </option>
                    @endforeach
                </select>
                <span class="font-medium">Baris</span>
            </div>
            
            {{-- TOTAL DATA --}}
            <span class="px-3 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold whitespace-nowrap">
                Total: {{ $projects->total() }} Data
            </span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Project</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Lokasi</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Waspang</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Tahapan</th>
                    <!-- <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Evidence</th> -->
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Progress</th>
                    <th class="px-5 py-3 text-center text-xs font-black uppercase text-gray-500">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($projects as $project)
                    @php
                        $summary = $project->progressSummary();

                        $persiapanDone = $summary['persiapanDone'];
                        $instalasiDone = $summary['instalasiDone'];
                        $pengukuranDone = $summary['pengukuranDone'];
                        $finishingDone = $summary['finishingDone'];

                        $boqTotal = $summary['materialTotal'];
                        $boqApproved = $summary['instalasiApproved'];

                        $progress = $summary['progress'];
                        $stageLabel = $summary['stageLabel'];

                        $evidences = $project->evidences ?? collect();
                       
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

                        $pendingCount = $evidences->where('status', 'pending')->count();
                        $approvedCount = $evidences->where('status', 'approved')->count();
                        $rejectedCount = $evidences->where('status', 'rejected')->count();

                        // Deteksi program (ambil dari lops jika di model project tidak ada)
                        $programName = $project->program ?? optional($project->lops->first())->program_sap ?? '';
                        $isPT2 = (str_replace(' ', '', strtoupper($programName)) === 'PT2');
                        $labelRole = $isPT2 ? 'Teknisi' : 'Waspang';

                        if ($progress == 100) {
                            $stageBadge = 'bg-green-100 text-green-700';
                            $progressColor = 'bg-green-600';
                        } elseif ($stageLabel === 'Finishing') {
                            $stageBadge = 'bg-purple-100 text-purple-700';
                            $progressColor = 'bg-purple-600';
                        } elseif ($stageLabel === 'Pengukuran') {
                            $stageBadge = 'bg-blue-100 text-blue-700';
                            $progressColor = 'bg-blue-600';
                        } elseif ($stageLabel === 'Instalasi') {
                            $stageBadge = 'bg-yellow-100 text-yellow-700';
                            $progressColor = 'bg-yellow-600';
                        } else {
                            $stageBadge = 'bg-red-100 text-red-700';
                            $progressColor = 'bg-red-600';
                        }
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                        <td class="px-5 py-4">
                            <div class="flex items-start gap-3">
                                <!-- <div class="w-10 h-10 rounded-2xl bg-gray-900 text-white dark:bg-white-900 dark:text-white flex items-center justify-center text-xs font-black shrink-0">
                                    {{ $project->status }}
                                </div> -->

                                <div class="min-w-[220px]">
                                    <p class="font-black text-gray-900 dark:text-white leading-snug">
                                        {{ $project->project_name }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        PID: {{ $project->pid ?? '-' }} · {{ strtoupper($project->execution_type ?? '-') }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <td class="px-5 py-4">
                            <p class="font-bold text-gray-800 dark:text-gray-100">
                                {{ $project->lop?->branch ?? '-' }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                STO {{ $project->lop?->sto ?? '-' }}
                            </p>
                        </td>

                        <td class="px-5 py-4">
                            @if($assignedUser)
                                <p class="font-bold text-gray-900 dark:text-white">{{ $assignedUser->name }}</p>
                                <p class="text-xs text-green-600 font-bold">Assigned ({{ $assignedRoleBadge }})</p>
                            @else
                                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-bold">
                                    Belum diassign
                                </span>
                            @endif
                        </td>

                        <td class="px-5 py-4">
                            <span class="px-3 py-1 rounded-full {{ $stageBadge }} text-xs font-black">
                                {{ $stageLabel }}
                            </span>

                            <div class="flex gap-1 mt-2">

                                <span class="w-2.5 h-2.5 rounded-full
                                    {{ in_array($stageLabel, ['Persiapan','Instalasi','Pengukuran','Finishing']) ? 'bg-red-500' : 'bg-gray-300' }}">
                                </span>

                                <span class="w-2.5 h-2.5 rounded-full
                                    {{ in_array($stageLabel, ['Instalasi','Pengukuran','Finishing']) ? 'bg-yellow-500' : 'bg-gray-300' }}">
                                </span>

                                <span class="w-2.5 h-2.5 rounded-full
                                    {{ in_array($stageLabel, ['Pengukuran','Finishing']) ? 'bg-blue-500' : 'bg-gray-300' }}">
                                </span>

                                <span class="w-2.5 h-2.5 rounded-full
                                    {{ $stageLabel == 'Finishing' ? 'bg-green-500' : 'bg-gray-300' }}">
                                </span>

                            </div>
                        </td>

                        <!-- <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1">
                                <span class="px-2 py-1 rounded-lg bg-yellow-100 text-yellow-700 text-[11px] font-black">P {{ $pendingCount }}</span>
                                <span class="px-2 py-1 rounded-lg bg-green-100 text-green-700 text-[11px] font-black">A {{ $approvedCount }}</span>
                                <span class="px-2 py-1 rounded-lg bg-red-100 text-red-700 text-[11px] font-black">R {{ $rejectedCount }}</span>
                            </div>

                            <p class="text-xs text-gray-500 mt-2">
                                BOQ {{ $boqApproved }}/{{ $boqTotal }}
                            </p>
                        </td> -->

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

                            <button type="button"
                                    onclick="toggleMenu(event, 'menu-{{ $project->id_project }}', this)"
                                    class="w-9 h-9 rounded-xl flex items-center justify-center transition-colors duration-200 text-gray-600 hover:bg-gray-200 hover:text-gray-900 dark:text-white dark:hover:bg-gray-400 dark:hover:text-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5h.01M12 12h.01M12 19h.01"/>
                                </svg>
                            </button>

                            {{-- MENU DROPDOWN (Ubah ke Fixed dan Dikelompokkan) --}}
                            <div id="menu-{{ $project->id_project }}"
                                class="action-menu-dropdown hidden fixed w-56 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-2xl z-[9999] overflow-hidden">
                                
                                <div class="flex flex-col text-left py-2">
                                    <button type="button" onclick="openDetailModal('detail-modal-{{ $project->id_project }}')"
                                            class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-800 hover:text-blue-700 transition-colors">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0x" />
                                        </svg>
                                        <span class="font-semibold">Detail Project</span>
                                    </button>
                                    <a href="{{ route('admin.projects.tracking', $project->id_project) }}"
                                    class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-gray-800 hover:text-blue-700 transition-colors">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25A7.5 7.5 0 1119.5 10.5z" />
                                        </svg>
                                        <span class="font-semibold">Tracking Progress</span>
                                    </a>

                                    <button type="button" onclick="openAssignModal('{{ $project->id_project }}', @js($project->project_name), @js($programName))"
                                            class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-amber-50 dark:hover:bg-gray-800 hover:text-amber-700 transition-colors">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                        </svg>
                                        <span class="font-semibold">{{ $assignedUser ? 'Reassign ' . $labelRole : 'Assign ' . $labelRole }}</span>
                                    </button>
                                    
                                    <button type="button" onclick="openKmlModal('{{ $project->id_project }}', @js($project->project_name))"
                                            class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                        </svg>
                                        <span class="font-semibold">Upload KML</span>
                                    </button>
                                    @if($project->kml_file)
                                        <a href="{{ route('projects.view-kml', $project->id_project) }}"
                                        class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
                                            </svg>
                                            <span class="font-semibold">View KML</span>
                                        </a>
                                    @endif

                                    <button type="button" onclick="openEditProjectModal({ id:'{{ $project->id_project }}' })"
                                            class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                         <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                        </svg>
                                        <span class="font-semibold">Edit Data</span>
                                    </button>
                                    <form method="POST" action="{{ route('projects.destroy',$project->id_project) }}" class="m-0">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" onclick="return confirm('Hapus project ini?')"
                                                class="w-full px-4 py-2 text-left text-sm flex items-center gap-3 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                             <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                            <span class="font-semibold">Delete Project</span>
                                        </button>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="mx-auto w-14 h-14 rounded-3xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-2xl mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-icon lucide-trash"><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </div>
                            <p class="font-black text-gray-900 dark:text-white">Belum ada project</p>
                            <p class="text-sm text-gray-500 mt-1">Data project akan tampil di sini setelah upload/import PID.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- DETAIL MODAL AREA - DI LUAR TABLE --}}
@foreach($projects as $project)
    @php
        $summary = $project->progressSummary();

        $progress = $summary['progress'];
        $stageLabel = $summary['stageLabel'];
        $evidences = $project->evidences ?? collect();
        
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

        if ($progress == 100) {
            $stageBadge = 'bg-green-100 text-green-700';
        } elseif ($stageLabel === 'Finishing') {
            $stageBadge = 'bg-purple-100 text-purple-700';
        } elseif ($stageLabel === 'Pengukuran') {
            $stageBadge = 'bg-blue-100 text-blue-700';
        } elseif ($stageLabel === 'Instalasi') {
            $stageBadge = 'bg-yellow-100 text-yellow-700';
        } else {
            $stageBadge = 'bg-red-100 text-red-700';
        }
    @endphp

    <div id="detail-modal-{{ $project->id_project }}"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

        <div class="bg-white dark:bg-gray-900 w-full max-w-5xl max-h-[90vh] rounded-2xl overflow-hidden flex flex-col shadow-2xl">

            <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Detail Project
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $project->project_name }}
                    </p>
                </div>

                <button type="button"
                        onclick="closeDetailModal('detail-modal-{{ $project->id_project }}')"
                        class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 flex items-center justify-center text-2xl hover:bg-gray-100 dark:hover:bg-gray-800">
                    ×
                </button>
            </div>

            <div class="overflow-y-auto">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Branch</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                            {{ $project->lop?->branch ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">STO</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                            {{ $project->lop?->sto ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Mitra</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                            {{ $project->mitra_name ?? $project->lop?->mitra_name ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Jenis Eksekusi</p>
                        <span class="inline-flex mt-1 px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                            {{ strtoupper($project->execution_type ?? '-') }}
                        </span>
                    </div>

                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Progress</p>
                        <span class="inline-flex mt-1 px-3 py-1 rounded-full {{ $stageBadge }} text-xs font-bold">
                            {{ $progress }}% · {{ $stageLabel }}
                        </span>
                    </div>

                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Ditugaskan Kepada</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                            {{ $assignedUser->name ?? 'Belum diassign' }} 
                            @if($assignedUser) <span class="text-xs font-normal text-gray-500">({{ $assignedRoleBadge }})</span> @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Item Designator
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            Total {{ $project->boqItems->count() }} item designator
                        </p>
                    </div>

                    <button type="button"
                            onclick="openBoqModal('{{ $project->id_project }}', @js($project->project_name))"
                            class="h-10 px-4 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-700">
                        + Tambah Designator
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="text-left px-6 py-3 font-semibold text-gray-600 dark:text-gray-300">Designator</th>
                                <th class="text-left px-6 py-3 font-semibold text-gray-600 dark:text-gray-300">Item Pekerjaan</th>
                                <th class="text-left px-6 py-3 font-semibold text-gray-600 dark:text-gray-300">Satuan</th>
                                <th class="text-right px-6 py-3 font-semibold text-gray-600 dark:text-gray-300">Plan</th>
                                <th class="text-center px-6 py-3 font-semibold text-gray-600 dark:text-gray-300">Evidence</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse($project->boqItems as $boq)
                                @php
                                    $boqHasEvidence = $evidences
                                        ->where('stage', 'instalasi')
                                        ->where('evidence_type', 'progress_boq')
                                        ->where('boq_item_id', $boq->id_boq)
                                        ->count() > 0;
                                @endphp

                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <td class="px-6 py-4 font-semibold text-gray-900 dark:text-white">
                                        {{ $boq->designator ?? '-' }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <p class="font-bold text-gray-900 dark:text-white">
                                            {{ $boq->item_name ?? '-' }}
                                        </p>
                                    </td>

                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                        {{ $boq->unit ?? '-' }}
                                    </td>

                                    <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                        {{ $boq->quantity_plan ?? 0 }}
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if($boqHasEvidence)
                                            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold">
                                                Uploaded
                                            </span>
                                        @else
                                            <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-bold">
                                                Belum
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        Belum ada item designator untuk project ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <button type="button"
                        onclick="openAssignModal('{{ $project->id_project }}', @js($project->project_name), @js($programName))"
                        class="h-11 px-6 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                    👷 {{ $assignedUser ? 'Reassign ' . $labelRole : 'Assign ' . $labelRole }}
                </button>

                <button type="button"
                        onclick="openEditProjectModal({
                            id: '{{ $project->id_project }}',
                            project_name: @js($project->project_name),
                            branch: @js($project->lop?->branch),
                            sto: @js($project->lop?->sto),
                            mitra_name: @js($project->mitra_name ?? $project->lop?->mitra_name),
                            status: @js($project->status_project),
                            latitude: @js($project->latitude),
                            longitude: @js($project->longitude),
                            location_address: @js($project->location_address),
                            boq_items: @js($project->boqItems->map(function($boq) {
                                return [
                                    'id_boq' => $boq->id_boq,
                                    'designator_id' => $boq->designator_id, /* INI YANG SEBELUMNYA KURANG */
                                    'designator' => $boq->designator,
                                    'item_name' => $boq->item_name,
                                    'unit' => $boq->unit,
                                    'quantity_plan' => $boq->quantity_plan,
                                ];
                            }))
                        })"
                        class="h-11 px-6 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                    Edit Project
                </button>

                <form method="POST"
                      action="{{ route('projects.destroy', $project->id_project) }}"
                      onsubmit="return confirm('Hapus project ini? Semua BOQ dan eviden terkait bisa ikut terhapus.')">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                            class="h-11 px-6 rounded-xl border border-red-300 bg-white hover:bg-red-50 text-red-600 text-sm font-bold">
                        Delete
                    </button>
                </form>
            </div>

        </div>
    </div>
@endforeach
{{-- PAGINATION LINK SAJA --}}
    @if($projects->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
            {{ $projects->links() }}
        </div>
    @endif

</div>

{{-- ASSIGN MODAL --}}
<div id="assignModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-3 sm:p-4">
    <div class="bg-white dark:bg-gray-900 w-full max-w-lg max-h-[90vh] rounded-2xl overflow-hidden flex flex-col">
        <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div class="min-w-0">
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">Assign Waspang & Teknisi</h2>
                <p id="assignProjectName" class="text-sm text-gray-500 mt-1 truncate"></p>
                {{-- Indikator Tipe Kebutuhan (Otomatis dari JS) --}}
                <span id="assignRoleNeeded" class="inline-block mt-2 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded"></span>
            </div>
            <button type="button" onclick="closeAssignModal()" class="shrink-0 w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 flex items-center justify-center text-2xl leading-none hover:bg-gray-100 dark:hover:bg-gray-800">×</button>
        </div>

        <form method="POST" action="{{ route('projects.assign') }}" class="flex flex-col min-h-0">
            @csrf
            <input type="hidden" name="project_id" id="project_id">
            
            <div class="px-5 py-4 overflow-y-auto">
                <div class="relative mb-4">
                    <input type="text" 
                        id="searchWaspangAssign" 
                        oninput="searchAssignUser(this.value)"
                        placeholder="Cari nama pengguna..." 
                        class="w-full h-11 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none">
                    
                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">🔍</div>
                </div>

                <div class="space-y-3" id="assignUserList">
                    {{-- Loop dari variabel gabungan Waspang & Teknisi --}}
                    @foreach($assignableUsers as $user)
                        @php
                            // (Mencari berdasarkan waspang_id ATAU teknisi_id):
                            $activeCount = \App\Models\ProjectAssignment::where('waspang_id', $user->id_user)
                                            ->orWhere('teknisi_id', $user->id_user)
                                            ->distinct('project_id')
                                            ->count();
                            $isBusy = $activeCount >= 10;
                            $initials = strtoupper(collect(explode(' ', $user->name))->map(fn($word) => substr($word, 0, 1))->take(2)->implode(''));
                        @endphp

                        <label class="block cursor-pointer assign-user-item" 
                            data-name="{{ strtolower($user->name) }}" 
                            data-role="{{ $user->role }}" {{-- Penting untuk filter JS --}}
                            data-project-count="{{ $activeCount }}">

                            {{-- Name diubah jadi assigned_user_id --}}
                            <input type="radio" name="assigned_user_id" value="{{ $user->id_user }}" class="peer sr-only" required>

                            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 p-4 transition peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-full bg-blue-700 text-white flex items-center justify-center text-sm font-bold shrink-0">
                                        {{ $initials }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white truncate">
                                            {{ $user->name }}
                                        </h3>
                                        <p class="text-xs sm:text-sm text-gray-500 flex items-center gap-2">
                                            {{-- Menampilkan Badge Role --}}
                                            <span class="font-bold {{ $user->role == 'teknisi' ? 'text-purple-600' : 'text-blue-600' }}">
                                                {{ ucfirst($user->role) }}
                                            </span>
                                            • {{ $activeCount }} proyek aktif
                                        </p>
                                    </div>
                                    @if($isBusy)
                                        <span class="shrink-0 px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">Overload</span>
                                    @else
                                        <span class="shrink-0 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">Idle</span>
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach

                    <div id="emptyUserSearch" class="hidden rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
                        Pengguna tidak ditemukan.
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <button type="button" onclick="closeAssignModal()" class="h-11 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="h-11 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-black">Assignment</button>
            </div>
        </form>
    </div>
</div>

{{-- IMPORT CSV MODAL --}}
<div id="importModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl overflow-hidden">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    Import LOP dari CSV
                </h2>
                <p class="text-sm text-gray-500">
                    Upload file CSV untuk input banyak project sekaligus
                </p>
            </div>

            <button type="button"
                    onclick="closeImportModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700">
                ×
            </button>
        </div>

        <form method="POST"
              action="{{ route('projects.import.csv') }}"
              enctype="multipart/form-data">
            @csrf

            <div class="p-5 space-y-4">

                <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800 p-4 text-sm text-gray-600 dark:text-gray-400">
                    Format header CSV wajib:
                    <br>
                    <strong>project_name, branch, sto, mitra_name, jenis_eksekusi, status</strong>
                    <br><br>
                    Contoh nilai:
                    <br>
                    jenis_eksekusi: <strong>plan, survey, ogp, finish</strong>
                    <br>
                    status: <strong>active, completed, waiting_ut</strong>
                </div>

                <input type="file"
                       name="csv_file"
                       accept=".csv,text/csv"
                       required
                       class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 text-sm dark:bg-gray-950">

            </div>

            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">

                <button type="button"
                        onclick="closeImportModal()"
                        class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold">
                    Batal
                </button>

                <button type="submit"
                        class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                    Import
                </button>

            </div>

        </form>

    </div>

</div>

{{-- PROJECT MODAL --}}
<div id="projectModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-3 sm:p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-3xl max-h-[90vh] rounded-2xl overflow-hidden flex flex-col">

        <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-gray-200 dark:border-gray-800">

            <div>
                <h2 id="projectModalTitle" class="text-lg font-bold text-gray-900 dark:text-white">
                    Input LOP Baru
                </h2>
                <p class="text-sm text-gray-500">
                    Isi data proyek konstruksi
                </p>
            </div>

            <button type="button"
                    onclick="closeProjectModal()"
                    class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 text-xl hover:bg-gray-100 dark:hover:bg-gray-800">
                ×
            </button>

        </div>

        <form id="projectForm"
              method="POST"
              action="{{ route('projects.store') }}"
              class="flex flex-col min-h-0">
            @csrf

            <input type="hidden" name="_method" id="projectMethod" value="POST">

            <div class="p-5 overflow-y-auto space-y-4">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Nama LOP <span class="text-red-500">*</span>
                    </label>

                    <input type="text"
                           name="project_name"
                           id="project_name"
                           required
                           placeholder="contoh: Pemasangan FTTH Jl. Raya Darmo"
                           class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Branch <span class="text-red-500">*</span>
                        </label>

                        <input type="text"
                               name="branch"
                               id="branch"
                               placeholder="contoh: Surabaya"
                               class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            STO <span class="text-red-500">*</span>
                        </label>

                        <input type="text"
                               name="sto"
                               id="sto"
                               placeholder="contoh: DMO"
                               class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                    </div>

                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Nama Mitra
                    </label>

                    <input type="text"
                           name="mitra_name"
                           id="mitra_name"
                           placeholder="contoh: PT. Telkom Akses"
                           class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                </div>

                {{-- Lokasi Project --}}
                <div class="pt-2">

                    <div class="flex items-center justify-between gap-3 mb-3">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                Lokasi Project
                            </h3>
                            <p class="text-xs text-gray-500">
                                Isi manual atau ambil otomatis dari GPS browser
                            </p>
                        </div>

                        <button type="button"
                                onclick="getProjectLocation()"
                                class="h-9 px-3 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700">
                            Gunakan Lokasi Saat Ini
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Latitude
                            </label>

                            <input type="text"
                                name="latitude"
                                id="latitude"
                                placeholder="-7.257472"
                                class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Longitude
                            </label>

                            <input type="text"
                                name="longitude"
                                id="longitude"
                                placeholder="112.752088"
                                class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                        </div>

                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Alamat / Keterangan Lokasi
                        </label>

                        <input type="text"
                            name="location_address"
                            id="location_address"
                            placeholder="contoh: Jl. Raya Darmo, Surabaya"
                            class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                    </div>

                </div>

                <input type="hidden" name="status" id="status" value="active">

                {{-- Designator BOQ Awal --}}
                <div class="pt-2">

                    <div class="flex items-center justify-between gap-3 mb-3">

                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                Item Designator Awal
                            </h3>
                            <p class="text-xs text-gray-500">
                                Pilih Designator, item dan satuan otomatis terisi
                            </p>
                        </div>

                        <button type="button"
                                onclick="addDesignatorRow()"
                                class="h-9 px-3 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                            + add Designator
                        </button>

                    </div>

                    <div id="designatorContainer" class="space-y-3">

                        <div class="grid grid-cols-12 gap-2 designator-row">

                           <select name="designator_id[]"
                            onchange="fillDesignatorData(this)"
                            class="designator-select col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                                <option value="">Pilih Designator</option>

                                @foreach($designators as $designator)
                                    <option value="{{ $designator->id_designator }}"
                                            data-designator="{{ $designator->designator }}"
                                            data-item="{{ $designator->item_name }}"
                                            data-unit="{{ $designator->unit }}">
                                        {{ $designator->designator }} - {{ $designator->item_name }}
                                    </option>
                                @endforeach
                            </select>

                            <input type="text"
                                   name="boq_item_name[]"
                                   placeholder="Item pekerjaan"
                                   readonly
                                   class="col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

                            <input type="text"
                                   name="boq_unit[]"
                                   placeholder="Satuan"
                                   readonly
                                   class="col-span-5 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

                            <input type="number"
                                   step="0.01"
                                   name="boq_qty[]"
                                   placeholder="0"
                                   class="col-span-5 sm:col-span-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

                            <button type="button"
                                    onclick="removeDesignatorRow(this)"
                                    class="col-span-2 sm:col-span-1 h-10 rounded-xl text-gray-400 hover:text-red-500 text-xl">
                                ×
                            </button>

                        </div>

                    </div>

                </div>

            </div>

            <div class="grid grid-cols-2 gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">

                <button type="button"
                        onclick="closeProjectModal()"
                        class="h-10 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                    Batal
                </button>

                <button type="submit"
                        class="h-10 rounded-xl bg-gray-900 hover:bg-black text-white text-sm font-semibold">
                    Save
                </button>

            </div>

        </form>

    </div>

</div>

    {{-- KML MODAL --}}
    <div id="kmlModal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

        <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl overflow-hidden">

            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                        Upload KML
                    </h2>
                    <p id="kmlProjectName" class="text-sm text-gray-500 truncate">
                        Upload file peta project
                    </p>
                </div>

                <button type="button"
                        onclick="closeKmlModal()"
                        class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 text-xl hover:bg-gray-100 dark:hover:bg-gray-800">
                    ×
                </button>
            </div>

            <form id="kmlForm"
                method="POST"
                enctype="multipart/form-data">
                @csrf

                <div class="p-5 space-y-4">

                    <input type="file"
                        name="kml_file"
                        accept=".kml,.xml"
                        required
                        class="block w-full text-sm border border-gray-300 dark:border-gray-700 rounded-xl cursor-pointer bg-white dark:bg-gray-950 dark:text-gray-300
                                file:mr-3 file:py-2.5 file:px-4 file:rounded-l-xl file:border-0 file:text-sm file:font-bold
                                file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">

                    <div class="rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800 p-3">
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Format file: <b>.kml</b> atau <b>.xml</b>, maksimal 5MB.
                            Jika upload ulang, file lama akan diganti.
                        </p>
                    </div>

                </div>

                <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">

                    <button type="button"
                            onclick="closeKmlModal()"
                            class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold">
                        Batal
                    </button>

                    <button class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                        Upload
                    </button>

                </div>

            </form>

        </div>

    </div>

@include('admin.projects.modals.boq-modal')

@endsection

<script>
function searchAssignUser(keyword) {
        let filter = keyword.toLowerCase();
        let items = document.querySelectorAll('.assign-user-item');
        let hasVisible = false;
        
        // Cek indikator teks untuk mengetahui apakah butuh Teknisi atau Waspang
        let neededText = document.getElementById('assignRoleNeeded').innerText || '';
        let neededRole = neededText.includes('Teknisi') ? 'teknisi' : 'waspang';

        items.forEach(item => {
            let name = item.getAttribute('data-name');
            name = name ? name.toLowerCase() : '';
            let role = item.getAttribute('data-role');
            
            // Logika: Jika Role-nya cocok DAN namanya mengandung huruf yang diketik
            if (role === neededRole && name.includes(filter)) {
                item.style.display = 'block'; 
                hasVisible = true;
            } else {
                item.style.display = 'none';
            }
        });

        // Tampilkan peringatan "Kosong" jika tidak ada yang cocok
        let emptyState = document.getElementById('emptyUserSearch');
        if (emptyState) {
            emptyState.style.display = hasVisible ? 'none' : 'block';
        }
    }

    // FUNGSI 2: Buka Modal
    function openAssignModal(projectId, projectName, program) {
        document.getElementById('project_id').value = projectId;
        document.getElementById('assignProjectName').innerText = projectName;
        
        // Deteksi program PT2
        let prog = program ? String(program).toUpperCase().replace(/\s/g, "") : '';
        let isPT2 = (prog === 'PT2');

        // Ubah teks indikator
        document.getElementById('assignRoleNeeded').innerText = isPT2 ? 'Project ini membutuhkan: Teknisi' : 'Project ini membutuhkan: Waspang';

        // Bersihkan kolom pencarian setiap kali modal dibuka
        let searchInput = document.getElementById('searchWaspangAssign');
        if(searchInput) searchInput.value = '';

        // Panggil fungsi search dengan keyword kosong untuk me-reset daftar list (menyesuaikan role)
        searchAssignUser('');

        // Tampilkan Modal
        document.getElementById('assignModal').classList.remove('hidden');
        document.getElementById('assignModal').classList.add('flex');
    }

function closeAssignModal()
    {
        const modal = document.getElementById('assignModal');

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('searchWaspangAssign');

    if (!searchInput) return;

    searchInput.addEventListener('keyup', function () {

        const keyword = this.value.toLowerCase().trim();

        const items = document.querySelectorAll('.assign-waspang-item');
        const emptyState = document.getElementById('emptyWaspangSearch');

        let visibleCount = 0;

        items.forEach(item => {

            const name = item.dataset.name || '';

            if (name.includes(keyword)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }

        });

        if (emptyState) {
            emptyState.classList.toggle('hidden', visibleCount > 0);
        }

    });

});
</script>

<script>
    function openDetailModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDetailModal(id) {
        const modal = document.getElementById(id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function openImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
        document.getElementById('importModal').classList.add('flex');
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
        document.getElementById('importModal').classList.remove('flex');
    }

    function openProjectModal() {
        const modal = document.getElementById('projectModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.getElementById('projectModalTitle').innerText = 'Input LOP Baru';
        document.getElementById('projectForm').action = "{{ route('projects.store') }}";
        document.getElementById('projectMethod').value = 'POST';
        document.getElementById('projectForm').reset();

        if (document.getElementById('status')) {
            document.getElementById('status').value = 'active';
        }
    }

    function openEditProjectModal(project) {
        document.getElementById('projectModal').classList.remove('hidden');
        document.getElementById('projectModal').classList.add('flex');

        document.getElementById('projectModalTitle').innerText = 'Edit Project & BOQ';
        document.getElementById('projectForm').action = `/projects/update/${project.id}`;
        document.getElementById('projectMethod').value = 'PUT';

        document.getElementById('project_name').value = project.project_name ?? '';
        document.getElementById('branch').value = project.branch ?? '';
        document.getElementById('sto').value = project.sto ?? '';
        document.getElementById('mitra_name').value = project.mitra_name ?? '';
        document.getElementById('latitude').value = project.latitude ?? '';
        document.getElementById('longitude').value = project.longitude ?? '';
        document.getElementById('location_address').value = project.location_address ?? '';

        if (document.getElementById('status')) {
            document.getElementById('status').value = project.status ?? 'active';
        }

        renderEditBoqItems(project.boq_items ?? []);
    }

    // Fungsi Pengisi Data Master Designator ke Field Input
    function fillDesignatorData(select) {
        const row = select.closest('.designator-row');
        const selected = select.options[select.selectedIndex];

        // Mendukung input array untuk item baru (boq_item_name) dan edit lama (existing_item_name)
        const itemNameInput = row.querySelector('input[name="boq_item_name[]"]') || row.querySelector('input[name="existing_item_name[]"]');
        const unitInput = row.querySelector('input[name="boq_unit[]"]') || row.querySelector('input[name="existing_unit[]"]');

        if (itemNameInput) itemNameInput.value = selected.dataset.item || '';
        if (unitInput) unitInput.value = selected.dataset.unit || '';
    }

    // Merender Daftar BOQ yang sudah ada pada Modal Edit (Dilengkapi Dropdown TomSelect)
    function renderEditBoqItems(items) {
        const container = document.getElementById('designatorContainer');
        container.innerHTML = '';

        if (items.length === 0) {
            addDesignatorRow();
            return;
        }

        items.forEach((item) => {
            const row = `
                <div class="grid grid-cols-12 gap-2 designator-row items-center border-b border-gray-100 dark:border-gray-800 pb-2 mb-2">
                    <input type="hidden" name="existing_boq_id[]" value="${item.id_boq ?? ''}">

                    <div class="col-span-12 sm:col-span-4">
                        <select name="existing_designator_id[]"
                                onchange="fillDesignatorData(this)"
                                class="designator-select-edit h-10 text-sm">
                            <option value="${item.designator_id ?? ''}" 
                                    data-item="${item.item_name ?? ''}" 
                                    data-unit="${item.unit ?? ''}" 
                                    selected>
                                ${item.designator ?? ''} - ${item.item_name ?? ''}
                            </option>
                            @foreach($designators as $designator)
                                <option value="{{ $designator->id_designator }}"
                                        data-designator="{{ $designator->designator }}"
                                        data-item="{{ $designator->item_name }}"
                                        data-unit="{{ $designator->unit }}">
                                    {{ $designator->designator }} - {{ $designator->item_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <input type="text"
                           name="existing_item_name[]"
                           value="${item.item_name ?? ''}"
                           readonly
                           class="col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800 focus:ring-0">

                    <input type="text"
                           name="existing_unit[]"
                           value="${item.unit ?? ''}"
                           readonly
                           class="col-span-6 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800 focus:ring-0 text-center">

                    <input type="number"
                           step="0.01"
                           name="existing_qty[]"
                           value="${item.quantity_plan ?? 0}"
                           class="col-span-6 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm text-center">
                </div>
            `;
            container.insertAdjacentHTML('beforeend', row);
        });

        // Inisialisasi TomSelect pada kolom dropdown edit
        container.querySelectorAll('.designator-select-edit').forEach(select => {
            initSingleDesignatorSearch(select);
        });
    }

    // Fungsi Tambah Baris Designator Baru
    function addDesignatorRow() {
        const container = document.getElementById('designatorContainer');
        const row = `
            <div class="grid grid-cols-12 gap-2 designator-row mt-2">
                <div class="col-span-12 sm:col-span-4">
                    <select name="designator_id[]"
                            onchange="fillDesignatorData(this)"
                            class="designator-select h-10 text-sm">
                        <option value="">Pilih designator baru...</option>
                        @foreach($designators as $designator)
                            <option value="{{ $designator->id_designator }}"
                                    data-designator="{{ $designator->designator }}"
                                    data-item="{{ $designator->item_name }}"
                                    data-unit="{{ $designator->unit }}">
                                {{ $designator->designator }} - {{ $designator->item_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <input type="text"
                       name="boq_item_name[]"
                       placeholder="Item pekerjaan"
                       readonly
                       class="col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

                <input type="text"
                       name="boq_unit[]"
                       placeholder="Satuan"
                       readonly
                       class="col-span-5 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800 text-center">

                <input type="number"
                       step="1"
                       name="boq_qty[]"
                       placeholder="Qty Plan"
                       class="col-span-5 sm:col-span-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm text-center">

                <button type="button"
                        onclick="removeDesignatorRow(this)"
                        class="col-span-2 sm:col-span-1 h-10 rounded-xl text-gray-400 hover:text-red-500 text-xl font-bold flex items-center justify-center">
                    ×
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', row);
        const newRow = container.lastElementChild;
        const newSelect = newRow.querySelector('.designator-select');
        initSingleDesignatorSearch(newSelect);
    }

    function removeDesignatorRow(button) {
        const rows = document.querySelectorAll('.designator-row');
        if (rows.length <= 1) {
            const row = button.closest('.designator-row');
            if(row.querySelector('select').tomselect) {
                row.querySelector('select').tomselect.clear();
            }
            row.querySelector('input[name="boq_item_name[]"]').value = '';
            row.querySelector('input[name="boq_unit[]"]').value = '';
            row.querySelector('input[name="boq_qty[]"]').value = '';
            return;
        }
        button.closest('.designator-row').remove();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initDesignatorSearch();
    });

    function initDesignatorSearch() {
        document.querySelectorAll('.designator-select').forEach(select => {
            initSingleDesignatorSearch(select);
        });
    }

    function initSingleDesignatorSearch(select) {
        if (!select || select.tomselect) return;
        new TomSelect(select, {
            create: false,
            placeholder: 'Ketik designator...',
            maxOptions: 1000,
            searchField: ['text'],
            sortField: { field: 'text', direction: 'asc' },
            onChange: function () { fillDesignatorData(select); }
        });
    }

    function closeProjectModal() {
        document.getElementById('projectModal').classList.add('hidden');
        document.getElementById('projectModal').classList.remove('flex');
    }

    // ---------------------------------------------------------
    // FUNGSI AKSI BARIS TABEL: EDIT & HAPUS DESIGNATOR
    // ---------------------------------------------------------
    function editBoqItem(idBoq, designator, plan) {
        // Karena sistem edit sudah tergabung dalam Modal Utama "Edit Project", 
        // Anda bisa langsung menampilkan alert instruksi atau otomatis memicu modal utama
        alert("Silakan klik tombol 'Edit Project' berwarna putih di pojok kanan bawah modal detail ini untuk mengganti designator atau menyesuaikan volume plan.");
    }

    function deleteBoqItem(idBoq) {
        if (!confirm('Peringatan: Yakin ingin menghapus item designator ini dari proyek? (Tindakan ini tidak bisa dibatalkan)')) {
            return;
        }

        // Membuat form POST (spoofed menjadi DELETE) secara dinamis
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/projects/boq/${idBoq}`; // Menunjuk ke route destroyBoq yang baru kita buat

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodOverride = document.createElement('input');
        methodOverride.type = 'hidden';
        methodOverride.name = '_method';
        methodOverride.value = 'DELETE';

        form.appendChild(csrfToken);
        form.appendChild(methodOverride);
        
        document.body.appendChild(form);
        form.submit();
    }

    // ---------------------------------------------------------
    // KUMPULAN FUNGSI LAINNYA
    // ---------------------------------------------------------
    function getProjectLocation() {
        if (!navigator.geolocation) {
            alert('Browser tidak mendukung GPS / Geolocation');
            return;
        }
        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('latitude').value = position.coords.latitude.toFixed(8);
                document.getElementById('longitude').value = position.coords.longitude.toFixed(8);
            },
            function(error) {
                alert('Gagal mengambil lokasi. Pastikan izin lokasi browser aktif.');
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    function openKmlModal(projectId, projectName) {
        const modal = document.getElementById('kmlModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.getElementById('kmlForm').action = `/projects/${projectId}/upload-kml`;
        document.getElementById('kmlProjectName').innerText = projectName;
    }

    function closeKmlModal() {
        const modal = document.getElementById('kmlModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function toggleMenu(event, menuId, btnElement) {
        event.stopPropagation(); // Mencegah klik menyebar ke window
        
        let menu = document.getElementById(menuId);
        let isHidden = menu.classList.contains('hidden');
        
        // 1. Tutup semua dropdown menu yang sedang terbuka
        document.querySelectorAll('.action-menu-dropdown').forEach(el => {
            el.classList.add('hidden');
        });
        
        if (isHidden) {
            // 2. Tampilkan menu
            menu.classList.remove('hidden');
            
            // 3. Ambil koordinat tombol yang di-klik
            let rect = btnElement.getBoundingClientRect();
            
            // 4. Hitung ruang layar yang tersedia
            let menuHeight = menu.offsetHeight;
            let spaceBelow = window.innerHeight - rect.bottom;
            
            // 5. Jika ruang di bawah sempit, buka ke ATAS. Jika luas, buka ke BAWAH.
            if (spaceBelow < menuHeight && rect.top > menuHeight) {
                menu.style.top = (rect.top - menuHeight - 5) + 'px'; // Buka ke Atas
            } else {
                menu.style.top = (rect.bottom + 5) + 'px'; // Buka ke Bawah
            }
            
            // 6. Rata Kanan dengan tombol
            menu.style.left = (rect.right - menu.offsetWidth) + 'px';
        }
    }

        // TUTUP MENU SAAT KLIK DI LUAR
        window.addEventListener('click', function(e) {
            if (!e.target.closest('.action-menu-container')) {
                document.querySelectorAll('.action-menu-dropdown').forEach(el => el.classList.add('hidden'));
            }
        });

        // TUTUP MENU SAAT TABEL DI-SCROLL (Agar menu fixed tidak melayang tertinggal)
        let tableContainer = document.querySelector('.overflow-x-auto');
        if(tableContainer) {
            tableContainer.addEventListener('scroll', function() {
                document.querySelectorAll('.action-menu-dropdown').forEach(el => el.classList.add('hidden'));
            });
        }

    document.addEventListener('click', function(e){
        if(!e.target.closest('.relative')){
            document.querySelectorAll('[id^="menu-"]').forEach(menu=>{
                menu.classList.add('hidden');
            });
        }
    });
</script>

<script>
    // WINDOW BOQ ITEMS SEEDER
    window.projectBoqItems = {
        @foreach($projects as $project)
            "{{ $project->id_project }}": [
                @foreach($project->boqItems as $boq)
                    {
                        id_boq: @js($boq->id_boq ?? null),
                        designator_id: @js($boq->designator_id ?? null),
                        designator: @js($boq->designator ?? '-'),
                        item_name: @js($boq->item_name ?? '-'),
                        unit: @js($boq->unit ?? '-'),
                        quantity_plan: @js($boq->quantity_plan ?? 0),
                    },
                @endforeach
            ],
        @endforeach
    };
</script>

<script>
    // FUNGSI MODAL BOQ (TAMBAH DESIGNATOR DARI LUAR)
    function openBoqModal(projectId, projectName) {
        const modal = document.getElementById('boqModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.getElementById('boq_project_id').value = projectId;
        document.getElementById('boqProjectName').innerText = projectName;

        renderExistingBoq(projectId);
        resetBoqRows();

        setTimeout(() => {
            initBoqDesignatorSearch();
        }, 50);
    }

    function closeBoqModal() {
        const modal = document.getElementById('boqModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function renderExistingBoq(projectId) {
        const list = document.getElementById('existingBoqList');
        const count = document.getElementById('existingBoqCount');
        const items = window.projectBoqItems[projectId] || [];

        count.innerText = `${items.length} item`;

        if (items.length === 0) {
            list.innerHTML = `<div class="p-4 text-sm text-gray-500 text-center">Belum ada item designator pada project ini.</div>`;
            return;
        }

        list.innerHTML = items.map((item) => {
            return `
                <div class="p-3 flex items-start justify-between gap-3 border-b border-gray-100 dark:border-gray-800">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">${item.item_name}</p>
                        <p class="text-xs text-gray-500 mt-0.5">${item.designator} · ${item.unit}</p>
                    </div>
                    <span class="shrink-0 px-2.5 py-1 rounded-lg bg-blue-100 text-blue-700 text-[11px] font-bold">
                        Plan ${item.quantity_plan}
                    </span>
                </div>
            `;
        }).join('');
    }

    function resetBoqRows() {
        const container = document.getElementById('boqContainer');
        const rows = container.querySelectorAll('.boq-row');

        rows.forEach((row, index) => {
            if (index > 0) row.remove();
        });

        const firstRow = container.querySelector('.boq-row');
        if (!firstRow) return;

        const select = firstRow.querySelector('select');
        if (select && select.tomselect) {
            select.tomselect.clear();
        } else if (select) {
            select.value = '';
        }

        firstRow.querySelector('input[name="boq_item_name[]"]').value = '';
        firstRow.querySelector('input[name="boq_unit[]"]').value = '';
        firstRow.querySelector('input[name="boq_qty[]"]').value = '';
    }

    function fillBoqDesignatorData(select) {
        const row = select.closest('.boq-row');
        const selected = select.options[select.selectedIndex];
        row.querySelector('input[name="boq_item_name[]"]').value = selected.dataset.item || '';
        row.querySelector('input[name="boq_unit[]"]').value = selected.dataset.unit || '';
    }

    function addBoqRow() {
        const container = document.getElementById('boqContainer');
        const row = `
            <div class="grid grid-cols-12 gap-2 boq-row mt-2">
                <select name="designator_id[]"
                        onchange="fillBoqDesignatorData(this)"
                        class="boq-designator-select col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                    <option value="">Cari designator...</option>
                    @foreach($designators as $designator)
                        <option value="{{ $designator->id_designator }}"
                                data-designator="{{ $designator->designator }}"
                                data-item="{{ $designator->item_name }}"
                                data-unit="{{ $designator->unit }}">
                            {{ $designator->designator }} - {{ $designator->item_name }}
                        </option>
                    @endforeach
                </select>

                <input type="text"
                       name="boq_item_name[]"
                       placeholder="Item pekerjaan"
                       readonly
                       class="col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

                <input type="text"
                       name="boq_unit[]"
                       placeholder="Satuan"
                       readonly
                       class="col-span-5 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800 text-center">

                <input type="number"
                       step="0.01"
                       name="boq_qty[]"
                       placeholder="0"
                       class="col-span-5 sm:col-span-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm text-center">

                <button type="button"
                        onclick="removeBoqRow(this)"
                        class="col-span-2 sm:col-span-1 h-10 rounded-xl text-gray-400 hover:text-red-500 text-xl font-bold flex items-center justify-center">
                    ×
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', row);
        const newRow = container.lastElementChild;
        const newSelect = newRow.querySelector('.boq-designator-select');
        initSingleBoqDesignatorSearch(newSelect);
    }

    function removeBoqRow(button) {
        const rows = document.querySelectorAll('.boq-row');
        if (rows.length <= 1) {
            const row = button.closest('.boq-row');
            const select = row.querySelector('select');
            if (select.tomselect) select.tomselect.clear();
            else select.value = '';

            row.querySelector('input[name="boq_item_name[]"]').value = '';
            row.querySelector('input[name="boq_unit[]"]').value = '';
            row.querySelector('input[name="boq_qty[]"]').value = '';
            return;
        }
        button.closest('.boq-row').remove();
    }

    function initSingleBoqDesignatorSearch(select) {
        if (!select || select.tomselect) return;
        new TomSelect(select, {
            create: false,
            placeholder: 'Cari designator...',
            maxOptions: 1000,
            searchField: ['text'],
            sortField: { field: 'text', direction: 'asc' },
            onChange: function () { fillBoqDesignatorData(select); }
        });
    }

    function initBoqDesignatorSearch() {
        document.querySelectorAll('.boq-designator-select').forEach(select => {
            initSingleBoqDesignatorSearch(select);
        });
    }
</script>



