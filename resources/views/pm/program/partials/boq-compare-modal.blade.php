{{--
    Revisi (permintaan user): modal "Review BOQ" khusus role TIF -- pengganti
    tombol Tracking Progress yg dihapus utk role ini. Menampilkan
    perbandingan BOQ Plan vs BOQ Survey (per ronde 1, 2, dst -- kalau LOP
    belum pernah Survey/Re-Survey, kolom Survey tidak muncul sama sekali,
    tinggal Plan vs Actual) vs BOQ Actual, per item designator.

    Pola SAMA persis dgn pm.program.partials.detail-modal (Alpine reaktif,
    dibuka via $dispatch dari tombol di table.blade.php, data SUDAH
    di-render server-side per baris project -- bukan fetch AJAX) supaya
    konsisten dgn modal Detail Project yg sudah ada.
--}}
<div x-data="boqCompareModal()"
     x-on:open-boq-compare.window="open($event.detail)"
     x-show="show"
     x-cloak
     x-transition.opacity
     @keydown.escape.window="close()"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">

    <div @click.outside="close()"
         class="bg-white dark:bg-slate-900 w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-[2rem] border border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col">

        <div class="bg-white dark:bg-slate-900 px-6 py-5 border-b border-slate-200 dark:border-slate-800 shrink-0">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Review BOQ</p>
                    <h2 class="text-lg md:text-xl font-black leading-snug break-words text-slate-900 dark:text-white" x-text="selected.lopName"></h2>
                    <p class="text-xs mt-1 text-slate-500 dark:text-slate-400">
                        <span x-text="selected.projectName"></span> ·
                        <span x-text="selected.pid"></span> ·
                        <span x-text="selected.pidSap"></span>
                    </p>
                </div>
                <button type="button"
                        @click="close()"
                        class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-300 text-xl shrink-0">
                    ×
                </button>
            </div>
        </div>

        <div class="overflow-y-auto">

            {{-- KARTU RINGKASAN --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 px-6 py-5 border-b border-slate-200 dark:border-slate-800">
                <div class="rounded-2xl bg-slate-50 dark:bg-slate-800 p-3">
                    <p class="text-[10px] uppercase text-slate-400 font-bold">Total Item</p>
                    <p class="text-lg font-black text-slate-900 dark:text-white mt-0.5" x-text="selected.items.length"></p>
                </div>
                <div class="rounded-2xl bg-blue-50 dark:bg-blue-900/20 p-3">
                    <p class="text-[10px] uppercase text-blue-600 dark:text-blue-300 font-bold">Total Plan</p>
                    <p class="text-lg font-black text-blue-700 dark:text-blue-300 mt-0.5" x-text="formatNum(totalPlan())"></p>
                </div>
                <div class="rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 p-3">
                    <p class="text-[10px] uppercase text-emerald-600 dark:text-emerald-300 font-bold">Total Actual</p>
                    <p class="text-lg font-black text-emerald-700 dark:text-emerald-300 mt-0.5" x-text="formatNum(totalActual())"></p>
                </div>
                <div class="rounded-2xl p-3" :class="selected.rounds.length ? 'bg-purple-50 dark:bg-purple-900/20' : 'bg-slate-50 dark:bg-slate-800'">
                    <p class="text-[10px] uppercase font-bold" :class="selected.rounds.length ? 'text-purple-600 dark:text-purple-300' : 'text-slate-400'">Ronde Survey</p>
                    <template x-if="selected.rounds.length">
                        <p class="text-lg font-black text-purple-700 dark:text-purple-300 mt-0.5">
                            <span x-text="selected.rounds.length"></span> ronde
                            <span class="text-xs font-bold" x-show="selected.deviationPercent !== null" x-text="'· deviasi ' + Number(selected.deviationPercent).toFixed(1) + '%'"></span>
                        </p>
                    </template>
                    <template x-if="!selected.rounds.length">
                        <p class="text-sm font-bold text-slate-400 mt-1.5">Belum ada Survey</p>
                    </template>
                </div>
            </div>

            <div class="px-6 pt-4">
                <p class="text-xs text-slate-500 dark:text-slate-400" x-show="!selected.rounds.length">
                    ℹ️ LOP ini belum pernah melalui BOQ Survey/Re-Survey -- tabel di bawah hanya membandingkan <strong>Plan</strong> vs <strong>Actual</strong>.
                </p>
            </div>

            <div class="overflow-x-auto px-6 py-4">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800">
                        <tr>
                            <th class="text-left px-4 py-3 text-xs font-black uppercase text-slate-500 rounded-l-xl">Designator</th>
                            <th class="text-left px-4 py-3 text-xs font-black uppercase text-slate-500">Item Pekerjaan</th>
                            <th class="text-left px-4 py-3 text-xs font-black uppercase text-slate-500">Satuan</th>
                            <th class="text-right px-4 py-3 text-xs font-black uppercase text-blue-600 dark:text-blue-300">Plan</th>
                            <template x-for="(rn, ri) in selected.rounds" :key="'th-round-'+rn">
                                <th class="text-right px-4 py-3 text-xs font-black uppercase text-purple-600 dark:text-purple-300" x-text="'Survey R' + rn"></th>
                            </template>
                            <th class="text-right px-4 py-3 text-xs font-black uppercase text-emerald-600 dark:text-emerald-300">Actual</th>
                            <th class="text-center px-4 py-3 text-xs font-black uppercase text-slate-500 rounded-r-xl">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                        <template x-for="(item, idx) in selected.items" :key="idx">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-white" x-text="item.designator"></td>
                                <td class="px-4 py-3 font-bold text-slate-900 dark:text-white" x-text="item.item_name"></td>
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400" x-text="item.unit"></td>
                                <td class="px-4 py-3 text-right font-bold text-blue-700 dark:text-blue-300" x-text="formatNum(item.plan)"></td>
                                <template x-for="(rn, ri) in selected.rounds" :key="'td-round-'+idx+'-'+rn">
                                    <td class="px-4 py-3 text-right font-bold text-purple-700 dark:text-purple-300"
                                        x-text="item.survey[ri] !== null && item.survey[ri] !== undefined ? formatNum(item.survey[ri]) : '-'"></td>
                                </template>
                                <td class="px-4 py-3 text-right font-bold text-emerald-700 dark:text-emerald-300"
                                    x-text="item.actual !== null ? formatNum(item.actual) : '-'"></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-black"
                                          :class="statusBadgeClass(item)"
                                          x-text="statusLabel(item)"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="selected.items.length === 0">
                            <td class="px-4 py-8 text-center text-slate-400 text-sm" :colspan="4 + selected.rounds.length + 2">
                                Belum ada item BOQ untuk LOP ini.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="px-6 pb-5">
                <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-400">
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Actual ≥ Referensi (Sesuai/Lebih)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Actual &lt; Referensi (Kurang)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span> Belum Ada Data Actual</span>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
            <button type="button" @click="close()" class="h-11 px-6 rounded-2xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-black">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function boqCompareModal() {
        return {
            show: false,
            selected: {
                projectName: '-',
                pid: '-',
                pidSap: '-',
                lopName: '-',
                rounds: [],
                deviationPercent: null,
                items: [],
            },

            open(data) {
                this.selected = {
                    ...this.selected,
                    ...data,
                    rounds: Array.isArray(data.rounds) ? data.rounds : [],
                    items: Array.isArray(data.items) ? data.items : [],
                    deviationPercent: data.deviationPercent ?? null,
                };
                this.show = true;
                document.body.classList.add('overflow-hidden');
            },

            close() {
                this.show = false;
                document.body.classList.remove('overflow-hidden');
            },

            formatNum(val) {
                if (val === null || val === undefined) return '-';
                const num = Number(val);
                return num.toLocaleString('id-ID', { maximumFractionDigits: 2 });
            },

            totalPlan() {
                // Revisi (permintaan user): Total Plan/Total Survey cuma
                // menghitung item designator MATERIAL (is_material dari server,
                // konvensi sama dgn WaspangController::materialBoqItems),
                // item Jasa dikecualikan dari perhitungan total ini.
                return this.selected.items
                    .filter((item) => item.is_material)
                    .reduce((sum, item) => sum + (Number(item.plan) || 0), 0);
            },

            totalActual() {
                return this.selected.items.reduce((sum, item) => sum + (Number(item.actual) || 0), 0);
            },

            referenceFor(item) {
                // Referensi pembanding Actual: prioritas Survey RONDE TERAKHIR kalau
                // ada, fallback ke Plan -- sama dgn logic compare_qty di
                // review-boq.blade.php (Section AR/AQ).
                if (item.survey && item.survey.length) {
                    for (let i = item.survey.length - 1; i >= 0; i--) {
                        if (item.survey[i] !== null && item.survey[i] !== undefined) {
                            return Number(item.survey[i]);
                        }
                    }
                }
                return Number(item.plan) || 0;
            },

            statusLabel(item) {
                if (item.actual === null || item.actual === undefined) return 'Belum Ada Data';
                return Number(item.actual) >= this.referenceFor(item) ? 'Sesuai/Lebih' : 'Kurang';
            },

            statusBadgeClass(item) {
                if (item.actual === null || item.actual === undefined) return 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400';
                return Number(item.actual) >= this.referenceFor(item)
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                    : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
            },
        };
    }
</script>
