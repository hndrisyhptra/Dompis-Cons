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

                {{-- Filter Chips --}}
                <div class="space-y-3">

                    {{-- Program --}}
                    <div class="flex flex-col lg:flex-row lg:items-center gap-2">
                        <div class="w-18 shrink-0 text-xs font-black uppercase tracking-wide text-gray-400">
                            Program
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ request()->fullUrlWithQuery(['program' => null]) }}"
                            class="px-4 py-2 rounded-full text-xs font-bold border transition
                            {{ !request('program') ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                Semua Program
                            </a>

                            @foreach($programs as $program)
                                <a href="{{ request()->fullUrlWithQuery(['program' => $program]) }}"
                                class="px-4 py-2 rounded-full text-xs font-bold border transition
                                {{ request('program') == $program ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                    {{ $program }}
                                </a>
                            @endforeach
                        </div>
                    

                    {{-- Branch --}}
                    <div class="flex flex-col lg:flex-row lg:items-center gap-2">
                        <div class="w-18 shrink-0 text-xs font-black uppercase tracking-wide text-gray-400">
                            Branch
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ request()->fullUrlWithQuery(['branch' => null]) }}"
                            class="px-4 py-2 rounded-full text-xs font-bold border transition
                            {{ !request('branch') ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                Semua Branch
                            </a>

                            @foreach($branches as $branch)
                                <a href="{{ request()->fullUrlWithQuery(['branch' => $branch]) }}"
                                class="px-4 py-2 rounded-full text-xs font-bold border transition
                                {{ request('branch') == $branch ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                    {{ $branch }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                    {{-- Tahapan --}}
                    <!-- <div class="flex flex-col lg:flex-row lg:items-center gap-2">
                        <div class="w-24 shrink-0 text-xs font-black uppercase tracking-wide text-gray-400">
                            Tahapan
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ request()->fullUrlWithQuery(['stage' => null]) }}"
                            class="px-4 py-2 rounded-full text-xs font-bold border transition
                            {{ !request('stage') ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                Semua
                            </a>

                            <a href="{{ request()->fullUrlWithQuery(['stage' => 'preparation']) }}"
                            class="px-4 py-2 rounded-full text-xs font-bold border transition
                            {{ request('stage') == 'preparation' ? 'bg-red-600 text-white border-red-600 shadow-sm' : 'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                Preparation
                            </a>

                            <a href="{{ request()->fullUrlWithQuery(['stage' => 'instalasi']) }}"
                            class="px-4 py-2 rounded-full text-xs font-bold border transition
                            {{ request('stage') == 'instalasi' ? 'bg-yellow-600 text-white border-yellow-600 shadow-sm' : 'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                Instalasi
                            </a>

                            <a href="{{ request()->fullUrlWithQuery(['stage' => 'pengukuran']) }}"
                            class="px-4 py-2 rounded-full text-xs font-bold border transition
                            {{ request('stage') == 'pengukuran' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                Pengukuran
                            </a>

                            <a href="{{ request()->fullUrlWithQuery(['stage' => 'finishing']) }}"
                            class="px-4 py-2 rounded-full text-xs font-bold border transition
                            {{ request('stage') == 'finishing' ? 'bg-green-600 text-white border-green-600 shadow-sm' : 'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                                Finishing
                            </a>
                        </div>
                    </div> -->

                </div>

            </form>

        </div>

{{-- Project Cards --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

@forelse($projects as $project)

   @php
    $summary = $project->progressSummary();

    $persiapanDone = $summary['persiapanDone'];
    $instalasiDone = $summary['instalasiDone'];
    $pengukuranDone = $summary['pengukuranDone'];
    $finishingDone = $summary['finishingDone'];

    $boqTotal = $summary['materialTotal'];
    $boqApproved = $summary['instalasiApproved'];
    $finishingApproved = $summary['finishingApproved'];
    $finishingTotal = $summary['finishingTotal'] ?? 0;

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
        $accentColor = 'bg-blue-600';
        $borderColor = 'border-l-blue-600';
        $progressColor = 'bg-blue-600';
        $badgeClass = 'bg-purple-100 text-blue-700';
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
                        Instalasi {{ $boqApproved }}/{{ $boqTotal }} Item Designators
                    </p>
                    <!-- <p class="text-[10px] text-red-600">
                        Debug Finish: {{ $finishingApproved }}/{{ $finishingTotal }}
                    </p> -->
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

@empty

    <div class="col-span-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 text-center text-gray-500">
        Belum ada project.
    </div>

@endforelse

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

                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Jenis Eksekusi <span class="text-red-500">*</span>
                    </label>

                    <select name="jenis_eksekusi"
                            id="jenis_eksekusi"
                            required
                            class="w-full h-11 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">
                        <option value="">— Pilih jenis —</option>
                        <option value="plan">PLAN</option>
                        <option value="survey">SURVEY</option>
                        <option value="ogp">OGP</option>
                        <option value="finish">FINISH</option>
                    </select>
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
function openAssignModal(projectId, projectName)
    {
        const modal = document.getElementById('assignModal');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.getElementById('project_id').value = projectId;
        document.getElementById('assignProjectName').innerText = projectName;

        // RESET SEARCH
        const searchInput = document.getElementById('searchWaspangAssign');
        const items = document.querySelectorAll('.assign-waspang-item');
        const emptyState = document.getElementById('emptyWaspangSearch');

        if (searchInput) {
            searchInput.value = '';
        }

        items.forEach(item => {
            item.classList.remove('hidden');
        });

        if (emptyState) {
            emptyState.classList.add('hidden');
        }
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

function openDetailModal(id)
{
    const modal = document.getElementById(id);

    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeDetailModal(id)
{
    const modal = document.getElementById(id);

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function openImportModal()
{
    document.getElementById('importModal').classList.remove('hidden');
    document.getElementById('importModal').classList.add('flex');
}

function closeImportModal()
{
    document.getElementById('importModal').classList.add('hidden');
    document.getElementById('importModal').classList.remove('flex');
}

function openProjectModal()
{
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

function openEditProjectModal(project)
{
    document.getElementById('projectModal').classList.remove('hidden');
    document.getElementById('projectModal').classList.add('flex');

    document.getElementById('projectModalTitle').innerText = 'Edit Project & BOQ';
    document.getElementById('projectForm').action = `/projects/update/${project.id}`;
    document.getElementById('projectMethod').value = 'PUT';

    document.getElementById('project_name').value = project.project_name ?? '';
    document.getElementById('branch').value = project.branch ?? '';
    document.getElementById('sto').value = project.sto ?? '';
    document.getElementById('mitra_name').value = project.mitra_name ?? '';
    document.getElementById('jenis_eksekusi').value = project.jenis_eksekusi ?? 'plan';
    document.getElementById('latitude').value = project.latitude ?? '';
    document.getElementById('longitude').value = project.longitude ?? '';
    document.getElementById('location_address').value = project.location_address ?? '';

    if (document.getElementById('status')) {
        document.getElementById('status').value = project.status ?? 'active';
    }

    renderEditBoqItems(project.boq_items ?? []);
}

function renderEditBoqItems(items)
{
    const container = document.getElementById('designatorContainer');

    container.innerHTML = '';

    if (items.length === 0) {
        addDesignatorRow();
        return;
    }

    items.forEach((item) => {
        const row = `
            <div class="grid grid-cols-12 gap-2 designator-row">

                <input type="hidden" name="existing_boq_id[]" value="${item.id_boq ?? ''}">

                <input type="text"
                       name="existing_designator[]"
                       value="${item.designator ?? ''}"
                       readonly
                       class="col-span-12 sm:col-span-3 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

                <input type="text"
                       name="existing_item_name[]"
                       value="${item.item_name ?? ''}"
                       readonly
                       class="col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

                <input type="text"
                       name="existing_unit[]"
                       value="${item.unit ?? ''}"
                       readonly
                       class="col-span-5 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

                <input type="number"
                       step="0.01"
                       name="existing_qty[]"
                       value="${item.quantity_plan ?? 0}"
                       class="col-span-5 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

                <button type="button"
                        onclick="removeDesignatorRow(this)"
                        class="col-span-2 sm:col-span-1 h-10 rounded-xl text-gray-400 hover:text-red-500 text-xl">
                    ×
                </button>

            </div>
        `;

        container.insertAdjacentHTML('beforeend', row);
    });

    addDesignatorRow();
}

function closeProjectModal()
{
    document.getElementById('projectModal').classList.add('hidden');
    document.getElementById('projectModal').classList.remove('flex');
}

function fillDesignatorData(select)
{
    const row = select.closest('.designator-row');
    const selected = select.options[select.selectedIndex];

    row.querySelector('input[name="boq_item_name[]"]').value = selected.dataset.item || '';
    row.querySelector('input[name="boq_unit[]"]').value = selected.dataset.unit || '';
}

    function addDesignatorRow()
    {
        const container = document.getElementById('designatorContainer');

        const row = `
            <div class="grid grid-cols-12 gap-2 designator-row">

                <select name="designator_id[]"
                        onchange="fillDesignatorData(this)"
                        class="designator-select col-span-12 sm:col-span-4 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

                    <option value="">Pilih designator</option>

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
                    step="1"
                    name="boq_qty[]"
                    placeholder="0"
                    class="col-span-5 sm:col-span-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

                <button type="button"
                        onclick="removeDesignatorRow(this)"
                        class="col-span-2 sm:col-span-1 h-10 rounded-xl text-gray-400 hover:text-red-500 text-xl">
                    ×
                </button>

            </div>
        `;

        container.insertAdjacentHTML('beforeend', row);

        const newRow = container.lastElementChild;
        const newSelect = newRow.querySelector('.designator-select');

        initSingleDesignatorSearch(newSelect);
    }

    function removeDesignatorRow(button)
    {
        const rows = document.querySelectorAll('.designator-row');

        if (rows.length <= 1) {
            const row = button.closest('.designator-row');

            row.querySelector('select').value = '';
            row.querySelector('input[name="boq_item_name[]"]').value = '';
            row.querySelector('input[name="boq_unit[]"]').value = '';
            row.querySelector('input[name="boq_qty[]"]').value = '';

            return;
        }

        button.closest('.designator-row').remove();
    }

    // SEARCH ITEM DESIGNATOR SAAT PILIH ITEM DESIGNATOR
    function initDesignatorSearch()
    {
        document.querySelectorAll('.designator-select').forEach(function(select) {
            initSingleDesignatorSearch(select);
        });
    }

        document.addEventListener('DOMContentLoaded', function () {
        initDesignatorSearch();
    });

    function initSingleDesignatorSearch(select)
    {
        if (!select) {
            return;
        }

        if (select.tomselect) {
            return;
        }

        new TomSelect(select, {
            create: false,
            placeholder: 'Cari designator...',
            maxOptions: 1000,
            searchField: ['text'],
            sortField: {
                field: 'text',
                direction: 'asc'
            },
            onChange: function () {
                fillDesignatorData(select);
            }
        });
    }

    function openBoqModal(projectId, projectName)
    {
        const modal = document.getElementById('boqModal');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.getElementById('boq_project_id').value = projectId;
        document.getElementById('boqProjectName').innerText = projectName;

        initBoqDesignatorSearch();
    }

    function closeBoqModal()
    {
        const modal = document.getElementById('boqModal');

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

</script>

<script>
    window.projectBoqItems = {
        @foreach($projects as $project)
            "{{ $project->id_project }}": [
                @foreach($project->boqItems as $boq)
                    {
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
    function openBoqModal(projectId, projectName)
{
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

function closeBoqModal()
{
    const modal = document.getElementById('boqModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function renderExistingBoq(projectId)
{
    const list = document.getElementById('existingBoqList');
    const count = document.getElementById('existingBoqCount');

    const items = window.projectBoqItems[projectId] || [];

    count.innerText = `${items.length} item`;

    if (items.length === 0) {
        list.innerHTML = `
            <div class="p-4 text-sm text-gray-500 text-center">
                Belum ada item designator pada project ini.
            </div>
        `;

        return;
    }

    list.innerHTML = items.map((item) => {
        return `
            <div class="p-3 flex items-start justify-between gap-3">

                <div class="min-w-0">

                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                        ${item.item_name}
                    </p>

                    <p class="text-xs text-gray-500 mt-0.5">
                        ${item.designator} · ${item.unit}
                    </p>

                </div>

                <span class="shrink-0 px-2.5 py-1 rounded-lg bg-blue-100 text-blue-700 text-[11px] font-bold">
                    Plan ${item.quantity_plan}
                </span>

            </div>
        `;
    }).join('');
}

function resetBoqRows()
{
    const container = document.getElementById('boqContainer');
    const rows = container.querySelectorAll('.boq-row');

    rows.forEach((row, index) => {
        if (index > 0) {
            row.remove();
        }
    });

    const firstRow = container.querySelector('.boq-row');

    if (!firstRow) {
        return;
    }

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

function fillBoqDesignatorData(select)
{
    const row = select.closest('.boq-row');
    const selected = select.options[select.selectedIndex];

    row.querySelector('input[name="boq_item_name[]"]').value = selected.dataset.item || '';
    row.querySelector('input[name="boq_unit[]"]').value = selected.dataset.unit || '';
}

function addBoqRow()
{
    const container = document.getElementById('boqContainer');

    const row = `
        <div class="grid grid-cols-12 gap-2 boq-row">

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
                   class="col-span-5 sm:col-span-2 h-10 rounded-xl border-gray-300 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800">

            <input type="number"
                   step="0.01"
                   name="boq_qty[]"
                   placeholder="0"
                   class="col-span-5 sm:col-span-1 h-10 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm">

            <button type="button"
                    onclick="removeBoqRow(this)"
                    class="col-span-2 sm:col-span-1 h-10 rounded-xl text-gray-400 hover:text-red-500 text-xl">
                ×
            </button>

        </div>
    `;

    container.insertAdjacentHTML('beforeend', row);

    const newRow = container.lastElementChild;
    const newSelect = newRow.querySelector('.boq-designator-select');

    initSingleBoqDesignatorSearch(newSelect);
}

function removeBoqRow(button)
{
    const rows = document.querySelectorAll('.boq-row');

    if (rows.length <= 1) {
        const row = button.closest('.boq-row');

        const select = row.querySelector('select');

        if (select.tomselect) {
            select.tomselect.clear();
        } else {
            select.value = '';
        }

        row.querySelector('input[name="boq_item_name[]"]').value = '';
        row.querySelector('input[name="boq_unit[]"]').value = '';
        row.querySelector('input[name="boq_qty[]"]').value = '';

        return;
    }

    button.closest('.boq-row').remove();
}

    function initSingleBoqDesignatorSearch(select)
        {
            if (!select) return;

            if (select.tomselect) return;

            new TomSelect(select, {
                create: false,
                placeholder: 'Cari designator...',
                maxOptions: 1000,
                searchField: ['text'],
                sortField: {
                    field: 'text',
                    direction: 'asc'
                },
                onChange: function () {
                    fillBoqDesignatorData(select);
                }
            });
        }

    function initBoqDesignatorSearch()
    {
        document.querySelectorAll('.boq-designator-select').forEach(function(select) {
            initSingleBoqDesignatorSearch(select);
        });
    }

    function getProjectLocation()
    {
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
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    function openKmlModal(projectId, projectName)
    {
        const modal = document.getElementById('kmlModal');

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.getElementById('kmlForm').action = `/projects/${projectId}/upload-kml`;
        document.getElementById('kmlProjectName').innerText = projectName;
    }

    function closeKmlModal()
    {
        const modal = document.getElementById('kmlModal');

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

