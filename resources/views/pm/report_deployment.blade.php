{{--
    Halaman BARU "Report Deployment" (permintaan user) -- khusus role PM
    (route pm.report_deployment, role:pm SAJA, TIDAK termasuk tif). Isinya
    tabel "Report Deployment" yang sama persis dengan yang ada di Dashboard
    PM, tapi PROGRAM LENGKAP (Konstruksi Eksternal tetap tampil, beda dgn
    versi inline di pm/dashboard.blade.php yang exclude program itu utk
    role tif).
--}}
@extends('layouts.pm')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Report Deployment</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Breakdown status progress seluruh LOP PT 3, per Region &amp; Branch, dengan filter Region/Branch/Program.</p>
    </div>

    @include('partials.report-deployment', ['stageCube' => $stageCube, 'matrixDetailRoute' => 'pm.dashboard.matrix-detail'])
</div>
@endsection
