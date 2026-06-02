@extends('layouts.admin')

@section('content')

    {{-- Statistik --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Total LOP
            </p>

            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">
                {{ $totalProject }}
            </h2>

        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Project Active
            </p>

            <h2 class="text-2xl font-bold text-blue-700 dark:text-blue-400 mt-2">
                {{ $activeProject }}
            </h2>

        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Waiting UT
            </p>

            <h2 class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-2">
                {{ $waitingUt }}
            </h2>

        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Completed
            </p>

            <h2 class="text-2xl font-bold text-green-700 dark:text-green-400 mt-2">
                {{ $completedProject }}
            </h2>

        </div>

    </div>

    {{-- CHART ANALYTICS --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">

            {{-- STATUS PROJECT --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">
        
                <div class="mb-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Status Project
                    </h2>

                    <p class="text-xs text-gray-500">
                        Active • Waiting UT • Completed
                    </p>
                </div>

                <div class="h-[320px]">
                    <canvas id="statusChart"></canvas>
                </div>

            </div>

            {{-- PROGRESS STO --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">

                <div class="mb-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Progress STO
                    </h2>

                    <p class="text-xs text-gray-500">
                        Persentase Ready UT
                    </p>
                </div>

                <div class="h-[350px]">
                    <canvas id="stoChart"></canvas>
                </div>

            </div>

            {{-- PROGRESS BRANCH --}}
            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-5">

                <div class="mb-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Progress Branch
                    </h2>

                    <p class="text-xs text-gray-500">
                        Total vs Ready UT
                    </p>
                </div>

                <div class="h-[350px]">
                    <canvas id="branchChart"></canvas>
                </div>

            </div>

            {{-- APPROVAL PROGRESS --}}
            <div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm p-5">

                <div class="mb-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        Approval Progress
                    </h2>

                    <p class="text-xs text-gray-500">
                        Eviden Approved vs Pending
                    </p>
                </div>

                <div class="h-[320px]">
                    <canvas id="approvalChart"></canvas>
                </div>

            </div>

        </div>

    {{-- ANALYTICS PER STO / BRANCH --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mt-10">


    {{-- STO --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                    Progress per STO
                </h2>
                <p class="text-xs text-gray-500">
                    Berdasarkan project ready UT
                </p>
            </div>
        </div>

        <div class="space-y-3">

            @forelse($analyticsBySto as $item)

                <div class="rounded-xl border border-gray-100 dark:border-gray-800 p-3">

                    <div class="flex items-center justify-between gap-3 mb-2">

                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                {{ $item['label'] }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $item['ready'] }} ready · {{ $item['ongoing'] }} ongoing · {{ $item['total'] }} total
                            </p>
                        </div>

                        <span class="text-sm font-black text-blue-700">
                            {{ $item['percent'] }}%
                        </span>

                    </div>

                    <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full"
                             style="width: {{ $item['percent'] }}%">
                        </div>
                    </div>

                </div>

            @empty

                <p class="text-sm text-gray-500">
                    Belum ada data STO.
                </p>

            @endforelse

        </div>

    </div>

    {{-- BRANCH --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                        Progress per Branch
                    </h2>
                    <p class="text-xs text-gray-500">
                        Berdasarkan project ready UT
                    </p>
                </div>
            </div>

            <div class="space-y-3">

                @forelse($analyticsByBranch as $item)

                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 p-3">

                        <div class="flex items-center justify-between gap-3 mb-2">

                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $item['label'] }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $item['ready'] }} ready · {{ $item['ongoing'] }} ongoing · {{ $item['total'] }} total
                                </p>
                            </div>

                            <span class="text-sm font-black text-green-700">
                                {{ $item['percent'] }}%
                            </span>

                        </div>

                        <div class="h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                            <div class="h-full bg-green-600 rounded-full"
                                style="width: {{ $item['percent'] }}%">
                            </div>
                        </div>

                    </div>

                @empty

                    <p class="text-sm text-gray-500">
                        Belum ada data branch.
                    </p>

                @endforelse

            </div>

        </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Waiting UT', 'Completed'],
            datasets: [{
                data: [
                    {{ $activeProject }},
                    {{ $waitingUt }},
                    {{ $completedProject }}
                ],
                backgroundColor: [
                    '#2563eb',
                    '#f59e0b',
                    '#16a34a'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '68%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        usePointStyle: true
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('stoChart'), {
    type: 'bar',
    data: {
        labels: @json($analyticsBySto->pluck('label')),
        datasets: [{
            label: 'Progress Ready UT',
            data: @json($analyticsBySto->pluck('percent')),
            backgroundColor: '#2563eb',
            borderRadius: 10,
            categoryPercentage: 0.7,
            barPercentage: 0.8
        }]
    },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: value => value + '%'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

    new Chart(document.getElementById('branchChart'), {
        type: 'bar',
        data: {
            labels: @json($analyticsByBranch->pluck('label')),
            datasets: [
                {
                    label: 'Total Project',
                    data: @json($analyticsByBranch->pluck('total')),
                    backgroundColor: '#94a3b8',
                    borderRadius: 8
                },
                {
                    label: 'Ready UT',
                    data: @json($analyticsByBranch->pluck('ready')),
                    backgroundColor: '#16a34a',
                    borderRadius: 8
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        usePointStyle: true
                    }
                }
            }
        }
    });

    new Chart(document.getElementById('approvalChart'), {
    type: 'doughnut',
    data: {
        labels: ['Approved', 'Pending', 'Rejected'],
        datasets: [{
            data: [
                {{ $totalApprovedEvidence }},
                {{ $totalPendingEvidence }},
                {{ $totalRejectedEvidence }}
            ],
            backgroundColor: [
                '#16a34a',
                '#f59e0b',
                '#dc2626'
            ],
            borderWidth: 0
        }]
    },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

});
</script>
@endpush
