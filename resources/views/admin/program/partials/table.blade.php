<div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-black text-gray-900 dark:text-white">Daftar Project {{ $programName }}</h2>
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

                        // Karena file ini untuk OSP, NODEB, dsb, label default adalah Waspang
                        $labelRole = 'Waspang';

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
                                
                                {{-- MENU DROPDOWN LENGKAP --}}
                                <div id="menu-{{ $project->id_project }}" class="action-menu-dropdown hidden fixed w-56 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-2xl z-[9999] overflow-hidden">
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
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">Belum ada project {{ $programName }}.</td>
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