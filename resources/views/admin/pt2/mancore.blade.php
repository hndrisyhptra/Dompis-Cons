@extends('layouts.admin')

@section('content')
@php
    $mancore = \Illuminate\Support\Facades\DB::table('pt2_mancores')->where('project_id', $project->id_project)->first();
    $step5Completed = $mancore ? true : false;
@endphp

<div class="max-w-4xl mx-auto space-y-4 px-4 py-6">

    {{-- Header & Stepper --}}
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Approval PT2</h1>
        <p class="text-sm text-gray-500">Pilih project untuk mulai review step by step</p>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-bold text-gray-900 dark:text-white truncate">{{ $project->project_name }}</h2>
                <p class="text-sm text-gray-500">{{ $project->lop?->branch }} · {{ $project->lop?->sto }}</p>
            </div>
            <a href="{{ route('admin.pt2.approval') }}" class="h-10 px-4 rounded-xl border border-gray-300 inline-flex items-center text-sm font-bold text-gray-700">← Kembali</a>
        </div>
        
        @include('admin.pt2.partials.stepper', ['currentStep' => 5])
    </div>

    {{-- Step Title --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="h-1 bg-indigo-500"></div>
        <div class="p-4 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Step 5 — Mancore</h2>
                <p class="text-sm text-gray-500">Data update core pada sistem UIM.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $step5Completed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                {{ $step5Completed ? 'Submitted' : 'Pending' }}
            </span>
        </div>
    </div>

    {{-- Form Data Mancore --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5 shadow-sm">
        @if($mancore)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] font-bold text-gray-500 uppercase">ODP Label</p>
                    <p class="text-lg font-black text-gray-900 dark:text-white mt-1">{{ $mancore->odp_label ?? '-' }}</p>
                </div>
                
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <p class="text-[10px] font-bold text-gray-500 uppercase">ODC Label</p>
                    <p class="text-lg font-black text-gray-900 dark:text-white mt-1">{{ $mancore->odc_label ?? '-' }}</p>
                </div>

                <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                    <p class="text-[10px] font-bold text-indigo-500 uppercase">Distribusi Core</p>
                    <p class="text-base font-black text-indigo-700 dark:text-indigo-400 mt-1">{{ $mancore->distribusi_core ?? '-' }}</p>
                </div>

                <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800">
                    <p class="text-[10px] font-bold text-indigo-500 uppercase">Feeder Core</p>
                    <p class="text-base font-black text-indigo-700 dark:text-indigo-400 mt-1">{{ $mancore->feeder_core ?? '-' }}</p>
                </div>
            </div>
            
            <p class="text-xs text-gray-400 mt-4 text-right">Diupdate pada: {{ \Carbon\Carbon::parse($mancore->updated_at)->format('d M Y H:i') }}</p>
        @else
            <div class="p-8 text-center text-sm font-bold text-red-500 bg-red-50 rounded-xl">
                Teknisi belum menginput Data Mancore.
            </div>
        @endif
    </div>

    {{-- Footer Actions --}}
    <div class="flex items-center justify-between pt-2">
        <a href="{{ route('admin.pt2.dismantle', $project->id_project) }}" class="text-sm font-bold text-gray-500 hover:text-gray-900 transition">← Step Dismantle</a>
        
        {{-- Tombol Go-Live muncul di step terakhir --}}
        @if($project->status == 'waiting_ut')
        <form method="POST" action="#">
            @csrf
            <button type="button" class="h-10 px-5 rounded-xl bg-green-600 hover:bg-green-700 text-white text-sm font-bold inline-flex items-center justify-center transition shadow-md">
                Setujui & Go-Live
            </button>
        </form>
        @endif
    </div>

</div>
@endsection