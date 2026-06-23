@extends('layouts.admin')

@section('content')

@php
    $progressSummary = $project->progressSummary();
    $progress = $progressSummary['progress'] ?? 0;

    $evidences = $project->evidences ?? collect();

    $totalEvidence = $evidences->count();
    $approvedEvidence = $evidences->where('status', 'approved')->count();
    $pendingEvidence = $evidences->where('status', 'pending')->count();
    $rejectedEvidence = $evidences->where('status', 'rejected')->count();

    $assignedLog = $logs
        ->whereIn('activity_type', ['assign_waspang', 'reassign_waspang'])
        ->sortBy('created_at')
        ->first();

    $startDate = $assignedLog?->created_at ?? $project->created_at;
    // Memastikan hasil bulat ke bawah
    $durationDays = $startDate ? floor($startDate->diffInDays(now())) : 0;

    $stageLabels = [
        'persiapan' => 'Persiapan',
        'instalasi' => 'Instalasi',
        'pengukuran' => 'Pengukuran',
        'finishing' => 'Finishing',
    ];

    $stageRoutes = [
        'persiapan' => 'Step 1',
        'instalasi' => 'Step 2',
        'pengukuran' => 'Step 3',
        'finishing' => 'Step 4',
    ];

    $stageProgress = [
        'persiapan' => $progress >= 25,
        'instalasi' => $progress >= 50,
        'pengukuran' => $progress >= 75,
        'finishing' => $progress >= 100,
    ];

    /*
    |--------------------------------------------------------------------------
    | Group log hanya yang ada aktivitas
    |--------------------------------------------------------------------------
    */
    $timelineGroups = collect();

    foreach ($logs->sortBy('created_at') as $log) {
        if (in_array($log->activity_type, ['assign_waspang', 'reassign_waspang', 'remove_assignment'])) {
            $key = 'assignment';
            $title = 'Assignment Waspang';
        } elseif (in_array($log->activity_type, ['update_kendala', 'resume_project'])) {
            $key = 'kendala';
            $title = 'Kendala / Resume';
        } elseif ($log->activity_type === 'project_completed') {
            $key = 'complete';
            $title = 'Project Complete';
        } elseif ($log->stage && isset($stageLabels[$log->stage])) {
            $key = $log->stage;
            $title = $stageRoutes[$log->stage] . ' - ' . $stageLabels[$log->stage];
        } else {
            $key = 'lainnya';
            $title = 'Aktivitas Lainnya';
        }

        if (!$timelineGroups->has($key)) {
            $timelineGroups->put($key, [
                'title' => $title,
                'items' => collect(),
            ]);
        }

        $timelineGroups[$key]['items']->push($log);
    }

    $boqItemsRaw = $project->boqItems ?? collect();

        $boqItems = $boqItemsRaw->filter(function ($item) {

            $type = strtolower(trim(
                $item->designatorData?->type
                ?? ''
            ));

            return $type === 'material';
        })->values();

    $totalBoqItem = $boqItems->count();

    $boqPlan = (float) $boqItems->sum('quantity_plan');
    $boqActual = (float) $boqItems->sum('quantity_actual');

    $boqPercent = $totalBoqItem > 0
        ? round(($boqItems->where('quantity_actual', '>', 0)->count() / $totalBoqItem) * 100)
        : 0;

    $stageEvidenceStats = [];

    foreach ($stageLabels as $stageKey => $stageName) {

        if ($stageKey === 'instalasi') {
            $stageTotal = $totalBoqItem;

            $stageApproved = $boqItems->filter(function ($item) use ($evidences) {
                return $evidences
                    ->where('stage', 'instalasi')
                    ->where('boq_item_id', $item->id_boq)
                    ->where('status', 'approved')
                    ->count() > 0;
            })->count();

            $percent = $stageTotal > 0
                ? round(($stageApproved / $stageTotal) * 100)
                : 0;
        } else {
            $stageTotal = $evidences->where('stage', $stageKey)->count();

            $stageApproved = $evidences
                ->where('stage', $stageKey)
                ->where('status', 'approved')
                ->count();

            $percent = $stageTotal > 0
                ? round(($stageApproved / $stageTotal) * 100)
                : 0;
        }

        $stageEvidenceStats[$stageKey] = [
            'name' => $stageName,
            'total' => $stageTotal,
            'approved' => $stageApproved,
            'percent' => $percent,
        ];
    }
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    {{-- HEADER --}}
    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">
                    Tracking Project
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    History aktivitas project dari assign, upload eviden, approval, kendala, sampai complete.
                </p>
            </div>

            <a href="{{ url()->previous() }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-700">
                Kembali
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">

            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">PID</p>
                <p class="font-black text-gray-900 dark:text-white mt-1">
                    {{ $project->pid ?? '-' }}
                </p>
            </div>

            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">PID SAP</p>
                <p class="font-black text-gray-900 dark:text-white mt-1">
                    {{ $project->pid_sap ?? '-' }}
                </p>
            </div>

            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">Nama Project</p>
                <p class="font-black text-gray-900 dark:text-white mt-1 truncate">
                    {{ $project->project_name ?? '-' }}
                </p>
            </div>

            <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 p-4">
                <p class="text-xs text-emerald-700 dark:text-emerald-300">Progress</p>
                <p class="font-black text-emerald-700 dark:text-emerald-300 mt-1">
                    {{ $progress }}%
                </p>

                <div class="mt-2 h-1.5 bg-emerald-100 dark:bg-emerald-900 rounded-full overflow-hidden">
                    <div class="h-full bg-emerald-600 rounded-full" style="width: {{ $progress }}%"></div>
                </div>
            </div>

        </div>

    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

        {{-- TIMELINE --}}
        <div class="xl:col-span-8 space-y-4">

            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6 shadow-sm">
                <h2 class="text-lg font-black text-gray-900 dark:text-white">
                    Timeline Aktivitas
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    Record Aktivitas Waspang dan Admin.
                </p>
            </div>

            @forelse($timelineGroups as $key => $group)

                @php
                    $items = $group['items'];
                    $isLong = $items->count() > 3;
                @endphp

                <details class="group bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm"
                         open>

                    <summary class="cursor-pointer list-none px-5 py-4 flex items-center justify-between gap-4 hover:bg-gray-50 dark:hover:bg-gray-800/70 transition">

                        <div>
                            <h3 class="text-sm font-black text-gray-900 dark:text-white">
                                {{ $group['title'] }}
                            </h3>

                            <p class="text-xs text-gray-500 mt-1">
                                {{ $items->count() }} aktivitas
                                @if($isLong)
                                    · Klik Collapse agar ringkas
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-black">
                                {{ $items->count() }}
                            </span>

                            <span class="w-9 h-9 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 flex items-center justify-center font-black group-open:rotate-180 transition">
                                ▾
                            </span>
                        </div>

                    </summary>

                    <div class="px-5 pb-5">

                        <div class="relative border-l-2 border-gray-200 dark:border-gray-800 ml-2 space-y-5">

                            @foreach($items as $log)

                                @php
                                    $color = match($log->activity_type) {
                                        'assign_waspang' => 'bg-blue-500',
                                        'reassign_waspang' => 'bg-indigo-500',
                                        'remove_assignment' => 'bg-gray-500',
                                        'upload_evidence' => 'bg-amber-500',
                                        'approve_evidence' => 'bg-emerald-500',
                                        'reject_evidence' => 'bg-red-500',
                                        'update_kendala' => 'bg-orange-500',
                                        'resume_project' => 'bg-green-500',
                                        'project_completed' => 'bg-emerald-700',
                                        default => 'bg-gray-500',
                                    };

                                    $meta = is_array($log->meta) ? $log->meta : [];
                                    $photoPaths = [];

                                    if ($log->activity_type === 'update_kendala') {
                                        if (!empty($meta['photo_paths']) && is_array($meta['photo_paths'])) {
                                            $photoPaths = $meta['photo_paths'];
                                        } elseif (!empty($meta['photo_path'])) {
                                            $decoded = json_decode($meta['photo_path'], true);
                                            $photoPaths = is_array($decoded) ? $decoded : [$meta['photo_path']];
                                        }
                                    }
                                @endphp

                                <div class="relative pl-6">

                                    <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full {{ $color }} border-4 border-white dark:border-gray-900"></span>

                                    <div class="rounded-2xl bg-gray-50 dark:bg-gray-800/70 border border-gray-100 dark:border-gray-800 p-4">

                                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-2">
                                            <div>
                                                <h4 class="text-sm font-black text-gray-900 dark:text-white">
                                                    {{ $log->title }}
                                                </h4>

                                                <p class="text-xs text-gray-600 dark:text-gray-300 mt-1 leading-relaxed">
                                                    {{ $log->description }}
                                                </p>
                                            </div>

                                            <p class="text-[11px] text-gray-500 whitespace-nowrap">
                                                {{ $log->created_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }}
                                            </p>
                                        </div>

                                        <div class="flex flex-wrap gap-2 mt-3 text-[11px]">

                                            <span class="px-2.5 py-1 rounded-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 font-bold">
                                                Oleh: {{ $log->user?->name ?? '-' }}
                                            </span>

                                            @if($log->targetUser)
                                                <span class="px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-bold">
                                                    Target: {{ $log->targetUser->name }}
                                                </span>
                                            @endif

                                            @if($log->stage)
                                                <span class="px-2.5 py-1 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 font-bold">
                                                    Step: {{ ucfirst($log->stage) }}
                                                </span>
                                            @endif

                                            @if($log->status_after)
                                                <span class="px-2.5 py-1 rounded-full
                                                    {{ $log->status_after === 'approved' ? 'bg-emerald-50 text-emerald-700' : '' }}
                                                    {{ $log->status_after === 'rejected' ? 'bg-red-50 text-red-700' : '' }}
                                                    {{ $log->status_after === 'pending' ? 'bg-amber-50 text-amber-700' : '' }}
                                                    {{ $log->status_after === 'kendala' ? 'bg-orange-50 text-orange-700' : '' }}
                                                    {{ $log->status_after === 'open' ? 'bg-blue-50 text-blue-700' : '' }}
                                                    {{ $log->status_after === 'completed' ? 'bg-emerald-50 text-emerald-700' : '' }}
                                                    {{ !in_array($log->status_after, ['approved','rejected','pending','kendala','open','completed']) ? 'bg-gray-100 text-gray-700' : '' }}
                                                    font-bold">
                                                    Status: {{ ucfirst($log->status_after) }}
                                                </span>
                                            @endif

                                        </div>

                                        {{-- Foto hanya tampil untuk kendala --}}
                                        @if($log->activity_type === 'update_kendala' && count($photoPaths) > 0)

                                            <div class="mt-4">
                                                <p class="text-xs font-black text-gray-700 dark:text-gray-200 mb-2">
                                                    Foto Kendala
                                                </p>

                                                <div class="grid grid-cols-3 gap-2">
                                                    @foreach($photoPaths as $photo)
                                                        <a href="{{ asset('storage/' . $photo) }}" target="_blank"
                                                           class="block aspect-square rounded-2xl overflow-hidden border border-gray-200 bg-gray-100 hover:opacity-90 transition">
                                                            <img src="{{ asset('storage/' . $photo) }}"
                                                                 class="w-full h-full object-cover">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </div>

                                        @endif

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                </details>

            @empty

                <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-10 text-center">
                    <p class="text-sm text-gray-500">
                        Belum ada aktivitas tracking.
                    </p>
                </div>

            @endforelse

        </div>

        {{-- SIDEBAR --}}
        <div class="xl:col-span-4 space-y-5">

            {{-- Ringkasan --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">

                <h2 class="text-sm font-black text-gray-900 dark:text-white">
                    Ringkasan Project
                </h2>

                <div class="grid grid-cols-2 gap-3 mt-4">

                    <div class="rounded-2xl bg-blue-50 dark:bg-blue-900/20 p-4">
                        <p class="text-xs text-blue-700 dark:text-blue-300">Durasi Berjalan</p>
                        <p class="text-2xl font-black text-blue-700 dark:text-blue-300 mt-1">
                            {{ $durationDays }}
                        </p>
                        <p class="text-[11px] text-blue-600 dark:text-blue-300">hari</p>
                    </div>

                    <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                        <p class="text-xs text-gray-500">Total Eviden</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                            {{ $totalEvidence }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 p-4">
                        <p class="text-xs text-emerald-700 dark:text-emerald-300">Approve</p>
                        <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">
                            {{ $approvedEvidence }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-amber-50 dark:bg-amber-900/20 p-4">
                        <p class="text-xs text-amber-700 dark:text-amber-300">Pending</p>
                        <p class="text-2xl font-black text-amber-700 dark:text-amber-300 mt-1">
                            {{ $pendingEvidence }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-red-50 dark:bg-red-900/20 p-4 col-span-2">
                        <p class="text-xs text-red-700 dark:text-red-300">Reject</p>
                        <p class="text-2xl font-black text-red-700 dark:text-red-300 mt-1">
                            {{ $rejectedEvidence }}
                        </p>
                    </div>

                </div>

            </div>

            {{-- Progress Tahap --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">

                <h2 class="text-sm font-black text-gray-900 dark:text-white">
                    Progress Tahap
                </h2>

                <div class="mt-5 space-y-4">

                    @foreach($stageLabels as $stageKey => $stageName)

                        @php
                            $done = $stageProgress[$stageKey] ?? false;
                            $hasLog = $logs->where('stage', $stageKey)->count() > 0;
                        @endphp

                        <div class="flex items-center gap-3">

                            <div class="w-9 h-9 rounded-2xl flex items-center justify-center font-black
                                {{ $done ? 'bg-emerald-600 text-white' : ($hasLog ? 'bg-amber-500 text-white' : 'bg-gray-100 text-gray-400') }}">
                                {{ $done ? '✓' : '•' }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-black text-gray-900 dark:text-white">
                                    {{ $stageName }}
                                </p>

                                <p class="text-xs text-gray-500">
                                    @if($done)
                                        Selesai / approved
                                    @elseif($hasLog)
                                        Sedang berjalan
                                    @else
                                        Belum ada aktivitas
                                    @endif
                                </p>
                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

            @php
                $firstLop = $project->lops?->first();
            @endphp

            {{-- Informasi Project --}}
            <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">
                    Informasi Project
                </h2>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Program</span>
                        <span class="font-black text-slate-900 dark:text-white text-right">
                            {{ $project->program ?? '-' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Status</span>
                        <span class="font-black text-slate-900 dark:text-white text-right">
                            {{ $project->status_project ?? '-' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Nama LOP</span>
                        <span class="font-black text-slate-900 dark:text-white text-right">
                            {{ $firstLop?->lop_name ?? $project->lop_name ?? '-' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Branch</span>
                        <span class="font-black text-slate-900 dark:text-white text-right">
                            {{ $firstLop?->branch ?? $project->branch ?? '-' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">STO</span>
                        <span class="font-black text-slate-900 dark:text-white text-right">
                            {{ $firstLop?->sto ?? $project->sto ?? '-' }}
                        </span>
                    </div>

                    <div class="flex justify-between gap-3">
                        <span class="text-slate-500">Mitra</span>
                        <span class="font-black text-slate-900 dark:text-white text-right">
                            {{ $firstLop?->mitra_name ?? $project->mitra_name ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Insight --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">

                <h2 class="text-sm font-black text-gray-900 dark:text-white">
                    Insight
                </h2>

                <div class="mt-4 space-y-3">

                    @if($rejectedEvidence > 0)
                        <div class="rounded-2xl bg-red-50 border border-red-100 p-3">
                            <p class="text-xs font-black text-red-700">
                                Ada eviden rejected
                            </p>
                            <p class="text-xs text-red-600 mt-1">
                                Perlu follow up Waspang untuk revisi eviden.
                            </p>
                        </div>
                    @endif

                    @if($pendingEvidence > 0)
                        <div class="rounded-2xl bg-amber-50 border border-amber-100 p-3">
                            <p class="text-xs font-black text-amber-700">
                                Ada eviden pending
                            </p>
                            <p class="text-xs text-amber-600 mt-1">
                                Perlu review Admin agar progress bisa naik.
                            </p>
                        </div>
                    @endif

                    @if($progress >= 100)
                        <div class="rounded-2xl bg-emerald-50 border border-emerald-100 p-3">
                            <p class="text-xs font-black text-emerald-700">
                                Project Complete
                            </p>
                            <p class="text-xs text-emerald-600 mt-1">
                                Semua eviden wajib sudah approved.
                            </p>
                        </div>
                    @endif

                    @if($pendingEvidence == 0 && $rejectedEvidence == 0 && $progress < 100)
                        <div class="rounded-2xl bg-blue-50 border border-blue-100 p-3">
                            <p class="text-xs font-black text-blue-700">
                                Menunggu update berikutnya
                            </p>
                            <p class="text-xs text-blue-600 mt-1">
                                Belum ada pending/reject, project menunggu evidence tahap berikutnya.
                            </p>
                        </div>
                    @endif

                </div>

            </div>

            {{-- Progress Eviden Per Tahap --}}
            <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
                <h2 class="text-sm font-black text-slate-900 dark:text-white">
                    Persentase Progress Tahap
                </h2>

                <p class="text-xs text-slate-500 mt-1">
                    Berdasarkan upload eviden dari total eviden project.
                </p>

                <div class="mt-5 space-y-4">
                    @foreach($stageEvidenceStats as $stageKey => $stage)
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="text-sm font-black text-slate-900 dark:text-white">
                                        {{ $stage['name'] }}
                                    </p>

                                    <p class="text-xs text-slate-500">

                                        @if($stageKey === 'instalasi')
                                            {{ $stage['approved'] }}
                                            /
                                            {{ $stage['total'] }}
                                            item designator approved
                                        @else
                                            {{ $stage['approved'] }}
                                            /
                                            {{ $stage['total'] }}
                                            eviden approved
                                        @endif

                                    </p>
                                </div>

                                <span class="text-sm font-black text-blue-600">
                                    {{ $stage['percent'] }}%
                                </span>
                            </div>

                            <div class="h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-cyan-500"
                                    style="width: {{ $stage['percent'] }}%">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        {{-- BOQ Summary --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">
                Summary BOQ
            </h2>

            <div class="grid grid-cols-2 gap-3 mt-4">

                <div class="rounded-3xl bg-slate-50 dark:bg-slate-800 p-4">
                    <p class="text-xs text-slate-500 font-bold">Total Item BOQ</p>
                    <p class="text-3xl font-black text-slate-900 dark:text-white mt-1">
                        {{ $totalBoqItem }}
                    </p>
                    <p class="text-[11px] text-slate-500">
                        item designator
                    </p>
                </div>

                <div class="rounded-3xl bg-blue-50 dark:bg-blue-900/20 p-4">
                    <p class="text-xs text-blue-700 dark:text-blue-300 font-bold">Item Actual</p>
                    <p class="text-3xl font-black text-blue-700 dark:text-blue-300 mt-1">
                        {{ $boqItems->where('quantity_actual', '>', 0)->count() }}
                    </p>
                    <p class="text-[11px] text-blue-600 dark:text-blue-300">
                        designator progress
                    </p>
                </div>

            </div>

            <div class="mt-5">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="font-bold text-slate-500">
                        BOQ Item Plan vs Actual
                    </span>

                    <span class="font-black text-slate-900 dark:text-white">
                        {{ $boqItems->where('quantity_actual', '>', 0)->count() }}
                        /
                        {{ $totalBoqItem }}
                    </span>
                </div>

                <div class="h-3 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-green-500"
                        style="width: {{ min($boqPercent, 100) }}%">
                    </div>
                </div>

                <p class="text-xs text-slate-500 mt-2">
                    Progress BOQ: <b>{{ $boqPercent }}%</b>
                </p>
            </div>
        </div>

    </div>

</div>

@endsection