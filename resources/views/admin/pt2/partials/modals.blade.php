{{-- DETAIL MODAL LOOP UNTUK LOP PT 2 --}}
@foreach($projects as $project)
    @foreach($project->lops as $lop)
        @php
            $summary = $lop->progressSummary();
            $progress = $summary['progress'];
            $stageLabel = $summary['stageLabel'];
            
            $assignedUser = $lop->assignment->teknisi ?? null;

            if ($progress == 100) { $stageBadge = 'bg-green-100 text-green-700'; } 
            elseif ($stageLabel === 'Finishing') { $stageBadge = 'bg-purple-100 text-purple-700'; } 
            elseif ($stageLabel === 'Pengukuran') { $stageBadge = 'bg-blue-100 text-blue-700'; } 
            elseif ($stageLabel === 'Instalasi') { $stageBadge = 'bg-yellow-100 text-yellow-700'; } 
            else { $stageBadge = 'bg-red-100 text-red-700'; }
        @endphp

        {{-- 1. MODAL DETAIL LOP --}}
        <div id="detail-modal-pt2-{{ $lop->id_pt2_lop }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
            <div class="bg-white dark:bg-gray-900 w-full max-w-5xl max-h-[90vh] rounded-2xl overflow-hidden flex flex-col shadow-2xl">
                <div class="flex items-start justify-between px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Detail LOP (PT 2)</h2>
                        <p class="text-sm text-gray-500 mt-1">{{ $lop->lop_name }} (IHLD: {{ $lop->id_ihld ?? '-' }})</p>
                    </div>
                    <button type="button" onclick="closeDetailModalPt2('detail-modal-pt2-{{ $lop->id_pt2_lop }}')" class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 flex items-center justify-center text-2xl dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800">×</button>
                </div>

                <div class="overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 px-6 py-5 border-b border-gray-200 dark:border-gray-800">
                        <div><p class="text-xs uppercase text-gray-400 font-semibold">PID / SAP</p><p class="text-sm font-bold dark:text-white mt-1">{{ $project->pid ?? '-' }} / {{ $project->pid_sap ?? '-' }}</p></div>
                        <div><p class="text-xs uppercase text-gray-400 font-semibold">Branch & STO</p><p class="text-sm font-bold dark:text-white mt-1">{{ $lop->branch ?? $project->branch ?? '-' }} - STO {{ $lop->sto ?? $project->sto ?? '-' }}</p></div>
                        <div><p class="text-xs uppercase text-gray-400 font-semibold">Tematik</p><p class="text-sm font-bold dark:text-white mt-1">{{ $lop->tematik ?? '-' }}</p></div>
                        <div><p class="text-xs uppercase text-gray-400 font-semibold">Progress</p><span class="inline-flex mt-1 px-3 py-1 rounded-full {{ $stageBadge }} text-xs font-bold">{{ $progress }}% · {{ $stageLabel }}</span></div>
                        <div><p class="text-xs uppercase text-gray-400 font-semibold">Teknisi Bertugas</p><p class="text-sm font-bold dark:text-white mt-1">{{ $assignedUser->name ?? 'Belum diassign' }}</p></div>
                    </div>

                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                        <div>
                            <h3 class="text-lg font-bold dark:text-white">Item Designator (BOQ)</h3>
                            <p class="text-xs text-gray-500">Total {{ $lop->boqItems->count() }} item</p>
                        </div>
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
                                @forelse($lop->boqItems as $boq)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="px-6 py-4 font-semibold dark:text-white">{{ $boq->designator ?? '-' }}</td>
                                        <td class="px-6 py-4 font-bold dark:text-white">{{ $boq->item_name ?? '-' }}</td>
                                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $boq->unit ?? '-' }}</td>
                                        <td class="px-6 py-4 text-right font-bold dark:text-white">{{ $boq->quantity_plan ?? 0 }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-6 text-center text-gray-500">Belum ada data BOQ untuk LOP ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
                    <button type="button" onclick="openAssignModalPt2({{ $project->id_pt2_project }}, {{ $lop->id_pt2_lop }}, '{{ addslashes($lop->lop_name) }}')" class="h-11 px-6 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-bold dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800">👷 {{ $assignedUser ? 'Reassign Teknisi' : 'Assign Teknisi' }}</button>
                </div>
            </div>
        </div>

        {{-- 2. MODAL EDIT LOP (Data Induk & GPS) --}}
        <div id="edit-lop-modal-{{ $lop->id_pt2_lop }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-3 sm:p-4">
            <div class="bg-white dark:bg-gray-900 w-full max-w-2xl max-h-[90vh] rounded-2xl overflow-hidden flex flex-col shadow-2xl">
                <div class="flex items-start justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h2 class="text-lg font-bold dark:text-white">Edit Data LOP</h2>
                    <button type="button" onclick="closeEditLopModalPt2('edit-lop-modal-{{ $lop->id_pt2_lop }}')" class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-xl hover:bg-gray-100 dark:hover:bg-gray-800">×</button>
                </div>
                
                {{-- Endpoint Update LOP (Harus Anda buatkan Route & Function di AdminPt2Controller nanti) --}}
                <form method="POST" action="#" class="flex flex-col min-h-0">
                    @csrf
                    @method('PUT')
                    
                    <div class="p-5 overflow-y-auto space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold dark:text-gray-300 mb-1">Nama LOP *</label>
                                <input type="text" name="lop_name" value="{{ $lop->lop_name }}" required class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold dark:text-gray-300 mb-1">ID IHLD</label>
                                <input type="text" name="id_ihld" value="{{ $lop->id_ihld }}" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold dark:text-gray-300 mb-1">Branch</label>
                                <input type="text" name="branch" value="{{ $lop->branch }}" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold dark:text-gray-300 mb-1">STO</label>
                                <input type="text" name="sto" value="{{ $lop->sto }}" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold dark:text-gray-300 mb-1">Nama Mitra</label>
                                <input type="text" name="mitra_name" value="{{ $lop->mitra_name }}" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold dark:text-gray-300 mb-1">Tematik</label>
                                <input type="text" name="tematik" value="{{ $lop->tematik }}" class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                        <button type="button" onclick="closeEditLopModalPt2('edit-lop-modal-{{ $lop->id_pt2_lop }}')" class="h-10 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-sm font-semibold">Batal</button>
                        <button type="submit" class="h-10 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Update LOP</button>
                    </div>
                </form>
            </div>
        </div>

    @endforeach
@endforeach

{{-- 3. ASSIGN TEKNISI MODAL PT 2 --}}
<div id="assignModalPt2" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-3 sm:p-4">
    <div class="bg-white dark:bg-gray-900 w-full max-w-lg max-h-[90vh] rounded-2xl overflow-hidden flex flex-col shadow-2xl">
        <div class="flex items-start justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-xl font-bold dark:text-white">Assign Teknisi (PT 2)</h2>
                <p id="assignLopName" class="text-sm text-gray-500 mt-1"></p>
                <span class="inline-block mt-2 px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded">Dibutuhkan: Teknisi</span>
            </div>
            <button type="button" onclick="closeAssignModalPt2()" class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 flex items-center justify-center text-2xl dark:text-white hover:bg-gray-100 dark:hover:bg-gray-800">×</button>
        </div>
        
        <form method="POST" action="{{ route('admin.pt2.assign.store') }}" class="flex flex-col min-h-0">
            @csrf
            <input type="hidden" name="pt2_project_id" id="pt2_project_id_input">
            <input type="hidden" name="pt2_lop_id" id="pt2_lop_id_input">
            
            <div class="px-5 py-4 overflow-y-auto">
                <input type="text" id="searchTeknisiPt2" oninput="searchAssignTeknisiPt2(this.value)" placeholder="Cari nama Teknisi..." class="w-full h-11 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white px-4 mb-4 text-sm focus:ring-emerald-600 outline-none">
                
                <div class="space-y-3" id="assignTeknisiList">
                    @foreach($assignableUsers->where('role', 'teknisi') as $user)
                        @php
                            $activeCount = \App\Models\Pt2Assignment::where('teknisi_id', $user->id_user)->count();
                        @endphp
                        <label class="block cursor-pointer assign-teknisi-item" data-name="{{ strtolower($user->name) }}">
                            <input type="radio" name="assigned_user_id" value="{{ $user->id_user }}" class="peer sr-only" required>
                            <div class="rounded-2xl border border-gray-200 dark:border-gray-700 p-4 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 dark:peer-checked:bg-emerald-900/30 flex items-center gap-3 transition">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-bold dark:text-white">{{ $user->name }}</h3>
                                    <p class="text-sm text-gray-500"><span class="font-bold text-emerald-600">Teknisi PT 2</span> • {{ $activeCount }} LOP aktif</p>
                                </div>
                            </div>
                        </label>
                    @endforeach
                    <div id="emptyTeknisiSearch" class="hidden rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">Teknisi tidak ditemukan.</div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                <button type="button" onclick="closeAssignModalPt2()" class="h-11 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="h-11 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Assign Teknisi</button>
            </div>
        </form>
    </div>
</div>

{{-- 4. KML MODAL PT 2 --}}
<div id="kmlModalPt2" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-2xl overflow-hidden shadow-2xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-bold dark:text-white">Upload KML (PT 2)</h2>
                <p id="kmlLopName" class="text-sm text-gray-500 truncate"></p>
            </div>
            <button type="button" onclick="closeKmlModalPt2()" class="w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-xl hover:bg-gray-100 dark:hover:bg-gray-800">×</button>
        </div>
        
        {{-- Endpoint Route Upload KML PT2 (Sesuaikan nanti) --}}
        <form id="kmlFormPt2" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="p-5 space-y-4">
                <input type="file" name="kml_file" accept=".kml,.xml" required class="block w-full text-sm border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-950 dark:text-white file:mr-3 file:py-2.5 file:px-4 file:bg-emerald-50 file:text-emerald-700">
            </div>
            <div class="flex justify-end gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                <button type="button" onclick="closeKmlModalPt2()" class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 dark:text-white text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">Batal</button>
                <button class="h-10 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Upload</button>
            </div>
        </form>
    </div>
</div>