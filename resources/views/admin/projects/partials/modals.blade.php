{{-- DETAIL MODAL LOOP --}}
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

        if ($progress == 100) { $stageBadge = 'bg-green-100 text-green-700'; } 
        elseif ($stageLabel === 'Finishing') { $stageBadge = 'bg-purple-100 text-purple-700'; } 
        elseif ($stageLabel === 'Pengukuran') { $stageBadge = 'bg-blue-100 text-blue-700'; } 
        elseif ($stageLabel === 'Instalasi') { $stageBadge = 'bg-yellow-100 text-yellow-700'; } 
        else { $stageBadge = 'bg-red-100 text-red-700'; }
    @endphp

    <div id="detail-modal-{{ $project->id_project }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="bg-white dark:bg-gray-900 w-full max-w-5xl max-h-[90vh] rounded-2xl overflow-hidden flex flex-col shadow-2xl">
            <div class="flex items-start justify-between px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">Detail Project</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $project->project_name }}</p>
                </div>
                <button type="button" onclick="closeDetailModal('detail-modal-{{ $project->id_project }}')" class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 flex items-center justify-center text-2xl hover:bg-gray-100 dark:hover:bg-gray-800">×</button>
            </div>

            <div class="overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                    <div><p class="text-xs uppercase text-gray-400 font-semibold">Branch</p><p class="text-sm font-bold dark:text-white mt-1">{{ $project->lop?->branch ?? '-' }}</p></div>
                    <div><p class="text-xs uppercase text-gray-400 font-semibold">STO</p><p class="text-sm font-bold dark:text-white mt-1">{{ $project->lop?->sto ?? '-' }}</p></div>
                    <div><p class="text-xs uppercase text-gray-400 font-semibold">Mitra</p><p class="text-sm font-bold dark:text-white mt-1">{{ $project->mitra_name ?? '-' }}</p></div>
                    <div><p class="text-xs uppercase text-gray-400 font-semibold">Progress</p><span class="inline-flex mt-1 px-3 py-1 rounded-full {{ $stageBadge }} text-xs font-bold">{{ $progress }}% · {{ $stageLabel }}</span></div>
                    <div><p class="text-xs uppercase text-gray-400 font-semibold">Ditugaskan Kepada</p><p class="text-sm font-bold dark:text-white mt-1">{{ $assignedUser->name ?? 'Belum diassign' }} @if($assignedUser) <span class="text-xs font-normal text-gray-500">({{ $assignedRoleBadge }})</span> @endif</p></div>
                </div>

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                    <div>
                        <h3 class="text-lg font-bold dark:text-white">Item Designator</h3>
                        <p class="text-xs text-gray-500">Total {{ $project->boqItems->count() }} item</p>
                    </div>
                    <button type="button" onclick="openBoqModal('{{ $project->id_project }}', '{{ addslashes($project->project_name) }}')" class="h-10 px-4 rounded-xl bg-blue-600 text-white text-sm font-bold hover:bg-blue-700">+ Tambah Designator</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="text-left px-6 py-3 font-semibold text-gray-600 dark:text-gray-300">Designator</th>
                                <th class="text-left px-6 py-3 font-semibold text-gray-600 dark:text-gray-300">Item Pekerjaan</th>
                                <th class="text-left px-6 py-3 font-semibold text-gray-600 dark:text-gray-300">Satuan</th>
                                <th class="text-right px-6 py-3 font-semibold text-gray-600 dark:text-gray-300">Plan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach($project->boqItems as $boq)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-6 py-4 font-semibold dark:text-white">{{ $boq->designator ?? '-' }}</td>
                                    <td class="px-6 py-4 font-bold dark:text-white">{{ $boq->item_name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $boq->unit ?? '-' }}</td>
                                    <td class="px-6 py-4 text-right font-bold dark:text-white">{{ $boq->quantity_plan ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                <button type="button" onclick="openAssignModal('{{ $project->id_project }}', '{{ addslashes($project->project_name) }}', '{{ addslashes($programName) }}')" class="h-11 px-6 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-bold dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800">👷 {{ $assignedUser ? 'Reassign ' . $labelRole : 'Assign ' . $labelRole }}</button>
            </div>
        </div>
    </div>
@endforeach

{{-- ASSIGN MODAL --}}
<div id="assignModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-3 sm:p-4">
    <div class="bg-white dark:bg-gray-900 w-full max-w-lg max-h-[90vh] rounded-2xl overflow-hidden flex flex-col">
        <div class="flex items-start justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-xl font-bold dark:text-white">Assign Waspang & Teknisi</h2>
                <p id="assignProjectName" class="text-sm text-gray-500 mt-1"></p>
                <span id="assignRoleNeeded" class="inline-block mt-2 px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded"></span>
            </div>
            <button type="button" onclick="closeAssignModal()" class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 flex items-center justify-center text-2xl dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800">×</button>
        </div>
        <form method="POST" action="{{ route('projects.assign') }}" class="flex flex-col min-h-0">
            @csrf
            <input type="hidden" name="project_id" id="project_id">
            <div class="px-5 py-4 overflow-y-auto">
                <input type="text" id="searchWaspangAssign" oninput="searchAssignUser(this.value)" placeholder="Cari nama pengguna..." class="w-full h-11 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white px-4 mb-4 text-sm focus:ring-blue-600 outline-none">
                <div class="space-y-3" id="assignUserList">
                    @foreach($assignableUsers as $user)
                        @php
                            $activeCount = \App\Models\ProjectAssignment::where('waspang_id', $user->id_user)->orWhere('teknisi_id', $user->id_user)->distinct('project_id')->count();
                        @endphp
                        <label class="block cursor-pointer assign-user-item" data-name="{{ strtolower($user->name) }}" data-role="{{ $user->role }}">
                            <input type="radio" name="assigned_user_id" value="{{ $user->id_user }}" class="peer sr-only" required>
                            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-4 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/30 flex items-center gap-3">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-bold dark:text-white">{{ $user->name }}</h3>
                                    <p class="text-sm text-gray-500"><span class="font-bold {{ $user->role == 'teknisi' ? 'text-purple-600' : 'text-blue-600' }}">{{ ucfirst($user->role) }}</span> • {{ $activeCount }} proyek aktif</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                    <div id="emptyUserSearch" class="hidden rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">Pengguna tidak ditemukan.</div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                <button type="button" onclick="closeAssignModal()" class="h-11 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="h-11 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 text-white text-sm font-semibold hover:bg-black">Assign</button>
            </div>
        </form>
    </div>
</div>

{{-- IMPORT CSV MODAL --}}
<div id="importModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="bg-white dark:bg-gray-900 w-full max-w-lg rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-bold dark:text-white">Import LOP dari CSV</h2>
            </div>
            <button type="button" onclick="closeImportModal()" class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800">×</button>
        </div>
        <form method="POST" action="{{ route('projects.import.csv') }}" enctype="multipart/form-data">
            @csrf
            <div class="p-5 space-y-4">
                <input type="file" name="csv_file" accept=".csv,text/csv" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-3 text-sm dark:bg-gray-950 dark:text-white">
            </div>
            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                <button type="button" onclick="closeImportModal()" class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-sm font-semibold">Batal</button>
                <button type="submit" class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Import</button>
            </div>
        </form>
    </div>
</div>

{{-- PROJECT MODAL (INPUT/EDIT) --}}
<div id="projectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-3 sm:p-4">
    <div class="bg-white dark:bg-gray-900 w-full max-w-3xl max-h-[90vh] rounded-2xl overflow-hidden flex flex-col">
        <div class="flex items-start justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <h2 id="projectModalTitle" class="text-lg font-bold dark:text-white">Input LOP Baru</h2>
            <button type="button" onclick="closeProjectModal()" class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-xl hover:bg-gray-100 dark:hover:bg-gray-800">×</button>
        </div>
        <form id="projectForm" method="POST" action="{{ route('projects.store') }}" class="flex flex-col min-h-0">
            @csrf
            <input type="hidden" name="_method" id="projectMethod" value="POST">
            <div class="p-5 overflow-y-auto space-y-4">
                <div>
                    <label class="block text-sm font-semibold dark:text-gray-300 mb-1">Nama LOP *</label>
                    <input type="text" name="project_name" id="project_name" required class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold dark:text-gray-300 mb-1">Branch *</label>
                        <input type="text" name="branch" id="branch" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold dark:text-gray-300 mb-1">STO *</label>
                        <input type="text" name="sto" id="sto" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold dark:text-gray-300 mb-1">Nama Mitra</label>
                    <input type="text" name="mitra_name" id="mitra_name" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                </div>
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold dark:text-white">Lokasi Project</h3>
                        <button type="button" onclick="getProjectLocation()" class="h-9 px-3 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700">Gunakan GPS</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div><input type="text" name="latitude" id="latitude" placeholder="Latitude" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm"></div>
                        <div><input type="text" name="longitude" id="longitude" placeholder="Longitude" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm"></div>
                    </div>
                    <div class="mt-4"><input type="text" name="location_address" id="location_address" placeholder="Alamat" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm"></div>
                </div>
                <input type="hidden" name="status" id="status" value="active">
                <div class="pt-2">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold dark:text-white">Item Designator</h3>
                        <button type="button" onclick="addDesignatorRow()" class="h-9 px-3 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-xs font-semibold">+ add Designator</button>
                    </div>
                    <div id="designatorContainer" class="space-y-3"></div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                <button type="button" onclick="closeProjectModal()" class="h-10 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-sm font-semibold">Batal</button>
                <button type="submit" class="h-10 rounded-xl bg-gray-900 dark:bg-white dark:text-gray-900 text-white text-sm font-semibold">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- KML MODAL --}}
<div id="kmlModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div><h2 class="text-lg font-bold dark:text-white">Upload KML</h2><p id="kmlProjectName" class="text-sm text-gray-500 truncate"></p></div>
            <button type="button" onclick="closeKmlModal()" class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-xl">×</button>
        </div>
        <form id="kmlForm" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="p-5 space-y-4">
                <input type="file" name="kml_file" accept=".kml,.xml" required class="block w-full text-sm border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-950 dark:text-white file:mr-3 file:py-2.5 file:px-4 file:bg-blue-50 file:text-blue-700">
            </div>
            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                <button type="button" onclick="closeKmlModal()" class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-sm font-semibold">Batal</button>
                <button class="h-10 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">Upload</button>
            </div>
        </form>
    </div>
</div>