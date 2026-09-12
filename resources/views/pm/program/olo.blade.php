@extends('layouts.pm')

@section('content')

<div x-data="pmProjectDetailModal()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Program OLO</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">List Project OLO</p>
        </div>
    </div>

    @include('pm.program.partials.filters', [
        'routeName' => 'program.olo',
        'exportRouteName' => 'program.olo.export',
    ])

    @include('pm.program.partials.table', ['projects' => $projects, 'programName' => 'OLO'])

    @include('pm.program.partials.detail-modal')

    {{-- Revisi (permintaan user): modal BARU "Review BOQ" khusus role tif --}}
    @include('pm.program.partials.boq-compare-modal')

</div>

@endsection
