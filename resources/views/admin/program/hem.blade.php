@extends('layouts.admin')

@section('content')

{{-- Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Program HEM</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">List Project Konstruksi</p>
    </div>
    <!-- <div class="flex gap-2">
        <button type="button" onclick="openImportModal()" class="h-10 px-4 inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-gray-700 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-gray-800">Import CSV</button>
        <button type="button" onclick="openProjectModal('HEM')" class="h-10 px-4 inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">+ LOP HEM Baru</button>
    </div> -->
</div>

{{-- Search & Filter --}}
<div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-4 mb-6 shadow-sm">
    <form method="GET" action="{{ route('program.hem') }}" class="space-y-4">
        <div class="flex flex-col lg:flex-row gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari project, STO, branch, mitra..." class="flex-1 h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm focus:ring-blue-500 focus:border-blue-500 px-4">
            <button class="h-11 px-6 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold">Cari</button>
            <a href="{{ route('program.hem') }}" class="h-11 px-5 inline-flex items-center justify-center rounded-2xl border border-gray-300 text-sm font-bold hover:bg-gray-100">Reset</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-1 gap-3">
            <div>
                <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">Branch</label>
                <select name="branch" onchange="this.form.submit()" class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm px-4">
                    <option value="">Semua Branch</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch }}" {{ request('branch') == $branch ? 'selected' : '' }}>{{ $branch }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

{{-- Include Tabel Utama (Dibuat reusable) --}}
@include('admin.program.partials.table', ['projects' => $projects, 'programName' => 'HEM'])
@include('admin.projects.partials.modals')
@include('admin.projects.modals.boq-modal')
@include('admin.projects.partials.scripts')

@endsection