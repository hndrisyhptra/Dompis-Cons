{{-- Project Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">


   @php
    $summary = $project->progressSummary();

    $persiapanDone = $summary['persiapanDone'];
    $instalasiDone = $summary['instalasiDone'];
    $pengukuranDone = $summary['pengukuranDone'];
    $finishingDone = $summary['finishingDone'];

    $boqTotal = $summary['materialTotal'];
    $boqApproved = $summary['instalasiApproved'];
    $finishingApproved = $summary['finishingApproved'];

    $progress = $summary['progress'];
    $stageLabel = $summary['stageLabel'];

    $evidences = $project->evidences ?? collect();
    $waspang = optional($project->assignment)->waspang;

    $pendingCount = $evidences->where('status', 'pending')->count();
    $approvedCount = $evidences->where('status', 'approved')->count();
    $rejectedCount = $evidences->where('status', 'rejected')->count();

    if ($progress == 100) {
        $accentColor = 'bg-green-600';
        $borderColor = 'border-l-green-600';
        $progressColor = 'bg-green-600';
        $badgeClass = 'bg-green-100 text-green-700';
    } elseif ($stageLabel === 'Finishing') {
        $accentColor = 'bg-purple-600';
        $borderColor = 'border-l-purple-600';
        $progressColor = 'bg-purple-600';
        $badgeClass = 'bg-purple-100 text-purple-700';
    } elseif ($stageLabel === 'Pengukuran') {
        $accentColor = 'bg-blue-500';
        $borderColor = 'border-l-blue-500';
        $progressColor = 'bg-blue-500';
        $badgeClass = 'bg-blue-100 text-blue-700';
    } elseif ($stageLabel === 'Instalasi') {
        $accentColor = 'bg-yellow-600';
        $borderColor = 'border-l-yellow-600';
        $progressColor = 'bg-yellow-600';
        $badgeClass = 'bg-yellow-100 text-yellow-700';
    } else {
        $accentColor = 'bg-red-500';
        $borderColor = 'border-l-red-500';
        $progressColor = 'bg-red-500';
        $badgeClass = 'bg-red-100 text-red-700';
    }
@endphp

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 border-l-4 {{ $borderColor }} rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition">

        <div class="h-1 {{ $accentColor }}"></div>

        <div class="p-4">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-3">

                <div class="min-w-0">

                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-[10px] font-bold">
                            #{{ $project->id_project }}
                        </span>

                        <span class="px-2 py-0.5 rounded-lg {{ $badgeClass }} text-[10px] font-bold">
                            {{ $stageLabel }}
                        </span>
                    </div>

                    <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                        {{ $project->project_name }}
                    </h2>

                    <p class="text-[11px] text-gray-500 mt-1 truncate">
                        {{ $project->lop?->branch }} · {{ $project->lop?->sto }} · {{ $project->execution_type }}
                    </p>

                </div>

                <div class="text-right shrink-0">
                    <p class="text-[10px] text-gray-500">Progress</p>
                    <p class="text-xl font-black text-gray-900 dark:text-white">
                        {{ $progress }}%
                    </p>
                </div>

            </div>

            {{-- Meta --}}
            <div class="grid grid-cols-2 gap-2 mt-3">

                <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-2">
                    <p class="text-[10px] text-gray-500">Nama Waspang</p>
                    <p class="text-[11px] font-bold text-gray-900 dark:text-white truncate">
                        {{ $waspang->name ?? 'Belum diassign' }}
                    </p>
                </div>

                <div class="rounded-xl bg-gray-50 dark:bg-gray-800 p-2">
                    <p class="text-[10px] text-gray-500">BOQ Approved</p>
                    <p class="text-[11px] font-bold text-gray-900 dark:text-white">
                        Instalasi {{ $boqApproved }}/{{ $boqTotal }} item
                    </p>
                </div>

            </div>

            {{-- Progress Bar --}}
            <div class="mt-3 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="h-full {{ $progressColor }} rounded-full"
                     style="width: {{ $progress }}%">
                </div>
            </div>

            {{-- Step Badges --}}
            <div class="grid grid-cols-4 gap-1.5 mt-3 text-center text-[10px] font-bold">

                <div class="rounded-lg py-1 {{ $persiapanDone ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500' }}">
                    Persiapan
                </div>

                <div class="rounded-lg py-1 {{ $instalasiDone ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500' }}">
                    Instalasi
                </div>

                <div class="rounded-lg py-1 {{ $pengukuranDone ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                    Pengukuran
                </div>

                <div class="rounded-lg py-1 {{ $finishingDone ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    Finishing
                </div>

            </div>

            {{-- Approval Summary --}}
            <div class="flex items-center justify-between mt-3">

                <div class="flex items-center gap-1.5 text-[10px] font-bold">

                    <span class="px-2 py-1 rounded-lg bg-yellow-100 text-yellow-700">
                        P {{ $pendingCount }}
                    </span>

                    <span class="px-2 py-1 rounded-lg bg-green-100 text-green-700">
                        A {{ $approvedCount }}
                    </span>

                    <span class="px-2 py-1 rounded-lg bg-red-100 text-red-700">
                        R {{ $rejectedCount }}
                    </span>

                </div>

                <button type="button"
                        onclick="openDetailModal('detail-modal-{{ $project->id_project }}')"
                        class="h-8 px-3 rounded-xl bg-gray-900 text-white text-[11px] font-bold hover:bg-black transition">
                    Detail
                </button>

            </div>

            {{-- Actions --}}
            <div class="grid grid-cols-2 gap-2 mt-3">

              <button type="button"
                        onclick="openKmlModal('{{ $project->id_project }}', @js($project->project_name))"
                        class="h-9 inline-flex items-center justify-center rounded-xl border border-blue-300 text-blue-600 text-xs font-bold hover:bg-blue-50 transition">
                    Upload KML
                </button>

                @if($project->kml_file)
                    <a href="{{ route('projects.view-kml', $project->id_project) }}"
                    class="h-9 inline-flex items-center justify-center rounded-xl border border-green-300 text-green-600 text-xs font-bold hover:bg-green-50 transition">
                        View KML
                    </a>
                @else
                    <button type="button"
                            disabled
                            class="h-9 inline-flex items-center justify-center rounded-xl border border-gray-200 text-gray-400 text-xs font-bold cursor-not-allowed">
                        View KML
                    </button>
                @endif

                @if($progress == 100)

                    <a href="#"
                       class="h-9 col-span-2 inline-flex items-center justify-center rounded-xl bg-green-700 text-white text-xs font-bold hover:bg-green-800 transition">
                        Berkas Siap Uji Terima
                    </a>

                @else

                    <button type="button"
                            onclick="openAssignModal('{{ $project->id_project }}', @js($project->project_name))"
                            class="h-9 inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-bold hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        {{ $waspang ? 'Reassign' : 'Assign' }}
                    </button>

                    <button type="button"
                            onclick="openBoqModal('{{ $project->id_project }}', @js($project->project_name))"
                            class="h-9 inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-bold hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        + Item Designator
                    </button>

                @endif

            </div>

        </div>

    </div>


    {{-- DETAIL MODAL --}}
    <div id="detail-modal-{{ $project->id_project }}"
         class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">

        <div class="bg-white dark:bg-gray-900 w-full max-w-5xl max-h-[90vh] rounded-2xl overflow-hidden flex flex-col">

            <div class="flex items-start justify-between gap-4 px-6 py-5 border-b border-gray-200 dark:border-gray-800">

                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Detail LOP
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
                            {{ $project->lop?->branch }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">STO</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                            {{ $project->lop?->sto }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Mitra</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                            {{ $project->mitra_name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Jenis Eksekusi</p>
                        <span class="inline-flex mt-1 px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                            {{ strtoupper($project->execution_type) }}
                        </span>
                    </div>

                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Progress</p>
                        <span class="inline-flex mt-1 px-3 py-1 rounded-full {{ $badgeClass }} text-xs font-bold">
                            {{ $progress }}% · {{ $stageLabel }}
                        </span>
                    </div>

                    <div>
                        <p class="text-xs uppercase text-gray-400 font-semibold">Nama Waspang</p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                            {{ $waspang->name ?? 'Belum diassign' }}
                        </p>
                    </div>

                </div>

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">

                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Item BOQ Awal
                    </h3>

                    <button type="button"
                            onclick="openBoqModal('{{ $project->id_project }}', @js($project->project_name))"
                            class="h-10 px-4 inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm font-bold">
                        + Item Designator
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
                                            {{ $boq->item_name }}
                                        </p>
                                    </td>

                                    <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                        {{ $boq->unit }}
                                    </td>

                                    <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                        {{ $boq->quantity_plan }}
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
                                        Belum ada item BOQ untuk project ini.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="flex flex-col sm:flex-row justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">

                <!-- <button type="button"
                        onclick="closeDetailModal('detail-modal-{{ $project->id_project }}')"
                        class="h-11 px-6 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                    Tutup
                </button> -->

                <button type="button"
                        onclick="openAssignModal('{{ $project->id_project }}', @js($project->project_name))"
                        class="h-11 px-6 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                    👷 {{ $waspang ? 'Reassign Waspang' : 'Assign Waspang' }}
                </button>

               <button type="button"
                        onclick="openEditProjectModal({
                            id: '{{ $project->id_project }}',
                            project_name: @js($project->project_name),
                            branch: @js($project->branch),
                            sto: @js($project->sto),
                            mitra_name: @js($project->mitra_name),
                            jenis_eksekusi: '{{ $project->jenis_eksekusi }}',
                            status: '{{ $project->status }}',
                            latitude: @js($project->latitude),
                            longitude: @js($project->longitude),
                            location_address: @js($project->location_address)
                            boq_items: @js($project->boqItems->map(function($boq) {
                                return [
                                    'id_boq' => $boq->id_boq,
                                    'designator' => $boq->designator,
                                    'item_name' => $boq->item_name,
                                    'unit' => $boq->unit,
                                    'quantity_plan' => $boq->quantity_plan,
                                ];
                            }))
                        })"
                        class="h-11 px-6 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                    Edit
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



</div>

{{-- ASSIGN MODAL --}}
<div id="assignModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-3 sm:p-4">

    <div class="bg-white dark:bg-gray-900 w-full max-w-lg max-h-[90vh] rounded-2xl overflow-hidden flex flex-col">

        <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-gray-200 dark:border-gray-800">

            <div class="min-w-0">
                <h2 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white">
                    Assign Waspang
                </h2>

                <p id="assignProjectName" class="text-sm text-gray-500 mt-1 truncate"></p>
            </div>

            <button type="button"
                    onclick="closeAssignModal()"
                    class="shrink-0 w-10 h-10 rounded-xl border border-gray-300 dark:border-gray-700 flex items-center justify-center text-2xl leading-none hover:bg-gray-100 dark:hover:bg-gray-800">
                ×
            </button>

        </div>

        <form method="POST"
              action="{{ route('projects.assign') }}"
              class="flex flex-col min-h-0">
            @csrf

            <input type="hidden" name="project_id" id="project_id">

            <div class="px-5 py-4 overflow-y-auto">

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Pilih waspang yang akan ditugaskan untuk proyek ini:
                </p>

                <div class="relative mb-4">
                    <input type="text"
                        id="searchWaspangAssign"
                        placeholder="Cari nama waspang..."
                        class="w-full h-11 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-600 outline-none">

                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        🔍
                    </div>
                </div>

                <div class="space-y-3">

                    @foreach($waspangs as $waspangUser)

                        @php
                            $activeCount = $waspangUser->assignments
                            ->unique('project_id')
                            ->count();
                            $isBusy = $activeCount >= 3;

                            $initials = strtoupper(
                                collect(explode(' ', $waspangUser->name))
                                    ->map(fn($word) => substr($word, 0, 1))
                                    ->take(2)
                                    ->implode('')
                            );
                        @endphp

                        <label class="block cursor-pointer assign-waspang-item"
                            data-name="{{ strtolower($waspangUser->name) }}"
                            data-project-count="{{ $activeCount }}">

                            <input type="radio"
                                   name="waspang_id"
                                   value="{{ $waspangUser->id_user }}"
                                   class="peer sr-only"
                                   required>

                            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 p-4 transition peer-checked:border-blue-500 peer-checked:bg-blue-50">

                                <div class="flex items-center gap-3">

                                    <div class="w-11 h-11 rounded-full bg-blue-700 text-white flex items-center justify-center text-sm font-bold shrink-0">
                                        {{ $initials }}
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white truncate">
                                            {{ $waspangUser->name }}
                                        </h3>

                                        <p class="text-xs sm:text-sm text-gray-500">
                                            {{ $activeCount }} proyek aktif
                                        </p>
                                    </div>

                                    @if($isBusy)
                                        <span class="shrink-0 px-3 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                            Overload
                                        </span>
                                    @else
                                        <span class="shrink-0 px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
                                            Idle
                                        </span>
                                    @endif

                                </div>

                            </div>

                        </label>

                    @endforeach

                    <div id="emptyWaspangSearch"
                        class="hidden rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500">
                        Waspang tidak ditemukan.
                    </div>

                </div>

                <div class="mt-4 rounded-2xl bg-gray-100 dark:bg-gray-800 p-4 text-sm text-gray-600 dark:text-gray-400">
                    Waspang yang dipilih akan dapat melihat LOP ini di dashboard mereka.
                </div>

            </div>

            <div class="grid grid-cols-2 gap-3 px-5 py-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">

                <button type="button"
                        onclick="closeAssignModal()"
                        class="h-11 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-100 dark:hover:bg-gray-800">
                    Batal
                </button>

                <button type="submit"
                        class="h-11 rounded-xl bg-gray-900 text-white text-sm font-semibold hover:bg-black">
                    Assignment
                </button>

            </div>

        </form>

    </div>

</div>
 @if($projects->hasPages())
        <div class="mt-6">
            {{ $projects->links() }}
        </div>
    @endif