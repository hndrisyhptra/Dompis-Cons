<div x-data="reportDeploymentPt2Widget('{{ route($matrixDetailRoute) }}')">

    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/50">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-gray-800 dark:text-gray-200">Report Deployment PT 2</h2>
                    <p class="text-xs text-gray-400 mt-1">Klik nama Region untuk detail per Branch. Klik angka untuk melihat daftar LOP.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <select x-model="stageFilterRegion"
                            @change="if (!stageBranchOptions().includes(stageFilterBranch)) stageFilterBranch = ''"
                            class="text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Region</option>
                        <template x-for="r in stageRegions()" :key="'region-'+r">
                            <option :value="r" x-text="r"></option>
                        </template>
                    </select>

                    <select x-model="stageFilterBranch"
                            class="text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Branch</option>
                        <template x-for="b in stageBranchOptions()" :key="'branch-'+b">
                            <option :value="b" x-text="b"></option>
                        </template>
                    </select>

                    <button type="button" @click="stageResetFilters()"
                            x-show="stageFilterRegion || stageFilterBranch"
                            class="text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 px-2 py-2">
                        Reset
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gray-100/60 dark:bg-gray-950/60 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-6 py-3 text-left whitespace-nowrap">Breakdown (Region / Branch)</th>
                        <th class="px-3 py-3 text-center text-rose-600 dark:text-rose-400 whitespace-nowrap">Drop</th>
                        <th class="px-3 py-3 text-center text-slate-500 dark:text-slate-400 whitespace-nowrap">Inisiasi</th>
                        <th class="px-3 py-3 text-center text-amber-600 dark:text-amber-400 whitespace-nowrap">Survey</th>
                        <th class="px-3 py-3 text-center text-blue-600 dark:text-blue-400 whitespace-nowrap">Instalasi</th>
                        <th class="px-3 py-3 text-center text-indigo-600 dark:text-indigo-400 whitespace-nowrap">Finishing</th>
                        <th class="px-3 py-3 text-center text-purple-600 dark:text-purple-400 whitespace-nowrap">FI-OGP Golive</th>
                        <th class="px-3 py-3 text-center text-emerald-600 dark:text-emerald-400 whitespace-nowrap">Golive</th>
                        <th class="px-6 py-3 text-center text-gray-700 dark:text-gray-300 whitespace-nowrap">Grand Total</th>
                    </tr>
                </thead>

                <template x-for="reg in stageGroupedRows()" :key="reg.region">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr class="cursor-pointer bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition" @click="stageToggle(reg.region)">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-5 h-5 flex items-center justify-center rounded bg-blue-100 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="transition-transform duration-200" :class="stageExpanded[reg.region] ? 'rotate-90' : ''"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                    <span class="font-black text-gray-800 dark:text-gray-200 text-sm whitespace-nowrap" x-text="reg.region"></span>
                                </div>
                            </td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 font-black hover:bg-rose-100" @click.stop="stageShow(reg.region, '', 'drop')" x-text="reg.drop"></span></td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-slate-50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 font-black hover:bg-slate-100" @click.stop="stageShow(reg.region, '', 'inisiasi')" x-text="reg.inisiasi"></span></td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-400 font-black hover:bg-amber-100" @click.stop="stageShow(reg.region, '', 'survey')" x-text="reg.survey"></span></td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 font-black hover:bg-blue-100" @click.stop="stageShow(reg.region, '', 'instalasi')" x-text="reg.instalasi"></span></td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 font-black hover:bg-indigo-100" @click.stop="stageShow(reg.region, '', 'finishing')" x-text="reg.finishing"></span></td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-400 font-black hover:bg-purple-100" @click.stop="stageShow(reg.region, '', 'fi_ogp_golive')" x-text="reg.fi_ogp_golive"></span></td>
                            <td class="px-3 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 font-black hover:bg-emerald-100" @click.stop="stageShow(reg.region, '', 'golive')" x-text="reg.golive"></span></td>
                            <td class="px-6 py-4 text-center"><span class="cursor-pointer px-3 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 font-black hover:bg-gray-200" @click.stop="stageShow(reg.region, '', 'total')" x-text="reg.total"></span></td>
                        </tr>

                        <template x-for="br in reg.branches" :key="reg.region + '-' + br.name">
                            <tr class="bg-gray-50/50 dark:bg-gray-950/50 hover:bg-gray-100/50 transition" x-show="stageExpanded[reg.region]">
                                <td class="px-6 py-3 pl-[3.25rem]">
                                    <span class="font-bold text-gray-600 dark:text-gray-400 whitespace-nowrap" x-text="'• ' + br.name"></span>
                                </td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-rose-600 dark:text-rose-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'drop')" x-text="br.drop"></span></td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-slate-600 dark:text-slate-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'inisiasi')" x-text="br.inisiasi"></span></td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-amber-600 dark:text-amber-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'survey')" x-text="br.survey"></span></td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-blue-600 dark:text-blue-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'instalasi')" x-text="br.instalasi"></span></td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-indigo-600 dark:text-indigo-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'finishing')" x-text="br.finishing"></span></td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-purple-600 dark:text-purple-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'fi_ogp_golive')" x-text="br.fi_ogp_golive"></span></td>
                                <td class="px-3 py-3 text-center"><span class="cursor-pointer text-emerald-600 dark:text-emerald-400 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'golive')" x-text="br.golive"></span></td>
                                <td class="px-6 py-3 text-center"><span class="cursor-pointer text-gray-700 dark:text-gray-300 font-bold hover:underline" @click.stop="stageShow(reg.region, br.name, 'total')" x-text="br.total"></span></td>
                            </tr>
                        </template>
                    </tbody>
                </template>

                <tbody x-show="stageGroupedRows().length === 0">
                    <tr>
                        <td colspan="9" class="px-6 py-10 text-center text-gray-400 font-medium">Tidak ada data statistik tersedia.</td>
                    </tr>
                </tbody>

                <tfoot x-show="stageGroupedRows().length > 0">
                    <tr class="bg-gray-100/80 dark:bg-gray-950/80 border-t-2 border-gray-300 dark:border-gray-700">
                        <td class="px-6 py-4 font-black text-gray-900 dark:text-white uppercase text-xs tracking-wide">Grand Total</td>
                        <td class="px-3 py-4 text-center font-black text-rose-700 dark:text-rose-400" x-text="stageGrandTotal().drop"></td>
                        <td class="px-3 py-4 text-center font-black text-slate-700 dark:text-slate-300" x-text="stageGrandTotal().inisiasi"></td>
                        <td class="px-3 py-4 text-center font-black text-amber-700 dark:text-amber-400" x-text="stageGrandTotal().survey"></td>
                        <td class="px-3 py-4 text-center font-black text-blue-700 dark:text-blue-400" x-text="stageGrandTotal().instalasi"></td>
                        <td class="px-3 py-4 text-center font-black text-indigo-700 dark:text-indigo-400" x-text="stageGrandTotal().finishing"></td>
                        <td class="px-3 py-4 text-center font-black text-purple-700 dark:text-purple-400" x-text="stageGrandTotal().fi_ogp_golive"></td>
                        <td class="px-3 py-4 text-center font-black text-emerald-700 dark:text-emerald-400" x-text="stageGrandTotal().golive"></td>
                        <td class="px-6 py-4 text-center font-black text-gray-900 dark:text-white" x-text="stageGrandTotal().total"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- MODAL DETAIL LOP (KLIK ANGKA) -- sama pola dgn modal di
         partials/report-deployment.blade.php (PT3), duplikasi di sini
         karena widget ini x-data root sendiri. --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div class="absolute inset-0 bg-black/50" @click="close()"></div>

        <div class="relative bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[85vh] flex flex-col overflow-hidden border border-gray-200 dark:border-gray-800">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-4 bg-gray-50/50 dark:bg-gray-950/50">
                <div class="min-w-0">
                    <h3 class="text-sm font-black text-gray-800 dark:text-gray-200 truncate" x-text="title"></h3>
                    <p class="text-xs text-gray-400 mt-0.5"><span x-text="count"></span> LOP</p>
                </div>
                <button type="button" @click="close()" class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-300 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <div class="overflow-y-auto p-4">
                <div x-show="loading" class="text-center text-sm text-gray-400 py-10">Memuat data...</div>
                <div x-show="error" x-text="error" class="text-center text-sm text-rose-500 py-10"></div>

                <div x-show="!loading && !error" class="overflow-x-auto">
                    <table class="w-full text-xs border-collapse">
                        <thead class="bg-gray-100/60 dark:bg-gray-950/60 text-gray-400 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-3 py-2.5 text-left">No</th>
                                <th class="px-3 py-2.5 text-left">PID</th>
                                <th class="px-3 py-2.5 text-left">Project</th>
                                <th class="px-3 py-2.5 text-left">LOP</th>
                                <th class="px-3 py-2.5 text-left">Branch</th>
                                <th class="px-3 py-2.5 text-left">STO</th>
                                <th class="px-3 py-2.5 text-left">Program</th>
                                <th class="px-3 py-2.5 text-left">Status</th>
                                <th class="px-3 py-2.5 text-left">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <template x-for="row in rows" :key="row.no">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-3 py-2.5 text-gray-400" x-text="row.no"></td>
                                    <td class="px-3 py-2.5 font-bold text-gray-700 dark:text-gray-300" x-text="row.pid"></td>
                                    <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400" x-text="row.project_name"></td>
                                    <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400" x-text="row.lop_name"></td>
                                    <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400" x-text="row.branch"></td>
                                    <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400" x-text="row.sto"></td>
                                    <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400" x-text="row.program"></td>
                                    <td class="px-3 py-2.5 text-gray-600 dark:text-gray-400" x-text="row.status_label"></td>
                                    <td class="px-3 py-2.5">
                                        <a :href="row.detail_url" class="text-blue-600 dark:text-blue-400 font-bold hover:underline">Lihat</a>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="!loading && rows.length === 0">
                                <td colspan="9" class="px-3 py-8 text-center text-gray-400">Tidak ada data.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function reportDeploymentPt2Widget(matrixDetailUrl) {
        return {
            // Pola sama dgn reportDeploymentWidget() (PT3) -- @@json() SENGAJA
            // ditaruh di dalam <script>, BUKAN di atribut HTML x-data="...",
            // krn tanda kutip literal hasil @@json() akan memutus atribut HTML
            // kalau ditaruh langsung di x-data (lihat catatan FIX di
            // reportDeploymentWidget() pada partials/report-deployment.blade.php).
            stageCube: @json($pt2StageCube ?? []),
            stageFilterRegion: '',
            stageFilterBranch: '',
            stageExpanded: {},

            open: false,
            loading: false,
            error: '',
            title: '',
            rows: [],
            count: 0,

            stageRegions() {
                return [...new Set(this.stageCube.map((r) => r.region))].sort();
            },

            stageBranchOptions() {
                return [...new Set(
                    this.stageCube
                        .filter((r) => !this.stageFilterRegion || r.region === this.stageFilterRegion)
                        .map((r) => r.branch)
                )].sort();
            },

            stageResetFilters() {
                this.stageFilterRegion = '';
                this.stageFilterBranch = '';
            },

            stageFilteredCube() {
                return this.stageCube.filter((r) =>
                    (!this.stageFilterRegion || r.region === this.stageFilterRegion) &&
                    (!this.stageFilterBranch || r.branch === this.stageFilterBranch)
                );
            },

            stageGroupedRows() {
                const keys = ['drop', 'inisiasi', 'survey', 'instalasi', 'finishing', 'fi_ogp_golive', 'golive'];
                const regionsMap = {};

                for (const row of this.stageFilteredCube()) {
                    if (!regionsMap[row.region]) {
                        const base = { region: row.region, total: 0, branches: {} };
                        keys.forEach((k) => { base[k] = 0; });
                        regionsMap[row.region] = base;
                    }
                    const rg = regionsMap[row.region];
                    keys.forEach((k) => { rg[k] += row[k]; });
                    rg.total += row.total;

                    if (!rg.branches[row.branch]) {
                        const b = { name: row.branch, total: 0 };
                        keys.forEach((k) => { b[k] = 0; });
                        rg.branches[row.branch] = b;
                    }
                    const br = rg.branches[row.branch];
                    keys.forEach((k) => { br[k] += row[k]; });
                    br.total += row.total;
                }

                return Object.values(regionsMap)
                    .map((rg) => ({ ...rg, branches: Object.values(rg.branches).sort((a, b) => a.name.localeCompare(b.name)) }))
                    .sort((a, b) => a.region.localeCompare(b.region));
            },

            stageGrandTotal() {
                const keys = ['drop', 'inisiasi', 'survey', 'instalasi', 'finishing', 'fi_ogp_golive', 'golive'];
                const gt = { total: 0 };
                keys.forEach((k) => { gt[k] = 0; });

                for (const row of this.stageFilteredCube()) {
                    keys.forEach((k) => { gt[k] += row[k]; });
                    gt.total += row.total;
                }

                return gt;
            },

            stageToggle(region) {
                this.stageExpanded[region] = !this.stageExpanded[region];
            },

            stageShow(region, branch, metric) {
                this.show({
                    type: 'stage_breakdown_pt2',
                    region: region || '',
                    branch: branch || '',
                    metric: metric,
                });
            },

            async show(params) {
                this.open = true;
                this.loading = true;
                this.error = '';
                this.rows = [];
                this.title = '';
                this.count = 0;

                const query = new URLSearchParams({
                    type: params.type || '',
                    region: params.region || '',
                    branch: params.branch || '',
                    metric: params.metric || '',
                }).toString();

                try {
                    const res = await fetch(`${matrixDetailUrl}?${query}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    const data = await res.json();

                    if (!res.ok) {
                        this.error = data.message || 'Gagal memuat data LOP.';
                    } else {
                        this.title = data.title;
                        this.rows = data.rows || [];
                        this.count = data.count || 0;
                    }
                } catch (e) {
                    this.error = 'Terjadi kesalahan saat memuat data LOP.';
                } finally {
                    this.loading = false;
                }
            },

            close() {
                this.open = false;
            },
        };
    }
</script>
