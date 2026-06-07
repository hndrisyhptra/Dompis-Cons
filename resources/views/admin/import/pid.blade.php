@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Import PID
            </h1>

            <p class="text-sm text-gray-500">
                Upload master PID harian sebagai dasar mapping LOP.
            </p>
        </div>

    </div>

    {{-- ALERT --}}
    @if(session('success'))
        <div class="rounded-2xl bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- UPLOAD CARD --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <form action="{{ route('admin.import.pid.upload') }}"
              method="POST"
              enctype="multipart/form-data"
              class="flex flex-col lg:flex-row lg:items-end gap-3">

            @csrf

            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">
                    File CSV PID
                </label>

                <input type="file"
                       name="file"
                       accept=".csv,.txt"
                       required
                       class="block w-full text-sm border border-gray-300 dark:border-gray-700 rounded-xl cursor-pointer bg-white dark:bg-gray-950 dark:text-gray-300
                              file:mr-3 file:py-2.5 file:px-4 file:rounded-l-xl file:border-0 file:text-sm file:font-bold
                              file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <button class="h-10 px-5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shrink-0">
                Upload
            </button>

        </form>

        <div class="mt-3 rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800 p-3">
            <p class="text-xs text-gray-500 leading-relaxed">
                Format header:
                <span class="font-mono text-gray-700 dark:text-gray-300">
                    pid, pid_sap, project_name, program, execution_type, status_project, latitude, longitude, location_address, map_note
                </span>
            </p>
        </div>

    </div>

    <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200"></h2>
     <form method="GET"
          action="{{ route('admin.import.pid') }}"
          class="flex gap-2">

        <input type="text"
               name="search"
               value="{{ $search ?? '' }}"
               placeholder="Cari PID, PID SAP, Project..."
               class="h-10 w-80 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm px-3">

        <button
            class="h-10 px-4 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold">
            Cari
        </button>

        @if(!empty($search))
            <a href="{{ route('admin.import.pid') }}"
               class="h-10 px-4 rounded-xl border border-gray-300 text-sm font-bold inline-flex items-center justify-center">
                Reset
            </a>
        @endif

    </form>
    </div>
    

    {{-- TABLE CARD --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-3">

            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                    Data PID Terbaru
                </h2>

                <p class="text-xs text-gray-500">
                    Menampilkan 10 data per halaman
                </p>
            </div>

            <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold">
                {{ $projects->total() }} data
            </span>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                            PID
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                            PID SAP
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                            Nama LOP
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                            Program
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                            Jenis Eksekusi
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                            Status
                        </th>
                        <!-- <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                            GPS
                        </th> -->
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">

                    @forelse($projects as $project)

                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/70 transition">

                            <td class="px-4 py-3 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $project->pid ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $project->pid_sap ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900 dark:text-white max-w-xs truncate">
                                    {{ $project->project_name ?? '-' }}
                                </p>

                                @if($project->location_address)
                                    <p class="text-xs text-gray-500 max-w-xs truncate mt-0.5">
                                        {{ $project->location_address }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $project->program ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-bold">
                                    {{ $project->execution_type ?? '-' }}
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2.5 py-1 rounded-lg
                                    @if($project->status_project == 'active')
                                        bg-green-50 text-green-700
                                    @elseif($project->status_project == 'close' || $project->status_project == 'bast')
                                        bg-blue-50 text-blue-700
                                    @else
                                        bg-gray-100 text-gray-600
                                    @endif
                                    text-xs font-bold">
                                    {{ $project->status_project ?? '-' }}
                                </span>
                            </td>

                            <!-- <td class="px-4 py-3">
                                @if($project->latitude && $project->longitude)
                                    <span class="px-2.5 py-1 rounded-lg bg-green-50 text-green-700 text-xs font-bold">
                                        Ada
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-500 text-xs font-bold">
                                        Kosong
                                    </span>
                                @endif
                            </td> -->

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                Belum ada data PID.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- PAGINATION --}}
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
            {{ $projects->links() }}
        </div>

    </div>

</div>

@endsection