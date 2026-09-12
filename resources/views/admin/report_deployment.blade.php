{{--
    Halaman BARU "Report Deployment" (permintaan user) -- khusus role
    admin, superadmin, officer (route admin.report_deployment, role:
    admin,superadmin,officer -- super_tif SENGAJA TIDAK termasuk). Isinya
    tabel "Report Deployment" (breakdown status progress per Region &
    Branch + filter Region/Branch/Program + Grand Total), PROGRAM LENGKAP
    (tidak ada exclude Konstruksi Eksternal utk role-role ini).
--}}
@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Report Deployment</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Breakdown status progress seluruh LOP PT 3, per Region &amp; Branch, dengan filter Region/Branch/Program.</p>
    </div>

    @include('partials.report-deployment', ['stageCube' => $stageCube, 'matrixDetailRoute' => 'admin.dashboard.matrix-detail'])
</div>
@endsection
