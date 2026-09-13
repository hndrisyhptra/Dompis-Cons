{{--
    Partial "Durasi per Tahap" AGREGAT (permintaan user, REVISI tampilan +
    breakdown lengkap) -- rata-rata durasi tiap staging (Inisiasi s.d.
    Golive), breakdown Region/Branch/Program. Sumber data:
    lop_stage_histories yang diisi otomatis lewat Lop::advanceStage()
    setiap kali status_progress LOP berubah (lihat Section BE
    ANALISA_REFACTOR_PERSIAPAN.md). Cuma menghitung histori yang SUDAH
    SELESAI (completed_at tidak null) -- lihat catatan lengkap di
    DashboardPmController::buildStageDurationCube().

    BEDA dgn "Durasi per Tahap" di halaman Timeline (per-LOP, satu project)
    -- partial ini agregat lintas LOP/portofolio, dipakai utk lihat tahap
    mana yang paling sering jadi bottleneck secara keseluruhan.

    REVISI (permintaan user):
    1. Breakdown SEKARANG lengkap 1:1 dgn seluruh kode project_stages alur
       normal (10 kode, lihat ProjectStage::scopeSequential()) TERMASUK
       sub-step "Persiapan" (Inisiasi/Survey/Perizinan/Material Delivery)
       yang SEBELUMNYA digabung jadi 1 kolom. Toggle "Detail Sub-Tahap
       Persiapan" (default OFF, compact -- persis spt tampilan lama) utk
       memilih mode ringkas (7 kolom, Persiapan digabung) vs lengkap (10
       kolom, semua sub-step tampil terpisah) -- data di server SUDAH
       selalu lengkap (buildStageDurationCube()), penggabungan cuma di
       sisi tampilan (client, Alpine) supaya tidak overwhelming secara
       default tapi tetap bisa dilihat detail 1 klik.
    2. Tampilan dibuat lebih modern/informatif: kartu ringkasan KPI (rata2
       total siklus, tahap tercepat, tahap bottleneck/terlama, jumlah data
       tercatat) + visualisasi bar horizontal per tahap (proporsional
       thdp tahap terlama) SEBELUM tabel breakdown Region/Branch, supaya
       user bisa langsung "melihat" pola tanpa harus baca angka di tabel.

    Dipakai oleh 2 halaman FULL PAGE (menu khusus role admin/superadmin/
    officer/PM, sama spt "Report Deployment"):
    - resources/views/admin/stage_duration_report.blade.php
    - resources/views/pm/stage_duration_report.blade.php

    Variabel yang WAJIB dikirim lewat @include():
    - $durationCube : array cube dari DashboardPmController::buildStageDurationCube()
    - $stageMeta    : array metadata tahap (code/label/phase_group/sequence/color)
                      dari DashboardPmController::stageDurationMeta() -- 1 sumber
                      kebenaran urutan & warna tahap (SAMA dgn project_stages).

    CATATAN: histori baru mulai dicatat sejak fitur ini aktif -- LOP yang
    sudah lama & sempat melewati tahap2 SEBELUM fitur ini ada TIDAK punya
    data durasi utk tahap2 tsb (tidak ikut dihitung rata-rata, BUKAN
    dianggap 0 hari). Data akan makin lengkap seiring waktu berjalan.
--}}
<div x-data="stageDurationWidget()" x-init="init()">

    {{-- ============================================================
         KARTU RINGKASAN KPI
         ============================================================ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Rata-rata Siklus</p>
            <p class="text-2xl font-black text-gray-800 dark:text-gray-100 mt-1">
                <span x-text="kpi().totalDays"></span><span class="text-xs font-bold text-gray-400 ml-1">hari</span>
            </p>
            <p class="text-[10px] text-gray-400 mt-1">Inisiasi &rarr; Golive (akumulasi rata2 semua tahap)</p>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Tahap Tercepat</p>
            <p class="text-lg font-black text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-1.5">
                <i class="fa-solid fa-bolt text-xs"></i>
                <span x-text="kpi().fastest ? kpi().fastest.label : '-'"></span>
            </p>
            <p class="text-[10px] text-gray-400 mt-1">
                <span x-text="kpi().fastest ? kpi().fastest.days + ' hari rata-rata' : 'Belum ada data'"></span>
            </p>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Tahap Terlama (Bottleneck)</p>
            <p class="text-lg font-black text-rose-600 dark:text-rose-400 mt-1 flex items-center gap-1.5">
                <i class="fa-solid fa-triangle-exclamation text-xs"></i>
                <span x-text="kpi().slowest ? kpi().slowest.label : '-'"></span>
            </p>
            <p class="text-[10px] text-gray-400 mt-1">
                <span x-text="kpi().slowest ? kpi().slowest.days + ' hari rata-rata' : 'Belum ada data'"></span>
            </p>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Data Tercatat</p>
            <p class="text-2xl font-black text-gray-800 dark:text-gray-100 mt-1">
                <span x-text="kpi().maxCount"></span><span class="text-xs font-bold text-gray-400 ml-1">LOP</span>
            </p>
            <p class="text-[10px] text-gray-400 mt-1">Jumlah histori tahap selesai terbanyak (1 tahap)</p>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/50">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-gray-800 dark:text-gray-200">Durasi per Tahap</h2>
                    <p class="text-xs text-gray-400 mt-1">Rata-rata durasi LOP menyelesaikan tiap staging (angka dalam kurung = jumlah LOP yang sudah menyelesaikan tahap tsb). Klik nama Region untuk detail per Branch.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select x-model="filterRegion"
                            @change="if (!branchOptions().includes(filterBranch)) filterBranch = ''"
                            class="text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Region</option>
                        <template x-for="r in regions()" :key="'region-'+r">
                            <option :value="r" x-text="r"></option>
                        </template>
                    </select>

                    <select x-model="filterBranch"
                            class="text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Branch</option>
                        <template x-for="b in branchOptions()" :key="'branch-'+b">
                            <option :value="b" x-text="b"></option>
                        </template>
                    </select>

                    <select x-model="filterProgram"
                            class="text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Program</option>
                        <template x-for="p in programs()" :key="'program-'+p">
                            <option :value="p" x-text="p === 'EKSBIS' ? 'Eksbis' : p"></option>
                        </template>
                    </select>

                    <button type="button" @click="resetFilters()"
                            x-show="filterRegion || filterBranch || filterProgram"
                            class="text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 px-2 py-2">
                        Reset
                    </button>
                </div>
            </div>

            {{-- TOGGLE DETAIL SUB-TAHAP PERSIAPAN (permintaan user) --}}
            <div class="mt-4 flex items-center justify-between flex-wrap gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <button type="button" role="switch" :aria-checked="showDetail.toString()" @click="showDetail = !showDetail"
                            class="relative w-10 h-[22px] rounded-full transition-colors"
                            :class="showDetail ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-700'">
                        <span class="absolute top-0.5 left-0.5 w-[18px] h-[18px] bg-white rounded-full shadow transition-transform"
                              :class="showDetail ? 'translate-x-[18px]' : 'translate-x-0'"></span>
                    </button>
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-300">Detail Sub-Tahap Persiapan</span>
                </label>
                <p class="text-[11px] text-gray-400" x-show="showDetail">
                    Menampilkan Inisiasi, Survey, Perizinan &amp; Material Delivery secara terpisah.
                </p>
                <p class="text-[11px] text-gray-400" x-show="!showDetail">
                    Mode ringkas -- klik toggle utk lihat breakdown lengkap tiap sub-tahap Persiapan.
                </p>
            </div>
        </div>

        {{-- ============================================================
             VISUALISASI BAR HORIZONTAL PER TAHAP (ringkasan, mudah dibaca)
             ============================================================ --}}
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800">
            <template x-if="overviewStages().length === 0">
                <p class="text-xs text-gray-400 text-center py-6">Belum ada data durasi utk ditampilkan.</p>
            </template>

            <div class="space-y-3">
                <template x-for="(stage, idx) in overviewStages()" :key="'ov-'+stage.code">
                    <div>
                        <template x-if="stage.isGroupHeader">
                            <p class="text-[10px] font-black uppercase tracking-wider text-gray-400 mt-2 mb-1" x-text="stage.groupLabel"></p>
                        </template>
                        <template x-if="!stage.isGroupHeader">
                            <div class="flex items-center gap-3">
                                <div class="w-36 shrink-0 text-right">
                                    <span class="text-xs font-bold text-gray-600 dark:text-gray-300" :class="stage.isSub ? 'pl-3' : ''" x-text="(stage.isSub ? '↳ ' : '') + stage.label"></span>
                                </div>
                                <div class="flex-1 h-6 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden relative group">
                                    <div class="h-full rounded-lg transition-all duration-500"
                                         :class="colorClasses(stage.color).bar"
                                         :style="'width: ' + barWidthPct(stage) + '%'">
                                    </div>
                                </div>
                                <div class="w-24 shrink-0 text-right">
                                    <span class="text-xs font-black text-gray-700 dark:text-gray-200" x-text="stat(grandTotal(), stage).days"></span>
                                    <span class="text-[10px] text-gray-400 block" x-text="stat(grandTotal(), stage).count + ' LOP'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- ============================================================
             TABEL BREAKDOWN REGION / BRANCH
             ============================================================ --}}
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gray-100/60 dark:bg-gray-950/60 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-6 py-3 text-left whitespace-nowrap">Breakdown (Region / Branch)</th>
                        <template x-for="stage in tableStages()" :key="'th-'+stage.code">
                            <th class="px-3 py-3 text-center whitespace-nowrap" :class="colorClasses(stage.color).text">
                                <span x-show="stage.isSub" class="text-gray-300 dark:text-gray-600 font-normal">Persiapan ·</span>
                                <span x-text="stage.isSub ? stage.subLabel : stage.label"></span>
                            </th>
                        </template>
                    </tr>
                </thead>

                <template x-if="groupedRows().length === 0">
                    <tbody>
                        <tr>
                            <td :colspan="tableStages().length + 1" class="px-6 py-10 text-center text-gray-400">
                                Belum ada data durasi (histori baru mulai tercatat sejak fitur ini aktif).
                            </td>
                        </tr>
                    </tbody>
                </template>

                <template x-for="reg in groupedRows()" :key="reg.region">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr class="cursor-pointer bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition" @click="toggle(reg.region)">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-5 h-5 flex items-center justify-center rounded bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200" :class="expanded[reg.region] ? 'rotate-90' : ''"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                    <span class="font-black text-gray-800 dark:text-gray-200 text-sm whitespace-nowrap" x-text="reg.region"></span>
                                </div>
                            </td>
                            <template x-for="stage in tableStages()" :key="'reg-'+reg.region+'-'+stage.code">
                                <td class="px-3 py-4 text-center">
                                    <span class="inline-flex flex-col items-center px-3 py-1 rounded-lg font-black whitespace-nowrap" :class="colorClasses(stage.color).badge">
                                        <span x-text="stat(reg, stage).days"></span>
                                        <span class="text-[9px] font-bold opacity-70" x-text="stat(reg, stage).count + ' LOP'"></span>
                                    </span>
                                </td>
                            </template>
                        </tr>

                        <template x-for="br in reg.branches" :key="reg.region + '-' + br.name">
                            <tr class="bg-gray-50/50 dark:bg-gray-950/50 hover:bg-gray-100/50 transition" x-show="expanded[reg.region]">
                                <td class="px-6 py-3 pl-[3.25rem]">
                                    <span class="font-bold text-gray-600 dark:text-gray-400 whitespace-nowrap" x-text="'• ' + br.name"></span>
                                </td>
                                <template x-for="stage in tableStages()" :key="'br-'+reg.region+'-'+br.name+'-'+stage.code">
                                    <td class="px-3 py-3 text-center">
                                        <span class="font-bold whitespace-nowrap" :class="colorClasses(stage.color).text" x-text="stat(br, stage).days"></span>
                                        <span class="text-[9px] text-gray-400 block" x-text="stat(br, stage).count + ' LOP'"></span>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </template>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/50" x-show="groupedRows().length > 0">
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs">
                <span class="font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">Grand Total:</span>
                <template x-for="stage in tableStages()" :key="'gt-'+stage.code">
                    <span class="font-bold" :class="colorClasses(stage.color).text">
                        <span x-text="stage.isSub ? stage.subLabel : stage.label"></span>
                        <span x-text="stat(grandTotal(), stage).days"></span>
                    </span>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
    function stageDurationWidget() {
        return {
            cube: @json($durationCube ?? []),
            stageMeta: @json($stageMeta ?? []),
            filterRegion: '',
            filterBranch: '',
            filterProgram: '',
            expanded: {},
            showDetail: false,

            init() {
                // no-op hook (dipertahankan supaya x-init konsisten dgn pola
                // widget Alpine lain di app ini, tempat menaruh inisialisasi
                // di masa depan bila dibutuhkan).
            },

            bucketKeys() {
                return this.stageMeta.map((s) => s.code);
            },

            // Palet warna 1:1 dgn Project::stageColorClasses() (PHP) --
            // supaya konsisten dgn warna tahap yang sudah dipakai di
            // Timeline/stepper LOP, TIDAK di-duplikasi jadi definisi baru.
            colorClasses(color) {
                const map = {
                    slate: { bar: 'bg-slate-400 dark:bg-slate-500', badge: 'bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300', text: 'text-slate-600 dark:text-slate-400' },
                    amber: { bar: 'bg-amber-400 dark:bg-amber-500', badge: 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400', text: 'text-amber-600 dark:text-amber-400' },
                    blue: { bar: 'bg-blue-500 dark:bg-blue-500', badge: 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-400', text: 'text-blue-600 dark:text-blue-400' },
                    indigo: { bar: 'bg-indigo-500 dark:bg-indigo-500', badge: 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400', text: 'text-indigo-600 dark:text-indigo-400' },
                    emerald: { bar: 'bg-emerald-500 dark:bg-emerald-500', badge: 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400', text: 'text-emerald-600 dark:text-emerald-400' },
                    purple: { bar: 'bg-purple-500 dark:bg-purple-500', badge: 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-400', text: 'text-purple-600 dark:text-purple-400' },
                    green: { bar: 'bg-green-500 dark:bg-green-500', badge: 'bg-green-50 dark:bg-green-950/40 text-green-700 dark:text-green-400', text: 'text-green-600 dark:text-green-400' },
                };

                return map[color] || { bar: 'bg-gray-400', badge: 'bg-gray-50 dark:bg-gray-800/60 text-gray-700 dark:text-gray-300', text: 'text-gray-600 dark:text-gray-400' };
            },

            // Daftar "tahap tampilan" -- kalau showDetail true, 1:1 dgn
            // stageMeta (semua sub-step Persiapan terpisah). Kalau false,
            // sub-step phase_group 'persiapan' digabung jadi 1 baris
            // "Persiapan" (subCodes = gabungan 4 kode), tahap lain tetap
            // 1:1. Dipakai bersama oleh visualisasi bar & tabel supaya
            // konsisten (1 sumber definisi tampilan).
            displayStages() {
                if (this.showDetail) {
                    return this.stageMeta.map((s) => ({
                        code: s.code,
                        label: s.label,
                        subLabel: s.label,
                        color: s.color,
                        subCodes: [s.code],
                        isSub: s.phase_group === 'persiapan',
                        isGroupHeader: false,
                    }));
                }

                const rows = [];
                let persiapanAdded = false;
                for (const s of this.stageMeta) {
                    if (s.phase_group === 'persiapan') {
                        if (persiapanAdded) continue;
                        persiapanAdded = true;
                        const subCodes = this.stageMeta.filter((x) => x.phase_group === 'persiapan').map((x) => x.code);
                        rows.push({ code: 'persiapan', label: 'Persiapan', subLabel: 'Persiapan', color: 'slate', subCodes, isSub: false, isGroupHeader: false });
                    } else {
                        rows.push({ code: s.code, label: s.label, subLabel: s.label, color: s.color, subCodes: [s.code], isSub: false, isGroupHeader: false });
                    }
                }

                return rows;
            },

            tableStages() {
                return this.displayStages();
            },

            // Sama dgn displayStages() tapi menyisipkan 1 "baris header"
            // penanda group sebelum sub-step Persiapan pertama saat mode
            // detail aktif, supaya bar-chart ringkasan jelas mana yang
            // sub-tahap dari Persiapan.
            overviewStages() {
                if (!this.showDetail) {
                    return this.displayStages();
                }

                const rows = [];
                let groupMarked = false;
                for (const row of this.displayStages()) {
                    if (row.isSub && !groupMarked) {
                        rows.push({ code: '__persiapan_group__', isGroupHeader: true, groupLabel: 'Sub-Tahap Persiapan', label: '', color: 'slate', subCodes: [] });
                        groupMarked = true;
                    }
                    rows.push(row);
                }

                return rows;
            },

            regions() {
                return [...new Set(this.cube.map((r) => r.region))].sort();
            },

            branchOptions() {
                return [...new Set(
                    this.cube
                        .filter((r) => !this.filterRegion || r.region === this.filterRegion)
                        .map((r) => r.branch)
                )].sort();
            },

            programs() {
                return [...new Set(this.cube.map((r) => r.program))].sort();
            },

            resetFilters() {
                this.filterRegion = '';
                this.filterBranch = '';
                this.filterProgram = '';
            },

            filteredCube() {
                return this.cube.filter((r) =>
                    (!this.filterRegion || r.region === this.filterRegion) &&
                    (!this.filterBranch || r.branch === this.filterBranch) &&
                    (!this.filterProgram || r.program === this.filterProgram)
                );
            },

            groupedRows() {
                const keys = this.bucketKeys();
                const regionsMap = {};

                for (const row of this.filteredCube()) {
                    if (!regionsMap[row.region]) {
                        const base = { region: row.region, branches: {} };
                        keys.forEach((k) => { base[k] = { sum_seconds: 0, count: 0 }; });
                        regionsMap[row.region] = base;
                    }
                    const rg = regionsMap[row.region];
                    keys.forEach((k) => {
                        rg[k].sum_seconds += row[k].sum_seconds;
                        rg[k].count += row[k].count;
                    });

                    if (!rg.branches[row.branch]) {
                        const b = { name: row.branch };
                        keys.forEach((k) => { b[k] = { sum_seconds: 0, count: 0 }; });
                        rg.branches[row.branch] = b;
                    }
                    const br = rg.branches[row.branch];
                    keys.forEach((k) => {
                        br[k].sum_seconds += row[k].sum_seconds;
                        br[k].count += row[k].count;
                    });
                }

                return Object.values(regionsMap)
                    .map((rg) => ({ ...rg, branches: Object.values(rg.branches).sort((a, b) => a.name.localeCompare(b.name)) }))
                    .sort((a, b) => a.region.localeCompare(b.region));
            },

            grandTotal() {
                const keys = this.bucketKeys();
                const gt = {};
                keys.forEach((k) => { gt[k] = { sum_seconds: 0, count: 0 }; });

                for (const row of this.filteredCube()) {
                    keys.forEach((k) => {
                        gt[k].sum_seconds += row[k].sum_seconds;
                        gt[k].count += row[k].count;
                    });
                }

                return gt;
            },

            // Jumlahkan sum_seconds/count dari seluruh subCodes 1 baris
            // tampilan (row bisa berupa hasil groupedRows()/grandTotal(),
            // yang key-nya per stage_code MENTAH) -- lalu return { days,
            // count } siap tampil. Dipakai SERAGAM oleh visualisasi bar &
            // tabel supaya "Persiapan" (gabungan) dan sub-step individual
            // selalu dihitung dgn cara yang sama (weighted, bukan rata2-
            // dari-rata2).
            stat(row, stage) {
                let sum = 0;
                let count = 0;
                for (const code of stage.subCodes) {
                    const s = row[code];
                    if (s) {
                        sum += s.sum_seconds;
                        count += s.count;
                    }
                }

                if (count === 0) {
                    return { days: '-', count: 0, avg: null };
                }

                const days = sum / count / 86400;

                return { days: days.toFixed(1), count, avg: days };
            },

            avgDaysValue(stage) {
                const s = this.stat(this.grandTotal(), stage);
                return s.avg;
            },

            barWidthPct(stage) {
                if (stage.isGroupHeader) return 0;
                const s = this.stat(this.grandTotal(), stage);
                if (s.avg === null) return 0;

                const maxDays = Math.max(
                    ...this.overviewStages()
                        .filter((s2) => !s2.isGroupHeader)
                        .map((s2) => this.avgDaysValue(s2))
                        .filter((v) => v !== null),
                    0.01
                );

                return Math.max(4, Math.round((s.avg / maxDays) * 100));
            },

            // KPI ringkasan atas: total siklus (akumulasi rata2 SEMUA
            // tahap individual -- SELALU dari stageMeta mentah, TIDAK
            // dipengaruhi toggle showDetail, supaya angka "Inisiasi ->
            // Golive" konsisten & tidak berubah saat toggle diklik),
            // tahap tercepat/terlama (dari yg SEDANG ditampilkan/
            // displayStages(), supaya konsisten dgn apa yg user lihat di
            // bar chart & tabel), & jumlah data terbanyak.
            kpi() {
                const gt = this.grandTotal();
                let totalDays = 0;
                for (const meta of this.stageMeta) {
                    const s = this.stat(gt, { subCodes: [meta.code] });
                    if (s.avg !== null) totalDays += s.avg;
                }

                const candidates = this.displayStages()
                    .map((stage) => ({ label: stage.isSub ? 'Persiapan · ' + stage.subLabel : stage.label, days: this.stat(gt, stage) }))
                    .filter((c) => c.days.avg !== null)
                    .map((c) => ({ label: c.label, days: c.days.days, avg: c.days.avg, count: c.days.count }));

                let fastest = null;
                let slowest = null;
                let maxCount = 0;

                for (const c of candidates) {
                    if (!fastest || c.avg < fastest.avg) fastest = c;
                    if (!slowest || c.avg > slowest.avg) slowest = c;
                    if (c.count > maxCount) maxCount = c.count;
                }

                return {
                    totalDays: totalDays.toFixed(1),
                    fastest,
                    slowest,
                    maxCount,
                };
            },

            toggle(region) {
                this.expanded[region] = !this.expanded[region];
            },
        };
    }
</script>
