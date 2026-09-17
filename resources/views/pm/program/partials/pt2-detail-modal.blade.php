{{--
    Modal "Detail LOP" utk halaman Project ID > PT 2 (role TIF/PM,
    permintaan user 2026-09-17) -- clone dari pm/program/partials/
    detail-modal.blade.php (Regular), tapi:
    - Field "Program"/"Execution Type" dihapus (tidak relevan utk PT2).
    - Tombol footer "Tracking Progress" DIHAPUS -- belum ada route
      tracking PT2 yang aman diakses pm/tif (admin.pt2.tracking dibatasi
      role admin/superadmin/super_tif/officer), diganti tombol "Tutup" saja.
--}}
<div x-show="show"
     x-cloak
     x-transition.opacity
     @keydown.escape.window="close()"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">

    <div @click.outside="close()"
         class="bg-white dark:bg-slate-900 w-full max-w-4xl max-h-[90vh] overflow-hidden rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col">

        <div class="bg-gradient-to-br from-indigo-700 to-blue-700 px-6 py-5 text-white shrink-0">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-bold opacity-80">Detail LOP PT 2</p>
                    <h2 class="text-lg md:text-xl font-black leading-snug break-words" x-text="selected.projectName"></h2>
                    <p class="text-xs mt-1 opacity-90">
                        <span x-text="selected.pid"></span> ·
                        <span x-text="selected.pidSap"></span> ·
                        <span x-text="selected.lopName"></span>
                    </p>
                </div>

                <button type="button"
                        @click="close()"
                        class="w-10 h-10 rounded-2xl bg-white/20 hover:bg-white/30 text-white text-xl shrink-0">
                    ×
                </button>
            </div>
        </div>

        <div class="overflow-y-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 px-6 py-5 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <p class="text-xs uppercase text-slate-400 font-bold">Branch</p>
                    <p class="text-sm font-black text-slate-900 dark:text-white mt-1" x-text="selected.branch"></p>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-400 font-bold">STO</p>
                    <p class="text-sm font-black text-slate-900 dark:text-white mt-1" x-text="selected.sto"></p>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-400 font-bold">ID IHLD</p>
                    <p class="text-sm font-black text-slate-900 dark:text-white mt-1" x-text="selected.idIhld"></p>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-400 font-bold">Nama LOP</p>
                    <p class="text-sm font-black text-slate-900 dark:text-white mt-1" x-text="selected.lopName"></p>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-400 font-bold">Mitra</p>
                    <p class="text-sm font-black text-slate-900 dark:text-white mt-1" x-text="selected.mitra"></p>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-400 font-bold">Status Progress</p>
                    <span class="inline-flex mt-1 px-3 py-1 rounded-full text-xs font-black"
                          :class="selected.statusBadgeClass"
                          x-text="selected.statusLabel"></span>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-400 font-bold">Tahapan</p>
                    <span class="inline-flex mt-1 px-3 py-1 rounded-full text-xs font-black"
                          :class="selected.stageBadgeClass"
                          x-text="selected.stageLabel + ' · ' + selected.progress + '%'"></span>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-400 font-bold">Ditugaskan Kepada</p>
                    <p class="text-sm font-black text-slate-900 dark:text-white mt-1">
                        <span x-text="selected.assignedName || 'Belum diassign'"></span>
                    </p>
                </div>
            </div>

            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
                <h3 class="text-sm font-black text-slate-900 dark:text-white">Item Designator (BOQ)</h3>
                <p class="text-xs text-slate-500 mt-1">Total <span x-text="selected.items.length"></span> item · read-only</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-black uppercase text-slate-500">Designator</th>
                            <th class="text-left px-6 py-3 text-xs font-black uppercase text-slate-500">Item Pekerjaan</th>
                            <th class="text-left px-6 py-3 text-xs font-black uppercase text-slate-500">Satuan</th>
                            <th class="text-right px-6 py-3 text-xs font-black uppercase text-slate-500">Plan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <template x-for="(item, idx) in selected.items" :key="idx">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-6 py-3 font-semibold text-slate-900 dark:text-white" x-text="item.designator"></td>
                                <td class="px-6 py-3 font-bold text-slate-900 dark:text-white" x-text="item.item_name"></td>
                                <td class="px-6 py-3 text-slate-600 dark:text-slate-400" x-text="item.unit"></td>
                                <td class="px-6 py-3 text-right font-bold text-slate-900 dark:text-white" x-text="item.quantity_plan"></td>
                            </tr>
                        </template>
                        <tr x-show="selected.items.length === 0">
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400 text-sm">Belum ada item designator.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
            <button type="button"
                    @click="close()"
                    class="h-11 px-6 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-white text-sm font-black inline-flex items-center gap-2">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function pmPt2DetailModal() {
        return {
            show: false,
            selected: {
                projectName: '-',
                pid: '-',
                pidSap: '-',
                branch: '-',
                sto: '-',
                idIhld: '-',
                lopName: '-',
                mitra: '-',
                statusLabel: '-',
                statusBadgeClass: 'bg-slate-100 text-slate-600',
                stageLabel: '-',
                stageBadgeClass: 'bg-slate-100 text-slate-600',
                progress: 0,
                assignedName: '',
                items: [],
            },

            open(data) {
                this.selected = {
                    ...this.selected,
                    ...data,
                    items: Array.isArray(data.items) ? data.items : [],
                };

                this.show = true;
                document.body.classList.add('overflow-hidden');
            },

            close() {
                this.show = false;
                document.body.classList.remove('overflow-hidden');
            },
        };
    }
</script>
