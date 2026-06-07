@extends('layouts.admin')

@section('content')

@php
    $evidences = $project->evidences ?? collect();

    $materialBoqItems = ($project->boqItems ?? collect())->filter(function ($boq) {
        return optional($boq->designatorData)->type === 'material'
            || optional($boq->designatorDataByCode)->type === 'material';
    });

    $finalTotal = $materialBoqItems->count();
    $finalUploaded = 0;
    $finalApproved = 0;

    foreach ($materialBoqItems as $boq) {
        $items = $evidences
            ->where('stage', 'finishing')
            ->where('evidence_type', 'final_boq')
            ->where('boq_item_id', $boq->id_boq);

        if ($items->count() > 0) {
            $finalUploaded++;
        }

        if ($items->count() > 0 && $items->where('status', 'approved')->count() == $items->count()) {
            $finalApproved++;
        }
    }

    $finishingApproved = $finalTotal > 0 && $finalApproved == $finalTotal;

    $status = $finishingApproved ? 'approved' : 'pending';

    $statusClass = match ($status) {
        'approved' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        default => 'bg-yellow-100 text-yellow-700',
    };

    $stepSummary = [
        [
            'label' => 'Persiapan',
            'stage' => 'persiapan',
            'route' => route('admin.evidences.review.project', $project->id_project),
        ],
        [
            'label' => 'Instalasi',
            'stage' => 'instalasi',
            'route' => route('admin.evidences.review.instalasi', $project->id_project),
        ],
        [
            'label' => 'Pengukuran',
            'stage' => 'pengukuran',
            'route' => route('admin.evidences.review.pengukuran', $project->id_project),
        ],
    ];
@endphp

<div class="max-w-4xl mx-auto space-y-4">

    {{-- HEADER --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <div class="flex items-center justify-between gap-3">

            <div class="min-w-0">
                <h1 class="text-base font-bold text-gray-900 dark:text-white truncate">
                    {{ $project->project_name }}
                </h1>

                <p class="text-xs text-gray-500 mt-1">
                    {{ $project->lop?->branch }} · {{ $project->lop?->sto }} ·
                    Waspang:
                    <span class="font-semibold">
                        {{ optional($project->assignment)->waspang->name ?? '-' }}
                    </span>
                </p>
            </div>

            <a href="{{ route('admin.evidences.approval') }}"
               class="h-9 px-3 rounded-xl border border-gray-300 dark:border-gray-700 inline-flex items-center text-xs font-bold">
                ← Kembali
            </a>

        </div>

        {{-- STEPPER --}}
        <div class="mt-5 flex items-center justify-between">

            <a href="{{ route('admin.evidences.review.project', $project->id_project) }}"
               class="flex flex-col items-center w-16 opacity-80">
                <div class="w-8 h-8 rounded-full bg-green-100 text-green-700 border border-green-500 flex items-center justify-center text-xs font-bold">
                    ✓
                </div>
                <p class="mt-1 text-[11px] font-semibold">Persiapan</p>
            </a>

            <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>

            <a href="{{ route('admin.evidences.review.instalasi', $project->id_project) }}"
               class="flex flex-col items-center w-16 opacity-80">
                <div class="w-8 h-8 rounded-full bg-green-100 text-green-700 border border-green-500 flex items-center justify-center text-xs font-bold">
                    ✓
                </div>
                <p class="mt-1 text-[11px] font-semibold">Instalasi</p>
            </a>

            <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>

            <a href="{{ route('admin.evidences.review.pengukuran', $project->id_project) }}"
               class="flex flex-col items-center w-16 opacity-80">
                <div class="w-8 h-8 rounded-full bg-green-100 text-green-700 border border-green-500 flex items-center justify-center text-xs font-bold">
                    ✓
                </div>
                <p class="mt-1 text-[11px] font-semibold">Pengukuran</p>
            </a>

            <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>

            <div class="flex flex-col items-center w-16">
                <div class="w-8 h-8 rounded-full border border-red-500 bg-red-50 text-red-600 flex items-center justify-center text-xs font-bold">
                    {{ $finishingApproved ? '✓' : '4' }}
                </div>
                <p class="mt-1 text-[11px] font-bold text-red-600">Finish</p>
            </div>

        </div>

    </div>

    {{-- STEP TITLE --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

        <div class="h-1 bg-green-600"></div>

        <div class="p-4 flex items-center justify-between gap-3">

            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Step 4 — Finishing / Siap Uji Terima
                </h2>

                <p class="text-xs text-gray-500 mt-1">
                    Review eviden akhir sebelum project dinyatakan siap UT.
                </p>
            </div>

            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $statusClass }}">
                {{ $finalApproved }}/{{ $finalTotal }} Approved
            </span>

        </div>

    </div>

    {{-- REVIEW SUMMARY --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

        @foreach($stepSummary as $step)

            @php
                $stepItems = $project->evidences->where('stage', $step['stage']);

                $approved = $stepItems->where('status', 'approved')->count();
                $pending = $stepItems->where('status', 'pending')->count();
                $rejected = $stepItems->where('status', 'rejected')->count();
                $total = $stepItems->count();

                $isReviewed =
                    $total > 0 &&
                    $pending == 0 &&
                    $rejected == 0;
            @endphp

            <a href="{{ $step['route'] }}"
               class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 hover:shadow-md transition-all">

                <div class="flex items-start justify-between gap-3">

                    <div>
                        <p class="text-xs text-gray-500">
                            {{ $step['label'] }}
                        </p>

                        <p class="mt-1 text-sm font-bold {{ $isReviewed ? 'text-green-700' : 'text-yellow-700' }}">
                            {{ $isReviewed ? '✓ Reviewed' : 'Needs Review' }}
                        </p>
                    </div>

                    <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold
                        {{ $isReviewed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $isReviewed ? '✓' : '!' }}
                    </div>

                </div>

                <div class="flex flex-wrap gap-1.5 mt-4 text-[10px] font-bold">

                    <span class="px-2 py-1 rounded-lg bg-green-100 text-green-700">
                        Approved {{ $approved }}
                    </span>

                    <span class="px-2 py-1 rounded-lg bg-yellow-100 text-yellow-700">
                        Pending {{ $pending }}
                    </span>

                    <span class="px-2 py-1 rounded-lg bg-red-100 text-red-700">
                        Reject {{ $rejected }}
                    </span>

                </div>

                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">

                    <span class="text-[11px] text-gray-500">
                        Total Eviden
                    </span>

                    <span class="text-xs font-bold text-gray-700 dark:text-gray-200">
                        {{ $total }}
                    </span>

                </div>

            </a>

        @endforeach

    </div>

    {{-- FINISHING FINAL BOQ EVIDEN --}}
    <div class="space-y-3">

        @forelse($materialBoqItems as $boq)

            @php
                $instalasiItems = $evidences
                    ->where('stage', 'instalasi')
                    ->where('evidence_type', 'progress_boq')
                    ->where('boq_item_id', $boq->id_boq)
                    ->sortByDesc('created_at');

                $finalItems = $evidences
                    ->where('stage', 'finishing')
                    ->where('evidence_type', 'final_boq')
                    ->where('boq_item_id', $boq->id_boq)
                    ->sortByDesc('created_at');

                $firstItem = $finalItems->first();
                $status = $firstItem->status ?? 'pending';

                $statusClass = match ($status) {
                    'approved' => 'bg-green-100 text-green-700',
                    'rejected' => 'bg-red-100 text-red-700',
                    default => $finalItems->count() > 0
                        ? 'bg-yellow-100 text-yellow-700'
                        : 'bg-gray-100 text-gray-600',
                };

                $iconClass = match ($status) {
                    'approved' => 'bg-green-100 text-green-700',
                    'rejected' => 'bg-red-100 text-red-700',
                    default => $finalItems->count() > 0
                        ? 'bg-yellow-100 text-yellow-700'
                        : 'bg-gray-100 text-gray-600',
                };

                $iconText = match ($status) {
                    'approved' => '✓',
                    'rejected' => '×',
                    default => $loop->iteration,
                };
            @endphp

            <div x-data="{ open: false }"
                class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

                <button type="button"
                        @click="open = !open"
                        class="w-full p-4 flex items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-gray-800">

                    <div class="flex items-center gap-3 min-w-0">

                        <div class="w-8 h-8 rounded-xl {{ $iconClass }} flex items-center justify-center text-xs font-bold shrink-0">
                            {{ $iconText }}
                        </div>

                        <div class="text-left min-w-0">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                {{ $boq->item_name }}
                            </h3>

                            <p class="text-[11px] text-gray-500 truncate">
                                {{ $boq->designator ?? '-' }}
                                · Plan {{ $boq->quantity_plan }} {{ $boq->unit }}
                                · Final {{ $finalItems->count() }} foto
                            </p>
                        </div>

                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold {{ $statusClass }}">
                            {{ $finalItems->count() > 0 ? ucfirst($status) : 'Belum' }}
                        </span>

                        <span class="text-gray-400 text-xs" x-text="open ? '▲' : '▼'"></span>
                    </div>

                </button>

                <div x-show="open"
                    x-transition
                    class="border-t border-gray-100 dark:border-gray-800 p-4 space-y-4">

                    <div>
                        <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                            Eviden Instalasi Existing
                        </p>

                        @if($instalasiItems->count() > 0)
                            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                                @foreach($instalasiItems as $evidence)
                                    <a href="{{ asset('storage/' . $evidence->file_path) }}"
                                    target="_blank"
                                    class="aspect-square rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800">
                                        <img src="{{ asset('storage/' . $evidence->file_path) }}"
                                            class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-500">
                                Belum ada eviden instalasi.
                            </p>
                        @endif
                    </div>

                    <div>
                        <p class="text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                            Eviden Final
                        </p>

                        @if($finalItems->count() > 0)
                            <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                                @foreach($finalItems as $evidence)
                                    <a href="{{ asset('storage/' . $evidence->file_path) }}"
                                    target="_blank"
                                    class="aspect-square rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800">
                                        <img src="{{ asset('storage/' . $evidence->file_path) }}"
                                            class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-500">
                                Belum ada eviden final untuk item ini.
                            </p>
                        @endif
                    </div>

                    @if($firstItem?->description)
                        <div class="rounded-xl bg-blue-50 border border-blue-100 p-3">
                            <p class="text-[11px] font-bold text-blue-700 mb-1">
                                Catatan Waspang
                            </p>
                            <p class="text-xs text-blue-900">
                                {{ $firstItem->description }}
                            </p>
                        </div>
                    @endif

                    @if($status == 'rejected' && $firstItem?->review_note)
                        <div class="rounded-xl bg-red-50 border border-red-100 p-3">
                            <p class="text-[11px] font-bold text-red-700 mb-1">
                                Catatan Reject Admin
                            </p>
                            <p class="text-xs text-red-900">
                                {{ $firstItem->review_note }}
                            </p>
                        </div>
                    @endif

                    @if($firstItem)

                        <div class="border-t border-gray-100 dark:border-gray-800 pt-4">

                            @if($status == 'approved' || $status == 'rejected')

                                <form method="POST"
                                    action="{{ route('admin.evidences.reset', $firstItem->id_evidence) }}">
                                    @csrf

                                    <button class="h-9 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                                        Atur Ulang
                                    </button>
                                </form>

                            @else

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                                    <form method="POST"
                                        action="{{ route('admin.evidences.reject', $firstItem->id_evidence) }}"
                                        class="md:col-span-2">
                                        @csrf

                                        <textarea name="review_note"
                                                rows="2"
                                                required
                                                placeholder="Catatan reject..."
                                                class="w-full h-20 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-sm resize-none"></textarea>

                                        <button class="mt-2 h-9 w-full rounded-xl border border-red-300 text-red-600 text-xs font-bold hover:bg-red-50">
                                            × Reject
                                        </button>
                                    </form>

                                    <form method="POST"
                                        action="{{ route('admin.evidences.approve', $firstItem->id_evidence) }}">
                                        @csrf

                                        <button class="w-full h-[116px] rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-bold">
                                            ✓ Approve
                                        </button>
                                    </form>

                                </div>

                            @endif

                        </div>

                    @endif

                </div>

            </div>

        @empty

            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-6 text-center text-gray-500">
                Tidak ada item material pada BOQ.
            </div>

        @endforelse

    </div>

    {{-- FOOTER --}}
    <div class="flex items-center justify-between pt-2">

        <a href="{{ route('admin.evidences.review.pengukuran', $project->id_project) }}"
           class="h-10 px-5 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-bold inline-flex items-center justify-center">
            ← Step 3 Pengukuran
        </a>

        @if($finishingApproved)

            <button class="h-10 px-5 rounded-xl bg-green-600 text-white text-sm font-bold">
                ✓ Siap Uji Terima
            </button>

        @else

            <button disabled
                    class="h-10 px-5 rounded-xl border border-gray-300 text-sm font-bold text-gray-400">
                Menunggu Approval
            </button>

        @endif

    </div>

</div>

@endsection