@extends('layouts.admin')

@section('content')

<div x-data="pidPage()" class="min-h-screen bg-slate-50 dark:bg-slate-950 -m-4 md:-m-6 p-4 md:p-6">

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="rounded-[2rem] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <p class="text-xs font-black text-blue-700 uppercase tracking-widest">
                        PID Monitoring
                    </p>

                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white mt-1">
                        Data PID
                    </h1>

                    <p class="text-xs text-slate-500 mt-2 max-w-2xl">
                        Daftar data PID dan LOP hasil bulk import. Gunakan halaman ini untuk review, edit, dan validasi data project.
                    </p>
                </div>

                <a href="{{ route('admin.import.pid') }}"
                   class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-blue-700 text-white text-sm font-black hover:bg-blue-800 shadow-lg shadow-blue-700/20">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-cloud-upload-icon lucide-cloud-upload">
                            <path d="M12 13v8"/><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="m8 17 4-4 4 4"/>
                        </svg>
                    </span>
                    <span>Bulk Import PID</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">

            {{-- TOTAL PID --}}
            <div class="rounded-3xl bg-white border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase">
                            Total PID
                        </p>

                        <p class="text-3xl font-black text-slate-900 mt-2">
                            {{ number_format($totalPid) }}
                        </p>

                        <p class="text-xs text-slate-500 mt-1">
                            Semua project yang masuk
                        </p>
                    </div>

                    <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-sliders-icon lucide-file-sliders"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                            <path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M8 12h8"/><path d="M10 11v2"/><path d="M8 17h8"/><path d="M14 16v2"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- TOTAL LOP --}}
            <div class="rounded-3xl bg-white border border-slate-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500 font-bold uppercase">
                            Total LOP
                        </p>

                        <p class="text-3xl font-black text-slate-900 mt-2">
                            {{ number_format($totalLop) }}
                        </p>

                        <p class="text-xs text-slate-500 mt-1">
                            LOP hasil upload PID
                        </p>
                    </div>

                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-spreadsheet-icon lucide-file-spreadsheet"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/>
                            <path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M8 13h2"/><path d="M14 13h2"/><path d="M8 17h2"/><path d="M14 17h2"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- SUDAH ADA BOQ --}}
            <div class="rounded-3xl bg-white border border-emerald-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-emerald-700 font-bold uppercase">
                            Sudah Ada BOQ
                        </p>

                        <p class="text-3xl font-black text-emerald-700 mt-2">
                            {{ number_format($sudahAdaBoq) }}
                        </p>

                        <p class="text-xs text-slate-500 mt-1">
                            Siap untuk assignment
                        </p>
                    </div>

                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-badge-check-icon lucide-badge-check">
                            <path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- BELUM ADA BOQ --}}
            <div class="rounded-3xl bg-white border border-amber-200 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-amber-700 font-bold uppercase">
                            Belum Ada BOQ
                        </p>

                        <p class="text-3xl font-black text-amber-700 mt-2">
                            {{ number_format($belumAdaBoq) }}
                        </p>

                        <p class="text-xs text-slate-500 mt-1">
                            Menunggu upload BOQ
                        </p>
                    </div>

                    <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center text-2xl">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-hourglass-icon lucide-hourglass"><path d="M5 22h14"/>
                            <path d="M5 2h14"/><path d="M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22"/><path d="M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/>
                        </svg>
                    </div>
                </div>
            </div>

        </div>
        </div>

        {{-- FILTER --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 p-5 shadow-sm">
            <form method="GET"
                  action="{{ route('admin.data-pid') }}"
                  class="flex flex-col lg:flex-row gap-3">

                <div class="flex-1">
                    <label class="block text-xs font-black text-slate-500 uppercase mb-2">
                        Pencarian
                    </label>

                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            🔎
                        </span>

                        <input type="text"
                               name="search"
                               value="{{ $search ?? '' }}"
                               placeholder="Cari PID, PID SAP, Nama LOP, Program..."
                               class="w-full h-12 rounded-2xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white text-sm pl-11 pr-4">
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <button class="h-12 px-6 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-black hover:opacity-90">
                        Cari
                    </button>

                    @if(!empty($search))
                        <a href="{{ route('admin.data-pid') }}"
                           class="h-12 px-5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-black inline-flex items-center justify-center">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">

            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-slate-900 dark:text-white">
                        List Data PID
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Menampilkan data project terbaru.
                    </p>
                </div>

                <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-black">
                    {{ $projects->total() }} data
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">PID</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">PID SAP</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">Nama LOP</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">STO / Branch</th>
                            <th class="px-5 py-4 text-left text-xs font-black text-slate-500 uppercase">Program</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">Status</th>
                            <th class="px-5 py-4 text-center text-xs font-black text-slate-500 uppercase">Aksi</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        @forelse($projects as $project)
                            @php
                                $lop = $project->lop;

                                $detailData = [
                                    'id_project' => $project->id_project,
                                    'pid' => $project->pid ?? '-',
                                    'pid_sap' => $project->pid_sap ?? '-',
                                    'project_name' => $project->project_name ?? '-',
                                    'program' => $project->program ?? '-',
                                    'execution_type' => $project->execution_type ?? '-',
                                    'status_project' => $project->status_project ?? '-',
                                    'id_ihld' => $lop?->id_ihld ?? '-',
                                    'lop_name' => $lop?->lop_name ?? $project->project_name ?? '-',
                                    'sto' => $lop?->sto ?? '-',
                                    'branch' => $lop?->branch ?? '-',
                                    'tematik' => $lop?->tematik ?? '-',
                                    'batch' => $lop?->batch ?? '-',
                                    'mitra_name' => $lop?->mitra_name ?? '-',
                                    'no_sp' => $lop?->no_sp ?? '-',
                                    'tgl_sp' => $lop?->tgl_sp ?? '-',
                                    'tgl_toc' => $lop?->tgl_toc ?? '-',
                                    'update_url' => route('admin.import.pid.update', $project->id_project),
                                    'delete_url' => route('admin.import.pid.delete', $project->id_project),
                                ];
                            @endphp

                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-black text-slate-900 dark:text-white">
                                        {{ $project->pid ?? '-' }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        ID {{ $project->id_project }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full bg-blue-50 text-blue-700 text-xs font-black">
                                        {{ $project->pid_sap ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 min-w-[260px]">
                                    <p class="font-black text-slate-900 dark:text-white truncate max-w-sm">
                                        {{ $project->project_name ?? '-' }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-1">
                                        IHLD: {{ $lop?->id_ihld ?? '-' }} · Mitra: {{ $lop?->mitra_name ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <p class="font-bold text-slate-900 dark:text-white">
                                        {{ $lop?->sto ?? '-' }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $lop?->branch ?? '-' }}
                                    </p>
                                </td>

                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-black">
                                        {{ $project->program ?? '-' }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    @php
                                        $status = strtolower($project->status_project ?? 'active');
                                    @endphp

                                    <span class="px-3 py-1.5 rounded-full text-xs font-black
                                        {{ $status === 'active' ? 'bg-emerald-50 text-emerald-700' : '' }}
                                        {{ $status === 'init' ? 'bg-blue-50 text-blue-700' : '' }}
                                        {{ $status === 'close' ? 'bg-slate-100 text-slate-700' : '' }}
                                        {{ $status === 'bast' ? 'bg-amber-50 text-amber-700' : '' }}
                                        {{ !in_array($status, ['active','init','close','bast']) ? 'bg-slate-100 text-slate-700' : '' }}">
                                        {{ strtoupper($project->status_project ?? '-') }}
                                    </span>
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <div class="relative inline-block text-left" x-data="{ openMenu: false }">
                                        <button type="button"
                                                @click="openMenu = !openMenu"
                                                class="w-10 h-10 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 font-black">
                                            ⋮
                                        </button>

                                        <div x-show="openMenu"
                                             @click.away="openMenu = false"
                                             x-cloak
                                             class="absolute right-0 mt-2 w-44 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl z-20 overflow-hidden">

                                            <button type="button"
                                                    @click="openMenu = false; openDetail(@js($detailData))"
                                                    class="w-full text-left px-4 py-3 text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                                                Detail
                                            </button>

                                            <button type="button"
                                                    @click="openMenu = false; openEdit(@js($detailData))"
                                                    class="w-full text-left px-4 py-3 text-sm font-bold text-amber-700 hover:bg-amber-50">
                                                Edit
                                            </button>

                                            <form action="{{ route('admin.import.pid.delete', $project->id_project) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Yakin hapus project ini? Semua BOQ dan assignment akan ikut terhapus.')">
                                                @csrf
                                                @method('DELETE')

                                                <button class="w-full text-left px-4 py-3 text-sm font-bold text-red-700 hover:bg-red-50">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center">
                                    <div class="w-16 h-16 rounded-3xl bg-slate-100 mx-auto flex items-center justify-center text-2xl mb-4">
                                        📋
                                    </div>

                                    <p class="text-sm font-black text-slate-700 dark:text-slate-200">
                                        Belum ada data PID
                                    </p>

                                    <p class="text-xs text-slate-500 mt-1">
                                        Silakan import PID terlebih dahulu.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if ($projects->hasPages())
                <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800">
                    {{ $projects->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- DETAIL MODAL --}}
    <div x-show="showDetail"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">

        <div @click.away="close()"
             class="bg-white dark:bg-slate-900 w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-2xl">

            <div class="bg-slate-900 px-6 py-5 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-bold opacity-70">
                            Detail Data PID
                        </p>

                        <h2 class="text-lg md:text-xl font-black leading-snug break-words" x-text="selected.project_name"></h2>

                        <p class="text-xs mt-1 opacity-80">
                            <span x-text="selected.pid"></span>
                            ·
                            <span x-text="selected.pid_sap"></span>
                        </p>
                    </div>

                    <button type="button"
                            @click="close()"
                            class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 text-white text-xl">
                        ×
                    </button>
                </div>
            </div>

            <div class="p-5 overflow-y-auto max-h-[72vh] space-y-5">

                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase mb-3">
                        Data Project
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <template x-for="item in projectFields" :key="item.label">
                            <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-4">
                                <p class="text-xs text-slate-500 font-bold" x-text="item.label"></p>
                                <p class="font-black text-slate-900 dark:text-white mt-1 break-words" x-text="item.value"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-black text-slate-400 uppercase mb-3">
                        Data LOP
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <template x-for="item in lopFields" :key="item.label">
                            <div class="rounded-3xl bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 p-4">
                                <p class="text-xs text-slate-500 font-bold" x-text="item.label"></p>
                                <p class="font-black text-slate-900 dark:text-white mt-1 break-words" x-text="item.value"></p>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex justify-end">
                <button type="button"
                        @click="close()"
                        class="h-11 px-5 rounded-2xl bg-slate-900 text-white text-sm font-black hover:bg-slate-800">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div x-show="showEdit"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">

        <div @click.away="close()"
             class="bg-white dark:bg-slate-900 w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-2xl">

            <div class="bg-amber-500 px-6 py-5 text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold opacity-90">
                            Edit Data PID & LOP
                        </p>

                        <h2 class="text-lg md:text-xl font-black" x-text="selected.project_name"></h2>
                    </div>

                    <button type="button"
                            @click="close()"
                            class="w-10 h-10 rounded-2xl bg-white/20 hover:bg-white/30 text-white text-xl">
                        ×
                    </button>
                </div>
            </div>

            <form method="POST" :action="selected.update_url">
                @csrf
                @method('PUT')

                <div class="p-5 overflow-y-auto max-h-[68vh] space-y-5">

                    <div>
                        <h3 class="text-xs font-black text-slate-400 uppercase mb-3">
                            Data Project
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-black text-slate-500">PID</label>
                                <input name="pid" x-model="selected.pid" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">PID SAP</label>
                                <input name="pid_sap" x-model="selected.pid_sap" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Program</label>
                                <input name="program" x-model="selected.program" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div class="md:col-span-3">
                                <label class="text-xs font-black text-slate-500">Nama LOP</label>
                                <input name="nama_lop" x-model="selected.project_name" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Execution Type</label>
                                <select name="execution_type" x-model="selected.execution_type" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                                    <option value="kemitraan">Kemitraan</option>
                                    <option value="swakelola">Swakelola</option>
                                    <option value="turnkey">Turnkey</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Status Project</label>
                                <select name="status_project" x-model="selected.status_project" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                                    <option value="init">Init</option>
                                    <option value="active">Active</option>
                                    <option value="close">Close</option>
                                    <option value="bast">Bast</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-xs font-black text-slate-400 uppercase mb-3">
                            Data LOP
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="text-xs font-black text-slate-500">ID IHLD</label>
                                <input name="id_ihld" x-model="selected.id_ihld" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Tematik</label>
                                <input name="tematik" x-model="selected.tematik" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">STO</label>
                                <input name="sto" x-model="selected.sto" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Branch</label>
                                <input name="branch" x-model="selected.branch" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Batch</label>
                                <input name="batch" x-model="selected.batch" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">No SP</label>
                                <input name="no_sp" x-model="selected.no_sp" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Tanggal SP</label>
                                <input type="date" name="tgl_sp" x-model="selected.tgl_sp" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Tanggal TOC</label>
                                <input type="date" name="tgl_toc" x-model="selected.tgl_toc" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Mitra</label>
                                <input name="mitra_name" x-model="selected.mitra_name" class="mt-1 w-full rounded-2xl border-slate-300 text-sm">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex justify-end gap-2">
                    <button type="button"
                            @click="close()"
                            class="h-11 px-5 rounded-2xl bg-white border border-slate-300 text-slate-700 text-sm font-black">
                        Batal
                    </button>

                    <button class="h-11 px-5 rounded-2xl bg-amber-500 text-white text-sm font-black hover:bg-amber-600">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
    function pidPage() {
        return {
            showDetail: false,
            showEdit: false,

            selected: {},

            get projectFields() {
                return [
                    { label: 'PID', value: this.selected.pid || '-' },
                    { label: 'PID SAP', value: this.selected.pid_sap || '-' },
                    { label: 'Program', value: this.selected.program || '-' },
                    { label: 'Execution Type', value: this.selected.execution_type || '-' },
                    { label: 'Status Project', value: this.selected.status_project || '-' },
                ];
            },

            get lopFields() {
                return [
                    { label: 'Nama LOP', value: this.selected.lop_name || '-' },
                    { label: 'ID IHLD', value: this.selected.id_ihld || '-' },
                    { label: 'STO', value: this.selected.sto || '-' },
                    { label: 'Branch', value: this.selected.branch || '-' },
                    { label: 'Tematik', value: this.selected.tematik || '-' },
                    { label: 'Batch', value: this.selected.batch || '-' },
                    { label: 'Mitra', value: this.selected.mitra_name || '-' },
                    { label: 'No SP', value: this.selected.no_sp || '-' },
                    { label: 'Tanggal SP', value: this.selected.tgl_sp || '-' },
                    { label: 'Tanggal TOC', value: this.selected.tgl_toc || '-' },
                ];
            },

            openDetail(data) {
                this.selected = data;
                this.showDetail = true;
                document.body.classList.add('overflow-hidden');
            },

            openEdit(data) {
                this.selected = data;
                this.showEdit = true;
                document.body.classList.add('overflow-hidden');
            },

            close() {
                this.showDetail = false;
                this.showEdit = false;
                document.body.classList.remove('overflow-hidden');
            },
        }
    }
</script>

@endsection