@extends('layouts.admin')

@section('content')

@php
    $requirements = [
        [
            'number' => 1,
            'title' => 'Eviden OTDR',
            'type' => 'otdr',
            'description' => 'Review foto hasil pengukuran kabel menggunakan OTDR.',
        ],
        [
            'number' => 2,
            'title' => 'File (.SOR)',
            'type' => 'otdr_sor',
            'description' => 'Review file mentah berformat .sor dari alat ukur.',
        ],
        [
            'number' => 3,
            'title' => 'Eviden OPM',
            'type' => 'opm',
            'description' => 'Review foto hasil pengukuran power optic menggunakan OPM.',
        ],
        [
            'number' => 4,
            'title' => 'Eviden Kedalaman Galian',
            'type' => 'kedalaman',
            'description' => 'Review foto kedalaman galian jika pekerjaan menggunakan jalur tanam.',
        ],
        [
            'number' => 5,
            'title' => 'Eviden Pengukuran Lainnya',
            'type' => 'lainnya',
            'description' => 'Review foto hasil pengukuran lainnya.',
        ],
    ];

    $approvedCount = 0;

    foreach ($requirements as $req) {
        $items = $project->evidences
            ->where('stage', 'pengukuran')
            ->where('evidence_type', $req['type']);

        if ($items->count() > 0 && $items->where('status', 'approved')->count() == $items->count()) {
            $approvedCount++;
        }
    }

    $pengukuranCompleted = $approvedCount >= count($requirements);
@endphp

<div class="max-w-4xl mx-auto space-y-4">

    {{-- HEADER --}}
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
        <div class="h-1 bg-red-500"></div>

        <div class="p-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Step 3 — Pengukuran
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Review eviden OTDR, File SOR, OPM, Kedalaman Galian, dan Pengukuran Lainnya
                </p>
            </div>

            <span class="px-3 py-1 rounded-full text-xs font-bold
                {{ $pengukuranCompleted
                    ? 'bg-green-100 text-green-700'
                    : 'bg-yellow-100 text-yellow-700' }}">
                {{ $approvedCount }}/{{ count($requirements) }} Approved
            </span>
        </div>
    </div>

    {{-- REVIEW LIST MENGGUNAKAN KOMPONEN REVIEW-ITEM --}}
    <div class="space-y-4">
        @foreach($requirements as $req)
            @php
                $items = $project->evidences
                    ->where('stage', 'pengukuran')
                    ->where('evidence_type', $req['type'])
                    ->sortByDesc('created_at');
            @endphp

            @include('admin.evidences.partials.review-item', [
                'number' => $req['number'],
                'title' => $req['title'],
                'description' => $req['description'],
                'items' => $items,
                'type' => $req['type'],
            ])
        @endforeach
    </div>

    {{-- Footer Navigation --}}
    <div class="flex items-center justify-between pt-2">

        <a href="{{ route('admin.evidences.review.instalasi', $project->id_project) }}"
           class="h-10 px-5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm font-bold inline-flex items-center justify-center hover:bg-gray-50 transition">
            ← Step 2 Instalasi
        </a>

        <a href="{{ route('admin.evidences.review.finishing', $project->id_project) }}"
           class="h-10 px-5 rounded-xl bg-gray-900 text-white text-sm font-bold inline-flex items-center justify-center hover:bg-gray-800 transition">
            Step Berikutnya (Finish) →
        </a>

    </div>

</div>

@endsection