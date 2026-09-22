<div x-show="detailMode === 'staging'" x-cloak class="space-y-4">
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-gray-200 p-5 dark:border-gray-800">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h2 class="text-sm font-black uppercase tracking-wider text-gray-800 dark:text-gray-200">Pergerakan LOP per Branch</h2>
                    <p class="mt-1 max-w-3xl text-xs leading-5 text-gray-400">
                        Kolom menunjukkan posisi Step dan Sub-step LOP saat ini. Klik angka pada ikon
                        <span class="font-bold text-emerald-600">bergerak</span> atau
                        <span class="font-bold text-rose-600">tidak bergerak</span> untuk melihat LOP, aktivitas, dan pelakunya.
                    </p>
                </div>

                <div class="grid w-full grid-cols-1 gap-2 sm:grid-cols-2 xl:flex xl:w-auto xl:items-center">
                    <div class="inline-flex h-10 items-center rounded-lg bg-gray-100 p-1 dark:bg-gray-800 sm:col-span-2 xl:col-span-1">
                        <button type="button" @click="movementFilter = 'all'" :class="movementFilter === 'all' ? activeFilterClass : inactiveFilterClass" class="rounded-lg px-3 py-1.5 text-xs font-bold">Semua</button>
                        <button type="button" @click="movementFilter = 'inactive'" :class="movementFilter === 'inactive' ? activeFilterClass : inactiveFilterClass" class="rounded-lg px-3 py-1.5 text-xs font-bold">Tidak Bergerak</button>
                        <button type="button" @click="movementFilter = 'active'" :class="movementFilter === 'active' ? activeFilterClass : inactiveFilterClass" class="rounded-lg px-3 py-1.5 text-xs font-bold">Bergerak</button>
                    </div>

                    <select x-model="regionFilter" class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 xl:w-auto">
                        <option value="">All Region</option>
                        <template x-for="region in regions()" :key="'matrix-region-' + region">
                            <option :value="region" x-text="region"></option>
                        </template>
                    </select>

                    <input type="search" x-model.debounce.250ms="search" placeholder="Cari Branch / LOP / PID / Aktor..."
                           class="h-10 w-full rounded-lg border border-gray-200 bg-white px-3 text-xs text-gray-700 placeholder:text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 xl:w-[280px]">
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-4 text-[10px] font-bold text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-1.5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="4" r="2"/><path d="M12 7v6M8 10l4-3 4 3M9 20l3-7 3 7"/></svg>
                    </span>
                    LOP tidak bergerak
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="flex h-7 w-7 items-center justify-center rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="15" cy="4" r="2"/><path d="m13.5 7.5-3.5 3.5-3 1.5M13.5 7.5l3 3 3-1.5M10 11l3 3-2 6M13 14l4 2 2 4"/></svg>
                    </span>
                    LOP bergerak
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-max border-collapse text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 dark:bg-gray-950/70 dark:text-gray-300">
                    <tr>
                        <th rowspan="3" class="sticky left-0 z-30 min-w-[190px] border-b border-r border-gray-200 bg-gray-50 px-4 py-3 align-middle text-[10px] font-black uppercase tracking-wider dark:border-gray-800 dark:bg-gray-950">
                            Branch
                        </th>
                        <template x-for="step in matrixSteps()" :key="'matrix-step-' + step.code">
                            <th :colspan="step.substeps.length * 2"
                                class="border-b border-r border-gray-200 px-3 py-2 text-center text-[10px] font-black uppercase tracking-wider dark:border-gray-800"
                                :class="stageHeaderClass(step.code)"
                                x-text="step.short_label"></th>
                        </template>
                    </tr>
                    <tr>
                        <template x-for="column in matrixColumns()" :key="'matrix-substep-' + column.code">
                            <th colspan="2" class="min-w-[112px] border-b border-r border-gray-200 px-2 py-2 text-center text-[9px] font-black uppercase leading-4 dark:border-gray-800" x-text="column.label"></th>
                        </template>
                    </tr>
                    <tr>
                        <template x-for="column in matrixColumns()" :key="'matrix-icons-' + column.code">
                            <template x-for="movement in ['inactive', 'active']" :key="column.code + '-' + movement">
                                <th class="w-14 border-b border-r border-gray-200 px-2 py-1.5 text-center dark:border-gray-800"
                                    :title="movement === 'active' ? 'LOP Bergerak' : 'LOP Tidak Bergerak'">
                                    <span class="mx-auto flex h-7 w-7 items-center justify-center rounded-md"
                                          :class="movement === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'">
                                        <svg x-show="movement === 'active'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="LOP Bergerak"><circle cx="15" cy="4" r="2"/><path d="m13.5 7.5-3.5 3.5-3 1.5M13.5 7.5l3 3 3-1.5M10 11l3 3-2 6M13 14l4 2 2 4"/></svg>
                                        <svg x-show="movement === 'inactive'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-label="LOP Tidak Bergerak"><circle cx="12" cy="4" r="2"/><path d="M12 7v6M8 10l4-3 4 3M9 20l3-7 3 7"/></svg>
                                    </span>
                                </th>
                            </template>
                        </template>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                    <template x-for="branch in filteredMatrixBranches()" :key="'matrix-branch-' + branch.branch">
                        <tr class="hover:bg-blue-50/40 dark:hover:bg-blue-950/20">
                            <th class="sticky left-0 z-20 border-r border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                                <div class="flex items-center gap-2">
                                    <span class="font-black text-gray-900 dark:text-white" x-text="branch.branch"></span>
                                    <span class="h-2 w-2 rounded-full" :class="branch.movement_status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                </div>
                                <p class="mt-1 text-[10px] font-medium text-gray-400" x-text="branch.region"></p>
                                <p class="mt-1 text-[9px] font-bold text-gray-500 dark:text-gray-400"><span x-text="branch.moved_lops"></span> / <span x-text="branch.total_lops"></span> LOP bergerak</p>
                            </th>

                            <template x-for="column in matrixColumns()" :key="branch.branch + '-' + column.code">
                                <template x-for="movement in ['inactive', 'active']" :key="branch.branch + '-' + column.code + '-' + movement">
                                    <td class="border-r border-gray-100 p-1.5 text-center dark:border-gray-800">
                                        <button type="button"
                                                @click="openMatrixDetail(branch, column, movement)"
                                                :disabled="matrixCell(branch, column.code, movement).length === 0"
                                                class="mx-auto flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-xs font-black transition disabled:cursor-default disabled:text-gray-300 dark:disabled:text-gray-700"
                                                :class="matrixCell(branch, column.code, movement).length > 0 ? (movement === 'active' ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300') : ''"
                                                :title="matrixCell(branch, column.code, movement).length > 0 ? 'Klik untuk melihat daftar LOP' : 'Tidak ada LOP'"
                                                x-text="matrixCell(branch, column.code, movement).length"></button>
                                    </td>
                                </template>
                            </template>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div x-show="filteredMatrixBranches().length === 0" class="p-10 text-center text-sm text-gray-400">
                Tidak ada Branch atau LOP yang cocok dengan filter.
            </div>
        </div>
    </div>

    <div x-show="matrixDetail" x-cloak @keydown.escape.window="closeMatrixDetail()"
         class="fixed inset-0 z-[80] flex items-center justify-center bg-gray-950/60 p-4"
         role="dialog" aria-modal="true">
        <div @click.outside="closeMatrixDetail()" class="flex max-h-[88vh] w-full max-w-4xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 p-5 dark:border-gray-800">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-black text-gray-900 dark:text-white" x-text="matrixDetail?.branch"></h3>
                        <span class="rounded-md px-2 py-1 text-[10px] font-black uppercase"
                              :class="matrixDetail?.movement === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'"
                              x-text="matrixDetail?.movement === 'active' ? 'LOP Bergerak' : 'LOP Tidak Bergerak'"></span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-bold" x-text="matrixDetail?.step_label"></span>
                        <span class="mx-1">·</span>
                        <span x-text="matrixDetail?.substep_label"></span>
                        <span class="mx-1">·</span>
                        <span x-text="(matrixDetail?.lops.length || 0) + ' LOP'"></span>
                    </p>
                </div>
                <button type="button" @click="closeMatrixDetail()" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700" aria-label="Tutup detail">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="overflow-y-auto bg-gray-50/70 p-4 dark:bg-gray-950/40">
                <div class="space-y-2">
                    <template x-for="lop in matrixDetail?.lops || []" :key="'matrix-detail-lop-' + lop.lop_id">
                        <article class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                            <button type="button" @click="toggleMatrixLop(lop.lop_id)" class="w-full p-4 text-left hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-[minmax(220px,1.4fr)_minmax(140px,0.7fr)_minmax(200px,1fr)_28px] md:items-center">
                                    <div>
                                        <p class="text-sm font-black text-gray-900 dark:text-white" x-text="lop.lop_name"></p>
                                        <p class="mt-1 text-[10px] text-gray-400"><span class="font-bold" x-text="lop.pid_sap"></span> · <span x-text="lop.project_name"></span></p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-bold uppercase text-gray-400">Posisi Saat Ini</p>
                                        <p class="mt-1 text-[11px] font-bold text-blue-600 dark:text-blue-400" x-text="lop.status_label"></p>
                                    </div>
                                    <div>
                                        <template x-if="lop.movement_status === 'active'">
                                            <div>
                                                <p class="text-[9px] font-bold uppercase text-gray-400">Aktivitas Terakhir Hari Itu</p>
                                                <p class="mt-1 text-[11px] font-bold text-gray-700 dark:text-gray-300"><span x-text="lop.last_activity_time"></span> · <span x-text="lop.last_activity_title"></span></p>
                                                <div class="mt-1 flex flex-wrap gap-1">
                                                    <template x-for="actor in lop.actors" :key="actor.id || actor.name">
                                                        <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold text-amber-700 dark:bg-amber-950/50 dark:text-amber-300" x-text="actor.name"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="lop.movement_status === 'inactive'">
                                            <p class="text-[11px] font-bold text-rose-600 dark:text-rose-400">Tidak ada aktivitas dan pelaku pada tanggal terpilih.</p>
                                        </template>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400 transition" :class="expandedMatrixLops[lop.lop_id] ? 'rotate-180' : ''"><path d="m6 9 6 6 6-6"/></svg>
                                </div>
                            </button>

                            <div x-show="expandedMatrixLops[lop.lop_id]" x-collapse x-cloak class="border-t border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/50">
                                <div x-show="lop.activities.length === 0" class="py-3 text-center text-xs font-semibold text-gray-400">
                                    Tidak ada aktivitas operasional pada tanggal terpilih.
                                </div>
                                <template x-for="activity in lop.activities" :key="activity.id">
                                    <div class="flex items-start gap-3 border-b border-gray-200 py-2 last:border-0 dark:border-gray-800">
                                        <span class="w-10 shrink-0 text-[10px] font-black text-blue-600" x-text="activity.time"></span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <p class="text-[11px] font-bold text-gray-800 dark:text-gray-200" x-text="activity.title"></p>
                                                <span class="rounded bg-violet-100 px-1.5 py-0.5 text-[9px] font-black text-violet-700 dark:bg-violet-950/60 dark:text-violet-300" x-text="activity.stage_label"></span>
                                            </div>
                                            <p class="mt-0.5 text-[10px] text-gray-400" x-text="activity.description || activity.type"></p>
                                        </div>
                                        <span class="shrink-0 text-[9px] font-bold text-amber-600 dark:text-amber-400" x-text="activity.actor"></span>
                                    </div>
                                </template>
                            </div>
                        </article>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
