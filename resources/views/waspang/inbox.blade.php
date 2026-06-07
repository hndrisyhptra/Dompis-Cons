<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox LOP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f7f6f2] text-gray-900">

<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

    <div class="bg-blue-700 text-white px-5 pt-6 pb-5 rounded-b-[1.7rem]">

        <div class="flex items-center gap-3">

            <a href="{{ route('waspang.dashboard') }}" class="text-3xl leading-none">
                ‹
            </a>

            <div>
                <h1 class="text-xl font-bold">
                    Inbox LOP
                </h1>
                <p class="text-xs opacity-90">
                    {{ $projects->count() }} Order di Assign
                </p>
            </div>
        </div>
    </div>

    <!-- SEARCH BAR -->
    <div class="px-4 mt-4">

        <form method="GET" action="{{ route('waspang.inbox') }}">

            <div class="relative">

                <input type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari LOP, STO, branch, mitra..."
                    class="w-full h-11 rounded-2xl border border-gray-300 bg-white pl-10 pr-4 text-sm font-medium focus:ring-2 focus:ring-blue-100 focus:border-blue-700 outline-none">

                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    🔍
                </div>

            </div>

        </form>

    </div>

    <div class="px-4 mt-4 space-y-4">

        @forelse($projects as $project)

            @php
                $evidences = $project->evidences ?? collect();
                $boqItems = $project->boqItems ?? collect();

                /*
                |--------------------------------------------------------------------------
                | STEP CHECK
                |--------------------------------------------------------------------------
                */

                $barangTibaUploaded =
                    $evidences
                        ->where('stage', 'persiapan')
                        ->where('evidence_type', 'barang_tiba')
                        ->count() > 0;

                $perizinanUploaded =
                    $evidences
                        ->where('stage', 'persiapan')
                        ->where('evidence_type', 'perizinan')
                        ->count() > 0;

                $persiapanDone =
                    $barangTibaUploaded &&
                    $perizinanUploaded;

                /*
                |--------------------------------------------------------------------------
                | INSTALASI
                |--------------------------------------------------------------------------
                */

                $boqTotal = $boqItems->count();

                $boqDone = $boqItems->filter(function ($boq) use ($evidences) {

                    return $evidences
                        ->where('stage', 'instalasi')
                        ->where('evidence_type', 'progress_boq')
                        ->where('boq_item_id', $boq->id_boq)
                        ->count() > 0;

                })->count();

                $instalasiDone =
                    $boqTotal > 0 &&
                    $boqDone == $boqTotal;

                /*
                |--------------------------------------------------------------------------
                | PENGUKURAN
                |--------------------------------------------------------------------------
                */

                $otdrUploaded =
                    $evidences
                        ->where('stage', 'pengukuran')
                        ->where('evidence_type', 'otdr')
                        ->count() > 0;

                $opmUploaded =
                    $evidences
                        ->where('stage', 'pengukuran')
                        ->where('evidence_type', 'opm')
                        ->count() > 0;

                $kedalamanUploaded =
                    $evidences
                        ->where('stage', 'pengukuran')
                        ->where('evidence_type', 'kedalaman')
                        ->count() > 0;

                $pengukuranDone =
                    $otdrUploaded &&
                    $opmUploaded &&
                    $kedalamanUploaded;

                /*
                |--------------------------------------------------------------------------
                | FINISHING
                |--------------------------------------------------------------------------
                */

                $finishingDone =
                    $evidences
                        ->where('stage', 'finishing')
                        ->where('evidence_type', 'final_site')
                        ->count() > 0;

                /*
                |--------------------------------------------------------------------------
                | PROGRESS
                |--------------------------------------------------------------------------
                */

                $doneStep = 0;

                if ($persiapanDone) $doneStep++;
                if ($instalasiDone) $doneStep++;
                if ($pengukuranDone) $doneStep++;
                if ($finishingDone) $doneStep++;

                $progress = round(($doneStep / 4) * 100);

                /*
                |--------------------------------------------------------------------------
                | READY UT
                |--------------------------------------------------------------------------
                */

                $approvedCount = $evidences->where('status', 'approved')->count();

                $pendingCount = $evidences->where('status', 'pending')->count();

                $rejectedCount = $evidences->where('status', 'rejected')->count();

                $totalEvidence = $evidences->count();

                $isFinish =
                    $totalEvidence > 0 &&
                    $approvedCount == $totalEvidence &&
                    $pendingCount == 0 &&
                    $rejectedCount == 0;

                /*
                |--------------------------------------------------------------------------
                | STYLE
                |--------------------------------------------------------------------------
                */

                $borderColor = $isFinish
                    ? 'border-l-green-700'
                    : 'border-l-blue-700';

                $progressColor = $isFinish
                    ? 'bg-green-700'
                    : 'bg-blue-700';

                /*
                |--------------------------------------------------------------------------
                | UPDATE TERAKHIR
                |--------------------------------------------------------------------------
                */

                $lastEvidence = $evidences
                    ->sortByDesc('updated_at')
                    ->first();

                $lastUpdate =
                    optional($lastEvidence)->updated_at
                    ?? $project->updated_at;
            @endphp

            <div class="bg-white border border-gray-200 border-l-[3px] {{ $borderColor }} rounded-2xl p-3.5 shadow-sm">

                <div class="flex justify-between gap-3">

                    <div class="min-w-0">
                        <h2 class="text-[17px] font-bold leading-tight">
                            {{ $project->project_name }}
                        </h2>

                        <p class="text-xs text-gray-500 mt-1">
                            {{ $project->lop?->branch }} · {{ $project->lop?->sto }} · {{ strtoupper($project->execution_type) }}
                        </p>
                    </div>

                    @php
                        $allStepDone =
                            $persiapanDone &&
                            $instalasiDone &&
                            $pengukuranDone &&
                            $finishingDone;
                    @endphp

                    <span class="shrink-0 h-fit px-2 py-[3px] rounded-lg text-xs font-bold
                        {{ $allStepDone ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">

                        {{ $allStepDone ? 'Selesai' : 'On Progress' }}

                    </span>

                </div>

                <div class="flex flex-wrap gap-1 mt-3 text-[10px] font-medium">

                    <span class="px-2 py-[3px] rounded-lg
                        {{ $persiapanDone ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $persiapanDone ? '✓ Persiapan' : '○ Persiapan' }}
                    </span>

                    <span class="px-2 py-[3px] rounded-lg
                        {{ $instalasiDone ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $instalasiDone ? '✓ Instalasi' : '○ Instalasi' }}
                    </span>

                    <span class="px-2 py-[3px] rounded-lg
                        {{ $pengukuranDone ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $pengukuranDone ? '✓ Pengukuran' : '○ Pengukuran' }}
                    </span>

                    <span class="px-2 py-[3px] rounded-lg
                        {{ $finishingDone ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $finishingDone ? '✓ Finishing' : '○ Finishing' }}
                    </span>

                </div>

                <div class="mt-4 h-1.5 bg-gray-200 rounded-lg overflow-hidden">
                    <div class="h-full {{ $progressColor }} rounded-lg"
                         style="width: {{ $progress }}%">
                    </div>
                </div>

                <div class="flex items-center justify-between mt-3">

                <div>
                    <p class="text-[11px] text-gray-500">
                        Total Progress
                    </p>

                    <p class="text-sm font-black text-blue-700">
                        {{ $progress }}%
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-[11px] text-gray-500">
                        Update Terakhir
                    </p>

                    <p class="text-[11px] font-bold text-gray-900">
                        {{ $lastUpdate ? $lastUpdate->diffForHumans() : '-' }}
                    </p>

                    <a href="{{ route('waspang.projects.show', $project->id_project) }}"
                    class="inline-block text-[11px] font-bold text-blue-700 mt-1">
                        Detail →
                    </a>
                </div>

            </div>

                <div class="mt-3">

                @if($allStepDone)

                    <button
                        class="h-10 w-full inline-flex items-center justify-center rounded-xl bg-green-700 text-white text-sm font-bold">

                        Siap Uji Terima

                    </button>

                @else

                    @php

                        if (!$persiapanDone) {

                            $nextRoute = route('waspang.projects.show', $project->id_project);

                        } elseif (!$instalasiDone) {

                            $nextRoute = route('waspang.projects.instalasi', $project->id_project);

                        } elseif (!$pengukuranDone) {

                            $nextRoute = route('waspang.projects.pengukuran', $project->id_project);

                        } else {

                            $nextRoute = route('waspang.projects.finishing', $project->id_project);

                        }

                    @endphp

                    <a href="{{ $nextRoute }}"
                    class="h-10 w-full inline-flex items-center justify-center rounded-xl bg-blue-700 text-white text-sm font-bold">

                        Upload Eviden

                    </a>

                @endif

            </div>

            </div>

        @empty

            <div class="bg-white border border-gray-200 rounded-2xl p-6 text-center text-gray-500">
                Belum ada LOP yang diassign.
            </div>

        @endforelse

    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'inbox'])

</div>

<script>
    let searchTimeout = null;
    const searchInput = document.querySelector('input[name="search"]');

    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            clearTimeout(searchTimeout);

            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    }
</script>

</body>
</html>