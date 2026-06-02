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

            <div class="relative inline-block">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7 text-white-600">
        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
    </svg>

    <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full ring-2 ring-white"></span>
</div>
        </div>
    </div>


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
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 13.5l3 3 3-3m-3 3v-6m10.125-3H17.25m3 0v1.125c0 .621-.504 1.125-1.125 1.125H3.75A1.125 1.125 0 012.625 11.25V5.25m17.625 0A1.125 1.125 0 0019.125 4.125H4.875A1.125 1.125 0 003.75 5.25m17.625 0v11.25c0 .621-.504 1.125-1.125 1.125H3.75a1.125 1.125 0 01-1.125-1.125V5.25t" />
                    </svg>
                <h3 class="font-semibold text-[15px]">Inbox LOP</h3>
                <p class="text-sm text-gray-500">
                    {{ $activeProjectsCount }} Project Aktif
                </p>
            </a>

            <a href="{{ route('waspang.ready-ut') }}"
            class="block bg-blue-700 rounded-2xl p-3 text-white shadow-sm hover:bg-blue-800 transition-colors">
                
                <!-- Heroicons: Check Badge (Outline) -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                </svg>

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

            $allProjects = $projects ?? collect();

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

        $lopSelesai = $allProjects->filter(function ($project) {

            $evidences = $project->evidences ?? collect();
            $boqItems = $project->boqItems ?? collect();

            $persiapan =
                $evidences->where('stage', 'persiapan')
                    ->where('evidence_type', 'barang_tiba')
                    ->where('status', 'approved')
                    ->count() > 0
                &&
                $evidences->where('stage', 'persiapan')
                    ->where('evidence_type', 'perizinan')
                    ->where('status', 'approved')
                    ->count() > 0;

            $boqTotal = $boqItems->count();

            $boqApproved = $boqItems->filter(function ($boq) use ($evidences) {

                return $evidences
                    ->where('stage', 'instalasi')
                    ->where('evidence_type', 'progress_boq')
                    ->where('boq_item_id', $boq->id_boq)
                    ->where('status', 'approved')
                    ->count() > 0;

            })->count();

            $instalasi =
                $boqTotal > 0 &&
                $boqApproved == $boqTotal;

            $pengukuran =
                $evidences->where('stage', 'pengukuran')
                    ->where('evidence_type', 'otdr')
                    ->where('status', 'approved')
                    ->count() > 0
                &&
                $evidences->where('stage', 'pengukuran')
                    ->where('evidence_type', 'opm')
                    ->where('status', 'approved')
                    ->count() > 0
                &&
                $evidences->where('stage', 'pengukuran')
                    ->where('evidence_type', 'kedalaman')
                    ->where('status', 'approved')
                    ->count() > 0;

            $finishing =
                $evidences->where('stage', 'finishing')
                    ->where('status', 'approved')
                    ->count() > 0;

            return
                $persiapan &&
                $instalasi &&
                $pengukuran &&
                $finishing;

        })->count();

        $lopProgressPercent =
            $totalLop > 0
                ? round(($lopSelesai / $totalLop) * 100)
                : 0;

        $lastUpdate = optional(
            $allProjects
                ->flatMap(fn($project) => $project->evidences ?? collect())
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