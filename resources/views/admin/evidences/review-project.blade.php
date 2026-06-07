@extends('layouts.admin')

@section('content')

@php
    $barangTiba = $project->evidences
        ->where('stage', 'persiapan')
        ->where('evidence_type', 'barang_tiba');

    $perizinan = $project->evidences
        ->where('stage', 'persiapan')
        ->where('evidence_type', 'perizinan');

    $barangTibaStatus = $barangTiba->first()->status ?? 'pending';
    $perizinanStatus = $perizinan->first()->status ?? 'pending';

    $pendingCount = 0;
    if ($barangTiba->where('status', 'approved')->count() == 0) $pendingCount++;
    if ($perizinan->where('status', 'approved')->count() == 0) $pendingCount++;

    $barangApproved =
    $barangTiba->count() > 0 &&
    $barangTiba->where('status', 'approved')->count() == $barangTiba->count();

    $perizinanApproved =
    $perizinan->count() > 0 &&
    $perizinan->where('status', 'approved')->count() == $perizinan->count();

    $persiapanCompleted =
    $barangApproved &&
    $perizinanApproved;
    
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

    {{-- Project Card --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <div class="flex items-start justify-between gap-3">

            <div class="min-w-0">
                <h2 class="text-base font-bold text-gray-900 dark:text-white truncate">
                    {{ $project->project_name }}
                </h2>

                <p class="text-sm text-gray-500">
                    {{ $project->lop?->branch }} · {{ $project->lop?->sto }}
                </p>
            </div>

            <a href="{{ route('admin.evidences.approval') }}"
               class="h-10 px-4 rounded-xl border border-gray-300 dark:border-gray-700 inline-flex items-center text-sm font-bold">
                ← Kembali
            </a>

        </div>

        {{-- STEPPER COMPACT --}}
            <div class="mt-5 flex items-center justify-between">

                <a href="{{ route('admin.evidences.review.project', $project->id_project) }}"
                class="flex flex-col items-center w-16">
                    <div class="w-8 h-8 rounded-full border border-red-500 bg-red-50 text-red-600 flex items-center justify-center text-xs font-bold">
                        1
                    </div>
                    <p class="mt-1 text-[11px] font-bold text-red-600">
                        Persiapan
                    </p>
                </a>

                <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>

                <a href="{{ route('admin.evidences.review.instalasi', $project->id_project) }}"
                    class="flex flex-col items-center w-16 opacity-50">
                    <div class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-xs font-bold">
                        2
                    </div>
                    <p class="mt-1 text-[11px] font-semibold">
                        Instalasi
                    </p>
                </a>

                <div class="flex-1 h-0.5 bg-gray-200 mx-1"></div>

                <a href="{{ route('admin.evidences.review.pengukuran', $project->id_project) }}"
                    class="flex flex-col items-center w-16 opacity-50">
                    <div class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center text-xs font-bold">
                        3
                    </div>
                    <p class="mt-1 text-[11px] font-semibold">
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

    {{-- Step Title --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="h-1 bg-red-500"></div>

        <div class="p-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Step 1 — Persiapan
                </h2>
                <p class="text-sm text-gray-500">
                    2 item eviden wajib: barang tiba & perizinan
                </p>
            </div>

            <span class="px-3 py-1 rounded-full text-xs font-bold
                {{ $persiapanCompleted
                    ? 'bg-green-100 text-green-700'
                    : 'bg-yellow-100 text-yellow-700' }}">
                {{ $persiapanCompleted ? 'Approved' : 'Pending' }}
            </span>
        </div>
    </div>

    {{-- Eviden Barang Tiba --}}
    @include('admin.evidences.partials.review-item', [
        'number' => 1,
        'title' => 'Eviden Barang / Material Tiba',
        'description' => '',
        'items' => $barangTiba,
        'type' => 'barang_tiba',
    ])

    {{-- Eviden Perizinan --}}
    @include('admin.evidences.partials.review-item', [
        'number' => 2,
        'title' => 'Eviden Perizinan',
        'description' => '',
        'items' => $perizinan,
        'type' => 'perizinan',
    ])

    {{-- Footer --}}
    <div class="flex items-center justify-between pt-2">

        <p class="text-sm text-gray-600">
            Step 1 dari 4
        </p>

        <a href="{{ route('admin.evidences.review.instalasi', $project->id_project) }}"
        class="h-10 px-5 rounded-xl bg-gray-900 text-white text-sm font-bold inline-flex items-center justify-center">
            Step Berikutnya →
        </a>

    </div>

</div>

@endsection