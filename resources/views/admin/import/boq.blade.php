@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
            Bulk Import BOQ
        </h1>
        <p class="text-sm text-gray-500">
            Upload BOQ matrix berdasarkan PID atau Nama LOP. Sheet aktif harus sesuai nama package, contoh: PAKET 5.
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

        <form action="{{ route('admin.import.boq.upload') }}"
              method="POST"
              enctype="multipart/form-data"
              class="flex flex-col lg:flex-row lg:items-end gap-3">

            @csrf

            <div class="flex-1">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">
                    File Excel BOQ
                </label>

                <input type="file"
                       name="file"
                       accept=".xlsx,.xls"
                       required
                       class="block w-full text-sm border border-gray-300 dark:border-gray-700 rounded-xl cursor-pointer bg-white dark:bg-gray-950 dark:text-gray-300
                              file:mr-3 file:py-2.5 file:px-4 file:rounded-l-xl file:border-0 file:text-sm file:font-bold
                              file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div class="w-full lg:w-64">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">
                    Mapping Header BOQ
                </label>

                <select name="mapping_by"
                        required
                        class="block w-full h-10 text-sm border border-gray-300 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-950 dark:text-gray-300">
                    <option value="pid">Berdasarkan PID</option>
                    <option value="lop_name">Berdasarkan Nama LOP</option>
                </select>
            </div>

            <button class="h-10 px-5 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold shrink-0">
                Upload BOQ
            </button>

        </form>

        <div class="mt-3 rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-100 dark:border-gray-800 p-3">
            <p class="text-xs text-gray-500 leading-relaxed">
                Format:
                <span class="font-mono text-gray-700 dark:text-gray-300">
                    A1 = DESIGNATOR, B1 dst = PID, A2 dst = Designator, isi cell = volume.
                </span>
            </p>
        </div>

    </div>

</div>

@endsection