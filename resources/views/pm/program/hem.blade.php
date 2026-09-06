@extends('layouts.pm')

@section('content')

<div x-data="pmProjectDetailModal()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Program HEM</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">List Project HEM</p>
        </div>
    </div>

    @include('pm.program.partials.filters', [
        'routeName' => 'program.hem',
        'exportRouteName' => 'program.hem.export',
    ])

    @include('pm.program.partials.table', ['projects' => $projects, 'programName' => 'HEM'])

    @include('pm.program.partials.detail-modal')

</div>

@endsection
