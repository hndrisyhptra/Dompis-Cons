@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        <div>
            <h1 class="text-xl font-bold">
                Manual Mapping PID ↔ LOP
            </h1>

            <p class="text-sm text-gray-500">
                LOP yang tidak berhasil auto matching
            </p>
        </div>

        <form method="GET"
            action="{{ route('admin.import.lop.mapping') }}"
            class="flex gap-2">

            <input type="text"
                name="search"
                value="{{ $search ?? '' }}"
                placeholder="Cari LOP, STO, Branch..."
                class="h-10 w-80 rounded-xl border border-gray-300 dark:border-gray-700 dark:bg-gray-950 text-sm px-3">

            <button
                class="h-10 px-4 rounded-xl bg-blue-700 hover:bg-blue-800 text-white text-sm font-bold">
                Cari
            </button>

            @if(!empty($search))
                <a href="{{ route('admin.import.lop.mapping') }}"
                class="h-10 px-4 rounded-xl border border-gray-300 text-sm font-bold inline-flex items-center justify-center hover:bg-gray-50">
                    Reset
                </a>
            @endif

        </form>

    </div>

    @if(session('success'))

        <div class="bg-green-100 text-green-700 rounded-xl px-4 py-3">
            {{ session('success') }}
        </div>

    @endif

    <div class="bg-white rounded-2xl border overflow-hidden">

        <table class="w-full text-sm">

            <thead class="bg-gray-50">

                <tr>

                    <th class="px-4 py-3 text-left">
                        Nama LOP
                    </th>

                    <th class="px-4 py-3 text-center">
                        Program
                    </th>

                    <th class="px-4 py-3 text-center">
                        Mapping PID
                    </th>

                    <th class="px-4 py-3 text-center">
                        Action
                    </th>

                </tr>

            </thead>

            <tbody>

            @foreach($unmappedLops as $lop)

                <tr class="border-t">

                    <td class="px-4 py-3">

                        <div class="font-semibold">
                            {{ $lop->lop_name }}
                        </div>

                    </td>

                    <td class="px-4 py-3">

                        {{ $lop->program_sap }}

                    </td>

                    <td class="px-4 py-3">

                        <form
                            method="POST"
                            action="{{ route('admin.import.lop.mapping.save', $lop->id_lop) }}"
                            class="flex gap-2"
                        >

                            @csrf

                            <select
                                name="project_id"
                                class="pid-select w-full"
                                required
                            >

                                <option value="">
                                    Pilih PID
                                </option>

                                @foreach($projects as $project)

                                    <option
                                        value="{{ $project->id_project }}"
                                    >

                                        {{ $project->pid_sap }}
                                        -
                                        {{ $project->project_name }}

                                    </option>

                                @endforeach

                            </select>

                    </td>

                    <td class="px-4 py-3 text-right">

                        <button
                            class="px-4 py-2 rounded-xl bg-blue-700 text-white"
                        >

                            Mapping

                        </button>

                        </form>

                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

    {{ $unmappedLops->links() }}

</div>

<script>

$(document).ready(function() {

    $('.pid-select').select2({

        placeholder: 'Cari PID SAP atau Nama Project',

        width: '100%'

    });

});

</script>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    $('.pid-select').select2({
        placeholder: 'Cari PID SAP / Nama LOP',
        width: '100%',
        allowClear: true
    });
});
</script>
@endpush