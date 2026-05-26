@extends('layouts.admin')

@section('content')

<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="border-b border-gray-200 dark:border-gray-800 pb-5">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Approval Eviden
        </h1>

        <p class="text-sm text-gray-500">
            Pilih Project untuk mulai review step by step
        </p>
    </div>

    <div>
        <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
            Project dengan eviden menunggu review:
        </p>

        <div class="mb-5">

        <form method="GET">

            <div class="relative">

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search project, STO, mitra, waspang..."
                    class="w-full rounded-2xl border border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-800
                        px-4 py-3 pl-11
                        text-sm
                        focus:ring-2 focus:ring-blue-500
                        outline-none"
                >

                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                    🔍
                </div>

            </div>

        </form>

    </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 2xl:grid-cols-3 gap-4">

            @forelse($projects as $project)

            @php
            $items = $project->evidences;
            $projectId = $project->id_project;
            $waspang = optional($project->assignment)->waspang;

            $persiapanTotal = 2;

            $persiapanApproved = $items
                ->where('stage', 'persiapan')
                ->where('status', 'approved')
                ->pluck('evidence_type')
                ->unique()
                ->count();

            $instalasiTotal = $project->boqItems->count();

            $instalasiApproved = $items
                ->where('stage', 'instalasi')
                ->where('status', 'approved')
                ->pluck('boq_item_id')
                ->unique()
                ->count();

            $pengukuranTotal = 3;

            $pengukuranApproved = $items
                ->where('stage', 'pengukuran')
                ->where('status', 'approved')
                ->pluck('evidence_type')
                ->unique()
                ->count();

            $finishingTotal = 1;

            $finishingApproved = $items
                ->where('stage', 'finishing')
                ->where('status', 'approved')
                ->count() > 0 ? 1 : 0;

            $pendingCount = $items->where('status', 'pending')->count();
            $approvedCount = $items->where('status', 'approved')->count();
            $rejectedCount = $items->where('status', 'rejected')->count();

            $progress =
                $persiapanApproved +
                $instalasiApproved +
                $pengukuranApproved +
                $finishingApproved;

            $total =
                $persiapanTotal +
                $instalasiTotal +
                $pengukuranTotal +
                $finishingTotal;

            $progressPercent = $total > 0
                ? ($progress / $total) * 100
                : 0;
        @endphp

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden hover:shadow-md transition-all duration-200">

                {{-- TOP ACCENT --}}
                <div class="h-1 bg-blue-600"></div>

                <div class="p-4">

                    {{-- TOP --}}
                    <div class="flex items-start justify-between gap-3">

                        <div class="min-w-0 flex items-start gap-3">

                            {{-- PROJECT BADGE --}}
                            <div class="w-11 h-11 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0">
                                <span class="text-xs font-black">
                                    #{{ $projectId }}
                                </span>
                            </div>

                            <div class="min-w-0">

                                <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                    {{ $project->project_name ?? 'Project #' . $projectId }}
                                </h2>

                                <p class="text-[11px] text-gray-500 mt-0.5 truncate">
                                    {{ $project->branch ?? '-' }}
                                    ·
                                    {{ $project->sto ?? '-' }}
                                    ·
                                    {{ $project->mitra_name ?? '-' }}
                                </p>

                                <p class="text-[11px] text-gray-400 mt-1 truncate">
                                    Waspang:
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $waspang->name ?? '-' }}
                                    </span>
                                </p>

                            </div>

                        </div>

                        {{-- STATUS --}}
                        <div class="shrink-0">

                            @if($pendingCount > 0)

                                <div class="px-2.5 py-1 rounded-xl bg-yellow-100 text-yellow-700 text-[10px] font-bold whitespace-nowrap">
                                    {{ $pendingCount }} Pending
                                </div>

                            @elseif($rejectedCount > 0)

                                <div class="px-2.5 py-1 rounded-xl bg-red-100 text-red-700 text-[10px] font-bold whitespace-nowrap">
                                    {{ $rejectedCount }} Rejected
                                </div>

                            @else

                                <div class="px-2.5 py-1 rounded-xl bg-green-100 text-green-700 text-[10px] font-bold whitespace-nowrap">
                                    Complete
                                </div>

                            @endif

                        </div>

                    </div>

                    {{-- STEP BADGES --}}
                    <div class="flex flex-wrap gap-1.5 mt-4">

                        <div class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-[10px] font-bold text-gray-700 dark:text-gray-300">
                            Persiapan
                            <span class="ml-1 text-blue-700">
                                {{ $persiapanApproved }}/{{ $persiapanTotal }}
                            </span>
                        </div>

                        <div class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-[10px] font-bold text-gray-700 dark:text-gray-300">
                            Instalasi
                            <span class="ml-1 text-blue-700">
                                {{ $instalasiApproved }}/{{ $instalasiTotal }}
                            </span>
                        </div>

                        <div class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-[10px] font-bold text-gray-700 dark:text-gray-300">
                            Pengukuran
                            <span class="ml-1 text-blue-700">
                                {{ $pengukuranApproved }}/{{ $pengukuranTotal }}
                            </span>
                        </div>

                        <div class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-[10px] font-bold text-gray-700 dark:text-gray-300">
                            Finishing
                            <span class="ml-1 text-blue-700">
                                {{ $finishingApproved }}/{{ $finishingTotal }}
                            </span>
                        </div>

                    </div>

                    {{-- PROGRESS --}}
                    <div class="mt-4">

                        <div class="flex items-center justify-between mb-1">

                            <p class="text-[11px] text-gray-500">
                                Progress Review
                            </p>

                            <p class="text-[11px] font-bold text-gray-700 dark:text-gray-300">
                                {{ round($progressPercent) }}%
                            </p>

                        </div>

                        <div class="h-1.5 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">

                            <div class="h-full bg-blue-600 rounded-full transition-all duration-300"
                                style="width: {{ $progressPercent }}%">
                            </div>

                        </div>

                    </div>

                    {{-- FOOTER --}}
                    <div class="flex items-center justify-between mt-4">

                        <div class="flex items-center gap-3 text-[10px]">

                            <div class="px-2 py-1 rounded-lg bg-yellow-100 text-yellow-700 font-bold">
                                P {{ $pendingCount }}
                            </div>

                            <div class="px-2 py-1 rounded-lg bg-green-100 text-green-700 font-bold">
                                A {{ $approvedCount }}
                            </div>

                            <div class="px-2 py-1 rounded-lg bg-red-100 text-red-700 font-bold">
                                R {{ $rejectedCount }}
                            </div>

                        </div>

                        <a href="{{ route('admin.evidences.review.project', $projectId) }}"
                        class="h-9 px-4 rounded-xl bg-gray-900 text-white inline-flex items-center justify-center gap-2 text-xs font-bold hover:bg-black transition-all">

                            Review

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-3.5 h-3.5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"/>

                            </svg>

                        </a>

                    </div>

                </div>
{{ $projects->links() }}
            </div>

        @empty

                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 text-center text-gray-500">
                    Belum ada eviden menunggu review.
                </div>

            @endforelse

        </div>
    </div>

</div>

<script>

    let timeout = null;

    const searchInput = document.querySelector('input[name="search"]');

    searchInput.addEventListener('keyup', function () {

        clearTimeout(timeout);

        timeout = setTimeout(() => {

            this.form.submit();

        }, 500);

    });

</script>


@endsection