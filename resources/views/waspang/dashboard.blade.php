<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Waspang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#f7f6f2] text-gray-900">

<div class="min-h-screen max-w-md mx-auto bg-[#f7f6f2] pb-24">

    {{-- Header --}}
    <div class="bg-blue-700 text-white px-5 pt-6 pb-6 rounded-b-[1.7rem]">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-sm opacity-90">Semangat Pagi..!</p>
                <h1 class="text-xl font-bold leading-tight">
                    {{ auth()->user()->name }}
                </h1>
                <p class="text-xs opacity-90 mt-1.5">
                    {{ now()->translatedFormat('l, d F Y') }}
                </p>
            </div>

            <div class="relative">
                <span class="text-2xl">🔔</span>
                <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full"></span>
            </div>
        </div>
    </div>

    {{-- Statistic --}}
        @php
        $projects = $latestProjects ?? collect();

        $totalAssigned = $projects->count();

        $activeProjectsCount = 0;
        $readyUtCount = 0;

        $preparation = 0;
        $installation = 0;
        $finish = 0;

        foreach ($projects as $project) {

            $evidences = $project->evidences ?? collect();
            $boqItems = $project->boqItems ?? collect();

            $barangTibaApproved = $evidences
                ->where('stage', 'persiapan')
                ->where('evidence_type', 'barang_tiba')
                ->where('status', 'approved')
                ->count() > 0;

            $perizinanApproved = $evidences
                ->where('stage', 'persiapan')
                ->where('evidence_type', 'perizinan')
                ->where('status', 'approved')
                ->count() > 0;

            $persiapanApproved = $barangTibaApproved && $perizinanApproved;

            $boqTotal = $boqItems->count();

            $boqApproved = $boqItems->filter(function ($boq) use ($evidences) {
                return $evidences
                    ->where('stage', 'instalasi')
                    ->where('evidence_type', 'progress_boq')
                    ->where('boq_item_id', $boq->id_boq)
                    ->where('status', 'approved')
                    ->count() > 0;
            })->count();

            $instalasiApproved = $boqTotal > 0 && $boqApproved == $boqTotal;

            $otdrApproved = $evidences
                ->where('stage', 'pengukuran')
                ->where('evidence_type', 'otdr')
                ->where('status', 'approved')
                ->count() > 0;

            $opmApproved = $evidences
                ->where('stage', 'pengukuran')
                ->where('evidence_type', 'opm')
                ->where('status', 'approved')
                ->count() > 0;

            $kedalamanApproved = $evidences
                ->where('stage', 'pengukuran')
                ->where('evidence_type', 'kedalaman')
                ->where('status', 'approved')
                ->count() > 0;

            $pengukuranApproved = $otdrApproved && $opmApproved && $kedalamanApproved;

            $finishingApproved = $evidences
                ->where('stage', 'finishing')
                ->where('evidence_type', 'final_site')
                ->where('status', 'approved')
                ->count() > 0;

            $isReadyUt =
                $persiapanApproved &&
                $instalasiApproved &&
                $pengukuranApproved &&
                $finishingApproved;

            if ($isReadyUt) {
                $readyUtCount++;
                $finish++;
            } else {
                $activeProjectsCount++;
            }

            if (!$persiapanApproved) {
                $preparation++;
            } elseif (!$instalasiApproved) {
                $installation++;
            }
        }
    @endphp

         {{-- STATISTIK CARD--}}
        <div class="grid grid-cols-2 gap-3 px-5 -mt-4">

        <div class="bg-white border border-gray-200 rounded-2xl p-3 shadow-sm"> <p class="text-xs text-gray-500 font-medium">LOP Assigned</p>
            <h2 class="text-xl font-bold text-red-500 mt-1.5"> {{ $totalAssigned }}
            </h2>
            <span class="inline-block mt-2 px-2.5 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold">
                Total Order
            </span>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-3 shadow-sm"> <p class="text-xs text-gray-500 font-medium">Preparation</p>
            <h2 class="text-xl font-bold text-red-500 mt-1.5"> {{ $preparation }}
            </h2>
            <span class="inline-block mt-2 px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-bold">
                Persiapan
            </span>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-3 shadow-sm"> <p class="text-xs text-gray-500 font-medium">Installation</p>
            <h2 class="text-xl font-bold text-yellow-600 mt-1.5"> {{ $installation }}
            </h2>
            <span class="inline-block mt-2 px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-bold">
                Instalasi
            </span>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-3 shadow-sm"> <p class="text-xs text-gray-500 font-medium">LOP Finish</p>
            <h2 class="text-xl font-bold text-green-700 mt-1.5"> {{ $finish }}
            </h2>
            <span class="inline-block mt-2 px-2.5 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold">
                Ready UT
            </span>
        </div>

    </div>

    {{-- AKSI CEPAT --}}
    <div class="px-5 mt-7">
        <h2 class="text-sm font-bold text-gray-500 uppercase mb-3">
            Aksi Cepat
        </h2>

        <div class="grid grid-cols-2 gap-3">

            <a href="{{ route('waspang.inbox') }}"
               class="bg-white rounded-2xl border border-gray-200 p-3 shadow-sm">
                <div class="text-blue-700 text-xl mb-2">▣</div>
                <h3 class="font-semibold text-[15px]">Inbox LOP</h3>
                <p class="text-sm text-gray-500">
                    {{ $activeProjectsCount }} Project Aktif
                </p>
            </a>

            <a href="{{ route('waspang.ready-ut') }}"
            class="bg-blue-700 rounded-2xl p-3 text-white shadow-sm">
                <div class="text-xl mb-2">✓</div>
                <h3 class="font-semibold text-[15px]">List LOP</h3>
                <p class="text-sm opacity-90">
                    {{ $readyUtCount ?? 0 }} LOP Finish
                </p>
            </a>

        </div>
    </div>

    {{-- Progress Summary --}}
    <div class="px-5 mt-7">

        <h2 class="text-sm font-bold text-gray-500 uppercase mb-3">
            Progress Pekerjaan
        </h2>

        @php

            $allProjects = $latestProjects ?? collect();

            $persiapanCount = 0;
            $instalasiCount = 0;
            $pengukuranCount = 0;
            $finishingCount = 0;

            $globalBoqTotal = 0;
            $globalBoqDone = 0;

            foreach ($allProjects as $project) {

                $evidences = $project->evidences ?? collect();
                $boqItems = $project->boqItems ?? collect();

                /*
                |--------------------------------------------------------------------------
                | BOQ PROGRESS
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

                $globalBoqTotal += $boqTotal;
                $globalBoqDone += $boqDone;

                /*
                |--------------------------------------------------------------------------
                | STEP CHECK
                |--------------------------------------------------------------------------
                */

                $persiapanUploaded =
                    $evidences->where('stage', 'persiapan')->where('evidence_type', 'barang_tiba')->count() > 0
                    &&
                    $evidences->where('stage', 'persiapan')->where('evidence_type', 'perizinan')->count() > 0;

                $instalasiUploaded =
                    $boqTotal > 0 &&
                    $boqDone == $boqTotal;

                $pengukuranUploaded =
                    $evidences->where('stage', 'pengukuran')->where('evidence_type', 'otdr')->count() > 0
                    &&
                    $evidences->where('stage', 'pengukuran')->where('evidence_type', 'opm')->count() > 0
                    &&
                    $evidences->where('stage', 'pengukuran')->where('evidence_type', 'kedalaman')->count() > 0;

                $finishingUploaded =
                    $evidences->where('stage', 'finishing')->where('evidence_type', 'final_site')->count() > 0;

                /*
                |--------------------------------------------------------------------------
                | ACTIVE STEP
                |--------------------------------------------------------------------------
                */

                if (!$persiapanUploaded) {

                    $persiapanCount++;

                } elseif (!$instalasiUploaded) {

                    $instalasiCount++;

                } elseif (!$pengukuranUploaded) {

                    $pengukuranCount++;

                } elseif (!$finishingUploaded) {

                    $finishingCount++;

                }

                /*
                |--------------------------------------------------------------------------
                | APPROVAL
                |--------------------------------------------------------------------------
                */

                $pending = $evidences->where('status', 'pending')->count();

                $rejected = $evidences->where('status', 'rejected')->count();

                $approved = $evidences->where('status', 'approved')->count();

                $totalEvidence = $evidences->count();

            }

            $globalProgress =
                $globalBoqTotal > 0
                    ? round(($globalBoqDone / $globalBoqTotal) * 100)
                    : 0;

        @endphp

        {{-- GLOBAL PROGRESS --}}
        @php
            $totalLop = $allProjects->count();

            $lopSelesai = 0;

            foreach ($allProjects as $project) {

        $evidences = $project->evidences ?? collect();
        $boqItems = $project->boqItems ?? collect();

        /*
        |--------------------------------------------------------------------------
        | STEP PERSIAPAN
        |--------------------------------------------------------------------------
        */

        $barangTibaApproved =
            $evidences
                ->where('stage', 'persiapan')
                ->where('evidence_type', 'barang_tiba')
                ->where('status', 'approved')
                ->count() > 0;

        $perizinanApproved =
            $evidences
                ->where('stage', 'persiapan')
                ->where('evidence_type', 'perizinan')
                ->where('status', 'approved')
                ->count() > 0;

        $persiapanDone =
            $barangTibaApproved &&
            $perizinanApproved;

        /*
        |--------------------------------------------------------------------------
        | STEP INSTALASI
        |--------------------------------------------------------------------------
        */

        $boqTotal = $boqItems->count();

        $boqApproved = $boqItems->filter(function ($boq) use ($evidences) {

            return $evidences
                ->where('stage', 'instalasi')
                ->where('evidence_type', 'progress_boq')
                ->where('boq_item_id', $boq->id_boq)
                ->where('status', 'approved')
                ->count() > 0;

        })->count();

        $instalasiDone =
            $boqTotal > 0 &&
            $boqApproved == $boqTotal;

        /*
        |--------------------------------------------------------------------------
        | STEP PENGUKURAN
        |--------------------------------------------------------------------------
        */

        $otdrApproved =
            $evidences
                ->where('stage', 'pengukuran')
                ->where('evidence_type', 'otdr')
                ->where('status', 'approved')
                ->count() > 0;

        $opmApproved =
            $evidences
                ->where('stage', 'pengukuran')
                ->where('evidence_type', 'opm')
                ->where('status', 'approved')
                ->count() > 0;

        $kedalamanApproved =
            $evidences
                ->where('stage', 'pengukuran')
                ->where('evidence_type', 'kedalaman')
                ->where('status', 'approved')
                ->count() > 0;

        $pengukuranDone =
            $otdrApproved &&
            $opmApproved &&
            $kedalamanApproved;

        /*
        |--------------------------------------------------------------------------
        | STEP FINISHING
        |--------------------------------------------------------------------------
        */

        $finishingDone =
            $evidences
                ->where('stage', 'finishing')
                ->where('evidence_type', 'final_site')
                ->where('status', 'approved')
                ->count() > 0;

        /*
        |--------------------------------------------------------------------------
        | READY UT
        |--------------------------------------------------------------------------
        */

        $isReadyUt =
            $persiapanDone &&
            $instalasiDone &&
            $pengukuranDone &&
            $finishingDone;

        if ($isReadyUt) {
            $lopSelesai++;
        }
    }

        $lopProgressPercent = $totalLop > 0
        ? round(($lopSelesai / $totalLop) * 100)
        : 0;

        $lastUpdate = optional(
        $allProjects
            ->flatMap(fn ($project) => $project->evidences ?? collect())
            ->sortByDesc('updated_at')
            ->first()
            )->updated_at;

        @endphp

        <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-sm mb-4">

            <div class="flex items-start justify-between gap-3">

                <div>
                    <p class="text-xs text-gray-500 font-medium">
                        Total Progress
                    </p>

                    <h2 class="text-3xl font-black text-blue-700 mt-1">
                        {{ $lopProgressPercent }}%
                    </h2>

                    <p class="text-xs text-gray-500 mt-1">
                        {{ $lopSelesai }} dari {{ $totalLop }} LOP Finish
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-xs text-gray-500">
                        Update Terakhir
                    </p>

                    <p class="text-xs font-bold text-gray-900 mt-1">
                        {{ $lastUpdate ? $lastUpdate->diffForHumans() : '-' }}
                    </p>
                </div>

            </div>

            <div class="mt-4 h-2 bg-gray-200 rounded-full overflow-hidden">

                <div class="h-full bg-blue-700 rounded-full"
                    style="width: {{ $lopProgressPercent }}%">
                </div>

            </div>

        </div>


    </div>

    @include('waspang.partials.bottom-nav', ['active' => 'home'])

</div>

</body>
</html>