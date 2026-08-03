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