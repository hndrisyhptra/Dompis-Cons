{{--
    Tabel Project ID untuk role TIF/PM. Struktur data & progressSummary()
    disamakan dengan admin/program/partials/table.blade.php, tapi kolom Aksi
    SENGAJA cuma 2 tombol kecil + tooltip (Detail Project, Tracking Progress)
    -- tanpa Assign/Edit/Upload KML/Delete seperti punya admin, karena bukan
    wewenang TIF/PM (lihat permintaan user).
--}}
<style>
    /* Tooltip kecil untuk tombol aksi -- sama dengan yang dipakai di halaman
       User Management (lihat resources/views/admin/users/index.blade.php). */
    .pm-tooltip { position: relative; }
    .pm-tooltip::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%) translateY(4px);
        background: #111827;
        color: #fff;
        font-size: 11px;
        line-height: 1.2;
        font-weight: 600;
        padding: 5px 9px;
        border-radius: 8px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .15s ease, transform .15s ease;
        z-index: 30;
    }
    .pm-tooltip:hover::after {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(0);
    }
</style>

<div class="bg-white dark:bg-gray-900 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-base font-black text-gray-900 dark:text-white">Daftar Project {{ $programName }}</h2>
            <p class="text-xs text-gray-500 mt-1">Monitoring progress &amp; assignment (read-only)</p>
        </div>
        <span class="px-3 py-1.5 rounded-lg bg-blue-100 text-blue-700 text-xs font-bold whitespace-nowrap">Total: {{ $projects->total() }} Data</span>
    </div>

    <div class="overflow-x-auto min-h-[300px]">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Project</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Lokasi</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Waspang</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Status Project</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Tahapan</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Progress</th>
                    <th class="px-5 py-3 text-center text-xs font-black uppercase text-gray-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($projects as $project)
                    @php
                        $summary = $project->progressSummary();
                        $progress = $summary['progress'];
                        $stageLabel = $summary['stageLabel'];

                        $assignmentData = $project->assignment;
                        $assignedUser = null;
                        $assignedRoleBadge = '';

                        if ($assignmentData) {
                            if ($assignmentData->waspang_id) {
                                $assignedUser = $assignmentData->waspang ?? \App\Models\User::find($assignmentData->waspang_id);
                                $assignedRoleBadge = 'Waspang';
                            } elseif ($assignmentData->teknisi_id) {
                                $assignedUser = \App\Models\User::find($assignmentData->teknisi_id);
                                $assignedRoleBadge = 'Teknisi';
                            }
                        }

                        // Section AL: warna badge tahap diselaraskan ke skema standar
                        // Project::stageColorClasses() (sama dgn admin/projects/index,
                        // project-card, project-detail, evidences/approval) -- sebelumnya
                        // if/elseif manual ini cuma kenal label 'Finishing'/'Pengukuran'/
                        // 'Instalasi', tahap lain (termasuk Persiapan Instalasi & FI-OGP
                        // Golive yg progress-nya sudah tinggi) selalu jatuh ke else -> badge
                        // MERAH seolah bermasalah, padahal tidak.
                        $stageColors = \App\Models\Project::stageColorClasses($summary['effectiveStageColor'] ?? null);
                        $stageBadge = $stageColors['badge'];
                        $progressColor = $stageColors['progress'];

                        $statusProgress = $summary['effectiveStageCode'] ?? $project->lop?->status_progress ?? '-';
                        $statusBadge = match ($statusProgress) {
                            'golive' => 'bg-emerald-100 text-emerald-700',
                            'fi_ogp_golive' => 'bg-purple-100 text-purple-700',
                            'drop' => 'bg-red-100 text-red-700',
                            'hold' => 'bg-amber-100 text-amber-700',
                            'inisiasi' => 'bg-slate-100 text-slate-600',
                            default => 'bg-gray-100 text-gray-500',
                        };

                        $boqItemsForModal = $project->boqItems->map(fn ($boq) => [
                            'designator' => $boq->designator ?? '-',
                            'item_name' => $boq->item_name ?? '-',
                            'unit' => $boq->unit ?? '-',
                            'quantity_plan' => (float) ($boq->quantity_plan ?? 0),
                        ])->values();

                        // Revisi (permintaan user): role 'tif' -- tombol Tracking
                        // Progress dihapus, diganti tombol "Review BOQ" (modal
                        // perbandingan Plan vs Survey ronde 1,2,dst vs Actual). Role
                        // 'pm' TIDAK disentuh -- Tracking Progress tetap ada, tombol
                        // Review BOQ TIDAK ditambahkan (sesuai permintaan user,
                        // khusus role tif saja).
                        $isTifRole = auth()->user()?->role === 'tif';

                        // Data BOQ Survey per ronde (BoqSurveyRound/BoqSurveyRoundItem,
                        // lihat migration 2026_09_10_140000_create_boq_survey_rounds_tables
                        // & 2026_09_10_160000_split_quantity_survey_from_quantity_actual)
                        // -- kalau LOP belum pernah Survey/Re-Survey sama sekali,
                        // $roundNumbers kosong & modal cuma tampilkan Plan vs Actual.
                        $lopIdForBoq = $project->lop?->id_lop;
                        $surveyRounds = $lopIdForBoq
                            ? \App\Models\BoqSurveyRound::where('lop_id', $lopIdForBoq)->orderBy('round_number')->get()
                            : collect();
                        $roundNumbers = $surveyRounds->pluck('round_number')->values();

                        $roundItemsMap = [];
                        if ($surveyRounds->isNotEmpty()) {
                            $roundIdToNumber = $surveyRounds->pluck('round_number', 'id');
                            $allRoundItems = \App\Models\BoqSurveyRoundItem::whereIn('boq_survey_round_id', $surveyRounds->pluck('id'))->get();
                            foreach ($allRoundItems as $ri) {
                                $rn = $roundIdToNumber[$ri->boq_survey_round_id] ?? null;
                                if ($rn === null || !$ri->boq_item_id) {
                                    continue;
                                }
                                $roundItemsMap[$ri->boq_item_id][$rn] = (float) $ri->quantity_survey;
                            }
                        }

                        $boqCompareItems = $project->boqItems->map(function ($boq) use ($roundItemsMap, $roundNumbers) {
                            $surveyPerRound = [];
                            foreach ($roundNumbers as $rn) {
                                $surveyPerRound[] = $roundItemsMap[$boq->id_boq][$rn] ?? null;
                            }

                            // Revisi (permintaan user): perhitungan Total Plan/Total
                            // Survey pada modal ini cuma menghitung item designator
                            // MATERIAL saja (Jasa dikecualikan) -- pakai konvensi yang
                            // sama persis dgn WaspangController (materialBoqItems):
                            // designator berawalan "M-" ATAU type master designator
                            // = 'material'.
                            $isMaterialItem = str_starts_with($boq->designator ?? '', 'M-')
                                || optional($boq->designatorData)->type === 'material';

                            return [
                                'designator' => $boq->designator ?? '-',
                                'item_name' => $boq->item_name ?? '-',
                                'unit' => $boq->unit ?? '-',
                                'plan' => (float) ($boq->quantity_plan ?? 0),
                                'survey' => $surveyPerRound,
                                'actual' => $boq->quantity_actual !== null ? (float) $boq->quantity_actual : null,
                                'is_material' => $isMaterialItem,
                            ];
                        })->values();

                        $latestBoqRound = $surveyRounds->last();

                        $boqCompareData = [
                            'projectName' => $project->project_name,
                            'pid' => $project->pid ?? '-',
                            'pidSap' => $project->pid_sap ?? '-',
                            'lopName' => $project->lop?->lop_name ?? '-',
                            'rounds' => $roundNumbers,
                            'deviationPercent' => $latestBoqRound?->deviation_percent,
                            'items' => $boqCompareItems,
                        ];

                        $detailPayload = [
                            'projectId' => $project->id_project,
                            'projectName' => $project->project_name,
                            'pid' => $project->pid ?? '-',
                            'pidSap' => $project->pid_sap ?? '-',
                            'program' => $project->program ?? '-',
                            'branch' => $project->lop?->branch ?? '-',
                            'sto' => $project->lop?->sto ?? '-',
                            'idIhld' => $project->lop?->id_ihld ?? '-',
                            'lopName' => $project->lop?->lop_name ?? '-',
                            'mitra' => $project->mitra_name ?? '-',
                            'executionType' => $project->execution_type ?? '-',
                            'statusLabel' => $statusOptions[$statusProgress] ?? $stageLabel,
                            'statusBadgeClass' => $statusBadge,
                            'stageLabel' => $stageLabel,
                            'stageBadgeClass' => $stageBadge,
                            'progress' => $progress,
                            'assignedName' => $assignedUser->name ?? '',
                            'assignedRole' => $assignedRoleBadge,
                            'trackingUrl' => route('admin.projects.tracking', $project->id_project),
                            'items' => $boqItemsForModal,
                        ];
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                        <td class="px-5 py-4">
                            <div class="min-w-[220px]">
                                <p class="font-black text-gray-900 dark:text-white leading-snug">{{ $project->project_name }}</p>
                                <p class="text-xs text-gray-500 mt-1">PID: {{ $project->pid ?? '-' }} · {{ strtoupper($project->execution_type ?? '-') }}</p>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-gray-800 dark:text-gray-100">{{ $project->lop?->branch ?? '-' }}</p>
                            <p class="text-xs text-gray-500 mt-1">STO {{ $project->lop?->sto ?? '-' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            @if($assignedUser)
                                <p class="font-bold text-gray-900 dark:text-white">{{ $assignedUser->name }}</p>
                                <p class="text-xs text-green-600 font-bold">Assigned ({{ $assignedRoleBadge }})</p>
                            @else
                                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-bold">Belum diassign</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-3 py-1 rounded-full {{ $statusBadge }} text-xs font-black">{{ $statusOptions[$statusProgress] ?? $stageLabel }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-3 py-1 rounded-full {{ $stageBadge }} text-xs font-black">{{ $stageLabel }}</span>
                        </td>
                        <td class="px-5 py-4 min-w-[150px]">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-gray-500">Progress</span>
                                <span class="text-sm font-black text-gray-900 dark:text-white">{{ $progress }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full rounded-full {{ $progressColor }}" style="width: {{ $progress }}%"></div>
                            </div>
                        </td>

                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button"
                                        @click="open(@js($detailPayload))"
                                        class="w-8 h-8 rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 transition inline-flex items-center justify-center pm-tooltip"
                                        data-tooltip="Detail Project">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>

                                @unless($isTifRole)
                                <a href="{{ route('admin.projects.tracking', $project->id_project) }}"
                                   class="w-8 h-8 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition inline-flex items-center justify-center pm-tooltip"
                                   data-tooltip="Tracking Progress">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25A7.5 7.5 0 1119.5 10.5z" />
                                    </svg>
                                </a>
                                @endunless

                                @if($isTifRole)
                                {{-- Revisi (permintaan user): tombol BARU "Review BOQ" khusus
                                role tif -- buka modal perbandingan Plan vs Survey per
                                ronde vs Actual (lihat pm.program.partials.boq-compare-modal). --}}
                                <button type="button"
                                        @click="$dispatch('open-boq-compare', @js($boqCompareData))"
                                        class="w-8 h-8 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition inline-flex items-center justify-center pm-tooltip"
                                        data-tooltip="Review BOQ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </button>
                                @endif

                                {{-- Revisi (permintaan user): tombol BARU "Timeline" -- halaman
                                kronologi horizontal+vertical lengkap dgn eviden foto. --}}
                                <a href="{{ route('admin.projects.timeline', $project->id_project) }}"
                                   class="w-8 h-8 rounded-lg border border-purple-200 bg-purple-50 text-purple-700 hover:bg-purple-100 transition inline-flex items-center justify-center pm-tooltip"
                                   data-tooltip="Timeline">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6.75v.008M10.5 12v.008M15 17.25v.008" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">Belum ada project {{ $programName }}.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($projects->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
            {{ $projects->links() }}
        </div>
    @endif
</div>
