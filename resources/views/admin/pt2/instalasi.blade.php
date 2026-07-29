@extends('layouts.admin')

@section('content')
@php
    // PERBAIKAN: Gunakan key 'material' sesuai dengan TeknisiPt2Controller
    $evBarangTiba = $project->evidences->where('stage', 'instalasi')->where('evidence_type', 'material');
    $evProgress = $project->evidences->where('stage', 'instalasi')->where('evidence_type', 'progress_instalasi');
    
    // Gabungkan untuk mengecek status persetujuan keseluruhan Step 2
    $evInstalasiAll = collect()->merge($evBarangTiba)->merge($evProgress);
    $step2Completed = $evInstalasiAll->count() > 0 && $evInstalasiAll->where('status', 'approved')->count() == $evInstalasiAll->count();
@endphp

<div class="max-w-4xl mx-auto space-y-4 px-4 py-6">

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Approval PT2</h1>
        <p class="text-sm text-gray-500">Pilih project untuk mulai review step by step</p>
    </div>

    {{-- Project Card & Stepper --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-bold text-gray-900 dark:text-white truncate">{{ $project->project_name }}</h2>
                <p class="text-sm text-gray-500">{{ $project->lop?->branch }} · {{ $project->lop?->sto }}</p>
            </div>
            <a href="{{ route('admin.pt2.approval') }}" class="h-10 px-4 rounded-xl border border-gray-300 inline-flex items-center text-sm font-bold text-gray-700 hover:bg-gray-50 transition">← Kembali</a>
        </div>

        @include('admin.pt2.partials.stepper', ['currentStep' => 2])
    </div>

    {{-- Step Title --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="h-1 bg-indigo-500"></div>
        <div class="p-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Step 2 — Instalasi</h2>
                <p class="text-sm text-gray-500">Tinjau eviden material tiba dan progress instalasi BOQ.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $step2Completed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ $step2Completed ? 'Approved' : 'Pending' }}
            </span>
        </div>
    </div>

    {{-- 1. Eviden Material / Barang Tiba --}}
    @include('admin.evidences.partials.review-item', [
        'number' => 1,
        'title' => 'Eviden Material / Barang Tiba',
        'description' => 'Foto fisik material / kabel saat tiba di lokasi sebelum diinstalasi.',
        'items' => $evBarangTiba,
        'type' => 'material',  {{-- PASTIKAN INI TERTULIS material --}}
    ])

    {{-- 2. Eviden Progress Instalasi --}}
    @include('admin.evidences.partials.review-item', [
        'number' => 2,
        'title' => 'Eviden Progress Instalasi',
        'description' => 'Foto fisik material yang telah selesai terpasang (Progress BOQ).',
        'items' => $evProgress,
        'type' => 'progress_instalasi',
    ])

    {{-- Footer Actions --}}
    <div class="flex items-center justify-between pt-2">
        <a href="{{ route('admin.pt2.review', $project->id_project) }}" class="text-sm font-bold text-gray-500 hover:text-gray-900 transition">
            ← Step Survey
        </a>
        <a href="{{ route('admin.pt2.redaman', $project->id_project) }}" class="h-10 px-5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-bold inline-flex items-center justify-center transition hover:opacity-90">
            Step Redaman →
        </a>
    </div>

</div>
@endsection