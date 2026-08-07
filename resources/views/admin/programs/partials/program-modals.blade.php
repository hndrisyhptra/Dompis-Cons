{{-- DETAIL MODAL AREA --}}
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

{{-- PROJECT MODAL (Edit & Input Baru) --}}
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

{{-- IMPORT MODAL (Jika masih dipakai) --}}
{{-- Paste seluruh HTML <div id="importModal"> Anda di sini --}}

@include('admin.projects.modals.boq-modal')