<div x-show="showDetail"
     x-cloak
     x-transition.opacity
     @keydown.escape.window="close()"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">

    <div @click.away="close()"
         class="bg-white dark:bg-slate-900 w-full max-w-3xl max-h-[90vh] overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800 shadow-2xl">

        <div class="border-b border-slate-200 bg-white px-6 py-5 text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Detail Data PID & LOP</p>
                    <h2 class="text-lg md:text-xl font-black leading-snug break-words" x-text="selected.project_name"></h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        <span x-text="selected.pid"></span> · <span x-text="selected.pid_sap"></span>
                    </p>
                </div>

                <button type="button"
                        @click="close()"
                        class="h-10 w-10 shrink-0 rounded-lg border border-slate-200 bg-white text-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white"
                        aria-label="Tutup modal detail PID">
                    ×
                </button>
            </div>
        </div>

        <div class="p-5 overflow-y-auto max-h-[68vh] space-y-5">

            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase mb-3">Data Project</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <template x-for="field in projectFields" :key="field.label">
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-3">
                            <p class="text-[10px] font-black text-slate-400 uppercase" x-text="field.label"></p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white mt-1 break-words" x-text="field.value"></p>
                        </div>
                    </template>
                </div>
            </div>

            <div>
                <h3 class="text-xs font-black text-slate-400 uppercase mb-3">Data LOP</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <template x-for="field in lopFields" :key="field.label">
                        <div class="rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 p-3">
                            <p class="text-[10px] font-black text-slate-400 uppercase" x-text="field.label"></p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white mt-1 break-words" x-text="field.value"></p>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900">
            <button type="button"
                    @click="close()"
                    class="h-11 rounded-lg border border-slate-300 bg-white px-5 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                Tutup
            </button>

            <button type="button"
                    @click="showDetail = false; showEdit = true"
                    class="h-11 rounded-lg bg-slate-900 px-5 text-sm font-black text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                Edit Data
            </button>
        </div>
    </div>
</div>

<div x-show="showEdit"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">

        <div @click.away="close()"
             class="bg-white dark:bg-slate-900 w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-lg border border-slate-200 dark:border-slate-800 shadow-2xl">

            <div class="border-b border-slate-200 bg-white px-6 py-5 text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-white">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                            Edit Data PID & LOP
                        </p>

                        <h2 class="text-lg md:text-xl font-black" x-text="selected.project_name"></h2>
                    </div>

                    <button type="button"
                            @click="close()"
                            class="h-10 w-10 rounded-lg border border-slate-200 bg-white text-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white"
                            aria-label="Tutup modal edit PID">
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
                                <input name="pid" x-model="selected.pid" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">PID SAP</label>
                                <input name="pid_sap" x-model="selected.pid_sap" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Program</label>
                                <input name="program" x-model="selected.program" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div class="md:col-span-3">
                                <label class="text-xs font-black text-slate-500">Nama LOP</label>
                                <input name="nama_lop" x-model="selected.project_name" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Execution Type</label>
                                <select name="execution_type" x-model="selected.execution_type" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    <option value="kemitraan">Kemitraan</option>
                                    <option value="swakelola">Swakelola</option>
                                    <option value="turnkey">Turnkey</option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Status Progress</label>
                                <select name="status_progress" x-model="selected.status_progress" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
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
                                <input name="id_ihld" x-model="selected.id_ihld" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Tematik</label>
                                <input name="tematik" x-model="selected.tematik" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">STO</label>
                                <input name="sto" x-model="selected.sto" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Branch</label>
                                <input name="branch" x-model="selected.branch" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Batch</label>
                                <input name="batch" x-model="selected.batch" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">No SP</label>
                                <input name="no_sp" x-model="selected.no_sp" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Tanggal SP</label>
                                <input type="date" name="tgl_sp" x-model="selected.tgl_sp" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Tanggal TOC</label>
                                <input type="date" name="tgl_toc" x-model="selected.tgl_toc" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>

                            <div>
                                <label class="text-xs font-black text-slate-500">Mitra</label>
                                <input name="mitra_name" x-model="selected.mitra_name" class="mt-1 w-full rounded-lg border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="flex justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900">
                    <button type="button"
                            @click="close()"
                            class="h-11 rounded-lg border border-slate-300 bg-white px-5 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        Batal
                    </button>

                    <button class="h-11 rounded-lg bg-slate-900 px-5 text-sm font-black text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
