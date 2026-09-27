<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApprovalCenterService
{
    /** @var array<string, Collection<int, array<string, mixed>>> */
    private array $queueCache = [];

    public function __construct(private readonly DatabaseSchemaInspector $schema) {}

    /**
     * Antrean hidup untuk Monitoring Approval. Satu baris mewakili satu LOP
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
            $hasAssignedBy = $this->schema->hasColumn('pro_assign', 'assigned_by');
            $assignmentIds = DB::table('pro_assign')
                ->selectRaw('project_id, MIN(id_proassign) AS assignment_id')
                ->groupBy('project_id');
            $projectLopIds = DB::table('lops')
                ->selectRaw('project_id, MIN(id_lop) AS lop_id')
                ->groupBy('project_id');

            $query = DB::table('evidences as evidence')
                ->join('projects as project', 'project.id_project', '=', 'evidence.project_id')
                ->leftJoin('boq_items as boq', 'boq.id_boq', '=', 'evidence.boq_item_id')
                ->leftJoin('lops as boq_lop', 'boq_lop.id_lop', '=', 'boq.lop_id')
                ->leftJoinSub($projectLopIds, 'project_lop_ids', 'project_lop_ids.project_id', '=', 'project.id_project')
                ->leftJoin('lops as project_lop', 'project_lop.id_lop', '=', 'project_lop_ids.lop_id')
                ->leftJoinSub($assignmentIds, 'assignment_ids', 'assignment_ids.project_id', '=', 'project.id_project')
                ->leftJoin('pro_assign as assignment', 'assignment.id_proassign', '=', 'assignment_ids.assignment_id')
                ->leftJoin('users as uploader', 'uploader.id_user', '=', 'evidence.uploaded_by')
                ->where('evidence.status', 'pending');

            if ($hasAssignedBy) {
                $query->leftJoin('users as admin', 'admin.id_user', '=', 'assignment.assigned_by');
            }

            if ($assignedAdminId !== null) {
                if (! $hasAssignedBy) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereExists(fn ($assigned) => $assigned
                        ->selectRaw('1')
                        ->from('pro_assign as scoped_assignment')
                        ->whereColumn('scoped_assignment.project_id', 'project.id_project')
                        ->where('scoped_assignment.assigned_by', $assignedAdminId));
                }
            }

            $rows = $rows->concat($query->select([
                'evidence.project_id', 'evidence.stage', 'evidence.evidence_type', 'evidence.created_at',
                'project.project_name',
                $this->optionalColumn('projects', 'project', 'pid', 'pid'),
                $this->optionalColumn('projects', 'project', 'pid_sap', 'pid_sap'),
                $this->optionalColumn('projects', 'project', 'branch', 'project_branch'),
                $this->optionalColumn('projects', 'project', 'sto', 'project_sto'),
                $this->optionalColumn('projects', 'project', 'program', 'project_program'),
                DB::raw('COALESCE(boq_lop.id_lop, project_lop.id_lop) AS lop_id'),
                DB::raw('COALESCE(boq_lop.lop_name, project_lop.lop_name) AS lop_name'),
                $this->coalesceColumns('lops', ['boq_lop', 'project_lop'], 'branch', 'lop_branch'),
                $this->coalesceColumns('lops', ['boq_lop', 'project_lop'], 'sto', 'lop_sto'),
                $this->coalesceColumns('lops', ['boq_lop', 'project_lop'], 'program_sap', 'lop_program'),
                'uploader.name as uploader_name',
                $hasAssignedBy ? 'assignment.assigned_by as admin_id' : DB::raw('NULL AS admin_id'),
                $hasAssignedBy ? 'admin.name as admin_name' : DB::raw('NULL AS admin_name'),
            ])->get()->map(function ($evidence): array {
                return [
                    'source' => 'pt3',
                    'source_label' => 'PT3 / Reguler',
                    'group_key' => 'pt3:'.($evidence->lop_id ?? 'project-'.$evidence->project_id),
                    'project_id' => $evidence->project_id,
                    'lop_id' => $evidence->lop_id,
                    'project_name' => $evidence->project_name ?: '-',
                    'lop_name' => $evidence->lop_name ?: ($evidence->project_name ?: 'LOP belum bernama'),
                    'pid' => $evidence->pid_sap ?: ($evidence->pid ?: '-'),
                    'branch' => $evidence->lop_branch ?: ($evidence->project_branch ?: '-'),
                    'sto' => $evidence->lop_sto ?: ($evidence->project_sto ?: '-'),
                    'program' => $evidence->lop_program ?: ($evidence->project_program ?: '-'),
                    'stage' => (string) $evidence->stage,
                    'evidence_type' => (string) $evidence->evidence_type,
                    'uploader_name' => $evidence->uploader_name ?: '-',
                    'admin_id' => $evidence->admin_id,
                    'admin_name' => $evidence->admin_name ?: 'Belum ada admin pengawal',
                    'created_at' => $this->asCarbon($evidence->created_at),
                ];
            }));
        }

        if ($this->hasPt2Tables()) {
            $assignmentIds = DB::table('pt2_assignments')
                ->selectRaw('pt2_lop_id, MIN(id_pt2_assignment) AS assignment_id')
                ->groupBy('pt2_lop_id');

            $query = DB::table('pt2_evidences as evidence')
                ->join('pt2_lops as lop', 'lop.id_pt2_lop', '=', 'evidence.pt2_lop_id')
                ->leftJoin('pt2_projects as project', 'project.id_pt2_project', '=', 'lop.pt2_project_id')
                ->leftJoinSub($assignmentIds, 'assignment_ids', 'assignment_ids.pt2_lop_id', '=', 'lop.id_pt2_lop')
                ->leftJoin('pt2_assignments as assignment', 'assignment.id_pt2_assignment', '=', 'assignment_ids.assignment_id')
                ->leftJoin('users as uploader', 'uploader.id_user', '=', 'evidence.uploaded_by')
                ->leftJoin('users as admin', 'admin.id_user', '=', 'assignment.assigned_by')
                ->where('evidence.status', 'pending');

            if ($assignedAdminId !== null) {
                $query->whereExists(fn ($assigned) => $assigned
                    ->selectRaw('1')
                    ->from('pt2_assignments as scoped_assignment')
                    ->whereColumn('scoped_assignment.pt2_lop_id', 'lop.id_pt2_lop')
                    ->where('scoped_assignment.assigned_by', $assignedAdminId));
            }

            $rows = $rows->concat($query->select([
                'evidence.pt2_project_id', 'evidence.pt2_lop_id', 'evidence.stage',
                'evidence.evidence_type', 'evidence.created_at', 'lop.lop_name', 'lop.branch', 'lop.sto',
                'project.project_name', 'project.pid', 'project.pid_sap', 'uploader.name as uploader_name',
                'assignment.assigned_by as admin_id', 'admin.name as admin_name',
            ])->get()->map(function ($evidence): array {
                return [
                    'source' => 'pt2',
                    'source_label' => 'PT2',
                    'group_key' => 'pt2:'.$evidence->pt2_lop_id,
                    'project_id' => $evidence->pt2_project_id,
                    'lop_id' => $evidence->pt2_lop_id,
                    'project_name' => $evidence->project_name ?: '-',
                    'lop_name' => $evidence->lop_name ?: 'LOP belum bernama',
                    'pid' => $evidence->pid_sap ?: ($evidence->pid ?: '-'),
                    'branch' => $evidence->branch ?: '-',
                    'sto' => $evidence->sto ?: '-',
                    'program' => 'PT2',
                    'stage' => (string) $evidence->stage,
                    'evidence_type' => (string) $evidence->evidence_type,
                    'uploader_name' => $evidence->uploader_name ?: '-',
                    'admin_id' => $evidence->admin_id,
                    'admin_name' => $evidence->admin_name ?: 'Belum ada admin pengawal',
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
        return $this->schema->hasTables(['evidences', 'projects', 'pro_assign', 'boq_items', 'lops', 'users']);
    }

    private function hasPt2Tables(): bool
    {
        return $this->schema->hasTables(['pt2_evidences', 'pt2_lops', 'pt2_projects', 'pt2_assignments', 'users']);
    }

    private function optionalColumn(string $table, string $tableAlias, string $column, string $resultAlias)
    {
        return $this->schema->hasColumn($table, $column)
            ? "{$tableAlias}.{$column} as {$resultAlias}"
            : DB::raw("NULL AS {$resultAlias}");
    }

    /** @param array<int, string> $tableAliases */
    private function coalesceColumns(string $table, array $tableAliases, string $column, string $resultAlias)
    {
        if (! $this->schema->hasColumn($table, $column)) {
            return DB::raw("NULL AS {$resultAlias}");
        }

        $columns = implode(', ', array_map(fn (string $alias) => "{$alias}.{$column}", $tableAliases));

        return DB::raw("COALESCE({$columns}) AS {$resultAlias}");
    }
}
