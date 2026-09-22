@php
    $summary = $summaryData ?? [
        'date' => now()->toDateString(),
        'date_label' => now()->translatedFormat('l, d F Y'),
        'is_today' => true,
        'previous_date' => now()->subDay()->toDateString(),
        'next_date' => null,
        'scope' => 'PT 3 / Reguler',
        'kpis' => [],
        'branches' => [],
        'stage_groups' => [],
        'latest_recorded_activity_label' => '-',
        'data_quality' => [],
    ];
@endphp

<style>[x-cloak] { display: none !important; }</style>

<div x-data="deploymentMovementSummary()" x-init="init()" class="space-y-5">
    <div class="rounded-lg border border-blue-200 dark:border-blue-900/70 bg-blue-50/70 dark:bg-blue-950/30 p-4">
        <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18"/><path d="m15 6 6 6-6 6"/><path d="M3 6h6"/><path d="M3 18h6"/></svg>
            </div>
            <div>
                <h2 class="text-sm font-black text-blue-900 dark:text-blue-100">Pergerakan harian {{ $summary['scope'] }}</h2>
                <p class="mt-1 text-xs leading-5 text-blue-700 dark:text-blue-300">
                    Satu LOP dihitung bergerak jika memiliki minimal satu aktivitas operasional yang tercatat pada tanggal terpilih. Klik Branch untuk melihat LOP, Aktor, dan Rincian Aktivitasnya.
                </p>
                <p class="mt-1 text-[11px] text-blue-600/80 dark:text-blue-400/80">
                    PT2 belum digabung karena actor Survey/Dismantle/Mancore belum tercatat lengkap. Log sinkronisasi/backfill otomatis juga tidak dihitung sebagai pergerakan pekerjaan.
                </p>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[11px] font-black uppercase tracking-widest text-gray-400">Tanggal Monitoring</p>
                <h2 class="mt-1 text-lg font-black text-gray-900 dark:text-white">{{ $summary['date_label'] }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Aktivitas terakhir sampai tanggal terpilih: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $summary['latest_recorded_activity_label'] }}</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a :href="summaryUrl('{{ $summary['previous_date'] }}')"
                   class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 px-3 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                    Hari sebelumnya
                </a>

                @if($summary['next_date'])
                    <a :href="summaryUrl('{{ $summary['next_date'] }}')"
                       class="inline-flex h-10 items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 px-3 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                        Hari berikutnya
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                @endif

                @unless($summary['is_today'])
                    <a :href="summaryUrl('{{ now()->toDateString() }}')"
                       class="inline-flex h-10 items-center rounded-lg bg-blue-600 px-3 text-xs font-bold text-white hover:bg-blue-700">
                        Kembali ke hari ini
                    </a>
                @endunless

                <form method="GET" action="{{ route($reportRouteName) }}" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="summary">
                    <input type="hidden" name="view" :value="detailMode">
                    <input type="date" name="date" value="{{ $summary['date'] }}" max="{{ now()->toDateString() }}"
                           class="h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-xs font-semibold text-gray-700 dark:text-gray-200">
                    <button type="submit" class="h-10 rounded-lg bg-gray-900 dark:bg-gray-100 px-3 text-xs font-bold text-white dark:text-gray-900">Tampilkan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-6">
        <div class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4">
            <p class="text-[10px] font-black uppercase tracking-wider text-gray-400">Total Branch</p>
            <p class="mt-2 text-2xl font-black text-gray-900 dark:text-white">{{ number_format($summary['kpis']['total_branches'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-emerald-200 dark:border-emerald-900/70 bg-white dark:bg-gray-900 p-4">
            <p class="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Branch Bergerak</p>
            <p class="mt-2 text-2xl font-black text-emerald-700 dark:text-emerald-300">{{ number_format($summary['kpis']['active_branches'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-rose-200 dark:border-rose-900/70 bg-white dark:bg-gray-900 p-4">
            <p class="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">Tanpa Pergerakan</p>
            <p class="mt-2 text-2xl font-black text-rose-700 dark:text-rose-300">{{ number_format($summary['kpis']['inactive_branches'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-blue-200 dark:border-blue-900/70 bg-white dark:bg-gray-900 p-4">
            <p class="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400">LOP Bergerak</p>
            <p class="mt-2 text-2xl font-black text-blue-700 dark:text-blue-300">{{ number_format($summary['kpis']['moved_lops'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-violet-200 dark:border-violet-900/70 bg-white dark:bg-gray-900 p-4">
            <p class="text-[10px] font-black uppercase tracking-wider text-black-600 dark:text-black-400">Total Aktivitas</p>
            <p class="mt-2 text-2xl font-black text-black-700 dark:text-black-300">{{ number_format($summary['kpis']['activity_count'] ?? 0) }}</p>
        </div>
        <div class="rounded-lg border border-amber-200 dark:border-amber-900/70 bg-white dark:bg-gray-900 p-4">
            <p class="text-[10px] font-black uppercase tracking-wider text-black-600 dark:text-black-400">Aktor Aktif</p>
            <p class="mt-2 text-2xl font-black text-black-700 dark:text-black-300">{{ number_format($summary['kpis']['active_actors'] ?? 0) }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-[10px] font-black uppercase tracking-widest text-gray-400">Mode Detail</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Gunakan tampilan Branch untuk mencari area pasif, atau Staging untuk melihat posisi Step dan Sub-step LOP per Branch.</p>
        </div>
        <div class="inline-flex rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
            <button type="button" @click="setDetailMode('branch')"
                    :class="detailMode === 'branch' ? activeFilterClass : inactiveFilterClass"
                    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-xs font-bold">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/></svg>
                Summary per Branch
            </button>
            <button type="button" @click="setDetailMode('staging')"
                    :class="detailMode === 'staging' ? activeFilterClass : inactiveFilterClass"
                    class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-xs font-bold">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/><circle cx="8" cy="6" r="2"/><circle cx="14" cy="12" r="2"/><circle cx="10" cy="18" r="2"/></svg>
                Detail per Staging
            </button>
        </div>
    </div>

    <div x-show="detailMode === 'branch'" x-cloak class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-800 p-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-gray-800 dark:text-gray-200">Summary per Branch</h2>
                    <p class="mt-1 text-xs text-gray-400">Area tanpa pergerakan diletakkan paling atas agar cepat terlihat.</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <div class="inline-flex rounded-lg bg-gray-100 dark:bg-gray-800 p-1">
                        <button type="button" @click="movementFilter = 'all'" :class="movementFilter === 'all' ? activeFilterClass : inactiveFilterClass" class="rounded-lg px-3 py-1.5 text-xs font-bold">Semua</button>
                        <button type="button" @click="movementFilter = 'inactive'" :class="movementFilter === 'inactive' ? activeFilterClass : inactiveFilterClass" class="rounded-lg px-3 py-1.5 text-xs font-bold">Tidak Bergerak</button>
                        <button type="button" @click="movementFilter = 'active'" :class="movementFilter === 'active' ? activeFilterClass : inactiveFilterClass" class="rounded-lg px-3 py-1.5 text-xs font-bold">Bergerak</button>
                    </div>

                    <select x-model="regionFilter" class="h-10 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-xs font-semibold text-gray-700 dark:text-gray-200">
                        <option value="">All Region</option>
                        <template x-for="region in regions()" :key="region">
                            <option :value="region" x-text="region"></option>
                        </template>
                    </select>

                    <input type="search" x-model.debounce.250ms="search" placeholder="Cari Branch / LOP / PID..."
                           class="h-10 min-w-[220px] rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 text-xs text-gray-700 dark:text-gray-200 placeholder:text-gray-400">
                </div>
            </div>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            <template x-for="branch in filteredBranches()" :key="branch.branch">
                <section>
                    <button type="button"
                            @click="toggleBranch(branch.branch)"
                            class="w-full p-5 text-left transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(180px,1.5fr)_repeat(5,minmax(90px,0.7fr))_minmax(170px,1fr)_32px] lg:items-center">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-black text-gray-900 dark:text-white" x-text="branch.branch"></span>
                                    <span class="rounded-md px-2 py-0.5 text-[10px] font-black uppercase"
                                          :class="branch.movement_status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'"
                                          x-text="branch.movement_status === 'active' ? 'Bergerak' : 'Tidak bergerak'"></span>
                                </div>
                                <p class="mt-1 text-xs text-gray-400" x-text="branch.region"></p>
                            </div>
                            <div><p class="text-[10px] font-bold uppercase text-gray-400">Total LOP</p><p class="mt-1 font-black text-gray-800 dark:text-gray-200" x-text="branch.total_lops"></p></div>
                            <div><p class="text-[10px] font-bold uppercase text-gray-400">LOP Bergerak</p><p class="mt-1 font-black text-emerald-600" x-text="branch.moved_lops"></p></div>
                            <div><p class="text-[10px] font-bold uppercase text-gray-400">Belum Bergerak</p><p class="mt-1 font-black text-rose-600" x-text="branch.not_moved_lops"></p></div>
                            <div><p class="text-[10px] font-bold uppercase text-gray-400">Aktivitas</p><p class="mt-1 font-black text-black-600" x-text="branch.activity_count"></p></div>
                            <div><p class="text-[10px] font-bold uppercase text-gray-400">Aktor</p><p class="mt-1 font-black text-black-600" x-text="branch.actor_count"></p></div>
                            <div>
                                <p class="text-[10px] font-bold uppercase text-gray-400">Last Update</p>
                                <p class="mt-1 text-xs font-bold text-gray-700 dark:text-gray-300" x-text="branch.last_activity_label"></p>
                                <p x-show="branch.inactive_days !== null && branch.inactive_days > 0" class="mt-1 text-[10px] font-bold text-rose-500" x-text="branch.inactive_days + ' hari tanpa aktivitas sampai tanggal ini'"></p>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400 transition" :class="expandedBranches[branch.branch] ? 'rotate-180' : ''"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                    </button>

                    <div x-show="expandedBranches[branch.branch]" x-collapse x-cloak class="bg-gray-50/70 dark:bg-gray-950/40 px-5 pb-5">
                        <div x-show="branch.lops.length === 0" class="rounded-lg border border-dashed border-rose-200 dark:border-rose-900/60 bg-white dark:bg-gray-900 p-6 text-center">
                            <p class="font-bold text-rose-600 dark:text-rose-400">Tidak ada LOP yang memiliki aktivitas pada tanggal ini.</p>
                            <p class="mt-1 text-xs text-gray-400">Branch ini perlu menjadi perhatian untuk tindak lanjut harian.</p>
                        </div>

                        <div x-show="branch.lops.length > 0" class="grid gap-3 pt-3">
                            <template x-for="lop in branch.lops" :key="branch.branch + '-' + lop.lop_id">
                                <article class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
                                    <button type="button" @click="toggleLop(branch.branch, lop.lop_id)" class="w-full p-4 text-left hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(220px,1.5fr)_minmax(160px,1fr)_minmax(130px,0.8fr)_minmax(180px,1fr)_32px] md:items-center">
                                            <div>
                                                <p class="text-sm font-black text-gray-900 dark:text-white" x-text="lop.lop_name"></p>
                                                <p class="mt-1 text-xs text-gray-400"><span class="font-bold" x-text="lop.pid_sap"></span> · <span x-text="lop.project_name"></span></p>
                                            </div>
                                            <div><p class="text-[10px] font-bold uppercase text-gray-400">Program / STO</p><p class="mt-1 text-xs font-bold text-gray-700 dark:text-gray-300" x-text="lop.program + ' / ' + lop.sto"></p></div>
                                            <div><p class="text-[10px] font-bold uppercase text-gray-400">Status</p><p class="mt-1 text-xs font-bold text-blue-600 dark:text-blue-400" x-text="lop.status_label"></p></div>
                                            <div>
                                                <p class="text-[10px] font-bold uppercase text-gray-400">Last Update</p>
                                                <p class="mt-1 text-xs font-bold text-gray-700 dark:text-gray-300"><span x-text="lop.last_activity_time"></span> · <span x-text="lop.last_activity_title"></span></p>
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    <template x-for="actor in lop.actors" :key="actor.id || 'system'">
                                                        <span class="rounded-md bg-amber-50 dark:bg-amber-950/50 px-2 py-1 text-[10px] font-bold text-amber-700 dark:text-amber-300" x-text="actor.name"></span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1 text-xs font-black text-black-600">
                                                <span x-text="lop.activity_count"></span>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400 transition" :class="expandedLops[branch.branch + '-' + lop.lop_id] ? 'rotate-180' : ''"><path d="m6 9 6 6 6-6"/></svg>
                                            </div>
                                        </div>
                                    </button>

                                    <div x-show="expandedLops[branch.branch + '-' + lop.lop_id]" x-collapse x-cloak class="border-t border-gray-100 dark:border-gray-800 px-4 py-3">
                                        <div class="space-y-2">
                                            <template x-for="activity in lop.activities" :key="activity.id">
                                                <div class="flex items-start gap-3 rounded-lg bg-gray-50 dark:bg-gray-950/60 p-3">
                                                    <span class="w-12 shrink-0 text-xs font-black text-blue-600" x-text="activity.time"></span>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <p class="text-xs font-black text-gray-800 dark:text-gray-200" x-text="activity.title"></p>
                                                            <span class="rounded bg-violet-100 dark:bg-violet-950/60 px-1.5 py-0.5 text-[9px] font-black uppercase text-violet-700 dark:text-violet-300" x-text="activity.category"></span>
                                                        </div>
                                                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400" x-text="activity.description || activity.type"></p>
                                                    </div>
                                                    <span class="shrink-0 text-[10px] font-bold text-amber-600 dark:text-amber-400" x-text="activity.actor"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </article>
                            </template>
                        </div>
                    </div>
                </section>
            </template>

            <div x-show="filteredBranches().length === 0" class="p-10 text-center text-sm text-gray-400">
                Tidak ada Branch yang cocok dengan filter.
            </div>
        </div>
    </div>

    @include('partials.deployment-movement-by-stage')

    @if(($summary['data_quality']['activities_without_actor'] ?? 0) > 0)
        <p class="text-xs text-amber-600 dark:text-amber-400">
            {{ number_format($summary['data_quality']['activities_without_actor']) }} aktivitas pada tanggal ini tidak memiliki actor dan ditampilkan sebagai “Sistem/tidak tercatat”.
        </p>
    @endif
</div>

<script>
    function deploymentMovementSummary() {
        return {
            branches: @json($summary['branches']),
            stageGroups: @json($summary['stage_groups']),
            summaryBaseUrl: @json(route($reportRouteName)),
            detailMode: @json(request()->query('view') === 'staging' ? 'staging' : 'branch'),
            movementFilter: 'all',
            regionFilter: '',
            search: '',
            expandedBranches: {},
            expandedLops: {},
            matrixDetail: null,
            expandedMatrixLops: {},
            activeFilterClass: 'bg-white dark:bg-gray-700 text-blue-700 dark:text-blue-300 shadow-sm',
            inactiveFilterClass: 'text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200',

            regions() {
                return [...new Set(this.branches.map((branch) => branch.region))].sort();
            },

            init() {},

            setDetailMode(mode) {
                this.detailMode = mode;
                this.closeMatrixDetail();
                const url = new URL(window.location.href);
                url.searchParams.set('tab', 'summary');
                url.searchParams.set('view', mode);
                window.history.replaceState({}, '', url);
            },

            summaryUrl(date) {
                const url = new URL(this.summaryBaseUrl, window.location.origin);
                url.searchParams.set('tab', 'summary');
                url.searchParams.set('date', date);
                url.searchParams.set('view', this.detailMode);

                return url.toString();
            },

            filteredBranches() {
                const needle = this.search.trim().toLowerCase();

                return this.branches.filter((branch) => {
                    const matchesMovement = this.movementFilter === 'all' || branch.movement_status === this.movementFilter;
                    const matchesRegion = !this.regionFilter || branch.region === this.regionFilter;
                    const matchesSearch = !needle
                        || branch.branch.toLowerCase().includes(needle)
                        || branch.lops.some((lop) => [lop.lop_name, lop.pid_sap, lop.project_name, lop.program, lop.last_actor]
                            .some((value) => String(value || '').toLowerCase().includes(needle)));

                    return matchesMovement && matchesRegion && matchesSearch;
                });
            },

            toggleBranch(branch) {
                this.expandedBranches[branch] = !this.expandedBranches[branch];
            },

            toggleLop(branch, lopId) {
                const key = branch + '-' + lopId;
                this.expandedLops[key] = !this.expandedLops[key];
            },

            matrixSteps() {
                const steps = this.stageGroups.map((step) => ({
                    code: step.code,
                    label: step.label,
                    short_label: step.label.replace(/^Step\s+\d+\s*·\s*/i, ''),
                    substeps: step.substeps.map((substep) => ({
                        code: substep.code,
                        label: substep.label,
                    })),
                }));
                const knownStageCodes = new Set(steps.flatMap((step) => step.substeps.map((substep) => substep.code)));
                const allLops = this.branches.flatMap((branch) => branch.all_lops || branch.lops || []);
                const hasUnmapped = allLops.some((lop) => this.normalizedCurrentStage(lop.status_progress, knownStageCodes) === 'activity_other');

                if (hasUnmapped && !knownStageCodes.has('activity_other')) {
                    steps.push({
                        code: 'other',
                        label: 'Posisi Belum Terpetakan',
                        short_label: 'Belum Terpetakan',
                        substeps: [{ code: 'activity_other', label: 'Status Lainnya' }],
                    });
                }

                return steps;
            },

            matrixColumns() {
                return this.matrixSteps().flatMap((step) => step.substeps.map((substep) => ({
                    ...substep,
                    step_code: step.code,
                    step_label: step.label,
                })));
            },

            filteredMatrixBranches() {
                const needle = this.search.trim().toLowerCase();
                const columns = this.matrixColumns();
                const knownStageCodes = new Set(columns.map((column) => column.code));

                return this.branches.map((branch) => {
                    if (this.movementFilter !== 'all' && branch.movement_status !== this.movementFilter) {
                        return null;
                    }

                    if (this.regionFilter && branch.region !== this.regionFilter) {
                        return null;
                    }

                    const branchMatches = !needle || branch.branch.toLowerCase().includes(needle);
                    const branchLops = branch.all_lops || branch.lops || [];
                    const lops = branchMatches
                        ? branchLops
                        : branchLops.filter((lop) => [lop.lop_name, lop.pid_sap, lop.project_name, lop.program, lop.last_actor, lop.status_label]
                            .concat((lop.actors || []).map((actor) => actor.name))
                            .some((value) => String(value || '').toLowerCase().includes(needle)));

                    if (needle && !branchMatches && lops.length === 0) {
                        return null;
                    }

                    const cells = Object.fromEntries(columns.map((column) => [column.code, { active: [], inactive: [] }]));
                    lops.forEach((lop) => {
                        const stageCode = this.normalizedCurrentStage(lop.status_progress, knownStageCodes);
                        const movement = lop.movement_status === 'active' ? 'active' : 'inactive';
                        cells[stageCode]?.[movement].push(lop);
                    });

                    return {
                        ...branch,
                        all_lops: lops,
                        total_lops: lops.length,
                        moved_lops: lops.filter((lop) => lop.movement_status === 'active').length,
                        matrix_cells: cells,
                    };
                }).filter(Boolean);
            },

            normalizedCurrentStage(status, knownStageCodes) {
                const code = String(status || '').trim().toLowerCase();
                const aliases = {
                    drm: 'perizinan',
                    persiapan: 'persiapan_instalasi',
                };
                const normalized = aliases[code] || code;

                return knownStageCodes.has(normalized) ? normalized : 'activity_other';
            },

            matrixCell(branch, stageCode, movement) {
                return branch.matrix_cells?.[stageCode]?.[movement] || [];
            },

            openMatrixDetail(branch, column, movement) {
                const lops = this.matrixCell(branch, column.code, movement);
                if (lops.length === 0) {
                    return;
                }

                this.expandedMatrixLops = {};
                this.matrixDetail = {
                    branch: branch.branch,
                    region: branch.region,
                    step_label: column.step_label,
                    substep_label: column.label,
                    movement,
                    lops,
                };
            },

            closeMatrixDetail() {
                this.matrixDetail = null;
                this.expandedMatrixLops = {};
            },

            toggleMatrixLop(lopId) {
                this.expandedMatrixLops[lopId] = !this.expandedMatrixLops[lopId];
            },

            stageHeaderClass(stepCode) {
                return {
                    persiapan: 'bg-slate-100 text-slate-700 dark:bg-slate-900/60 dark:text-slate-300',
                    persiapan_instalasi: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-300',
                    instalasi: 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300',
                    pengukuran: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300',
                    finishing: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
                    golive: 'bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300',
                    pause: 'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
                    other: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                }[stepCode] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
            },
        };
    }
</script>
