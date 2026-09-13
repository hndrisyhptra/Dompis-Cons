@extends('layouts.pm')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Durasi per Tahap</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Rata-rata durasi LOP PT 3 menyelesaikan tiap staging (Inisiasi s.d. Golive, termasuk breakdown sub-tahap Persiapan), per Region &amp; Branch, dengan filter Region/Branch/Program.</p>
    </div>

    @include('partials.stage-duration-report', ['durationCube' => $durationCube, 'stageMeta' => $stageMeta])
</div>
@endsection
