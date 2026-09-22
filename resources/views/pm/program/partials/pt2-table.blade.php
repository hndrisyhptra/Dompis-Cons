{{--
    Tabel "Project ID > PT 2" (permintaan user 2026-09-17) utk role TIF/PM --
    versi read-only sederhana, POLA SAMA dgn pm/program/partials/table.blade.php
    (Regular), tapi:
    - 1 baris = 1 Pt2Lop (bukan 1 Project spt Regular, krn 1 project PT2 bisa
      punya banyak LOP -- lihat catatan ProgramController::pt2()).
    - Tombol Timeline memakai route read-only lintas-role `pt2.timeline`.
      Detail LOP tetap memakai modal read-only pt2-detail-modal.blade.php.
    - Progress & label tahap dari Pt2Lop::progressSummary() (Section BM).
--}}
<style>
    /* Tooltip kecil utk tombol aksi -- sama dgn pm/program/partials/table.blade.php
       (Regular), disalin di sini krn partial ini berdiri sendiri (tidak
       nge-include table.blade.php punya Regular). */
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
            <h2 class="text-base font-black text-gray-900 dark:text-white">Daftar LOP {{ $programName }}</h2>
            <p class="text-xs text-gray-500 mt-1">Monitoring progress &amp; assignment (read-only)</p>
        </div>
        <span class="px-3 py-1.5 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold whitespace-nowrap">Total: {{ $lops->total() }} Data</span>
    </div>

    <div class="overflow-x-auto min-h-[300px]">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-950 border-b border-gray-200 dark:border-gray-800">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Project</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Lokasi</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Teknisi</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Status Progress</th>
                    <th class="px-5 py-3 text-left text-xs font-black uppercase text-gray-500">Progress</th>
                    <th class="px-5 py-3 text-center text-xs font-black uppercase text-gray-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($lops as $lop)
                    @php
                        $project = $lop->project;
                        $summary = $lop->progressSummary();
                        $progress = (int) ($summary['progress'] ?? 0);
                        $stageLabel = $summary['stageLabel'] ?? '-';
                        $stageBadge = $summary['badge'] ?? 'bg-gray-100 text-gray-500';
                        $progressColor = $summary['color'] ?? 'bg-gray-400';

                        $teknisi = $lop->assignment?->teknisi;
                        $branchResolved = $lop->branch ?: ($project->branch ?? '-');

                        $statusProgress = strtolower(trim((string) $lop->status_progress));
                        $statusBadge = match ($statusProgress) {
                            'golive' => 'bg-emerald-100 text-emerald-700',
                            'fi_ogp_golive' => 'bg-purple-100 text-purple-700',
                            'drop' => 'bg-red-100 text-red-700',
                            'finishing' => 'bg-indigo-100 text-indigo-700',
                            'instalasi' => 'bg-blue-100 text-blue-700',
                            'survey' => 'bg-amber-100 text-amber-700',
                            default => 'bg-slate-100 text-slate-600',
                        };

                        $boqItemsForModal = $lop->boqItems->map(fn ($boq) => [
                            'designator' => $boq->designator ?? '-',
                            'item_name' => $boq->item_name ?? '-',
                            'unit' => $boq->unit ?? '-',
                            'quantity_plan' => (float) ($boq->quantity_plan ?? 0),
                        ])->values();

                        $detailPayload = [
                            'projectName' => $project->project_name ?? '-',
                            'pid' => $project->pid ?? '-',
                            'pidSap' => $project->pid_sap ?? ($lop->pid_sap ?? '-'),
                            'branch' => $branchResolved,
                            'sto' => $lop->sto ?? '-',
                            'idIhld' => $lop->id_ihld ?? '-',
                            'lopName' => $lop->lop_name ?? '-',
                            'mitra' => $lop->mitra_name ?? ($project->mitra_name ?? '-'),
                            'statusLabel' => $statusOptions[$statusProgress] ?? ucfirst($statusProgress),
                            'statusBadgeClass' => $statusBadge,
                            'stageLabel' => $stageLabel,
                            'stageBadgeClass' => $stageBadge,
                            'progress' => $progress,
                            'assignedName' => $teknisi->name ?? '',
                            'items' => $boqItemsForModal,
                        ];
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 transition">
                        <td class="px-5 py-4">
                            <div class="min-w-[220px]">
                                <p class="font-black text-gray-900 dark:text-white leading-snug">{{ $project->project_name ?? '-' }}</p>
                                <p class="text-xs text-gray-500 mt-1">PID: {{ $project->pid ?? '-' }} · LOP: {{ $lop->lop_name ?? '-' }}</p>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-gray-800 dark:text-gray-100">{{ $branchResolved }}</p>
                            <p class="text-xs text-gray-500 mt-1">STO {{ $lop->sto ?? '-' }}</p>
                        </td>
                        <td class="px-5 py-4">
                            @if($teknisi)
                                <p class="font-bold text-gray-900 dark:text-white">{{ $teknisi->name }}</p>
                                <p class="text-xs text-green-600 font-bold">Assigned</p>
                            @else
                                <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-xs font-bold">Belum diassign</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <span class="px-3 py-1 rounded-full {{ $statusBadge }} text-xs font-black">{{ $statusOptions[$statusProgress] ?? ucfirst($statusProgress) }}</span>
                        </td>
                        <td class="px-5 py-4 min-w-[150px]">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-gray-500">{{ $stageLabel }}</span>
                                <span class="text-sm font-black text-gray-900 dark:text-white">{{ $progress }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                <div class="h-full rounded-full {{ $progressColor }}" style="width: {{ $progress }}%"></div>
                            </div>
                        </td>

                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            <button type="button"
                                    @click="open(@js($detailPayload))"
                                    class="w-8 h-8 rounded-lg border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 transition inline-flex items-center justify-center pm-tooltip"
                                    data-tooltip="Detail LOP">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                            <a href="{{ route('pt2.timeline', $lop->id_pt2_lop) }}"
                               class="w-8 h-8 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition inline-flex items-center justify-center pm-tooltip"
                               data-tooltip="Timeline">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">Belum ada LOP {{ $programName }}.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($lops->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50">
            {{ $lops->links() }}
        </div>
    @endif
</div>
