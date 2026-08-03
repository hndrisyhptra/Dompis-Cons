@extends('layouts.admin')

@section('content')

@php
    $materialBoqItems = $project->boqItems->filter(function ($boq) {
        return str_starts_with($boq->designator, 'M-') || optional($boq->designatorData)->type === 'material' || optional($boq->designatorDataByCode)->type === 'material';
    });

    $boqTotal = $materialBoqItems->count();
    $boqApprovedCount = 0;

    foreach ($materialBoqItems as $boq) {
        $photos = $project->evidences
            ->where('stage', 'instalasi')
            ->where('evidence_type', 'progress_boq')
            ->where('boq_item_id', $boq->id_boq);

        if ($photos->count() > 0 && $photos->where('status', 'approved')->count() == $photos->count()) {
            $boqApprovedCount++;
        }
    }

    $instalasiCompleted = $boqTotal > 0 && $boqApprovedCount >= $boqTotal;
@endphp

<div class="max-w-4xl mx-auto space-y-4">

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            Approval Eviden
        </h1>
        <p class="text-sm text-gray-500">
            Pilih project untuk mulai review step by step
        </p>
    </div>

    {{-- HEADER & STEPPER COMPONENT --}}
    @include('admin.evidences.partials.stepper')

    {{-- Step Title Card --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="h-1 bg-blue-500"></div>

        <div class="p-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Step 2 — Instalasi
                </h2>
                <p class="text-sm text-gray-500">
                    Review eviden progress instalasi berdasarkan designator material
                </p>
            </div>

            <span class="px-3 py-1 rounded-full text-xs font-bold
                {{ $instalasiCompleted
                    ? 'bg-green-100 text-green-700'
                    : 'bg-yellow-100 text-yellow-700' }}">
                {{ $boqApprovedCount }}/{{ $boqTotal }} Approved
            </span>
        </div>
    </div>
    {{-- BOQ ITEMS REVIEW LIST --}}
    <div class="space-y-4">
        @forelse($materialBoqItems as $boq)
            @php
                $items = $project->evidences
                    ->where('stage', 'instalasi')
                    ->where('evidence_type', 'progress_boq')
                    ->where('boq_item_id', $boq->id_boq)
                    ->sortByDesc('created_at');

                // Variabel untuk dikirim ke komponen review-item
                $designatorTitle = $boq->designator ?? 'DESIGNATOR'; // Judul utama yang nampak di luar
                $itemName = $boq->item_name; // Muncul di dalam kotak saat di-klik
                $planVal = number_format($boq->quantity_plan, 0, ',', '.') . ' ' . $boq->unit;
                $actualVal = number_format($boq->quantity_actual ?? 0, 0, ',', '.') . ' ' . $boq->unit;
                if ($boq->actual_reason) {
                    $actualVal .= " (Alasan 0: {$boq->actual_reason})";
                }
            @endphp

            @include('admin.evidences.partials.review-item', [
                'number' => $loop->iteration,
                'title' => $designatorTitle,         // Tampil sebagai judul accordion (Designator)
                'description' => 'ada',              // Trigger untuk memunculkan kotak detail item BOQ
                'subtitle_item_name' => $itemName,   // Nama Item BOQ lengkap
                'plan' => $planVal,                  // Target Plan yang jelas
                'actual' => $actualVal,              // Aktual Lapangan yang jelas
                'items' => $items,
                'type' => 'progress_boq',
            ])
        @empty
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-8 text-center text-gray-500 text-sm">
                Tidak ada daftar item BOQ material terpetakan untuk project ini.
            </div>
        @endforelse
    </div>

    {{-- Footer Navigation --}}
    <div class="flex items-center justify-between pt-2">

        <a href="{{ route('admin.evidences.review.project', $project->id_project) }}"
           class="h-10 px-5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm font-bold inline-flex items-center justify-center hover:bg-gray-50 transition">
            ← Step 1 Persiapan
        </a>

        <a href="{{ route('admin.evidences.review.pengukuran', $project->id_project) }}"
           class="h-10 px-5 rounded-xl bg-gray-900 text-white text-sm font-bold inline-flex items-center justify-center hover:bg-gray-800 transition">
            Step Berikutnya (Ukur) →
        </a>

    </div>

</div>

@endsection