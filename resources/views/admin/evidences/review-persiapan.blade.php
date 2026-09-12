@extends('layouts.admin')

@section('content')

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

    {{-- HEADER & STEPPER --}}
    @include('admin.evidences.partials.stepper')

    {{-- Step Title --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="h-1 bg-red-500"></div>

        <div class="p-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Step 1 — Persiapan
                </h2>
                <p class="text-sm text-gray-500">
                    4 sub-step: Inisiasi, Survey, Perizinan &amp; Material Delivery
                </p>
            </div>

            <span class="px-3 py-1 rounded-full text-xs font-bold
                {{ $persiapanDone
                    ? 'bg-green-100 text-green-700'
                    : 'bg-yellow-100 text-yellow-700' }}">
                {{ $persiapanDone ? 'Approved' : 'Pending' }}
            </span>
        </div>
    </div>

    {{-- BREAKDOWN 4 SUB-STEP (status indikator posisi LOP, sama pola dgn stepper Waspang) --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach($subSteps as $key => $sub)
            @php
                $subClass = match(true) {
                    $sub['done'] => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:border-emerald-900',
                    $sub['active'] => 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:border-blue-900',
                    default => 'bg-gray-50 text-gray-500 border-gray-200 dark:bg-gray-800/40 dark:border-gray-700',
                };
                $subStatusLabel = $sub['done'] ? 'Selesai' : ($sub['active'] ? 'Aktif' : 'Menunggu');
            @endphp
            <div class="rounded-2xl border p-3 {{ $subClass }}">
                <p class="text-[10px] font-black uppercase tracking-wider">{{ $sub['label'] }}</p>
                <p class="text-xs font-bold mt-1">{{ $subStatusLabel }}</p>
            </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-500 px-1 leading-relaxed">
        Sub-step <strong>Inisiasi</strong> &amp; <strong>Survey</strong> belum punya eviden foto yang perlu di-approve di halaman ini (dikerjakan lewat menu lain) — kartu di atas cuma menunjukkan posisi LOP saat ini. Sub-step <strong>Perizinan</strong> &amp; <strong>Material Delivery</strong> punya eviden foto yang perlu di-review di bawah.
    </p>

    {{-- Eviden Perizinan --}}
    @include('admin.evidences.partials.review-item', [
        'number' => 1,
        'title' => 'Eviden Perizinan',
        'description' => '',
        'items' => $perizinanEvidences,
        'type' => 'perizinan',
    ])

    {{-- Eviden BA KP --}}
    @include('admin.evidences.partials.review-item', [
        'number' => 2,
        'title' => 'Eviden BA KP',
        'description' => '',
        'items' => $baKpEvidences,
        'type' => 'ba_kp',
    ])

    {{-- Eviden Material Delivery --}}
    @include('admin.evidences.partials.review-item', [
        'number' => 3,
        'title' => 'Eviden Material Delivery',
        'description' => '',
        'items' => $materialDeliveryEvidences,
        'type' => 'material_delivery',
    ])

    {{-- Footer --}}
    <div class="flex items-center justify-between pt-2">

        <p class="text-sm text-gray-600">
            Step 1 dari 7
        </p>

        <a href="{{ route('admin.evidences.review.project', $project->id_project) }}"
        class="h-10 px-5 rounded-xl bg-gray-900 text-white text-sm font-bold inline-flex items-center justify-center">
            Step Berikutnya (Persiapan Instalasi) →
        </a>

    </div>

</div>

@endsection
