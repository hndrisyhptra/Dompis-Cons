{{--
    Halaman "Download Semua LOP" (menu BARU, permintaan user 2026-09-17) --
    khusus role superadmin. Sengaja dibuat sederhana (cuma 2 tombol besar),
    terpisah dari halaman Data PID (admin.data-pid) yang sudah ada dan lebih
    lengkap (KPI, filter, tabel) -- lihat ImportController::downloadLopPage().
--}}
@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <p class="text-xs font-black text-blue-700 uppercase tracking-widest">Master Data</p>
        <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">Download Semua LOP</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Unduh seluruh data LOP yang ada di database, dipisah PT 3 (Regular) dan PT 2, dalam format Excel.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-blue-200 dark:border-blue-900 p-6 shadow-sm flex flex-col">
            <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
            </div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white">PT 3 (Regular)</h2>
            <p class="text-xs text-slate-500 mt-1 flex-1">Seluruh program: OSP, OLO, HEM, NODE B, EKSBIS, Konstruksi Eksternal. Total <span class="font-bold">{{ number_format($totalRegularLop) }}</span> LOP.</p>
            <a href="{{ route('admin.data-pid.export', ['type' => 'regular']) }}"
               class="mt-5 h-11 px-5 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-black inline-flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download PT 3
            </a>
        </div>

        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-900 p-6 shadow-sm flex flex-col">
            <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5Z"/><path d="M8 7h8"/><path d="M8 11h8"/><path d="M8 15h5"/></svg>
            </div>
            <h2 class="text-lg font-black text-slate-900 dark:text-white">PT 2</h2>
            <p class="text-xs text-slate-500 mt-1 flex-1">Seluruh LOP Program PT 2. Total <span class="font-bold">{{ number_format($totalPt2Lop) }}</span> LOP.</p>
            <a href="{{ route('admin.data-pid.export', ['type' => 'pt2']) }}"
               class="mt-5 h-11 px-5 rounded-2xl bg-indigo-700 hover:bg-indigo-800 text-white text-sm font-black inline-flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download PT 2
            </a>
        </div>
    </div>
</div>
@endsection
