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

                        if ($progress == 100) { $stageBadge = 'bg-green-100 text-green-700'; $progressColor = 'bg-green-600'; }
                        elseif ($stageLabel === 'Finishing') { $stageBadge = 'bg-purple-100 text-purple-700'; $progressColor = 'bg-purple-600'; }
                        elseif ($stageLabel === 'Pengukuran') { $stageBadge = 'bg-blue-100 text-blue-700'; $progressColor = 'bg-blue-600'; }
                        elseif ($stageLabel === 'Instalasi') { $stageBadge = 'bg-yellow-100 text-yellow-700'; $progressColor = 'bg-yellow-600'; }
                        else { $stageBadge = 'bg-red-100 text-red-700'; $progressColor = 'bg-red-600'; }

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

                                <a href="{{ route('admin.projects.tracking', $project->id_project) }}"
                                   class="w-8 h-8 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition inline-flex items-center justify-center pm-tooltip"
                                   data-tooltip="Tracking Progress">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25s-7.5-4.108-7.5-11.25A7.5 7.5 0 1119.5 10.5z" />
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
