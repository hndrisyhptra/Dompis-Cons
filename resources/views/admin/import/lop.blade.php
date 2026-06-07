@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            Import LOP
        </h1>
        <p class="text-sm text-gray-500">
            Upload data LOP berdasarkan WO Order dan auto mapping ke PID.
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-2xl bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm font-semibold">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 p-4">

        <form action="{{ route('admin.import.lop.upload') }}"
              method="POST"
              enctype="multipart/form-data"
              class="flex flex-col lg:flex-row lg:items-end gap-3">

            @csrf

            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">
                    File CSV LOP
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
                Minimal header:
                <span class="font-mono text-gray-700 dark:text-gray-300">
                    lop_name,pid_sap,program_sap,sto,branch,wo_smile,mitra_name
                </span>
            </p>
        </div>

    </div>

     <div class="flex flex-col sm:flex-row justify-between items-center mb-4 gap-4">
    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-200"></h2>
    <form method="GET"
          action="{{ route('admin.import.lop') }}"
          class="flex gap-2">

        <input type="text"
               name="search"
               value="{{ $search ?? '' }}"
               placeholder="Cari LOP, PID SAP, STO, Branch..."
               class="h-10 w-80 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm px-3">

        <button
            class="h-10 px-4 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold">
            Cari
        </button>

        @if(!empty($search))
            <a href="{{ route('admin.import.lop') }}"
               class="h-10 px-4 rounded-xl border border-gray-300 text-sm font-bold inline-flex items-center justify-center">
                Reset
            </a>
        @endif

    </form>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden">

        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-sm font-bold text-gray-900 dark:text-white">
                    Data LOP Terbaru
                </h2>
                <p class="text-xs text-gray-500">
                    Menampilkan 10 data per halaman
                </p>
            </div>

            <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold">
                {{ $lops->total() }} data
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">LOP</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">PID SAP</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">STO</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Branch</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">WO Smile</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Mapping</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($lops as $lop)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/70">
                            <td class="px-4 py-3">
                                <p class="font-bold text-gray-900 dark:text-white max-w-xs truncate">
                                    {{ $lop->lop_name ?? '-' }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ $lop->mitra_name ?? '-' }}
                                </p>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $lop->pid_sap ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $lop->sto ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $lop->branch ?? '-' }}
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $lop->wo_smile ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                @if($lop->mapping_status == 'auto_matched')
                                    <span class="px-2.5 py-1 rounded-lg bg-green-50 text-green-700 text-xs font-bold">
                                        Auto Matched
                                    </span>
                                @elseif($lop->mapping_status == 'manual_mapped')
                                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold">
                                        Manual
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg bg-red-50 text-red-700 text-xs font-bold">
                                        Unmapped
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3 text-left">

                                @if($lop->mapping_status == 'manual_mapped')

                                    <form method="POST"
                                        action="{{ route('admin.import.lop.mapping.reset', $lop->id_lop) }}"
                                        onsubmit="return confirm('Reset mapping manual LOP ini?')">
                                        @csrf

                                        <button class="h-8 px-3 rounded-lg border border-yellow-300 text-yellow-700 bg-yellow-50 hover:bg-yellow-100 text-xs font-bold">
                                            Remapping
                                        </button>
                                    </form>

                                @elseif($lop->mapping_status == 'auto_matched')

                                    <span class="text-xs text-gray-400">
                                        Auto locked
                                    </span>

                                @else

                                    <a href="{{ route('admin.import.lop.mapping') }}"
                                    class="h-8 px-3 inline-flex items-center justify-center rounded-lg bg-blue-600 text-white text-xs font-bold">
                                        Mapping
                                    </a>

                                @endif

                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                Belum ada data LOP.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800">
            {{ $lops->links() }}
        </div>

    </div>

</div>

@endsection