{{-- Report Deployment lengkap untuk role PM dan TIF. --}}
@extends('layouts.pm')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Report Deployment</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Pantau status deployment dan pergerakan pekerjaan harian seluruh Branch.
        </p>
    </div>

    @include('partials.report-deployment-tabs', [
        'activeTab' => $activeTab,
        'summaryDate' => $summaryDate,
        'reportRouteName' => 'pm.report_deployment',
    ])

    @if($activeTab === 'summary')
        @include('partials.deployment-movement-summary', [
            'summaryData' => $summaryData,
            'reportRouteName' => 'pm.report_deployment',
        ])
    @else
        @include('partials.report-deployment', ['stageCube' => $stageCube, 'matrixDetailRoute' => 'pm.dashboard.matrix-detail'])

        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">Report Deployment — PT 2</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Breakdown status progress seluruh LOP PT 2, per Region &amp; Branch.</p>
        </div>

        @include('partials.report-deployment-pt2', ['pt2StageCube' => $pt2StageCube, 'matrixDetailRoute' => 'pm.dashboard.matrix-detail'])
    @endif
</div>
@endsection
