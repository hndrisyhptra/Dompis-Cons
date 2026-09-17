@extends('layouts.pm')

@section('content')

<div x-data="pmPt2DetailModal()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Program PT 2</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">List LOP Program PT 2 (read-only)</p>
        </div>
    </div>

    @include('pm.program.partials.filters', [
        'routeName' => 'program.pt2',
        'exportRouteName' => 'program.pt2.export',
    ])

    @include('pm.program.partials.pt2-table', ['lops' => $lops, 'programName' => 'PT 2', 'statusOptions' => $statusOptions])

    @include('pm.program.partials.pt2-detail-modal')

</div>

@endsection
