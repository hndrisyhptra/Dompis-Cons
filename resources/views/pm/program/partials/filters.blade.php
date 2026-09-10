{{--
    Filter Search + Region + Branch + Status Progress + tombol Download Data
    LOP untuk halaman Project ID role TIF/PM. Include dengan:
      @include('pm.program.partials.filters', [
          'routeName' => 'program.osp',
          'exportRouteName' => 'program.osp.export',
      ])
    $regions, $branches, $statusOptions sudah dikirim dari
    ProgramController::getProgramData().
--}}
@php
    $hasActiveFilter = request('search') || request('region') || request('branch') || request('status_progress');
@endphp

<div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 p-4 mb-6 shadow-sm">
    <form method="GET" action="{{ route($routeName) }}" class="space-y-4">
        <div class="flex flex-col lg:flex-row gap-3">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari nama project, PID, LOP, STO, branch, mitra..."
                   class="flex-1 h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500 px-4">
            <button class="h-11 px-6 rounded-2xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold">Cari</button>
            @if($hasActiveFilter)
                <a href="{{ route($routeName) }}"
                   class="h-11 px-5 inline-flex items-center justify-center rounded-2xl border border-gray-300 dark:border-gray-700 dark:text-white text-sm font-bold hover:bg-gray-100 dark:hover:bg-gray-800">
                    Reset
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">Region</label>
                <select name="region" onchange="this.form.submit()"
                        class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm px-4">
                    <option value="">Semua Region</option>
                    @foreach($regions as $regionName => $regionBranches)
                        <option value="{{ $regionName }}" @selected(request('region') === $regionName)>{{ $regionName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">Branch</label>
                <select name="branch" onchange="this.form.submit()"
                        class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm px-4">
                    <option value="">Semua Branch</option>
                    @foreach($branches as $branchName)
                        <option value="{{ $branchName }}" @selected(request('branch') === $branchName)>{{ $branchName }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">Status Progress</label>
                <select name="status_progress" onchange="this.form.submit()"
                        class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm px-4">
                    <option value="">Semua Status</option>
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('status_progress') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-black uppercase tracking-wide text-gray-400 mb-1">Per Page</label>
                <select name="per_page" onchange="this.form.submit()"
                        class="w-full h-11 rounded-2xl border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-white text-sm px-4">
                    @foreach([10, 20, 50] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 10) === $size)>{{ $size }} data</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-end gap-3">
            <a href="{{ route($exportRouteName) }}"
               class="h-11 px-5 rounded-2xl bg-gray-100 dark:bg-gray-800 dark:text-white text-gray-700 text-sm font-black inline-flex items-center justify-center gap-2 hover:bg-gray-200 dark:hover:bg-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                Download Semua Data LOP
            </a>

            @if($hasActiveFilter)
                <a href="{{ route($exportRouteName, request()->query()) }}"
                   class="h-11 px-5 rounded-2xl bg-emerald-600 text-white text-sm font-black hover:bg-emerald-700 inline-flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download Sesuai Filter
                </a>
            @endif
        </div>
    </form>
</div>
