@extends('layouts.admin')

@section('content')

<div class="space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">

        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
               Bulk Import PID
            </h1>

            <p class="text-sm text-gray-500">
                Upload Data PID dan LOP lengkap.
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
                    File Excel PID
                </label>

                <input type="file"
                    name="file"
                    accept=".xlsx,.xls"
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
                    pid, pid_sap, nama_lop, program, execution_type, status_project, id_ihld, tematik, sto, branch, batch, no_sp, tgl_sp, tgl_toc, mitra_name
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
               placeholder="Cari PID, PID SAP, LOP..."
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
                            ID IHLD
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                            Program
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">
                            Aksi
                        </th>
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
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900 dark:text-white max-w-xs truncate">
                                    {{ $project->lop?->id_ihld ?? '-' }}
                                </p>
                            </td>

                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ $project->program ?? '-' }}
                            </td>

                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">

                                    <!-- <a href="{{ route('admin.import.boq') }}?project_id={{ $project->id_project }}"
                                    class="px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold hover:bg-blue-100">
                                        Upload BOQ
                                    </a>

                                    <a href="#"
                                    class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-bold hover:bg-indigo-100">
                                        Upload KML
                                    </a>

                                    @if($project->kml_file)
                                        <a href="{{ asset('storage/' . $project->kml_file) }}"
                                        target="_blank"
                                        class="px-3 py-1.5 rounded-lg bg-green-50 text-green-700 text-xs font-bold hover:bg-green-100">
                                            View KML
                                        </a>
                                    @else
                                        <span class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-400 text-xs font-bold">
                                            View KML
                                        </span>
                                    @endif -->

                                    <button type="button"
                                            onclick="openDetailModal('detailProject{{ $project->id_project }}')"
                                            class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-xs font-bold hover:bg-gray-200">
                                        Detail
                                    </button>

                                    <button type="button"
                                            onclick="openDetailModal('editProject{{ $project->id_project }}')"
                                            class="px-3 py-1.5 rounded-lg bg-yellow-50 text-yellow-700 text-xs font-bold hover:bg-yellow-100">
                                        Edit
                                    </button>

                                    <form action="{{ route('admin.import.pid.delete', $project->id_project) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin hapus project ini? Semua BOQ dan assignment akan ikut terhapus.')">

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            class="px-3 py-1.5 rounded-lg bg-red-50 text-red-700 text-xs font-bold hover:bg-red-100">
                                            Delete
                                        </button>

                                    </form>

                                </div>
                            </td>

                        </tr>

                        <div id="detailProject{{ $project->id_project }}"
                        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">

                        <div class="bg-white dark:bg-gray-900 w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-3xl border border-gray-200 dark:border-gray-800 shadow-2xl">

                            {{-- HEADER --}}
                            <div class="bg-blue-700 px-6 py-5 text-white">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold opacity-80">Detail Project</p>
                                        <h2 class="text-lg font-black leading-snug break-words">
                                            {{ $project->project_name ?? '-' }}
                                        </h2>
                                        <p class="text-xs mt-1 opacity-90">
                                            {{ $project->pid ?? '-' }} · {{ $project->pid_sap ?? '-' }}
                                        </p>
                                    </div>

                                    <button type="button"
                                            onclick="closeDetailModal('detailProject{{ $project->id_project }}')"
                                            class="w-9 h-9 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xl">
                                        ×
                                    </button>
                                </div>
                            </div>

                            {{-- BODY --}}
                            <div class="p-5 overflow-y-auto max-h-[70vh] space-y-5">

                                {{-- PROJECT --}}
                                <div>
                                    <h3 class="text-xs font-black text-gray-400 uppercase mb-3">
                                        Data PID
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">PID</p>
                                            <p class="font-bold">{{ $project->pid ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">PID SAP</p>
                                            <p class="font-bold">{{ $project->pid_sap ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">Program</p>
                                            <p class="font-bold">{{ $project->program ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">Jenis Eksekusi</p>
                                            <p class="font-bold capitalize">{{ $project->execution_type ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">Status Project</p>
                                            <p class="font-bold capitalize">{{ $project->status_project ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- LOP --}}
                                <div>
                                    <h3 class="text-xs font-black text-gray-400 uppercase mb-3">
                                        Data LOP
                                    </h3>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div class="rounded-2xl bg-blue-50 dark:bg-gray-950 p-3 md:col-span-3">
                                            <p class="text-xs text-gray-500">Nama LOP</p>
                                            <p class="font-bold break-words">{{ $project->lop?->lop_name ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">ID IHLD</p>
                                            <p class="font-bold">{{ $project->lop?->id_ihld ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">STO</p>
                                            <p class="font-bold">{{ $project->lop?->sto ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">Branch</p>
                                            <p class="font-bold">{{ $project->lop?->branch ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">Tematik</p>
                                            <p class="font-bold">{{ $project->lop?->tematik ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">Batch</p>
                                            <p class="font-bold">{{ $project->lop?->batch ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">Mitra</p>
                                            <p class="font-bold">{{ $project->lop?->mitra_name ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">No SP</p>
                                            <p class="font-bold">{{ $project->lop?->no_sp ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">Tanggal SP</p>
                                            <p class="font-bold">{{ $project->lop?->tgl_sp ?? '-' }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-gray-50 dark:bg-gray-950 p-3">
                                            <p class="text-xs text-gray-500">Tanggal TOC</p>
                                            <p class="font-bold">{{ $project->lop?->tgl_toc ?? '-' }}</p>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            {{-- FOOTER --}}
                            <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 flex justify-end">
                                <button type="button"
                                        onclick="closeDetailModal('detailProject{{ $project->id_project }}')"
                                        class="h-10 px-5 rounded-xl bg-gray-900 text-white text-sm font-bold hover:bg-gray-800">
                                    Tutup
                                </button>
                            </div>

                        </div>
                    </div>

                    {{-- MODAL EDIT --}}
                    <div id="editProject{{ $project->id_project }}"
                        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">

                        <div class="bg-white dark:bg-gray-900 w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-3xl border border-gray-200 dark:border-gray-800 shadow-2xl">

                            <div class="bg-yellow-500 px-6 py-5 text-white">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold opacity-90">Edit Data</p>
                                        <h2 class="text-lg font-black leading-snug">
                                            {{ $project->project_name ?? '-' }}
                                        </h2>
                                    </div>

                                    <button type="button"
                                            onclick="closeDetailModal('editProject{{ $project->id_project }}')"
                                            class="w-9 h-9 rounded-xl bg-white/20 hover:bg-white/30 text-white text-xl">
                                        ×
                                    </button>
                                </div>
                            </div>

                            <form method="POST"
                                action="{{ route('admin.import.pid.update', $project->id_project) }}">
                                @csrf
                                @method('PUT')

                                <div class="p-5 overflow-y-auto max-h-[65vh] space-y-5">

                                    <div>
                                        <h3 class="text-xs font-black text-gray-400 uppercase mb-3">
                                            Data PID
                                        </h3>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">PID</label>
                                                <input name="pid"
                                                    value="{{ $project->pid }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">PID SAP</label>
                                                <input name="pid_sap"
                                                    value="{{ $project->pid_sap }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">Program</label>
                                                <input name="program"
                                                    value="{{ $project->program }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div class="md:col-span-3">
                                                <label class="text-xs font-bold text-gray-500">Nama LOP</label>
                                                <input name="nama_lop"
                                                    value="{{ $project->project_name }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">Execution Type</label>
                                                <select name="execution_type"
                                                        class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                                    <option value="kemitraan" @selected($project->execution_type == 'kemitraan')>Kemitraan</option>
                                                    <option value="swakelola" @selected($project->execution_type == 'swakelola')>Swakelola</option>
                                                    <option value="turnkey" @selected($project->execution_type == 'turnkey')>Turnkey</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">Status Project</label>
                                                <select name="status_project"
                                                        class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                                    <option value="init" @selected($project->status_project == 'init')>Init</option>
                                                    <option value="active" @selected($project->status_project == 'active')>Active</option>
                                                    <option value="close" @selected($project->status_project == 'close')>Close</option>
                                                    <option value="bast" @selected($project->status_project == 'bast')>Bast</option>
                                                </select>
                                            </div>

                                        </div>
                                    </div>

                                    <div>
                                        <h3 class="text-xs font-black text-gray-400 uppercase mb-3">
                                            Data LOP
                                        </h3>

                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">ID IHLD</label>
                                                <input name="id_ihld"
                                                    value="{{ $project->lop?->id_ihld }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">Tematik</label>
                                                <input name="tematik"
                                                    value="{{ $project->lop?->tematik }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">STO</label>
                                                <input name="sto"
                                                    value="{{ $project->lop?->sto }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">Branch</label>
                                                <input name="branch"
                                                    value="{{ $project->lop?->branch }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">Batch</label>
                                                <input name="batch"
                                                    value="{{ $project->lop?->batch }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">No SP</label>
                                                <input name="no_sp"
                                                    value="{{ $project->lop?->no_sp }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">Tanggal SP</label>
                                                <input type="date"
                                                    name="tgl_sp"
                                                    value="{{ $project->lop?->tgl_sp }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">Tanggal TOC</label>
                                                <input type="date"
                                                    name="tgl_toc"
                                                    value="{{ $project->lop?->tgl_toc }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                            <div>
                                                <label class="text-xs font-bold text-gray-500">Mitra</label>
                                                <input name="mitra_name"
                                                    value="{{ $project->lop?->mitra_name }}"
                                                    class="mt-1 w-full rounded-xl border-gray-300 text-sm">
                                            </div>

                                        </div>
                                    </div>

                                </div>

                                <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 flex justify-end gap-2">
                                    <button type="button"
                                            onclick="closeDetailModal('editProject{{ $project->id_project }}')"
                                            class="h-10 px-5 rounded-xl border border-gray-300 text-sm font-bold">
                                        Batal
                                    </button>

                                    <button class="h-10 px-5 rounded-xl bg-yellow-500 text-white text-sm font-bold hover:bg-yellow-600">
                                        Simpan Perubahan
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>

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

<script>
    function openDetailModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeDetailModal(id) {
        const modal = document.getElementById(id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

@endsection