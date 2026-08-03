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
                                    <option value="drop">Drop</option>
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