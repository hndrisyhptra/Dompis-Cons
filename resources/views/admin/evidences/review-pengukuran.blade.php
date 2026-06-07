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
            'title' => 'Eviden OPM',
            'type' => 'opm',
            'description' => 'Review foto hasil pengukuran power optic menggunakan OPM.',
        ],
        [
            'number' => 3,
            'title' => 'Eviden Kedalaman Galian',
            'type' => 'kedalaman',
            'description' => 'Review foto kedalaman galian jika pekerjaan menggunakan jalur tanam.',
        ],
        [
            'number' => 4,
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

            {{-- STEPPER COMPACT --}}
            <div class="mt-5 flex items-center justify-between">

                <a href="{{ route('admin.evidences.review.project', $project->id_project) }}"
                class="flex flex-col items-center w-16 opacity-70">
                    <div class="w-8 h-8 rounded-full bg-green-100 text-green-700 border border-green-500 flex items-center justify-center text-xs font-bold">
                        ✓
                    </div>
                    <p class="mt-1 text-[11px] font-semibold">
                        Persiapan
                    </p>
                </a>

                <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>

                <a href="{{ route('admin.evidences.review.instalasi', $project->id_project) }}"
                    class="flex flex-col items-center w-16 opacity-70">
                    <div class="w-8 h-8 rounded-full bg-green-100 text-green-700 border border-green-500 flex items-center justify-center text-xs font-bold">
                        ✓
                    </div>
                    <p class="mt-1 text-[11px] font-bold">
                        Instalasi
                    </p>
                </a>

                <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>

                <a href="{{ route('admin.evidences.review.pengukuran', $project->id_project) }}"
                    class="flex flex-col items-center w-16">
                    <div class="w-8 h-8 rounded-full border border-red-500 bg-red-50 text-red-600 flex items-center justify-center text-xs font-bold">
                        3
                    </div>
                    <p class="mt-1 text-[11px] font-semibold text-red-600">
                        Pengukuran
                    </p>
                </a>

                <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>

                <a href="{{ route('admin.evidences.review.finishing', $project->id_project) }}"
                    class="flex flex-col items-center w-16 opacity-50">
                    <div class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-xs font-bold">
                        4
                    </div>
                    <p class="mt-1 text-[11px] font-semibold">
                        Finish
                    </p>
                </a>

            </div>

        </div>

    {{-- STEP TITLE --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

        <div class="h-1 bg-red-500"></div>

        <div class="p-4 flex items-center justify-between gap-3">

            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Step 3 — Pengukuran
                </h2>

                <p class="text-xs text-gray-500 mt-1">
                    Review eviden OTDR, OPM, dan kedalaman galian
                </p>
            </div>

            <span class="px-3 py-1 rounded-full text-xs font-bold
                {{ $pengukuranCompleted ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ $approvedCount }}/{{ count($requirements) }} Approved
            </span>

        </div>

    </div>

    {{-- REVIEW LIST --}}
    <div class="space-y-3">

        @foreach($requirements as $req)

            @php
                $items = $project->evidences
                    ->where('stage', 'pengukuran')
                    ->where('evidence_type', $req['type'])
                    ->sortByDesc('created_at');

                $firstItem = $items->first();
                $status = $firstItem->status ?? 'pending';
                $photoCount = $items->count();

                $statusClass = match ($status) {
                    'approved' => 'bg-green-100 text-green-700',
                    'rejected' => 'bg-red-100 text-red-700',
                    default => 'bg-yellow-100 text-yellow-700',
                };

                $iconClass = match ($status) {
                    'approved' => 'bg-green-100 text-green-700',
                    'rejected' => 'bg-red-100 text-red-700',
                    default => 'bg-yellow-100 text-yellow-700',
                };

                $iconText = match ($status) {
                    'approved' => '✓',
                    'rejected' => '×',
                    default => $req['number'],
                };

                $uploadedAt = optional($firstItem?->created_at)->translatedFormat('d M Y • H:i');
            @endphp

            <div x-data="{ open: false }"
                 class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

                {{-- BADGE CARD --}}
                <button type="button"
                        @click="open = !open"
                        class="w-full p-4 flex items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition">

                    <div class="flex items-center gap-3 min-w-0">

                        <div class="w-8 h-8 rounded-xl {{ $iconClass }} flex items-center justify-center text-xs font-bold shrink-0">
                            {{ $iconText }}
                        </div>

                        <div class="text-left min-w-0">

                            <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                {{ $req['title'] }}
                            </h3>

                            <div class="mt-1 flex flex-wrap items-center gap-1.5">

                                <span class="text-[11px] text-gray-500">
                                    {{ $photoCount }} foto
                                </span>

                                @if($uploadedAt)
                                    <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                    <span class="text-[11px] text-gray-500">
                                        {{ $uploadedAt }}
                                    </span>
                                @endif

                                @if($firstItem?->description)
                                    <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                    <span class="text-[11px] text-blue-600 font-medium">
                                        Ada catatan
                                    </span>
                                @endif

                            </div>

                        </div>

                    </div>

                    <div class="flex items-center gap-2 shrink-0">

                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold {{ $statusClass }}">
                            {{ ucfirst($status) }}
                        </span>

                        <span class="text-gray-400 text-xs" x-text="open ? '▲' : '▼'"></span>

                    </div>

                </button>

                {{-- DETAIL --}}
                <div x-show="open"
                     x-transition
                     class="border-t border-gray-100 dark:border-gray-800">

                    <div class="p-4">

                        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                            {{ $req['description'] }}
                        </p>

                        @if($firstItem?->description)
                            <div class="mt-3 rounded-xl bg-blue-50 border border-blue-100 p-3">
                                <p class="text-[11px] font-bold text-blue-700 mb-1">
                                    Catatan Waspang
                                </p>
                                <p class="text-xs text-blue-900 leading-relaxed">
                                    {{ $firstItem->description }}
                                </p>
                            </div>
                        @endif

                        @if($status == 'rejected' && $firstItem?->review_note)
                            <div class="mt-3 rounded-xl bg-red-50 border border-red-100 p-3">
                                <p class="text-[11px] font-bold text-red-700 mb-1">
                                    Catatan Reject Admin
                                </p>
                                <p class="text-xs text-red-900 leading-relaxed">
                                    {{ $firstItem->review_note }}
                                </p>
                            </div>
                        @endif

                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 mt-4">

                            @forelse($items as $evidence)

                                <a href="{{ asset('storage/' . $evidence->file_path) }}"
                                   target="_blank"
                                   class="aspect-square rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800">

                                    <img src="{{ asset('storage/' . $evidence->file_path) }}"
                                         class="w-full h-full object-cover hover:scale-105 transition-all duration-200">

                                </a>

                            @empty

                                <div class="col-span-3 text-xs text-gray-500">
                                    Belum ada foto eviden.
                                </div>

                            @endforelse

                        </div>

                    </div>

                    @if($firstItem)

                        <div class="border-t border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-950 p-4">

                            @if($status == 'approved')

                                <div class="flex items-center justify-between gap-3">

                                    <div>
                                        <p class="text-xs font-bold text-green-700">
                                            Eviden disetujui
                                        </p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">
                                            Item pengukuran ini sudah approved.
                                        </p>
                                    </div>

                                    <form method="POST"
                                          action="{{ route('admin.evidences.reset', $firstItem->id_evidence) }}">
                                        @csrf

                                        <button class="h-9 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                                            Atur Ulang
                                        </button>
                                    </form>

                                </div>

                            @elseif($status == 'rejected')

                                <div class="flex items-center justify-between gap-3">

                                    <div>
                                        <p class="text-xs font-bold text-red-700">
                                            Eviden ditolak
                                        </p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">
                                            Waspang perlu upload ulang eviden ini.
                                        </p>
                                    </div>

                                    <form method="POST"
                                          action="{{ route('admin.evidences.reset', $firstItem->id_evidence) }}">
                                        @csrf

                                        <button class="h-9 px-4 rounded-xl border border-gray-300 dark:border-gray-700 text-xs font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                                            Atur Ulang
                                        </button>
                                    </form>

                                </div>

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

        @endforeach

    </div>

    {{-- FOOTER --}}
    <div class="flex items-center justify-between pt-2">

        <a href="{{ route('admin.evidences.review.instalasi', $project->id_project) }}"
           class="h-10 px-5 rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-bold inline-flex items-center justify-center">
            ← Step 2 Instalasi
        </a>

        <a href="{{ route('admin.evidences.review.finishing', $project->id_project) }}"
            class="h-10 px-5 rounded-xl bg-gray-900 text-white text-sm font-bold inline-flex items-center justify-center">
                Step Berikutnya →
        </a>

    </div>

</div>

@endsection