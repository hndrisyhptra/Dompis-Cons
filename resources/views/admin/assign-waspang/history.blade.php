@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                History Assignment
            </h1>

            <p class="text-sm text-gray-500">
                History project untuk {{ $waspang->name }}
            </p>
        </div>

        <a href="{{ route('assign-waspang.index') }}"
           class="h-10 px-4 rounded-xl border border-gray-300 text-sm font-bold inline-flex items-center">
            Kembali
        </a>
    </div>

    <form method="GET"
        action="{{ route('admin.assign-waspang.history', $waspang->id_user) }}"
        class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <div class="flex flex-col sm:flex-row gap-3">

            <input type="text"
                name="search"
                value="{{ $search ?? '' }}"
                placeholder="Cari Nama Project, PID, STO, Branch..."
                class="flex-1 h-11 rounded-xl border-gray-300 text-sm">

            <div class="flex gap-2">
                <button class="h-11 px-5 rounded-xl bg-gray-900 text-white text-sm font-bold">
                    Cari
                </button>

                @if (!empty($search))
                    <a href="{{ route('admin.assign-waspang.history', $waspang->id_user) }}"
                    class="h-11 px-5 rounded-xl border border-gray-300 text-sm font-bold flex items-center">
                        Reset
                    </a>
                @endif
            </div>

        </div>
    </form>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left">Project Name</th>
                        <th class="px-4 py-3 text-left">PID</th>
                        <th class="px-4 py-3 text-left">STO/Branch</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <!-- <th class="px-4 py-3 text-left">Tanggal Assign</th> -->
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($assignments as $assignment)

                        @php
                            $project = $assignment->project;
                            $evidences = $project?->evidences ?? collect();
                            $boqItems = $project?->boqItems ?? collect();

                            $persiapanDone =
                                $evidences->where('stage', 'persiapan')
                                    ->where('evidence_type', 'barang_tiba')
                                    ->where('status', 'approved')
                                    ->count() > 0
                                &&
                                $evidences->where('stage', 'persiapan')
                                    ->where('evidence_type', 'perizinan')
                                    ->where('status', 'approved')
                                    ->count() > 0;

                            $materialBoqItems = $boqItems->filter(function ($boq) {
                                return str_starts_with($boq->designator, 'M-');
                            });

                            $boqTotal = $materialBoqItems->count();

                            $boqApproved = $materialBoqItems->filter(function ($boq) use ($evidences) {
                                return $evidences
                                    ->where('stage', 'instalasi')
                                    ->where('evidence_type', 'progress_boq')
                                    ->where('boq_item_id', $boq->id_boq)
                                    ->where('status', 'approved')
                                    ->count() > 0;
                            })->count();

                            $instalasiDone = $boqTotal > 0 && $boqApproved >= $boqTotal;

                            $finishingDone =
                                $evidences->where('stage', 'finishing')
                                    ->where('status', 'approved')
                                    ->count() > 0;

                            $isComplete = $persiapanDone && $instalasiDone && $finishingDone;
                        @endphp

                        <tr>
                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                                {{ $project->project_name ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $project->pid ?? '-' }}
                            </td>


                            <td class="px-4 py-3">
                                {{ $project->lop?->sto ?? '-' }} / {{ $project->lop?->branch ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                @if($isComplete)
                                    <span class="px-2.5 py-1 rounded-lg bg-green-100 text-green-700 text-xs font-bold">
                                        Finish
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-yellow-100 text-yellow-700 text-xs font-bold">
                                        On Progress
                                    </span>
                                @endif
                            </td>

                            <!-- <td class="px-4 py-3 text-gray-500">
                                {{ $assignment->created_at?->format('d M Y H:i') ?? '-' }}
                            </td> -->
                        </tr>

                    @empty

                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                Belum ada history assignment.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

    </div>

</div>

@endsection