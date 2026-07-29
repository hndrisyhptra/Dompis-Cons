@extends('layouts.admin')

@section('content')
@php
    // Ambil data berdasarkan evidence_type yang spesifik untuk Step 3
    $evRedaman = $project->evidences->where('stage', 'finishing')->where('evidence_type', 'redaman_port');
    
    // PERBAIKAN: Gunakan key 'foto_lainnya' sesuai dengan Controller Teknisi
    $evLainnya = $project->evidences->where('stage', 'finishing')->where('evidence_type', 'foto_lainnya');
    
    // Gabungkan untuk mengecek status persetujuan keseluruhan Step 3
    $evStep3All = collect()->merge($evRedaman)->merge($evLainnya);
    
    // Cek apakah selesai (jika ada data, dan semua data yang ada statusnya 'approved')
    $step3Completed = $evStep3All->count() > 0 && $evStep3All->where('status', 'approved')->count() == $evStep3All->count();
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

        @include('admin.pt2.partials.stepper', ['currentStep' => 3])
    </div>

    {{-- Step Title --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="h-1 bg-indigo-500"></div>
        <div class="p-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Step 3 — Redaman & Port</h2>
                <p class="text-sm text-gray-500">Tinjau hasil ukur redaman dan foto tambahan (opsional).</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $step3Completed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ $step3Completed ? 'Approved' : 'Pending' }}
            </span>
        </div>
    </div>

    {{-- 1. Eviden Redaman / OPM / OTDR --}}
    @include('admin.evidences.partials.review-item', [
        'number' => 1,
        'title' => 'Eviden Hasil Ukur (Redaman & Port)',
        'description' => 'Bukti foto pengukuran redaman (OPM/OTDR) sesuai Mode (A/B/C).',
        'items' => $evRedaman,
        'type' => 'redaman_port',
    ])

    {{-- 2. Eviden Tambahan (Lainnya) - Opsional --}}
    @include('admin.evidences.partials.review-item', [
        'number' => 2,
        'title' => 'Foto Tambahan / Lainnya (Opsional)',
        'description' => 'Dokumentasi pendukung lainnya di lapangan.',
        'items' => $evLainnya,
        'type' => 'foto_lainnya', {{-- PASTIKAN INI TERTULIS foto_lainnya --}}
    ])

    {{-- Footer Actions --}}
    <div class="flex items-center justify-between pt-2">
        <a href="{{ route('admin.pt2.instalasi', $project->id_project) }}" class="text-sm font-bold text-gray-500 hover:text-gray-900 transition">
            ← Step Instalasi
        </a>
        <a href="{{ route('admin.pt2.dismantle', $project->id_project) }}" class="h-10 px-5 rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-bold inline-flex items-center justify-center transition hover:opacity-90">
            Step Dismantle →
        </a>
    </div>

</div>
@endsection