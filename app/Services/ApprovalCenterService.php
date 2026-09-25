<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\Pt2Evidence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ApprovalCenterService
{
    /** @var array<string, Collection<int, array<string, mixed>>> */
    private array $queueCache = [];

    /**
     * Antrean hidup untuk Pusat Approval. Satu kartu mewakili satu LOP
     * (fallback project untuk eviden lama yang belum mempunyai relasi LOP).
     */
    public function queue(?int $assignedAdminId = null): Collection
    {
        $cacheKey = $assignedAdminId === null ? 'all' : 'admin:'.$assignedAdminId;

        return $this->queueCache[$cacheKey] ??= $this->buildQueue($assignedAdminId);
    }

    public function pendingCountForAdmin(int $adminId): int
    {
        return $this->queue($adminId)->sum('pending_count');
    }

    /** @return array<string, mixed> */
    public function loginSnapshot(int $adminId): array
    {
        $queue = $this->queue($adminId);

        return [
            'lop_count' => $queue->count(),
            'evidence_count' => $queue->sum('pending_count'),
            'urgent_count' => $queue->where('aging_bucket', 'overdue')->count(),
            'previews' => $queue->take(3)->map(fn (array $item) => [
                'lop_name' => $item['lop_name'],
                'source_label' => $item['source_label'],
                'pending_count' => $item['pending_count'],
                'age_label' => $item['age_label'],
            ])->values()->all(),
        ];
    }

    private function buildQueue(?int $assignedAdminId): Collection
    {
        $rows = collect();

        if ($this->hasRegularTables()) {
            $query = Evidence::query()
                ->with([
                    'uploader:id_user,name',
                    'boqItem.lop',
                    'project.assignment.admin:id_user,name',
                    'project.lop',
                ])
                ->where('status', 'pending')
                ->whereHas('project');

            if ($assignedAdminId !== null) {
                $query->whereHas('project.assignment', fn ($assignment) => $assignment
                    ->where('assigned_by', $assignedAdminId));
            }

            $rows = $rows->concat($query->get()->map(function (Evidence $evidence): array {
                $project = $evidence->project;
                $lop = $evidence->boqItem?->lop ?? $project?->lop;
                $assignment = $project?->assignment;

                return [
                    'source' => 'pt3',
                    'source_label' => 'PT3 / Reguler',
                    'group_key' => 'pt3:'.($lop?->id_lop ?? 'project-'.$evidence->project_id),
                    'project_id' => $evidence->project_id,
                    'lop_id' => $lop?->id_lop,
                    'project_name' => $project?->project_name ?: '-',
                    'lop_name' => $lop?->lop_name ?: ($project?->project_name ?: 'LOP belum bernama'),
                    'pid' => $project?->pid_sap ?: ($project?->pid ?: '-'),
                    'branch' => $lop?->branch ?: ($project?->branch ?: '-'),
                    'sto' => $lop?->sto ?: ($project?->sto ?: '-'),
                    'program' => $lop?->program_sap ?: ($project?->program ?: '-'),
                    'stage' => (string) $evidence->stage,
                    'evidence_type' => (string) $evidence->evidence_type,
                    'uploader_name' => $evidence->uploader?->name ?: '-',
                    'admin_id' => $assignment?->assigned_by,
                    'admin_name' => $assignment?->admin?->name ?: 'Belum ada admin pengawal',
                    'created_at' => $this->asCarbon($evidence->created_at),
                ];
            }));
        }

        if ($this->hasPt2Tables()) {
            $query = Pt2Evidence::query()
                ->with([
                    'uploader:id_user,name',
                    'lop.assignment.assigner:id_user,name',
                    'lop.project',
                ])
                ->where('status', 'pending')
                ->whereHas('lop');

            if ($assignedAdminId !== null) {
                $query->whereHas('lop.assignment', fn ($assignment) => $assignment
                    ->where('assigned_by', $assignedAdminId));
            }

            $rows = $rows->concat($query->get()->map(function (Pt2Evidence $evidence): array {
                $lop = $evidence->lop;
                $project = $lop?->project;
                $assignment = $lop?->assignment;

                return [
                    'source' => 'pt2',
                    'source_label' => 'PT2',
                    'group_key' => 'pt2:'.$evidence->pt2_lop_id,
                    'project_id' => $evidence->pt2_project_id,
                    'lop_id' => $evidence->pt2_lop_id,
                    'project_name' => $project?->project_name ?: '-',
                    'lop_name' => $lop?->lop_name ?: 'LOP belum bernama',
                    'pid' => $project?->pid_sap ?: ($project?->pid ?: '-'),
                    'branch' => $lop?->branch ?: '-',
                    'sto' => $lop?->sto ?: '-',
                    'program' => 'PT2',
                    'stage' => (string) $evidence->stage,
                    'evidence_type' => (string) $evidence->evidence_type,
                    'uploader_name' => $evidence->uploader?->name ?: '-',
                    'admin_id' => $assignment?->assigned_by,
                    'admin_name' => $assignment?->assigner?->name ?: 'Belum ada admin pengawal',
                    'created_at' => $this->asCarbon($evidence->created_at),
                ];
            }));
        }

        return $rows
            ->groupBy('group_key')
            ->map(fn (Collection $group) => $this->summarizeGroup($group))
            ->sortByDesc(fn (array $item) => [$item['age_hours'], $item['pending_count']])
            ->values();
    }

    /** @return array<string, mixed> */
    private function summarizeGroup(Collection $group): array
    {
        $first = $group->first();
        $oldestAt = $group->min('created_at');
        $latestAt = $group->max('created_at');
        $ageHours = $oldestAt ? (int) $oldestAt->diffInHours(now()) : 0;
        $bucket = $ageHours >= 48 ? 'overdue' : ($ageHours >= 24 ? 'warning' : 'fresh');
        $stages = $group->groupBy('stage')->map(fn (Collection $items, string $stage) => [
            'code' => $stage,
            'label' => $this->stageLabel($stage),
            'count' => $items->count(),
        ])->values();

        return array_merge($first, [
            'pending_count' => $group->count(),
            'oldest_at' => $oldestAt,
            'latest_at' => $latestAt,
            'age_hours' => $ageHours,
            'age_label' => $this->ageLabel($ageHours),
            'aging_bucket' => $bucket,
            'stages' => $stages,
            'evidence_types' => $group->pluck('evidence_type')->filter()->unique()->values(),
            'uploaders' => $group->pluck('uploader_name')->filter()->unique()->values(),
            'review_url' => $this->reviewUrl($first['source'], (int) $first['project_id'], (int) ($first['lop_id'] ?? 0)),
        ]);
    }

    private function reviewUrl(string $source, int $projectId, int $lopId): ?string
    {
        if ($source === 'pt2' && $lopId > 0) {
            return route('admin.pt2.review', $lopId);
        }

        if ($source === 'pt3') {
            return route('admin.evidences.review.project', $projectId);
        }

        return null;
    }

    private function stageLabel(string $stage): string
    {
        return match (strtolower($stage)) {
            'persiapan' => 'Persiapan / Survey',
            'perizinan' => 'Perizinan',
            'persiapan_instalasi', 'material_delivery' => 'Persiapan Instalasi',
            'instalasi', 'progress' => 'Instalasi',
            'pengukuran' => 'Pengukuran',
            'finishing', 'finish', 'redaman' => 'Finishing',
            'dismantle' => 'Dismantle',
            default => ucfirst(str_replace('_', ' ', $stage ?: 'Lainnya')),
        };
    }

    private function ageLabel(int $hours): string
    {
        if ($hours < 1) {
            return '< 1 jam';
        }

        if ($hours < 24) {
            return $hours.' jam';
        }

        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0 ? "{$days} hari {$remainingHours} jam" : "{$days} hari";
    }

    private function asCarbon($value): ?CarbonImmutable
    {
        return $value ? CarbonImmutable::parse($value) : null;
    }

    private function hasRegularTables(): bool
    {
        return Schema::hasTable('evidences')
            && Schema::hasTable('projects')
            && Schema::hasTable('pro_assign');
    }

    private function hasPt2Tables(): bool
    {
        return Schema::hasTable('pt2_evidences')
            && Schema::hasTable('pt2_lops')
            && Schema::hasTable('pt2_assignments');
    }
}
