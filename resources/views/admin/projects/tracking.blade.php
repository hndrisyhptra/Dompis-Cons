@extends('layouts.admin')

@section('content')

<div class="max-w-5xl mx-auto space-y-6">

    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">
                    Tracking Project
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    Riwayat aktivitas project dari assign, upload eviden, approval, kendala, sampai complete.
                </p>
            </div>

            <a href="{{ url()->previous() }}"
               class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 text-sm font-bold hover:bg-gray-200 dark:hover:bg-gray-700">
                Kembali
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">PID</p>
                <p class="font-black text-gray-900 dark:text-white mt-1">
                    {{ $project->pid ?? '-' }}
                </p>
            </div>

            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">PID SAP</p>
                <p class="font-black text-gray-900 dark:text-white mt-1">
                    {{ $project->pid_sap ?? '-' }}
                </p>
            </div>

            <div class="rounded-2xl bg-gray-50 dark:bg-gray-800 p-4">
                <p class="text-xs text-gray-500">Progress</p>
                <p class="font-black text-emerald-600 mt-1">
                    {{ $project->progressSummary()['progress'] ?? 0 }}%
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-6">

        <h2 class="text-lg font-black text-gray-900 dark:text-white mb-6">
            Timeline Aktivitas
        </h2>

        <div class="relative border-l-2 border-gray-200 dark:border-gray-800 ml-3 space-y-7">

            @forelse($logs as $log)

                @php
                    $color = match($log->activity_type) {
                        'assign_waspang' => 'bg-blue-500',
                        'reassign_waspang' => 'bg-indigo-500',
                        'upload_evidence' => 'bg-amber-500',
                        'approve_evidence' => 'bg-emerald-500',
                        'reject_evidence' => 'bg-red-500',
                        'update_kendala' => 'bg-orange-500',
                        'project_completed' => 'bg-green-600',
                        default => 'bg-gray-500',
                    };
                @endphp

                <div class="relative pl-8">

                    <span class="absolute -left-[9px] top-1 w-4 h-4 rounded-full {{ $color }} border-4 border-white dark:border-gray-900"></span>

                    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/60 p-4">

                        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-2">
                            <div>
                                <h3 class="font-black text-gray-900 dark:text-white">
                                    {{ $log->title }}
                                </h3>

                                <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                    {{ $log->description }}
                                </p>
                            </div>

                            <span class="text-xs text-gray-500 whitespace-nowrap">
                                {{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-4 text-xs">

                            <span class="px-3 py-1 rounded-full bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                                Oleh: {{ $log->user?->name ?? '-' }}
                            </span>

                            @if($log->targetUser)
                                <span class="px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                    Target: {{ $log->targetUser->name }}
                                </span>
                            @endif

                            @if($log->stage)
                                <span class="px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                    Stage: {{ ucfirst($log->stage) }}
                                </span>
                            @endif

                            @if($log->status_after)
                                <span class="px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                                    Status: {{ ucfirst($log->status_after) }}
                                </span>
                            @endif

                        </div>

                    </div>
                </div>

            @empty

                <div class="pl-8 text-center py-10">
                    <p class="text-gray-500 text-sm">
                        Belum ada aktivitas tracking.
                    </p>
                </div>

            @endforelse

        </div>

    </div>

</div>

@endsection